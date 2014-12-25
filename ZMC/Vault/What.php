<?













class ZMC_Vault_What extends ZMC_Vault
{
	private $confDles = null;
	
	protected $zmc_type_class = 'ZMC_Type_What';
	protected $defaultEditAction = 'Create';
	
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Vault', 'ZMC - What to Vault', 'what');
		$whatPage = new self($pm);
		$whatPage->ymlFilePath = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'Vault-default.yml';
		
		if (empty($pm->selected_name)) {
			$pm->addWarning("Please select a backup set above.");
			return 'MessageBox';
		}
		
		$saved_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved';
		if(!is_dir($saved_path))
			mkdir($saved_path, 0777, true);
		$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs';
		if(!is_dir($logs_path))
			mkdir($logs_path, 0777, true);
		
		$pm->advanced_options_title = 'Advanced Options for This Object Only';
		$pm->raw_binding = ZMC_Yaml_sfYaml::load(ZMC::$registry->etc_amanda . $pm->selected_name .  '/binding-' . $pm->edit['profile_name'] . '.yml'); 
		$pm->users = ZMC_User::$users; 
		$template = $whatPage->runState($pm);
		$whatPage->loadDisklist($pm); 

		$licenses = ZMC_License::readLicenses($pm);
		if ($licenses['licenses']['zmc']['Remaining']['vault'] <= 0) {
			$pm->addError("You do not have the license for 'Vault' feature. Please contact Zmanda Support for more information.");
			return 'MessageBox';
		}
		
		$whatPage->getPaginator($pm);
		
		ZMC_HeaderFooter::$instance->injectYuiCode(<<<EOD
		
			zmcRegistry.adjust_vault_datetime_pickers()
			
			zmcRegistry.adjust_vault_backup_run_range_div()
EOD
			);
		
		return empty($template) ? 'VaultWhat' : $template;
	}
	
	
	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{		
  		if ($pm->isErrors())
			return;
  		
  		if (!empty($state))
  			$pm->state = $state;
  		$redirectPage = '';
  		
  		if($pm->state === 'Create' && file_exists($this->ymlFilePath))
  			$pm->state = 'Edit';
  		
 		switch($pm->state)
		{	
			case 'Create':
			case 'Cancel':
				$pm->vault_job = self::$defaults;
				unlink($this->ymlFilePath);
				break;
				
			case 'Next':	
				if(!empty($_POST['vault_type']) && !empty($_POST['vault_level'])){ 
 					$pm->vault_job['vault_type'] = $_POST['vault_type'];
 					if($_POST['vault_type'] === 'time_frame'){
 						$pm->vault_job['vault_start_date'] = $_POST['vault_start_date'];
 						$pm->vault_job['vault_start_time'] = $_POST['vault_start_time'];
 						$pm->vault_job['vault_end_date'] = $_POST['vault_end_date'];
 						$pm->vault_job['vault_end_time'] = $_POST['vault_end_time'];
 					}
 					if($_POST['vault_type'] === 'last_x_days')
 						$pm->vault_job['num_of_days'] = $_POST['num_of_days'];
 					
 					$pm->vault_job['vault_level'] = $_POST['vault_level'];
 					
 					file_put_contents($this->ymlFilePath, ZMC_Yaml_sfYaml::dump($pm->vault_job));
 					$pm->state = 'Edit';
 					$redirectPage = ZMC::redirectPage('ZMC_Vault_Where', $pm);
				}
				break;
				
			case 'Edit':
				$pm->vault_job = ZMC_Yaml_sfYaml::load($this->ymlFilePath);
				break;	
					
			default:
				break;
		}
		return $redirectPage;
	}
	
	public function loadDisklist(ZMC_Registry_MessageBox $pm)
	{
		$pm->dles = null;
		try
		{
			if (empty($this->confDles))
			{
				$this->confDles = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => "/conf/read/{$pm->selected_name}",
						'data' => array(
								'what' => 'disklist.conf',
						)
				));
				$pm->merge($this->confDles);
				
			}
	
			if ($pm->offsetExists('conf'))
				$pm->offsetUnset('conf');
	
			if (!empty($this->confDles['conf']) && !empty($this->confDles['conf']['dle_list']))
				foreach($this->confDles['conf']['dle_list'] as $id => &$dle)
				{
					$dle['natural_key'] = $id;
					$pm->dles[$id] =& $dle;
				}
		}
		catch (Exception $e)
		{
			$pm->addError("An unexpected problem occurred while reading and processing the object list '{$pm->selected_name}': $e");
		}
	}
	
	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		$this->getDeviceList($pm); 
		if (empty($pm->device_profile_list))
			return;

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('id', false);

		$flattened =& ZMC::flattenArrays($pm->device_profile_list);
		foreach($flattened as &$device)
			$device = ZMC_DeviceFilter::filter($pm, 'output', $device);
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'_key_name',
			'id',
			'stderr',
			'changer:changerdev',
			'changer:comment',
			'private:last_modified_time',
			'private:last_modified_by',
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}

	public function &getDeviceList(ZMC_Registry_MessageBox $pm, $name = '')
	{
		$return = false;
		if (empty($pm->device_profile_list))
		{
			try
			{
				$pm->device_profile_list = array();
				$result = ZMC_Yasumi::operation($pm, array(
					'pathInfo' => "/Device-Profile/read_profiles",
					'data' => array(
						'message_type' => 'admin devices read',
					),
				));
				unset($result['request']);
				$pm->merge($result);
				ZMC_Type_Devices::addExpireWarnings($pm);
				ZMC_DeviceFilter::filterNamedList($pm, $pm->device_profile_list);
			}
			catch(Exception $e)
			{
				$pm->addInternal("Unable to read device list: $e");
				return $return;
			}
		}
		if (empty($name))
			return $pm->device_profile_list;
		if (isset($pm->device_profile_list[$name]))
		{
			$device =& $pm->device_profile_list[$name];
			return $device;
		}
		return $return;
	}

	public static function &get(ZMC_Registry_MessageBox $pm, $name)
	{
		if (empty(self::$instance))
			self::$instance = new self($pm);
		return self::$instance->getDeviceList($pm, $name);
	}
	
	protected function edit(ZMC_Registry_MessageBox $pm)
	{
		if (!$this->getSelectedBinding($pm))
			return;
	
		if ($pm->binding['dev_meta']['media_type'] === 'tape')
			$this->inventory($pm);
	
		$pm->tapeListPm = new ZMC_Registry_MessageBox(array(
				'level0_tapelist' => $pm->binding['schedule']['level0_tapelist'],
				'tombstone' => $pm->tombstone,
				'subnav' => $pm->subnav));
		if ($pm->binding['dev_meta']['media_type'] === 'tape')
			$groupedTapelist = $this->paginateTapeMedia($pm);
		else
			$this->paginateVtapeMedia($pm);
	
		switch($d = $pm->binding['dev_meta']['device_type'])
		{
			case ZMC_Type_Devices::TYPE_SINGLE_TAPE:
			case ZMC_Type_Devices::TYPE_MULTIPLE_TAPE:
				$labelList = $this->calculateChangerView($pm, $groupedTapelist);
				break;
			case ZMC_Type_Devices::TYPE_ATTACHED:
			case ZMC_Type_Devices::TYPE_CLOUD:
				break;
			default:
				throw new ZMC_Exception("This version of ZMC does not support device type $d on this page.");
		}
	
		if (!empty($labelList))
		{
			$pm->labelListPm = new ZMC_Registry_MessageBox(array('tombstone' => $pm->tombstone, 'subnav' => $pm->subnav, 'url' => $pm->url, 'label_status' => $pm->label_status));
			$flattened =& ZMC::flattenArrays($labelList, false);
			$paginator = new ZMC_Paginator_Array(
					$pm->labelListPm,
					$flattened,
					$pm->labelListPm->cols = array(
							'slot',
							'barcode',
							'last_used',
							'label',
					),
					'sort_labellist',
					20
			);
			$paginator->createColUrls($pm->labelListPm);
			$pm->labelListPm->rows = $paginator->get();
			$pm->labelListPm->goto = $paginator->shortFooter($pm->url);
		}
	}
	
	protected function paginateVtapeMedia($pm)
	{
		$retention = $pm->edit['initial_retention'];
		if ($pm->state === 'Prune')
		{
			{
				$retention = (empty($_POST['initial_retention']) ? null : intval($_POST['initial_retention']));
				$this->addSingleUserWarning($pm);
				if (empty($retention))
					$pm->addError("Can not prune expired media using retention \"$_POST[initial_retention]\".");
				else
					ZMC_BackupSet::pruneAllExpired($pm, $pm->binding, $retention);
			}
		}
		$tl =& ZMC_BackupSet::mergeFindTapelist($pm, $pm->binding, $retention);
		ZMC_Paginator_Reset::defaultSortOrder(array('datetime', 'label'), $sortKey = 'sort_vtapelist');
		$paginator = new ZMC_Paginator_Array(
				$pm->tapeListPm,
				$tl,
				$pm->tapeListPm->cols = array(
						'host',
						'directory' => 'disk_name',
						'media_label' => 'label',
						'age',
						'prune_reason',
						'backup_level' => 'level',
						'percent_use',
						'size',
						'reuse',
						'datetime',
						'time_duration',
						'zmc_type',
						'encrypt',
						'compress',
						'status',
						'nb' => ZMC::$registry->dev_only ? true:null,
						'nc' => ZMC::$registry->dev_only ? true:null,
				),
				$sortKey,
				20
		);
		$paginator->createColUrls($pm->tapeListPm);
		$pm->tapeListPm->rows = $paginator->get();
		$pm->tapeListPm->goto = $paginator->shortFooter($pm->url);
		$pm->merge($pm->tapeListPm, null, true); 
	}
	
	protected function paginateTapeMedia($pm)
	{
		ZMC_HeaderFooter::$instance->addRegistry(array('barcodes_enabled' => ($pm->binding['changer']['ignore_barcodes'] === 'off')));
		$groupedTapelist = null;
		if (empty($pm->binding['schedule']['tapelist']))
			return;
		$groupedTapelist =& $this->reformatTapeList($pm->binding['schedule']['tapelist'], $pm->binding['schedule']['tapecycle'], $pm->binding['schedule']['dumpcycle_start_time'], $pm->binding['dev_meta']['media_type'] === 'vtape');
		$pm->addDetail('<pre>' . print_r($groupedTapelist, true) . '</pre>');
		$flattened =& ZMC::flattenArrays($groupedTapelist, false);
		ZMC_Paginator_Reset::defaultSortOrder(array('last_used', 'reuse'), $sortKey = 'sort_tapelist');
		$paginator = new ZMC_Paginator_Array(
				$pm->tapeListPm,
				$flattened,
				$pm->tapeListPm->cols = array(
						'last_used',
						'labels',
						'reuse',
				),
				$sortKey,
				20
		);
		$paginator->createColUrls($pm->tapeListPm);
		$pm->tapeListPm->rows = $paginator->get();
		$pm->tapeListPm->goto = $paginator->shortFooter($pm->url);
		$pm->merge($pm->tapeListPm, null, true); 
		return $groupedTapelist;
	}
}
