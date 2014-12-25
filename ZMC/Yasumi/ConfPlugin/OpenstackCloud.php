<?















class ZMC_Yasumi_ConfPlugin_OpenStackCloud extends ZMC_Yasumi_ConfPlugin_S3Compatible
{
protected $usernameKey = 'S3_ACCESS_KEY';

protected function setEndpoint($loc) { }

protected function adjustSlots()
{
	$result = parent::adjustSlots();
	
	
	return $result;
}

public function makeChanger(&$conf)
{
	if ($this->reply->binding_conf['device_property_list']['USE_API_KEYS'] !== 'on')
		ZMC::array_move($conf['device_property_list'], $conf['device_property_list'], array('S3_ACCESS_KEY' => 'USERNAME', 'S3_SECRET_KEY' => 'PASSWORD'));
	parent::makeChanger($conf);
	return $conf;
}
}
