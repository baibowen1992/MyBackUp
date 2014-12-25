<?





















class ZMC_Yasumi_ConfPlugin_S3Cloud extends ZMC_Yasumi_ConfPlugin_S3Compatible
{
public function makeBindings($profileName, $deviceProfile, $bindingFilename, $newBindings, $action)
{
	if (!empty($deviceProfile['certificate_file']))
	{
		$fn = $deviceProfile['certificate_file'];
		if (dirname($fn) !== '/etc/zmanda/zmc/s3certs')
			return $this->reply->addError("Please specify a valid S3 certificate file (illegal path: tried to use '$fn').");
		if (!ZMC_BackupSet::isValidName($this->reply, basename($fn)))
			return $this->reply->addError("Please specify a valid S3 certificate file (illegal filename: tried to use '$fn').");
		if (!is_readable($fn))
			if (file_exists($fn))
				return $this->reply->addInternal("Can not read S3 certificate file. Please check permissions/ownership of '$fn'");
			else
				return $this->reply->addError('Can not create S3 device without a S3 certficate.');
		$lines = file($deviceProfile['certificate_file']);
		foreach($lines as $line)
		{
			list($ignored, $name, $ignored, $value) = explode('"', $line);
			$newBindings['device_property_list'][$name] = $value;
		}
		unset($deviceProfile['certificate_file']);
	}
	return(parent::makeBindings($profileName, $deviceProfile, $bindingFilename, $newBindings, $action));
}
}
