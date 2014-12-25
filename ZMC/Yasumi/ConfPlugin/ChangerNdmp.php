<?













class ZMC_Yasumi_ConfPlugin_ChangerNdmp extends ZMC_Yasumi_ConfPlugin_Chgrobot
{
	protected $changerPrefix = 'chg-ndmp'; 

	protected function validateBindings()
	{
		if (isset($this->bindings['property_list']['tape_device']))
			$this->bindings['property_list']['tape_device'] = str_replace("\n", ' ', trim($this->bindings['property_list']['tape_device']));
		parent::validateBindings();
	}
	
	public function makeChanger(&$conf)
	{
		$deviceList = explode(" ", $conf['property_list']['tape_device']);
		$taper_parallel_write = 0;
		
		foreach($deviceList as $device){
			if(empty($device))
				continue;
			
			list($driveslot, $tapedev) = explode('=', $device);
			if ($driveslot >= 0)
			{
				$conf['changer']['tape_device'][] = "$device";
				$taper_parallel_write++;
			}
		}
		$conf['taper_parallel_write'] = $taper_parallel_write;
		
		$conf['changer']['load_poll'] =
		$conf['changer']['initial_poll_delay'] . 's'
				. ' poll ' . $conf['changer']['poll_drive_ready'] . 's'
						. ' until ' . $conf['changer']['max_drive_wait'] . 's';

		$conf['changer']['ndmp_auth'] = $conf['property_list']['ndmp_auth'];
		$conf['changer']['ndmp_password'] = $conf['property_list']['ndmp_password'];
		$conf['changer']['ndmp_username'] = $conf['property_list']['ndmp_username'];
		$conf['changer']['use_slots'] = $conf['property_list']['use_slots'];
		unset($conf['property_list']);
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
		
		ZMC_Yasumi_ConfPlugin::makeChanger($conf);
	}
}
