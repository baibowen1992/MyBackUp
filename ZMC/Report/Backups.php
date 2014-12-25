<?













class ZMC_Report_Backups
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Report', '云备份 - Backups Report', 'backups');
		$pm->addDefaultInstruction('查看前一次备份汇总报告');
		if (!ZMC_BackupSet::assertSelected($pm))
			return 'MessageBox'; 
		$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));
		ZMC_Report::refreshDB($pm);
		ZMC_Report::setUpBIDArrays($pm, ZMC_BackupSet::getId());
		$pm->numBackup=0;
		if (isset($pm->BIDRangeArray[0]['bidinfo']))
			if (!($pm->numBackup = $pm->BIDRangeArray[0]['bidinfo']->mGetNumItem()))
				$pm->addMessage('今天没有备份集.');
	
		ZMC_Report::renderMessageArea($pm);
		return 'ReportBackups';
	}
}
