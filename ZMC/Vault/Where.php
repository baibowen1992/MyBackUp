<?













class ZMC_Vault_Where extends ZMC_Vault
	{
	
	protected $zmc_type_class = 'ZMC_Type_Where';
	
	public static function run(ZMC_Registry_MessageBox $pm)
	{			
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Vault', '云备份 - Where to Vault', 'where');
		$wherePage = new self($pm);
		$wherePage->ymlFilePath = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'Vault-default.yml';
		
		if (empty($pm->selected_name)) {
			$pm->addWarning("Please select a backup set above.");
			return 'MessageBox';
		}
		
		$licenses = ZMC_License::readLicenses($pm);
		if ($licenses['licenses']['zmc']['Remaining']['vault'] <= 0) {
			$pm->addError("You do not have the license for 'Vault' feature. Please contact Support for more information.");
			return 'MessageBox';
		}
		
		$template = $wherePage->runState($pm);
		$wherePage->getPaginator($pm);
	
		return empty($template) ? 'VaultWhere' : $template;
	}
	
	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if (!empty($state))
			$pm->state = $state;
	
		$pm->vault_job = ZMC_Yaml_sfYaml::load($this->ymlFilePath);
		$redirectPage = '';
		switch($pm->state)
		{
			case 'Next':
				if(isset($_POST['tape_drives'])){
					$pm->vault_job['slot_range'] = $_POST['slot_range'];
					$pm->vault_job['tape_drives'] = array();
					foreach($_POST['tape_drives'] as $drive => $slot){
						if($slot !== 'skip') {
							$pm->vault_job['tape_drives'][$slot] = $drive;
						}
					}
					$pm->vault_job['autolabel'] = $_POST['autolabel'];
					$pm->vault_job['autolabel_how'] = $_POST['autolabel_how'];
					file_put_contents($this->ymlFilePath, ZMC_Yaml_sfYaml::dump($pm->vault_job));
					$pm->state = 'Edit';
					$redirectPage = ZMC::redirectPage('ZMC_Vault_When', $pm);
				} else {
					reset($_POST['selected_ids']);
					$pm->vault_job['vault_device'] = key($_POST['selected_ids']);
					$adminDevice = new ZMC_Admin_Devices($pm);
					$device_profile_list = $adminDevice->getDeviceList($pm);
					$selected_device = $device_profile_list[$pm->vault_job['vault_device']];
					$device_type = $selected_device['_key_name'];
					$pm->vault_job['device_type'] = $device_type;
					if($device_type === 'changer_library'){
						$result = ZMC_Yasumi::operation(new ZMC_Registry_MessageBox(), array('pathInfo' => '/Tape-Drive/discover_tapes', 'data' => array()));
						$pm->vault_job['changerdev'] = $selected_device['changer']['changerdev'];
						$pm->vault_job['max_slots'] = $selected_device['max_slots'];
						$pm->vault_job['tapedev_list'] = array_keys($result['tapedev_list']);
						file_put_contents($this->ymlFilePath, ZMC_Yaml_sfYaml::dump($pm->vault_job));
						$pm->state = 'Config_Tape_Changer';
					} else {
						file_put_contents($this->ymlFilePath, ZMC_Yaml_sfYaml::dump($pm->vault_job));
						$pm->state = 'Edit';
						$redirectPage = ZMC::redirectPage('ZMC_Vault_When', $pm);
					}
				}
				
				break;
				
			case 'Cancel': 
				unlink($this->ymlFilePath);
				$redirectPage = ZMC::redirectPage('ZMC_Vault_What', $pm);
				break;
	
			case 'Edit':
				if(!is_array($pm->vault_job) || empty($pm->vault_job)) {
					$pm->addError('A vault job must be configured first. Please choose what to vault');
					$pm->state = 'Cancel';
					$redirectPage = ZMC::redirectPage('ZMC_Vault_What', $pm);
					break;
				}
				
			case 'Refresh Table':
			case '':
			default:
				$this->getSelectedBinding($pm);
				if (!empty($pm->device_profile_list[$pm->binding['private']['zmc_device_name']]['private']['tape_drives']))
					$pm->addMessage('This changer library appears to have ' . $pm->device_profile_list[$pm->binding['private']['zmc_device_name']]['private']['tape_drives']. ' tape drive units.');
				$pm->form_type = call_user_func(array($this->zmc_type_class, 'get'), $pm->binding['_key_name']);
				break;
		}
		
		return $redirectPage;
	}
	
	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (empty($pm->device_profile_list))
			return;
	
		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('config_name', false);
	
		$flattened =& ZMC::flattenArrays($pm->device_profile_list);
		$showVtapes = $showTapes = null;
		foreach($flattened as &$row)
			if ($row['dev_meta:media_type'] !== 'vtape')
				$showTapes = true;
			else
			{
				if (!empty($row['media:partition_total_space']))
				{
					$row['autolabel'] = 'Yes';
					$showVtapes = true;
				}
			}
	
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
		if (!empty($pm->rows))
			foreach($pm->rows as &$row)
			{
				$row = ZMC_DeviceFilter::filter($pm, 'output', $row);
				
				
				if (!empty($pm->sets))
					unset($pm->sets[$row['config_name']]);
			}
	}
}
