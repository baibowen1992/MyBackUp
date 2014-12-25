<?
















class ZMC_Yasumi_Conf extends ZMC_Yasumi
{
	
	protected $amanda2Id = array();

	
	protected $lstats = null;

	protected function init()
	{
		$this->updateStatsCache('', 'read');
	}

	protected function opRead($what = null)
	{
		$what1 = $what;
		if (empty($what) && !empty($this->data['what']))
			$what = basename($this->data['what']);
		
		
		if ($what === '..')
			$what = '';
		if (empty($what))
			throw new ZMC_Exception_YasumiFatal('Specify &what=(amanda.conf|disklist.conf|etc.yml)');
		
		if (substr($what, -4) === '.yml')
			$this->reply->conf = $this->loadYaml($what);
		else
			$this->reply->conf = $this->readData($what);

		if (!strncasecmp(basename($what), 'disklist', 8)) 
			if (!empty($this->reply['conf']['dle_list']))
				$this->inputFilterDisklist($this->reply['conf']['dle_list']);
			else
				$this->reply['conf']['dle_list'] = array();

		
		return $what;
	}

	protected function inputFilterDisklist(&$dles)
	{
		$numErrs = $this->reply->isErrors();
		$this->amanda2Id = array();
		foreach($dles as $id => &$dle)
			$this->inputFilterDle($id, $dle);

		if ($this->reply->isErrors() > $numErrs) 
			throw new ZMC_Exception_YasumiFatal('Fatal problems detected during input filter/normalization: ' . $this->reply->getErrors());
	}

	





	protected function inputFilterDle($id, &$dle, $restInput = false)
	{
		
		if (empty($dle)) 
		{
			if ($restInput)
			{
				if (isset($this->reply['conf']['dle_list'][$id]))
					return;
				else
					$this->reply->addError("Delete failed. DLE $id not found on disk. Already deleted?");
			}
			else 
				$this->reply->addError("$id empty!");

			return;
		}

		if (!$restInput)
			$this->upgradeDle($id, $dle); 

		$this->normalizeParams($dle); 
		if (!$restInput) 
			if (	empty($dle['property_list'])
				||	empty($dle['property_list']['zmc_type'])
				||	$dle['property_list']['zmc_type'] === 'unknown') 
			{
				$dle['property_list']['zmc_type'] = 'cli';
				return;
			}
		list($list, $host_name, $disk_name) = explode('|', $id, 3);
		$plist =& $dle['property_list']; 
		$plist['zmc_amanda_id'] = "$list|$host_name|" . $this->convertToAmandaIndexPath($disk_name);

		























		$checkForCollision = true;
		if ($restInput) 
		{
			if (!empty($this->data['update'])) 
			{
				if (empty($dle['natural_key_orig'])) 
					return $this->reply->addError("Update to $id requested, but 'natural_key_orig' missing.");

				if ($dle['natural_key_orig'] === $id) 
				{
					if (!isset($this->reply['conf']['dle_list'][$id]))
						return $this->reply->addError("Update to $id requested, but on-disk copy of DLE is missing. Already deleted or moved? (code #" . __LINE__ . ')'); 
					$checkForCollision = false; 
				}
				else 
				{
					
					if (!isset($this->reply['conf']['dle_list'][$dle['natural_key_orig']]))
						return $this->reply->addError("Update to $dle[natural_key_orig] requested, but on-disk copy of DLE is missing. Already deleted or moved? (code #" . __LINE__ . ')'); 
				}
			}
			else 
			{
				if (!empty($dle['natural_key_orig'])) 
					return $this->reply->addError("Creation of $id requested, but 'natural_key_orig' exists ($dle[natural_key_orig]).");
			}
		}

		
		
		if ($checkForCollision && isset($this->amanda2Id[$plist['zmc_amanda_id']])) 
		{
			$this->reply->addEscapedError("$id collides with existing " . ZMC::escape($this->amanda2Id[$plist['zmc_amanda_id']]) . '.  ' . ZMC::$registry->messages['dle_collision']);
			if (!$restInput)
				
				throw new ZMC_Exception_YasumiFatal('@TODO: set ZMC health status for the backup set');
		}

		$this->amanda2Id[$plist['zmc_amanda_id']] = $id;
		if (!isset($dle['inherits']))
			$dle['inherits'] = array();
		if (!empty($plist['zmc_custom_app']))
			$app = $plist['zmc_custom_app'];
		elseif (!empty($plist['zmc_amanda_app'])) 
			$app = $plist['zmc_amanda_app'];
		else 
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("DLE $dle[host_name] $dle[disk_device] must specify 'zmc_amanda_app' or 'zmc_custom_app' property."));
		$base="zmc_$plist[zmc_type]_base";
		$app=strtolower("zmc_{$app}_app");
		$dle['inherits'][$base] = $base;
		$dle['inherits'][$app] = $app;
		if (empty($dle['disk_name']))
			$dle['disk_name'] = $dle['disk_device'];

		
		foreach($this->filterKeys as $key)
			unset($dle[$key]);

		
		foreach(array(
			'natural_key',

			'auth', 
			'amanda_configuration_name',
			'zmc_override_app',
			'amanda_configuration_id',
			
			'verification_status_output',
		) as $prop)
		{
			unset($plist[$prop]);
			unset($dle[$prop]);
		}
		foreach(array(
			'zmc_extended_attributes',
			'zmc_custom_app',
			'zmc_override_app',
			'zmc_amanda_app',
			'zmc_show_advanced',
			'zmc_dle_template',
			'bandwidth_toggle',
		) as $prop)
		{
			if (isset($plist[$prop]) && empty($plist[$prop]))
				unset($plist[$prop]);
		}
		
	}

	



	protected function &readData($filename)
	{
		$readFilename = $this->permCheck($filename);
		try
		{
			ZMC_Yasumi_Parser::parse($readFilename, $contents, $this);
			if (empty($contents))
			{
				$a = array();
				return $a;
			}

			$this->normalizeParams($contents, ZMC::$registry->default_units); 
		}
		catch (ZMC_Exception $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Parsing/Normalizing problem: ' . $e->getMessage() . " for file '$filename'"));
		}

		return $contents;
	}

	






	protected function opMergeWrite()
	{
		$callback = null;
		$this->optional[] = 'amanda_configuration_id';
		$this->optional[] = 'verify'; 
		$this->optional[] = 'update'; 
		$this->optional[] = 'mode';
		$where = $this->opRead();
		$changedList = array();
		
		if (!empty($this->data['where'])) 
			$where = $this->data['where'];
		if (strncasecmp(basename($where), 'disklist', 8)) 
			$this->merge($this->reply['conf'], $this->data['conf']);
		else 
		{
			if (empty($this->data['conf']['dle_list']))
				return; 

			$deleted = $this->crudFilterDisklist($this->reply['conf']['dle_list'], $this->data['conf']['dle_list'], $changedList);
			
			
			if ($where === 'disklist.conf') 
				$this->applyDisklist($this->reply['conf']['dle_list']);
			$callback = 'disklistLicenseCheck';
			if (empty($changedList) && $deleted === 0)
				return;
		}

		foreach(array_keys($changedList) as $key)
			if ($changedList[$key]['strategy'] === 'skip')
				unset($changedList[$key]);
		$this->resetCheckStatus($changedList);
		$this->write($where, $callback);

		if (!empty($this->data['verify']) && !empty($changedList))
			$this->verifyDles($changedList); 
	}

	protected function disklistLicenseCheck($filename)
	{
		$result =& $this->updateStatsCache($filename);
		if (empty($result['over_limit']))
			return true; 
		if (!is_array($result['over_limit']))
			$this->errorLog(__FUNCTION__ . '(): unexpected value for over_limit:' . $result);
		foreach($result['over_limit'] as $licenseGroup => $count)
			$messages[] = "$licenseGroup exceeds license limit by $count";
		
		return implode("\n", $messages);
	}

	protected function applyDisklist($dles)
	{
		$amandaPass = array();
		$files = array();
		foreach($dles as $id => $dle)
		{
			$plist =& $dle['property_list'];
			if (empty($plist['zmc_type']))
				continue;

			$line = '';
			$hostName = $dle['host_name'];
			switch($plist['zmc_type'])
			{
				case 'vmware':
					
					$hostName = $plist['esx_host_name'];
					
					$line = "//$hostName $plist[esx_username]%$plist[esx_password]";
					break;

				case 'ndmp':
					$hostName = $plist['filer_host_name'];
					
					$line = ZMC_Yasumi_Parser::quote($hostName)
						. ' ' . ZMC_Yasumi_Parser::quote($plist['filer_volume'] . $plist['filer_directory'])
						. ' ' . ZMC_Yasumi_Parser::quote($plist['filer_username'])
						. ' ' . ZMC_Yasumi_Parser::quote($plist['filer_password'])
						. ' ' . $plist['filer_auth'];
					break;

				case 'cifs':
					$checkCifs = true;
					if (!empty($plist['zmc_share_username']))
					{
						$len = strlen($dle['disk_device']);
						$disk = $dle['disk_device'];
						for ($n = 1, $i = 0; $i < $len; $i++) 
							if (($dle['disk_device'][$i] === '\\') && $n++ === 4)
							{
								$disk = substr($dle['disk_device'], 0, $i);
								break;
							}

						if (empty($plist['zmc_share_password']))
							$plist['zmc_share_password'] = ''; 

						if (false !== strpos($plist['zmc_share_username'], '%'))
							throw new ZMC_Exception_YasumiFatal($this->reply->addError("DLE $dle[host_name]:$dle[disk_device] must not use a share username containing a '%' (percent) symbol: $plist[zmc_share_username]"));
						$line = ZMC_Yasumi_Parser::quote($disk) . ' ' . ZMC_Yasumi_Parser::quote($plist['zmc_share_username']) . '%' . ZMC_Yasumi_Parser::quote($plist['zmc_share_password']);
						if (!empty($plist['zmc_share_domain']))
							$line .= ' ' . ZMC_Yasumi_Parser::quote($plist['zmc_share_domain']);

					}
					break;

				default:
					break;
			}
			if (!empty($line))
			{
				@$amandaPass[$plist['zmc_type']] .= $line . "\n";
				if (!ZMC::isLocalHost($hostName))
					@$files[$plist['zmc_type']][$hostName] .= $line . "\n";
			}
		}

		if (!empty($checkCifs))
		{
			if (!file_exists($samba = ZMC::$registry->getAmandaConstant('SAMBA_CLIENT')))
				$msg = $this->reply->addWarnError("Please install 'smbclient' package (e.g. \"yum install samba-client\"). Did not find executable at '$samba'");
			elseif (!is_executable($samba = ZMC::$registry->getAmandaConstant('SAMBA_CLIENT')))
				$msg = $this->reply->addWarnError("Unable to execute \"samba\". " . ZMC::getFilePermHelp($samba));
			if (!empty($msg) && ZMC::$registry->safe_mode)
				throw new ZMC_Exception_YasumiFatal($msg);
		}

		foreach($amandaPass as $type => $content)
		{
			$fn = $this->getAmandaConfPath() . ZMC_Type_What::getKey($type, 'amanda_pass');
			if (!empty($content))
			{
				if (false === file_put_contents($fn, $content, LOCK_EX))
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to update password file '$fn'. " . ZMC::getFilePermHelp($fn)));
			}
			elseif (!empty($fn) && file_exists($fn))
				unlink($fn);

			if (!empty($files[$type]))
			{
				$filenames = '';
				foreach($files[$type] as $host => $lines)
				{
					if (false === file_put_contents("$fn.$host", $lines, LOCK_EX))
						throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to update file '$fn.$host'. " . ZMC::getFilePermHelp($fn)));
	
					$filenames .= "$fn.$host ";
				}
				$this->reply->addMessage('Please copy each file to the appropriate Amanda client system. Remove the hostname from the file, when copying. Files: ' . $filenames);
			}
		}
	}

	protected function write($where = null, $callback = null, $conf = null)
	{
		if (empty($conf))
			$conf =& $this->reply['conf'];
		
		$this->checkFields(array('commit_comment', 'what', 'conf', 'username', 'user_id'), array('human', 'where', 'debug', 'timezone', 'timestamp'));
		if (empty($where))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Specify &where=(amanda.conf|disklist.conf)"));
		if ($where[0] !== '/')
			$new = $this->getAmandaConfPath() . $where . '.tmp.'.uniqid();
		else
			$new = $where;

		
		if (!strncasecmp(basename($where), 'disklist', 8)) 
			foreach($this->reply['conf']['dle_list'] as &$dle)
			{
				if (isset($dle['property_list']))
					unset($dle['property_list']['zmc_amanda_id']);
				unset($dle['natural_key_orig']);
			}

		$orig = $this->permCheck($where, true);
		$suffix = substr($where, -4);
		$bak = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR .  $this->getAmandaConfPath() . DIRECTORY_SEPARATOR . basename($where) . '-' . ZMC::dateNow(true) . $suffix;
		$this->normalizeParams($conf, ZMC::$registry->default_units); 
		if ($suffix === '.yml')
			$contents = $this->dumpYaml($conf, 5);
		else
			$contents =& $this->yaml2conf($conf);

		$this->human && print("==>Human: New $orig (#" . __LINE__ . ")<==\n$contents\n"); 

		$this->commit($orig, $bak, $new, $contents, $callback);
		return $contents;
	}

	
	protected function opWrite()
	{
		$this->reply['conf'] = $this->data['conf'];
		$this->write(empty($this->data['where']) ? $this->data['what'] : $this->data['where']);
	}

	







	protected function opReadWrite()
	{
		$what = $this->opRead();
		$where = (empty($this->data['where']) ? $what : $this->data['where']);
		$this->data['conf']['dle_list'] = array();
		if ($disklist = !strncasecmp(basename($what), 'disklist', 8)) 
			$this->crudFilterDisklist($this->reply['conf']['dle_list'], $this->data['conf']['dle_list'], $changedList);
		$this->data['commit_comment'] = __FUNCTION__;
		$this->data['conf'] = true; 
		$this->write($where);
		
	}

	






	protected function crudFilterDisklist(&$final, &$new, &$changedList)
	{
		$changedList = array();
		$this->optional[] = 'update'; 
		$this->optional[] = 'overwrite'; 
		
		$deleted = 0;
		$this->amanda2Id = array();

		foreach($new as $id => &$dle)
		{
			if (empty($dle))
			{
				if (empty($final[$id]))
				{
					$this->reply->addWarning("Delete requested, but already deleted? ($id)");
					continue;
				}

				$deleted++;
				if ($dir = $this->dleHasIndexes($this->getAmandaConfPath(true), $final[$id]['host_name'], $final[$id]['disk_name']))
				{
					ZMC::auditLog($msg = "Marked '$id' as deleted, but indexes remain in '$dir'.");
					$final[$id]['strategy'] = 'skip';
					$final[$id]['property_list']['zmc_status'] = 'deleted';
					$this->markDleUpdated($id, $final[$id], "Deleted. $msg");
				}
				else
				{
					unset($final[$id]);
					ZMC::auditLog($msg = "Deleted '$id' (no indexes exist).");
					$this->reply->addMessage($msg);
				}
				continue;
			}

			if (empty($this->data['update'])) 
				foreach(array_keys($dle) as $key) 
					if ($dle[$key] === null)
						unset($dle[$key]);

			ZMC::unflattenArray($dle, false); 
			if ($dle['property_list']['zmc_type'] === 'cli') 
				continue;

			$numErrs = $this->reply->isErrors();
			$this->inputFilterDle($id, $dle, true);
			if ($this->reply->isErrors() > $numErrs) 
				throw new ZMC_Exception_YasumiFatal($this->reply->addError('Fatal problems detected during input filter/normalization prevented the request from completing successfully.'));

			if (empty($this->data['update'])) 
			{
				if (empty($dle['property_list']['creation_time'])) 
					$dle['property_list']['creation_time'] = ZMC::humanDate(true);
				$this->markDleUpdated($id, $dle, 'Created');
				$final[$id] =& $dle;
				$changedList[$id] =& $dle;
				continue;
			}

			$fid = (empty($dle['natural_key_orig']) ? $id : $dle['natural_key_orig']);
			unset($dle['natural_key_orig']); 
			if (ZMC::$registry->dev_only)
			{
				ksort($dle);
				ksort($final[$fid]);
			}

			if (ZMC::isArraySubset($dle, $final[$fid])) 
			{
				$this->reply->addDetail("Skipping DLE '$id' (no changes)", __FILE__, __LINE__);
				continue;
			}

			if (!empty($this->data['overwrite']))
			{
				$final[$fid] =& $dle; 
				$changedList[$id] =& $dle;
				$this->markDleUpdated($id, $final[$id], 'Edited (overwrite)');
			}
			else
			{
				
				
				foreach(array('zmc_amcheck', 'zmc_amcheck_date', 'zmc_amcheck_code', 'zmc_amcheck_warn') as $prop)
					unset($final[$fid]['property_list'][$prop]);

				foreach(array_keys($final[$fid]['inherits']) as $key)
					if (!strncmp($key, 'zmc_', 4))
					{
						$last = strrchr($key, '_');
						if ($last === '_base' || $last === '_app')
							unset($final[$fid]['inherits'][$key]);
					}
				
				$this->merge($final[$fid], $dle); 
				$changedList[$id] =& $final[$fid];
				$this->markDleUpdated($fid, $final[$fid], 'Edited (merge)');
			}

			if ($this->moveDle($fid, $id, $final)) 
				$this->markDleUpdated($id, $dle, "Moved '$id' to '$fid'");
		}

		if (count($final))
		{
			$hosts = array();
			$disks = array();
			unset($dle); 
			foreach($final as &$dle)
			{
				$hosts[] = $dle['host_name'];
				$disks[] = $dle['disk_name'];
				$this->reply['skip'] += ($dle['strategy'] === 'skip');
			}
			array_multisort($hosts, SORT_ASC, SORT_STRING, $disks, SORT_ASC, SORT_STRING, $final);
			
		}

		if (empty($changedList) && $deleted === 0)
			$this->reply->addWarning('No changes detected to disklist.', null, __FILE__, __LINE__);

		return $deleted;
	}

	private function markDleUpdated($id, &$dle, $msg)
	{
		$dle['property_list']['zmc_occ'] = ZMC::occTime(); 
		if (isset($this->username))
			$dle['property_list']['last_modified_by'] = $this->username;
		$dle['property_list']['last_modified_time'] = ZMC::humanDate(true);
		$this->reply->addMessage($msg = "Updated DLE $id: $msg by " . $this->username);
		ZMC::auditLog($msg);
	}

	private function moveDle($fid, $id, &$final)
	{
		if ($fid === $id)
			return false;
		list($list, $host_name, $disk_name) = explode('|', $id, 3);
		list($origList, $orig_host_name, $orig_disk_name) = explode('|', $fid, 3);
		if ($list !== $origList)
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("Not Supported. Use copy instead. Can not move DLE from list '$origList' to list '$list'"));
		if ($this->dleHasIndexes($this->getAmandaConfPath(true), $orig_host_name, $orig_disk_name))
		{
			if ($host_name !== $orig_host_name) 
				throw new ZMC_Exception_YasumiFatal($this->reply->addError("Cannot move DLE from '$orig_host_name' to '$host_name'"));
			if ($disk_name !== $orig_disk_name) 
				throw new ZMC_Exception_YasumiFatal($this->reply->addError("Cannot move DLE from '$orig_disk_name' to '$disk_name'"));
		}
		$final[$id] =& $final[$fid];
		unset($final[$fid]);
		return true;
	}

	private function dleHasIndexes($amandaConfPath, $host_name, $disk_name)
	{
		if (ZMC::isDirEmpty($dir = "$amandaConfPath/index/$host_name/$disk_name"))
			return false;
		return $dir;
	}

	




	protected function upgradeDle($id, &$dle)
	{
		if (empty($dle['property_list']['zmc_version']))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("DLE '$id' missing 'zmc_version' property."));

		if(isset($dle['property_list']['quiesce']) && !isset($dle['property_list']['zmc_quiesce'])){
			$dle['property_list']['zmc_quiesce'] = $dle['property_list']['quiesce'];
			if($dle['property_list']['quiesce'] == "YES" && $dle['property_list']['zmc_amanda_app'] == "vmware" ){
				$dle['property_list']['zmc_amanda_app'] = "vmware_quiesce_on";
			}
			if($dle['property_list']['quiesce'] == "NO" && $dle['property_list']['zmc_amanda_app'] == "vmware" ){
				$dle['property_list']['zmc_amanda_app'] = "vmware_quiesce_off";
			}
			unset($dle['property_list']['quiesce']);
		}

		if ($dle['property_list']['zmc_version'] > ZMC::$registry->zmc_version)
		{
			$msg = $this->reply->addWarnError("DLE '$id' has 'zmc_version' " . $dle['property_list']['zmc_version']
				. ', but this version of AEE only supports version ' . ZMC::$registry->zmc_version . '.');
			if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
		}

		if ($dle['property_list']['zmc_version'] == ZMC::$registry->zmc_version) 
			return;

		
		$msg = $this->reply->addWarnError('Not supported: Migration of DLE from Yasumi version #'
			. $dle['property_list']['zmc_version'] . ' to version #' . ZMC::$registry->zmc_version
			. ' DLE: ' . $dle['host_name'] . ' ' . $dle['disk_name']);
		if (ZMC::$registry->safe_mode)
			throw new ZMC_Exception_YasumiFatal($msg);
		else
			return; 

		if (!empty($dle['program']))
		{
			$dle['program'] = 'APPLICATION';
			$dle['application'] = array('comment' => 'zmc converted from GNUTAR to amgtar', 'plugin' => 'amgtar');
		}

		$dle['property_list']['zmc_version'] = ZMC::$registry->zmc_version; 
		
	}

	



	protected function opVerifyDles($dles = array())
	{
		$this->optional[] = 'amanda_configuration_id';
		$this->optional[] = 'mode';
		$this->optional[] = 'what';
		$this->optional[] = 'where';
		
		$this->standardFieldCheck();
		$hostnames = array();
		if (empty($dles))
		{
			if (empty($this->data['conf']) || empty($this->data['conf']['dle_list']))
			{
				$where = $this->opRead();
				$dles =& $this->reply['conf']['dle_list']; 
			}
			else
			{
				$dles2verify =& $this->data['conf']['dle_list']; 
				if (is_array(current($dles2verify))) 
					$this->crudFilterDisklist($dles, $dles2verify); 
				else 
				{
					unset($this->data['conf']['dle_list']);
					$where = $this->opRead();
					$allDles =& $this->reply['conf']['dle_list']; 
					foreach($dles2verify as $id => $ignored)
						$dles[$id] =& $allDles[$id]; 
				}
			}
		}

		if (empty($dles))
			return $this->reply->addWarning('No DLEs to verify.');

		$this->resetCheckStatus($dles);
		if (!empty($where)) 
			$lines = explode("\n", $this->write($where, 'disklistLicenseCheck', $this->reply['conf']));

		$this->verifyDles($dles);
	}

	private function resetCheckStatus(&$dles)
	{
		if (empty($dles)) return;
		foreach($dles as &$dle)
		{
			$dle['property_list']['zmc_amcheck'] = 'checking (check host request received ' . ZMC::humanDate(true) . ')';
			foreach(array('zmc_amcheck_date', 'zmc_amcheck_code', 'zmc_amcheck_warn') as $prop)
				unset($dle['property_list'][$prop]);
		}
	}

	private function verifyDles(&$dles)
	{
		if (empty($dles))
			return $this->reply->addWarning('No DLEs to verify.');

		$dateNow = ZMC::dateNow(true);
		$disklistFilename = $this->getAmandaConfPath(true) . 'disklist.' . str_replace(' ', '_', $dateNow) . '.conf';
		foreach($dles as $id => &$dle) 
		{
			$hostnames[$dle['host_name']][$id] = &$dle;
			if (isset($dle['strategy']) && $dle['strategy'] === 'skip')
				unset($dle['strategy']); 
		}
		ksort($hostnames);
		$lines = explode("\n", $this->write($disklistFilename, $dles));
		
		
		
		

		$cmd = ZMC::getAmandaCmd('amcheck');
		if (!$this->synchronousBegin()) 
			return;

		$this->data['commit_comment'] = __FUNCTION__;
		$this->debugLog(__FUNCTION__ . "() - child processing: " . implode(', ', array_keys($hostnames)), __FILE__, __LINE__);
		
		$eventId = time();
		

		
		foreach($hostnames as $hostname => &$dles)
		{
			$plists = array();
			foreach($dles as $id => &$dle)
			{
				if(!preg_match('/^(\\\\)+|\/\//', $dle['disk_name']))
					$add_equal = '=';
				else
					$add_equal = '';
				$args = array('--client-verbose', '-odiskfile=' . basename($disklistFilename), '-c', $this->getAmandaConfName(), '='.$hostname, $add_equal.$dle['disk_name']);
				try
				{


					$command = ZMC_ProcOpen::procOpen('amcheck', $cmd, $args, $stdout, $stderr, 'amcheck command failed unexpectedly', $this->getLogInfo());
					
					$this->setVerificationStatus($eventId, $plists[$id], 0, $stdout, $stderr, $dateNow);
				}
				catch (ZMC_Exception_ProcOpen $e)
				{
					
					$this->setVerificationStatus($eventId, $plists[$id], $e->getCode(), $e->getStdout(), $e->getStderr(), $dateNow);
					
					
					
					
					
					
				}
			}




			
			$where = $this->opRead();
			foreach($plists as $id => $plist)
				if (isset($this->reply['conf']['dle_list'][$id]))
					$this->merge($this->reply['conf']['dle_list'][$id]['property_list'], $plist);

			
			$this->write($where);
			
		}


		if (!$this->debug)
			unlink($disklistFilename);
	}

	protected function setVerificationStatus($eventId, &$plist, $code, $stdout, $stderr, $dateNow) 
	{
		$plist = array(
			'zmc_amcheck_code' => $code,
			'zmc_amcheck_warn' => 0,
			'zmc_amcheck_date' => $dateNow,
		);

		$messages = '';


























	
		foreach(explode("\n", $stdout) as $line)
		{
			if ($line === '' || $line === null)
				continue;
			if ($line[0] === 'O' && $line[1] === 'K')
			{
				if (!strncmp($line, 'OK version', 10))
					$plist['zmc_amcheck_version'] = substr($line, 11);
				elseif(!strncmp($line, 'OK platform', 11))
					$plist['zmc_amcheck_platform'] = ZMC::distro2abbreviation(substr($line, 12));
				elseif(!strncmp($line, 'OK amgtar gtar-version', 22))
					$plist['zmc_amcheck_app'] = substr($line, 23);
				continue;
			}
			if (!strncmp($line, '(brought to you by Amanda', 25))
				break;
			$messages .= "$line\n";
		}
		$output = "$messages$stderr";
		if (false !== strpos($output, 'WARNING:'))
		{
			$plist['zmc_amcheck_warn'] = $code || 1;
			if (false === strpos($output, 'ERROR:'))
				$plist['zmc_amcheck_code'] = '';
		}
		if ($plist['zmc_amcheck_code'] != 0 || $plist['zmc_amcheck_warn'] != 0)
			$plist['zmc_amcheck'] = ZMC_Yasumi_Parser::quote(wordwrap($stderr) . wordwrap($stdout));
				
		else
		{
			$plist['zmc_amcheck'] = null;
			$plist['zmc_amcheck_code'] = null;
			$plist['zmc_amcheck_warn'] = null;
		}
		










	}

	public function convertToAmandaIndexPath($path) 
	{
		return strtr($path, "\\/:", "___");
	}
}
