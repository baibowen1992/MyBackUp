<?













global $pm;
global $rowHeight;

echo "\n<form method='post' action='$pm->url'>\n";
echo "<STYLE TYPE=\"text/css\">\n";
createCSSClass("Text",	$rowHeight);
createCSSClass("Succ",	$rowHeight);
createCSSClass("Fail",	$rowHeight);
createCSSClass("Prog",	$rowHeight);
createCSSClass("Empty",   $rowHeight);
createCSSClass("InitFail",$rowHeight);
echo "</STYLE>\n";

ZMC_BackupCalendar::renderCalendar($pm, "ReportTimeline");
?>



<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Backup States'); ?>
	<img src="/images/section/report/legend_timeline.gif" />
</div>


<div class="zmcLeftWindow" style="clear:left; max-width:978px;">
	<? ZMC_Report::zoomFitTitleBar($pm, 'Report Timeline', true); ?>
	<div class="dataTable">
		<table>
			<tr><? ZMC_Report::renderDLEPhaseHeader('ReportTimeline', $rowHeight); ?></tr>
			<? mRenderDLEBars($pm, 'ReportTimeline'); ?>
		</table>
	</div>

	<div class="zmcButtonBar">
		<input type='submit' id='show_monitor_tips' name='show_monitor_tips' value='Toggle Details' />
	</div>
</div>

</form>
