<?













global $pm;

ZMC_BackupCalendar::renderCalendar($pm, "ReportMedia");

function getDataDumpImage($detailRow)
{
	$imgLoc = "/images/section/report/data";
	$imgInc = "_full";
	$imgLev = "";
	$imgFail= "";

	$level = $detailRow['l'];
	$status = $detailRow['status'];

	if ($level == 1)
	{
		$imgLev = "_L1";
		$imgInc = "_incremental";
	}
	elseif ($level >= 2)
	{
		$imgLev = "_L2";
		$imgInc = "_incremental";
	}

	if ($status == "WARNING")
		$imgFail="_warning";
	elseif ($status == "ERROR")
		$imgFail="_failure";

	return $imgLoc.$imgInc.$imgFail.$imgLev.".gif";
}

function getDumpToolTip($detailRow)
{
	if ($detailRow['status'] == "ERROR")
		$str = "FAILED";
	else
	{
		$str = "";
		if ($detailRow['dump_orig_kb'] != "0" )
			$str .= "BackupSize: ".$detailRow['dump_orig_kb']." KiB";
		if ($detailRow['dump_out_kb'] != "0" )
			$str .= " Compr BackupSize: ".$detailRow['dump_out_kb']." KiB";
		if ($detailRow['dump_rate'] != "0" )
			$str .= " BackupRate: ".$detailRow['dump_rate']." KiB/s";
		if ($detailRow['tape_rate'] != "0" )
			$str .= " MediaRate: ".$detailRow['tape_rate']." KiB/s";
	}

	return $str;
}

echo "\n<form method='post' action='$pm->url'>\n";
?>


<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Legend: Data'); ?>
	<img src="/images/section/report/legend_data.gif" />
</div>



<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Data Chart', '', '', '', ZMC_Report::renderDayWeekNavigation("ReportData")); ?>
	<div class="dataTable">
		<table width="100%">
			<tr>
				<th align=left>HostName</td>
				<th align=center>Directory</td>
				<? ZMC_Report::renderTimeLineHeader($pm, "ReportData", 75); ?>
			</tr>
			<tr>
				<th>&nbsp;</td>
				<th>&nbsp;</td>
				<? ZMC_Report::renderTimeStampPullDown($pm, "ReportData"); ?>
			</tr>
	<?

			for ($r = 0; $r < count($pm->DLERowArray); $r++)
			{
				if ($r % 2)
					echo "<tr class=stripeGray>";
				else
					echo "<tr class=stripeWhite>";

				$hostDir = explode("\0", $pm->DLERowArray[$r]);
				$hostName = $hostDir[0];
				$dirName  = $hostDir[1];
				$dTitle="";
				$hTitle="";
				if (strlen($hostName) > 19) 
				{
					$hostName = "...".substr($hostDir[0],-19);
					$hTitle=$hostDir[0];
				}

				if  (strlen($dirName) > 19) 
				{
					$dirName = "...".substr($hostDir[1],-19);
					$dTitle=$hostDir[1];
				}

				echo "<td width=80 align=left nowrap title=$hTitle>$hostName</td>";
				echo "<td width=80 align=left nowrap title=$dTitle>$dirName</td>";

				$colWidth = 778 / $GLOBALS['selectRangeSize'];
				for ($d=0; $d < $GLOBALS['selectRangeSize']; $d++)
				{
					$detailRow = NULL;

					
					$colTS = $pm->BIDRangeArray[$d]['ts'];
					if ($colTS < $_SESSION["ReportDataCalMin"]
						|| $colTS > $_SESSION["ReportDataCalMax"])
					{
						echo "<td class=outOfBounds width=$colWidth>&nbsp;</td>";
						continue;
					}

					if (isset($pm->BIDRangeArray[$d]['bidinfo']))
					{
						$curBID = $pm->BIDRangeArray[$d]['bidinfo']->mGetBID();
						$curDay = $pm->BIDRangeArray[$d]['ts'];

						
						list($hostname, $directory) = explode("\0", $pm->DLERowArray[$r]);
						$hostname = ZMC_Mysql::escape($hostname);
						$directory = ZMC_Mysql::escape($directory);
						$detailRow = ZMC_Mysql::getOneRow("SELECT * FROM backuprun_dump_summary WHERE configuration_id="
							. ZMC_BackupSet::getId()
							. " AND backuprun_id='$curBID' AND hostname='$hostname' AND directory='$directory' LIMIT 1");
					}

					if ($detailRow == NULL)
						echo "<td width=$colWidth>&nbsp;</td>";
					else
					{
						$imgLoc = getDataDumpImage($detailRow);
						$altStr = getDumpToolTip($detailRow);

						
						$url = "?dayClickTimeStamp=".$curDay."#".$curBID;

						if ($detailRow['status']=="ERROR")
							echo "<td width=$colWidth align=center ><a href='{$url}_FAILURE'><img src='$imgLoc' title='$altStr' border=0 ></a></td>";
						else if ($detailRow['status']=="WARNING")
							echo "<td width=$colWidth align=center ><a href='{$url}_STRANGE'><img src='$imgLoc' title='$altStr' border=0 ></a></td>";
						else 
							echo "<td width=$colWidth align=center ><img src='$imgLoc' title='$altStr' border=0 ></td>";
					}
				}

				echo "</tr>\n";
			}
			?>
		</table>
	</div>
</div>
</form>
