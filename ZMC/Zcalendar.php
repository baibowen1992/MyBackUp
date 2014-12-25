<?













define ('SEC_IN_DAY', 86400);

class ZMC_Zcalendar
{
	public $MAX_DAYS;
	public $dayPointer =  0;
	public $currentDay;
	public $dayArray = array();
	public $skipEnds =false;
	public $curMonth;
	public $curYear;

	
	public $timeStampLowBound;
	public $timeStampHighBound;
	public static $PRV_MONTH=-8;
	public static $NXT_MONTH=-9;
	public $ERR_MSG;

	public function __construct($month=null,$year=null) 
	{
		$this->curMonth = $month;
		$this->curYear  = $year;
		if ($month != null && $year != null)
			$this->setMonthYear($month,$year);
	}

	private function computeDayArray($month, $year)
	{
		$dayPointer =  0;
		$tmp = date("d | w",mktime(0,0,0,$month,1,$year));
		$firstDay = explode(" | ",$tmp);
		$this->MAX_DAYS = $this->DaysInMonth($month,$year);
		$this->dayArray = array(); 

		
		if ($firstDay[1] > 0)  
		{
			$prevMonth	 = $month;
			$prevMonthYear = $year;

			
			$prevMonth -=1;
			if ($prevMonth==0)
			{
				$prevMonth=12;
				$prevMonthYear-=1;
			}

			$prevMonthMaxDays= $this->DaysInMonth($prevMonth,$prevMonthYear);
			$prevMonthDay = $prevMonthMaxDays-$firstDay[1]+1;

			$this->timeStampLowBound  = mktime(0,0,0, $prevMonth,$prevMonthDay,$prevMonthYear);

			
			for($x = 0; $x < $firstDay[1]; $x++) 
			{
				$this->dayArray[]  = array($prevMonthDay, self::$PRV_MONTH);
				$prevMonthDay++;
			}
		}
		else
			$this->timeStampLowBound  = mktime(0,0,0, $month,1,$year);

		
		$y = $firstDay[1];
		for($x = 1; $x <= $this->MAX_DAYS; $x++) 
		{
			$this->dayArray[] = array($x,$y);
			if ($y == 6)
				$y = 0;
			else
				$y++;
		}

		if ($y <= 6 && $y != 0 ) 
		{
			$nextMonthDay=1;
			for($x=$y;$x <= 6; $x++) 
			{
				$this->dayArray[] = array($nextMonthDay, self::$NXT_MONTH);
				$nextMonthDay++;
			}
		}

		$formatStr = "+".count($this->dayArray)." days";
		$this->timeStampHighBound  = strtotime($formatStr,$this->timeStampLowBound);
	}

	public static function decrementMonth($month, $year)
	{
		$month--;
		if ($month == 0)
		{
			$month=12;
			$year--;
		}
	}

	public static function incrementMonth($month, $year)
	{
		$month++;
		if ($month == 13)
		{
			$month=1;
			$year++;
		}
	}

	function setMonthYear($month,$year)
	{
		$this->curMonth = $month;
		$this->curYear  = $year;
		$this->computeDayArray($month,$year);
	}

	function nextDay() 
	{
		if ($this->dayPointer > count($this->dayArray)) 
		{
			$this->ERR_MSG = "no more days";
			$this->currentDay = array("",-1);
			return false;
		}

		$curDay = $this->dayArray[$this->dayPointer];

		if ($this->skipEnds) 
		{
			if( $curDay[1] == 6 )
				$this->dayPointer +=2;
			if( $curDay[1] == 0 )
				$this->dayPointer ++;

			$curDay = $this->dayArray[$this->dayPointer];
		}

		$this->dayPointer++;
		$this->currentDay = $curDay;
		return true;
	}

	function hasMoreDays() 
	{
		return ($this->dayPointer < count($this->dayArray));
	}

	function  DaysInMonth($month,$year)
	{
		
		return 31 -((($month-(($month<8)?1:0))%2)+(($month==2)?((!($year%((!($year%100))?400
					:4)))?1:2):0));
	}

	
	function getIndexInDayArray($timeStamp)
	{
		if ($timeStamp < $this->timeStampLowBound || $timeStamp > $this->timeStampHighBound)
			return -1;
		else
		{
			$lFloat = (($timeStamp-$this->timeStampLowBound)/SEC_IN_DAY);
			$lInt   = (int)(($timeStamp-$this->timeStampLowBound)/SEC_IN_DAY);
			
		
			
			
			
			if ($lFloat == $lInt)
				return $lInt;
			else
			{
				$d = 0;
				$ts = $this->timeStampLowBound;
				while ($ts < $timeStamp)
				{
					$ts = strtotime("+1 day",$ts);
					$d++;
				}
				return $d;
			}
		}
	}

	function getTimeStampAtIndex($index)
	{
		return $this->timeStampLowBound + ($index * SEC_IN_DAY);
	}
}
