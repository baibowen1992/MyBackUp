<?













$GLOBALS['totalBackups'] = 0;
$GLOBALS['numSuccBackups'] = 0;
$GLOBALS['numFailBackups'] = 0;
$GLOBALS['numProgBackups'] = 0;

define('MaskSucc', 0x0001);
define('MaskProg', 0x0010);
define('MaskFail', 0x0100);

$GLOBALS['showSucc']=true;
$GLOBALS['showFail']=true;
$GLOBALS['showProg']=true;

$GLOBALS['rowHeight'] = 8;
$GLOBALS['zoomFit'] = false; 
$GLOBALS['colWidth'] = 70;

function computeZoomParams(ZMC_Registry_MessageBox $pm, $subnavInfo="What")
{
	global $numRows;
	global $rowHeight;
	$canvasHeight = 280;
	$canvasWidth = 740;
	global $zoomFit;
	global $colWidth;

	$numRows = count($pm->DLEStateRowArray);

	if ($numRows > 0)
		$rowHeight = $canvasHeight/$numRows;

	if ($rowHeight < 8) 
	{
		if ($rowHeight < 1) $rowHeight=1; 
		$GLOBALS['zoomFitNeeded'] = true;
	}
	else
		$GLOBALS['zoomFitNeeded'] = false;

	if (isset($_GET['zoomlevel']))
	{
		$_SESSION[$subnavInfo.'ZoomLevel']=$_GET['zoomlevel'];
		unset($_GET['zoomlevel']);
	}

	if (isset($_SESSION[$subnavInfo.'ZoomLevel']))
		$zoomFit = ($_SESSION[$subnavInfo.'ZoomLevel'] == "zoomFit");

	if ($zoomFit && $GLOBALS['zoomFitNeeded'])
		$colWidth = ($canvasWidth)/4;
	else
	{
		$rowHeight = 8; 
		if ($subnavInfo == "ReportTimeline")
			$colWidth = ($canvasWidth - 330)/4;
		else
			$colWidth = ($canvasWidth - 280)/4;
	}

	
	
	$vScrollBarNeeded = true;
	if (($rowHeight*$numRows) < $canvasHeight)
		$vScrollBarNeeded = false;

	if ($vScrollBarNeeded == false)
	{
		
		$scrollBarWidth = 23;
		$newWidth = 746;

		

		
		echo "<STYLE TYPE=\"text/css\">\n";
		echo "#interiorDataTable {\n";
		echo "width:".$newWidth."px;\n";
		echo "}\n";
		echo "</STYLE>\n";
	}
}

function createCSSClass($type, $height)
{
	echo ".monitorTable".$type."Cell {\n";

	if ($height > 3)
	{
		if ($type == "Text")
		{
			echo "padding:2px 3px;\n";
			echo "border-right:1px solid #888888;\n";
		}
		else
			echo "padding:2px 0px;\n";

		if ($type != "Empty" && $type != "InitFail")
			echo "border-top:1px solid #dce2c6;\n";

		echo "height:".$height."px;\n";
	
		if ($type != "InitFail")
			echo "background-repeat:repeat-x;\n";
		else					 
			echo "background-repeat:no-repeat;\n";
	
		if ($type == "Succ")
			echo "background:url(/images/section/monitor/success.gif);\n";
		else if ($type == "Prog")
			echo "background:url(/images/section/monitor/progress.gif);\n";
		else if ($type == "Fail")
			echo "background:url(/images/section/monitor/failure.gif);\n";
		else if ($type == "InitFail")
			echo "background-image:url(/images/section/report/data_full_failure.gif);\n";
	}
	else
	{
		
		echo "border-right:1px solid #ffffff;\n";
		
		echo "border-bottom:1px solid #ffffff;\n";
		echo "height:".$height."px;\n";

		if ($type == "Succ")
			echo "background-color:#00ff00;\n";
		else if ($type == "Prog")
			echo "background-color:#0000ff;\n";
		else if ($type == "Fail")
			echo "background-color:#ff0000;\n";
		else if ($type == "InitFail")
			echo "background-color:#ff00ff;\n";
	}

	echo "}\n";
}

function mRenderTextColumns($subnavInfo="what", $row)
{
	echo '<td class=monitorTableTextCell>', ZMC_BackupSet::displayName($row['hostname']), '</td>';
	echo '<td class=monitorTableTextCell>', str_replace('/', '<wbr/>/', ZMC::escape($row['directory'])), '</td>';

	if ($subnavInfo === 'ReportTimeline')
		echo '<td class=monitorTableTextCell width=50 align=center >', substr($row['backuprun_date_time'], 11), '</td>';

	echo '<td class="wocloudCenterNoLeftPad"><img src="', ZMC_Report::getLevelImage($row['backup_level']), '" title="Level ', $row['backup_level'], '" border=0 ></td>';
}

function mRenderOneDLERow($subnavInfo="what", $phaseCol, $rowNum, $row)
{
	if ($rowNum % 2)
		echo "<tr class=stripeGray>";
	else
		echo "<tr class=stripeWhite>";

	$emptyColumn = "<td><div class=monitorTableEmptyCell /></td>\n";
	
	if ($GLOBALS['rowHeight'] >= 8)
		mRenderTextColumns($subnavInfo, $row);

	

	$lNumDrawn = 0; 
	global $colWidth;

	$lState = $row['state'];
	if ($lState == "Failed")
	{
		$allNull = true;
		for ($cc=0; $cc < count($phaseCol); $cc++)
		{
			if ($row[$phaseCol[$cc]]!=NULL)
			{
				$allNull=false;
				break;
			}
		}

		
		if ($allNull)
		{
			
			showToolTip($colWidth, $row['failed'], 'monitorTableInitFailCell');
			for ($ee = 1; $ee < count($phaseCol); $ee++)
				echo $emptyColumn;
			$lNumDrawn = count($phaseCol);
		}
		else
		{
			
			$failColIndex=-1;
			for ($cc= count($phaseCol)-1; $cc >= 0; $cc--)
			{
				if ($row[$phaseCol[$cc]]!=NULL)
				{
					$failColIndex=$cc;
					break;
				}
			}
			
			
			for ($ss = 0; $ss < $failColIndex; $ss++)
				showToolTip($colWidth, $row[$phaseCol[$ss]], 'monitorTableSuccCell');

			
			
			showToolTip($colWidth, $row['failed'], 'monitorTableFailCell');
			




			for ($ee = $failColIndex +1; $ee < count($phaseCol); $ee++)
				showToolTip($colWidth, '', 'monitorTableFailCell');
			$lNumDrawn = count($phaseCol);
		}
	}
	else if ($lState == "Backup Started")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableProgCell');
		$lNumDrawn = 1;
	}
	else if ($lState == "Flush Completed")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['estimate'], 'monitorTableProgCell');
		$lNumDrawn = 2;
	}
	else if ($lState == "Estimate Completed")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['estimate'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['holding_disk'], 'monitorTableProgCell');
		$lNumDrawn = 3;
	}
	else if ($lState == "Backups in Holding Disk Waiting")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['estimate'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['holding_disk'], 'monitorTableSuccCell');
		$lNumDrawn = 3;
	}
	else if ($lState == "Backups in Holding Disk")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['estimate'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['holding_disk'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['media'], 'monitorTableProgCell');
		$lNumDrawn = 4;
	}
	else if ($lState == "Backups in Media")
	{
		showToolTip($colWidth, $row['flush'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['estimate'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['holding_disk'], 'monitorTableSuccCell');
		showToolTip($colWidth, $row['media'], 'monitorTableSuccCell');
		$lNumDrawn = 4;
	}

	
	for ($ee = $lNumDrawn; $ee < count($phaseCol); $ee++)
		echo $emptyColumn;

	echo "</tr>";
}

function XmRenderOneDLERow($subnavInfo="what", $phaseCol, $rowNum, $row)
{
	global $colWidth;
	global $rowHeight;

	$failedRow   = ($row['state']=="Failed");
	$successRow  = ($row['state']=="Backups in Media");
	$progressRow = ($row==false && $successRow==false);

	$emptyColumn = "<td width=$colWidth><div class=monitorTableEmptyCell /></td>\n";

	
	if ($rowNum%2)
		echo "<tr class=stripeGray>";
	else
		echo "<tr class=stripeWhite>";

	
	if ($rowHeight >= 8)
		mRenderTextColumns($subnavInfo, $row);

	if ($successRow)
	{
		for ($cc=0; $cc < count($phaseCol);$cc++)
			showToolTip($colWidth, $row[$phaseCol[$cc]], 'monitorTableSuccCell');
	}
	else if ($failedRow)
	{
		
		$allNull = true;
		for ($cc=0; $cc < count($phaseCol);$cc++)
		{
			if ($row[$phaseCol[$cc]]!=NULL)
			{
				$allNull=false;
				break;
			}
		}

		
		if ($allNull)
		{
			
			showToolTip($colWidth, $row['failed'], 'monitorTableInitFailCell');
			for ($ee = 1; $ee < count($phaseCol); $ee++)
				echo $emptyColumn;
		}
		else
		{
			
			$failColIndex=-1;
			for ($cc= count($phaseCol)-1;$cc>=0;$cc--)
			{
				if ($row[$phaseCol[$cc]]!=NULL)
				{
					$failColIndex=$cc;
					break;
				}
			}
			
			
			for ($ss = 0; $ss < $failColIndex; $ss++)
				showToolTip($colWidth, $row[$phaseCol[$ss]], 'monitorTableSuccCell');

			
			showToolTip($colWidth, $row[$phaseCol[$failColIndex]], 'monitorTableFailCell');

			
			for ($ee = $failColIndex+1; $ee < count($phaseCol); $ee++)
				echo $emptyColumn;
		}
	}
	else  
	{
		
		$progCol=-1;
		for ($cc= count($phaseCol)-1;$cc>=0;$cc--)
		{
			if ($row[$phaseCol[$cc]]!=NULL)
			{
				$progCol=$cc;
				break;
			}
		}

		if ($progCol != -1)
		{
			
			for ($ss = 0; $ss < $progCol; $ss++)
				showToolTip($colWidth, $row[$phaseCol[$ss]], 'monitorTableSuccCell');
			
			showToolTip($colWidth, $row[$phaseCol[$progCol]], 'monitorTableProgCell');

			
			for ($ee = $progCol+1; $ee < count($phaseCol); $ee++)
				echo $emptyColumn;
		}
		else
		{
			
		}
	}
	echo "</tr>";
}

function mRenderDLEBars(ZMC_Registry_MessageBox $pm, $subnavInfo="what")
{
	$lShowDiff = (isset($GLOBALS['diffDate']));

	$k=0;
	$phases = array("flush","estimate","holding_disk","media");
	for ($r=0; $r < $GLOBALS['numRows']; $r++)
	{
		$monrow = $pm->DLEStateRowArray[$r];

		
		$failedRow   = ($monrow['state']=="Failed");
		$successRow  = ($monrow['state']=="Backups in Media");
		$progressRow = ($failedRow==false && $successRow==false);

		
		if ($GLOBALS['showFail'] == false && $failedRow)   continue;
		if ($GLOBALS['showSucc'] == false && $successRow)  continue;
		if ($GLOBALS['showProg'] == false && $progressRow) continue;

		mRenderOneDLERow($subnavInfo,$phases,$k,$monrow);
		if ($lShowDiff) 
		{
			if (isset($GLOBALS['DiffDLEStateRowArray'][$r]))
				mRenderOneDLERow($subnavInfo, $phases, $k, $GLOBALS['DiffDLEStateRowArray'][$r]);
			else
			{
				
			}
		}

		$k++;
	}
}

function showToolTip($width, $tip, $class)
{
	$tip = ZMC::escape($tip);
	echo "<td width2ignore='$width' title='$tip'><div class='$class'></div>";
	if (!empty($_POST['show_monitor_tips']))
	{
		unset($_POST['show_monitor_tips']);
		if (isset($_SESSION['show_monitor_tips']))
			unset($_SESSION['show_monitor_tips']);
		else
			$_SESSION['show_monitor_tips'] = true;
	}

	if (!empty($_SESSION['show_monitor_tips']))
		echo substr($tip, 0, 128);

	echo "</td>\n";
}
