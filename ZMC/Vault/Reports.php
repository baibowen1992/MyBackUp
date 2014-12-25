<?













class ZMC_Vault_Reports extends ZMC_Vault
{

	public static function run(ZMC_Registry_MessageBox $pm)
	{	
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Vault', '云备份 - Reports for previous vault runs', 'reports');
		$page = new self($pm);

		if (empty($pm->selected_name)) {
			$pm->addMessage("Please select a backup set above.");
			return 'MessageBox';
		}

		$page->runState($pm);
		$pm->addError(ZMC_VaultCalendar::initReportCalendar($pm));

		$licenses = ZMC_License::readLicenses($pm);
		if ($licenses['licenses']['zmc']['Remaining']['vault'] <= 0) {
			$pm->addError("You do not have the license for 'Vault' feature. Please contact Support for more information.");
			return 'MessageBox';
		}

		return 'VaultReports';
	}

	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if (!empty($state))
			$pm->state = $state;

		switch($pm->state)
		{
		case 'Edit':
			case '':
				default:
					$pm->log_list = array();
					$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
						. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
					foreach (glob($logs_path . 'amvault.*.log') as $log){
						$contents = file_get_contents($log);
						if ($contents === false)
							$this->reply->addError("Unable to read '$log': " . ZMC_Error::error_get_last());
						elseif(!empty($contents))
							$pm->log_list[$log] = $contents;
					}
					$pm->vault_reports = array();
					$selectedDate = new DateTime();
					$selectedDate->setTimestamp($_SESSION['VaultReportsDayClick']);
					$selectedDateStr = $selectedDate->format("Ymd");
					if(isset($_REQUEST['dayClickTimeStamp'])){
						$selectedDate->setTimestamp($_REQUEST['dayClickTimeStamp']);
						$selectedDateStr = $selectedDate->format("Ymd");
					}
					if(empty($selectedDateStr))
						$selectedDateStr = date("Ymd", time());

					foreach($pm->log_list as $log => $contents){
						if(substr($log, strrpos($log, '.') - 14, 8)){
							$hasLog = true;
							$timeStr = substr($log, strrpos($log, '.') - 14, 14);
							$date = DateTime::createFromFormat('YmdHis', $timeStr);
							$d = $date->format('Ymd');
							if ($d === $selectedDateStr){
								$pm->vault_reports[$timeStr]['date'] = $d;
								$pm->vault_reports[$timeStr]['time'] = $date->format('H:i:s');
								$pm->vault_reports[$timeStr]['log'] = $log;
								$pm->vault_reports[$timeStr]['content'] = $contents;
							}
						}
					}
					unset($pm->log_list);

					break;
		}
	}
}
