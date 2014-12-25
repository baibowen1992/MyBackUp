<?













class ZMC_Yasumi_DeviceProfile extends ZMC_Yasumi_Conf
{
	
	protected $create = false;

	protected function init()
	{
		$this->cache_path = ZMC::$registry->tmp_path . 'device_profile_list';
		parent::init();
		if (!empty($this->data['device_profile_list']))
		{
			foreach($this->data['device_profile_list'] as $key => &$profile)
			{
				if ($profile instanceof Array_Object)
					$profile = $profile->getArrayCopy();
				if (is_array($profile))
					ZMC::unflattenArray($profile); 
			}
			
			$this->normalizeParams($this->data['device_profile_list'], null, 1);
		}
	}

	protected function runFilter()
	{
		if (!empty($this->reply['device_profile_list']))
			ZMC::convertToDisplayUnits($this->reply['device_profile_list']); 

		parent::runFilter();
	}

	protected function opReadProfiles($deviceName = null)
	{
		$this->mkdirIfNotExists(ZMC::$registry->device_profiles);
		$this->cache_path = $cacheFn = 'device_profile_list';
		$this->reply[$cacheFn] = array();
		$deviceFilenames = glob(ZMC::$registry->device_profiles . '*.yml');
		$deviceFilenames[] = ZMC::$registry->device_profiles; 
		if (ZMC::useCache($this->reply, $deviceFilenames, $this->cache_path, false, false))
		{
			if ((include $this->cache_path) === true)
			{
				foreach(array_keys($this->reply[$cacheFn]) as $deviceName)
					if (!file_exists(ZMC::$registry->device_profiles . $deviceName . '.yml'))
						unset($this->reply->device_profile_list[$deviceName]);
				$this->debugLog('Read ' . count($this->reply[$cacheFn]) . ' cached device profiles.');
				return;
			}
		}
		array_pop($deviceFilenames);

		$err = 'Device not found: check spelling, permissions, connections ...';
		$dev_meta = ZMC_Type_Devices::get();
		foreach($dev_meta as &$adevice)
			unset($adevice['form']);

		foreach($deviceFilenames as $fn)
		{
			$name = substr(basename($fn), 0, -4);
			unset($device);
			$device =& $this->loadYaml($fn);
			$this->reply['device_profile_list'][$name] =& $device;
			$device['private']['profile'] = true; 
			
			if (!strncmp($device['_key_name'], 'tape', 4))
			{
				$this->reply->addError('Standalone, single tape devices are not supported in this version of this product.');
				continue;
				if (!is_array(ZMC::$registry->tapedev_list[$device['changer']['tapedev_prefix']])){
					$device['stderr'] = $err;
					$this->reply->addWarning($name .": Tape ".strtolower($err));
					continue;
				} else {
					$this->merge($device, ZMC::$registry->tapedev_list[$device['changer']['tapedev_prefix']]);
				}
			}
			elseif (!strncmp($device['_key_name'], 'changer', 7)) 
			{
				if($device['_key_name'] === 'changer_ndmp')
				{
					$device['changer']['changerdev'] = $device['changerdev'];
				} else {
					if (empty($device['changer']['changerdev']))
					{
						$this->reply->addError("Empty changer device", $device);
						continue;
					}
						$this->merge($device['private'], ZMC::$registry->changerdev_list[$device['changer']['changerdev']]);
				}
			}
			$device['dev_meta'] = $dev_meta[$device['_key_name']];
		}

		if (false === file_put_contents($fn = $this->cache_path, '<? $this->reply[\'device_profile_list\'] = ' . var_export($this->reply['device_profile_list'], true) . ";return true;\n", LOCK_EX))
			throw new ZMC_Exception("Unable to write to \"$fn\"" . ZMC::getFilePermHelp($fn));
	}

	





	protected function &opMerge()
	{
		$this->opReadProfiles();
		if (count(array_diff_key($this->data['device_profile_list'], $this->reply['device_profile_list'])))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Merge contains new profile.  Use "create" instead.'));
		$this->mergeWriteDeviceList();
		$this->regenerateBindings();
		$null = null;
		return $null; 
	}

	protected function mergeWriteDeviceList()
	{
		$time = ZMC::occTime();
		if (!is_array($this->data['device_profile_list']) || empty($this->data['device_profile_list']))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Missing device_profile_list. Normally sent via JSON POST.'));

		$changerdevUser = array();
		foreach($this->data['device_profile_list'] as $key => &$profile)
		{
			$this->addLastModified($profile['private']);
			if (isset($profile['private']['occ']) && $profile['private']['occ'] < $this->reply['device_profile_list'][$key]['private']['occ'])
			{
				$msg = $this->reply->addWarnError("Profile has been edited by another user.  Please reload the page, review any changes made by others, and try again.");
				if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
			}

			if (ZMC_Type_Devices::getLicenseGroup($profile['_key_name']) === 'disk'){
				if (!empty($profile['changer']['changerdev_prefix'])){
					$profile['changer']['changerdev'] = $profile['changer']['changerdev_prefix'];
				}
			   	if (!empty($profile['changer']['changerdev']))
					if ($result = $this->checkPath(ZMC::$registry->vtapes_deny, $p = $profile['changer']['changerdev']))
					{
						$msg = $this->reply->addWarnError("$key: Unable to use the requested virtual tape root path: $p\n$result");
						if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
					}
			}

			$id = (empty($profile['changer']['changerdev']) ? $profile['id'] : $profile['changer']['changerdev']);
			$profile['changerfile'] = ZMC::$registry->device_profiles . str_replace('/', '_', $id) . '.state';
			if(in_array($profile['_key_name'], array('disk', 'attached_storage'))){
				unset($profile['changerfile']);
			}

			if ($this->create)
			{
				$this->assertEntitledToCreate($profile['_key_name'], __LINE__);
				$profile['private']['zmc_ags_version'] = ZMC::$registry->zmc_ags_version;
			}
			else
				$this->assertEntitledToUseDevice($key, __LINE__);

			if ($this->create)
				$this->reply['device_profile_list'][$key] =& $profile;
			else
				$this->merge($this->reply['device_profile_list'][$key], $profile);




			if (isset($profile['changer']['changerdev']))
			{
				$otherChangers = array();
				if (ZMC::$registry->offsetExists('changerdev_user'))
					$otherChangers = ZMC::$registry->changerdev_user;

				if (!isset($otherChangers[$profile['changer']['changerdev']])) 
				$changerdevUser[] = $profile['changer']['changerdev'];
			}
		}
		if (!empty($changerdevUser))
			ZMC::$registry->mergeOverride('changerdev_user', $changerdevUser);


		







		$this->mkdirIfNotExists(ZMC::$registry->device_profiles);
		$bak = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . 'device_profiles' . DIRECTORY_SEPARATOR . $key . '-' . ZMC::dateNow(true) . '.yml';
		foreach(array_keys($this->data['device_profile_list']) as $deviceName)
		{
			$yml = $this->dumpYaml($profileArray = $this->reply['device_profile_list'][$deviceName], 5);
			$this->commit($msg = ZMC::$registry->device_profiles . "$deviceName.yml", $bak, null , $yml);
			if (!empty($profileArray['ssl_ca_cert']))
				if (false === file_put_contents($fn = ZMC::$registry->device_profiles . "$deviceName.pem", str_replace('\n', "\n", $profileArray['ssl_ca_cert'])."\n"))
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to save certificate to: $fn"));
		}
		ZMC::auditLog(($this->create ? 'Created new' : 'Updated') . " ZMC device: '$msg'.");
	}

	
	protected function regenerateBindings()
	{
		foreach(ZMC_BackupSet::listConfigs() as $config)
		{
			$this->listDeviceBindings($config, $deviceBindings);
			foreach($deviceBindings as $deviceBinding)
				if (isset($this->data['device_profile_list'][$deviceBinding['profile_name']]) && $this->data['device_profile_list'][$deviceBinding['profile_name']] !== 'NONE')
					ZMC_BackupSet::getStatus($this->reply, $config);
		}
	}

	protected function listDeviceBindings($config, &$result)
	{
		$result = array();
		$this->amanda_configuration_name = $config;
		if ($name = $this->getAmandaConfPath(false)) 
			foreach (glob($name . '*-*.yml', GLOB_NOSORT) as $filename)
			{
				$filename = basename($filename);
				strtok($filename, '-'); 
			   	$profileName = substr(strtok('-'), 0, -4);
				$result[] = array(
					'amanda_configuration_name' => $config,
					'profile_name' => $profileName,
					'active' => !empty($this->reply['config2profiles']) && !empty($this->reply['config2profiles'][$config][$profileName]),
					'filename_readonly' => $filename
				);
			}
	}

	




	public function opCreate()
	{
		$this->create = true;
		$this->checkFields(array('device_profile_list', 'user_id', 'username', 'commit_comment'), array('name'));
		$deviceName = $this->getAmandaConfName();
		if (empty($deviceName))
			$deviceName = key($this->data['device_profile_list']);
		$deviceName = trim(str_replace('-', '_', $deviceName));
		$this->data['type'] = $this->data['device_profile_list'][$deviceName]['_key_name'];
		if (!ZMC::isalnum_($deviceName))
		{
			$msg = $this->reply->addWarnError("Use only alphanumeric characters or the underscore character for device names.  Illegal device name: '$deviceName'");
			if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
		}
		ZMC_Type_Devices::mergeCreationDefaults($this->data['device_profile_list'][$deviceName]);
		$this->mergeWriteDeviceList();
		unlink($this->cache_path); 
		$this->updateStatsCache();
		$this->opReadProfiles(); 
	}

	protected function opDelete()
	{
		$ids = array();
		foreach($this->data['device_profile_list'] as $id => $ignored)
		{
			if (!file_exists($orig = ZMC::$registry->device_profiles . $id . '.yml'))
			{
                $this->reply->addWarning("Deleting '$orig' failed.  Device not found.  Already deleted?");
				continue;
			}
			$bak = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . 'device_profiles' . DIRECTORY_SEPARATOR . $id . '-' . ZMC::dateNow(true) . '.yml';
			$this->commit($orig, $bak, null, '');
			if (file_exists($this->cache_path))
				unlink($this->cache_path);
            if (false === unlink($orig))
                $this->reply->addError("Deleting '$orig' failed. " . ZMC::getFilePermHelp($orig));
			else
				$ids[] = "'$id'";
		}

		if (!empty($ids))
			$this->reply->addMessage("Deleted: " . implode(', ', $ids));

		$this->updateStatsCache();
	}

	protected function assertEntitledToUseDevice($device, $line)
	{
		if (empty($this->reply['device_profile_list']))
			$this->opReadProfiles();
		if (empty($this->reply['device_profile_list'][$device]) || empty($this->reply['device_profile_list'][$device]['_key_name']))
			return false;
		$this->assertEntitledToUseType($this->reply['device_profile_list'][$device]['_key_name'], $line);
	}

	protected function assertEntitledToUseType($_key_name, $line)
	{
		if (!is_string($_key_name))
			$_key_name = $_key_name['_key_name'];

		$licenseGroup = ZMC_Type_Devices::getKey($_key_name, 'license_group');
		if (!isset($this->lstats['licenses']['zmc']['Licensed'][$licenseGroup]) || !($this->lstats['licenses']['zmc']['Licensed'][$licenseGroup] > 0))
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("No valid license found for device type '$licenseGroup'"));
	}

	protected function assertEntitledToCreate($licenseGroup, $line)
	{
		$this->assertEntitledToUseType($licenseGroup, $line); 
	}

	protected function getKeyName($name) 
	{
		if (false === ($file = file_get_contents($fn = ZMC::$registry->device_profiles . $name . '.yml')))
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("Can not find device '$name' at: $fn"));

		$kn = "\n  _key_name: ";
		$pos1 = strpos($file, $kn) + strlen($kn);
		$pos2 = strpos($file, "\n", $pos1);
		$keyName = substr($file, $pos1, $pos2 - $pos1);
		if (empty($keyName))
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("Can not find _key_name for device '$name'."));
		return $keyName;
	}
}
