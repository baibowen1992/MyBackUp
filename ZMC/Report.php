<?













class ZMC_Report extends ZMC_Bidlist
{

public static function renderDLEPhaseHeader($subnavInfo = 'What', $rowHeight)
{
	if ($rowHeight >= 8)
	{
		if ($subnavInfo === 'ReportTimeline')
		{
			?>
			<th>HostName</td>
			<th>Directory</td>
			<th class='zmcCenterNoLeftPad'>Time</td>
			<th class='zmcCenterNoLeftPad'>Level</td>
			<?
		}
		else
		{
			?>
			<th>HostName</td>
			<th>Directory</td>
			<th class='zmcCenterNoLeftPad'>Level</td>
			<?
		}
	}

	foreach(array(
		"Clearing<br />Staging Area",
		"Checking<br />Backup Plan",
		"Transferring<br />Backup to Server",
		"Writing to<br />Backup Media")
		as $header)
		echo "<th width='120' id='timelinePhase-1' class='zmcCenterNoLeftPad'>$header</td>\n";
}

public static function zoomFitTitleBar(ZMC_Registry_MessageBox $pm, $subnavTitle, $dayWeekNav = false)
{
	$appendToTitle = '';
	if ($GLOBALS['zoomFitNeeded']) 
	{
		$infit = $GLOBALS['zoomFit'] ? 'In' : 'Fit';
		$appendToTitle = "&nbsp; <a href='?zoomlevel=zoom$infit'><input type='checkbox' name='rtZoomFit' id='rtZoomFit'>&nbsp;&nbsp;Zoom $infit</a>";
	}

	if ($dayWeekNav)
		$appendToTitle .= self::renderDayWeekNavigation(str_replace(' ', '', $subnavTitle));

	ZMC::titleHelpBar($pm, $subnavTitle, '', '', '', $appendToTitle);
}

public static function renderMessageArea(ZMC_Registry_MessageBox $pm, $msg="")
{
	global $errors;
	if (!empty($GLOBALS['exception']))
	{
		ob_start();
		ZMC::renderException($GLOBALS['exception']);
		$pm->addError(ob_get_clean());
	} 
	elseif ($errors != null && $errors[0] !='')
		$pm->addEscapedError($errors);
	elseif ($msg != "")
		$pm->addDefaultInstruction($msg);
}

public static function renderDayWeekNavigation($subnavInfo="What")
{
	$date = date("Y-m-d", $_SESSION[$subnavInfo . 'DayClick']); 
	return <<<EOD
<div style='text-align:center;'>
		<a title='PreviousWeek' href="{$_SERVER['SCRIPT_NAME']}?navWeek=decrease"><img src='/images/global/calendar/arrow-left-x2.png'></a>
		<a title='PreviousDay' href="{$_SERVER['SCRIPT_NAME']}?navDay=decrease"><img src='/images/global/calendar/arrow-left.png'></a>
		$date
		<a title='NextDay' href="{$_SERVER['SCRIPT_NAME']}?navDay=increase"><img src='/images/global/calendar/arrow-right.png'></a>
		<a title='NextWeek' href="{$_SERVER['SCRIPT_NAME']}?navWeek=increase"><img src='/images/global/calendar/arrow-right-x2.png'></a>
</div>
EOD;
}

public static function renderTimeLineHeader(ZMC_Registry_MessageBox $pm, $subnavInfo="What",$colWidth)  
{
	for ($d=0; $d < count($pm->BIDRangeArray); $d++)
	{
		$iconImage="";
		$altStr="";
		$imgStr="";

		if (isset($pm->BIDRangeArray[$d]['bidinfo']))
		{
			if ($pm->BIDRangeArray[$d]['bidinfo']->mGetNumItem() == 1)
			{
				$singleStat=$pm->BIDRangeArray[$d]['bidinfo']->mGetCumulativeStatus();
				$iconImage = self::mGetStatusIconImage($singleStat, false, false);
				$altStr = $pm->BIDRangeArray[$d]['bidinfo']->mGetToolTip();
			}
			else 
			{
				$multiStat=$pm->BIDRangeArray[$d]['bidinfo']->mGetCumulativeStatus();
				$iconImage = self::mGetStatusIconImage($multiStat, true, false);
				$altStr = $pm->BIDRangeArray[$d]['bidinfo']->mGetToolTip();
			}
		}
		if ($iconImage != "")
			$imgStr = "<img style='position:absolute;' title='$altStr' src='$iconImage'>";

		$dplus = $d+1;
		echo "<th id='mediaDate-$dplus' class='zmcCenterNoLeftPad'>" . date("Y-m-d", $pm->BIDRangeArray[$d]['ts']) . "&nbsp;$imgStr</td>\n";
	}
}

public static function renderTimeStampPullDown(ZMC_Registry_MessageBox $pm, $subnavInfo)
{
	for ($d=0; $d < count($pm->BIDRangeArray); $d++)
	{
		$numBackup=0;
		if (isset($pm->BIDRangeArray[$d]['bidinfo']))
			$numBackup = $pm->BIDRangeArray[$d]['bidinfo']->mGetNumItem();

		switch ($numBackup)
		{
			case 0:
				echo "<th class='zmcCenterNoLeftPad'>&nbsp;</td>";
				break;

			case 1:
				echo "<th class='zmcCenterNoLeftPad'>".$pm->BIDRangeArray[$d]['bidinfo']->mGetTime(0)."</td>";
				break;

			default:
				echo "<th class='zmcCenterNoLeftPad'>";
				echo "<input  TYPE=hidden VALUE='", $pm->BIDRangeArray[$d]['ts'], "' NAME=columnDay>";
				echo "<select onchange='this.form.submit();' name=columnDayTSIndex style='float:none; width:65px; font-size:12px;' title=Timestamp(s) value=1>";
	
				$selectIndex = $pm->BIDRangeArray[$d]['bidinfo']->mGetCurIndex();
				for ($t = 0; $t < $numBackup; $t++)
				{
					$selectStr="";
					if ($t == $selectIndex)
						$selectStr = " selected='selected' ";

					echo "<option value=".$t.$selectStr." >"
						. substr($pm->BIDRangeArray[$d]['bidinfo']->mGetTime($t),0,6)
						. "</option>";
				}
				echo "</select>";
				echo "</td>";
				break;
		}
	}
}





public static function refreshDB($pm)
{
	try
	{
		$name = ZMC_BackupSet::getName();
		if (empty($name))
			return;
		$command = ZMC_ProcOpen::procOpen($cmd = 'amreport_wrapper', ZMC::getZmcTool($cmd), array($name), $stdout, $stderr, "Error status returned while processing recent Amanda logs for '$name'.");
		if (!empty($stdout) || !empty($stderr))
			$pm->addDetail("amreport_wrapper updates: $stdout\n$stderr");
	}
	catch (ZMC_Exception_ProcOpen $e)
	{
		ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex("A problem occurred while updating ZMC reports using amreport_wrapper: $e"), __FILE__, __LINE__);
	}
}

public static function mGetStatusIconImage($status)
{
	static $status2fn = array(
		'OK' => 'icon_calendar_success.gif',
		'WARNING' => 'icon_calendar_warning.gif',
		'ERROR' => 'icon_calendar_failure.gif',
	);
	return '/images/global/calendar/' . $status2fn[$status];
}

public static function setUpBIDArrays(ZMC_Registry_MessageBox $pm, $configurationID, $subnavInfo=null)
{
	if (empty($subnavInfo))
		$subnavInfo = ucfirst($pm->tombstone) . ucfirst($pm->subnav);
	if (!isset($pm->BIDRangeArray))
		$pm->BIDRangeArray = array();

	$confName = $_SESSION['configurationName'];
	if ($subnavInfo == "ReportDBEvent")
		$query = "SELECT * FROM mysql_zrm_backuprun_summary WHERE configuration_name='$confName'";
	elseif($subnavInfo == "ConvertBackup")
		$query = "SELECT * FROM mysql_zrm_backuprun_summary WHERE configuration_name='$confName' AND  backup_type='quick' AND backup_status = 'Backup succeeded'";
	elseif($subnavInfo == "DataIntegrity" && ZMC::$registry->short_name == "ZRM")
		
		$query = "SELECT * FROM backuprun_summary WHERE configuration_id='$configurationID' AND notes != 'quick' AND status_summary !='NULL'  ORDER BY backuprun_date_time ASC";
	else
		$query = "SELECT * FROM backuprun_summary WHERE configuration_id='$configurationID' AND status_summary!='NULL' ORDER BY backuprun_date_time ASC";

	global $calMonth;
	global $BIDCalArray;
	global $selectRangeSize;

	$BIDCalArray   = array();
	$anchor = $_SESSION[$subnavInfo.'DayClick'];
	$mm = date("m",$anchor);
	$yy = date("Y",$anchor);
	$dd = date("j",$anchor);

	for ($r = ($selectRangeSize -1); $r >= 0; $r--)
	{
		
		$pm->BIDRangeArray[$r]['ts'] = mktime(0, 0, 0, $mm, $dd, $yy);
		$dd--; 
	}

	
	$tsMax = -1;
	$tsMin = time();
	foreach(ZMC_Mysql::getAllRows($query) as $row)
	{
		
		if ($subnavInfo == "ReportDBEvent" || $subnavInfo == "ConvertBackup")
			$ts = $row['backup_date'];
		else
			$ts = $row['backuprun_date_time'];

		
		
		$tsInt = strtotime(substr($ts,0,10));

		if ($tsInt < $tsMin)
			$tsMin = $tsInt;

		if ($tsInt > $tsMax)
			$tsMax = $tsInt;

		
		$index = $calMonth->getIndexInDayArray($tsInt);
		if ($index >= 0 && $index < 42) 
		{
			if (!isset($BIDCalArray[$index]))
				$BIDCalArray[$index] =  new ZMC_Bidlist();

			if ($subnavInfo == "ReportDBEvent")
			{
				
				$status = "OK";
				if ($row['backup_status'] != "Backup succeeded")
					$status = "ERROR";

				$BIDCalArray[$index]->mAddWithLevel($row['backuprun_id'], $status, substr($ts,10), $row['backup_level']);
			}
			else
				$BIDCalArray[$index]->mAdd($row['backuprun_id'], $row['status_summary'], substr($ts,10));
		}


		
		if ($tsInt >= $pm->BIDRangeArray[0]['ts']) 
		{
			for($r = 0; $r < $selectRangeSize; $r++)
			{
				if ($tsInt == $pm->BIDRangeArray[$r]['ts'])
				{
					if (!isset($pm->BIDRangeArray[$r]['bidinfo']))
						$pm->BIDRangeArray[$r]['bidinfo'] = new ZMC_Bidlist();

					if ($subnavInfo == "ReportDBEvent")
					{
						
						$status = "OK";
						if ($row['backup_status'] != "Backup succeeded")
							$status = "ERROR";

						$pm->BIDRangeArray[$r]['bidinfo']->mAddWithLevel($row['backuprun_id'], $status, substr($ts,10), $row['backup_level']);
					}
					else
						$pm->BIDRangeArray[$r]['bidinfo']->mAdd($row['backuprun_id'], $row['status_summary'], substr($ts,10));
				}
			}
		}
	}

	$_SESSION[$subnavInfo."CalMin"] = $tsMin;
	$_SESSION[$subnavInfo."CalMax"] = $tsMax;

	
	
	
	for ($r = 0; $r < $selectRangeSize; $r++)
		if (isset($pm->BIDRangeArray[$r]['bidinfo']))
			if ($pm->BIDRangeArray[$r]['bidinfo']->mGetNumItem() > 1)
				if (isset($_SESSION[$subnavInfo][$pm->BIDRangeArray[$r]['ts']]))
					$pm->BIDRangeArray[$r]['bidinfo']->mSetCurIndex($_SESSION[$subnavInfo][$pm->BIDRangeArray[$r]['ts']]);

	$GLOBALS['progressTS']=0;
	self::setUpDLEStateRowArray($pm, $configurationID, $subnavInfo,1);
}

public static function setUpDLEStateRowArray(ZMC_Registry_MessageBox $pm, $configurationID, $subnavInfo = 'What', $active, $dayStamp=0)
{
	global $totalBackups;
	global $numSuccBackups;
	global $numFailBackups;
	global $numProgBackups;
	global $progressTS;
	global $sNonActiveTS;

	$pm->DLEStateRowArray = array();
	$totalBackups   = 0;
	$numSuccBackups = 0;
	$numFailBackups = 0;
	$numProgBackups = 0;

	$checkDayStamp=false;
	if ($active=='0' && $dayStamp != 0)
		$checkDayStamp=true;
	
	$sql = '';
	if (empty($_REQUEST['include_manual_backups']))
		$sql = "AND active='$active'";

	foreach(ZMC_Mysql::getAllRows("SELECT * FROM backuprun_dle_state WHERE configuration_id='$configurationID' $sql order by backuprun_date_time DESC") as $row)
	{
		if ($checkDayStamp)
		{
			$ts = $row['backuprun_date_time'];
			
			$tsInt = strtotime(substr($ts,0,10));
			if ($tsInt != $dayStamp) continue;
		}

		$pm->DLEStateRowArray[] = $row;
		$totalBackups++;

		if ($row['state'] == "Failed")
			$numFailBackups++;
		elseif ($row['state'] === 'Backups in Media')
			$numSuccBackups++;
	}

	
	if ($active==1 && $totalBackups==0)
	{
		if ($row = ZMC_Mysql::getOneRow("SELECT * FROM backuprun_dle_state WHERE configuration_id='$configurationID' AND active='0' order by backuprun_date_time DESC"))
		{
			$GLOBALS['sNoActiveBackup'] = true;
			
			$lastTimeStamp = $row['backuprun_date_time'];
			$a = explode("-",$lastTimeStamp);
			$b = explode(" ",$a[2]);
			$lastTSLabel = $a[1]."/".$b[0]."/".$a[0]." ".$b[1];
			$sNonActiveTS = mktime(0,0,0,$a[1],$b[0],$a[0]);
		}
	}
	elseif ($active == 1 && $totalBackups > 0)
	{
		
		

		$lastTimeStamp = $pm->DLEStateRowArray[0]['backuprun_date_time'];

		$pm->DLEStateRowArray = array();
		$totalBackups   = 0;
		$numSuccBackups = 0;
		$numFailBackups = 0;
		$numProgBackups = 0;

		
		foreach(ZMC_Mysql::getAllRows("SELECT * FROM backuprun_dle_state WHERE configuration_id='$configurationID' AND backuprun_date_time='$lastTimeStamp'") as $row1)
		{
			$pm->DLEStateRowArray[] = $row1;
			$totalBackups++;
	
			if ($row1['state'] == "Failed")
				$numFailBackups++;
			else if ($row1['state'] == "Backups in Media")
				$numSuccBackups++;

			if ($totalBackups>9000) break; 
		}
	}

	$numProgBackups = $totalBackups - $numSuccBackups - $numFailBackups;
	if ($active == 1) 
	{
		$progressTS = 0;
		if (count($pm->DLEStateRowArray)>0)
		{
			if ($pm->DLEStateRowArray[0]['backuprun_date_time'] != ' ' && $pm->DLEStateRowArray[0]['active']=='1' )
			{
				$str = $pm->DLEStateRowArray[0]['backuprun_date_time'];
				$a = explode("-",$str);
				$b = explode(" ",$a[2]);

				$curTSLabel = $a[1]."/".$b[0]."/".$a[0]." ".$b[1];
				$progressTS = mktime(0,0,0,$a[1],$b[0],$a[0]);
			}
		}
	}
}

public static function getLevelImage($level)
{
	$imgLoc = "/images/section/report/data";
	$imgInc = "_full";
	$imgLev = "";

	if ($level == 1)
	{
		$imgLev = "_L1";
		$imgInc = "_incremental";
	}
	else if ($level >= 2)
	{
		$imgLev = "_L2";
		$imgInc = "_incremental";
	}

	return $imgLoc.$imgInc.$imgLev.".gif";
}

} 
