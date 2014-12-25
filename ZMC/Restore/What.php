<?













class ZMC_Restore_What extends ZMC_Restore
{
	const SELECT = 255; 
	const IMPLIED_SELECT = 254; 

	const NO_SELECT = 0; 
	const IMPLIED_DESELECT = 1; 
	const DESELECT = 2; 

	public static $colorMap = array(
		ZMC_Restore_What::NO_SELECT => 'ffffff',
		ZMC_Restore_What::SELECT => 'c0ffc0',
		ZMC_Restore_What::IMPLIED_SELECT => 'e0ffe0',
		ZMC_Restore_What::DESELECT => 'ffc0c0',
		ZMC_Restore_What::IMPLIED_DESELECT => 'ffe0e0',
	);

	public static function runWrapped(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Restore', 'ZMC - What would you like to restore?', 'What');
		$pm->addDefaultInstruction('Configure where and how to perform restore.  Use "Restore All" to restore all, except items matching specified patterns.  Use "Explore & Select" to restore only some.');
		if (!($configName = ZMC_BackupSet::assertSelected($pm)))
			return 'MessageBox'; 
		if (!ZMC_BackupSet::hasBackups($pm, $configName))
			return 'MessageBox'; 

		$pm->rows = $pm->state = '';
		if (!empty($_REQUEST['action'])){

			if(!isset($_REQUEST['restore_pref']) && $_REQUEST['action'] == "Next"){
				$pm->addError("Restore preference must be selected.");
			}else{
				
				$pm->state = $_REQUEST['restore_pref'];
			}
			if($_REQUEST['action'] != "Next")
				$pm->state = $_REQUEST['action'];
			if($pm->state === 'Search' && empty($_REQUEST['restore_search'])){
				$pm->addError("File/Pattern to restore should not be empty.");
			}
		}
		unset($_REQUEST['action']);

		if (!empty($_REQUEST['disk_name']) && ZMC::$registry->trim_white_space)
			$_REQUEST['disk_name'] = ($_REQUEST['disk_name'] === '/') ? '/' : ltrim(rtrim($_REQUEST['disk_name'], " \t\r\n"), " \t\r\n");
		$job = new self($pm, $configName, empty($pm->state) ? false : true);
		$dles = $job->getAmadminDles();
		$pm->suggestedHosts = (is_array($dles) ? array_keys($dles) : array());
		$pm->buttons = ZMC_Restore::$buttons;
		if ($pm->amgetindex_state['running'])
			return 'RestoreWhat';
		if(($pm->state != "") && !isset($_REQUEST['locale_sort'])){
			ZMC::$registry->locale_sort = $pm->restore['locale_sort'] = "C";
		}

		if (empty($_POST['action']))
			switch($pm->amgetindex_state['state'][0])
			{
				case ZMC_Yasumi_Job::CRASHED:
					$pm->addMessage($pm->amgetindex_state['status'][1]);
					$pm->addError($pm->amgetindex_state['status'][0] . "\n" . $pm->amgetindex_state['output'][0]);
					return 'RestoreWhat';

				case ZMC_Yasumi_Job::UNBORN:
					break;

				default:
					if (!empty($pm->amgetindex_state['warning_message'])){
						ZMC_Mysql::query('truncate table '.$pm->restore['tableName']);
						$pm->addWarning($pm->amgetindex_state['warning_message']);
					}
					if (!empty($pm->amgetindex_state['status']))
						$pm->addMessage($pm->amgetindex_state['status'][0] . "\n" . $pm->amgetindex_state['output'][0]);
			}

		$job->runState($pm);
		if (empty($job->restore_job['client']) && (count($dles) === 1))
			$job->restore_job['client'] = current($job->pm->suggestedHosts);

		$suggestedDirectories = null;
		if (isset($dles[$job->restore_job['client']]))
			$suggestedDirectories = $dles[$job->restore_job['client']];

		if (empty($job->restore_job['disk_name']) && (count($suggestedDirectories) === 1))
			$job->restore_job['disk_name'] = current($suggestedDirectories);

		if (!empty($job->pm->amgetindex_state['successful'])) 
		{
			if ($job->getDirContentsWrapper($job->pm) 
				&& ($job->pm->state !== 'Next Step'))
			{
				$job->getBreadCrumbs($job->pm);
				if ($job->restore_job['cwd_ids_last'] === $job->restore_job['cwd_ids']){
					if ($job->restore_job['restore_type'] == ZMC_Restore::EXPRESS)
						$job->pm->addEscapedMessage($summary = "Selected: Restore All from the most recent backups <u>started</u> on or before:\n" . $job->restore_job['date_time_parsed']);
					else
						$job->pm->addEscapedMessage($summary = "Selected: Exploring the most recent backups <u>started</u> on or before:\n" . $job->restore_job['date_time_parsed']);

				}
				else 
				{
					$job->restore_job['cwd_ids_last'] = $job->restore_job['cwd_ids'];
					$job->restore_job['rbox'] =& $job->getRestoreMap(true, $job->restore_job['restore_total'], $warnings);
					if (!empty($warnings))
						$job->pm->addWarning($warnings);
					$job->restore_job['lbox'] = array();
					foreach ($job->pm->rows as $row) 
						if ($row['restore'] >= self::IMPLIED_SELECT) 
							$job->restore_job['lbox'][$row['filename']] = true;
				}
			}
		}
		$job->mergeToDisk();
		if (isset($job->restore_job['date_time_image'])) $msg = 'Using backup history from backup image(s) created ~: ' . $job->restore_job['date_time_image'];
		if (!empty($job->restore_job['date_time_image']))
			$job->pm->addMessage($msg);
		if (!empty($summary))
			ZMC_Events::add($summary, null, $msg);

		if (!$job->pm->isErrors())
		{
			switch($job->pm->state)
			{
				case 'Next Step':
					$count = $job->countSelected();
					if ($count != $job->restore_job['selected_count'])
					{
						$job->restore_job['selected_count'] = $count;
						$job->mergeToDisk();
					}
					if (!$job->whereNextStep())
						break;
				case ZMC_Restore::$buttons[ZMC_Restore::EXPRESS]:
					return ZMC::redirectPage('ZMC_Restore_Where', $job->pm);
			}
		}
		
		$tapelist = array();
		ZMC_BackupSet::getTapeList($pm, $tapelist, $pm->selected_name);
		$tl =& $tapelist['tapelist'];
		$deviceList = array();
		$hasOriginalTape = false;
		$pattern = '/^' . $pm->selected_name . '-.+' . '-vault-[0-9][0-9][0-9]$/';
		foreach(array_keys($tl) as $key){
			if (empty($tl[$key]['timestring'])){ 
				unset($tl[$key]);
			} else if (preg_match($pattern, $key)) {
				$components = explode('-', $key);
				$deviceName = $components[1];
				if(!in_array($deviceName, $deviceList))
					$deviceList[] = $deviceName;
			} else {
				$hasOriginalTape = true;
			}
		}
		
		
		$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/holding_list/' . $pm->selected_name));
		$holdingList = explode("\n", $result['holding_list']);
		
		if($hasOriginalTape || (count($holdingList) > 1 && !empty($holdingList[1])))
			$deviceList = array_merge(array($pm->set['profile_name']), $deviceList);
			
		if(empty($deviceList)){
			$pm->addMessage("This backup set, \"" . $pm->selected_name . "\", has no backups.");
			return 'MessageBox';
		}
		$job->restore_job['device_list'] = $deviceList;

		ZMC_HeaderFooter::$instance->addRegistry(array('dles' => $dles));
		return 'RestoreWhat';
	}

	private function getAmadminDles()
	{
		try
		{
			$result = ZMC_Yasumi::operation($this->pm, array(
				'pathInfo' => '/amadmin/find/' . $this->configName,
				
			));
			unset($result['request']);
		}
		catch (Exception $e)
		{
			$this->pm->addError("An unexpected problem occurred while reading the list of devices:'. $e");
			return false;
		}
		return $result->dle_list;
	}

	private function getAmadminfinds()
	{
		try
		{
			$result = ZMC_Yasumi::operation($this->pm, array(
				'pathInfo' => '/amadmin/find/' . $this->configName,
				
			));
			unset($result['request']);
		}
		catch (Exception $e)
		{
			$this->pm->addError("An unexpected problem occurred while reading the backup history'. $e");
			return false;
		}
		return $result;
	}

	private function getDiskListInfo()
	{
		try
		{
			$result = ZMC_Yasumi::operation($this->pm, array(
				'pathInfo' => '/conf/read/' . $this->configName,
				'data' => array('what' => 'disklist.conf'),
			));
			unset($result['request']);
		}
		catch (Exception $e)
		{
			$this->pm->addError("An unexpected problem occurred while reading the disklist.conf file'. $e");
			return false;
		}
		return $result->conf;
	}

	private function filterDiskName()
	{
		if (empty($this->restore_job['disk_name']))
			$this->pm->addError('Alias/Directory/Path is required.');
		
		
		if(strpos($this->restore_job['zmc_type'], 'windows') === false){ 
			if ($this->restore_job['disk_name'] !== strtr($this->restore_job['disk_name'], "?$^[]{}+|", "123456789"))
				$this->pm->addError('A manual restore using amrestore is required.  ZMC can not restore non-windows DLEs having an initial path containing any of the following characters: ?, $, ^, [, ], {, }, +, |');
		} else {
			if ($this->restore_job['disk_name'] !== strtr($this->restore_job['disk_name'], '*?"<>|', "123456"))
				$this->pm->addError('A manual restore using amrestore is required.  ZMC can not restore windows DLEs having an initial path containing any of the following characters: *, ?, ", <, >, |');
		}
	}

	public function runState()
	{
		if ($this->pm->amgetindex_state['state'][0] === ZMC_Yasumi_Job::ABORTED)
		{
			if ($this->pm->state === 'Reset')
				$this->clear();
			else
				$this->pm->addError($this->pm->amgetindex_state['state'][0] . ': ' . implode("\n", $this->pm->amgetindex_state['output']));
			return;
		}
		switch($this->pm->state) 
		{
			case 'Reset':
				if ($this->pm->amgetindex_state['running'])
				{
					$this->pm->addError('Please wait for current explore to complete, or abort the explore before "Clearing" the explore results.');
					break;
				}
				$this->clear();
				return;

			case ZMC_Restore::$buttons[ZMC_Restore::SEARCH]:
				$_REQUEST['restore_search'] = trim($_REQUEST['restore_search']);
				$this->restore_job['restore_search'] = (empty($_REQUEST['restore_search']))? '' : $_REQUEST['restore_search'];
				$_REQUEST['restore_pattern_type'] = trim($_REQUEST['restore_pattern_type']);
				$this->restore_job['restore_pattern_type'] = (empty($_REQUEST['restore_pattern_type']))? '' : $_REQUEST['restore_pattern_type'];
				
			case ZMC_Restore::$buttons[ZMC_Restore::SELECT]:
				$this->filterForm();
				if($this->pm->isErrors())
					return;
				if (true) 
					ZMC::normalizeWindowsDle($this->restore_job['disk_name']); 


				if (	$this->pm->amgetindex_state['successful']
					&&	$this->restore_job['client_last']		=== $this->restore_job['client']
					&&	$this->restore_job['disk_name_last']	=== $this->restore_job['disk_name']
					&&	$this->restore_job['locale_sort_last']	=== $this->restore_job['locale_sort']
					&&	$this->restore_job['timestamp_last']	=== $this->restore_job['timestamp']
					&&	$this->restore_job['restore_device_last'] === $this->restore_job['restore_device']
					&&	$this->restore_job['restore_pref_last'] === $this->restore_job['restore_pref']
					&&	$this->restore_job['restore_search_last'] === $this->restore_job['restore_search']
					&&	$this->restore_job['restore_pattern_type_last'] === $this->restore_job['restore_pattern_type'])
				{
					if ($results = $this->setCwd($this->restore_job, '/', true)) 
					{
						$this->restore_job['restore_type'] = ZMC_Restore::SELECT; 
						break;
					} 
				}
				if (!empty($this->pm->restore_state))
					$this->resetJob();
				$this->restore_job['client_last']		= $this->restore_job['client'];
				$this->restore_job['locale_sort_last']	= $this->restore_job['locale_sort'];
				$this->restore_job['disk_name_last']	= $this->restore_job['disk_name'];
				$this->restore_job['restore_pref_last']	= $this->restore_job['restore_pref'];
				if($this->restore_job['restore_pref'] === "Search"){
					$this->restore_job['restore_search_last']	= $this->restore_job['restore_search'];
					$this->restore_job['restore_pattern_type_last'] = $this->restore_job['restore_pattern_type'];
				}else{
					$this->restore_job['restore_search_last']= $this->restore_job['restore_search'] = '';
					$this->restore_job['restore_pattern_type_last'] = $this->restore_job['restore_pattern_type'] ='';
				}
				$this->restore_job['restore_device_last'] = $this->restore_job['restore_device'];
				$this->restore_job['lbox'] = null;
				$this->restore_job['rbox'] = null;
				$this->restore_job['cwd'] = '/';
				$this->restore_job['cwd_ids'] = 1;
				if ($this->pm->isErrors())
					return;

				
				if(strtolower($this->restore_job['restore_pref']) == ZMC_Restore::SEARCH){
					$restore_type =  ZMC_Restore::SEARCH;
					$this->restore_job['restore_type'] = ZMC_Restore::SEARCH; 
				}else{	
					$restore_type =  ZMC_Restore::SELECT;
					$this->restore_job['restore_type'] = ZMC_Restore::SELECT; 
				}
				if ($this->setOptions($restore_type) && $this->restore_job['browseable'])
				{
					$msg = "Searching for backups of object/directory path '{$this->restore_job['disk_name']}' on '{$this->restore_job['client']}' made on or before {$this->restore_job['date_time_parsed']} ..";
					$this->findMedia("Find media for backups of object/directory path '{$this->restore_job['disk_name']}' on '{$this->restore_job['client']}' made on or before {$this->restore_job['date_time_parsed']} ..");
					if(!empty($this->restore_job['date_time_parsed']) && empty($this->restore_job['media_explored'])){
						$this->pm->addError("No record found for:
								&emsp;&emsp;&emsp;Backup Date and Time: on or before {$this->restore_job['date_time_parsed']}
								&emsp;&emsp;&emsp;Restore Device: {$this->restore_job['restore_device']}
								&emsp;&emsp;&emsp;Host Name: {$this->restore_job['client']}
								&emsp;&emsp;&emsp;Alias/Directory/Path: {$this->restore_job['disk_name']}");
						return;
					}
					if ($this->createRestoreTree($msg))
					{		
						$this->pm->addMessage($msg);
						ZMC_HeaderFooter::$instance->addRegistry(array('i_pushed_explore_button' => true));
						$this->pm->amgetindex_state['running'] = true;
						
					}
					
					break;
				}

				$this->pm->addWarning('This DLE does not support "' . ZMC_Restore::$buttons[$restore_type] . '".  Using "' . ZMC_Restore::$buttons[ZMC_Restore::EXPRESS] . '" instead.');
				
	
			case ZMC_Restore::$buttons[ZMC_Restore::EXPRESS]:
				if (!empty($this->pm->restore_state))
					$this->resetJob(); 
				$this->pm->state = ZMC_Restore::$buttons[ZMC_Restore::EXPRESS];
				$this->filterForm();
				if($this->pm->isErrors())
					return;
				if (!$this->setOptions(ZMC_Restore::EXPRESS))
					return;
				if ($this->findMedia("Restore all for backups of object/directory path '{$this->restore_job['disk_name']}' on '{$this->restore_job['client']}' made on or before {$this->restore_job['date_time_parsed']} .."))
					$this->restore_job['restore_type'] = ZMC_Restore::EXPRESS; 
				if(!empty($this->restore_job['date_time_parsed']) && empty($this->restore_job['media_explored']))
					$this->pm->addError("No record found for:
							&emsp;&emsp;&emsp;Backup Date: on or before {$this->restore_job['date_time_parsed']}
							&emsp;&emsp;&emsp;Restore Device: {$this->restore_job['restore_device']}
							&emsp;&emsp;&emsp;Host Name: {$this->restore_job['client']}
							&emsp;&emsp;&emsp;Alias/Directory/Path: {$this->restore_job['disk_name']}");
				return;


			case 'Up': 
				$this->setCwd($this->restore_job, dirname($this->restore_job['cwd']));
				break;

			case 'Go':
				
				
				
				
				
				$origFpn = $this->restore_job['fpn'];
				$this->restore_job['fpn'] = trim($this->restore_job['fpn']);
				if (ZMC::$registry->dev_only)
					$this->pm->addWarning("Debug before: cwd=" . $this->restore_job['cwd'] . "; fpn=" . $this->restore_job['fpn']. "; disk_device=" . $this->restore_job['disk_device']);

				if ($this->restore_job['zwc'])
					ZMC::normalizeWindowsDle($this->restore_job['fpn']);

				if (empty($_GET['fpn']) && !strncmp($this->restore_job['fpn'], rtrim($this->restore_job['disk_device'], '/') . '/', strlen($this->restore_job['disk_device']) +1))
					
					$this->restore_job['fpn'] = '/' . ltrim(substr($this->restore_job['fpn'], strlen($this->restore_job['disk_device'])), '/');

				if ($this->restore_job['fpn'][0] !== '/')
					$this->restore_job['fpn'] = $this->restore_job['cwd'] . $this->restore_job['fpn']; 

				$this->restore_job['fpn'] = rtrim($this->restore_job['fpn'], '/') . '/'; 
				if (false === $this->setCwd($this->restore_job, $this->restore_job['fpn']))
				{
					if (ZMC::$registry->dev_only)
						$this->pm->addWarning("Not found (resetting to $origFpn): cwd=" . $this->restore_job['cwd'] . "; fpn=" . $this->restore_job['fpn']. "; disk_device=" . $this->restore_job['disk_device']);
					else
						$this->pm->addWarning($this->restore_job['fpn'] . ' not found.');
					$this->restore_job['fpn'] = $origFpn;
				}

				if (ZMC::$registry->dev_only)
					$this->pm->addWarning("Debug after: cwd=" . $this->restore_job['cwd'] . "; fpn=" . $this->restore_job['fpn']. "; disk_device=" . $this->restore_job['disk_device']);
				break;

			case '>>':
				$this->unlinkRestoreLists();
				$tmp = array_keys($this->restore_job['lbox']);
				$total = $this->updateRestoreFlagWrapper($this->restore_job['cwd'], $tmp, self::SELECT); 
				if ($total === false)
					break;
				if ($total)
					$this->pm->addMessage("Selected an additional $total descendant items implied by your selection of directories/folders.");
				ZMC::merge($this->restore_job['rbox'], $this->restore_job['lbox']); 
				$this->restore_job['lbox'] = null;
				return;

			case '<<':
				$this->unlinkRestoreLists();
				unset($this->pm->rbox['']); 
				$total = $this->updateRestoreFlagWrapper($this->restore_job['cwd'], $this->pm->rbox, self::DESELECT); 
				if ($total === false)
					break;
				if ($total)
					$this->pm->addMessage("Deselected an additional $total descendant items implied by your selection of directories/folders.");

				ZMC::array_move($this->restore_job['rbox'], $this->restore_job['lbox'], $this->pm->rbox); 
				return;

			case 'Reset':
				$this->restore_job['rbox'] = array();
				$this->unlinkRestoreLists();
				$this->resetSelections();
				return;

			default:
				break;
		}
	}

	






	public function updateRestoreFlagWrapper($prefix, array &$filenames, $restoreFlag)
	{
		if ($filenames[0] === '')
			unset($filenames[0]);
		unset($filenames['']);
		if (empty($filenames))
			return;

		if ($restoreFlag === self::DESELECT)
		{
			if ($prefix === '/')
				$restoreFlag = self::NO_SELECT;
			elseif(!$this->restore_job['excludable'])
			{
				$parent = $this->getRecord($this->restore_job['cwd_ids']);
				if ($parent['restore'] >= self::IMPLIED_SELECT)
				{
					$this->pm->addError("Pruning previously selected objects/files/directories is not supported. Instead, navigate to the top of the selected items and then remove the parent item from the restore list. (#$restoreFlag)");
					return false;
				}
			}
		}

		ZMC::auditLog($msg = __FUNCTION__ . "($prefix, " . count($filenames) . ", $restoreFlag)");
		$this->updateRestoreFlag($prefix, $filenames, $restoreFlag); 
		ZMC::auditLog($msg . ' FINISHED');
		if ($restoreFlag === self::SELECT){
			if($this->restore_job['restore_pref'] == "Search")
				$restoreFlag = self::SELECT; 
			else
				$restoreFlag = self::IMPLIED_SELECT; 
		}
		elseif ($restoreFlag === self::DESELECT)
			$restoreFlag = self::IMPLIED_DESELECT; 

		$dirs = array();
		$prefixLength = strlen($prefix);
		foreach($filenames as $ignored => $filename)
		{
			$length = strlen($filename);
			if ($filename[$length -1] === '/') 
				$dirs[$prefix . $filename] = $length + $prefixLength;
		}

		if (empty($dirs))
			return;

		
		if (!($resource = ZMC_Mysql::query('SELECT * from ' . $this->restore_job['tableName'])))
			return;
		
		$total = 0;
		$restore = array();
		ZMC::auditLog($msg = __FUNCTION__ . "() looping through entire table " . $this->restore_job['tableName']);
		while ($row = mysql_fetch_assoc($resource))
		{
			foreach($dirs as $dir => $length)
			{
				if (!strncmp($row['filename'], $dir, $length))
					if (strlen($row['filename']) !== $length) 
						$restore[$row['filename']] = null;

				if (count($restore) > 5000)
				{
					$this->updateRestoreFlag(null, array_keys($restore), $restoreFlag);
					$total += count($restore);
					$restore = array();
				}
			}
		}

		if (!is_bool($resource))
			mysql_free_result($resource);

		$this->updateRestoreFlag(null, array_keys($restore), $restoreFlag);
		ZMC::auditLog($msg . ' FINISHED: ' . ($total + count($restore)));
		return $total + count($restore);
	}
	
	public function filterHumanDate(&$date)
	{
		if (isset($_POST['now']) || empty($date)) 
		{
			$this->restore_job['date'] = $this->restore_job['time'] = $this->restore_job['timestamp'] = $this->restore_job['date_time_human'] = '';
			$this->restore_job['date_time_parsed'] = date('F j, Y, g:i a'); 
			return;
		}

		$parsed = getdate(strtotime($date));
		$this->restore_job['timestamp'] = $parsed[0];
		if ($parsed['hours'] === 0 && $parsed['minutes'] === 0 && $parsed['seconds'] === 0)
		{
			
			
			$this->restore_job['timestamp'] += 86399;
			$parsed['hours'] = 23;
			$parsed['minutes'] = 59;
			$parsed['seconds'] = 59;
		}
		$this->restore_job['date_time_parsed'] = date('F j, Y, g:i a', $this->restore_job['timestamp']);
		if ($parsed['year'] < 1990)
			$this->pm->addWarnError("Year '$parsed[year]' must be between the current year and 1980.");
		elseif ($this->restore_job['timestamp'] < ($yearAgo = (time() - 31536000 )))
			$this->pm->addMessage("Searching for a backup more than one year old.");
		elseif ($this->restore_job['timestamp'] < ($yearAgo - 31536000 ))
			$this->pm->addWarning("Searching for a backup more than two years old.");

		foreach($parsed as &$piece)
			if ($piece < 10)
				$piece = '0' . $piece;

		$this->restore_job['date'] = $parsed['year']	. $parsed['mon']	. $parsed['mday'];
		$this->restore_job['time'] = $parsed['hours']	. $parsed['minutes']. $parsed['seconds'];
	}

	private function getBreadCrumbs()
	{
		if ($this->restore_job['cwd'] === '/')
			return $this->restore_job['bread_crumbs'] = $this->restore_job['disk_device'];

		$maxLength = 70;
		$filepaths = array();
		$ancestryIndex = 0;
		$cwd = trim($this->restore_job['cwd'], '/');
		$nodes = explode('/', $cwd);
		if (!empty($nodes))
		{
			$totalBreadCrumbStringLength = 0;
			$exitLoop = false;
			foreach($nodes as $filename)
			{
				$totalBreadCrumbStringLength = strlen($filename) + $ancestryIndex;
				$ancestryIndex++;
				if ($totalBreadCrumbStringLength > $maxLength)
				{
					$newFileNameLength = $maxLength - $totalBreadCrumbStringLength -2;
					if ($newFileNameLength > 0)
						$filename = '...' . substr($filename, -$newFileNameLength);
					else
						break;

					$exitLoop = true;
				}

				$filepaths[] = $filename;

				if ($exitLoop)
					break;
			}
		}

		$length = 0;
		foreach ($filepaths as $path)
			$length += strlen($path) + 4;

		$this->restore_job['bread_crumbs'] = '';
		$crumbs = array();
		if (($length + strlen($this->restore_job['disk_device'])) < $maxLength)
			array_unshift($filepaths, $this->restore_job['disk_device']);
		else
			array_unshift($filepaths, null);


			$last = array_pop($filepaths);

		$fpn = '';
		$notFirst = 0;
		foreach ($filepaths as $filename)
		{
			$fpn .= $filename;
			if (!$notFirst++)
				$fpn = '/';
			elseif ($filename !== '/')
			   	$fpn .= '/';
			$crumbs[] = '<a href="?action=Go&amp;fpn=' . urlencode($fpn) . '">' . ZMC::escape($filename) . "</a>\n";
		}

		if (!empty($last))
			$crumbs[] = $last;

		
		$this->restore_job['bread_crumbs'] = implode('&nbsp;&gt; ', $crumbs);
	}

	
	
	private function getDirContentsWrapper()
	{
		if (!$this->pm->amgetindex_state['running'])
			if (empty($this->pm->rows))
				if (!empty($this->restore_job['cwd_ids']))
				{
					$this->pm->rows =& $this->getDirContents($this->restore_job['cwd_ids']);
					return true;
				}

		return false;
	}

	private function setOptions($restoreType)
	{
		$sqlBody = 'FROM backuprun_dle_state WHERE '
			. " state LIKE 'Backups in %'"
			. ' AND configuration_id = ' . ZMC_BackupSet::getId()
			. " AND hostname = '" . ZMC_Mysql::escape($this->restore_job['client']) . "'";
		$dirSql = " AND directory = '" . ZMC_Mysql::escape($this->restore_job['disk_name']) . "' ";
		$sql = 'SELECT * ' . $sqlBody . $dirSql
			. "AND backuprun_date_time <= "
					. ($this->restore_job['timestamp'] ? "from_unixtime(" . $this->restore_job['timestamp'] . ')' : 'NOW()')
			. ' ORDER BY backuprun_date_time DESC LIMIT 1';
		if (ZMC::$registry->debug) $this->pm->addDetail($sql);
		if (ZMC::$registry->dev_only) $this->pm->addWarning('@TODO: change ' . __CLASS__ . __FUNCTION__ . '() to use Amanda header now recorded in the index directory sinc AE 3.3.0');
		$row = ZMC_Mysql::getOneRow($sql);
		unset($this->restore_job['date_time_image']);
		if (!$row)
		{
			if(($amadmin_find_dle = $this->getAmadminfinds())){
				if(!empty($amadmin_find_dle['status']))
					if(array_search('PARTIAL', $amadmin_find_dle['status']))
						goto not_found_in_amadmin;
				$disklist = $this->getDiskListInfo();
				
				$dle_host_info = $this->pm['set']['configuration_name'].'|'.$this->restore_job['client'].'|'.$this->restore_job['disk_name'];
				if(empty($amadmin_find_dle['dle_list']))
					goto not_found_in_amadmin;
				if(!empty($disklist['dle_list'])){
					if(!empty($disklist['dle_list'][$dle_host_info])){
						$row['directory'] = ZMC_Mysql::escape($disklist['dle_list'][$dle_host_info]['disk_device']); 
						$row['hostname'] = ZMC_Mysql::escape($this->restore_job['client']); 
						$row['disk_name'] = ZMC_Mysql::escape($disklist['dle_list'][$dle_host_info]['disk_name']);
						$row['zmc_type'] = $disklist['dle_list'][$dle_host_info]['property_list']['zmc_type']; 
						$row['zmc_amanda_app'] = $disklist['dle_list'][$dle_host_info]['property_list']['zmc_amanda_app']; 
						$row['configuration_id'] = ZMC_BackupSet::getId(); 
						$this->restore_job['monitor_not_upto_date'] = "DLE entries are not up to date on Monitor screen.";
						goto found_in_amadmin_find;
					}
					else 
						goto not_found_in_amadmin;
				}else
					goto not_found_in_amadmin;
			}
			not_found_in_amadmin:
			$sql = 'SELECT backuprun_date_time ' . $sqlBody . $dirSql . ' ORDER BY backuprun_date_time DESC LIMIT 10';
			if (ZMC::$registry->debug) $this->pm->addDetail($sql);
			$rows = ZMC_Mysql::getAllOneValue($sql);
			if (empty($rows))
			{
				$hosts = ZMC_Mysql::getAllOneValue($sql = 'SELECT hostname ' . $sqlBody . ' LIMIT 1');
				if (!empty($hosts))
				{
					$disk_names = ZMC_Mysql::getAllOneValue($sql = "SELECT DISTINCT(disk_name) $sqlBody ORDER BY disk_name ASC LIMIT 100");
					$err = "No backups found for hostname \"{$this->restore_job['client']}\" and DLE \"{$this->restore_job['disk_name']}\". Try one of these:" . (count($hosts) < 5 ? "\n&emsp;&emsp;" : " ") . implode((count($hosts) < 5) ? "\n&emsp;&emsp;" : ', ', $disk_names);
				}
				else
				{
					$hosts = ZMC_Mysql::getAllOneValue($sql = 'SELECT DISTINCT(hostname) FROM backuprun_dle_state WHERE state LIKE \'Backups in %\' AND configuration_id = ' . ZMC_BackupSet::getId() . ' ORDER BY hostname ASC LIMIT 100');
					$err = "No backups found for hostname \"{$this->restore_job['client']}\". Try one of these:" .  (count($hosts) < 5 ? "\n&emsp;&emsp;" : " ") . implode((count($hosts) < 5) ? "\n&emsp;&emsp;" : ', ', $hosts);
				}
				$this->pm->addError($err);
			}
			else
			{
				foreach($rows as &$row)
					$row = '&bull; <a href="" onclick="gebi(\'date_time_human\').value=\'' . $row . '\'; gebi(\'explore_button\').click(); return false;">' . $row . '</a>';
				$this->pm->addEscapedError("No backups found on or before the specified date, host and directory path, but ZMC found backup images for specified host and directory path created at:\n" . implode(",\n", $rows));
			}

			
			return false;
		}
		found_in_amadmin_find:
		$parsed = getdate(strtotime($row['backuprun_date_time']));
		$this->restore_job['date_time_image'] = ZMC::humanDate($parsed[0]);
		foreach(array('directory', 'program', 'encrypt', 'encryptTool', 'encryptParams', 'compress', 'compressTool', 'compressParams') as $key)
			$this->restore_job[$key] = $row[$key]; 
		if (ZMC::$registry->debug) $this->pm->addDetail(print_r($row, true));
		$this->restore_job['zmc_type'] = strtolower($row['zmc_type']);
		if($this->restore_job['zmc_type'] == "ndmp"){
			$row['zmc_amanda_app'] = (!empty($row['zmc_amanda_app']))? $row['zmc_amanda_app'] : $row['zmc_custom_app'];
		}
		$this->restore_job['zmc_amanda_app'] = strtolower($row['zmc_amanda_app']);
		
		$backupDate = new DateTime($row['backuprun_date_time']);
		$headerFile = ZMC::$registry->etc_amanda . $this->restore_job['config'] . DIRECTORY_SEPARATOR. 'index'
			. DIRECTORY_SEPARATOR . $row['hostname'] . DIRECTORY_SEPARATOR . $row['disk_name']
			. DIRECTORY_SEPARATOR . $backupDate->format('YmdHis') . '_' . $row['backup_level'] . '.header';
		$this->restore_job['disk_device'] = $row['directory'];
		foreach(file($headerFile) as $line){
			if(strpos($line, "<diskdevice>") !== false){
				$this->restore_job['disk_device'] = trim(str_replace("</diskdevice>", "", str_replace("<diskdevice>", "", $line)));
				break;
			}
			if(preg_match("/<diskdevice encoding=\"raw\" raw=\"(.+)\">.+<\/diskdevice>/", $line, $matches)){
				$this->restore_job['disk_device'] = base64_decode($matches[1]);
				break;
			}
		}



		$result = ZMC_Type_AmandaApps::setOptions($this->pm, $this->restore_job, $restoreType); 
		if ($this->restore_job['zwc'])
			if ($this->restore_job['disk_device'] = $this->restore_job['disk_name']) 
				ZMC::normalizeWindowsDle($this->restore_job['disk_device']);

		ksort($this->restore_job);
		return $result;
	}

	private function filterForm()
	{
		$this->filterHumanDate($this->restore_job['date_time_human']); 
		$this->filterHostname($this->restore_job['client']);
		$this->filterDiskName();
	}
}
