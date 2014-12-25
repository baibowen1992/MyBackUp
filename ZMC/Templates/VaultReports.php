<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>

<? ZMC_VaultCalendar::renderCalendar($pm, 'VaultReports'); ?>

<div class="wocloudWindow" style='clear:left;'>
<? ZMC::titleHelpBar($pm, 'Vault Summary', '', '', '', ZMC_Report::renderDayWeekNavigation("VaultReports")); ?>

<div class="wocloudSubHeadingWide">
<table  border="0" cellspacing="0" cellpadding="0">
<?
$html = "<tr>";
	$html .= "<td id=summaryTimestamp-1 width=70>Timestamps:&nbsp;&nbsp;</td>";
$hasLog = false;
foreach($pm->vault_reports as $log => $contents){
	$hasLog = True;
	$html .= "<td id=summaryTimestamp-2><a href='#".$contents['time']."'>".$contents['time']. "</a>&nbsp;&nbsp;</td>";
}

if($hasLog){
	$html .= "</tr>";
	echo $html;
} else {
	echo "<tr><td>No vault run found for the selected date</td></tr>";
}
?>
</table>
</div>

<div class="wocloudFormWrapperText" style="margin:0; border:0; min-width:724px;">
<?
$i = count($pm->vault_reports);
foreach($pm->vault_reports as $log => $contents){
	echo '<a name="'.$contents['time'].'"></a>';
	echo '<div style="padding:0px 10px 0px 0px; margin:0 5px 5px 0;"> TimeStamp: &nbsp;<img src="/images/global/calendar/icon_calendar_success.gif"> <a href="#">'.$contents['time'].'</a></div>';
	echo "<a href='/ZMC_Report_Backups?dayClickTimeStamp=". strtotime($contents['date']) . "#" . $contents['time'] . "'>More details...</a><br /><br />";
	echo "<pre>".$contents['content']."<br /></pre>";
	if(--$i > 0)echo "<hr>";
}
?>
</div>
</div>
</form>
