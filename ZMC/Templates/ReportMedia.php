<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>

<div class="wocloudRightWindow" style="margin-left:0;">
<? ZMC::titleHelpBar($pm, 'Media Chart', '', '', '', ZMC_Report::renderDayWeekNavigation("ReportMedia")); ?>
	<div class="dataTable">
		<table style="width:730px;">
			<tr><? ZMC_Report::renderTimeLineHeader($pm, "ReportMedia", 75); ?></tr>
			<tr><? ZMC_Report::renderTimeStampPullDown($pm, "ReportMedia"); ?></tr>
		<?
		
		$maxRow=0;
		$tapeRangeArray=array();
		for ($d=0; $d < $GLOBALS['selectRangeSize'];$d++)
		{
			if (isset($pm->BIDRangeArray[$d]['bidinfo']))
			{
				$curBID = $pm->BIDRangeArray[$d]['bidinfo']->mGetBID();
				
				
				$tapeIdArray = ZMC_Report_Media::getTapeIdArrayPerBackupId($curBID);
				$tapeRangeArray[$d]=$tapeIdArray;
				if (count($tapeIdArray) > $maxRow)
					$maxRow = count($tapeIdArray);
			}
		}
		ZMC_BackupSet::getTapeList($pm, $tapelist);
		$tapelist =& $tapelist['tapelist'];

		if ($maxRow == 0)
			$maxRow = 1; 

		for ($r=0; $r < $maxRow; $r++)
		{
			if ($r%2)
				echo "<tr class='stripeGray'>";
			else
				echo "<tr class='stripeWhite'>";
			for ($d = 0; $d < $GLOBALS['selectRangeSize']; $d++)
			{
				
				$colTS = $pm->BIDRangeArray[$d]['ts'];
				if ($colTS < $_SESSION["ReportMediaCalMin"] || $colTS > $_SESSION["ReportMediaCalMax"])
				{
					echo "<td class='outOfBounds' width='75' height='108'>&nbsp;</td>";
					continue;
				}

				if (!isset($tapeRangeArray[$d]) || ($r >= count($tapeRangeArray[$d])))
					echo "<td width='75'>&nbsp;</td>";
				else
				{
					$detailRow = ZMC_Mysql::getOneRow('SELECT * FROM backuprun_tape_usage WHERE configuration_id=' . ZMC_BackupSet::getId() . " AND backuprun_tape_id='" . $tapeRangeArray[$d][$r] . "' LIMIT 1");
					$barCode   = @$tapelist[$detailRow['tape_label']]['barcode'];
					$result = ZMC_Yasumi::operation($pm, array('pathInfo' => "/Device-Profile/read_profiles/",
											                    'data' => array('message_type' => 'admin devices read',),));
					if(is_array($detailRow)){
						if(!empty($detailRow['configuration_id']) && !empty($detailRow['backuprun_id'])){
							$level = ZMC_Mysql::getOneRow('SELECT l FROM backuprun_dump_summary	WHERE configuration_id='. $detailRow['configuration_id']. " AND backuprun_id=".$detailRow['backuprun_id']);
							$detailRow['level'] = $level['l'];
							$device = ZMC_Mysql::getOneRow('SELECT device FROM configurations	WHERE configuration_id='. $detailRow['configuration_id']);
							$device_name = $device['profile_name'];
							$detailRow['_key_name'] = $result['device_profile_list'][$device_name]['_key_name'];
						}
					}
					$imgLoc = ZMC_Report_Media::getMediaImage($detailRow);
					$duration = explode(":",$detailRow['time_duration']);
					$title = 'Duration: ' . $duration[0] . 'h ' . $duration[1] . 'm';
					$percentUse = round($detailRow['percent_use'], 1);
					echo "<td width='75' class='wocloudCenterNoLeftPad'><img src=$imgLoc title=\"$title\" />".$detailRow['tape_label']."<br>".$detailRow['size']."<br>".$percentUse."%<br>".$barCode."</td>";
				}
			}
			echo "</tr>";
		}
		?>
		</table>
	</div>
</div>



<?
ZMC_BackupCalendar::renderCalendar($pm, 'ReportMedia');
?>

<div class="wocloudLeftWindow" style='clear:left;'>
	<? ZMC::titleHelpBar($pm, 'Legend: Media'); ?>
	<table style="text-align:center;" rules="all" width="220px"><tbody>
		<tr><th class="calendarHeading"><div>Media Type</div></th><th class="calendarHeading">Full</th><th class="calendarHeading">Partial</th></tr>
		<tr style="background-color:#FFFFFF; font: bold 12px Arial,sans-serif,Helvetica;"><th style="padding:10px 0px 10px 5px;" width="70px">Disk</th><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_disk_full.gif"></td><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_disk_half_full.gif"></td></tr>
		<tr style="background-color:#EAECE7; font: bold 12px Arial,sans-serif,Helvetica;"><th style="padding:11px 0px 10px 5px;">Tape</th><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_tape_full.gif"></td><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_tape_half_full.gif"></td></tr>
		<tr style="background-color:#FFFFFF; font: bold 12px Arial,sans-serif,Helvetica;"><th style="padding:10px 0px 10px 5px;">Tape Changer</th><td style='padding-right: 12px; text-align:right;'><img src="/images/section/report/media_changer_full.gif"></td><td style='padding-right: 12px; text-align:right;'><img src="/images/section/report/media_changer_half_full.gif"></td></tr>
		<tr style="background-color:#EAECE7; font: bold 12px Arial,sans-serif,Helvetica;"><th style="padding:10px 0px 10px 5px;">Cloud Storage</th><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_s3_full.gif"></td><td style='padding-right: 10px; text-align:right;'><img src="/images/section/report/media_s3_half_full.gif"></td></tr>
		</tbody>
	</table>
</div>
</form>
