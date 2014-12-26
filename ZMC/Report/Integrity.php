<?













class ZMC_Report_Integrity
{
	CONST TASK_DIV_NAME = 'Data Integrity';

public static function run(ZMC_Registry_MessageBox $pm)
{
	$pm->enable_switcher = true;
	ZMC_HeaderFooter::$instance->header($pm, 'Report', '云备份 - 数据完整性', 'data integrity');
	ZMC_HeaderFooter::$instance->addYui('wocloud-utils', array('dom', 'event', 'connection'));
	$pm->addDefaultInstruction('Verify integrity of data for previous backups.');
	if (!ZMC_BackupSet::assertSelected($pm))
		return 'MessageBox'; 

	$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));

	ZMC_Report::refreshDB($pm); 
	ZMC_Report::setUpBIDArrays($pm, $configId = ZMC_BackupSet::getId());
	if (	empty($_SESSION['DataIntegrityDayClick'])
		||	!isset($pm->BIDRangeArray[0]['bidinfo'])
		||	$pm->BIDRangeArray[0]['bidinfo']->mGetNumItem() <= 0)
		$pm->verifyButton = 'Click on a day in the calendar to choose a verification date.';
	else
		$pm->verifyButton = date('Y-m-d', $_SESSION['DataIntegrityDayClick']);

	if (isset($_POST['mode']))
		$_SESSION['DataIntegrityMode'] = $_POST['mode'];

	if (empty($_SESSION['DataIntegrityMode']))
		$_SESSION['DataIntegrityMode'] = 'ByTape';

	if (!empty($_POST['dataIntegrityTapeLabel']))
		$_SESSION['DataIntegrityTapeLabel'] = $_POST['dataIntegrityTapeLabel'];

	ZMC_BackupSet::getTapeList($pm, $pm['tapelist_stats']);
	$config = ZMC_BackupSet::getName();
	$amcheckdump = "/etc/amanda/$config/amcheckdump";
	if (!empty($_POST['action']))
	{
		@unlink("$amcheckdump.done");
		@unlink("$amcheckdump.out");
		$timestamps = array();
		if ($_SESSION['DataIntegrityMode'] === 'ByDate')
		{
			$what = "==>Verifying all backup images created on $_POST[viewDate] for backup set $config<==";
			$date = str_replace('-', '', $_POST['viewDate']);
			foreach($pm->tapelist_stats['tapelist'] as $record)
				if (!strncmp($record['timestring'], $date, 8))
					$timestamps[] = $record['timestring'];
		}
		else
		{
			$what = "==>Verifying all backup images on media labelled: " . $_POST['dataIntegrityTapeLabel'] . '<==';
			$timestamps = array($pm->tapelist_stats['tapelist'][$_POST['dataIntegrityTapeLabel']]['timestring']);
		}


		$PATH = getenv('PATH');
		$command = array("export PATH=$PATH", "date > $amcheckdump.out", "echo '$what' >> $amcheckdump.out");
		foreach($timestamps as $t)
			$command[] = "amcheckdump --timestamp $t $config < /dev/null 2>&1 >> $amcheckdump.out";
			
			
			

		$command[] = "mv $amcheckdump.out $amcheckdump.done) &";
		$cmd = '(' . implode(";\n", $command);
		exec($cmd, $output);
		sleep(2);
		if (ZMC::$registry->dev_only)
			$pm->taskInDiv .= "<h3>$cmd</h3>\n";
		
	}

	if (file_exists("$amcheckdump.out"))
	{
		$secs = 15;
		$interval = 15000;
		ZMC_HeaderFooter::$instance->addRegistry(array('monitor_countdown' => $secs));
		ZMC_HeaderFooter::$instance->injectYuiCode("
			setTimeout(function () { window.location.reload(); }, $interval)
			mcountdown($secs)
		");
	}

	$display = '';
	foreach(file("$amcheckdump." . (file_exists("$amcheckdump.out") ? 'out':'done')) as $line)
		if (strncmp($line, 'Press enter', 10) && strncmp($line, 'You will', 8))
			$display .= $line;
	$pm->taskInDiv .= "<pre>" . ZMC::escape($display) . '</pre><hr>';

	$pm->verifyByDate = ($_SESSION['DataIntegrityMode'] === 'ByDate');
	return 'ReportIntegrity';
}
}
