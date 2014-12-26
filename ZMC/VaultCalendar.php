<?













class ZMC_VaultCalendar
{

public static function initReportCalendar(ZMC_Registry_MessageBox $pm)
{
	$subnavInfo = ucfirst($pm->tombstone) . ucfirst($pm->subnav);
	$lError = NULL;

	
	$GLOABLS['calMonthIsPassive'] = false;

	global $selectRangeSize;
	$selectRangeSize=1;

	if (!isset($GLOBALS['calMonth'])) 
		$GLOBALS['calMonth'] = new ZMC_Zcalendar();

	global $todayDay;
	global $todayMonth;
	global $todayYear;

	$todayDay  =date("j",time());
	$todayMonth=date("m",time());
	$todayYear =date("Y",time());

	if (isset($_SESSION[$subnavInfo."Date"]))
	{
		$curMonth = date("m",$_SESSION[$subnavInfo."Date"]);
		$curYear  = date("Y",$_SESSION[$subnavInfo."Date"]);
		$curDay   = date("j",$_SESSION[$subnavInfo."Date"]);
	}
	else
	{
		$curMonth=date("m",time());
		$curYear =date("Y",time());
		$curDay  =date("j",time());
	}

	
	if (!isset($_SESSION[$subnavInfo."CalMin"]))
	{
		$_SESSION[$subnavInfo."CalMin"] = -1;
		$_SESSION[$subnavInfo."CalMax"] = time();
	}

	$postVar=false;
	
	if (isset($_REQUEST['viewDate']))
	{ 
		$postVar=true;

		if (strlen($_REQUEST['viewDate'])==0)
		{
			
			$lError = "Please enter a valid date (YYYY-MM-DD)";
		}
		else
		{
			$tokens = explode("-",$_REQUEST['viewDate']);
		
			if (count($tokens) != 3)
			{
				$lError = "Invalid date (".$_REQUEST['viewDate']."), please enter YYYY-MM-DD";
			}
			else
			{
				$curMonth=$tokens[1];
				$curDay  =$tokens[2];
				$curYear =$tokens[0];

				if (checkdate($curMonth,$curDay,$curYear) == false)
					$lError = "Invalid date (".$_REQUEST['viewDate'].")";
				else
				{
					$userEnteredDate = mktime(0,0,0,$curMonth,$curDay,$curYear);
					if ($userEnteredDate == false)
						$lError = "Date out of range";
					else
					{
						$_SESSION[$subnavInfo."DayClick"]=$userEnteredDate;			
						
						$_SESSION[$subnavInfo."ViewDate"]=$userEnteredDate;
					}
				}
			}
		}
		unset($_REQUEST['viewDate']);
	}

	$v1 = "columnDay";
	$v2 = "columnDayTSIndex";
	if (isset($_POST[$v1]))
	{
		$postVar=true;
		$_SESSION[$subnavInfo][$_POST[$v1]]=$_POST[$v2];
		unset($_POST[$v1]);
		unset($_POST[$v2]);
	}

	if ($postVar == false)   
	{
		
		if (isset($_GET['navMonth']))
		{
			if ($_GET['navMonth'] == "increase")
			{
				$curMonth +=1;
				if ($curMonth==13)
				{
					$curMonth=1;
					$curYear+=1;
				}
			}
			else if ($_GET['navMonth'] == "decrease")
			{
				$curMonth -=1;
				if ($curMonth==0)
				{
					$curMonth=12;
					$curYear-=1;
				}
			}
			$curDay=1; 
			unset($_GET['navMonth']);
		}

		
		if (isset($_GET['dayClickTimeStamp']))
		{
			$_SESSION[$subnavInfo."DayClick"]=$_GET['dayClickTimeStamp'];
			unset($_GET['dayClickTimeStamp']);
		}
		else
		{
			if (isset($_SESSION[$subnavInfo."DayClick"])==false)
			$_SESSION[$subnavInfo."DayClick"]=time();   
		}

		if (isset($_GET['navDay']))
		{
			if ($_GET['navDay'] == "increase")
			{
				$navdayTS = strtotime("+1 day",$_SESSION[$subnavInfo."DayClick"]);
				
			}
			else if ($_GET['navDay'] == "decrease")
			{
				$navdayTS = strtotime("-1 day",$_SESSION[$subnavInfo."DayClick"]);
				
			}
			$curMonth = date("m",$navdayTS);
			$curYear  = date("Y",$navdayTS);
			$curDay   = date("j",$navdayTS);
			$_SESSION[$subnavInfo."DayClick"] = $navdayTS;
			unset($_GET['navDay']);
		}

		if (isset($_GET['navWeek']))
		{
			if ($_GET['navWeek'] == "increase")
				$navweekTS = strtotime("+7 days",$_SESSION[$subnavInfo."DayClick"]);
			else if ($_GET['navWeek'] == "decrease")
				$navweekTS = strtotime("-7 days",$_SESSION[$subnavInfo."DayClick"]);

			$curMonth = date("m",$navweekTS);
			$curYear  = date("Y",$navweekTS);
			$curDay   = date("j",$navweekTS);
			$_SESSION[$subnavInfo."DayClick"] = $navweekTS;
			unset($_GET['navWeek']);
		}
	}

	
	if ($GLOBALS['calMonth']->curMonth != $curMonth || $GLOBALS['calMonth']->curYear  != $curYear)
		$GLOBALS['calMonth']->setMonthYear($curMonth,$curYear);

	$decMonthURL = "{$_SERVER['SCRIPT_NAME']}?navMonth=decrease";
	$incMonthURL = "{$_SERVER['SCRIPT_NAME']}?navMonth=increase";

	$curMonthLabel = date("F",mktime(0, 0, 0, $curMonth, $curDay, $curYear));
	$curYearLabel  = date("Y",mktime(0, 0, 0, $curMonth, $curDay, $curYear));

	$GLOBALS['calCaption'] = "<a href=".$decMonthURL." title='previous month'><img src='/images/global/calendar/arrow-left.png'></a> "
		. $curMonthLabel . ' ' . $curYearLabel
		. " <a href='$incMonthURL' title='next month'><img src='/images/global/calendar/arrow-right.png'></a>\n";

	$_SESSION[$subnavInfo."Date"] = mktime(0, 0, 0, $curMonth, $curDay, $curYear);

	if ($lError != NULL)
		return $lError;
}

public static function renderCalendar($pm)
{
	$subnavInfo = ucfirst($pm->tombstone) . ucfirst($pm->subnav);
	echo '<div class="wocloudLeftWindow">';
	ZMC::titleHelpBar($pm, '', 'Vault Date', '', '', "<div style='text-align: center;'>$GLOBALS[calCaption]</div>");
	global $calMonth;
	$WeekDayLabels = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
	$WeekDayLabelsAbb = array("S","M","T","W","T","F","S");
	global $BIDCalArray;
	global $progressTS; 

	echo "<table rules='all' style='background-color:white; text-align:center;' frame='void' summary='Monthly calendar'><tr>\n";

	for ($w=0; $w < 7;$w++)
		echo "<th class='calendarHeading' scope=col abbr=$WeekDayLabels[$w] title=$WeekDayLabels[$w] ><b>$WeekDayLabelsAbb[$w]</b></th>\n";

	echo "</tr>\n";

	$thisMonth = date("m",$_SESSION[$subnavInfo."Date"]);
	$thisYear  = date("Y",$_SESSION[$subnavInfo."Date"]);

	$GLOBALS['todayDay'] = date("j",time());
	$GLOBALS['todayMonth'] = date("m",time());
	$GLOBALS['todayYear'] = date("Y",time());
	$todayTS   =mktime(0, 0, 0, $GLOBALS['todayMonth'], $GLOBALS['todayDay'], $GLOBALS['todayYear']);

	$dayIndex=0;
	$timeLow = $calMonth->timeStampLowBound;

	$dd  =date("j",$timeLow);
	$mm  =date("m",$timeLow);
	$yy  =date("Y",$timeLow);
	
	$date = new DateTime();
	$logs_path = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
			. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;

	$numInRange=0;
	while ($calMonth->hasMoreDays())
	{
		echo "<tr>\n";
	
		for ($z=0; $z < 7;$z++)
		{
			$calMonth->nextDay();
			$day = $calMonth->currentDay;
	
			
			$clickTimeStamp = mktime(0,0,0,$mm,$dd,$yy);
	
			$isToday=false;
			$isProgress=false;
			if ($clickTimeStamp==$todayTS)
				$isToday=true;
	
			if ($clickTimeStamp==$progressTS)
				$isProgress=true;
	
			$inRange=false;
	
			if ($clickTimeStamp >= $pm->BIDRangeArray[0]['ts'] && $numInRange < $GLOBALS['selectRangeSize'])
			{
				for($r = 0; $r < $GLOBALS['selectRangeSize']; $r++)
				{
					if ($clickTimeStamp == $pm->BIDRangeArray[$r]['ts']) 
					{
						$inRange=true;
						$numInRange++;
						break;
					}
				}
			}
	
			$nextPrevMonth=false;
			if ($day[1] == ZMC_Zcalendar::$PRV_MONTH || $day[1] == ZMC_Zcalendar::$NXT_MONTH)
				$nextPrevMonth=true;
	
			$cellClass="commonCalendar ";
	
			$url =  "?dayClickTimeStamp=" . $clickTimeStamp;
	
			if ($isToday)
			{
				if ($inRange)
					$cellClass .= "todayRange ";
				else
					$cellClass .= "today ";
			}
			elseif ($nextPrevMonth)
			{
				if ($inRange)
					$cellClass .= "range ";
				else
					$cellClass .= "nextLast ";
			}
			elseif ($inRange)
				$cellClass .= "range ";
			
			$date->setTimestamp($clickTimeStamp);
			$dateStr = $date->format("Ymd");
	
			if ($clickTimeStamp >= $_SESSION[$subnavInfo."CalMin"] &&
				$clickTimeStamp <= $_SESSION[$subnavInfo."CalMax"]
				&& glob($logs_path . 'amvault.' . $dateStr . '*.log'))
			{
				echo "<td class='".$cellClass."'><a href=$url><font color=\"blue\"><b>$day[0]</b></font></a></td>\n";
			}
			else 
				echo "<td class='".$cellClass."'><font color=\"gray\">$day[0]</font></td>\n";
	
			$dd++;  
			$dayIndex++;
		}
		echo "</tr>\n";
	}
	

	$val = date("Y-m-d",$_SESSION[$subnavInfo."DayClick"]);
	?>
		</table>
		<div class="wocloudButtonBar">
			<input type="submit" name="action" value="Go" />
			<input style='float:right;' name='viewDate' type='text' title='Enter date in yyyy-mm-dd or yy-mm-dd format' class='wocloudShortestInput' maxlength='10' value='<?= $val ?>' />
		</div>
	</div>
	<?
}


private static function mGetLevelIconImage($level, $multi=false,$opaque=false)
{
	switch($level)
	{
		case 0:
			if ($multi)
				return ($opaque)?"icon-multi-level-0.png":"icon-multi-level-0.png";  
			else
				return ($opaque)?"icon-single-level-0.png":"icon-single-level-0.png";

		case 1:
			if ($multi)
				return ($opaque)?"icon-multi-level-1.png":"icon-multi-level-1.png";
			else
				return ($opaque)?"icon-single-level-1.png":"icon-single-level-1.png";

		default:
			if ($multi)
				return ($opaque)?"icon-multi-level-2.png":"icon-multi-level-2.png";
			else
				return ($opaque)?"icon-single-level-2.png":"icon-single-level-2.png";
	}
}

}
