<?

















class ZMC_Yasumi_ConfPlugin_Tape extends ZMC_Yasumi_ConfPlugin_Chgsingle
{
	protected function validateBindings()
	{
		foreach(array('tapedev' => 'tape device') as $key => $device)
			$this->checkDevice($key, $device);

		parent::validateBindings();
	}
}
