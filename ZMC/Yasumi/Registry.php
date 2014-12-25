<?













class ZMC_Yasumi_Registry extends ZMC_Yasumi
{
	protected function opGet()
	{
		$this->reply['registry'] = ZMC::$registry;
	}

	
}
