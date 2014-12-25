<?













class ZMC_Vault_When extends ZMC_Vault
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Vault', 'ZMC - Schedule Vault Jobs', 'when');
		ZMC_HeaderFooter::$instance->injectYuiCode("
			var o=gebi('zmc_schedule_type');
			if (o) o.onchange();
		");
		$whenPage = new self($pm);
		$whenPage->ymlFilePath = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'Vault-default.yml';
		
		if (empty($pm->selected_name)) {
			$pm->addWarning("Please select a backup set above.");
			return 'MessageBox';
		}
		
		$licenses = ZMC_License::readLicenses($pm);
		if ($licenses['licenses']['zmc']['Remaining']['vault'] <= 0) {
			$pm->addError("You do not have the license for 'Vault' feature. Please contact Zmanda Support for more information.");
			return 'MessageBox';
		}
		
		$template = $whenPage->runState($pm);
		return empty($template) ? 'VaultWhen' : $template;
	}
	
	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if ($pm->isErrors())
			return;
		
		if (!empty($state))
			$pm->state = $state;
		
		$pm->vault_job = ZMC_Yaml_sfYaml::load($this->ymlFilePath);
		$redirectPage = '';
		
		switch($pm->state)
		{
			case 'Next':
				
				$pm->vault_job['vault_hours'] = $this->processVaultHours($pm);
				$pm->vault_job['vault_minute'] = $_POST['minute'];
				$pm->vault_job['vault_schedule_type'] = $_POST['schedule_type'];
				$pm->vault_job['vault_custom_days'] = $this->processVaultCustomDays($pm);
				$pm->vault_job['vault_custom_dom'] = $this->processVaultCustomDOM($pm);
				$pm->vault_job['vault_when_string'] = $this->processVaultWhenString($pm);
				
				
				$timestamp = time();
				$pm->vault_job['timestamp'] = $timestamp;
				$pm->vault_job['vault_activated'] = "No";
				$savedJobPath = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
					. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-saved_' . $timestamp . '.yml';
				file_put_contents($savedJobPath, ZMC_Yaml_sfYaml::dump($pm->vault_job));
				
				
				unlink($this->ymlFilePath);

				
				$this->createCronFile($pm, $timestamp);
				
				
				$this->createConfFile($pm, $timestamp);
				
				
				$pm->state = '';
				$redirectPage = ZMC::redirectPage('ZMC_Vault_Jobs', $pm);
				break;
	
			case 'Cancel': 
				unlink($this->ymlFilePath);
				$redirectPage = ZMC::redirectPage('ZMC_Vault_What', $pm);
				break;
	
			case 'Edit':
				if(!is_array($pm->vault_job) || empty($pm->vault_job)){
					$pm->addError('A vault job must be configured first. Please choose what to vault.');
					$pm->state = 'Cancel';
					$redirectPage = ZMC::redirectPage('ZMC_Vault_What', $pm);
					break;
				} elseif (!isset($pm->vault_job['vault_device'])) {
					$pm->addError('Please choose where to vault.');
					$pm->state = 'Edit';
					$redirectPage = ZMC::redirectPage('ZMC_Vault_Where', $pm);
					break;
				}
				
			case '':
			case 'Refresh Table':
			case 'Refresh':
			default:
				$pm->state = $this->getSelectedBinding($pm) ? 'Edit' : '';
				break;
		}
		
		return $redirectPage;
	}
	
	private function processVaultHours(ZMC_Registry_MessageBox $pm) {
		$vault_hours = '';
		foreach($_POST['hours'] as $hour => $selected){
			if($selected)
				$vault_hours .= $hour . ",";
		}
		return rtrim($vault_hours, ",");
	}
	
	private function processVaultCustomDays(ZMC_Registry_MessageBox $pm) {
		$vault_custom_days = '';
		if($_POST['schedule_type'] === 'Custom Weekday'){
			ksort($_POST['custom_days']);
			foreach($_POST['custom_days'] as $day => $selected){
				if($selected === 'yes')
					$vault_custom_days .= $day . ",";
			}
		}
		return rtrim($vault_custom_days, ",");
	}
	
	private function processVaultCustomDOM(ZMC_Registry_MessageBox $pm) {
		$vault_custom_dom = '';
		if($_POST['schedule_type'] === 'Custom Days of the Month'){
			ksort($_POST['custom_dom']);
			foreach($_POST['custom_dom'] as $day => $selected){
				if($selected === 'yes')
					$vault_custom_dom .= $day . ",";
			}
		}
		return rtrim($vault_custom_dom, ",");
	}
	
	private function processVaultWhenString(ZMC_Registry_MessageBox $pm) {
		$vault_when_str = "At ";
		foreach(explode(',', $pm->vault_job['vault_hours']) as $hour)
			$vault_when_str .= $hour . ":" . $pm->vault_job['vault_minute'] . " ,";
		$vault_when_str = rtrim($vault_when_str, ",");
			
		switch($pm->vault_job['vault_schedule_type']){
			case 'Custom Weekday':
				$vault_when_str .= " on ";
				foreach(explode(',', $pm->vault_job['vault_custom_days']) as $day)
					$vault_when_str .= self::$daysOfWeek[$day] . " ,";
				$vault_when_str = rtrim($vault_when_str, ",");
				break;
		
			case 'Custom Days of the Month':
				$vault_when_str .= " on day ";
				foreach(explode(',', $pm->vault_job['vault_custom_dom']) as $day)
					$vault_when_str .= $day . " ,";
				$vault_when_str = rtrim($vault_when_str, ",");
				$vault_when_str .= " of the month";
				break;
					
			case 'Everyday':
			case 'Every Weekday':
			case 'Every Saturday':
			default:
				$vault_when_str .= " ". $pm->vault_job['vault_schedule_type'];
				break;
		}
		return $vault_when_str;
	}
	
	private function createCronFile(ZMC_Registry_MessageBox $pm, $timestamp)
	{		
		$adminDevice = new ZMC_Admin_Devices($pm);
		$device_profile_list = $adminDevice->getDeviceList($pm);
		$cmdStr = ZMC::$registry->cnf->zmc_bin_path . 'amvault.sh ' . $pm->selected_name . ' ' . $timestamp;

		$crontab = "### ZMC Vault Job - " . ZMC_Vault_Jobs::calculateJobPrettyName($pm->vault_job) . " to '" . $pm->vault_job['vault_device'] . "' " . $pm->vault_job['vault_when_string'] . " ###\n";
		$crontab .= $pm->vault_job['vault_minute'] . "\t" . $pm->vault_job['vault_hours'] . "\t";
		switch($pm->vault_job['vault_schedule_type']){
			case 'Everyday':
				$crontab .= "*\t*\t*\t";
				break;
			
			case 'Every Weekday':
				$crontab .= "*\t*\t1,2,3,4,5\t";
				break;
				
			case 'Every Saturday':
				$crontab .= "*\t*\t6\t";
				break;
				
			case 'Custom Weekday':
				$crontab .= "*\t*\t" . $pm->vault_job['vault_custom_days'] . "\t";
				break;
				
			case 'Custom Days of the Month':
				$crontab .= $pm->vault_job['vault_custom_dom'] . "\t*\t*\t";
				break;
				
			default:
				break;
		}

		$crontab .= str_replace('%', '\%', $cmdStr) . "\n";
		
		file_put_contents(ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'vault-' . $timestamp . '.cron', $crontab);
	}
	
	private function createConfFile(ZMC_Registry_MessageBox $pm, $timestamp)
	{
		$filename = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-changer_' . $pm->vault_job['vault_device'] . ".conf";
		
		if(file_exists($filename))
			return;
		
		$adminDevice = new ZMC_Admin_Devices($pm);
		$device_profile_list = $adminDevice->getDeviceList($pm);
		$selected_device = $device_profile_list[$pm->vault_job['vault_device']];
		$device_type = $selected_device['_key_name'];
		$content = "define changer " . $pm->vault_job['vault_device'] . " {\n";
		switch($device_type){				
			case 'attached_storage':
				$vaultDir = rtrim($selected_device['changer']['changerdev_prefix'], '/') . "/" . $pm->selected_name . "/" . $pm->vault_job['vault_device'];
				if(!is_dir($vaultDir))
					mkdir($vaultDir, 0777, true);
				$numslot = intval($selected_device['max_slots'] / 2);
				
				$content .= "\ttpchanger\t\"chg-disk:" . $vaultDir . "\"\n";
				$content .= "}\n";
				break;
				
			case 'changer_library':
				$tape_device = "";
				foreach($pm->vault_job['tapedev_list'] as $slot => $drive){
					$tape_device .= '"' . $slot . '=tape:' . $drive . '" ';
				}
				$content .= "\ttpchanger\t\"chg-robot:" . $selected_device['changer']['changerdev'] . "\"\n";
				$content .= "\tproperty\t\"autoclean\"\t\"off\"\n";
				$content .= "\tproperty\t\"autocleancount\"\t\"99\"\n";
				$content .= "\tproperty\t\"cleanshot\"\t\"0\"\n";
				$content .= "\tproperty\t\"comment\"\t\"" . $selected_device['changer']['comment'] . "\"\n";
				$content .= "\tproperty\t\"drive_choice\"\t\"" . $selected_device['changer']['drive_choice'] . "\"\n";
				$content .= "\tproperty\t\"eject_before_unload\"\t\"" . $selected_device['changer']['eject_before_unload'] . "\"\n";
				$content .= "\tproperty\t\"eject_delay\"\t\"" . $selected_device['changer']['eject_delay'] . "\"\n";
				$content .= "\tproperty\t\"ignore_barcodes\"\t\"off\"\n";
				$content .= "\tproperty\t\"load_poll\"\t\"0s poll 3s until 300s\"\n";
				$content .= "\tproperty\t\"status_interval\"\t\"" . $selected_device['changer']['status_interval'] . "\"\n";
				$content .= "\tproperty\t\"unload_delay\"\t\"" . $selected_device['changer']['unload_delay'] . "\"\n";
				$content .= "\tproperty\t\"tape_device\"\t" . $tape_device . "\n";
				$content .= "\tdevice_property\t\"LEOM\"\t\"" . $selected_device['device_property_list']['LEOM'] . "\"\n";
				$content .= "\tchangerfile\t\"" . $selected_device['changerfile'] . "\"\n";
				$content .= "}\n";
				break;
				
			case 's3_cloud':
				$bucketname = "zmc-" . strtolower($selected_device['device_property_list']['S3_ACCESS_KEY'] . "-" . $pm->selected_name . "-vault");
				$content .= "\ttpchanger\t\"chg-multi:s3:" . $bucketname . "/" . $pm->selected_name . "-tape{1..10}" . "\"\n";
				$content .= "\tproperty\t\"comment\"\t\"" . $selected_device['changer']['comment'] . "\"\n";
				$content .= "\tproperty\t\"ignore_barcodes\"\t\"on\"\n";
				$content .= "\tchangerfile\t\"" . $selected_device['changerfile'] . "\"\n";
				$content .= "\tdevice_property\t\"LEOM\"\t\"" . $selected_device['device_property_list']['LEOM'] . "\"\n";
				$content .= "\tdevice_property\t\"STORAGE_API\"\t\"" . $selected_device['device_property_list']['STORAGE_API'] . "\"\n";
				$content .= "\tdevice_property\t\"BLOCK_SIZE\"\t\"" . $selected_device['device_property_list']['BLOCK_SIZE'] . "m\"\n";
				$content .= "\tdevice_property\t\"REUSE_CONNECTION\"\t\"" . $selected_device['device_property_list']['REUSE_CONNECTION'] . "\"\n";
				$content .= "\tdevice_property\t\"S3_ACCESS_KEY\"\t\"" . $selected_device['device_property_list']['S3_ACCESS_KEY'] . "\"\n";
				$content .= "\tdevice_property\t\"S3_SECRET_KEY\"\t\"" . $selected_device['device_property_list']['S3_SECRET_KEY'] . "\"\n";
				$content .= "\tdevice_property\t\"S3_SSL\"\t\"" . $selected_device['device_property_list']['S3_SSL'] . "\"\n";
				$content .= "\tdevice_property\t\"S3_STORAGE_CLASS\"\t\"" . $selected_device['device_property_list']['S3_STORAGE_CLASS'] . "\"\n";
				$content .= "\tdevice_property\t\"S3_SUBDOMAIN\"\t\"off\"\n";
				$content .= "\tdevice_property\t\"S3_HOST\"\t\"s3.amazonaws.com\"\n";
				$content .= "\tdevice_property\t\"CREATE_BUCKET\"\t\"on\"\n";
				$content .= "\tdevice_property\t\"SSL_CA_INFO\"\t\"/opt/zmanda/amanda/common/share/curl/curl-ca-bundle.crt\"\n";
				$content .= "}\n";				
				break;
				
			default:
				break;
		}

		file_put_contents($filename, $content);
		
		ZMC_BackupSet::readConf($pm, $pm->selected_name);
		if(!isset($pm->conf['includefiles'][$filename]))
			$pm->conf['includefiles'][$filename] = true;
		ZMC_BackupSet::writeConf($pm, $pm->selected_name, $pm->conf);
		
		$status = ZMC_BackupSet::getStatus($pm, $pm->selected_name);
		$devices = explode(", ", $status['device']);
		if(!in_array($pm->vault_job['vault_device'], $devices)){
			$devices[] = $pm->vault_job['vault_device'];
			$status['device'] = implode(", ", $devices);
			ZMC_BackupSet::updateStatus($pm->selected_name, $status);
		}
	}
}
