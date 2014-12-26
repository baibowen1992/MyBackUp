<?













require 'ZMC/ReportCommon/DleState.php';

class ZMC_Report_Timeline
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Report', '云备份 - Backup Timeline Report', 'timeline');
		$pm->addDefaultInstruction('View timeline reports for previous backups.');
		if (!ZMC_BackupSet::assertSelected($pm))
			return 'MessageBox'; 
		$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));
		ZMC_Report::refreshDB($pm);
		ZMC_Report::setUpBIDArrays($pm, ZMC_BackupSet::getId());
		ZMC_Report::setUpDLEStateRowArray($pm, ZMC_BackupSet::getId(), "ReportTimeline", 0, $_SESSION['ReportTimelineDayClick']);
		computeZoomParams($pm, 'ReportTimeline');
		$pm->numBackup=0;
		if (isset($pm->BIDRangeArray[0]['bidinfo']))
			if (!($pm->numBackup = $pm->BIDRangeArray[0]['bidinfo']->mGetNumItem()))
				$pm->addMessage('No BACKUPS on this day.');

		ZMC_Report::renderMessageArea($pm);
		return 'ReportTimeline';
	}
}
