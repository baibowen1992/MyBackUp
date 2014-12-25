<?













class ZMC_Restore
{
const EXPRESS = 'express';
const SELECT = 'select';
const SEARCH = 'search';
const ELIST = 'elist';
const RLIST = 'rlist';

const FAILURE = 'Failure'; 
const SUCCESS = 'Success'; 
const CANCELLED = 'Cancelled'; 
const CRASHED = 'Stopped Unexpectedly'; 

private static $once = false;
private static $job;
public static $buttons = array(
	ZMC_Restore::EXPRESS => 'Restore All',
	ZMC_Restore::SELECT => 'Explore & Select',
	ZMC_Restore::SEARCH => 'Search',
);

protected $pm = null;
protected $configName = null;
protected $restore_job = null;
protected $redirect_page = false;


private static $defaults = array(
	'amclient_timelimit' => null,
	'backup_dir_type' => null,
	'bread_crumbs' => '',
	'browseable' => false,
	'client' => '',
	'client_last' => '',
	'configured_how' => false,
	'configured_where' => false,
	'conflict_dir_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
	'conflict_file_selected' => ZMC_Type_AmandaApps::RENAME_RESTORED_N,
	'conflict_resolvable' => false,
	'cwd' => '/',
	'cwd_ids' => 1,
	'cwd_ids_last' => 1,
	'date' => '',
	'date_time_human' => '',
	'digest' => '',
	'disk_device' => '',
	'disk_name' => '',
	'disk_name_last' => '',
	'dryrun' => 0,
	'elist' => '',
	'exchange_db' => '',
	'fpn' => '', 
	'globable' => false,
	'job_name' => 'default', 
	'locale_sort' => 'C',
	'media_explored' => null,
	'media_needed' => null,
	'occ' => 0,
	'restore_type' => '',
	'restore_type_last' => '',
	'restore_pref' => '',
	'restore_pref_last' => '',
	'restore_search' => '',
	'restore_search_last' => '',
	'restore_pattern_type' => '',
	'restore_pattern_type_last' => '',
	'rlist' => '',
	'rs' => '',
	'safe_mode' => true,
	'searchable' => false,
	'selected_count' => 0,
	'target_dir' => '',
	'target_dir_selected_type' => '',
	'target_dir_types' => null,
	'target_host' => '',
	'task_id' => 0,
	'temp_dir' => '',
	'temp_dir_auto' => 1,
	'temp_dir_types' => null,
	'time' => '',
	'timestamp' => '',
	'timestamp_last' => '',
	'timestamp_last' => '',
	'total_files_selected' => 0,
	'total_indirs_selected' => 0,
	'user_input_timeout' => 1800, 
	'zmc_share_domain' => '',
	'zmc_share_password' => '',
	'zmc_share_username' => '',
	'zmc_type' => '',
	'zwc' => false, 
	'restore_device' => '',
	'restore_device_last' => '',
	'point_in_time' => '',
);

protected function __construct(ZMC_Registry_MessageBox $pm, $configName, $resetCheckboxes = false)
{
	self::$job = $this;
	$this->configName = $configName;
	$this->pm = $pm;
	$pm->set = ZMC_BackupSet::get();
	if (!empty($pm->set['backup_running']))
		$pm->addMessage('Backups are running now for this backup set.');

	if ($pm->offsetExists('restore')) 
	{
		$this->restore_job =& $pm->restore; 
		return $this;
	}
	$this->yasumiJobRequest('read', 'default');
	$this->restore_job['restore_list_path'] = ZMC::$registry->etc_amanda . $this->restore_job['config'] . '/restore/';

		if ($profile = file_get_contents(str_replace('restore/', $pm->set['profile_name'] . '.profile', $this->restore_job['restore_list_path'])))
		{
			strtok($profile, ' ');
			$key_name = strtok(" \n"); 
			$this->restore_job['icon'] = ZMC_Type_Devices::getIcon($pm, $key_name, $pm->disabled);
		}
	if ($resetCheckboxes) 
	{
		$pm->rbox = array();
		
		unset($this->restore_job['lbox']);
		if (!empty($this->restore_job['rbox']))
			foreach($this->restore_job['rbox'] as &$set)
				$set = 0;

		if (!empty($_POST['lbox']))
		{
			foreach($_POST['lbox'] as $key => $value)
				$this->restore_job['lbox'][htmlspecialchars_decode($value, ENT_QUOTES)] = $key;
				
			unset($_POST['lbox']);
			unset($_REQUEST['lbox']);
		}

		if (!empty($_REQUEST['rbox']))
		{
			foreach($_POST['rbox'] as $key => $value)
			{
				$selection = htmlspecialchars_decode($value, ENT_QUOTES);
				$this->restore_job['rbox'][$selection] = $key; 
				$pm->rbox[] = $selection; 
			}
			unset($_REQUEST['rbox']);
			unset($_POST['rbox']);
		}
	}
	ZMC::merge($this->restore_job, $_REQUEST); 
	foreach(array('disk_device', 'disk_name', 'password', 'target_dir', 'target_host', 'temp_dir', 'username', 'zmc_share_domain', 'zmc_share_password', 'zmc_share_username') as $key)
		if (isset($this->restore_job[$key])) $this->restore_job[$key] = trim($this->restore_job[$key]);
	$this->restore_job['tableName'] = '`idxns_' . substr($this->configName, 0, 24) . '_' . ZMC_BackupSet::getId($this->configName) . '_' . $this->restore_job['job_name'] . '`';
	$this->restore_job['disk_name'] = trim($this->restore_job['disk_name']);
	$this->restore_job['client'] = trim($this->restore_job['client']);
	$op = ((!empty($_POST['action']) && ($_POST['action'] === 'Reset')) ? 'clear':'get_state');
	$result2 =& ZMC_Yasumi::operation($pm, array( 
			'pathInfo' => "/job_amgetindex/$op/" . $this->configName,
			'job_name' => $this->restore_job['job_name'],
			'data' => array('job' => $this->restore_job)));
	$pm->amgetindex_state =& $result2['state'];
	ksort($this->restore_job);
	if ($op === 'clear') 
		$this->resetToDefaults();
	else
	{
		$this->restore_job['date_time_parsed'] = $pm->restore['date_time_parsed'];
		if ($pm->subnav !== 'now') $this->restore_job['occ_mtime'] = ZMC::occTime();
		$result1 =& ZMC_Yasumi::operation($pm, array( 
				'pathInfo' => "/job_restore/$op/" . $this->configName,
				'data' => array('job' => $this->restore_job)));
		$pm->restore_state =& $result1['state'];

		if(preg_match('/ZMC_Restore_(.*)/', $pm->url))
		{
			if($pm->restore['amclient_timelimit'] == null){
				if(isset($pm->selected_name) && $pm->selected_name != null){
					ZMC_BackupSet::readConf($pm, $pm->selected_name);
					if(!empty($pm->conf['dtimeout'])){
						$pm->restore['amclient_timelimit'] = $pm->conf['dtimeout'];
					}
				}
			}else if($pm->restore['_key_name'] == 'vmware'){
				if ($pm->restore['amclient_timelimit'] < 6000)
					$pm->restore['amclient_timelimit'] = '6000';
			}	

		}

		
		if ($pm->restore_state['running'])
			return $pm->addError('Restore job running.  See progress on the ' . ZMC::getPageUrl($pm, 'Restore', 'Now')
			. '. Please wait for completion, before making any changes.');
		elseif (!empty($pm->restore_state['timestamp_end']))
			if (($secondsAgo = (time() - $pm->restore_state['timestamp_end'])) < 3600)
			{
				$mins = round($secondsAgo / 60, 0);
				$plural = $mins > 1 ? 's' : '';
				$pm->addMessage("A restore job for this backup set completed recently (about $mins minute$plural ago).");
				switch($this->restore_job['zmc_type'])
				{
					case 'windowsss':
						if (empty($this->restore_job['target_dir']) && $pm->restore_state['successful'])
							$pm->addMessage("System State restore succeeded.\nPlease reboot the client machine.");
						break;
					case 'windowsoracle':
						if ($pm->restore_state['successful'])
							$pm->addMessage("Oracle data restored successfully.\nPlease use Oracle Utilities to complete the recovery manually.");
						break;
					case 'windowsexchange':
						if (empty($this->restore_job['target_dir']))
							$pm->addMessage("Manually mount all the databases (Stores) that were dismounted before the restore.");
						break;
					case 'windowshyperv':
						if (empty($this->restore_job['target_dir']))
							$pm->addMessage("Manually mount all the databases (Stores) that were dismounted before the restore.");
						break;
				}
			}
	}

	if ($pm->amgetindex_state['running'])
		$msg = 'Explore job already in progress.  Please wait for completion.';
	elseif ($pm->subnav === 'what')
		return; 
	elseif (!$pm->amgetindex_state['successful'] && ($this->restore_job['restore_type'] !== ZMC_Restore::EXPRESS)) 
		$msg = 'A restore job must be configured first.  Please choose what to restore first.';
	elseif ($pm->subnav === 'where')
	{
		if ($this->whereNextStep())
			return;
		$location = 'ZMC_Restore_What';
	}
	elseif (empty($this->restore_job['configured_where']) || empty($this->restore_job['target_dir_selected_type']))
	{
		$msg = 'Please choose a location for the restore first.';
		$location = 'ZMC_Restore_Where';
	}
	else
		return;

	if ($msg) $pm->addError($msg);
	$this->redirect_page = ZMC::redirectPage(empty($location) ? 'ZMC_Restore_What' : $location, $pm); 
}

protected function whereNextStep()
{
	if ($this->restore_job['restore_type'] === ZMC_Restore::EXPRESS)
		return true;
	if (empty($this->pm->amgetindex_state['successful'])) 
		$this->pm->addError('Please finish an "' . ZMC_Restore::$buttons[ZMC_Restore::SELECT] . '" first, or choose "' . ZMC_Restore::$buttons[ZMC_Restore::EXPRESS] . '" before continuing to the next page.');
	elseif (!($this->restore_job['selected_count'] > 0)) 
		$this->pm->addError('Please select or deselect ' . $this->restore_job['element_names'] . ', before continuing to the next page.');
	else
		return true;
	return false;
}

public static function run(ZMC_Registry_MessageBox $pm)
{
	try
	{ $template = static::runWrapped($pm); }
	catch(Exception $e)
	{
		$pm->addError("$e");
		if (!empty(self::$job) && ($pm->subnav === 'what'))
			self::$job->clear();
		$template = 'Restore' . ucfirst($pm->subnav);
	}
	if (self::$once) return $template;
	self::$once = true;
	if (!empty(self::$job))
	{
		$parts = explode('/', self::$job->restore_job['element_name']);
		$pm->folder_name = ucfirst($parts[1]);
		ZMC_HeaderFooter::$instance->addYui('zmc-utils', array('dom', 'event', 'connection'));
		ZMC_HeaderFooter::$instance->addYui('zmc-task', array('zmc-utils'));
		ZMC_HeaderFooter::$instance->addYui('zmc-messagebox', array('zmc-utils'));
		ZMC_HeaderFooter::$instance->addYui('zmc-restore-what', array('zmc-utils'));
		self::$job->installAjaxMonitor($pm->restore_state['running'] ? (ZMC::$registry->debug ? 1000:1500) : 5000); 
		if (empty($pm->amgetindex_state)){
			$pm->addError("Failed to proceed to the next restore state.");		
			$pm->state = 'Reset';
			self::$job->runState($pm);
			return $template;
			
		}
		$pm->exploring = !empty($pm->amgetindex_state['running']);
		$pm->finished = !empty($pm->amgetindex_state['successful']);
file_put_contents('/tmp/jpm' . __LINE__, print_r($pm, true));
		ZMC_HeaderFooter::$instance->addRegistry(array(
			'was_exploring' => $pm->exploring,
			'was_restoring' => !empty($pm->restore_state['running']),
			
			'pollFreq' => ($pm->exploring ? 5000 : 20000),
			'restore_status_count' => 0,
			'explore_date_started_timestamp' => (empty($pm->amgetindex_state['date_started_timestamp']) ? 0 : $pm->amgetindex_state['date_started_timestamp']),
			'explore_status_count' => empty($pm->amgetindex_state['status']) ? 0 : count($pm->amgetindex_state['status'])
		));
		if (self::$job->restore_job['restore_type'] !== ZMC_Restore::EXPRESS) 
			ZMC_HeaderFooter::$instance->injectYuiCode('YAHOO.zmc.restore.what.loadSuccess()');
		if (!empty(self::$job->restore_job['restore_type']))
			$pm->addMessage("Restore Mode: <b>" . self::$buttons[self::$job->restore_job['restore_type']] . "</b>");
		if(!empty(self::$job->restore_job['monitor_not_upto_date']))
			$pm->addWarning(self::$job->restore_job['monitor_not_upto_date']);
		if (!empty(self::$job->restore_job['media_explored']))
			self::$job->warnIfMediaMissing();
	}
	return $template;
}

protected function installAjaxMonitor($frequency)
{
		ZMC_HeaderFooter::$instance->injectYuiCode("YAHOO.zmc.task.monitor('" . implode("', '", array(
			'Restore',
			$this->restore_job['job_name'],
			$this->pm->restore_state['state'][0],
			ZMC_HeaderFooter::$instance->getUrl('Restore', 'now'),
		))
		. "', 10, $frequency)");
}

protected function yasumiJobRequest($op, $jobName = null, $job = null)
{
	try
	{
		$result =& ZMC_Yasumi::operation($this->pm, array(
			'pathInfo' => "/job_restore/$op/" . $this->configName,
			'data' => array(

				'job' => $job ? $job : $this->restore_job,
				'job_name' => $jobName ? $jobName : $this->restore_job['job_name'],
			),
		));
		if (!empty($result->lstats))
			$this->pm->lstats =& $result->lstats;

		$this->pm->merge($result, null, true); 
		if ($op === 'start' || $op === 'clear')
		{
			$this->pm->restore_state = $result['state'];
			return true;
		}

		$this->resetToDefaults();
		$this->pm->restore_state = $result['state'];
		if (!empty($result['job']))
			ZMC::merge($this->restore_job, $result['job']);
		ksort($this->restore_job);
		return true;
	}
	catch (Exception $e)
	{
		$this->pm->addError("An unexpected problem occurred while trying to $op restore job for $this->configName.");
		return false;
	}
}

protected function clear()
{
	if ($this->pm->amgetindex_state['running'])
		return $this->pm->addError('Please wait for the explore to finish or click "abort".');
	if ($this->pm->restore_state['running'])
		return $this->pm->addError('Please wait for the restore to finish or click "Abort".');
	$occ = $this->restore_job['occ'];
	$this->resetJob(); 
	$this->resetToDefaults(); 
	$this->restore_job['occ'] = $occ;
	
}

protected function resetJob() 
{
	$this->dropRestoreTree(); 
	$this->yasumiJobRequest('clear'); 
	$this->unlinkRestoreLists();
	$this->restore_job['media_explored']	= null;
	$this->restore_job['media_needed']		= null;
	$this->pm->restore_state = null;
}

private function resetToDefaults()
{
	
	$occ = $this->restore_job ? $this->restore_job['occ'] : false;
	$workaround_for_stupid_php_bug = self::$defaults;
	$this->restore_job = $workaround_for_stupid_php_bug;
	if ($this->pm->offsetExists('restore'))
		$this->pm->offsetUnset('restore');
	$this->restore_job['config'] = $this->configName;
	$this->pm->restore =& $this->restore_job; 
	if ($occ) $this->restore_job['occ'] = $occ;
}

protected function mergeToDisk()
{
	$this->yasumiJobRequest('merge');
}

protected function rm() 
{
	$this->yasumiJobRequest('delete');
	$this->dropRestoreTree(); 
}

protected function dropRestoreTree()
{
	if (!empty($this->restore_job['tableName']))
		ZMC_Mysql::query('DROP TABLE IF EXISTS ' . $this->restore_job['tableName'], 'DROP failure while garbage collecting the index_details table.');
}

protected function countSelected() 
{
	if ($count = ZMC_Mysql::getOneValue('SELECT count(*) FROM ' . $this->restore_job['tableName'] . " WHERE restore <> " . ZMC_Restore_What::NO_SELECT))
	{
		$plural = (($count === 1) ? 'One ' . $this->restore_job['element_name'] : $count . ' ' . $this->restore_job['element_names']);
		$this->pm->addMessage("$plural " . $this->restore_job['element_names'] . "selected for restoration.");
	}
	else
		$this->pm->addWarning('No ' . $this->restore_job['element_names'] . ' selected for restoration');

	return $count;
}







protected function &getDirContents($id)
{

	$row = ZMC_Mysql::getOneRow($query = 'SELECT * FROM ' . $this->restore_job['tableName'] . " WHERE id = $id", null, true);
	$f = false;
	if ($row === false)
		return $f;
	$total = array();
	if (empty($row))
		return $total;
	$children = $row['sibling_id'] - $row['id'] -1;
	if ($children > ZMC::$registry->display_max_files) 
	{
		$children = ZMC_Mysql::getOneValue('SELECT COUNT(*) FROM ' . $this->restore_job['tableName'] . " WHERE ((parent_id = $row[id]) AND (id < $row[sibling_id]) AND (id > $row[id]))");
		if ($children > ZMC::$registry->display_max_files)
		{
			$parts = explode('/', $this->restore_job['element_name']);
			$dirName = $parts[1];
			$total = "The $dirName $row[filename] contains too many " . $this->restore_job['element_names'] . " ($children) to display. To restore or exclude these, first navigate to the parent $dirName, and then add or remove a checkmark next to the parent $dirName.";
			return $total;
		}
	}

	$rows = ZMC_Mysql::getAllRowsMap('SELECT * FROM ' . $this->restore_job['tableName'] . " WHERE ((parent_id = $row[id]) AND (id < $row[sibling_id]) AND (id > $row[id]))", null, false, null, 'name');
	foreach($rows as &$row) 
		if (false !== ($pos = strrpos($row['filename'], '/', -2)))
			$row['filename'] = substr($row['filename'], $pos +1);

	reset($rows);
	return $rows;
}

protected function getSelected($prefix)
{
	if (!empty($prefix))
		$prefix .= '/';
	$rows =& ZMC_Mysql::getAllRows('SELECT * FROM ' . $this->restore_job['tableName'] . 'WHERE restore = ' . ZMC_Restore_What::SELECT . ' OR restore = ' . ZMC_Restore_What::DESELECT . ' ORDER BY id'); 
	$this->pm->selected = $this->pm->deselected = array();
	$totalFiles = $totalInDirs = $totalExDirs = $totalExFiles = 0;

	ZMC::mkdirIfNotExists($fn = $this->restore_job['restore_list_path']);
	if (false === ($fp = fopen($fn .= 'rbox', 'w')))
		throw new ZMC_Exception("Unable to create $fn");

	foreach($rows as &$row)
	{
		if ($row['restore'] == ZMC_Restore_What::SELECT)
		{
			if ($row['filename'] === '/')
				fputs($fp, "/$prefix\n");
			else
				fputs($fp, $prefix . trim($row['filename'], '/') . "\n");
		}

		if ($row['type'] == 1)
		{
			$row['count'] = $row['sibling_id'] - $row['id'];
			if ($row['restore'] == ZMC_Restore_What::SELECT)
			{
				$this->pm->selected[] = $row;
				$totalInDirs += $row['count'];
			}
			else
			{
				$this->pm->deselected[] = $row;
				$totalExDirs += $row['count'];
			}
			continue;
		}
		if ($row['restore'] == ZMC_Restore_What::SELECT)
		{
			$totalFiles++;
			$this->pm->selected[] = $row;
		}
		else
		{
			$totalExFiles++;
			$this->pm->deselected[] = $row;
		}
	}
	fclose($fp);
	$this->restore_job['total_files_selected'] = $totalFiles;
	$this->restore_job['total_indirs_selected']  = $totalInDirs;
	$this->restore_job['total_files_deselected'] = $totalExFiles;
	$this->restore_job['total_indirs_deselected']  = $totalExDirs;
	$this->restore_job['file_count'] = $totalFiles + $totalInDirs - $totalExFiles - $totalExDirs; 
}







protected function &getRestoreMap($cwdOnly, &$total, &$warnings, $fullPathName = false, $restoreMap = true)
{
	



	$query = 'FROM ' . $this->restore_job['tableName'] . ' ';
	if ($restoreMap)
		$query .= 'WHERE restore >= ' . ZMC_Restore_What::IMPLIED_SELECT;
	else
		$query .= 'WHERE restore = ' . ZMC_Restore_What::DESELECT; 

	if ($cwdOnly)
		$query .= ' AND parent_id = ' . $this->restore_job['cwd_ids'];

	$total = ZMC_Mysql::getOneValue("SELECT count(*) $query");
	if ($total <= ZMC::$registry->display_max_files)
		$query .= ' GROUP BY filename';
	else
	{
		$warnings = "Too many results ($total) to display. Showing only the first few directories (if any) selected.";
		$query .= ' AND type = 1 GROUP BY filename LIMIT ' . ZMC::$registry->display_max_files;
	}

	if ($fullPathName)
	{
		$filenames =& ZMC_Mysql::getAllOneValue('SELECT filename ' . $query);
		return $filenames;
	}
	
	if (false === ($resource = ZMC_Mysql::query('SELECT filename ' . $query)))
		return array();

	$fnHash = array();
	while ($row = mysql_fetch_row($resource)) 
		if ($pos = strrpos($row[0], '/', -2))
			$fnHash[substr($row[0], $pos +1)] = null;
		else
			$fnHash[$row[0]] = null;

	mysql_free_result($resource);
	return $fnHash;
}

protected function resetSelections()
{
	ZMC_Mysql::query('UPDATE ' . $this->restore_job['tableName'] . ' SET restore = 0;');
	$this->restore_job['selected_count'] = 0;
	$this->restore_job['configured_how'] = false;
	$this->restore_job['configured_where'] = false;
	$this->restore_job['total_files_selected'] = 0;
	$this->restore_job['total_indirs_selected'] = 0;
}

protected function updateRestoreFlag($prefix, $filenames, $restoreFlag)
{
	if (empty($filenames))
		return;

	if (!empty($prefix))
		foreach($filenames as &$filename)
			$filename = $prefix . $filename;

	ZMC_Mysql::query('UPDATE ' . $this->restore_job['tableName'] . " SET restore = $restoreFlag WHERE filename IN ("
		. "'" . join("','", array_map('mysql_escape_string', $filenames)) . "')");
}

private function existsInIndexTable($id)
{	return ZMC_Mysql::getOneValue('SELECT COUNT(*) FROM ' . $this->restore_job['tableName'] . " WHERE id='$id'"); }

protected function setCwd(&$restore_job, $path, $failureOk = false)
{
	if (substr($path, -1) !== '/') 
		$path .= '/';

	$ids = $this->path2ids($path, $failureOk);
	if (empty($ids))
		return false;

	$restore_job['cwd_ids'] = $ids;
	$restore_job['cwd'] = $path;
	return true;
}

protected function getRecord($id)
{	return ZMC_Mysql::getOneRow('SELECT * FROM ' . $this->restore_job['tableName'] . " WHERE id='$id'"); }

protected function path2ids($path, $failureOk = false)
{
	if ($path[0] !== '/')
		ZMC::quit("$path does not begin with /");
	if (substr($path, -1) !== '/')
		$path .= '/';
	$path = ZMC_Mysql::escape($path);
	return ZMC_Mysql::getOneValue('SELECT id FROM ' . $this->restore_job['tableName'] . " WHERE filename='$path'", null, $failureOk);
}

protected function createIndexTable()
{
	$id = $this->restore_job['tableName'];
	if (empty($id))
		ZMC::quit($this->restore_job);
	$levelInt = 'tinyint';
	if ($this->restore_job['zmc_amanda_app'] === 'zwcinc' || $this->restore_job['zmc_amanda_app'] === 'zwcdiff' || $this->restore_job['zmc_amanda_app'] === 'zwclog')
		$levelInt = 'BIGINT'; 
	ZMC_Mysql::query("DROP TABLE IF EXISTS $id");
	ZMC_Mysql::query(<<<EOD
CREATE TABLE $id (
  id int(11) unsigned NOT NULL DEFAULT '0',
  parent_id int(11) unsigned NOT NULL DEFAULT '0',
  sibling_id int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'All rows between id and sibling_id are descendants of this row',
  level $levelInt unsigned NOT NULL DEFAULT '0' COMMENT 'Bitmask: bit position n indicates object exists in Level n',
  type tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '@TODO: remove after ZMC 3.3.0',
  restore tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'See Restore.php',
  filename mediumtext NOT NULL COMMENT 'full path filename' COMMENT 'full path, including filename',
  name varchar(255) NOT NULL COMMENT 'filename minus path (for searching)',
  PRIMARY KEY (id),
  KEY filename (filename(765)),
  KEY parent_id (parent_id),
  KEY restore (restore)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
EOD
	); 
}

protected function filterHostname(&$hostname)
{
	$hostname = trim($hostname);
	if (empty($hostname))
		$this->pm->addError('A host name is required.');
	elseif (!ZMC::isValidHostName($hostname))
		$this->pm->addWarnError("The host name provided ($hostname) is not valid.  Valid hosts naHes begin with an alphabetic character, end with an alphanumeric character, and may contain alphanumeric, '-' (hyphen), and '.' (period) characters in between.  Alterntively, IP addresses are also valid host names.");
}







protected function getDirectoryAncestry($currentId)
{
	if (empty($currentId) || !$this->existsInIndexTable($currentId))
		return array();

	$ancestorList = array();
	$ancestorIndex = 0;
	do
	{ 
		$row = $this->getRecord($currentId);
		$ancestorList[$ancestorIndex]['filename'] = $row['filename'];
		$ancestorList[$ancestorIndex]['id'] = $currentId;
		$currentId = $row['parent_id'];
		$ancestorIndex++;
	} while($currentId != 0);

	return array_reverse($ancestorList);
}

protected function findMedia($msg)
{
	$this->prepareNewJob($msg, '/amadmin/find_tapes/' . ZMC_BackupSet::getName());
	return($this->pm->isErrors() === 0);
}

protected function createRestoreTree($msg)
{
	$this->createIndexTable();
	$result = $this->prepareNewJob($msg, '/job_amgetindex/create_restore_tree/' . ZMC_BackupSet::getName());
	$this->pm->amgetindex_state =& $result['state'];
	return($this->pm->isErrors() === 0);
}

protected function prepareNewJob($msg, $pathInfo)
{
	$restore_search =($this->pm->state == "Search")? $this->restore_job['restore_search'] : '';
	$restore_pattern_type =($this->pm->state == "Search")? $this->restore_job['restore_pattern_type'] : 'default_match';
	$result = ZMC_Yasumi::operation($this->pm, array(
		'pathInfo' => $pathInfo,
		'data' => array(
			'job_name' => $this->restore_job['job_name'],
			'table' => $this->restore_job['tableName'],
			'host' => $this->restore_job['client'],
			'disk_name' => $this->restore_job['disk_name'],
			'date' => $this->restore_job['date'],
			'time' => $this->restore_job['time'],
			'_key_name' => $this->restore_job['_key_name'],
			'zmc_message' => $msg,
			'restore_search' => $restore_search,
			'restore_pattern_type' => $restore_pattern_type,
		)), true);
	$this->pm->merge($result, null, true);
	$media_explored =& $result['needed'];
	$restoreDevice = $this->restore_job['restore_device'];
	$isVaultDevice = $restoreDevice !== $this->pm->set['profile_name'];
	foreach($media_explored as $key => $tape){
		if($isVaultDevice && !preg_match('/^' . $this->pm->selected_name . '-' . $restoreDevice . '.*-vault-[0-9][0-9][0-9]$/', $tape['tape_label']))
			unset($media_explored[$key]);
		if(!$isVaultDevice && preg_match('/^' . $this->pm->selected_name . '-.+' . '-vault-[0-9][0-9][0-9]$/', $tape['tape_label']))
			unset($media_explored[$key]);
	}
	$this->restore_job['media_explored'] = $media_explored;
}

protected function unlinkRestoreLists()
{
	$this->restore_job['media_needed'] = null;
	$this->restore_job['media_counts'] = null;
	if (!empty($this->restore_job['restore_list_path']))
		if ($result = ZMC::rmrdir($this->restore_job['restore_list_path'])) 
			$pm->addError($result);
}

protected function createRestoreLists()
{
	$j =& $this->restore_job;
	$zmc_amanda_app = $this->restore_job['zmc_amanda_app'];
	$path = $this->restore_job['restore_list_path'];
	ZMC::mkdirIfNotExists($path);
	$express = ($j['restore_type'] === ZMC_Restore::EXPRESS);
	$excludable = $j['excludable'] && ($zmc_amanda_app !== 'windowsdump'); 
	$prefix = '';
	if ($j['zmc_type'] === 'cifs')
	{
		ZMC::parseShare($j['disk_name'], $cifshost, $cifsname, $cifspath);
		if (!empty($cifspath))
			$prefix = str_replace('\\', '/', $cifspath); 
	}
	switch($zmc_amanda_app)
	{
		case 'cifs':
			$this->createAmandaPass($path);
			break;

		case 'vmware':
		case 'vmware_quiesce_off':
		case 'vmware_quiesce_on':
			if($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL){
				$contents = file_get_contents(ZMC::$registry->etc_amanda . $this->restore_job['config'] . DIRECTORY_SEPARATOR . 'esxpass');
				if($contents)
					if(file_put_contents($path . 'amandapass', $contents))
						return;
				$this->pm->addWarnError("Unable to create the password file ($fn) needed to restore the backup image. " . ZMC::getFilePermHelp($fn));
				return;
			} else {
				ZMC::parseShare($this->restore_job['target_dir'], $esxhost, $ignore1, $ignore2);
				if (false === file_put_contents($fn = $path . 'amandapass', "//$esxhost " . $this->restore_job['zmc_share_username'] . '%' . $this->restore_job['zmc_share_password'] . "\n"))
					$this->pm->addWarnError("Unable to create the password file ($fn) needed to restore the backup image. " . ZMC::getFilePermHelp($fn));
			}
			break;
	}

	foreach(array(self::ELIST, self::RLIST) as $listType) 
	{
		if (empty($j[$listType]))
		{
			$j[$listType] = '';
			$j["count_$listType"] = 0;
			continue;
		}
		if ($j['zwc'] && $listType === self::ELIST)
		{
			$this->pm->addWarnError("Restore Cancelled: ZWC does not support \"exclude\" patterns when restoring.");
			if (ZMC::$registry->safe_mode) return;
		}
		











		$list = array_filter(explode("\n", strtr($j[$listType], "\r", "\n")));
		$j["count_$listType"] = count($list);
		$j[$listType] = implode("\n", $list);
		file_put_contents($fn = $path . $listType . '000', $j[$listType]);
	}

	if ($express) 
	{
		$this->restore_job['recursive'] = true;
		return $j['media_needed'] =& $j['media_explored'];
	}

	$j['media_needed'] = array();
	$this->getSelected($prefix);
	if (!empty($cifspath))
		$prefix = '/' . $prefix;
	if (!empty($j['media_needed']) && is_dir($path))
	{
		
		{
			if (ZMC::$registry->debug) $pm->addMessage("Using cached restore lists.");
			return; 
		}
	}
	$seen = array();
	$counts = $files = array(self::ELIST => array(), self::RLIST => array());
	$recursive = $this->restore_job['recursive'];
	$restoreCondition = '(restore = ' . ZMC_Restore_What::SELECT . ') OR (restore = ' . ZMC_Restore_What::DESELECT . ')';
	if ($this->restore_job['_key_name'] !== 'ndmp')
		$restoreCondition .= ' OR (restore = ' . ZMC_Restore_What::IMPLIED_SELECT . ')';
	if (false === ($resource = ZMC_Mysql::query($sql = "SELECT filename, restore, level, type FROM " . $this->restore_job['tableName'] . " WHERE ($restoreCondition) AND (parent_id <> 0) ORDER BY id")))
		return $this->pm->addError('DB Error.  Try ' . ZMC_Restore::EXPRESS . ' mode to restore everything.');

	if (!is_resource($resource)) ZMC::quit($resource);
	while ($row = mysql_fetch_row($resource)) 
	{
		list($filename, $restore, $bits, $type) = $row;
		settype($restore, 'int');
		
		settype($bits, 'int');
		if ($bits === 0 || $bits === 1) 
			$level = 0;
		elseif ($bits > 255)
			for($level = 1; ($bits = ($bits >> 1));)
				$level++;
		elseif ($bits >= 0x10) if ($bits >= 0x40) if ($bits >= 0x80) $level = 7; else $level = 6; elseif ($bits >= 0x20) $level = 5; else $level = 4; elseif ($bits >=0x4) if ($bits >=0x8) $level = 3; else $level = 2; elseif ($bits >= 0x2) $level = 1; else $level = 0;

		switch($zmc_amanda_app)
		{
			case 'cifs':
				if ($prefix !== '') $filename = $prefix . $filename;
				if ($j['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_CIFS)
				{
					
					
					$filename = '.' . str_replace(array('*', '?'), array('\*', '\?'), $filename);
					break;
				}
				
			case 'bsdtar':
			case 'gtar': 
				$filename = '.' . str_replace(array('*', '?', '[', ']'), array('\*', '\\\?', '\[', '\]'), rtrim($filename, '/'));
				break;
			case 'suntar':
				$filename = '.' . str_replace(array('*', '?', '[', ']'), array('\*', '\\?', '\[', '\]'), rtrim($filename, '/'));
				break;
			case 'star':
				$filename = ltrim($filename, '/');
				break;
		}
		$listType = (($restore >= ZMC_Restore_What::IMPLIED_SELECT) ? self::RLIST : self::ELIST);
		@$j['media_counts'][$listType][$level]++;
		if ($recursive)
		{
			switch($restore)
			{
				
				case ZMC_Restore_What::DESELECT:
					$level = '0'; 
					for($f = dirname($filename); strlen($f) > 1; $f = dirname($f))
						if (isset($seen[$listType][$level][$f]))
							continue 3;

					if ($type === '1') 
						$seen[$listType][$level][$filename] = true;
					break;

				case ZMC_Restore_What::SELECT:
					if ($type === '1') 
						$seen[$listType][$level][$filename] = true;
					break;

				case ZMC_Restore_What::IMPLIED_SELECT:
					for($f = dirname($filename); strlen($f) > 1; $f = dirname($f))
						if (isset($seen[$listType][$level][$f]))
							continue 3;
					break;
			}
		}

		if (empty($files[$listType][$level]))
		{
			$fp = fopen($fn = $path . $listType . $level, 'w');
			if (false === $fp)
				return $this->pm->addError("Unable to open $fn for writing " . ZMC::getFilePermHelp($fn));
			$files[$listType][$level] = array('fp' => $fp, 'fn' => $fn);
		}
		
		if($this->restore_job['zmc_type'] === 'windowssqlserver' || $this->restore_job['zmc_type'] === 'windowsexchange' || $this->restore_job['zmc_type'] === 'windowshyperv'){
			if(substr($filename, -1) === '/' || substr_count($filename, '/') != 4)
				continue;
		}
		
		fputs($files[$listType][$level]['fp'], $filename . "\n");
		@$counts[$listType][$level]++;
	}

	if (is_resource($resource)) mysql_free_result($resource);
	$level2media = array();
	foreach($j['media_explored'] as &$record){
		if(!isset($level2media[$record['level']]) || $level2media[$record['level']]['timestamp'] < $record['timestamp'])
			$level2media[$record['level']] =& $record;
	}

	foreach($files as $listType => &$levelHash)
		foreach($levelHash as $level => $list)
		{
			fclose($list['fp']);
			if (($listType === self::ELIST) && !isset($files[self::RLIST][$level]))
				unlink($list['fn']); 
			elseif (!empty($level2media[$level]))
				$j['media_needed'][$level] = $level2media[$level];
		}
	
	if($j['zmc_type'] === 'windowssqlserver' || $j['zmc_type'] === 'windowsexchange' || $j['zmc_type'] === 'windowshyperv'){
		if(!empty($level2media['0']))
			$j['media_needed']['0'] = $level2media['0'];
	}
	
	if((($j['zmc_type'] === 'windowsexchange' || $j['zmc_type'] === 'windowshyperv') && $j['zmc_amanda_app'] === 'zwcinc') || ($j['zmc_type'] === 'windowssqlserver' && $j['zmc_amanda_app'] === 'zwclog'))
		$j['media_needed'] = $j['media_explored'];

	$this->mergeToDisk();
	if ($j['zwc'] && count($excludes = glob($path . self::ELIST . '*', GLOB_NOSORT)))
		ZMC::quit(); 
}

private function createAmandaPass($path)
{
	$share = $this->restore_job['destination_location'];
	if ($share[0] !== '\\')
		return;

	if (empty($this->restore_job['zmc_share_username']))
	{
		if($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL){
			$contents = file_get_contents(ZMC::$registry->etc_amanda . $this->restore_job['config'] . DIRECTORY_SEPARATOR . 'cifs_network_shares');
			if($contents)
				if(file_put_contents($path . 'amandapass', $contents))
					return;
			$this->pm->addWarnError("Unable to create the password file ($fn) needed to restore the backup image. " . ZMC::getFilePermHelp($fn));
			return;
		}
		$this->pm->addWarnError("Please enter a Network/CIFS Share username on the Backup|where page.");
		if (ZMC::$registry->safe_mode) return;
	}

	$len = strlen($share);
	$disk = $share;
	for ($n = 1, $i = 0; $i < $len; $i++) 
		if (($share[$i] === '\\') && $n++ === 4)
		{
			$disk = substr($share, 0, $i);
			break;
		}

	if (empty($this->restore_job['zmc_share_password']))
		$this->restore_job['zmc_share_password'] = '';

	if (false !== strpos($this->restore_job['zmc_share_username'], '%'))
		$this->pm->addWarnError("Network/CIFS Share Destination Location $share must not use a share username containing a '%' (percent) symbol: " . $this->restore_job['zmc_share_username']);
	$amandapass = ZMC_Yasumi_Parser::quote($disk) . ' ' . ZMC_Yasumi_Parser::quote($this->restore_job['zmc_share_username']) . '%' . ZMC_Yasumi_Parser::quote($this->restore_job['zmc_share_password']);
	if (!empty($this->restore_job['zmc_share_domain']))
		$amandapass .= ' ' . ZMC_Yasumi_Parser::quote($this->restore_job['zmc_share_domain']);
	file_put_contents($path . 'amandapass', "$amandapass\n");
}

protected function isConflictResolutionConfigurable()
{
	if (empty($this->restore_job['conflict_resolvable']))
		$result = false;
	elseif (is_bool($this->restore_job['conflict_resolvable']))
		$result = $this->restore_job['conflict_resolvable'] ? array_keys(ZMC_Type_AmandaApps::$default_options) : false;
	elseif (!is_array($this->restore_job['conflict_resolvable']))
		throw new ZMC_Exception("ZMC configuration error: no conflict resolution methods found for DLEs of type: " . $this->restore_job['zmc_amanda_app']);
	else
	{
		$cr = $this->restore_job['conflict_resolvable'][$this->restore_job['target_dir_selected_type']];
		if (is_bool($cr))
			$result = $this->restore_job['conflict_resolvable'] ? array_keys(ZMC_Type_AmandaApps::$default_options) : false;
		elseif (!is_array($this->restore_job['conflict_resolvable'][$this->restore_job['target_dir_selected_type']]))
			throw new ZMC_Exception("ZMC configuration error: no conflict resolution methods found for destination locations of type: " . ZMC_Type_AmandaApps::$dirTypes[$this->restore_job['target_dir_selected_type']]['field']);
		else
			$result = $cr;
	}

	if ($result === false)
		$this->restore_job['conflict_dir_options'] = ZMC_Type_AmandaApps::NA; 
	elseif ($result === true)
		$this->restore_job['conflict_dir_options'] = array_keys(ZMC_Type_AmandaApps::$default_options);
	else
		$this->restore_job['conflict_dir_options'] = $result;

	$this->restore_job['conflict_file_options'] = (is_array($this->restore_job['conflict_dir_options']) ? array_diff($this->restore_job['conflict_dir_options'], array(ZMC_Type_AmandaApps::REMOVE_EXISTING)) : $this->restore_job['conflict_dir_options']);
	







	return !($result === false);
}

protected function startRestore()
{
	if (empty($this->restore_job['media_needed']))
		return $this->pm->addError("Please select a valid combination of date, host and DLE on the " . ZMC::getPageUrl($this->pm, 'Restore', 'what'));

	foreach($this->restore_job['media_needed'] as $record)
	{
		try
		{	$row =& ZMC_Mysql::getOneRow(array('backuprun_dle_state',
				'configuration_id' => ZMC_BackupSet::getId(),
				'hostname' => $this->restore_job['client'],
				'directory' => $this->restore_job['disk_name'],
				'backuprun_date_time' => $record['datetime']));
		}
		catch (Exception $e)
		{ return $this->pm->addError("Can not locate backup record in ZMC DB for $record[date] $record[time]. Manual restore will be required."); }

		
			
		$decrypt = '--no-decrypt';
		if ($row['encrypt']) 
		{
			$decrypt = '--decrypt';
			if ($this->restore_job['zwc']){
				if(isset($row['encryptTool']) && $row['encryptTool'] === 'zwcaes')
					$task['encrypt-algo'] = '3';
				$decrypt = (($row['encrypt'] == 2 ) ? '--decrypt' : '--no-decrypt');
			}
		}
		$decompress = '--no-decompress';
		if ($row['compress']) 
		{
			$decompress = '--decompress';
			if ($this->restore_job['zwc'])
				$decompress = (($row['compress'] > 3) ? '--decompress' : '--no-decompress');
		}
		$task['index_files'][$record['index']] = "$decrypt,$decompress";
	}
	$argMap = array( 
			'amclient_timelimit' => 1,
			'client' => 1,
			'config' => 1,
			'configuration_id' => 1,
			'conflict_dir_selected' => 1,
			'conflict_file_selected' => 1,
			'debug' => 1,
			'digest' => 1,
			'disk_device' => 1,
			'disk_name' => 1,
			'dryrun' => 1,
			'exchange_db' => 0,
			'extension' => 1,
			'file_count' => ($this->restore_job['restore_type'] !== ZMC_Restore::EXPRESS),
			'index_files' => 1,
			'job_name' => 1,
			'media_needed' => 1,
			'recursive' => 1,
			'restore_type' => 1,
			'safe_mode' => 1,
			'target_dir' => 0, 
			'target_dir_selected_type' => 1,
			'target_host' => 1,
			'target_port' => 1,
			'task_id' => 0,
			'temp_dir' => 0,
			'user_name' => 1,
			'user_input_timeout' => 1,
			'zmc_amanda_app' => 1,
			'zmc_type' => 1,
			'zwc' => 1,
			'point_in_time' => 1,
	);

	if ($this->restore_job['temp_dir'] === '/tmp')
	{
		if (ZMC::$registry->platform === 'solaris')
			$this->pm->addWarning('If using Solaris, then the ENTIRE backup image will be copied into tmpfs (RAM)!');
		$this->pm->addWarning('Using default temporary directory "/tmp/".  If the partition is too small, restore will fail. After restore, backups must be copied from /tmp partition to destination (slow).');
	}

	if ($this->restore_job['_key_name'] === 'ndmp')
	{
		if ($this->restore_job['data_path'] === 'amanda') 
			$task['target_dir'] = $this->restore_job['disk_device'] . ':::' . $this->restore_job['target_dir'];
		if(isset($this->restore_job['ndmp_filer_host_name'])
			&& isset($this->restore_job['ndmp_volume_name'])
			&& isset($this->restore_job['ndmp_directory'])
			&& isset($this->restore_job['ndmp_username'])
			&& isset($this->restore_job['ndmp_password'])
			&& isset($this->restore_job['ndmp_filer_auth'])
			&& isset($this->restore_job['remove_ndmp_credentials'])){
			$task['ndmp_filer_host_name'] = $this->restore_job['ndmp_filer_host_name'];
			$task['ndmp_volume_name'] = $this->restore_job['ndmp_volume_name'];
			$task['ndmp_directory'] = $this->restore_job['ndmp_directory'];
			$task['ndmp_username'] = $this->restore_job['ndmp_username'];
			$task['ndmp_password'] = $this->restore_job['ndmp_password'];
			$task['ndmp_filer_auth'] = $this->restore_job['ndmp_filer_auth'];
			$task['remove_ndmp_credentials'] = $this->restore_job['remove_ndmp_credentials'];
		}
	}
	elseif ($this->restore_job['_key_name'] === 'windowssqlserver'){
		if($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME)
			$task['sql_alternate_name'] = $this->restore_job['sql_alternate_name'];
		elseif($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH)
			$task['sql_alternate_path'] = $this->restore_job['sql_alternate_path'];
	}
	elseif ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL)
	{
		$task['target_dir'] = $this->restore_job['disk_device'];
		if (	$this->restore_job['zwc']
			&& ($this->restore_job['host_type'] === ZMC_Type_AmandaApps::HOST_TYPE_WINDOWS)
			&& ($this->restore_job['zmc_type'] !== 'windows'))
			$task['target_dir'] = ''; 
	}
	elseif ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_EXCHANGE)
		$task['exchange_db'] = 'yes';

	$task['debug'] = (ZMC::$registry->debug ? 1 : 0);
	$task['rlist'] = $task['elist'] = '';
	$task['configuration_id'] = ZMC_BackupSet::getId();

	

















	$missing = array();
	foreach($argMap as $key => $amfetch)
		if (!isset($task[$key]))
			if (isset($this->restore_job[$key]))
				$task[$key] = $this->restore_job[$key];
			elseif ($amfetch)
				$missing[] = $key;

	ksort($task);
	if (!empty($missing))
		return $this->pm->addEscapedError('Restore request configuration is not complete.  Please return to previous restore pages to provide the missing information: ' . implode(', ', $missing));

	$this->yasumiJobRequest('start', $this->restore_job['job_name'], $task);
}

protected function hostChecks()
{
	if (false === ($this->restore_job['target_port'] = 10081)) 
		return $this->pm->addWarnError('Found problem with entry for "amanda" service in /etc/services.');

	if (empty($this->restore_job['target_host']))
		return;

	$host = $this->restore_job['target_host'];
	if (!filter_var($host, FILTER_VALIDATE_IP))
		if (!ZMC::isValidHostname($host))
			$err = $this->pm->addWarnError("Invalid Destination Hostname: $host");
		else
		{
			if(true === ZMC::$registry->dns_server_check)
			{
				if (strpos($host, '.') && ($host[strlen($host) -1] !== '.'))
					$host .= '.';
				if ($host === 'localhost')
					$host .= '.';
				$records = dns_get_record($host);
				if (empty($records))
					$err = $this->pm->addWarnError("No DNS entry found for Destination Host: $host;");
			}
		}

	if (!empty($err) && ZMC::$registry->safe_mode) return;
	if ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_CIFS)
		$host = '127.0.0.1'; 
	if ($sock = fsockopen($host, $this->restore_job['target_port'], $errno, $errstr, ZMC::$registry->proc_open_ultrashort_timeout))
	{
		$payload = self::clientStatus($this->restore_job, $_SESSION['user_id'], $_SESSION['user'], '004');
		$packet = pack('CCnnnNNNNNN', 7, 0, 3, 32, 1, strlen($payload), 4, 1, 0, 0, 0) . $payload;
		$result = ZMC::socket_put_contents($sock, $packet, 32768, 'The Destintation Host did not respond.', $this->job['amclient_timelimit']);
		if (empty($result))
			$result = ZMC::socket_get_contents($sock, 'The Destination Host did not respond correctly.');
		fclose($sock);
		$start = strpos($result, 'READY FOR REQUEST', 33);
		if ($start === false)
			return $this->pm->addWarning("The Destination Host $host is NOT ready for a restore request.\n$result");
		$end = strpos($result, "\n", $start+1);
		$ready = str_replace('READY FOR REQUEST', 'Zmanda Restore Client (', substr($result, $start, $end - $start));
		$ready .= ') is ready for restore request';
		$this->pm->addMessage($this->restore_job['target_host'] . ": $ready");
		$start = strpos($result, 'SELinux', $end);
		if ($start === false)
			return;
		$length = strpos($result, "\n", $start+1) - $start;
		$selinux = substr($result, $start, $length);
		if (strpos($selinux, 'Disabled'))
			$this->pm->addMessage($selinux);
		else
			$this->pm->addWarning($selinux);
	}
	else
		$this->pm->addWarnError("Destination Host: Unable to open \"amanda\" port " . $this->restore_job['target_port'] . " on $host. Check firewalls and correct xinetd/inetd/Amanda client installation on Destination Host.");
}

public static function clientStatus($job, $user_id, $username, $debug, $historyFileName = '')
{
	return "<QUERY>\nCLIENT-STATUS\n"
			. "amclient_timelimit={$job['amclient_timelimit']}\n"
			. "config={$job['config']}\n"
			. "debug=$debug\n"
			. "digest={$job['digest']}\n"
			. "disk_device={$job['disk_device']}\n"
			. "disk_name={$job['disk_name']}\n"
			. "dryrun={$job['dryrun']}\n"
			. "history={$historyFileName}\n"
			. "job_name={$job['job_name']}\n"
			. "password=root\n"
			. "safe_mode=".(empty($job['safe_mode']) ? 'off':'on')."\n"
			. "user_id=$user_id\n"
			. "username=$username\n"
			. "zmc_build_branch=" . ZMC::$registry->svn->zmc_build_branch . "\n"
			. "zmc_build_version=" . ZMC::$registry->svn->zmc_build_version . "\n"
			. "zmc_svn_build_date=" . ZMC::$registry->svn->zmc_svn_build_date . "\n"
			. "zmc_svn_distro_type=" . ZMC::$registry->svn->zmc_svn_distro_type . "\n"
			. "zmc_svn_revision=" . ZMC::$registry->svn->zmc_svn_revision . "\n"
			. "</QUERY>\n";
}

protected function warnIfMediaMissing()
{
	$tapelist = array();
	ZMC_BackupSet::getTapeList($this->pm, $tapelist, $this->restore_job['config']);
	foreach($this->restore_job['media_explored'] as &$media)
		if (!file_exists($fn = ZMC::$registry->etc_amanda . $this->restore_job['config'] . '/index/' . $media['host']
			. '/' . str_replace(array('\\', '/', ':'),array('_','_','_'), $media['disk_name'])
			. '/' . $media['index']))
			$this->pm->addWarnError("Media has been erased. Please Clear your restore.", "$fn missing");
}

protected function mergeTapeStats()
{
	$tapelist = array();
	ZMC_BackupSet::getTapeList($this->pm, $tapelist, $this->restore_job['config']);
	foreach($this->restore_job['media_needed'] as &$media)
		if (!empty($tapelist['tapelist'][$media['tape_label']]))
			ZMC::merge($media, $tapelist['tapelist'][$media['tape_label']]);
}
}
