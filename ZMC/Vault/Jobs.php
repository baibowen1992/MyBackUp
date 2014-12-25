<?













class ZMC_Vault_Jobs extends ZMC_Vault
	{
	
	public static function run(ZMC_Registry_MessageBox $pm)
	{	
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Vault', 'ZMC - Manage vault jobs', 'jobs');
		$page = new self($pm);
		
		if (empty($pm->selected_name)) {
			$pm->addMessage("Please select a backup set above.");
			return 'MessageBox';
		}
		
		$page->runState($pm);
		$page->getPaginator($pm);
		
		$licenses = ZMC_License::readLicenses($pm);
		if ($licenses['licenses']['zmc']['Remaining']['vault'] <= 0) {
			$pm->addError("You do not have the license for 'Vault' feature. Please contact Zmanda Support for more information.");
			return 'MessageBox';
		}
		
		if(empty($pm->rows)){
			$pm->addWarning("This backup set, \"{$pm->selected_name}\", has no vault jobs.");
			return 'MessageBox';
		}
		
		$pm->addMessage("Auto refreshed at: " . ZMC::humanDate(true) . " (15 seconds refresh interval)");
		
		return 'VaultJobs';
	}
	
	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if (!empty($state))
			$pm->state = $state;
	
		switch($pm->state)
		{
			case 'Delete':
				
				$this->activate($pm, false);
				
				
				$ymlFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
						. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-saved_';
				$cronFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'vault-';
				foreach($_POST['selected_ids_vault_job'] as $timestamp => $selected){
					unlink($ymlFilenamePattern . $timestamp . ".yml");
					unlink($cronFilenamePattern. $timestamp . ".cron");
				}

				
				$this->runState($pm, 'Refresh Table');
				break;
				
			case 'Vault Now':
				ZMC_Vault_Jobs::startVaultJobNow($pm, $_POST['selected_ids_vault_job']);
				$this->runstate($pm, 'Refresh Table');
				break;
				
			case 'Abort':
				if (!ZMC_BackupSet::abort($pm, $pm->selected_name))
					$pm->addError("Unable to abort backup set: $pm->selected_name");
				else
					$pm->addMessage("Backup/Restore/Vault cancelled for: $pm->selected_name");
				$this->runstate($pm, 'Refresh Table');
				break;
				
			case 'Activate':
			case 'Deactivate':
				$this->activate($pm, $pm->state === 'Activate' ? true : false);
				$this->runState($pm, 'Refresh Table');
				break;
	
			case '':
			case 'Refresh Table':
			default:
				$filenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
					. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-saved_*.yml';
					$jobsList = array();
				foreach(glob($filenamePattern) as $filename)
					$jobsList[] = ZMC_Yaml_sfYaml::load($filename);
				
				$inProgressList = array();
				$adminDevice = new ZMC_Admin_Devices($pm);
				$device_profile_list = $adminDevice->getDeviceList($pm);
				foreach(glob("/var/log/amanda/server/" . $pm->selected_name . "/amvault.*.debug") as $logFile){
					$contents = file_get_contents($logFile);
					preg_match("/amvault: pid (.+) ruid .+\n/", $contents, $pid);
					if(is_dir('/proc/' . end($pid))){
						preg_match("/amvault: Arguments: (.+)\n/", $contents, $argsMatches);
						$cmdStr = "/usr/sbin/amvault " . $argsMatches[1];
						foreach($jobsList as $job){
							$command = str_replace("'", '', self::getVaultCommand($pm, $job, $device_profile_list));
							if(strpos($cmdStr, $command) !== false){
								if(preg_match("/amvault: WRITTEN SIZE: (.+)\n$/", $contents, $sizeMatches))
									$inProgressList[$cmdStr] = end($sizeMatches);
								else
									$inProgressList[$cmdStr] = "Starting...";
							}
						}
					}
				}
				
				$processedJobsList = array();
				foreach($jobsList as $job){
					$processedJob = array();
					$processedJob['vault_what'] = self::calculateJobPrettyName($job);
					$processedJob['vault_where'] = $job['vault_device'];				
					$processedJob['vault_when'] = $job['vault_when_string'];
					$processedJob['vault_activated'] = $job['vault_activated'];
					$processedJob['timestamp'] = $job['timestamp'];
					$cmdStr = str_replace("'", '', self::getVaultCommand($pm, $job, $device_profile_list));
					if(isset($inProgressList[$cmdStr]))
						$processedJob['in_progress'] = $inProgressList[$cmdStr];
					$processedJobsList[] = $processedJob;
				}
				$pm->job_list = $processedJobsList;
				break;
		}
	}

	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (empty($pm->job_list))
			return;
	
		$flattened =& ZMC::flattenArrays($pm->job_list);
	
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'vault_what',
			'vault_where',
			'vault_when',
			'timestamp',
			'vault_activated',
			'in_progress',
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}
	
	private function activate(ZMC_Registry_MessageBox $pm, $on = true)
	{
		$ymlFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-saved_';
		$confFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-changer_';
		
		foreach($_POST['selected_ids_vault_job'] as $timestamp => $selected){			
			try
			{
				$ymlFilename = $ymlFilenamePattern . $timestamp . ".yml";
				$job = ZMC_Yaml_sfYaml::load($ymlFilename);
				
				
				$result = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => "/crontab/sync/" . $pm->selected_name,
						'data' => array(
								'commit_comment' => "sync crontab",
								'message_type' => 'vault',
								'activate' => $on,
								'cron' => 'vault-' . $timestamp,
						),
				));
				foreach($result['messages'] as $msg){
					$pm->addMessage($msg);
				}
				
				
				$isActivated = $job['vault_activated'] === 'Yes';
				if($on xor $isActivated){ 
					$job['vault_activated'] = $on ? 'Yes' : 'No';
					file_put_contents($ymlFilename, ZMC_Yaml_sfYaml::dump($job));
				}
				
				if($on)
					$pm->addMessage("An email will be sent to you when the vault job is triggered.");
			}
			catch(Exception $e)
			{
				$pm->addError("$e");
			}
		}
	}
	
	public static function calculateJobPrettyName(array $job) {
		$prettyName = '';
		switch($job['vault_level']){
			case 'latest_full_backup':
				$prettyName = "Vault the latest full backup run";
				break;
			case 'full_only':
				if($job['vault_type'] === 'latest')
					$prettyName = "Vault the latest backup run only if it is a full backup";
				if($job['vault_type'] === 'last_x_days')
					$prettyName = "Vault all full backup runs started in the last {$job['num_of_days']} days";
				if($job['vault_type'] === 'time_frame')
					$prettyName = "Vault all full backup runs started within a time frame";
				break;
			case 'all_level':
				if($job['vault_type'] === 'latest')
					$prettyName = "Vault the latest backup run";
				if($job['vault_type'] === 'last_x_days')
					$prettyName = "Vault all backup runs started in the last {$job['num_of_days']} days";
				if($job['vault_type'] === 'time_frame')
					$prettyName = "Vault all backup runs started within a time frame";
				break;
		}
		return $prettyName;
	}
	
	public static function getVaultCommand(ZMC_Registry_MessageBox $pm, array $job, array $device_profile_list){
		if(empty($pm->edit)){
			$result = ZMC_Mysql::getAllRowsMap('SELECT * FROM configurations ORDER BY configuration_name', 'Unable to load configurations table', false, null, 'configuration_name');
			 $pm->edit = $result[$pm->selected_name];
		}
		$cmdStr = "/usr/sbin/amvault " . $pm->selected_name;
		
		if($job['vault_level'] === 'latest_full_backup'){
			$cmdStr .= " --latest-fulls";
		} else {
			switch($job['vault_type']){
				case 'time_frame':
					$startTimeStamp = str_replace('-', '', $job['vault_start_date']) . str_replace(':', '', $job['vault_start_time']) . "00";
					$endTimeStamp = str_replace('-', '', $job['vault_end_date']) . str_replace(':', '', $job['vault_end_time']) . "00";
					$timeFrame = "'" . $startTimeStamp . "-" . $endTimeStamp . "'";
					$cmdStr .= " --src-timestamp " . $timeFrame;
					if($job['vault_level'] === 'full_only')
						$cmdStr .= " --fulls-only";
					break;
				case 'last_x_days':
					$now = new DateTime();
					$startTimestamp = new DateTime('@' . ($now->getTimestamp() - ($job['num_of_days'] * 3600 * 24)));
					$cmdStr .= " --src-timestamp '" . $startTimestamp->format('Ymd') . "-". $now->format('Ymd') . "'";
					if($job['vault_level'] === 'full_only')
						$cmdStr .= " --fulls-only";
					break;
				case 'latest':
					$cmdStr .= " --src-timestamp latest";
					if($job['vault_level'] === 'full_only')
						$cmdStr .= " --fulls-only";
					break;
			}
		}
		
		$cmdStr .= ' --dst-changer ' . $job['vault_device'];
		$vault_device = $device_profile_list[$job['vault_device']];
		if($vault_device['_key_name'] == 'changer_library')
			$cmdStr .= ' --label-template \'' . $pm->selected_name . '-' . $job['vault_device'] . '-$b-vault-%%%\'';
		else
			$cmdStr .= ' --label-template \'' . $pm->selected_name . '-' . $job['vault_device'] . '-vault-%%%\'';
		
		switch($vault_device['_key_name']){
			case 's3_cloud':
				$cmdStr .= ' -o device_output_buffer_size=1024m';
				$cmdStr .= " --autolabel any";
				break;
		
			case 'changer_library':
				$cmdStr .= " -o 'CHANGER:" . $job['vault_device'] . ":property=\"use_slots\" \"" . $job['slot_range'] . "\"'";
					
				$tape_device = "";
				foreach($job['tape_drives'] as $slot => $drive){
					$tape_device .= '"' . $slot . '=tape:' . $drive . '" ';
				}
				$cmdStr .= " -o 'CHANGER:" . $job['vault_device'] . ":property=\"tape_device\" " . $tape_device . "'";
				
				$backup_device = $device_profile_list[$pm->edit['profile_name']];
				$cmdStr .= " -o 'DUMPTYPE:zmc_device_" . print_r($backup_device['_key_name'], true) . ":allow-split=yes'";
				if($job['autolabel'] === 'on')
					$cmdStr .= " --autolabel \"" . $job['autolabel_how'] . "\"";
				break;
		
			case 'attached_storage':
			default:
				break;
		}
		
		return $cmdStr;
	}
	
	public static function startVaultJobNow(ZMC_Registry_MessageBox $pm, array $timestamp_list, $opts = ''){
		$ymlFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-saved_';
		$confFilenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-changer_';
		
		$adminDevice = new ZMC_Admin_Devices($pm);
		$device_profile_list = $adminDevice->getDeviceList($pm);
		
		foreach($timestamp_list as $timestamp => $selected){
			try
			{
				$ymlFilename = $ymlFilenamePattern . $timestamp . ".yml";
				$pm->vault_job = ZMC_Yaml_sfYaml::load($ymlFilename);
				
				$vault_device = $device_profile_list[$pm->vault_job['vault_device']];
				
				$error = null;
				if($pm->vault_job['device_type'] === 'attached_storage') 
					$error = ZMC_Vault_Jobs::adjustSlotVtape($pm, $vault_device);
				if($pm->vault_job['device_type'] === 's3_cloud')
					$error = ZMC_Vault_Jobs::adjustSlotS3Cloud($pm, $vault_device);
				
				if($error){
					$pm->addError($error);
					return;
				}
				
				$cmdStr = self::getVaultCommand($pm, $pm->vault_job, $device_profile_list);
				if(!empty($opts))
					$cmdStr .= " " . $opts;
				
				
				$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
						. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs';
				$timestring = date("YmdHis");
				$cmdStr .= ' 2>&1 | (while read line; do echo "$(date): ${line}"; done) | tee -a ' . $logs_path . DIRECTORY_SEPARATOR . 'amvault.$(date +\%Y\%m\%d\%H\%M\%S).log';

				$result = ZMC::execv('bash', $cmdStr);
				sleep(5);
				$pm->addMessage("Starting '" . self::calculateJobPrettyName($pm->vault_job) . "' to '" . $pm->vault_job['vault_device'] . "'");
			}
			catch(Exception $e)
			{
				$pm->addError($e);
				$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
						. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'amvault.' . date("YmdHis") . '.log';
				file_put_contents($logs_path, $e);
				return;
			}
		}
	}
	
	public static function adjustSlotVtape(ZMC_Registry_MessageBox $pm, array $vault_device){
		$vaultDir = rtrim($vault_device['changer']['changerdev_prefix'], '/') . "/" . $pm->selected_name . "/" . $pm->vault_job['vault_device'];

		$slots = glob("$vaultDir/slot*", GLOB_NOSORT);
		$totalSlots = count($slots);
		foreach($slots as $slot)
			$sortedSlots[substr($slot, strpos($slot, '/slot') + 5)] = $slot;
		krsort($sortedSlots, SORT_NUMERIC);	
		
		if(count($slots)){
			for($slotNum = 1; $slotNum <= $totalSlots; $slotNum++) {
				$label = $pm->selected_name . '-' . $pm->vault_job['vault_device'] . '-vault-' . str_pad($slotNum, 3, '0', STR_PAD_LEFT);
				if(file_exists($sortedSlots[$slotNum] . '/00000.' . $label)) {
					if(count(glob("{$sortedSlots[$slotNum]}/*")) > 1) { 
						continue;
					} else { 
						return;
					}
				} else { 
					try
					{
						$cmd = ZMC::getAmandaCmd('amlabel');
						$label = "{$pm->selected_name}-{$pm->vault_job['vault_device']}-vault-" . str_pad($slotNum, 3, '0', STR_PAD_LEFT);
						$args = array("-otpchanger=" . $pm->vault_job['vault_device'], $pm->selected_name, $label, 'slot', $slotNum);
						$command = ZMC_ProcOpen::procOpen('amlabel', $cmd, $args, $stdout, $stderr, 'amlabel command failed unexpectedly');
						return;
					}
					catch (ZMC_Exception_ProcOpen $e)
					{
						$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
								. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'amvault.' . date("YmdHis") . '.log';
						file_put_contents($logs_path, "Failed to create label file for slot $vaultDir/slot$slotNum", FILE_APPEND);
						file_put_contents($logs_path, $e, FILE_APPEND);
						return "Failed to create label file for slot $vaultDir/slot$slotNum";
					}
				}
			}
		}
		
		
		if($totalSlots < $vault_device['max_slots']){
			$slotNum = $totalSlots + 1;
			if(mkdir("$vaultDir/slot$slotNum", 0700, true)){
				try
				{
					$cmd = ZMC::getAmandaCmd('amlabel');
					$label = "{$pm->selected_name}-{$pm->vault_job['vault_device']}-vault-" . str_pad($slotNum, 3, '0', STR_PAD_LEFT);
					$args = array("-otpchanger=" . $pm->vault_job['vault_device'], $pm->selected_name, $label, 'slot', $slotNum);
					$command = ZMC_ProcOpen::procOpen('amlabel', $cmd, $args, $stdout, $stderr, 'amlabel command failed unexpectedly');
					return;
				}
				catch (ZMC_Exception_ProcOpen $e)
				{
					$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
							. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'amvault.' . date("YmdHis") . '.log';
					file_put_contents($logs_path, "Failed to create label file for slot $vaultDir/slot$slotNum", FILE_APPEND);
					file_put_contents($logs_path, $e, FILE_APPEND);
					return "Failed to create label file for slot $vaultDir/slot$slotNum";
				}
			}
			return "Failed to create new slot $vaultDir/slot$slotNum for {$pm->vault_job['vault_device']}";
		} else {
			return "The number of slots exceeds the maximum permitted for this device ({$vault_device['max_slots']}). The maximum can be increased using the advanced settings on the Admin|devices page.";
		}
	}
	
	public static function adjustSlotS3Cloud(ZMC_Registry_MessageBox $pm, array $vault_device){
 		$tapelist = array();
	    ZMC_BackupSet::getTapeList($pm, $tapelist, $pm->selected_name);

	    if(filesize("/etc/amanda/$pm->selected_name/tapelist") != 0)
	    	$originalTapeList = ZMC_BackupSet::getTapeListForBackupSet($pm->selected_name);
	    else
	    	$originalTapeList = null;
	    
	    $labelPattern = '/^' . $pm->selected_name . '-' . $pm->vault_job['vault_device'] . '-vault-[0-9][0-9][0-9]$/';
	    
		$tl =& $tapelist['tapelist'];
		foreach(array_keys($tl) as $key)
			if (empty($tl[$key]['timestring'])) 
				unset($tl[$key]);
 			else if (!preg_match($labelPattern, $key)) 
 				unset($tl[$key]);
 		
 		$numSlot = count(array_keys($tl));
 		
 		$filename = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
 				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-changer_' . $pm->vault_job['vault_device'] . ".conf";
 		$confFile = fopen($filename, 'r');
 		while (!feof($confFile))
 		{
 			$currentLine = fgets($confFile);
 			if(preg_match("/tape\{1\.\.(\d+)\}/", $currentLine, $matches)){
 				$maxSlot = $matches[1];
 				break;
 			}
 		}
 		
 		if($numSlot >= $maxSlot){ 
 			$newSlot = $numSlot + 10;
 			$contents = file_get_contents($filename);
 			$contents = str_replace("tape{1..$maxSlot}", "tape{1..$newSlot}", $contents);
 			file_put_contents($filename, $contents);
 		}
	}
}
