<?


























































class ZMC_Yasumi_DeviceBinding extends ZMC_Yasumi_DeviceProfile
{
	
	private $deviceName = null;

	
	private $confFilename = null;

	
	private $ymlFilename = null;

	
	protected $bindingName = null;

	
	private $plugin = null;

	
	protected $create = false;

	
	protected $profileList = array();

	protected function init()
	{
		parent::init();
		if ($this->operation === 'all')
			return;

		if (!empty($this->data['binding_conf']))
		{
			ZMC::unflattenArray($this->data['binding_conf']); 
			if (!strncmp($this->operation, 'create', 6)) 
				ZMC_Type_Where::mergeCreationDefaults($this->data['binding_conf']); 
			$this->normalizeParams($this->data['binding_conf']); 
		}

		$this->checkFields(array('binding_name', 'user_id', 'username'), array('commit_comment', 'binding_conf', 'debug', 'timestamp', 'timezone', 'human'));

		if ($this->operation === 'duplicate')
		{
			if (!ZMC_BackupSet::isValidName($this->reply, $this->data['binding_name']))
			{
				$msg = $this->reply->addWarnError('Illegal backup set name: \'' . $this->data['binding_name'] . "'");
				if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
			}
		}
		elseif (!ZMC::isalnum_($this->data['binding_name']))
		{
			$msg = $this->reply->addWarnError('Use only alphanumeric characters or the underscore character for device names.  Illegal device name: \'' . $this->data['binding_name'] . "'");
			throw new ZMC_Exception_YasumiFatal($msg);
		}

		if (substr($this->data['binding_name'], -5) === '.conf')
			$deviceName = substr($this->data['binding_name'], 0, -5);
		elseif (substr($this->data['binding_name'], -4) === '.yml')
			$deviceName = substr($this->data['binding_name'], 0, -4);
		else
			$deviceName = $this->data['binding_name'];

		$this->reinit($deviceName);
	}

	protected function reinit($deviceName)
	{
		$this->deviceName = $deviceName;
		$this->bindingName = "binding-$deviceName";
		$this->ymlFilename = $this->getAmandaConfPath() . $this->bindingName . '.yml';
		$this->profileLinkFilename = $this->getAmandaConfPath() . $deviceName . '.profile';
		$this->profileFilename = ZMC::$registry->device_profiles . $deviceName . '.yml';
		$this->confFilename = $this->getAmandaConfPath() . $this->bindingName . '.conf'; 
		$linkStat = stat($this->profileLinkFilename);
		$profileStat = stat($this->profileFilename);
		if ($linkStat === false && $profileStat === false) 
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Missing profile: ' . $this->profileFilename . '  See: ' .  ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . 'device_profiles' . DIRECTORY_SEPARATOR));

		if ($linkStat && $profileStat)
			if ($linkStat['ino'] === $profileStat['ino'])
				return; 
			else 
			{
				$linkContents = file_get_contents($this->profileLinkFilename);
				$profileContents = file_get_contents($this->profileFilename);
				if ($linkContents !== $profileContents) 
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('ZMC device profile synchronization error (' . $this->profileFilename . ' <> ' . $this->profileLinkFilename . ')'));
				unlink($this->profileLinkFilename); 
			}

		$err = 'Unable to link ZMC device profile to: ';
		if ($profileStat)
		{
			$result = link($this->profileFilename, $this->profileLinkFilename);
			$warning = 'Repaired backup set device profile.';
			$err .= $this->profileLinkFilename . '. Please make sure both directories are on the same partition. '
				. ZMC::getFilePermHelp($this->profileFilename);
		}
		else
		{
			$result = link($this->profileLinkFilename, $this->profileFilename);
			$warning = 'Created missing device profile, using device information from backup set: ' . $this->getAmandaConfName();
			$err .= $this->profileFilename . '. Please make sure both directories are on the same partition. '
				. ZMC::getFilePermHelp($this->profileLinkFilename);
		}

		if ($result === false)
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal($err));

		if (!empty($warning))
			$this->reply->addWarning($warning);
	}

	protected function runFilter()
	{
		if (!empty($this->reply['binding_conf']))
			ZMC::convertToDisplayUnits($this->reply['binding_conf']); 

		if (!empty($this->reply['binding_list']))
			foreach($this->reply['binding_list'] as &$binding)
				ZMC::convertToDisplayUnits($binding); 

		parent::runFilter();
	}

	protected function opAll()
	{
		$this->optional[] = 'errors_only_for';
		$this->reply['binding_list'] = array();
		foreach(glob(ZMC::$registry->etc_amanda . '*/binding-*.yml', GLOB_NOSORT) as $pathname)
		{
			list($ignored, $deviceName) = explode('-', substr(basename($pathname), 0, -4));
			if (!empty($deviceName))
			{
				$this->amanda_configuration_name = basename(dirname($pathname));
				if ($this->amanda_configuration_name[0] === '.')
					continue;
				if (isset($this->data['only_sets']) && !isset($this->data['only_sets'][$this->amanda_configuration_name]))
					continue;
				$this->reinit($deviceName);
				try{ $binding = $this->getBindings('read', (!empty($this->data['errors_only_for']) && ($this->data['errors_only_for'] !== $this->amanda_configuration_name))); }
				catch(Exception $e)
				{	continue; }
				if (!empty($binding)) 
					$this->reply['binding_list'][$this->ymlFilename] =& $binding->binding_conf;
			}
		}
		if ($this->reply->offsetExists('binding_conf'))
			$this->reply->offsetUnset('binding_conf');
		$this->reply['history'] .= __FUNCTION__ . __LINE__ . ' unset binding_conf; created binding_list';
	}

	




	protected function &opReadYamlBinding()
	{
		$this->reply['binding_conf'] = $this->readYamlBinding();
	}

	protected function opPurge()
	{
		$this->getBindings('read');
		$this->plugin->purgeMedia();
		foreach($this->reply['binding_conf']['holdingdisk_list'] as $holding)
			if ($result = ZMC::rmrdir($holding['directory']))
				$this->reply->addWarnError("Deletion of $holding[directory] failed.\n$result");
	}

	









	protected function &opMerge($regenerate = false)
	{
		$this->checkFields(array('commit_comment', 'binding_conf'));
		$this->assertEntitledToUseDevice($this->deviceName, __LINE__);
		$action = 'merge';
		if ($regenerate)
			$action = 'regenerate';
		if ($this->create)
			$action = 'create';

		$this->getBindings($action, false, $this->data['binding_conf']);
		if ($this->reply->isErrors())
			throw new ZMC_Exception_YasumiFatal('Errors occurred while trying to update device configuration.  Update operation aborted.');

		if (empty($regenerate) && is_readable($this->ymlFilename)) 
		{
			$onDiskBindings = $this->readYamlBinding(); 
			$this->merge($onDiskBindings, $this->reply['binding_conf']); 
			ksort($onDiskBindings);
		}


		$defaultBindings = $this->getBindings('defaults', true); 
		$yaml =& $this->plugin->getBindingYaml(); 
		if(isset($this->reply['binding_conf']['_key_name']) && $this->reply['binding_conf']['_key_name'] == "changer_library"){
			if(!isset($this->reply['binding_conf']['changer']['slotrange'])){
				if(isset($this->reply['binding_conf']['changer']['firstslot']) && isset($this->reply['binding_conf']['changer']['lastslot'])){
					$this->reply['binding_conf']['changer']['slotrange'] = $this->reply['binding_conf']['changer']['firstslot'] ."-".$this->reply['binding_conf']['changer']['lastslot'];
					$this->reply['binding_conf']['changer']['slots'] = $this->reply['binding_conf']['changer']['firstslot'] ."-".$this->reply['binding_conf']['changer']['lastslot'];
					unset($yaml['changer_list'][$this->deviceName]['property_list']['firstslot']);
					unset($yaml['changer_list'][$this->deviceName]['property_list']['lastslot']);
				}
			}else{
					unset($yaml['changer_list'][$this->deviceName]['property_list']['firstslot']);
					unset($yaml['changer_list'][$this->deviceName]['property_list']['lastslot']);

			}
		}
		if(isset($this->reply['binding_conf']['private']['bandwidth_toggle']) && $this->reply['binding_conf']['private']['bandwidth_toggle'] == 'on')
		{	

			unset($yaml['changer_list'][$this->deviceName]['device_property_list']['MAX_RECV_SPEED']);
			unset($yaml['changer_list'][$this->deviceName]['device_property_list']['MAX_SEND_SPEED']);
			unset($yaml['changer_list'][$this->deviceName]['device_property_list']['NB_THREADS_BACKUP']);
			unset($yaml['changer_list'][$this->deviceName]['device_property_list']['NB_THREADS_RECOVERY']);
		}
		if (empty($onDiskBindings))
			$onDiskBindings = $this->reply['binding_conf'];

		$this->removeKeys($this->reply['binding_conf'], $defaultBindings['binding_conf'], 'default of');

		if(isset($this->data['binding_conf']['_key_name']) && $this->data['binding_conf']['_key_name'] == "attached_storage"){

			if(!isset($this->reply['binding_conf']['changer']['changerfile'])){
				$this->reply['binding_conf']['changer']['changerfile'] = rtrim($this->reply['device_profile_list'][$this->deviceName]['changer']['changerdev_prefix'], " /") . "/". $this->data['binding_conf']['config_name'] ."/state";
			}
		}

		$this->writeYamlBinding($this->reply['binding_conf']); 
		$this->reply->binding_conf =& $onDiskBindings; 
		$this->reply->binding_conf['_key_name'] = $this->reply['device_profile_list'][$this->deviceName]['dev_meta']['_key_name'];
		$this->reply['history'] .= __FUNCTION__ . __LINE__ . ' created binding_conf';
		unset($yaml['dev_meta']);
		unset($yaml['changer_list'][$this->deviceName]['device_property_list']['USE_API_KEYS']);
		$this->normalizeParams($yaml);
		$conf = $this->yaml2conf($yaml);
		return $conf;
	}

	




	protected function removeKeys(&$list, $keys, $comment)
	{
		foreach($keys as $key => &$value)
		{
			if ($key === 'private') 
				continue;
			
			
			if ($key !== 'holdingdisk_list' && $key !== 'schedule' && array_key_exists($key, $list))
			{
				if (is_array($value) && is_array($list[$key]))
				{
					$this->removeKeys($list[$key], $keys[$key], $comment);
					if (empty($list[$key]))
						unset($list[$key]);
					else
						ksort($list[$key]);
				}
				elseif ($value === null)
					unset($list[$key]); 
				elseif ($value == $list[$key] && (substr($key, -8) !== '_display'))
					unset($list[$key]); 
				
			}
		}

		ksort($list);
		unset($list['schedule']['tapelist']); 
		unset($list['schedule']['days']); 
	}

	



	protected function opCreateOnly()
	{
		$this->assertEntitledToCreate($this->reply['device_profile_list'][$this->deviceName]['_key_name'], __LINE__);
		$this->create = true;
	   	if (file_exists($this->ymlFilename))
		{
			$stats = stat($this->ymlFilename);
			if ($stats['size'] !== 0)
				throw new ZMC_Exception_YasumiFatal($this->reply->addError('Device settings already exists for '
					. $this->getAmandaConfName() . ' at: ' . $this->ymlFilename));
		}

		$this->data['binding_conf']['date_created_comment'] = ZMC::humanDate();
		$this->opMergeAndApply(); 
	}

	
	public function opCreate()
	{
		$fn = $this->getAmandaConfPath(true) . 'amanda.conf';
		copy($fn, "$fn.tmp");
		try
		{
			$this->opActivate(); 
			$this->opCreateOnly();
		}
		catch(Exception $e)
		{
			if (file_exists("$fn.tmp")) 
			{
				unlink($fn);
				rename("$fn.tmp", $fn); 
			}
			throw $e;
		}
	}

	








	public function opRegenerate()
	{
		$this->data['binding_conf'] = array();
		$this->opMergeAndApply('regenerate');
	}

	







	protected function opMergeAndApply($regenerate = false) 
	{
		$this->checkFields(array('commit_comment', 'binding_conf'));
		try
		{
			$path = $this->getAmandaConfPath();
			$name = basename($path);
			$bindingPath = $path . $this->bindingName;
			$conf =& $this->opMerge($regenerate);
			$contents = $prefix = "# DO NOT EDIT.  This file is generated automatically from {$this->ymlFilename}\n";
			$contents .= $conf;
		}
		catch(Exception $e)
		{
			if ($this->create && is_object($this->plugin))
				$this->plugin->cleanup();

			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('error while ' . ($this->create ? 'creating' : 'updating')
				. " device settings '{$this->confFilename}': $e"), $e->getCode());
		}
		try
		{
			if ($this->create)
			{
				$keyName = $this->data['binding_conf']['_key_name'];
				file_put_contents("$path/zmc_backupset_dumptypes", <<<EOD
# Edit this file to modify the effective dumptype used for *all* DLEs belonging to this backup set.
# Edits take effect immediately, so consider the effects of changes on
# Amanda processes already running (if any).

# Only this backup set uses the dumptype below.
# The dumptype "zmc_global_base" (in zmc_user_dumptypes) inherits the dumptype "zmc_backupset_dumptype".
define dumptype zmc_backupset_dumptype {
	zmc_device_$keyName
}

EOD
);
			}

			if (false === file_put_contents($this->confFilename, $contents))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Unable to write device settings to: '
					. $this->confFilename . ' ' . ZMC::getFilePermHelp($this->confFilename)));

			if (!rename($this->ymlFilename . '.new', $this->ymlFilename))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Save failed. Unable to create '" . $this->ymlFilename . "' " . ZMC::getFilePermHelp($this->ymlFilename)));
			$this->checkAmandaConfigAndLocalTests($name);
			$cronFilename = $bindingPath . '.cron';
			if (false === file_put_contents($cronFilename, $prefix . $this->plugin->getCrontab()))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to write to '$cronFilename'. " . ZMC::getFilePermHelp($cronFilename)));
			if (empty($regenerate)) 
				$this->syncCron();

			if ($this->create) 
				$this->updateStatsCache();

			$this->plugin->createAndLabelSlots(); 
		}
		catch(Exception $e)
		{
			if ($this->create || $this->debug || ZMC::$registry->dev_only)
			{
				if ($this->create)
					rename($this->ymlFilename, $this->ymlFilename . '.debug'); 
				else
					copy($this->ymlFilename, $this->ymlFilename . '.debug'); 
				copy($this->profileLinkFilename, $this->profileLinkFilename . '.debug');
				if ($this->create)
					rename($this->confFilename, $this->confFilename . '.debug');
				else
					copy($this->confFilename, $this->confFilename . '.debug');
				$this->plugin->cleanup();
			}

			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('error while ' . ($this->create ? 'creating' : 'updating')
				. " device settings '{$this->confFilename}': $e"), $e->getCode());
		}
	}

	protected function syncCron()
	{
		$results =& $this->command(array(
			'pathInfo' => "/crontab/sync/" . $this->amanda_configuration_name,
			'data' => array(
				'commit_comment' => "sync crontab of " . $this->amanda_configuration_name,
				'cron' => $this->bindingName,
			),
			'post' => null,
			'postData' => null,
		));
		$this->reply->merge($results, null, true); 
	}

	protected function checkAmandaConfigAndLocalTests($name)
	{
		try
		{
			ZMC_ProcOpen::procOpen('amcheck', $cmd = ZMC::getAmandaCmd('amcheck'), $args = array('-l', $name), $stdout, $stderr, "Configuration Self-Test Results Using:");
			$this->reply['amcheck'] = array('stdout' => $stdout, 'stderr' => $stderr);
		}
		catch(Exception $e)
		{
			$out = $e->getStdout() . $e->getStderr();
			if (stripos($out, 'error'))
				throw $e;
			$warnings = trim(preg_replace(array(
					'/WARNING:.*holding disk.*using nothing/i',
					'/WARNING:.*Not enough free space specified in amanda.conf/i',
					'/Amanda Tape Server Host Check\n/i',
					'/------*\n/',
					'/NOTE: skipping tape checks/i',
					'/Server check took 0\..*/i',
					'/WARNING:.*tapecycle.*runspercycle.*/i',
					'/.brought to you by Amanda.*/i'),
				array_fill(0, 16, ''), $out));
			if (!empty($warnings)) $this->reply->addWarning((ZMC::$registry->debug ? $e->getFile() . '#' . $e->getLine() . ' ':'') . $e->getMessage() . " $warnings");
		}
	}

	
	protected function getBindings($action, $cleanbox = false, $newBindings = array())
	{
		if (($action === 'read' || $action === 'delete') && !file_exists($this->ymlFilename))
		{
			if ($cleanbox)
				return;
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Can not read -> missing backup set device binding: $bindingFilename"));
		}

		
		if (!file_exists($this->profileFilename))
		{
			$this->mkdirIfNotExists(dirname($this->profileFilename), true); 
			if (file_exists($this->profileLinkFilename) && link($this->profileLinkFilename, $this->profileFilename))
				$this->reply->addWarning("Repaired missing profile by linking '$this->profileLinkFilename' to '$this->profileFilename'.");
			elseif ($cleanbox)
				return;
			else
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to repair missing profile ($this->profileFilename) used by this backup set (tried to use backup $this->profileLinkFilename."));
		}

		if (empty($this->reply['device_profile_list']))
			$this->opReadProfiles();
		$profile =& $this->reply['device_profile_list'][$this->deviceName];
		$profile['private']['action'] = $action; 

		try
		{
			$class = 'ZMC_Yasumi_ConfPlugin_' . $plugin = implode(array_map('ucfirst', explode('_', $profile['_key_name'])));
			if (!class_exists($class))
			{
				if ($cleanbox)
					return;
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unrecognized device plugin: $plugin"));
			}
			$plugin = new $class($this, array('post' => '', 'postData' => ''), $cleanbox ? null : $this->reply);
			if (!$cleanbox)
				$this->plugin = $plugin;
			return $plugin->makeBindings($this->deviceName, $profile, $this->ymlFilename, $newBindings, $action);
		}
		catch(Exception $e)
		{
			$msg = "Device plugin '" . $profile['_key_name'] . "' reported a problem: $e";
			if ($cleanbox)
			{
				$this->debugLog($msg);
				return;
			}
			$this->reply->addWarnError($msg);
			if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
		}
	}

	


	protected function writeYamlBinding($bindings = null)
	{
		if ($bindings === null)
		{
			if (empty($this->data['binding_conf']))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Missing binding key. Normally sent via JSON POST.'));
			else
				$bindings =& $this->data['binding_conf'];
		}
		$this->addLastModified($bindings['private']);
		
		$bak = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . $this->getAmandaConfName(true) . str_replace(DIRECTORY_SEPARATOR, '_', $this->ymlFilename) . ZMC::dateNow(true);
		if (file_exists($this->ymlFilename))
			$this->permCheck($this->ymlFilename);
		$new = $this->ymlFilename . time();
		$contents = $this->dumpYaml($this->yamlFilterKeys($bindings));
		
		
			$this->commit($this->ymlFilename . '.new', $bak, $new, $contents);
	}

	protected function yamlFilterKeys($yaml)
	{
		if (!empty($yaml['tapetype']) && array_key_exists('length', $yaml['tapetype']) && intval($yaml['tapetype']['length']) <= 0)
		{
			$msg = $this->reply->addWarnError("Device '{$this->deviceName}': tape size ({$yaml['tapetype']['length']}) must not be zero or negative.");
			if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
		}
		return $yaml;
	}

	
	protected function opActivate()
	{
		$this->assertEntitledToUseDevice($this->deviceName, __LINE__);
		file_put_contents($this->getAmandaConfPath() . 'zmc_binding.conf',
			"includefile \"$this->confFilename\"\n"
			. 'tpchanger "' . $this->deviceName . "\"\n");
		
	}

	
	protected function readYamlBinding()
	{
		if (!is_readable($this->ymlFilename))
			return $this->reply->addInternal("Unable to read ({$this->ymlFilename}). " . ZMC::getFilePermHelp($this->ymlFilename));
		$bindings =& $this->loadYaml($this->ymlFilename);
		if (empty($bindings))
			return array();
		return (empty($bindings) ? array() : $bindings);
	}

	protected function getMacros()
	{
		return array(
			'@@ZMC_AMANDA_CONF_PATH@@' => $this->getAmandaConfPath(true),
			'@@ZMC_AMANDA_CONF@@' => $this->getAmandaConfName(true),
			'@@ZMC_DEVICE_NAME@@' => $this->deviceName,
			'@@ZMC_PKG_BASE@@' => ZMC::$registry->install_path,
		);
	}

	
	protected function opDuplicate()
	{
		if (false !== ZMC_BackupSet::installAmandaConf($this->reply, $this->data['dup_name'], ZMC::$registry->etc_amanda . $this->data['binding_name']))
			return;

		if (!copy($this->getAmandaConfPath() . 'disklist.conf', $disklist = "$newDir/disklist.conf"))
			if (!touch($disklist))
				return $this->reply->addInternal("Unable to create: $disklist " . ZMC::getFilePermHelp($disklist));

		if (!touch($tapelist = "$newDir/tapelist"))
				return $this->reply->addInternal("Unable to create: $tapelist " . ZMC::getFilePermHelp($tapelist));

		$this->amanda_configuration_name = $this->data['dup_name'];
	}

	protected function opDefaults()
	{
		$this->getBindings('defaults');
	}

	public function rawYamlBinding($bindingName)
	{
		return $this->loadYaml($this->getAmandaConfPath() . "binding-$bindingName.yml");
	}
}
