<?













class ZMC_Report_Data
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Report', '云备份 - Backed up data report', 'Data');
		$pm->addDefaultInstruction('View reports about data in previous backups.');
		if (!ZMC_BackupSet::assertSelected($pm))
			return 'MessageBox'; 
		$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));
		ZMC_Report::refreshDB($pm);
		ZMC_Report::setUpBIDArrays($pm, $id = ZMC_BackupSet::getId());
		$reportHostDirArray = array();
		foreach(ZMC_Mysql::getAllRows("SELECT * FROM backuprun_dump_summary WHERE configuration_id='$id'") as $row)
			$reportHostDirArray[] = $row['hostname']."\0".$row['directory']; 

		$pm->DLERowArray = array_keys(array_count_values($reportHostDirArray));
		$pm->numBackup=0;
		if (isset($pm->BIDRangeArray[0]['bidinfo']))
			if (!($pm->numBackup = $pm->BIDRangeArray[0]['bidinfo']->mGetNumItem()))
				$pm->addMessage('No BACKUPS on this day.');

		ZMC_Report::renderMessageArea($pm);
		return 'ReportData';
	}
}
