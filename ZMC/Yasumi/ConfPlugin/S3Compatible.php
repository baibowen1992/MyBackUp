<?





















class ZMC_Yasumi_ConfPlugin_S3Compatible extends ZMC_Yasumi_ConfPlugin_Chgmulti
{
protected $changerPrefix = 'chg-multi:s3'; 

protected $bucketManager;

protected $usernameKey = 'S3_ACCESS_KEY';

public function makeBindings($profileName, $deviceProfile, $bindingFilename, $newBindings, $action)
{
	return(parent::makeBindings($profileName, $deviceProfile, $bindingFilename, $newBindings, $action));
}

protected function adjustSlots()
{
	if ($this->action !== 'create')
		$this->slots_in_bucket = $this->bindings['changer']['slotrange']; 
	else
	{
		$changerdev = $this->bindings['changer']['changerdev'];
		
		$this->bucketManager = ZMC_A3::createSingleton($this->reply, $this->bindings);
		
		$list = $this->bucketManager->listBucket($changerdev, true);
		$this->slots_in_bucket = (is_array($list) ? count($list):false);
		if ($this->slots_in_bucket > 0)
		{
			$suffixLen = strlen($suffix = 'special-tapestart');
			foreach(array_keys($list) as $objectName)
				if (substr($objectName, -$suffixLen) !== $suffix)
					$used = true;
				
			if (!empty($used)) 
			{
				$this->reply->addWarnError("Previously used cloud bucket. Please manually move or remove '$changerdev', before continuing. Found objects in this cloud bucket: " . implode("\n", array_keys(array_slice($list, 0, 10))));
				if (ZMC::$registry->safe_mode) return;
			}
		}
	}

	$this->slotsRequested = (empty($this->bindings['changer']['slots']) ? 1 : $this->bindings['changer']['slots']);
	if ($this->tooManySlotsRequested($this->slotsRequested))
		return;

	ZMC_BackupSet::getTapeList($this->reply, $this->tapeList, $this->getAmandaConfName(true));
	$this->dirOk = array();
	$error = false;
	$this->sorted = array();

	
	if (!empty($this->tapeList['tapelist']))
	{
		foreach($this->tapeList['tapelist'] as &$tape)
			$this->sorted[ltrim(substr($tape['label'], strrpos($tape['label'], '-') +1), '0')] = $tape['label'];
		krsort($this->sorted, SORT_NUMERIC);
		foreach($this->sorted as $slotNumber => $slot)
		{
			if (!is_integer($slotNumber) || ($slotNumber < 1))
			{
				$error = "DLE container '$slot' is not a valid name.";
				break;
			}

			if ($slotNumber > $this->slotsRequested)
				$this->bindings['changer']['slots'] = $this->slotsRequested = $slotNumber; 

			if ($slotNumber > $this->bindings['max_slots'])
			{
				$error = "The existing DLE container '$slot' exceeds the maximum permitted for this device ({$this->bindings['max_slots']}). The maximum can be increased using the advanced settings on the Admin|devices page.";
				break;
			}
			$this->dirOk[$slotNumber] = true;
		}
	}
	$this->bindings['changer']['slots'] = min($this->slotsRequested, $this->bindings['max_slots']);
	parent::adjustSlots();

	if ($error)
		return $this->reply->addWarnError($error);
}

public function makeChanger(&$conf)
{
	$conf['device_property_list']['CREATE-BUCKET'] = 'off'; 
	if (!empty($conf['device_property_list']['S3_SERVICE_PATH']))
		$conf['device_property_list']['S3_SERVICE_PATH'] = rtrim($conf['device_property_list']['S3_SERVICE_PATH'], '/');
	if (empty($conf['device_property_list']['S3_SERVICE_PATH']))
		unset($conf['device_property_list']['S3_SERVICE_PATH']);
	$conf['device_property_list']['SSL_CA_INFO'] = (empty($conf['ssl_ca_cert']) ? ZMC::$registry->curlopt_cainfo : str_replace('.state', '.pem', $conf['changerfile']));
	$conf['changer']['changerdev'] .= '/' . $this->getAmandaConfName(true) . "-tape{1.." . $conf['changer']['slots'] . '}';
	$conf['schedule']['runtapes'] = 1000;
	parent::makeChanger($conf);
	if(empty($conf['taper_parallel_write']))
		$conf['taper_parallel_write'] = 1;
	return $conf;
}



public function createAndLabelSlots()
{
	
	$labeled = 0;
	$numErrors = $this->reply->isErrors();
	$config = $this->getAmandaConfName(true);
	$tapeList = array();
	for($slot = 1; $slot <= $this->reply->binding_conf['changer']['slots']; $slot++)
	{
		if (!empty($this->dirOk[$slot]))
			continue;

		if (empty($this->bucketManager))
		{
			if (empty($this->reply->binding_conf))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to create labels in the ZMC cloud bucket."));
			$this->bucketManager = ZMC_A3::createSingleton($this->reply, $this->reply->binding_conf);
		}

		if ($this->slots_in_bucket === false)
		{
			
			
			if (false === $this->bucketManager->createBucket())
				break;
			$this->slots_in_bucket = true;
		}
		$label = "$config-" . str_pad($slot, (strlen($slot) > 4)? strlen($slot): 4, '0', STR_PAD_LEFT); 
		if (false === $this->bucketManager->createTape($config, $slot, $label))
			break; 
		$labeled++;
		$this->tapeList['tapelist'][$label] = $tapeList[$label] = array(
			'timestring' => 0,
			'label' => $label,
			'reuse' => 'reuse',
		);
	}

	if ($labeled)
	{
		if (ZMC::$registry->debug) $this->reply->addMessage("Created $labeled new containers for DLE backup images.");
		ZMC_BackupSet::putTapeList($this->reply, $tapeList, $config, true);
	}
	if ($this->reply->isErrors() > $numErrors)
		throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Failed to create all needed labels in the ZMC cloud bucket."));
}





protected function addSyntheticKeys()
{
	parent::addSyntheticKeys();
	$this->bindings['max_dle_by_volume'] = 1;
	$this->bindings['changer']['changerdev'] = str_replace('--', '-', 'zmc-' . strtolower(preg_replace('/[^-a-zA-Z0-9]+/', '-',
		$this->bindings['device_property_list'][$this->usernameKey] . '-' .  $this->getAmandaConfName(true))));
	$this->bindings['changerfile'] = ZMC::$registry->device_profiles . $this->bindings['private']['zmc_device_name'] . '.state';

	if ($this->action === 'defaults')
		return;

	$loc = empty($this->bindings['device_property_list']['S3_BUCKET_LOCATION']) ? '' : $this->bindings['device_property_list']['S3_BUCKET_LOCATION'];
	$this->setEndpoint($loc);
	$endpoint = strtok($this->bindings['device_property_list']['S3_HOST'], ':');
	if (empty($endpoint))
		return $this->reply->addWarnError("Please specify the Cloud endpoint (host name).");

	if (!ZMC::isValidHostName($endpoint))
		return $this->reply->addWarnError("$endpoint: Please specify a valid host name for the Cloud endpoint.");

	if (filter_var($endpoint, FILTER_VALIDATE_IP) === FALSE)
		if (!checkdnsrr($endpoint, 'ANY'))
			$this->reply->addWarning("DNS Error: ZMC can not contact the Cloud at the selected location '$loc' using the Cloud's endpoint: $endpoint");
}

protected function setEndpoint($loc)
{
	if (isset(ZMC_Type_Where::$cloudRegions[$this->bindings['_key_name']]))
	{
		if (!isset(ZMC_Type_Where::$cloudRegions[$this->bindings['_key_name']][$loc]))
			$loc = '';
		$this->bindings['device_property_list']['S3_HOST'] = ZMC_Type_Where::$cloudRegions[$this->bindings['_key_name']][$loc][1];
	}
}

protected function validateBindings()
{
	if (preg_match('/[^-a-zA-Z0-9]/', $this->bindings['changer']['changerdev']))
		$this->reply->addWarnError("The access key '" . $this->bindings['changer']['changerdev'] . "' contains non-alphanumeric characters (avoid IP address formats and periods)"); 

	parent::validateBindings();
}

public function cleanup()
{  }

public function purgeMedia()
{
	$this->reply->device =& $this->reply->binding_conf;
	$this->bucketManager = ZMC_A3::createSingleton($this->reply, $this->reply->device);
	$this->bucketManager->deleteBuckets(array($this->reply->device['changer']['changerdev'] => true), false);
}
}
