<?














class ZMC_Yasumi_ConfPlugin_Chgrobot extends ZMC_Yasumi_ConfPlugin
{
	protected $changerPrefix = 'chg-robot'; 

	protected $tape_name = 'Tape'; 

	protected $tapes_name = 'Tapes'; 

	protected function validateBindings()
	{
		if($this->bindings['_key_name'] === 'changer_ndmp')
			return;
		
		if (empty($this->bindings['changer']['tapedev']))
			$this->bindings['changer']['tapedev'] = array();
		if (!is_array($this->bindings['changer']['tapedev']))
			$this->bindings['changer']['tapedev'] = array($this->bindings['changer']['tapedev']);

		foreach($this->bindings['changer']['tapedev'] as $tapedev => $driveslot)
			if (($driveslot === '0') || ($driveslot > 1))
				$this->checkDevice($tapedev, 'tape device');

		if (isset($this->bindings['changer']['changerdev']))
			$this->checkDevice($this->bindings['changer']['changerdev'], 'changer device');

		parent::validateBindings();
	}

	protected function adjustSlots()
	{
		$this->bindings['changer']['slots'] = 0;
		if (isset($this->bindings['changer']['slotrange']))
			if(preg_match('/^((\d+(-\d+)?,?)?){1,}$/', $this->bindings['changer']['slotrange'])){
				$this->bindings['changer']['slots'] = $this->bindings['changer']['slotrange'];
			}
			else{
				$this->bindings['changer']['slots'] = 1;
			}
	}

	public function makeChanger(&$conf)
	{
		$conf['changer']['tape_device'] = array();
		$taper_parallel_write = 0;
		foreach($conf['changer']['tapedev'] as $tapedev => $driveslot)
			if (($driveslot === '0') || ($driveslot > 0))
			{
				$conf['changer']['tape_device'][] = "$driveslot=tape:$tapedev";
				$taper_parallel_write++;
			}
		if(!isset($conf['taper_parallel_write']) || $conf['taper_parallel_write'] >= $taper_parallel_write)
			$conf['taper_parallel_write'] = $taper_parallel_write;
		$conf['changer']['load_poll'] = 
			$conf['changer']['initial_poll_delay'] . 's'
			. ' poll ' . $conf['changer']['poll_drive_ready'] . 's'
			. ' until ' . $conf['changer']['max_drive_wait'] . 's';

		if (empty($conf['changer']['use_slots'])) 
			$conf['changer']['use_slots'] = $conf['changer']['slotrange'];
		else
		{
			$ranges = explode(',', $conf['changer']['use_slots']);
			foreach($ranges as $range)
			{
				if (!strpos($range, '-'))
					$conf['changer']['slots']++;
				else
				{
					list($min, $max) = explode('-', trim($range));
					$conf['changer']['slots'] += ($max - $min +1);
				}
			}
		}
		parent::makeChanger($conf);
	}

	public function createAndLabelSlots()
	{
		try 
		{
			$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($this->getAmandaConfName(), 'inventory'),
				$stdout, $stderr, "$cmd command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());
			$this->reply->addDetail("$stdout$stderr");
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->reply->addInternal($e->getStdout() . "; " . $e->getStderr());
		}
	}

	protected function maxTapecycle()
	{ return $this->bindings['changer']['slots']; }
}
