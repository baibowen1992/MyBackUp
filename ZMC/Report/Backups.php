<?













class ZMC_Report_Backups
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Report', 'ZMC - Backups Report', 'backups');
		$pm->addDefaultInstruction('View summary reports for previous backups.');
		if (!ZMC_BackupSet::assertSelected($pm))
			return 'MessageBox'; 
		$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));
		ZMC_Report::refreshDB($pm);
		ZMC_Report::setUpBIDArrays($pm, ZMC_BackupSet::getId());
		$pm->numBackup=0;
		if (isset($pm->BIDRangeArray[0]['bidinfo']))
			if (!($pm->numBackup = $pm->BIDRangeArray[0]['bidinfo']->mGetNumItem()))
				$pm->addMessage('No BACKUPS on this day.');
	
		ZMC_Report::renderMessageArea($pm);
		return 'ReportBackups';
	}
}
