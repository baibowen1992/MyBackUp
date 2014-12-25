<?














class ZMC_Backup_Staging extends ZMC_Backup
{
	protected $zmc_type_class = 'ZMC_Type_Staging';

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - 备份数据临时缓存', 'staging');
		$pm->addDefaultInstruction('为备份数据设置临时缓存区.');
		$stagingPage = new self($pm);
		$stagingPage->runState($pm);
		$stagingPage->getPaginator($pm);

		if (isset($pm->binding) && !empty($pm->binding['config_name']))
		{
			
			$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/holding_list/' . $pm->binding['config_name']));
			$lines = explode("\n", $result['holding_list']);
			$lines[0] = 'KiB level outdated Host      Object/Path/Directory';
			$pm->holding_list = ((count($lines) > 1 && !empty($lines[1])) ? trim(implode("\n", $lines)) : '该备份设置的缓存空间为空。');
		}
		
		if($pm->binding['dev_meta']['_key_name'] === 'changer_ndmp'){
			$pm->addMessage("该版本在本页暂时不支持 {$pm->binding['dev_meta']['name']} ");
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
				$pm->addWarning("编辑/新增  取消.");
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
			$pm->holding_list = ((count($lines) > 1 && !empty($lines[1])) ? trim(implode("\n", $lines)) : '这个备份集的缓存<br />目前为空.');
			if(!preg_match("/这个备份集的缓存/", $pm->holding_list)){
				if(isset($pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes']))
					$pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes'] =  " readonly onfocus='alert(\"不允许改变该缓存目录，因为该目录非空！\")'";
			}else{
				if(isset($pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes']))
					$pm->form_type['form']['holdingdisk_list:zmc_default_holding:directory']['attributes'] =  " ";
			}
		}

	}
}
