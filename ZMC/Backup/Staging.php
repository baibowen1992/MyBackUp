<?














class ZMC_Backup_Staging extends ZMC_Backup
{
	protected $zmc_type_class = 'ZMC_Type_Staging';

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		ZMC_HeaderFooter::$instance->header($pm, 'Backup', 'ZMC - Interim Staging of Backup Data', 'staging');
		$pm->addDefaultInstruction('Edit interim staging areas for backup data.');
		$stagingPage = new self($pm);
		$stagingPage->runState($pm);
		$stagingPage->getPaginator($pm);

		if (isset($pm->binding) && !empty($pm->binding['config_name']))
		{
			
			$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/holding_list/' . $pm->binding['config_name']));
			$lines = explode("\n", $result['holding_list']);
			$lines[0] = 'KiB level outdated Host      Object/Path/Directory';
			$pm->holding_list = ((count($lines) > 1 && !empty($lines[1])) ? trim(implode("\n", $lines)) : 'The staging area of this backup<br />set is currently empty.');
		}
		
		if($pm->binding['dev_meta']['_key_name'] === 'changer_ndmp'){
			$pm->addMessage("This version of ZMC does not support {$pm->binding['dev_meta']['name']} on this page.");
			return 'MessageBox';
		}
		if($pm->binding['holdingdisk_list']['zmc_default_holding']['used_space']){
				$pm->enableFlush = true;
			exec("pgrep -f amdump", $pids);
			if(!empty($pids))
				$pm->enableFlush = false;
			
			exec("pgrep -f amflush", $pids);
			if(!empty($pids))
				$pm->enableFlush = false;
			
			exec("pgrep -f amvault", $pids);
			if(!empty($pids)) 
				$pm->enableFlush = false;
			
			if(!$pm->enableFlush)
				$pm->addWarning('"Flush" is temporarily disabled because backup/vault/flush in running.');
		}
		
		return 'BackupStaging';
	}

	protected function validateForm(ZMC_Registry_MessageBox $pm)
	{ }

	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if (!empty($state))
			$pm->state = $state;

		switch($pm->state)
		{
			case 'Prune & Flush':
				$pm->addError("@TODO: " . $pm->state);
				break;

			case 'Update': 
				if(isset($_POST['holdingdisk_list:zmc_default_holding:directory']) && $_POST['holdingdisk_list:zmc_default_holding:directory'] != '')
				{   
					$_POST['holdingdisk_list:zmc_default_holding:directory'] = rtrim( $_POST['holdingdisk_list:zmc_default_holding:directory'], "/ ");
					if(!preg_match("/(.*)?[\/]?".$_POST['config_name']."[\/]?$/",$_POST['holdingdisk_list:zmc_default_holding:directory']))
						$_POST['holdingdisk_list:zmc_default_holding:directory'] = $_POST['holdingdisk_list:zmc_default_holding:directory']."/".$_POST['config_name']; 
					
				}   
				$this->updateAdd($pm, true);
				break;

			case 'Flush':
				
				$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/flush/' . $pm->selected_name));
				$pm->merge($result);
				return $this->runState($pm, 'Edit');

			default:
			case 'Refresh Table': 
			case 'Refresh': 
			case '':
			case 'Edit':
				if (!$this->getSelectedBinding($pm))
				{
					$pm->state = '';
					break;
				}
				if (!strncmp($pm->binding['_key_name'], 'ndmpchanger', 11))
				{
					$deviceType = ZMC_Type_Devices::get($pm->binding['_key_name']);
					$pm->addWarnError($deviceType['name'] . ' does not support staging areas.');
					if (ZMC::$registry->safe_mode)
					{
						$pm->state = '';
						break;
					}
				}
				$pm->form_type = call_user_func(array($this->zmc_type_class, 'get'), $pm->binding['_key_name']);
				$this->checkHoldingDiskSpace($pm);
				$this->buildFormWrapper($pm);
				break;

			case 'Cancel':
				$pm->state = '';
				ZMC_BackupSet::cancelEdit();
				$pm->addWarning("Edit/Add cancelled.");
				break;
		}
	}

	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		$this->getBindingList($pm);
		if (empty($pm->binding_list))
			return;

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('config_name', false);

		$flattened =& ZMC::flattenArrays($pm->binding_list);
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'config_name',
			'_key_name',
			'autoflush',
			'holdingdisk_list:zmc_default_holding:directory',
			'holdingdisk_list:zmc_default_holding:partition_total_space',
			'holdingdisk_list:zmc_default_holding:use',

			'private:last_modified_time',
			'private:last_modified_by',
			'comment'
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
		if (!empty($pm->rows))
			foreach($pm->rows as &$row)
			{
				$row = ZMC_DeviceFilter::filter($pm, 'output', $row);
				
				
				if (!empty($pm->sets))
					unset($pm->sets[$row['config_name']]);
			}
	}

	public static function getDefaultStagingSize(ZMC_Registry_MessageBox $pm)
	{
		return $pm->binding['tapetype']['length'];
	}

	public static function checkHoldingDiskSpace($pm){
		if (isset($pm->binding) && !empty($pm->binding['config_name']))
		{
			
			$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/holding_list/' . $pm->binding['config_name']));
			$lines = explode("\n", $result['holding_list']);
			$lines[0] = 'KiB level outdated Host      Object/Path/Directory';
			$pm->holding_list = ((count($lines) > 1 && !empty($lines[1])) ? trim(implode("\n", $lines)) : 'The staging area of this backup<br />set is currently empty.');
			if(!preg_match("/The staging area of this backup/", $pm->holding_list)){
				if(isset($pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes']))
					$pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes'] =  " readonly onfocus='alert(\"You are not allowed to change staging location because this staging area is not empty!\")'";
			}else{
				if(isset($pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes']))
					$pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes'] =  " ";
			}
		}

	}
}
