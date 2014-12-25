<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>

<? ZMC_BackupCalendar::renderCalendar($pm, 'ReportBackups'); ?>
<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '备份状态统计图'); ?>
	<img src="/images/section/report/legend_summary.gif" />
</div>


<div class="wocloudWindow" style='clear:left;'>
<? ZMC::titleHelpBar($pm, '备份报告', '', '', '', ZMC_Report::renderDayWeekNavigation("ReportBackups")); ?>

<div class="wocloudSubHeadingWide">
<table  border="0" cellspacing="0" cellpadding="0">
<tr>

<?
if ($pm->numBackup == 1)
{
	echo "<td id=summaryTimestamp-1 >Timestamp:&nbsp;&nbsp;".$pm->BIDRangeArray[0]['bidinfo']->mGetTime(0);
	$status = $pm->BIDRangeArray[0]['bidinfo']->mGetStatus(0);
	echo "&nbsp;&nbsp;<img src=", ZMC_Report::mGetStatusIconImage($status), " border=0 valign=middle></td>";
}
else if ($pm->numBackup > 1)
{
	echo "<td id=summaryTimestamp-1 width=70>Timestamps:&nbsp;&nbsp;</td>";

	
	$lod = "high";
	if ($pm->numBackup >= 13)
		$lod = "low";
	else if ($pm->numBackup >= 10)
		$lod = "medium";

	for ($b = 0; $b < $pm->numBackup; $b++)
	{
		$status = $pm->BIDRangeArray[0]['bidinfo']->mGetStatus($b);
		$imgsrc = ZMC_Report::mGetStatusIconImage($status);
		$ts = trim($pm->BIDRangeArray[0]['bidinfo']->mGetTime($b));
		$url = $pm->url . '#' . $ts;
	
		if ($lod == "high")
			echo "<td id=summaryTimestamp-2><a href='$url' >"
				. $ts . "&nbsp;<img src='$imgsrc' title='$ts' border=0 valign=middle></a>&nbsp;&nbsp;</td>\n";
		else if ($lod == "medium")
			echo "<td id=summaryTimestamp-2><a href='$url'>" . substr($ts,0,6)
				."..&nbsp;<img src='$imgsrc' title='$ts' border=0 valign=middle></a>&nbsp;&nbsp;</td>\n";
		else if ($lod == "low")
			echo "<td id=summaryTimestamp-2><a href='$url' >"
				. "&nbsp;<img src='$imgsrc' title='$ts' border=0 valign=middle></a>&nbsp;&nbsp;</td>\n";
	}
}
?>
</tr>
</table>
</div>

<div class="wocloudFormWrapperText" style="margin:0; border:0; min-width:724px;">
<?
if ($pm->numBackup==0)
	echo "<h1>&nbsp;</h1>";
else 
{
	
	if ($pm->numBackup > 1)
		echo "<A NAME=ReportBackupsTop></A>";
	
	$id = ZMC_BackupSet::getId();
	$record = ZMC_BackupSet::get($id);
	$bsName = $record['configuration_name'];
	$backupDevice = $record['profile_name'];

	for ($hr = '', $top = '', $b = 0; $b < $pm->numBackup; $b++)
	{
		$details = ZMC_Mysql::getOneRow("SELECT * FROM backuprun_summary WHERE configuration_id='" . $id . "' AND backuprun_id='" . $pm->BIDRangeArray[0]['bidinfo']->mGetBID($b) . "'");
		$imgsrc = ZMC_Report::mGetStatusIconImage($details['status_summary']);
		
		$pattern = '/' . $bsName . '-(.*)-vault-[0-9][0-9][0-9]/';
		if(preg_match($pattern, $details['usage_by_tape'], $matches))
			$deviceName = $matches[1];
		else
			$deviceName = $backupDevice;

		
		$timeStamp = trim($pm->BIDRangeArray[0]['bidinfo']->mGetTime($b));
		echo "<A NAME=" . $timeStamp . "></A>";
		$dateStamp = date("m/d/Y", $_SESSION['ReportBackupsDayClick']);
		$hhmm = substr($timeStamp, 0, 5) . ':59';
		echo "$hr<div style='padding:0px 10px 0px 0px; margin:0 5px 5px 0;'>$top TimeStamp: &nbsp;<img src='$imgsrc'> <a href='/ZMC_Restore_What?restore_device=$deviceName&date_time_human=$dateStamp+$hhmm&client=&disk_name='>".$timeStamp."</a></div>";
		$hr = '<hr>';
		$top = '<a href="#body">TOP</a> |';
		echo "<pre>";
		echo "<b>DUMP SUMMARY:</b><br>".ZMC::escape($details['dump_summary'])."<br>";
		echo "<b>STATISTICS:</b><br>".ZMC::escape($details['statistics'])."<br>";
		echo "<b>USAGE BY TAPE:</b><br>".ZMC::escape($details['usage_by_tape'])."<br>";
		
		echo "<A NAME=".$pm->BIDRangeArray[0]['bidinfo']->mGetBID($b)."_FAILURE></A>";
		echo "<b>FAILURE SUMMARY:</b><br>".ZMC::escape($details['failure_summary']);
		echo "<b>FAILURE DETAILS:</b><br>".ZMC::escape($details['failure_details']);
		
		echo "<A NAME=".$pm->BIDRangeArray[0]['bidinfo']->mGetBID($b)."_STRANGE></A>";
		echo "<b>STRANGE SUMMARY:</b><br>".ZMC::escape($details['strange_summary']);
		echo "<b>STRANGE DETAILS:</b><br>".ZMC::escape($details['strange_details']);
		echo "<b>NOTES:</b><br>".ZMC::escape($details['notes']);
		echo "</pre>";
	}
}

?>
</div> <!-- End Interior Output Container -->
</div> <!-- End AM REport Exterior Wrapper -->
</form>
