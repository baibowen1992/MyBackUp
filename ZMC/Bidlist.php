<?


















class ZMC_Bidlist
{
	protected $dList = array(); 
	protected $dCurItem;

	protected function __construct()
	{
		$dCurItem=-1;
	}

	protected function mAdd($xBID, $xStatus, $xTime)
	{
		$lEntry = array(
			"bid" => $xBID,
			"status" => $xStatus,
			"time" => $xTime
		);
		$this->dCurItem = $this->mGetNumItem();
		$this->dList[] = $lEntry;
	}

	protected function mAddWithLevel($xBID, $xStatus, $xTime, $xLevel)
	{
		$lEntry = array(
			"bid" => $xBID,
			"status" => $xStatus,
			"time" => $xTime,
			"level" => $xLevel
		);
		$this->dCurItem = $this->mGetNumItem();
		$this->dList[] = $lEntry;
	}

	public function mGetNumItem()
	{
		return count($this->dList);
	}

	protected function mGetCurIndex()
	{
		return $this->dCurItem;
	}

	protected function mSetCurIndex($i)
	{
		$this->dCurItem = $i;
	}

	public function mGetStatus($xIndex=-1)
	{
		$lIndex = ($xIndex==-1) ? $this->dCurItem : $xIndex;
		return $this->dList[$lIndex]['status'];
	}
	 
	public function mGetBID($xIndex=-1)
	{
		$lIndex = ($xIndex==-1) ? $this->dCurItem : $xIndex;
		return $this->dList[$lIndex]['bid'];
	}

	public function mGetTime($xIndex=-1)
	{
		$lIndex = ($xIndex==-1) ? $this->dCurItem : $xIndex;
		return $this->dList[$lIndex]['time'];
	}

	protected function mGetLevel($xIndex=-1)
	{
		$lIndex = ($xIndex==-1) ? $this->dCurItem : $xIndex;
		return $this->dList[$lIndex]['level'];
	}

	public function mGetCumulativeStatus()
	{
		$multiStat="OK";
		for ($c=0; $c < $this->mGetNumItem(); $c++)
		{
			switch($this->mGetStatus($c))
			{
				case "OK":
					break;
				case "WARNING":
					$multiStat="WARNING";
					break;
				case "ERROR":
					return "ERROR";
					break;
			}
		}
		return $multiStat;
	}

	protected function mHasAtleastOneOK()
	{
		for ($c=0; $c < $this->mGetNumItem(); $c++)
			if ($this->mGetStatus($c) === 'OK')
				return true;

		return false;
	}

	protected function mGetNumWithLevelAndStatus($xLevel,$xStatus)
	{
		$lNum = 0;
		for ($c=0; $c < $this->mGetNumItem(); $c++)
			if ($this->mGetStatus($c)==$xStatus && $this->mGetLevel($c)==$xLevel)
				$lNum++;

		return $lNum;
	}

	protected function mGetCumulativeLevel() 
	{
		$multiLevel=0;
		for ($c=0;$c<$this->mGetNumItem();$c++)
		{
			switch($this->mGetLevel($c))
			{
				case 0:
					break;

				case 1:
					$multiLevel=1;
					break;

				default: 
					return 2;  
					break;
			}
		}
		return $multiLevel;
	}

	public function mGetToolTip()
	{
		$str="";
		switch($this->mGetNumItem())
		{
			case 0:  
				break;

			case 1:
				switch($this->mGetStatus(0))
				{
					case "OK":
						$str .= "Normal backup: ".$this->mGetTime(0);
						break;

					case "WARNING":
						$str .= "Backup with Warning: ".$this->mGetTime(0);
						break;

					case "ERROR":
						$str .= "Backup with ERROR: ".$this->mGetTime(0);
						break;		   

					default:
						break;
				}
				break;

			case 2:
			case 3:
			case 4:
				for ($c=0; $c < $this->mGetNumItem(); $c++)
				{
					switch($this->mGetStatus($c))
					{
						case "OK":
							break;

						case "WARNING":
							$str .= " WARN: ";
							break;

						case "ERROR":
							$str .= " ERR: ";
							break;
					}
					$str .= $this->mGetTime($c);
					if ($c < $this->mGetNumItem()-1)
					$str .= " | ";
				}
				break;

			default:
				$multiStat = $this->mGetCumulativeStatus();
				$numBID = $this->mGetNumItem();
				switch($multiStat)
				{
					case "OK":
						$str="$numBID Normal backups";
						break;

					case "WARNING":
						$str="$numBID backups with atleast one Warning";
						break;

					case "ERROR":
						$str="$numBID backups with atleast one Error";
						break;
				}
				break;
		}

		return $str;
	}
}
