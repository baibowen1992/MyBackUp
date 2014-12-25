<?













class ZMC_Yasumi_ConfPlugin extends ZMC_Yasumi 
{
	
	private static $backupMode2amdump = array(
		'auto' => '',
		'full' => '-o DUMPTYPE:zmc_global_base:strategy=noinc',
		'incremental' => '-o DUMPTYPE:zmc_global_base:strategy=incronly',
	);

	
	protected $profileName = null;

	
	protected $bindings = null; 

	
	protected $tapes_name = 'Tapes';

	
	protected $tape_name = 'Tape';

	protected $changerPrefix = ''; 

	protected $labelSlots = array(); 

	protected $slotsRequested = 0;

	


















	public function makeBindings($profileName, $deviceProfile, $bindingFilename, $newBindings, $action)
	{
		if (empty($deviceProfile) || count($deviceProfile) < 3) 
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('empty device profile; ' . print_r($deviceProfile, true)));

		$this->action = $action;
		$this->reply->setPrefix($this->getAmandaConfName(true) . ': '); 
		$this->profileName = $profileName;
		$deviceProfileOcc = isset($deviceProfile['private']['occ']) ? $deviceProfile['private']['occ'] : 1; 
		$this->bindings =& $deviceProfile; 
		unset($this->bindings['private']); 

		if ($action === 'regenerate' || $action === 'merge' || $action === 'read' || $action === 'delete')
		{ 
			if (!file_exists($bindingFilename))
				if (is_dir(ZMC::$registry->etc_amanda . $this->getAmandaConfName(true)))
					return $this->reply->addError("$bindingFilename missing!");
				else
					return $this->reply->addError("Backup set has been deleted!");

			$origBindings =& $this->loadYaml($bindingFilename);
			if (empty($origBindings['private']['profile_occ'])) 
				$this->reply->addDetail(print_r(array($profileName, $deviceProfile, $bindingFilename, $newBindings, $action, $this), true));

			if ($origBindings['private']['profile_occ'] < $deviceProfileOcc)
				if ($action === 'merge')
				{
					$this->reply->addWarnError("Profile is newer than binding on disk. Please return to the Admin|backup sets page to refresh your session, before returning to this page.");
					if (ZMC::$registry->safe_mode) return;
				}
				elseif ($action !== 'regenerate') 
					$this->reply->addWarning("Device profile has been edited, but this backup sets bindings do not yet reflect these changes");
			
			
			if($this->bindings['_key_name'] === "attached_storage" ){
				if(isset($this->bindings['tapetype']['length']) && (int)$this->bindings['tapetype']['length'] > 1){
					unset($origBindings['tapetype']);
				}
			}
			$this->merge($this->bindings, $origBindings);
		}

		
			
		if ($action === 'merge' || $action === 'create')
		{
		   	if (empty($newBindings)) 
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Missing newBindings"));

			if (empty($newBindings['private']['profile_occ']) || empty($newBindings['private']['occ'])) 
			{
				if (ZMC::$registry->dev_only)
					ZMC::quit($newBindings);
				if ($this->debug)
					$this->reply->addDetail("newBindings = " . print_r($newBindings, true));
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Missing OCC"));
			}

			if ($newBindings['private']['profile_occ'] < $deviceProfileOcc)
			{
				
				$this->reply->addWarnError("Aborted. Requested changes might conflict with changes recently made to the device profile definition. OCC:" . $newBindings['private']['occ'] . '<' . $deviceProfileOcc);
				if (ZMC::$registry->safe_mode) return;
			}

			if ($action === 'merge')
				if ($newBindings['private']['occ'] < $origBindings['private']['occ'])
				{
					$this->reply->addWarnError("Aborted. Requested changes might conflict with changes recently made to '$bindingFilename'. OCC:"
						. $newBindings['private']['occ'] . '<' . $origBindings['private']['occ']);
					if (ZMC::$registry->safe_mode) return;
				}

			if ($action === 'merge' || $action === 'create')
				$this->merge($this->bindings, $newBindings);
		}

		$this->addSyntheticKeys();
		if ($this->action !== 'defaults' && $this->action !== 'delete')
		{
			$this->adjustSlots(); 
			$this->tooManySlotsRequested($this->slotsRequested); 
			if (empty($this->bindings['schedule']))
				$this->schedule_errors = $this->reply->addWarnError("Corrupt ZMC backup set configuration files.  Schedule missing."); 
			else
				$this->schedule_errors = $this->calcSchedule($this->bindings['schedule']);
			$this->validateBindings(); 
		}
		$this->bindings['private']['profile_occ'] = $deviceProfileOcc;
		ksort($this->bindings); 
		ksort($this->bindings['changer']); 
		if (!empty($this->bindings['media']))
			ksort($this->bindings['media']); 
		$this->reply->binding_conf =& $this->bindings;
		unset($this->bindings); 
		return $this->reply;
	}

	protected function addSyntheticKeys()
	{
		$this->bindings['isa'] = get_class($this);
		if ($this->action === 'create' || $this->action === 'defaults')
			$this->bindings['private']['zmc_ags_version'] = ZMC::$registry->zmc_ags_version;
		elseif ($this->bindings['private']['zmc_ags_version'] !== ZMC::$registry->zmc_ags_version) 
		{
			$this->reply->addWarnError("Wrong device version.  Found: " . $this->bindings['private']['zmc_ags_version'] . '. Wanted: ' . ZMC::$registry->zmc_ags_version); 
			if (ZMC::$registry->safe_mode) return;
		}
		$this->bindings['private']['binding'] = true; 
		$this->bindings['private']['action'] = $this->action; 
		$this->bindings['private']['occ'] = ZMC::occTime();
		$this->bindings['config_name'] = $this->getAmandaConfName(true);
		$this->bindings['private']['zmc_device_name'] = $this->profileName; 
		if (	($this->action !== 'defaults' && $this->action !== 'read' && $this->action !== 'delete')
			||	(($this->action === 'read') && ($this->data['errors_only_for'] === $this->bindings['config_name'])))
			$this->calculated_holding = $this->calcHolding($this->bindings['holdingdisk_list']);
		if (isset($this->bindings['tapetype']))
			$this->calcSplitsize($this->bindings['tapetype']);
	}

	private function createSchedule(&$schedule, &$defaults)
	{
		$defaults['retention_policy'] = $defaults['dumpcycle'];
		ZMC::merge($defaults, $schedule, true);
		$schedule = $defaults;
		$schedule['status'] = new ZMC_Registry_MessageBox();
		$schedule['status']->setPrefix($this->bindings['config_name'] . ': '); 
		ksort($schedule['custom_dom'], SORT_NUMERIC);
		ksort($schedule['custom_days'], SORT_NUMERIC);
		unset($schedule['full_hours']['']);
		unset($schedule['hours']['']);
		ksort($schedule['full_hours'], SORT_NUMERIC);
		ksort($schedule['hours'], SORT_NUMERIC);
	}

	protected function calcSchedule(&$schedule)
	{
		if (empty($schedule))
			$schedule = array();

		$defaults = array( 
			'custom_days' => array('ignore_this_key' => '', '0' => '', '1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => ''),
			'custom_dom' => array('ignore_this_key' => ''),
			'desired_retention_period' => 7,
			'dumpcycle' => 7,
			'estimated_retention_period' => 'NA',
			'full_hours' => array('ignore_this_key' => '', '0' => '1'),
			'full_minute' => '15',
			'full_hours_same' => 1,
			'historical_retention_period' => 'NA',
			'hours' => array('ignore_this_key' => '', '0' => '1'),
			'minute' => '15',
			'runtapes' => 1,
		);

		
		if (empty($schedule['schedule_type']))
			$schedule['schedule_type'] = 'Every Weekday';

		switch($schedule['schedule_type'])
		{
			
			
			
			
			
			
			
			case 'Every Saturday':
				$defaults['tapecycle'] = 1 + 1;
				$this->createSchedule($schedule, $defaults);
				$schedule['days'] = array(
						'6' => 'auto',
					);
				break;

			case 'Everyday':
				$defaults['tapecycle'] = 7 + 1;
				$this->createSchedule($schedule, $defaults);
				$schedule['days'] = array(
						'0' => 'auto',
						'1' => 'auto',
						'2' => 'auto',
						'3' => 'auto',
						'4' => 'auto',
						'5' => 'auto',
						'6' => 'auto',
					);
				break;

			case 'Every Weekday':
				$defaults['tapecycle'] = 5 + 1;
				$this->createSchedule($schedule, $defaults);
				$schedule['days'] = array(
						'1' => 'auto',
						'2' => 'auto',
						'3' => 'auto',
						'4' => 'auto',
						'5' => 'auto',
					);
				break;

			case 'Incremental Weekdays, Full Saturday':
				$defaults['tapecycle'] = 6 + 1;
				$this->createSchedule($schedule, $defaults);
				$schedule['full_minute'] = $schedule['minute'];
				$schedule['full_hours_same'] = 1;
				$schedule['days'] = array(
						'1' => 'incremental',
						'2' => 'incremental',
						'3' => 'incremental',
						'4' => 'incremental',
						'5' => 'incremental',
						'6' => 'full',
					);
				break;

			case 'Incremental Weekdays, Full Sunday':
				$defaults['tapecycle'] = 6 + 1;
				$this->createSchedule($schedule, $defaults);
				$schedule['full_minute'] = $schedule['minute'];
				$schedule['full_hours_same'] = 1;
				$schedule['days'] = array(
						'0' => 'full',
						'1' => 'incremental',
						'2' => 'incremental',
						'3' => 'incremental',
						'4' => 'incremental',
						'5' => 'incremental',
					);
				break;

			case 'Custom Days of the Month':
				$defaults['desired_retention_period'] = 30;
				$this->createSchedule($schedule, $defaults);
				$defaults['dumpcycle'] = 7 * 4; 
				$defaults['tapecycle'] = 2; 
				$schedule['dom'] = array_filter($schedule['custom_dom']);
				break;

			case 'Custom Weekday':
				$defaults['tapecycle'] = 2;
				$this->createSchedule($schedule, $defaults);
				$schedule['days'] = $schedule['custom_days'];
				break;

			default:
				$schedule['status']->addError("Not supported in this release of AE: $schedule[schedule_type]");
		}

		$defaults['tapecycle'] = max($defaults['tapecycle'], $this->maxTapecycle());
		$schedule['full_backups_per_dumpcycle'] = 0;
		if (strpos($schedule['schedule_type'], 'Days of the Month'))
			$keyName = 'dom';
		else
		{
			$keyName = 'days';
			foreach(array_keys($schedule['days']) as $day)
			{
				if ($day < 7)
					continue;
				elseif ($day == 7)
				{
					if (empty($schedule['days'][0])) 
					{
						$schedule['days'][0] = $schedule['days'][$day];
						$schedule['status']->addWarning("Moved backup scheduled for day 7 to day 0.   Only cron days 0 (Sunday) to 6 (Saturday) are valid.");
					}
					else
						$schedule['status']->addWarning("Removed backup scheduled for day $day.  Day 0 was already scheduled. Only cron days 0 (Sunday) to 6 (Saturday) are valid.");
				}
				else
					$schedule['status']->addWarning("Removed backup scheduled for day $day.  Only cron days 0 (Sunday) to 6 (Saturday) are valid.");
				unset($schedule['days'][$day]); 
			}
		}

		
		ZMC_BackupSet::getTapeList($this->reply, $schedule, $this->bindings['config_name'], $schedule['dumpcycle']);
		if ($schedule['total_tapes_seen'])
		{
			if (!isset($schedule['dumpcycle_max_tapes_used']) || $schedule['dumpcycle_tapes_used'] > $schedule['dumpcycle_max_tapes_used'])
				$schedule['dumpcycle_max_tapes_used'] = $schedule['dumpcycle_tapes_used'];
			if (!isset($schedule['dumpcycle_max_tapes_hist']))
				$schedule['dumpcycle_max_tapes_hist'] = array();
			
			$today = substr(ZMC::dateNow(true), 0, 8);
			if (!isset($schedule['dumpcycle_max_tapes_hist']["h$today"]) || $schedule['dumpcycle_tapes_used'] > $schedule['dumpcycle_max_tapes_hist']["h$today"])
				$schedule['dumpcycle_max_tapes_hist']["h$today"] = $schedule['dumpcycle_tapes_used'];
		}

		if (empty($this->data['tapelist']) 
			|| ($this->data['tapelist'] !== true && $this->data['tapelist'] !== $this->bindings['config_name']))
			unset($schedule['tapelist']); 

		$schedule['runs_per_week'] = 0;
		if (empty($schedule[$keyName]))
			return $schedule['status']->addError('No backups scheduled.');
		$schedule['crontab'] = '';
		foreach(self::$backupMode2amdump as $type => $how)
		{
			$days = implode(',', $whichDays = array_keys($schedule[$keyName], $type));
			if (strlen($days) === 0)
				continue;

			$whichHours = array_keys(array_filter($schedule['hours']));
			$whichMinute = $schedule['minute'];
			if ($type === 'full' && empty($schedule['full_hours_same']))
			{
				$whichHours = array_keys(array_filter($schedule['full_hours']));
				$whichMinute = $schedule['full_minute'];
			}

			$schedule['crontab'] .= implode("\t", array(
				$whichMinute,
				implode(',', $whichHours),
				$keyName === 'days' ? '*' : $days, 
				'*', 
				$keyName === 'days' ? $days : '*', 
				
				ZMC::$registry->cnf->zmc_bin_path . 'amdump.sh ' . $this->bindings['config_name'] . " $how --zmcdev " . $this->bindings['private']['zmc_device_name']
			)) . "\n";
			
			$subtotal = count($whichDays) * count($whichHours);
			$schedule['runs_per_week'] += $subtotal;
			if ($type !== 'incremental')
				$schedule['full_backups_per_dumpcycle'] += $subtotal;
		}

		if ($schedule['full_backups_per_dumpcycle'] === 0)
		{
			$msg = "Schedule has no full backup";
			if ($this->action === 'create')
				$schedule['status']->addWarnError($msg);
			else 
				$schedule['status']->addWarning($msg);
		}

		if (!empty($schedule['runs_per_week'])) 
		{
			if ($keyName === 'dom')
				
				$schedule['runspercycle'] = (($schedule['dumpcycle'] /7) / (52/12)) * $schedule['runs_per_week'];
			else
				$schedule['runspercycle'] = (integer)ceil($schedule['runs_per_week'] * (integer)ceil($schedule['dumpcycle'] /7));

		    $set = ZMC_BackupSet::getByName($this->amanda_configuration_name);
			$schedule['tapes_per_backup_run'] = ($this->bindings['dev_meta']['media_type'] === 'vtape' ? $set['dles_total'] : $schedule['runtapes']);
			$schedule['tapes_per_dumpcycle'] = $schedule['tapes_per_backup_run'] + $schedule['tapes_per_backup_run'] * $schedule['runspercycle'];
			$schedule['estimated_tapes_per_retention_period'] = (integer)ceil($schedule['tapes_per_dumpcycle'] * ($schedule['desired_retention_period'] / $schedule['dumpcycle']));
			$schedule['estimated_retention_period'] = (integer)floor(($schedule['estimated_tapes_per_retention_period'] + $schedule['archived_media'] - $schedule['runtapes']) / ($schedule['runspercycle'] * $schedule['runtapes']) * $schedule['dumpcycle']);
			$schedule['total_tapes_needed'] = $schedule['archived_media'] + $schedule['estimated_tapes_per_retention_period'];
			if (!empty($this->bindings['changer']['total_tapes']))
				$schedule['total_tapes_available'] = $this->bindings['changer']['total_tapes'] - $schedule['archived_media'];
			if(!isset($schedule['tapecycle']) || $schedule['tapecycle'] <= 0 || $schedule['tapecycle'] > $schedule['total_tapes_available']){
				if(isset($this->bindings['schedule']['tapecycle']))
					$schedule['tapecycle'] = $this->bindings['schedule']['tapecycle'];
				else
					$schedule['tapecycle'] = $schedule['estimated_tapes_per_retention_period'] + $schedule['archived_media'];
			}
			if($schedule['tapecycle'] < $schedule['runtapes']){
				if(isset($this->bindings['schedule']['tapecycle']) && isset($this->bindings['schedule']['runtapes'])) {
					$schedule['tapecycle'] = $this->bindings['schedule']['tapecycle'];
					$schedule['runtapes'] = $this->bindings['schedule']['runtapes'];
				} else
					$schedule['tapecycle'] = $schedule['estimated_tapes_per_retention_period'] + $schedule['archived_media'];
			}
			
		}

		if ($this->action !== 'create')
		{
			$find = $this->command(array('pathInfo' => "/amadmin/find/" . $this->bindings['config_name'], 'data' => array('holding_directory' => $this->bindings['holdingdisk_list']['zmc_default_holding']['directory']), 'post' => null, 'postData' => null,));
			
			if ($find->offsetExists('fatal')) 
			{
				$this->reply->addWarning("Unable to calculate Historical Retention Period due to internal error.");
				if ($this->debug) $this->reply->addDetail("$find");
			}
			else
			{
				$schedule['used_tapelist_count'] = (empty($find['used_tapelist_count']) ? 0 : count($find['used_tapelist_count']));
				$schedule['level0_tapelist'] = empty($find['level0_tapelist']) ? array() : $find['level0_tapelist'];
				$schedule['retention_timestamp'] = isset($find['retention_timestamp']) ? $find['retention_timestamp'] : 0;
				if ($schedule['retention_timestamp'] > 0)
					$schedule['historical_retention_period'] = (integer)floor((time() - $find['retention_timestamp']) / 86400);
				else
					$schedule['historical_retention_period'] = 'NA';
			}
		}
		ksort($schedule);
	}

	protected function verifySchedule(&$schedule)
	{
		$whenUrl = ZMC::getPageUrl($this->reply, 'Backup', 'when');
		$whereUrl = ZMC::getPageUrl($this->reply, 'Backup', 'where');
		$tapes = $this->tapes_name;
		$tape = $this->tape_name;

		if ($this->bindings['dev_meta']['media_type'] === 'vtape')
		{
			$schedule['tapecycle'] = 999999;
			return;
		}

		$schedule['shortfall'] = false;
		if (	($this->action !== 'create')
			&&	isset($schedule['estimated_tapes_per_retention_period'])
			&&	isset($schedule['total_tapes_available']))
		{
			if ($schedule['estimated_tapes_per_retention_period'] > $schedule['total_tapes_available'])
			{
				$schedule['shortfall'] = true;
				$schedule['status']->addEscapedWarning(ZMC::escape("Total $tapes needed ($schedule[total_tapes_needed]) for selected retention period exceeds the number available ($schedule[total_tapes_available]). Either add more $tapes using ") . $whereUrl . ", or reduce the retention period (number of $tapes needed per retention period) on $whenUrl" . ($schedule['archived_media'] ? ", or reduce the number of archived $tapes." : '.'));
			}

			if ($schedule['total_tapes_available'] > $this->bindings['max_slots'])
				$schedule['status']->addWarning('Please rotate tapes from your "shelf" into the changer library, when needed, to insure that the oldest tapes written are always available for the next backup run');
		}

		
		

		if ($schedule['desired_retention_period'] < $schedule['dumpcycle'])
			if ($this->action === 'create')
				$schedule['status']->addEscapedError("Retention period must be equal to or greater than Backup Cycle");
			else 
				$schedule['status']->addEscapedWarning("Retention period is less than Backup Cycle!");
	}

	public function getCrontab()
	{
		return $this->reply->binding_conf['schedule']['crontab'];
	}

	protected function makeConfHolding(&$conf)
	{
		foreach(array_keys($conf['holdingdisk_list']) as $holding)
		{
			if ($conf['holdingdisk_list'][$holding]['strategy'] === 'disabled')
			{
				unset($conf['holdingdisk_list'][$holding]);
				continue;
			}
			foreach(array('filesystem_reserved_percent', 'use_request', 'strategy') as $key)
				unset($conf['holdingdisk_list'][$holding][$key]);
		}
		if (!empty($conf['holdingdisk_list']))
			$conf['holdingdisk'] = implode(' ', array_keys($conf['holdingdisk_list']));
	}

	protected function validateBindings()
	{
		if (empty($this->schedule_errors))
			$this->verifySchedule($this->bindings['schedule']);
		$this->reply->merge($this->bindings['schedule']['status']);
	}

	
	protected function adjustSlots()
	{

		$this->bindings['changer']['slotrange'] = "1-".$this->bindings['changer']['slots'];
	}

	protected function tooManySlotsRequested($slotsRequested)
	{
		if ($slotsRequested > $this->bindings['max_slots'])
			$this->reply->addWarnError("The requested number of vtapes ($slotsRequested) exceeeds the Maximum Slots allowed (" . $this->bindings['max_slots'] . ") for this ZMC device. See Admin|devices page, advanced section, if you need to increase the maximum.");
	}

	protected function calcHolding(&$holdingList)
	{
		$tune2fs = 'tune2fs on Linux can report and set the amount of space reserved for use by a special user (typically "root"). ZMC can not auto-detect the amount of space reserved. Thus, the total space reported as free on the device often is not all available for use by AEE and the amandabackup user. Linux "ext" filesystems typically default to 5% reserved, and Solaris commonly defaults to 10% reserved.';
		$oldPrefix = $this->reply->getPrefix();
		$safeMode = ZMC::$registry->safe_mode;
		ZMC::$registry->safe_mode = ZMC::$registry->safe_mode && (($this->action === 'merge') || ($this->action === 'create'));
		try
		{
			foreach($holdingList as $name => &$holding)
			{
				if (empty($holding))
					continue;
	
				$this->reply->setPrefix($name);
	
				if (empty($holding['directory']))
				{
					$this->reply->addWarnError('Staging directory not specified ("holdingdisk" directory empty).');
					$holding['strategy'] = 'disabled';
					continue;
				}
	
				if ($result = $this->checkPath(ZMC::$registry->staging_deny, $holding['directory'], $this->action === 'defaults' || $this->action === 'read'))
				{
					$err = "Staging directory $holding[directory] not writable. $result";
					if (ZMC::$registry->safe_mode)
					{
						$this->reply->addWarnError("$err Disabled.");
						$holding['strategy'] = 'disabled';
						continue;
					}
					$this->reply->addWarnError($err);
				}
	
				if (!strncmp($holding['directory'], '/tmp/', 5))
					$this->reply->addWarning("Unsafe staging directory used: $holding[directory]." . ((ZMC::$registry->platform === 'solaris') ? 'Solaris normally uses *RAM* for /tmp!' : ''));
	
				if ($this->action === 'create' && is_dir($holding['directory'])) 
					if (count(glob("$holding[directory]/*")))
						$this->reply->addWarning("Found previously used staging directory '$holding[directory]'. Please manually move or remove '$holding[directory]', before continuing.");
	
				$this->addFreeSpaceKeys($holding, $holding['directory']);
				if (empty($holding['use_request_display']))
					$holding['use_request_display'] = 'm';
		
				if (empty($holding['use_request']))
					$holding['use_request'] = '0';
		
				if (empty($holding['strategy']) || (
						($holding['strategy'] !== 'disabled')
					&&	($holding['strategy'] !== 'all_except')
					&&	($holding['strategy'] !== 'no_more_than')))
					$holding['strategy'] = (($holding['use_request'][0] === '-') ? 'all_except' : 'no_more_than');
		
				ksort($holding);
				$reservedPercent = $holding['filesystem_reserved_percent'];
				$use = rtrim($holding['use_request'], 'm');
				$units = $holding['use_request_display'];
				if ($units === '%')
				   $use = intval($use);
	
				if ($holding['strategy'] === 'no_more_than')
				{
					if ($units === '%')
					{
						if ($use == 100)
							$use = 0;
						elseif ($use == 0)
							$holding['strategy'] = 'disabled';
						elseif (empty($holding['partition_total_space']))
						{
							$this->reply->addError("Use of '%' is not supported for \"$holding[directory]\".");
							continue;
						}
						else
						{
							if ($use >= (100 - $reservedPercent))
							{
								$err = "The OS has reserved $reservedPercent% of the filesystem.  The maximum space available for staging is the lesser of free space available or " . (100 - $reservedPercent -1) . "%.\n$tune2fs";
								if (!ZMC::$registry->space_warnings || !ZMC::$registry->safe_mode)
									$this->noticeLog($err);
								else
									$this->reply->addWarnError($err);

								if (ZMC::$registry->safe_mode) continue;
							}
							$use = bcmul(rtrim($holding['partition_total_space'], 'MiB'), $use/100, 0);
						}
					}
					$holding['use'] = $use . 'M';
				}
				elseif ($holding['strategy'] === 'all_except')
				{
					if ($units !== '%')
						$use = ((empty($use) || ($use[0] === '0')) ? '0' : $use);
					elseif ($use == 100)
						$holding['strategy'] = 'disabled';
					elseif ($use == 0)
						$use = '0';
					elseif (empty($holding['partition_total_space']))
					{
						$this->reply->addError("Use of '%' is not supported for \"$holding[directory]\".");
						continue;
					}
					else
					{
						if ($use >= (100 - $reservedPercent))
						{
							$err = "The OS has reserved $reservedPercent% of the filesystem.  The maximum space available for excluding from staging is the lesser of free space available or " . (100 - $reservedPercent -1) . "%.\n$tune2fs";
							if (!ZMC::$registry->space_warnings || !ZMC::$registry->safe_mode)
								$this->noticeLog($err);
							else
								$this->reply->addWarnError($err);
	
							if (ZMC::$registry->safe_mode) continue;
						}
						$use = bcmul(rtrim($holding['partition_total_space'], 'MiB'), $use/100, 0);
					}
	
					$holding['use'] = '-' . $use . 'M';
				}
	
				$warn = $err = null;
				$curBackupSet = $this->getAmandaConfName(true);
				if (!empty($holding['partition_total_space']))
				{
					if ($holding['strategy'] === 'all_except')
					{
						$reserved = bcadd(2, bcmul($reservedPercent/100, $holding['partition_total_space'], 0));
						if (1 === bccomp($reserved, $use))
							$err = "The OS has reserved $reservedPercent% of the filesystem.  Use \"All, except\" $reserved MiB or more.\n$tune2fs";
						elseif (1 === bccomp($use, $possible = bcsub($holding['partition_total_space'], $reserved)))
							$err = "Can not exclude more space ($use MiB) than possible ($possible MiB) on this partition.\n$tune2fs";
						elseif (1 === bccomp($use, $holding['partition_free_space']))
							$warn = "Can not use a size limit ($use MiB) greater than the partition free space ($holding[partition_free_space] MiB). Please reduce the amount excluded from use by staging, or free up more space before the next backup.";
					}
					elseif ($holding['strategy'] === 'no_more_than')
					{
						$available = bcmul(1 - $reservedPercent/100, $holding['partition_total_space']);
						if (1 === bccomp($use, $available))
							$err = "The OS has reserved $reservedPercent% of the filesystem.  The maximum space available for staging is the lesser of free space available or $available MiB.\n$tune2fs";
						elseif (1 === bccomp($use, $holding['partition_free_space']))
							$warn = "Backup set: $curBackupSet. Can not use more space ($use MiB) than free ($holding[partition_free_space] MiB). Please reduce the maximum staging size limit, or free up more space before the next backup.";
					}
				}

				if ($warn)
				{
					if (ZMC::$registry->space_warnings)
						$this->reply->addWarning($warn);
					else
						$this->noticeLog($warn);
				}
				if ($err)
				{
					if (!ZMC::$registry->space_warnings)
						$this->noticeLog($err);
					else
						$this->reply->addWarnError($err);
				}
				if (($holding['strategy'] === 'disabled') && !empty($holding['used_space']))
					$this->reply->addWarnError("Can not disable holding space $name, because it is not empty.");
			}
		} catch (Exception $e) {
			ZMC::$registry->safe_mode = $safeMode;
			$this->reply->setPrefix($oldPrefix);
			throw $e;
		}
		ZMC::$registry->safe_mode = $safeMode;
		$this->reply->setPrefix($oldPrefix);
		return true;
	}

	protected function addFreeSpaceKeys(&$array, $dir, $prefix = '')
	{
		if (empty($dir))
			return;

		$du = '0';
		if (ZMC::$registry->large_file_system === true){
			if(false != ($du = ZMC::lfs_du($dir)))
				$du = round(bcdiv($du, '1048576', 2), 1);
		}else{
		if (is_dir($dir))
			if (false !== ($du = ZMC::du($dir)))
				$du = round(bcdiv($du, '1048576', 2), 1);
			elseif (ZMC::$registry->space_warnings)
				$this->reply->addWarning("Unable to compute space used by '$dir' (possibly filesystem/files > 4 TiB/GiB). All space related checks and validations are disabled.");
		}
		$array[$prefix . 'used_space']  = $du;
		$array['partition_total_space_display'] = $array[$prefix . 'used_space_display'] = 'm';
		$array['partition_used_space_display'] = $array['partition_free_space_display'] = 'm';

		if (false === ZMC::diskFreeSpace($this->reply, $dir, $array['partition_free_space'], $array['partition_total_space'],
			$array['partition_used_space'], (isset(ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform]) ? ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform] : ZMC::$registry->filesystem_reserved_percent['default'])))
			if (ZMC::$registry->space_warnings)
				$this->reply->addWarning("Unable to compute space statistics for '$dir' (possibly filesystem/files > 4 TiB/GiB).");
	}

	protected function calcSplitSize(&$tapetype)
	{
		if (empty($tapetype['part_size_auto']) || $tapetype['part_size_auto'] !== 'on') 
			return;

		if ($this->bindings['schedule']['runtapes'] > 1)
		{
			$length = intval($tapetype['length']); 
			if ($length > 0)
			{
				$tapetype['part_size'] = round($length / 100 * $tapetype['part_size_percent']) . 'm';
				$tapetype['part_size_comment'] = 'auto-calculated';
			}
		}
		else 
			unset($tapetype['part_size']);
		
		if (empty($tapetype['part_cache_type']))
			$tapetype['part_cache_type'] = (empty($tapetype['part_cache_dir']) ? 'memory' : 'disk');

		if ($tapetype['part_cache_type'] === 'disk')
			unset($tapetype['part_cache_max_size']);
		else
			$tapetype['part_cache_max_size'] = ZMC::$registry->part_cache_max_size;
	}

	
	public function cleanup()
	{
	}

	public function purgeMedia()
	{
		$this->reply->addWarnError("This device does not yet support purging of media.");
	}

	protected function checkDevice($devicePath, $deviceType)
	{
		if (!is_string($devicePath) || empty($devicePath))
			$this->reply->addDetail("devicePath not a string: " . print_r($devicePath, true));

		$result = ZMC::is_readwrite($devicePath, false);
		if (empty($devicePath) || ($result === false))
			return; 

		if (file_exists($devicePath))
			$this->reply->addWarnError("The '$devicePath' $deviceType configured for this changer is not writable by the \"amandabackup\" user or this user's groups (\"disk\", \"tape\", and \"mysql\")"
				. ZMC::getPermHelp($devicePath, 'file', "chgrp disk '$devicePath'; chmod g+rw '$devicePath'"));
		else
		{
			if (!is_readable(dirname($devicePath)))
				$this->reply->addWarnError("The amandabackup user can not read the parent directory of '$devicePath' (the tape device configured for this changer)"
					. ZMC::getPermHelp(dirname($devicePath),
					'dir',
					'This directory should be readable (\"rx\" permision) by the \"disk\" group (note: \"amandabackup\" belongs to this group). For example, run the following command as the root user on this AEE server:',
					'chgrp disk ' . dirname($devicePath)
				));
			else
				$this->reply->addWarning("The '$devicePath' tape device configured for this changer does not exist.");
		}
	}
	
	public function makeChanger(&$conf)
	{
		$conf['changer_list'] = array($this->profileName => array('tpchanger' => $this->changerPrefix . ':' . $conf['changer']['changerdev'], 'device_property_list' => &$conf['device_property_list']));
		unset($conf['device_property_list']);
		if($conf['_key_name'] === "attached_storage"){
			if(!empty($conf['changer']['changerfile'])){
				$conf['changerfile'] = $conf['changer']['changerfile'];
				unset($conf['changer']['changerfile']);
				try{ 
					$this->mkdirIfNotExists(dirname($conf['changerfile']), false);
					if(!file_exists($conf['changerfile'])){
						
						
						
						
						if ($fp = fopen($conf['changerfile'], 'w')){
							fclose($fp);
						}
					}
				}
				catch (Exception $e){		
					$this->reply->addError("Failed to create state file : '". $conf['changerfile']."'");
				}
			}
		}
		ZMC::array_move($conf, $conf['changer_list'][$this->profileName], array('changer' => 'property_list', 'changerfile'));
	}

	



	public function &getBindingYaml()
	{
		$conf = $this->reply->binding_conf; 
		$ignore_barcodes = $conf['changer']['ignore_barcodes'];

		$this->makeChanger($conf);
		
		

		$this->makeConfHolding($conf);
		foreach($conf as $key => &$value)
			if (substr($key, -8) === '_comment')
				$value = "#$value\n"; 

		if (!empty($conf['tapetype'])) 
		{
			if (isset($conf['tapetype']['part_size_auto']) && $conf['tapetype']['part_size_auto'] === 'on')
				if (empty($conf['tapetype']['part_size']))
				{
					unset($conf['tapetype']['part_size']);
					unset($conf['tapetype']['part_cache_max_size']);
					unset($conf['tapetype']['part_size_percent']);
					unset($conf['tapetype']['part_cache_dir']);
					unset($conf['tapetype']['part_cache_type']);
				}
			unset($conf['tapetype']['part_size_auto']);
			unset($conf['tapetype']['part_size_percent']);
			$conf['tapetype_list'][$this->profileName] = $conf['tapetype'];
			$conf['tapetype'] = $this->profileName;
		}
			
		$taperscanName = str_replace('-', '_', $this->getAmandaConfName(true) . '_taperscan');
		if(!empty($conf['taperscan']))
			$conf['taperscan_list'][$taperscanName] = $conf['taperscan'];
		else
			$conf['taperscan_list'][$taperscanName] = array('plugin' => 'traditional');
		$conf['taperscan'] = $taperscanName;

		if (isset($conf['autolabel']) && ($conf['autolabel'] === 'off'))
			unset($conf['autolabel']);
		else
		{
			$format = $conf['autolabel_format'];
			if (!$conf['has_barcode_reader'] || ($ignore_barcodes === 'on'))
				$format = str_replace('-$b', '-%%%', $conf['autolabel_format']);
			$conf['autolabel'] =  "\"$format\" " . (isset($conf['autolabel_how']) ? $conf['autolabel_how'] : 'empty');
		}

		ZMC::array_move($conf['schedule'], $conf, array(
			'tapecycle',
			($this->reply->binding_conf['dev_meta']['media_type'] === 'vtape') ? '' : 'dumpcycle',
			'runtapes',
			'runspercycle',
		));

		$conf['runspercycle'] = (integer)ceil($conf['runspercycle']);
		
		foreach(array(
			'_key_name',
			'autolabel_how',
			'autolabel_format',
			'changer',
			'changerdev',
			'changerdev_prefix',
			'changerdev_suffix',
			'config_name',
			'driveslot',
			'enabled', 
			'has_barcode_reader',
			'id',
			'initial_poll_delay',
			'isa',
			'slotrange',
			'max_drive_wait',
			'max_slots',
			'media',
			'metalabel_counter',
			'private',
			'poll_drive_ready',
			'schedule',
			'slots',
			'ssl_ca_cert',
			'ssl_ca_cert_ignore',
			'stderr',
			'stdout',
			'tapedev',
			'tape_name',
			'tapes_name',
			'total_tapes',
			'zmc_uuid',
			'zmc_version',
			) as $key)
		{
			unset($conf[$key]);
			unset($conf['changer_list'][$this->profileName]['property_list'][$key]);
		}

		foreach(array('free_space', 'free_space_display', 'partition_free_space', 'partition_free_space_display', 'partition_used_space', 'partitionused_space', 'partition_used_space_display', 'total_space', 'total_space_display', 'partition_total_space', 'partition_total_space_display', 'used_space', 'used_space_display') as $key)
			foreach($conf['holdingdisk_list'] as &$holding)
				unset($holding[$key]);
		foreach(array(
			'comment',
			'license_group',
			'zmc_ags_version',
			) as $key)
			unset($conf[$key]);
		foreach($conf as $key => $ignored)
			if (substr($key, -8) === '_display') 
				unset($conf[$key]);

		ksort($conf); 
		ksort($conf['changer_list'][$this->profileName]['property_list']); 
		return $conf;
	}

	public function createAndLabelSlots()
	{}

	protected function maxTapecycle()
	{ return 0; }
}
