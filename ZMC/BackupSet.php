<?



















class ZMC_BackupSet
{
	const ABORTED = 'Aborted'; 
	const STARTED = 'Started';
	const FAILED = 'Failed';
	const FINISHED = 'Finished';
	const BAD_NAME = 'Use only alphanumeric characters, period, underscore, and hyphen characters in backup set names.';
	const ZMC_TEST_QUICK = 'zmc_test_quick';

	private   static $started = false;
	
	protected static $sets = array();

	
	protected static $hiddenSets = array();

	
	protected static $names2ids = array();

	
	protected static $myNames = array();

	
	protected static $allStatus = array();

	
	protected static $tapeListStat = array();

	const CRONTAB_STATUS = 'CRONTAB_STATUS';

	




	public static function start(ZMC_Registry_MessageBox $pm, $bootstrap = false)
	{
		if ($bootstrap)
		{
			if (file_exists($fn = ZMC::$registry->tmp_path . self::CRONTAB_STATUS))
				include $fn;
		}
		else
		{
			if (self::$started) 
				return;
			self::$started = true;
			$cacheFn = self::CRONTAB_STATUS;
			if (!ZMC::useCache($pm, __FILE__, $cacheFn, false, ZMC::$registry->proc_open_short_timeout)
				|| ((include $cacheFn) !== true))
			{
				$file = new ZMC_Sed($pm, $cacheFn);
				$allStatus = ZMC_Yasumi::operation($pm, array('pathInfo' => "/crontab/get_all_status"));
				if (isset($allStatus['all_status']) && is_array($allStatus['all_status']))
				{
					self::$allStatus =& $allStatus['all_status'];
					if (false === $file->close('<? self::$allStatus = ' . var_export(self::$allStatus, true) . ";\nreturn true;\n"))
						throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to cache ZMC crontab schedule. " . ZMC::getFilePermHelp($cacheFn)));
				}
			}
		}
		self::$myNames = array();
		self::$names2ids = array();
		self::$sets = array();
		self::$hiddenSets = array();
		$result = ZMC_Mysql::getAllRowsMap('SELECT * FROM configurations ORDER BY configuration_name', 'Unable to load configurations table', false, null, 'configuration_name');
		if (!is_array($result) || !isset($_SESSION['user_id']))
			return;

		ZMC::merge($result, self::$allStatus);
		foreach($result as &$set)
		{
			if (ZMC::$registry['debug_level'] > ZMC_Error::NOTICE)
				ksort($set);

			if (!isset($set['configuration_id']))
			{
				if (ZMC::$registry->dev_only) ZMC::quit(array($set, self::$allStatus, $result, self::$names2ids));
				continue;
			}
			self::$names2ids[$set['configuration_name']] = $set['configuration_id'];
			
			if ($set['version'] !== ZMC::$registry->zmc_backupset_version)
			{
				self::$hiddenSets[$set['configuration_id']] =& $set;
				continue;
			}

			if (empty($status['dles_total']))
				$status['dles_total'] = 0;
			self::$sets[$set['configuration_id']] = &$set;
			if (	ZMC_User::hasRole('Administrator')
				||	ZMC_User::hasRole('RestoreOnly')
				||	ZMC_User::hasRole('Monitor')
				|| ($set['owner_id'] == $_SESSION['user_id']))
				self::$myNames[$set['configuration_name']] = $set['configuration_id'];
		}
	}

	public static function cancelEdit()
	{
		if (!empty($_SESSION['configurationName']))
			$_SESSION['prior_name'] = $_SESSION['configurationName'];
		unset($_SESSION['configurationName']);
	}

	






	public static function create(ZMC_Registry_MessageBox $pm, $name, $notes = null, $ownerId = null)
	{
		try
		{
			$err = '';
			if (self::getId($name))
			{
				$pm->addError('This backup set name already exists.');
				return false;
			}
			elseif (strpos($name, 'zmc_test') === 0) 
			{
				$pm->addError('Backup set names may not begin with "zmc_test".');
				return false;
			}
			elseif (!ZMC_BackupSet::isValidName($pm, $name))
			{
				$pm->addError('Backup set names is not valid');
				return false;
			}
			ZMC_ProcOpen::procOpen('amserverconfig', ZMC::getAmandaCmd('amserverconfig'),
			  array($name), $stdout, $stderr, "Error status returned when creating configuration $name.");
			if (false === rename(ZMC::$registry->etc_amanda . $name . '/disklist', $dfn = ZMC::$registry->etc_amanda . $name . '/disklist.conf'))
				$pm->addError($err = "Unable to install 'amanda.conf' to \"$name\". " . ZMC::getFilePermHelp($dfn));
			else
				$pm->addError($err = self::installAmandaConf($pm, $name));

			if (!empty($err) || !self::add($pm, $name, $notes, (empty($ownerId) ? $_SESSION['user_id'] : $ownerId)))
			{
				self::rmAmanda($pm, $name);
				return false;
			}
			self::set($pm, $name);
			ZMC::auditLog($msg = 'ZMC user "' . $_SESSION['user'] . "\" created backup set '$name'");
			$pm->addMessage($msg, str_replace('. ', "\n", "$stdout\n$stderr"));
			return true;
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$pm->addError($e->getStderr() . "\n" . $e->getStdout());
			return false;
		}
	}

	
	public static function assertSelected(ZMC_Registry_MessageBox $pm) 
	{ 
		$name = self::select($pm, true);
		if (empty($name))
			$pm->addMessage('Choose a backup set to continue.');
		return $name;
	}

	public static function select(ZMC_Registry_MessageBox $pm, $requireBackupSet = false, $editId = null, $atLeastOne = true, $creatingNewSet = false)
	{
		$before = isset($_SESSION['configurationName']) ? $_SESSION['configurationName'] : 'not set';
		$name = self::selectWrapped($pm, $requireBackupSet, $editId, $atLeastOne);
		$pm->selected_name = self::set($pm, $name, $creatingNewSet);
		
		return $pm->selected_name;
	}

	public static function selectWrapped(ZMC_Registry_MessageBox $pm, $requireBackupSet = false, $editId = null, $atLeastOne = true)
	{
		ZMC_BackupSet::start($pm);
		$name = (isset($_SESSION['configurationName']) ? $_SESSION['configurationName'] : false);

		if (($name === false) && $requireBackupSet && isset($_SESSION['prior_name']))
			$name = $_SESSION['prior_name'];

		if (count(self::$myNames) === 0)
		{
			if ($pm->tombstone === 'Admin' && $pm->subnav === 'backup sets')
			{
				if ($pm->state === 'Add')
					return $_REQUEST[$editId];
				return false;
			}
			$name = false;
			if ($atLeastOne)
			{
				$pm->addError('Please create a backup set first.');
				return ZMC::redirectPage('ZMC_Admin_BackupSets', $pm); 
			}
		}
		elseif (!empty($_REQUEST['ConfigurationSwitcher'])) 
		{
			$name = $_REQUEST['ConfigurationSwitcher'];
			$_REQUEST['action'] = $_POST['action'] = '';
		}
		elseif (!empty($_REQUEST[$editId]))
			$name = $_REQUEST[$editId];
		elseif (!empty($_POST['selected_ids']) && !empty($_POST['action']) && ($_POST['action'] === 'Edit'))
		{
			reset($_POST['selected_ids']);
	        $name = key($_POST['selected_ids']); 
		}
		elseif (count(self::$myNames) === 1)
		{
			reset(self::$myNames);
			$name = self::set($pm, key(self::$myNames));
		}

		
		if (($pos = strpos($name, '%')) || ($pos = strpos($name, '|'))) 
			$name = substr($name, 0, $pos);

		return $name;
	}

	








	private static function set(ZMC_Registry_MessageBox $pm, $name = false, $creatingNewSet = false)
	{
		if (empty($name))
		{
			if (ZMC::$registry->dev_only) $pm->addDetail(__FILE__ . __LINE__ . ' empty name');
			return $_SESSION['configurationName'] = false;
		}

		if (!$creatingNewSet && !isset(self::$myNames[$name]))
		{
			$pm->addError("Unable to change working backup set to '$name'.\nPlease choose a different backup set.");
			return $_SESSION['configurationName'] = false;
		}

		if (!empty($_SESSION['configurationName']) && ($_SESSION['configurationName'] !== $name))
		{
			$_SESSION['prior_name'] = $_SESSION['configurationName'];
			$pm->addEscapedMessage($msg = 'Backup set changed to: <b>' . ZMC::escape($name) . '</b>');
		}

		return $_SESSION['configurationName'] = $name;
	}

	





	public static function get($id = null, $hidden = false)
	{
		$id = intval($id);
		if (empty($id))
			$id = self::getId();

		if (!empty(self::$sets[$id]))
			return self::$sets[$id];

		if ($hidden && !empty(self::$hiddenSets[$id]))
			return self::$hiddenSets[$id];

		return array();
	}

	public static function getByName($name)
	{
		return self::get(self::getId($name), true);
	}

	





	public static function getName($id = null, $hidden = false)
	{
		if ($id !== null)
		{
			$id = intval($id);
			if (!empty(self::$sets[$id]))
				return self::$sets[$id]['configuration_name'];
			if ($hidden && !empty(self::$hiddenSets[$id]))
				return self::$hiddenSets[$id]['configuration_name'];
			return false;
		}

		return empty($_SESSION['configurationName']) ? false : $_SESSION['configurationName'];
	}

	





	public static function getId($name = null)
	{
		if (empty($name))
			$name = self::getName();

		if (!empty($name) && isset(self::$names2ids[$name]))
			return self::$names2ids[$name];

		return 0;
	}

	



	public static function getMyNames()
	{
		return self::$myNames;
	}

	




	public static function getMySets()
	{
		$result = array();
		foreach(self::$myNames as $name => $id)
			$result[$name] = self::$sets[$id];
		return $result;
	}

	





	public static function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		foreach(self::$names2ids as $name => $id)
			self::updateLastDumpStatus($pm, $name);
		
		$cols = array(
			'configuration_name',
			'active',
			'backup_running' => null,
			'restore_running' => null,
			'last_amdump_result',
			'last_amdump_date',
			'schedule_type',
			'code',
			'dles_total',
			'dles_failed_amcheck',
			'dles_failed_license',
			'version',
			'status',
			'device',
			'creation_date',
			'migration_details'
		);
		$exclude = " configurations.configuration_name <> 'zmc_test_quick'";
		$where = " WHERE owner_id='" . $_SESSION['user_id'] . "' AND $exclude";
		if (ZMC_User::hasRole('Administrator'))
		{
			$cols[] = 'user';
			$where = ' LEFT JOIN users ON configurations.owner_id = users.user_id WHERE ' . $exclude;
		}
		$cols[] = 'configuration_notes';
		$all = ZMC_Mysql::getAllRowsMap("SELECT * FROM configurations $where");
		$sets = array();
		foreach($all as $set)
		{
			$name = $set['configuration_name'];
			$sets[$name] = $set;
			if (!empty(self::$allStatus[$name]))
				foreach(self::$allStatus[$name] as $key => $value)
					$sets[$name][$key] = $value;
		}


		$paginator = new ZMC_Paginator_Array($pm, $sets, $cols);
		$paginator->createColUrls($pm);
		$pm->rows = $paginator->get();
		
		$pm->goto = $paginator->footer($pm->url);
		$tmp = $pm->columns[0]; 
		$pm->columns[0] = $pm->columns[1];
		$pm->columns[1] = $tmp;
	}

	











	public static function isValidName($pm, $name)
	{
		if (empty($name))
			return false;
		
		if (filter_var($hostname, FILTER_VALIDATE_IP))
		{
			if ($pm) $pm->addWarnError("$name looks like an IP address. Please choose a different name.");
			return false;
		}

		if (!ctype_alnum($name[0]))
		{
			if ($pm) $pm->addWarnError("$name does not begin with a letter or digit.");
			return false;
		}

		if (strlen($name) < 5)
		{
			if ($pm) $pm->addWarnError("$name is too short (min: 5 characters).");
			return false;
		}
		if(isset($pm->binding[_key_name])){
			switch($pm->binding[_key_name]){
				case "google_cloud":
				case "hp_cloud":
				case "s3_cloud":
				case "cloudena_cloud":
				case "s3_compatible_cloud":
				case "iij_cloud":
				case "openstack_cloud":
					if (strlen($name) > 35)
					{
						$error_msg = "$name is too long (max: 35 characters).";
					}
					break;
				default:
					if (strlen($name) > 63)
					{
						$error_msg = "$name is too long (max: 63 characters).";
					}
					break;
			}
		}
		if(!empty($error_msg)){
			if ($pm) $pm->addWarnError($error_msg);
			return false;
		}

		if (strpos($name, '..'))
		{
			if ($pm) $pm->addWarnError("$name must not contain two consecutive periods (\"..\").");
			return false;
		}

		if (strpos($name, '-.'))
		{
			if ($pm) $pm->addWarnError("$name must not contain a hyphen followed by a period (\"-.\").");
			return false;
		}

		if (strpos($name, '.-'))
		{
			if ($pm) $pm->addWarnError("$name must not contain a period followed by a hyphen (\".-\").");
			return false;
		}

		if (!ctype_alnum(substr($name, -1)))
		{
			if ($pm) $pm->addWarnError("$name must end with a letter or digit.");
			return false;
		}

		$lname = strtolower($name);
		$reservedWords = array();
		if (ZMC::$registry->qa_mode)
		{
			if (strlen($name) < 5)
			{
				if ($pm) $pm->addWarnError("QA Mode: Please use a longer, more descriptive name. Hint: \"case12345\"");
				return false;
			}
			if (ctype_alpha($name))
			{
				if ($pm) $pm->addWarnError("QA Mode: Please use a more descriptive name by including at least one digit. Hint: \"Bug12345\"");
				return false;
			}
			$reservedWords = array('amanda', 'backup', 'binding', 'changer', 'cloud', 'cron', 'curinfo', 'data', 'default', 'device', 'disk', 'disklist', 'index', 'logs', 'restore', 'staging', 'tape', 'tape', 'tapelist', 'test', 'vtape', 'zmanda', 'zmc', 'none', 's3', 'openstack', 'open_stack', 'eucalyptus', 'swift', 'ubuntu', 'data');
		}
		$reservedWords[] = 'binding';
		foreach($reservedWords as $reserved)
			if (!strncmp($lname, $reserved, strlen($reserved)))
			{
				if ($pm) $pm->addWarnError("Please choose a different name.  Name '$name' begins with a reserved word: $reserved");
				return false;
			}

		foreach(array('zmanda', 'amanda', 'google', 'zmc') as $reserved)
			if (false !== strpos($lname, $reserved))
			{
				if ($pm) $pm->addWarnError("Please choose a different name.  Name '$name' contains a reserved word: $reserved");
				return false;
			}

		if (preg_match('/^[[:alnum:]_][-[:alnum:]._]*$/', $name))
			return true;

		if ($pm) $pm->addWarnError(self::BAD_NAME);
		return false;
	}

	






	public static function ownedBy($name, $userId = null)
	{
		if ($userId === null)
			$userId = $_SESSION['user_id'];

		if (ZMC_User::hasRole('Administrator', $userId))
			return true;

		$set = self::getByName($name, true);
		return $userId == $set['owner_id'];
	}

	



	public static function count()
	{
		return count(self::$myNames);
	}

	







	private static function add(ZMC_Registry_MessageBox $pm, $name, $notes, $ownerId = null, $status = array())
	{
		if (!self::isValidName($pm, $name))
		{
			$pm->addError("Can not add backup set '$name', because the name is not valid.");
			return false;
		}

		if (!isset($status['version']))
			$status['version'] = ZMC::$registry->zmc_backupset_version;
		if (!isset($status['code']))
			$status['code'] = 0;
		if (!isset($status['status']))
		{
			$status['status'] = 'OK';
			$status['profile_name'] = 'NONE';
		}

		$ownerId = intval($ownerId);
		if (empty($ownerId))
			$ownerId = $_SESSION['user_id'];

		$result = ZMC_Mysql::insert('configurations', array_merge(self::writeFilterStatus($status), array(
				'configuration_name' => ZMC_Mysql::escape($name),
				'configuration_notes' => $notes,
				'creation_date' => date('YmdHis', time()),
				'owner_id' => $ownerId,
			)),
			"Query failure while adding a configuration."
		);

		if ($result && mysql_affected_rows() > 0)
		{
			$id = mysql_insert_id();
			self::$myNames[$name] = $id;
			self::$names2ids[$name] = $id;
			self::$sets[$id] = ZMC_Mysql::getOneRow("SELECT * FROM configurations WHERE configuration_id=$id", 'DB failure');
			self::setStatus($name, $status);
			return $id;
		}

		return 0;
	}

	






	public static function duplicate(ZMC_Registry_MessageBox $pm, $oldName, $newName)
	{
		if (ZMC_BackupSet::isValidName($pm, $newName))
			if (self::getId($newName))
				$err = "The backup set '$newName' already exists.";
		
		if (!empty($err))
		{
			$pm->addError($err);
			return false;
		}

		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/Device-Binding/duplicate/$oldName",
				'data' => array(
					'commit_comment' => 'duplicate backup set config',
					'message_type' => 'backup set',
					'binding_name' => $newName,
				),
			));
			$pm->merge($result);
			$old = self::getByName($oldName);
			if (!self::add($pm, $newName, $old['configuration_notes']))
				return false;
			self::set($pm, $newName);
			ZMC::auditLog($msg = "Duplicated backup set '$oldName' to '$newName'.");
			$pm->addMessage($msg);
		}
		catch (Exception $e)
		{
			$pm->addError("An unexpected problem occurred while trying to duplicate the backup set '$oldName'. $e");
			ZMC::debugLog(__FILE__ . __LINE__ . " backup set duplicate exception: $e");
		}
	}

	






	public static function rm(ZMC_Registry_MessageBox $pm, $name, $purgeMedia = false, $purgeVaultMedia = false)
	{
		if (!self::ownedBy($name))
		{
			$pm->addError('Only the backup set owner, or user with the administrator role, can delete.');
			return false;
		}

		if (self::isActivated($name))
			self::activate($pm, false, $name);
		
		
		$savedDirectory = ZMC::$registry->etc_amanda . $name . DIRECTORY_SEPARATOR . 'jobs'
				. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved';
		foreach(glob($savedDirectory . DIRECTORY_SEPARATOR . 'Vault-saved_*.yml') as $filepath){
			$filename = basename($filepath);
			$timestamp = str_replace('.yml', '', str_replace('Vault-saved_', '', $filename));
			ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/crontab/sync/$name",
				'data' => array(
				'commit_comment' => "sync crontab",
				'message_type' => 'vault',
				'activate' => false,
				'cron' => 'vault-' . $timestamp,),
			));
		}

		if ($purgeMedia)
		{
			$set = self::getByName($name);
		   	if (!empty($set['profile_name']) && $set['profile_name'] !== 'NONE'){
				
				$pm->merge(ZMC_Yasumi::operation($pm, array('pathInfo' => "/Device-Binding/purge/$name", 'data' => array('binding_name' => $set['profile_name']))));
			}
		}
		
		if($purgeVaultMedia)
			self::purgeVaultMedia($pm, $name);
		
		self::rmAmanda($pm, $name);
		$id = self::getId($name);
		foreach(array('configurations', 'backuprun_dle_state', 'backuprun_dump_summary',  'backuprun_summary', 'backuprun_tape_usage') as $table)
			ZMC_Mysql::delete($table, array('configuration_id' => $id), null, true); 
		ZMC_Mysql::query("DROP TABLE IF EXISTS `index_details_$name`");
		unset(self::$myNames[$name]);
		unset(self::$names2ids[$name]);
		unset(self::$sets[$id]);
		unset(self::$hiddenSets[$id]);
		ZMC::auditLog($msg = "Deleted backup set: $name");
		$pm->addMessage($msg);
error_log(__FILE__ . __LINE__ . __FUNCTION__);
		unset($_SESSION['configurationName']);
		return true;
	}

	



	public static function rmAmanda(ZMC_Registry_MessageBox $pm, $name)
	{
		if (!is_dir($dir=ZMC::$registry->etc_amanda . $name))
			return true;
		if ($result = ZMC::rmrdir($dir))
			$pm->addError("Unable to delete backup set configuration directory: $dir\n$result");
		else
			return true;

		return false;
	}

	







	public static function update(ZMC_Registry_MessageBox $pm, $name, $notes, $ownerId = null)
	{
		$ownerId = intval($ownerId);
		if (ZMC::$registry->short_name === 'ZRM')
			ZRM_BackupSet::updateZrmComment($old, $notes);

		$updates = array('configuration_notes' => $notes);
		if (!empty($ownerId))
			$updates['owner_id'] = $ownerId;

		ZMC_Mysql::update('configurations', $updates, array('configuration_name' => $name), "Can not find and update backup set '$name'");
		ZMC::auditLog($msg = $_SESSION['user'] . " updated the backup set '$name'");
		$pm->addMessage($msg);
		return true;
	}

	








	public static function syncAmandaConfig(ZMC_Registry_MessageBox $pm, $skipName = null, $syncOnly = null)
	{
		$numerrs = $pm->isErrors();
		if (ZMC::$registry->debug)
		{
			if ($syncOnly)
				$pm->addMessage("Syncronizing Amanda and ZMC Backup Sets only for $syncOnly.");
			elseif ($skipName)
				$pm->addMessage("Syncronizing all Amanda and ZMC Backup Sets, except $skipName.");
			else
				$pm->addMessage("Syncronizing all Amanda and ZMC Backup Sets.");
		}
		self::start($pm, true); 
		$problems = self::getHealedConfigs($pm, $skipName, $syncOnly);
		$namesInDb = ZMC_Mysql::getAllOneValueMap('SELECT configuration_name, configuration_id FROM configurations', 'Unable to load configurations table');
		foreach ($pm->configsOnDisk as $name => &$status)
		{
			if (!empty($syncOnly) && $syncOnly !== $name)
				continue;

			if (!isset($namesInDb[$name])) 
			{
				if (!strncmp($name, 'zmc_test', 8) && $name !== 'zmc_test_quick')
					continue; 
				if ($result = ZMC::is_readwrite($configDir = ZMC::$registry->etc_amanda . $name))
				{
					$pm->addError("Unable to import backup set '$name'. $result");
					continue;
				}
				ZMC::auditLog($pm->addMessage("Imported configuration '$configDir/amanda.conf'."));
				if (self::add($pm, $name, "This backup set was imported from an existing Amanda configuration ($configDir). Initial status: $status[status] (code #$status[code])", 1, $status))
					$imported = true;
			}
		}

		if (!empty($imported))
			self::updateAmReports($pm);

		foreach (array_diff(array_keys($namesInDb), array_keys($pm->configsOnDisk)) as $name)
		{
			if (!empty($syncOnly) && $syncOnly !== $name)
				continue;

			if ($name === $skipName)
				continue;
			
			ZMC::debugLog("The ZMC backup set '$name' does not exist on this Amanda server.");
			
			

			self::updateStatus($name, array('code' => 401, 'status' => "Does not exist on disk at " . ZMC::$registry->etc_amanda . $name));
		}

		self::start($pm); 
		$cacheFn = 'syncCron';
		if (!ZMC::useCache($pm, null, $cacheFn, false, ZMC::$registry->proc_open_long_timeout) || (ZMC::$registry->ultra_turbo === false))
			self::syncCron($pm);
		return($problems || ($pm->isErrors() > $numerrs));
	}

	public static function updateAmReports(ZMC_Registry_MessageBox $pm)
	{
		$log = ZMC::$registry->cnf->zmc_log_dir . 'amreport_wrapper.log';
		try
		{
			ZMC_ProcOpen::procOpen('amreport_wrapper', $cmd = ZMC::getZmcTool('amreport_wrapper.sh'), $args = array('--all'), $stdout, $stderr, "Command '$cmd' failed unexpectedly");
			$pm->addEscapedMessage('Report update in progress. Several minutes may be required to complete, but you may continue to use ZMC. <a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=' . urlencode($log) . '">View progress in the log file (bottom of page).</a>');
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			if ($fp = fopen($log, 'a'))
			{
				fwrite($fp, "$e");
				fclose($fp);
			}
			$pm->addError("Unable to update ZMC reports.  See log: $log");
		}
	}

	public static function updateStatus($name, $status)
	{
		self::setStatus($name, $status);
		return ZMC_Mysql::update('configurations', self::writeFilterStatus($status),
			"configuration_name='" . ZMC_Mysql::escape($name) . "'",
			"Query failure while updating health status of ZMC backup sets");
	}

	private static function writeFilterStatus($status)
	{
		$status['status'] = str_ireplace('Yasumi', 'AGS', $status['status']);
		unset($status['name']);
		unset($status['profileFilename']);
		unset($status['id']);
		unset($status['bindingFilename']);
		unset($status['profileLinkFilename']);
		return $status;
	}

	





	public static function getHealedConfigs(ZMC_Registry_MessageBox $pm, $skipName = null, $syncOnly = null)
	{
		if (!($handle = opendir(ZMC::$registry->etc_amanda)))
			return $pm->addError('Unable to read "' . ZMC::$registry->etc_amanda . '".  Please check ownership and permissions.');

		$problems = 0;
		$pm->configsOnDisk = array();
		foreach(self::listConfigs($skipName) as $name)
		{
			if (!empty($syncOnly) && ($syncOnly !== $name))
				continue;
			if (!self::ownedBy($name))
				continue; 
			$pm->configsOnDisk[$name] = self::getStatus($pm, $name); 
			if (!empty($pm->configsOnDisk[$name]['code']))
				$problems++;
		}
		ksort($pm->configsOnDisk);
		return $problems;
	}

	public static function listConfigs($skipName = null)
	{
		$names = array();
		$cwd = getcwd();
		chdir(ZMC::$registry->etc_amanda);
		foreach(glob('[-._a-zA-Z0-9]*', GLOB_ONLYDIR) as $name)
		{
			if ($name[0] === '.' || $name === 'template.d' || !strncmp($name, 'zmc_test', 8))
				continue;

			if (($name !== $skipName))
				$names[] = $name;
		}
		chdir($cwd);
		return $names;
	}

	public static function setStatus($name, $status)
	{
		if ($status['code'] == 401) 
			return false;
		
		$fn = self::getStatusFn($name);
		if (false === is_readable(dirname($fn)) || false === is_readable($fn))
			return false; 

		if (!isset(self::$names2ids[$name]))
			
			
			
				return false;

		$status['id'] = self::$names2ids[$name];
		if (false === file_put_contents($fn, json_encode($status), LOCK_EX))
		{
			
			throw new ZMC_Exception("Unable to record backup set status to '$fn'. " . ZMC::getFilePermHelp($fn));
			return false;
		}
		$pstatus = "\$zmc_status = " . ZMC::php2perl($status);
		file_put_contents("$fn.pl", $pstatus, LOCK_EX);
		if (!empty(self::$hiddenSets[$status['id']]))
			ZMC::merge(self::$hiddenSets[$status['id']], $status);
		if (!empty(self::$sets[$status['id']]))
			ZMC::merge(self::$sets[$status['id']], $status);

		return true;
	}

	protected static function getStatusFn($name)
	{
		return ZMC::$registry->etc_amanda . $name . '/zmc_status';
	}

	






	public static function getStatus(ZMC_Registry_MessageBox $pm, $name, $regen = true)
	{
		try
		{
			self::start($pm);
			$status = self::getStatusOnly($pm, $name, $regen);
			
			$filenamePattern = ZMC::$registry->etc_amanda . $pm->selected_name . DIRECTORY_SEPARATOR . 'jobs'
					. DIRECTORY_SEPARATOR . 'vault' . DIRECTORY_SEPARATOR . 'saved' . DIRECTORY_SEPARATOR . 'Vault-changer_*.conf';
			$vaultDevices = array();
			foreach(glob($filenamePattern) as $filename)
				$vaultDevices[] = str_replace("Vault-changer_", '', str_replace(".conf", '', basename($filename)));
			$devices = '';
			if(!empty($status['profile_name']))
				$devices = $status['profile_name'];
			if(!empty($vaultDevices))
				$devices .= ", " . implode(", ", $vaultDevices);

			if($status['device'] !== $devices)
				$status['device'] = $devices;
			
			self::updateStatus($name, $status);
		}
		catch(Exception $e)
		{
			$pm->addError("A problem occurred while checking the status of backup set '$name'.");
			$pm->addDetail("$e");
		}
		return $status;
	}

	





	public static function getStatusOnly(ZMC_Registry_MessageBox $pm, $name, $regen = true)
	{
		$status = array('configuration_name' => $name);
		$statusFn = self::getStatusFn($name);
		$configDir = dirname($statusFn);
		if (!is_readable($configDir))
		{
			$status['code'] = 101;
			$status['version'] = 'unknown';
			$status['status'] = ZMC::getDirPermHelp($configDir);
			return $status;
		}

		if (!file_exists($statusFn))
		{
			if (file_exists(dirname($statusFn) . '/advanced.conf'))
			{
				$status['code'] = 201;
				$status['version'] = '2.6.4';
				
				$status['status'] = 'Unsupported Version.';
				if ($result = ZMC::is_readwrite($configDir))
					$status['status'] .= ' ' . $result;
				return $status;
			}
			if (!isset($_REQUEST['action']) || strtolower($_REQUEST['action']) !== 'migrate')
				file_put_contents($statusFn, '{"version":"' . ZMC::$registry->zmc_backupset_version . '","code":0,"status":"unknown"}', LOCK_EX); 
		}

		if (is_readable($statusFn) && (false !== ($json = file_get_contents($statusFn))))
			$decodedStatus = json_decode($json, true);

		if (empty($decodedStatus))
		{
			$status['code'] = 105;
			$status['version'] = 'unknown';
			$status['status'] = "Can not read: /etc/amanda/$name/zmc_status.  Please check ownership/permissions.";
			return $status;
		}

		if (is_array($decodedStatus))
			$status = array_merge($decodedStatus, $status);
		else
		{
			$status['code'] = 5011;
			$status['status'] = "corrupt status file: /etc/amanda/$name/zmc_status";
			return $status;
		}

		if (!array_key_exists('code', $status))
		{
			$status['code'] = 5012;
			$status['status'] = "corrupt status file: /etc/amanda/$name/zmc_status (missing 'code')";
			return $status;
		}

		if (!array_key_exists('status', $status))
		{
			$status['code'] = 5013;
			$status['status'] = "corrupt status file: /etc/amanda/$name/zmc_status (missing 'status')";
			return $status;
		}

		if (!array_key_exists('version', $status))
		{
			$status['code'] = 5014;
			$status['version'] = 'unknown';
			$status['status'] = "corrupt status file: /etc/amanda/$name/zmc_status (missing 'version')";
			return $status;
		}

		if ($status['version'] === 'unknown')
			return $status;

		if ($status['version'] != ZMC::$registry->zmc_backupset_version)
		{
			if ($status['code'] == 201)
				return $status;
			
			$status['status'] = 'Unsupported Version.  Version needed: ' . ZMC::$registry->zmc_backupset_version;
			
			$status['code'] = 203;
			return $status;
		}

		if ($result = ZMC::is_readwrite($configDir))
		{
			$status['code'] = 103;
			if (empty($status['version']))
				$status['version'] = 'unknown';
			$status['status'] = $result;
			return $status;
		}

		$status['code'] = 0;
		$status['status'] = '';
		$path = ZMC::$registry->etc_amanda . $name;
		if (!file_exists($disklist = "$path/disklist.conf"))
		{
			$status['status'] .= " Missing file '$disklist'.  Empty file created. ";
			file_put_contents($disklist, ''); 
		}

		self::mkDirs($pm, $path); 
		foreach(glob("$path/binding-*.yml", GLOB_NOSORT) as $filename) 
		{
			$bstatus = array( 
				'code' => 0, 
				'bindingFilename' => $filename,
				'status' => '',
			);
			$msg = '';
			list($ignored, $bstatus['profile_name']) = explode('-', $bindingName = substr(basename($bstatus['bindingFilename']), 0, -4));
			if (empty($bstatus['profile_name']))
			{
				ZMC::debugLog("Skipping corrupt binding filename: $bstatus[bindingFilename]");
				continue;
			}

			$profileLinkFilename = $path . '/' . $bstatus['profile_name'] . '.profile';
			$profileFilename = ZMC::$registry->device_profiles . $bstatus['profile_name'] . '.yml';
			$bstatus['profileFilename'] = $profileFilename;
			$bstatus['profileLinkFilename'] = $profileLinkFilename;
			if (empty($bindingStatus) || $bstatus['code'] == 0) 
				$bindingStatus = $bstatus;
		}

		if (!empty($bindingStatus))
		{
			foreach(array('code', 'status') as $key)
				if (!empty($status[$key]))
					$bindingStatus[$key] = implode('; ', $status[$key], $bindingStatus[$key]);
			$status = array_merge($status, $bindingStatus);
		}

		if ($regen)
		{
			$pm->binding_conf = null;
			$result = self::regenerateBindings($pm, $name, $status);
			$status['schedule_type'] = '';
			if (!empty($pm->binding_conf) && !empty($pm->binding_conf['schedule'])) 
				$status['schedule_type'] = $pm->binding_conf['schedule']['schedule_type'];

			if (is_string($result))
			{
				$status['code'] = 507;
				$status['status'] .= " Updating device settings failed: $result";
			}
		}

		if ($status['code'] == 0) 
		{
			if (!empty($status['profileFilename']) && file_exists($status['profileFilename']))
			{
				$status['status'] .= 'OK';
				$status['profile_name'] = ZMC::escape($status['profile_name']);
			}
			else 
			{
				$status['status'] .= 'OK';
				$status['profile_name'] = 'NONE';
			}
		}

		return $status;
	}

	private static function regenerateBindings(ZMC_Registry_MessageBox $pm, $name, array &$status)
	{
		if (!strncmp($name, 'zmc_test', 8))
			return false;

		if (!is_array($status) || !isset($status['code'])) 
			$pm->addDetail(__FUNCTION__ . "($name) called with bad \$status: " . print_r($status, true));

		
		if (($status['code'] != 0) && (substr($status['code'], 0, 1) != '5'))
		{
			$pm->addMessage("Skipped regeneration of '$name', because backup set is not healthy (code #$status[code])");
			return false;
		}

		if (empty($status['version']) || $status['version'] != ZMC::$registry->zmc_backupset_version)
		{
			$pm->addMessage("Skipped regeneration of '$name', because backup set is not from AEE version: " . ZMC::$registry->zmc_backupset_version);
			return false;
		}

		if (empty($status['profile_name']) || $status['profile_name'] === 'NONE')
		{
			$pm->addDetail($msg = "'$name': skipped regeneration, because it has no device settings.");
			
			return false;
		}

		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/Device-Binding/regenerate/$name",
				'data' => array(
					'commit_comment' => 'regenerating+bindings',
					'message_type' => 'regeneration',
					'binding_name' => $status['profile_name'],
				),
			), false);
			
			$pm->merge($result); 
			









			self::licenseCheck($pm, $status, $name);
			if (isset($pm->subnav) && ($pm->subnav !== 'when') && !empty($status['schedule_type'])
				&&	(self::isActivated($name) || $pm->subnav === 'now' || $pm->subnav === 'backup sets'))
					$pm->merge($result['binding_conf']['schedule']['status']);

			return true;
		}
		catch (Exception $e)
		{
			if (!empty($_SESSION['configurationName']) && $_SESSION['configurationName'] === $name) 
				$pm->addError("Updating device settings for backup set '$name' failed: " . $e->getMessage());

			return $e->getMessage();
		}
	}

	





	public static function installAmandaConf(ZMC_Registry_MessageBox $pm, $name)
	{
		$orig = $zmc_aee = ZMC::$registry->etc_zmanda_product;
		if (!self::mkDirs($pm, $dest = ZMC::$registry->etc_amanda . $name))
			return "Unable to create files \"$dest\". " . ZMC::getDirPermHelp($dest);

		@unlink("$dest/advanced.conf");
		if (!copy("$orig/amanda.conf", $destFn = "$dest/amanda.conf"))
			return "Unable to install 'amanda.conf' to \"$destFn\". " . ZMC::getFilePermHelp($destFn);

		touch($dest . '/zmc_binding.conf');
		$types = '/zmc_backupset_dumptypes';
		$destTypes = $dest . $types;
		if (!copy("$orig$types", $destTypes)) 
			if (!copy("$zmc_aee$types", $destTypes)) 
				return "Unable to install files \"$destTypes\". " . ZMC::getFilePermHelp($destTypes);

		return !ZMC_BackupSet::modifyConf($pm, $name, array(
			'infofile' => "$dest/curinfo",
			'logdir' => "$dest/logs",
			'indexdir' => "$dest/index"));
	}

	public static function isActivated($name = null)
	{
		
		return (($set = self::getByName($name)) ? !empty($set['active']) : false);
	}

	






	public static function activate(ZMC_Registry_MessageBox $pm, $on = true, $name = null)
	{
		if ($name === null)
			$name = $_SESSION['configurationName'];

		if (!self::ownedBy($name))
		{
			$pm->addError('Only the backup set owner, or user with the administrator role, can activate or deactivate a backup set.');
			return false;
		}
		$set = self::getByName($name);
		$err = null;
		$active = self::isActivated($name);
		if (!empty($set['dles_failed_license']))
			$err = 'License limits exceeded. Can not activate.';
		elseif (empty($set['schedule_type']))
			$err = 'No storage device or schedule configured. Can not activate.';
		elseif ($on && $active)
			$err = 'Already activated. No change.';
		elseif (!$on && !$active)
			$err = 'Already deactivated. Backup set was not active.';
		if (!empty($err))
		{
			ZMC::auditLog($msg = "$name: $err");
			$pm->addError($msg);
			return false;
		}

		ZMC::auditLog($on ? 'Activated' : 'Deactivated' . " backup set '$name'" . ($pm->isErrors() ? ' with errors: ' . $pm->getAllMerged() : ''));
		self::syncCron($pm, $name, $set, $on);
		if (file_exists($fn = ZMC::$registry->tmp_path . self::CRONTAB_STATUS))
			unlink($fn);
		if (!empty($pm->fatal))
			$pm->addError("Updating live schedule failed.");
		else
		{
			self::$sets[self::$names2ids[$name]]['active'] = $on;
			$pm->addMessage("\"$name\" schedule " . ($on ? 'activated.' : 'deactivated.'));
			return true;
		}

		return false;
	}

	private static function syncCron(ZMC_Registry_MessageBox $pm, $name = '', $set = null, $on = null)
	{
		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/crontab/sync/$name",
				'data' => array(
					'commit_comment' => "sync crontab",
					'message_type' => 'backup sets',
					'activate' => $on,
					'cron' => 'binding-' . $set['profile_name'],
				),
			));
		}
		catch(Exception $e)
		{
		}
		$pm->merge($result);
	}

	public static function displayName($name)
	{
		$result = '';
		$len = strlen($name);
		for($i=0; $i < $len; $i++)
		{
			switch($name[$i])
			{
				case '/':
				case '.':
				case '_':
				case '~':
				case '-':
					$result .= '@' . $name[$i] . '@';
					continue 2;
			}
			$result .= $name[$i];
		}
		return str_replace('@', '<wbr/>', ZMC::escape($result));
	}

	public static function modifyConf(ZMC_Registry_MessageBox $pm, $name, $conf, $what = 'amanda.conf')
	{
		return self::readConf($pm, $name, $conf, 'merge_write', $what);
	}

	public static function writeConf(ZMC_Registry_MessageBox $pm, $name, $conf, $what = 'amanda.conf')
	{
		return self::readConf($pm, $name, $conf, 'write', $what);
	}

	public static function readConf(ZMC_Registry_MessageBox $pm, $name = null, $conf = null, $op = 'read', $what = 'amanda.conf')
	{
		if (empty($name))
			$name = $_SESSION['configurationName'];

		if (!empty($conf))
		{
			ZMC::rmFormMetaData($conf);
			$conf['amrecover_do_fsf'] = null; 
		}
		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/conf/$op/$name",
				'data' => array(
					'what' => $what,
					'conf' => $conf,
					'commit_comment' => __CLASS__ . '::' . __FUNCTION__ . "($op)",
				),
			));
			$pm->merge($result);
			if (isset($pm->conf) && !isset($pm->conf['maxdumps'])) 
				$pm->conf['maxdumps'] = 1;
			return true;
		}
		catch(Exception $e)
		{
			$pm->addError("An unexpected problem occurred while trying to $op the configuration of the backup set '$name'. $e");
			return false;
		}
	}

	public static function addEditWarning(ZMC_Registry_MessageBox $pm, $checkAll = false)
	{
		if (	( $checkAll && (count(glob(ZMC::$registry->etc_amanda . '*/logs/log')) + count(glob(ZMC::$registry->etc_amanda . '*/logs/amdump'))))
			||	(!$checkAll && (file_exists($prefix =ZMC::$registry->etc_amanda . self::getName() . '/logs/log') || file_exists($prefix . '/logs/amdump'))))
			$pm->addWarning('A backup or restore operation is in progress. Some kinds of edits may cause the operation to fail.');
	}

	public static function licenseCheck(ZMC_Registry_MessageBox $pm, &$status, $name = null)
	{
		if (!isset($pm->lstats)) 
			return false;
		if ($name === null)
			$name = self::getName();
		$lstats =& $pm->lstats;
		if (!empty($lstats['fatal'])) 
			return;
		static $once = false;
		if (!$once)
		{
			
			$once = true;
			ZMC_Type_What::addExpireWarnings($pm);
		}
		if (!empty($lstats['zmc_typeconf_histograms']) && !empty($lstats['zmc_typeconf_histograms'][$name]))
			$status['dles_total'] = array_sum(array_map('array_sum', $lstats['zmc_typeconf_histograms'][$name]));
		if (isset($lstats['zmc_amcheck_histograms'][$name]))
			$status['dles_failed_amcheck'] = $lstats['zmc_amcheck_histograms'][$name];
		else
			$status['dles_failed_amcheck']= 0;
		if (empty($lstats['dles_over_limit']) || empty($lstats['dles_over_limit'][$name]))
			$status['dles_failed_license'] = '0';
		elseif ($status['dles_failed_license'] = array_sum(array_map('array_sum', $lstats['dles_over_limit'][$name])))
		{
			$list = '';
			foreach($lstats['dles_over_limit'][$name] as $type => $ids)
				$list .= "\n * backup type $type: " . implode(', ', array_keys($ids));
			$pm->addEscapedError("Some of the DLEs in the backup set '$name' are not covered by a valid, unexpired license.  Please visit the Zmanda Network store, or delete DLEs exceeding the allowed limits determined from the installed license.  See the "
				. ZMC::getPageUrl($pm, 'Admin', 'licenses')
				. ' for license details.<br />Please see the '
				. ZMC::getPageUrl($pm, 'Backup', 'what')
				. ' for details about the affected DLEs: ' . ZMC::escape($list));
			return false;
		}
		return true;
	}

	public static function getNamesUsing($device, $links = false)
	{
		if (empty($device)) ZMC::quit(__FUNCTION__ . '() - missing device');
		$configs = array();
		self::getNamesUsingHelper(self::$hiddenSets, $configs, $device, $links);
		self::getNamesUsingHelper(self::$sets, $configs, $device, $links);
		return $configs;
	}

	private static function getNamesUsingHelper($sets, &$configs, $device, $links)
	{
		foreach($sets as $set)
			if (ZMC_User::hasRole('Administrator') || ($set['owner_id'] == $_SESSION['user_id']) || ZMC_User::hasRole('RestoreOnly')){
				$devices = explode(', ', $set['device']);
				if (in_array($device, $devices))
					if ($links)
						$configs[] = '<a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'backup sets') . '?edit_id=' . urlencode($set['configuration_name']) . '&amp;action=Edit">' . self::displayName($set['configuration_name']) . '</a>';
					else
						$configs[] = $set['configuration_name'];
			}
	}

	public static function abort(ZMC_Registry_MessageBox $pm, $name)
	{
		try
		{
			ZMC::auditLog($msg = $_SESSION['user'] . " aborted backup/restore/vault run for backup set '$name'.");
			ZMC_ProcOpen::procOpen('amcleanup', ZMC::getAmandaCmd('amcleanup'),
				  array('-k', '-r', $name), $stdout, $stderr, "Error status returned when aborting: $name.");
			$pm->addMessage($msg);
			$pm->addDetail(str_replace('. ', "\n", "$stdout\n$stderr"));
			foreach(array(ZMC::$registry->etc_amanda . $name . '/amdump', ZMC::$registry->etc_amanda . $name . '/log') as $fn)
				if (file_exists($fn))
					unlink($fn);

			self::$sets[self::getId($name)]['backup_running'] = false;
			return true;
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$pm->addError($e->getStderr() . "\n" . $e->getStdout());
		}
		return false;
	}

	public static function putTapeList(ZMC_Registry_MessageBox $pm, $tapelist, $name = null, $merge = false)
	{
		if (empty($name))
			$name = self::getName();
		if (empty($name))
			return $pm->addBomb(__FUNCTION__ . '(): missing backup set name');

		$filename = ZMC::$registry->etc_amanda . $name . '/tapelist';
		$check_duplicate_label = array();
		if (file_exists($filename)){
			$old_content = file($filename, FILE_IGNORE_NEW_LINES);
			if (!empty($old_content)){
				foreach($old_content as $existing_label){
					if ($pos = strpos($existing_label, '#'))
						list($existing_label, $comment) = explode('#', $existing_label);
					$time = strtok(trim($existing_label), " \t");
					$label = strtok(" \t");
					if(isset($check_duplicate_label[$label]))
						$check_duplicate_label[$label] += 1;
					elseif(!isset($check_duplicate_label[$label]))
						$check_duplicate_label[$label] = 1;
				}
			}
		}
		$content = '';
		$already_considered_label = array();
		foreach($tapelist as $tape)
		{
			if(isset($check_duplicate_label[$tape['label']]) && $check_duplicate_label[$tape['label']] > 1)
				if(isset($already_considered_label[$tape['label']]))
					if($tape['timestring']==0)
						continue;
				else
					$already_considered_label[$tape['label']] = true;

			$content .= $tape['timestring'] . ' ' . $tape['label'] . ' ' . $tape['reuse'];
			foreach(array('barcode', 'meta', 'blocksize') as $optional)
				if (!empty($tape[$optional]))
					$content .= ' ' . strtoupper($optional) . ':' . $tape[$optional];

			if (!empty($tape['comment']))
				$content .= ' #' . $tape['comment'];
			$content .= "\n";
		}

		

		if (!$merge)
		{
			if (!isset(self::$tapeListStat[$name])) 
				$pm->addInternal("Unable to perform the requested update(s).  Missing tapelist information.  Please try again.");
			elseif ($stat['mtime'] > self::$tapeListStat[$name]['mtime'])
				$pm->addError("Unable to perform the requested update(s).  The tapelist was modified by another process.  Please try again.");
		}
		$cacheFn = "$name.tapelist";
		$stats = self::modifyTapelist($pm, $filename, $cacheFn, $content, $merge);
		if (!$merge)
			self::$tapeListStat[$name] =& $stats;
	}

	public static function getTapeList(ZMC_Registry_MessageBox $pm, &$results, $name = null, $dumpcycle = 0)
	{
		$results['dumpcycle_start_time'] = date('YmdHis', time() - $dumpcycle * 24 * 60 * 60); 
		$results['dumpcycle_tapes_used'] = 0;
		$results['tapelist'] = array();
		$results['archived_media'] = 0;
		$results['total_tapes_seen'] = 0;
		if (empty($name))
			$name = self::getName();
		if (empty($name))
			return $pm->addBomb(__FUNCTION__ . '(): missing backup set name');

		if (!file_exists($filename = ZMC::$registry->etc_amanda . $name . '/tapelist'))
			return;
		if ($result = ZMC::is_readwrite($filename, false))
			return $pm->addError("Unable to access list of media used. $result");
		
		$cacheFn = "$name.tapelist";
		if (ZMC::useCache($pm, array($filename, __FILE__), $cacheFn, true))
		{
			$lines = file($cacheFn, FILE_IGNORE_NEW_LINES);
			$stat = stat($cacheFn);
		}
		else
			$stat = self::modifyTapelist($pm, $filename, $cacheFn, $lines);

		if ($lines === false)
			return $pm->addInternal("Unable to read the list of used media.");

		self::$tapeListStat[$name] =& $stat;
		if (empty($lines))
			return;

		$i = 1;
		foreach($lines as $line)
		{
			if ($line[0] === '#')
				continue;
			$comment = '';
			$results['total_tapes_seen']++;
			if ($pos = strpos($line, '#'))
				list($line, $comment) = explode('#', $line);
			$time = strtok(trim($line), " \t");
			$label = strtok(" \t");
			$reuse = strtok(" \t");
			if(isset($results['tapelist'][$label])){
				$pm->addWarning("Duplicate label '$label' found.");
				if(isset($results['tapelist'][$label]['timestring']) && ($results['tapelist'][$label]['timestring']!= 0 && $time == 0)){
					continue;
				}
			}
			$results['tapelist'][$label] = array('timestring' => $time, 'label' => $label, 'reuse' => $reuse, 'comment' => $comment, '_line' => $i++);
			while ($field = strtok(" \t"))
			{
				list($key, $value) = explode(':', $field);
				$key = strtolower($key);
				switch($key)
				{
					case 'blocksize':
					case 'barcode':
					case 'meta':
						$results['tapelist'][$label][$key] = $value;
						break;
					default:
						$pm->addInternal("Unrecognized field in tapelist $filename:  $key");
				}
			}
			if ($reuse[0] === 'n' || $reuse[0] === 'N') 
				$results['archived_media']++;
			elseif ($time >= $results['dumpcycle_start_time'])
				$results['dumpcycle_tapes_used']++;
		}

		$labels = ZMC_Mysql::getAllRowsMap('SELECT * FROM backuprun_tapes WHERE configuration_id=' . self::getId($name), null, true, null, 'label');
		foreach($results['tapelist'] as $label => &$tape)
			if (($tape['timestring'] > 0) && !empty($labels[$label]))
				ZMC::merge($tape, $labels[$label]);
	}

	private static function modifyTapelist($pm, $filename, $cacheFn, &$lines, $merge = false)
	{
		if (false === ($fp = fopen("$filename.lock", "w+")))
			return $pm->addInternal("Unable to safely access the media list. Please try again.");
		for($i=5; $i; $i--)
			if (false === flock($fp, LOCK_EX | LOCK_NB, $wouldBlock))
				if ($wouldBlock)
					sleep(1);
				else
					return $pm->addError("Unable to access list of media used. Please try again in a few minutes.");

		$stat = stat($filename);
		if (empty($lines))
		{
			$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$fpc = fopen($cacheFn, 'w');
			if ($fpc === false)
				return $pm->addInternal("Unable to write to $cacheFn " . ZMC::getFilePermHelp($cacheFn));
			foreach($lines as $line)
				fputs($fpc, "$line\n");
			fclose($fpc);
		}
		else
		{
			$fpt = fopen($filename, $merge ? 'a' : 'w');
			if ($fpt === false)
				return $pm->addInternal(ZMC::getFilePermHelp($filename));
			fputs($fpt, $lines);
			fclose($fpt);
		}
		sleep(1); 
		flock($fp, LOCK_UN);
		fclose($fp);
		return $stat;
	}

	public static function getStatusIconHtml($name, $encName = null, $active ='')
	{
		$set = self::getByName($name);
		if (empty($set['schedule_type']))
			return '<img style="vertical-align:middle;" title="No backup schedule configured" src="/images/icons/power-disabled.png" height="18" width="20" onclick="noBubble(event); window.location.href=\'' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'when') . '\'" />';

		if (empty($encName))
		{
			$vation = (empty($set['active']) ? 'Inactive' : 'Active');
			$highlight = '';
		}
		else
		{
			$vation = (empty($set['active']) ? 'Activate+Now' : 'Deactivate+Now');
			$highlight = 'class="zmcHoverHighlight"';
		}

		$img = "<img style=\"vertical-align:middle;\" title='$vation' $highlight src='/images/icons/power-"
			. (empty($set['active']) ? 'off' : 'on')
			. ".png' height='18' width='20' "
			. ' onclick="noBubble(event); window.location.href=\''
			. ZMC_HeaderFooter::$instance->getUrl('Backup', 'now')
			. '\'" />';


		if(preg_match('/ZMC_Monitor/', $_SESSION['CURRENT_PAGE'])){
			if($active)
				$img .= '<img style="vertical-align:middle;" title="Backup Running" src="/images/icons/icon_calendar_progress.gif" height="18" width="20" />';
		}elseif (!empty($set['backup_running'])){
				$img .= '<img style="vertical-align:middle;" title="Backup Running" src="/images/icons/icon_calendar_progress.gif" height="18" width="20" />';
		}
		if (!empty($set['restore_running']))
			$img .= '<img style="vertical-align:middle;" title="Restore Running" src="/images/icons/icon_restore.gif" height="16" width="16" />';
		
		
		if (empty($encName))
			return $img;

		return '<a onclick="" href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'now')
			. "?action=$vation&edit_id=$name\">$img</a>\n";
	}

	public static function hasBackups(ZMC_Registry_MessageBox $pm, $name)
	{
		if (count($dirContents = scandir($dirName = ZMC::$registry->etc_amanda . $name . '/index')) > 2)
			return true;

		$pm->addMessage("This backup set, \"$name\", has no backups.");
		$pm->addDetail("\"$dirName\" is empty.  Thus, no Amanda indexes exist for this backup set.  If the backup media still exists, and has not been overwritten, a manual restore might be possible.");
		return false;
	}

	private static function mkDirs($pm, $path) 
	{
		if (!is_dir("$path/logs"))
		{
			$err = ZMC::mkdirIfNotExists("$path/logs");
			if (is_string($err))
			{
				$pm->addWarnError($err);
				return !ZMC::$registry->safe_mode;
			}
			file_put_contents("$path/logs/README.txt", 'Do not delete these log files.
They are required for correct operation of AEE.
The subdirectory "oldlogs" contains files that might be deleted,
but these files are useful to recreate old ZMC reports,
if ZMC is re-installed.');
		}
		$err = ZMC::mkdirIfNotExists("$path/index");
		if (is_string($err))
		{
			$pm->addWarnError($err);
			return !ZMC::$registry->safe_mode;
		}
		$err = ZMC::mkdirIfNotExists("$path/curinfo");
		if (is_string($err))
		{
			$pm->addWarnError($err);
			return !ZMC::$registry->safe_mode;
		}
		return true;
	}

	public static function startBackupNow($pm, $config, $dles_list, $how = '', $prune = true)
	{
		if (!is_readable($dir = ZMC::$registry->etc_amanda . $config))
		{
			$pm->addError("Unable to read configuration information for $config ($dir).");
			return self::ABORTED;
		}
		$status = self::getStatusOnly($pm, $config, true); 
		if (!empty($status['code']))
		{
			$pm->addError("$config has problems.  Backup aborted.");
			return self::ABORTED;
		}
		
		
		
		
		
		if (true !== ZMC_BackupSet::licenseCheck($pm, $status))
			return self::ABORTED; 

		if($pm->isErrors())
			return self::ABORTED;
	
		if ($prune)
			ZMC_BackupSet::pruneAllExpired($pm, $pm->binding_conf);

		if (self::getBackupLogFn($pm, $config))
		{
			
			$pm->addError("Can not start backup.  Another backup is already running.");
			return self::ABORTED;

		}

		self::getTapeList($pm, $results, $config);
		if (empty($results['tapelist']))
			if (!isset($pm->binding_conf['autolabel']))
			{
				$pm->addError("No labeled media found.  Please label some media, before starting a backup.");
				return self::ABORTED;
			}
			elseif ($pm->binding_conf['autolabel'] === 'off')
			{
				$pm->addError("No labeled media found.  Before starting a backup, please label some media, or enable auto-labelling of media.");
				return self::ABORTED;
			}

		try
		{
			$logs = ZMC::$registry->etc_amanda . "$config/logs/";
			$amdumpFn = "$logs.zmc.amdump";
			if (!ZMC_User::hasRole('Administrator') && !isset(self::$myNames[$config]))
			{
				$pm->addError("Insufficient ZMC privileges. Backup not started.");
				return self::ABORTED;
			}

			if (self::getBackupLogFn($pm, $config)) 
			{
				$pm->addError("Can not start backup.  Another backup is already running.");
				return self::ABORTED;
				
			}

			foreach(array("$amdumpFn.pid", "$amdumpFn", "$amdumpFn.result") as $fn)
				if (file_exists($fn))
					unlink($fn);

			$amdumpLogs = array();
			$backupLogFn = ZMC::$registry->cnf->zmc_log_dir . "zmc_backup_monitor.log";
			
			
			self::getNewAmdumpLogs($pm, $config, $amdumpLogs);
			file_put_contents("$amdumpFn.loglist", serialize($amdumpLogs)); 
			$backupLevel = '';
			if ($how === 'incremental')
				$backupLevel = '-o DUMPTYPE:zmc_global_base:strategy=incronly';
			elseif ($how === 'full')
				$backupLevel = '-o DUMPTYPE:zmc_global_base:strategy=noinc';
			elseif(preg_match('/incronly|noinc/', $how) || ($how && $how != "incremental" && $how != "full" && $how != "smart"))
				$backupLevel = $how;
			
			$selectedDLEs = '';
			if(!empty($dles_list))
				foreach($dles_list as $host => $disks)
					if(!empty($host)){
						$selectedDLEs .= "^" . str_replace(" ", "\ ", $host) . "$ ";
						foreach($disks as $disk)
							if(!empty($disk))
								$selectedDLEs .=  "^" . str_replace(" ", "\ ", str_replace("\\", "\\\\", preg_quote($disk))) . "$ ";
					}

			ZMC::execv('bash',
				'('
					. ZMC::getAmandaCmd('amdump') . " $config $selectedDLEs $backupLevel >> $amdumpFn 2>&1;
					echo \$? > $amdumpFn.result;
					date >> $amdumpFn.result;
					rm $amdumpFn.pid) >>$backupLogFn 2>>$backupLogFn &
				echo \$! > $amdumpFn.pid 2>&1
			");
			$backupMonitorPidFn = '/var/run/zmc/backup_monitor.pid';
			if (file_exists($zmcParserPidFn = '/var/run/zmc/zmc_parser.pid'))
				$pid = intval(trim(file_get_contents($zmcParserPidFn)));
			if (!empty($pid) && !file_exists("/proc/$pid"))
			{
				ZMC::errorLog("Cleaned up a dead zmc_parser pidfile: $zmcParserPidFn");
				unlink($zmcParserPidFn);
			}
			$monitoring = false;
			$wait = 60; 
			$pid = 0;
			for($sleep = 1; $sleep <= $wait; $sleep++)
			{
				usleep(250000); 
				if (file_exists($backupMonitorPidFn))
					$pid = intval(trim(file_get_contents($backupMonitorPidFn)));
				if (!empty($pid) && !file_exists("/proc/$pid"))
				{
					ZMC::errorLog("Cleaned up a dead backup_monitor pidfile: $backupMonitorPidFn");
					unlink($backupMonitorPidFn);
				}

				if (!$monitoring && self::getBackupLogFn($pm, $config))
				{
					$monitoring = true;
					if (!empty($pid) && file_exists("/proc/$pid"))
					{
						ZMC::debugLog(__FILE__ . __LINE__ . " wakeup backup_monitor: kill -HUP $pid");
						posix_kill($pid, SIGHUP); 
					}
					else
					{
						ZMC::debugLog(__FILE__ . __LINE__ . " launch backup_monitor >> $backupLogFn");
						exec(ZMC::$registry->cnf->zmc_bin_path . "backup_monitor >> $backupLogFn 2>>$backupLogFn &");
					}
				}

				if (file_exists("$amdumpFn.result") || !file_exists("$amdumpFn.pid"))
				{
					$result = str_replace("\n", ' at ', file_get_contents("$amdumpFn.result"));
					$return = self::FINISHED;
					if ($result[0] === '0')
						$pm->addMessage("Backup finished already! Total time: " . ($sleep * 0.25) . ' seconds');
					else
					{
						$pm->addError("Backup finished already, but a problem occurred: code #$result ($sleep)"
							. (" See $amdumpFn.result: "  . file_get_contents($amdumpFn)));
						$return = self::FAILED;
					}

					if (empty($pid)) 
						$pm->addError("Finished backup run, but there is a problem with the backup monitor ($sleep).");

					if ('OK' !== ($fatal = self::updateLastDumpStatus($pm, $config)))
						$pm->addError($fatal);
					return $return;
				}
				if (!empty($pid) && !file_exists("/proc/$pid"))
					$wait = 80; 
			}

			if (empty($pid)) 
				$pm->addError("Started backup, but there is a problem with the backup monitor ($sleep).");
			$pm->addWarning('Backup running ...');
		}
		catch (Exception $e) 
		{
			$pm->addError("Unable to start backup: $e");
			return self::ABORTED;
		}
		return self::STARTED; 
	}
	
	private static function getBackupLogFn(ZMC_Registry_MessageBox $pm, $config)
	{
		$logs = ZMC::$registry->etc_amanda . "$config/logs/";
		if (!file_exists($fn = "$logs/amdump"))
			if (!file_exists($fn = "$logs/amflush"))
				$fn = false;

		return $fn;
	}

	private static function amstatus(ZMC_Registry_MessageBox $pm, $config, $amstatusFn)
	{
		ZMC_ProcOpen::procOpen('amstatus', $cmd = ZMC::getAmandaCmd('amstatus'), array($config, '-odisplayunit=k','--locale-independent-date-format', '--file', $amstatusFn), $stdout, $stderr);

		return $stderr . $stdout;
	}

	private static function &getNewAmdumpLogs(ZMC_Registry_MessageBox $pm, $config, array &$foundPreviously)
	{
		$logs = ZMC::$registry->etc_amanda . "$config/logs/";
		$foundNow = array();
		foreach (glob($logs . 'amdump*', GLOB_NOSORT) as $filename)
		{
			$stat = stat($filename);
			$foundNow[$stat['ino'] . '.' . $stat['size']] = $filename;
		}
		$new = array_diff($foundNow, $foundPreviously);
		$foundPreviously = $foundNow;
		return $new;
	}

	
	public static function monitorBackupRun(ZMC_Registry_MessageBox $pm, $config)
	{
		$logListFn = ZMC::$registry->etc_amanda . "$config/logs/.zmc.amdump.loglist";
		$oldLogs = unserialize(file_get_contents($logListFn)); 
		$newLogs = self::getNewAmdumpLogs($pm, $config, $amdumpLogs);
		if ($amstatusFn = self::getBackupLogFn($pm, $config) || !empty($newLogs))
		{
			sort($newLogs);
			foreach($newLogs as $key => $log)
			{
				$result = self::amstatus($pm, $config, $amstatusFn); 
				$oldLogs[$key] = $log; 
				file_put_contents($logListFn, serialize($oldLogs)); 
			}
		}
	}

	private static function updateLastDumpStatus($pm, $name)
	{
		$id = self::getId($name);
		$sizeinode = $fatal = '';
		$logs = ZMC::$registry->etc_amanda . "$name/logs/";
		if (file_exists($amdump1 = $logs . 'amdump.1'))
		{
			$stat = stat($amdump1);
			$sizeinode = "$stat[size] $stat[ino]";
			if ($sizeinode === self::$sets[$id]['last_amdump_log'])
				return self::$sets[$id]['last_amdump_result'];

			if (false === ($fp = fopen($amdump1, 'r')))
				return "Unable to read $amdump1";

			fgets($fp); 
			fgets($fp); 
			$parts = explode(' ', trim(fgets($fp))); 
			fclose($fp);

			if ($fp = fopen($fn = $logs . "log.$parts[2].0", 'rb'))
			{
				$lineno = 0;
				while(($line = fgets($fp, 8192)) && ($lineno++ <= 1000))
					if (!strncmp($line, 'FATAL', 5))
					{
						$what = ucFirst(strtok(substr($line, 6), ' '));
						$fatal .= "Amanda $what: " . substr($line, strlen($what) + 7);
					}
				fclose($fp);
			}

			if (empty($fatal))
				$fatal = 'OK';

			ZMC_Mysql::update('configurations', array(
				'last_amdump_result' => (self::$sets[$id]['last_amdump_result'] = $fatal),
				'last_amdump_log' => (self::$sets[$id]['last_amdump_log'] = $sizeinode),
				'last_amdump_date'=> (self::$sets[$id]['last_amdump_result'] = (($fatal === 'OK' && !empty($parts)) ? $parts[2] : null))
			),
			array('configuration_name' => $name), "Can not find and update backup set '$name'");
		}
		return $fatal;
	}

	public static function getRawBindings(ZMC_Registry_MessageBox $pm, $names = null)
	{
		$raw = array();
		if ($names === null)
			$names = self::listConfigs();
		elseif (is_string($names))
		{
			$names = array($names);
			$want1 = true;
		}

		foreach($names as $name)
			foreach(glob($fn = ZMC::$registry->etc_amanda . $name . '/binding-*.yml', GLOB_NOSORT) as $found) 
				$raw[$name] = ZMC_Yaml_sfYaml::load($found);

		return $want1 ? $raw[$name] : $raw;
	}

	public static function &mergeFindTapelist(ZMC_Registry_MessageBox $pm, $binding, $initial_retention = 3650, $vaultedOnly = false, $father_retention=0, $grandfather_retention=0)
	{
		if (!$pm->offsetExists('selected_name')) ZMC::quit($pm);
		if (empty($binding)) ZMC::quit();
		try{
			ZMC_Yasumi::operation($pm, array('pathInfo' => "/amadmin/get_records/" . $pm->selected_name, 'data' => array(), 'post' => null, 'postData' => null,), true);
		}
		catch(Exception $e)
	    { return false; }
	    $tapelist = array();
	    ZMC_BackupSet::getTapeList($pm, $tapelist, $pm->selected_name);

	    
	    if(filesize("/etc/amanda/$pm->selected_name/tapelist") != 0)
	    	$originalTapeList = ZMC_BackupSet::getTapeListForBackupSet($pm->selected_name);
	    else
	    	$originalTapeList = null;
	    
		$tl =& $tapelist['tapelist'];
		foreach(array_keys($tl) as $key)
			if (empty($tl[$key]['timestring'])) 
				unset($tl[$key]);
 			else if ($originalTapeList && !$vaultedOnly && !in_array($key, $originalTapeList)) 
				unset($tl[$key]);
 			else if ($originalTapeList && $vaultedOnly && in_array($key, $originalTapeList)) 
 				unset($tl[$key]);

		if (empty($tl))
		{
			if($vaultedOnly)
				$pm->addMessage("No vaults found.");
			else
				$pm->addMessage("No backups found.");
			return $tl;
		}

		if (!empty($pm->records))
		{
		    foreach($pm->records as &$historicalRecord)
			{
				$label = $historicalRecord['tape_label'];
				if ($label[0] === '/') 
				{
					$stats = stat($label);
					$size = 'NA';
					$pu = '-';
					$displayLabel = '';
					if (!empty($stats) && !empty($stats['size']))
					{
						$size = round(bcdiv($stats['size'], '1048576'), 0);
						if (empty($binding['holdingdisk_list'])) ZMC::quit($binding);
						foreach($binding['holdingdisk_list'] as $holdName => $hold)
							if (!strncmp($hold['directory'] . '/', $label, $len = strlen($hold['directory'])+1))
							{
								$displayLabel = (($holdName === 'zmc_default_holding') ? '' : $holdName) . substr($label, $len);

								if (!empty($hold['use']))
									$pu = round($size / intval($hold['use']), 3) * 100;
								break;
							}
					}
					$tl[$label] = array(
						'comment' => $displayLabel,
						'ctimestamp' => $historicalRecord['timestamp'],
						'datetime' => $historicalRecord['datetime'],
						'disk_name' => $historicalRecord['disk_name'],
						'host' => $historicalRecord['host'],
						'label' => $historicalRecord['tape_label'],
						'level' => $historicalRecord['level'],
						'nb' => 1,
						'nc' => 1,
						'percent_use' => $pu,
						'reuse' => 'reuse',
						'size' => $size . 'M',
						'time_duration' => '-',
					);
				}
				else
				{
 					if(!array_key_exists($label, $tl)){ 
 						if($vaultedOnly) { 
	 						$label = '';
							foreach(array_keys($tl) as $key){
								if($tl[$key]['timestring'] === $historicalRecord['datetime']){
									$tl[$label] = $tl[$key];
									break;
								}
							}
 						} else { 
 							continue;
 						}
 					}
						
					if(empty($label)) 
						continue;
						
					$tl[$label]['ctimestamp'] = $historicalRecord['timestamp'];
					
					
					unset($historicalRecord['timestamp']);
					if (empty($tl[$label])) 
					{
						
						$tl[$label] = &$historicalRecord;
					}
					else {
						if($vaultedOnly){
							$host = $tl[$label]['host'];
							$diskName = $tl[$label]['disk_name'];
							ZMC::merge($tl[$label], $historicalRecord);
							if(!empty($host))
								$tl[$label]['host'] = $host . ", " . $tl[$label]['host'];
							if(!empty($diskName))
								$tl[$label]['disk_name'] = $diskName . ", " . $tl[$label]['disk_name'];
						} else {
							ZMC::merge($tl[$label], $historicalRecord);
						}
					}
					if (!empty($binding['media']['partition_total_space']))
						$tl[$label]['percent_use'] = round(intval($tl[$label]['size']) / $binding['media']['partition_total_space'], 3) * 100;
				}

				$tl[$label]['initial_retention'] = $initial_retention;
				$tl[$label]['father_retention'] = $father_retention;
				$tl[$label]['grandfather_retention'] = $grandfather_retention;

				if (empty($oldest) || ($tl[$label]['datetime'] < $oldest['datetime']))
					$oldest = $tl[$label];
			}
			unset($pm->records);
			foreach(array_keys($tl) as $key)
				if (empty($tl[$key]['host']))
					unset($tl[$key]);
			
			$sortByDate = array();
			foreach($tl as $key => $row)
				$sortByDate[$key] = $row['datetime'];
			array_multisort($sortByDate, SORT_ASC, $tl);
			$Lpath = array();
			foreach($tl as &$tape)
			{
				$key = "$tape[host]\0$tape[disk_name]";
				if ($tape['level'] == 0)
				{
					$L0[$key] = $tape['datetime'];
					if (!empty($pm->dles))
						$pm->dles[$pm->selected_name . "|$tape[host]|$tape[disk_name]"]['L0']++;
				}
				else
				{
					if ($tape['datetime'] < $L0[$key])
						$L0[$key] = 0;
					if (!empty($pm->dles))
						$pm->dles[$pm->selected_name . "|$tape[host]|$tape[disk_name]"]['Ln']++;
				}
			}

			if (!empty($pm->dles))
			{
				foreach(array_keys($pm->dles) as $key)
					if (empty($pm->dles[$key]['host_name']))
						unset($pm->dles[$key]);

				return $tl;
			}
			
			
			$t=time() +7200;
			$retentionDate = ZMC::dateNow($t - ($initial_retention * 86400) - 180);
			$priors = $prune_rest = array();

			$latestFullBackups = array(); 
			$secondLatestFullBackups = array(); 
			$keys = array_reverse(array_keys($tl));
			$hasDependants = array();
			$prevLevel = 0;

			foreach($keys as $key){
				$pkey = $tl[$key]['host'] . "\0" . $tl[$key]['disk_name'];
				$curLevel = $tl[$key]['level'];
				if($curLevel == 0){
					if(empty($latestFullBackups[$pkey])){
						$latestFullBackups[$pkey] = array('label' => $key, 'ctimestamp' => $tl[$key]['ctimestamp']);
					} else if (empty($secondLatestFullBackups[$pkey])) {
						$secondLatestFullBackups[$pkey] = array('label' => $key, 'ctimestamp' => $tl[$key]['ctimestamp']);
					}
				}
	
				if($curLevel < $prevLevel)
					$hasDependants[$pkey][$key] = true;
				else
					$hasDependants[$pkey][$key] = false;
				
				$prevLevel = $curLevel;
			}
			
			foreach($keys as $key)
			{
				$level = $tl[$key]['level'];
				$tl[$key]['prune_reason'] = null;
				$tl[$key]['age'] = round(($t - $tl[$key]['ctimestamp']) / 86400, 4);
				$pkey = $tl[$key]['host'] . "\0" . $tl[$key]['disk_name'];
				unset($prior);
				$prior = (isset($priors[$pkey]) ? $priors[$pkey] : -1);
				if (strcmp($tl[$key]['datetime'], $retentionDate) < 0)
				{
					if($level == 0
							&& ($latestFullBackups[$pkey]['label'] == $key 
									|| ($secondLatestFullBackups[$pkey]['label'] == $key
											&& (time() - $latestFullBackups[$pkey]['ctimestamp']) < ($initial_retention * 86400)))) 
						$tl[$key]['retained_reason'] = 'Last Full Backup';
					elseif (!empty($prune_rest[$pkey]))
						$tl[$key]['prune_reason'] = 'Expired';
					elseif (!empty($tl[$key]['Ln_missing']))
						$tl[$key]['prune_reason'] = 'no L' . $tl[$key]['Ln_missing'];
					elseif ($level >= $prior)
					{
						if ($prune_rest[$pkey] = ($prior <= 0)){
							
							
						}
						if ($prior == 0)
							$tl[$key]['prune_reason'] = 'Expired';
						elseif($hasDependants[$pkey][$key])
							$tl[$key]['retained_reason'] = 'Unexpired Dependant(s)';
						else
							$tl[$key]['prune_reason'] = 'Expired';
					}
					elseif ($level == 0)
					{
						$prune_rest[$pkey] = true;
						
						
						$tl[$key]['retained_reason'] = 'Unexpired Dependant(s)';
					}
					elseif($hasDependants[$pkey][$key])
						$tl[$key]['retained_reason'] = 'Unexpired Dependant(s)';
					else
						$tl[$key]['prune_reason'] = 'Expired';
				}
				else
					$tl[$key]['retained_reason'] = 'Unexpired';
				$priors[$pkey] = $level;
			}

			$rows = ZMC_Mysql::getAllRowsMap('SELECT CONCAT(REPLACE(REPLACE(REPLACE(backuprun_date_time, " ", ""), "-", ""), ":", ""),"-",hostname,"-",directory) as natkey, zmc_type, zmc_amanda_app, zmc_custom_app, compress, encrypt FROM backuprun_dle_state WHERE configuration_id = ' . ZMC_BackupSet::getId($pm->selected_name) . " AND backuprun_date_time >= '$oldest[date] $oldest[time]' ORDER BY backuprun_date_time ASC");
			$pm->size_expired_but_retained = $pm->size_expired = $pm->size_unexpired = $pm->size_total = 0;
			foreach($tl as &$tape)
			{
				$key = $tape['datetime'] . '-' . $tape['host'] . '-' . $tape['disk_name'];
				$pm->size_total += ZMC::mediasummaryunits($tape['size'], $pm->conf['displayunit']);
				if (!empty($tape['prune_reason']))
					$pm->size_expired += ZMC::mediasummaryunits($tape['size'], $pm->conf['displayunit']);
				else
				{
					$pm->size_unexpired += ZMC::mediasummaryunits($tape['size'], $pm->conf['displayunit']);
					if ($tape['retained_reason'] !== 'Unexpired')
						$pm->size_expired_but_retained += ZMC::mediasummaryunits($tape['size'], $pm->conf['displayunit']);
				}
				if (!isset($rows[$key]))
					continue;
				$row = $rows[$key];
				$tape['zmc_type'] = $row['zmc_type'];
				$tape['zmc_amanda_app'] = $row['zmc_amanda_app'];
				$tape['zmc_custom_app'] = $row['zmc_custom_app'];
				$tape['compress'] = $row['compress'];
				$tape['encrypt'] = $row['encrypt'];


				ksort($tape);
			}
		}

		if (!empty($L0_missing))
			$pm->addError("$L0_missing incremental backups have no corresponding full backup image!");

		return $tl;
	}
	
	public static function getTapeListForBackupSet($backupsetName){
		
		
		$tapeList = array();
		$filename = ZMC::$registry->etc_amanda . $backupsetName . '/tapelist';
		if(file_exists($filename)){
			$content = file($filename, FILE_IGNORE_NEW_LINES);
		    if (!empty($content)){
				foreach($content as $existing_label){
					if ($pos = strpos($existing_label, '#'))
						list($existing_label, $comment) = explode('#', $existing_label);
					$time = strtok(trim($existing_label), " \t");
					$label = strtok(" \t");
					if(!empty($label) && strpos($label, '-vault-') === false)
						$tapeList[] = $label;
				}
			}
		}
		return $tapeList;
	}

	public static function mediaOperation(ZMC_Registry_MessageBox $pm, $op, $ids = array(), $data = array())
	{
		try
		{
			if (empty($pm->selected_name)) 
				$pm->addInternal('No backup set selected. Please select a backup set and try again.');
			$set = ZMC_BackupSet::getByName($pm->selected_name);
			if (empty($set) || empty($set['profile_name'])) 
				$pm->addInternal("Backup set {$pm->selected_name} not found.   Please select a backup set and try again.", print_r($set, true));

			if(!array_key_exists('binding_conf', $data))    
				$data = array('binding_conf' => $data); 
	
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => '/label/' . strtr($op, ' ', '_') . '/' .  $pm->selected_name,
				'postData' => json_encode(array('media_list' => $ids)),
				'data' => array_merge($data, array(
					'commit_comment' => ($pm->offsetExists('subnav') ? $pm->subnav:'Amdump.php') . "|$op",
					'binding_name' => $set['profile_name'],
					'message_type' => "label/$op",
					'barcodes_enabled' => empty($_REQUEST['barcodes_enabled']),
				))
			));
			unset($result['request']);
			$pm->merge($result);
		}
		catch(Exception $e)
		{ throw $e; }
	}

	public static function is_already_in_use($configuration_name, $label){
		try{
			$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($configuration_name, 'inventory'),
				$stdout, $stderr, "'$cmd inventory' command failed unexpectedly");
			if($stdout){
				preg_match_all("/^(.*)?$label(.*)?reserved(.*)?\$/m", $stdout, $matches, PREG_PATTERN_ORDER);
				if(!empty($matches[0])){
					foreach($matches[0] as $val){
						if(preg_match("/$label/", $val)){
							return true;
						}
					}
				}
			}
		}catch (ZMC_Exception_ProcOpen $e){}

		return false;
	}

	public static function pruneAllExpired(ZMC_Registry_MessageBox $pm, $binding, $retention = null)
	{
		$set = self::getByName($pm->selected_name);
		if (empty($set))
			return $pm->addError("Can not prune media for backup set: " . $pm->selected_name . ". Unable to find this backup set. Was it deleted?");
		if (empty($retention))
			$retention = $set['initial_retention'];
		if (!($retention > 0))
			return $pm->addError("Invalid retention period: $retention");

        $tl =& ZMC_BackupSet::mergeFindTapelist($pm, $binding, $retention);
		$prunable = array();
		$vtapesWanted = $set['dles_total'];

		foreach($tl as $label => $tape)
		{
			if (!isset($tape['age'])){
				
				
				
				
				continue;
			}
			$is_already_use = self::is_already_in_use($set['configuration_name'], $label);
			if (!empty($tape['prune_reason']) && $tape['reuse'] !== 'no-reuse' && $is_already_use == false){
				$pm->addDetail("Pruning level ".$tape['level']." for ".$tape['host']."\0".$tape['disk_name']." with label $label");	
				$prunable[$label] = true;
			}
			if (!empty($tape['prune_reason']) && $tape['reuse'] !== 'no-reuse' && $is_already_use == true){
				$pm->addDetail("Not Pruning level ".$tape['level']." for ".$tape['host']."\0".$tape['disk_name']." with label $label, because it is already in use by another Amanda process. i.e amvault, amdump");	
			}
			if ($tape['label'][0] === '/')
				$vtapesWanted++;
		}

		
 		ZMC::auditLog(__FUNCTION__ . ': ' . implode(', ', array_keys($prunable)));
		ZMC_BackupSet::mediaOperation($pm, 'Recycle', $prunable,
			array('binding_conf' => $binding, 'want_num_empty' => $vtapesWanted));
	}
	
	public static function purgeVaultMedia(ZMC_Registry_MessageBox $pm, $name){
		$vault_devices = array();
		foreach(glob(ZMC::$registry->etc_amanda . $name . "/jobs/vault/saved/Vault-changer_*.conf") as $filePath){
			if(preg_match('/Vault-changer_(.+)\.conf/', $filePath, $matches))
				$vault_devices[] = $matches[1];
		}
		
		$adminDevice = new ZMC_Admin_Devices($pm);
		$device_profile_list = $adminDevice->getDeviceList($pm);
		
		foreach($vault_devices as $device_name){
			$device = $device_profile_list[$device_name];
			switch($device['_key_name']){
				case 's3_cloud':
					$bucketname = "zmc-" . strtolower($device['device_property_list']['S3_ACCESS_KEY'] . "-" . $name . "-vault");
					$bucketManager = ZMC_A3::createSingleton($pm, $device);
					$bucketManager->deleteBuckets(array($bucketname => true), true);
					break;
					
				case 'attached_storage':
					$vaultDir = rtrim($device['changer']['changerdev_prefix'], '/') . "/" . $name . "/" . $device['id'];
					if ($result = ZMC::rmrdir($realdir = realpath($vaultDir)))
					{
						$pm->addWarning("Unable to delete directory: $vaultDir" . ($realdir !== $vaultDir ? " (real path: $realdir)":'') . "\n$result");
					}
					break;
					
				default:
					break;
			}
		}
	}
}
