<?













class ZMC_Admin_Preferences
{
	const PROMPT = 0;
	const RECOMMENDED = 1;

	private static $boolConfigs = array(
		
		
		'sync_always' => array('Auto-Sync Manual Edits?', true),
		'security_warnings' => array('Security Warnings?', true),
		'space_warnings' => array('Space Warnings?', true),
		'always_show_switcher' => array('Show backup set chooser?', false),
		'allow_dropping_vtapes' => array('Enable dropping vtapes?', false),
		'find_hostnames' => array('Recognize hostname aliases of this server?', true),
		'internet_connectivity' => array('Server has Internet connectivity?', true),
		'dns_server_check' => array('DNS Server Check?', true),
		'test_internet_connectivity' => array('Test Internet connectivity?', true),
		'trim_white_space' => array('Auto trim white space?', true),
		'check_localhost_tar_version' => array('Check version of tar on localhost?', true),
		'use_cache' => array('Turbo Mode?', true),
		'log_slow_queries' => array('Log Slow Queries?', true),
		'ultra_turbo' => array('Display Stale Results?', false),
		'verbose_logs' => array('Verbose Logs?', true),
		'verify_installed_files' => array('Verify Installed Files?', true),
		'enable_monitor_role' => array('Monitor Only Role?', false),
		'enable_restore_role' => array('Restore Only Role?', false),

		'debug' => array('Debug Mode?', false),
		'input_filters' => array('Input Filters?', true),
		'safe_mode' => array('Safe Mode?', true),
		
		'auto_exclude_unix_dirs' => array('Automatically exclude certain directories from *nix DLEs?', true),
		'auto_exclude_windows_dirs' => array('Automatically exclude certain directories from Windows DLEs?', false),
		'large_file_system' => array('Large File System (LFS)', true),
		'default_check_server_installation' => array('Check Server Installation? <br />(Login Screen)', true),
		'default_sync_backupset' => array('Sync Backup Sets? <br />(Login Screen)', true),
	);

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		if (ZMC::$registry->offsetExists('qa_team'))
		{
			self::$boolConfigs['dev_only'] = array('Developer Mode?', false);
			self::$boolConfigs['raw_restore_log'] = array('Raw Restore Log?', false);
			self::$boolConfigs['qa_mode'] = array('Zmanda QA Mode?', false);
		}

		if (ZMC_User::hasRole('Administrator'))
		{
			$tmp = ZMC::$registry->tmp_path;
			$uploadFn = $tmp . '/upload_logs.txt';
			if (file_exists($uploadFn))
				$pm->addWarning("Prior Log Upload Status: " . file_get_contents($uploadFn));
			if (	!empty($_REQUEST['ConfirmationYes'])
				&&	!empty($_REQUEST['action'])
				&& ($_REQUEST['action'] === 'UploadConfirm')
				&& ($_REQUEST['ConfirmationYes'] === 'Upload'))
			{
				unlink($uploadFn);
				$pm->addWarning("Collecting and compressing Amanda Enterprise logs and ZMC backup set configuration files.  Uploading to Zmanda may require several minutes.");
				ZMC::execv('bash', "cd $tmp; /opt/zmanda/amanda/bin/zm-support --ftp-to-zmanda > $uploadFn &");
			}
		}

		$pm->flagErrors = $pm->flagWarnings = array();
		$pm->sessionTimeout = ZMC::$registry->session_timeout;
		$pm->app = ZMC::$registry->short_name;
		$pm->skip_backupset_start = true;
		if (!empty($_REQUEST['form']))
		{
			$action = 'op' . ucFirst($_REQUEST['form']);
		   	if (method_exists('ZMC_Admin_Preferences', $action))
				call_user_func(array('ZMC_Admin_Preferences', $action), $pm);
		}
		if (ZMC::$registry->input_filters === false)
			self::addEscapedWarning($pm, 'input_filters', "Do <b>not</b> disable ZMC input filters, unless instructed by Zmanda Customer Support.  Re-enable Input Filters as soon as possible.");
		if (ZMC::$registry->safe_mode === false)
			self::addEscapedWarning($pm, 'safe_mode', "Do <b>not</b> disable safe mode, unless instructed by Zmanda Customer Support.  Re-enable safe mode as soon as possible.");
		if (ZMC::$registry->dev_only === true)
			self::addEscapedWarning($pm, 'dev_only', "Developer Mode is <b>NOT</b> supported on production installations.\nDEBUG mode auto-enabled (required when using developer mode).");
		if (ZMC::$registry->qa_mode === true)
			self::addWarning($pm, 'qa_mode', "QA Mode is <b>NOT</b> supported on production installations.");
		if (ZMC::$registry->use_cache !== true)
			self::addEscapedWarning($pm, 'use_cache', "Turbo Mode <b>is</b> recommended on all production installations.");
		if (ZMC::$registry->ultra_turbo === true)
			self::addEscapedWarning($pm, 'ultra_turbo', "Displaying stale results allows ZMC to display results quicker, but some information shown in ZMC will only be updated every " . ZMC::$registry->proc_open_long_timeout . " seconds.  Expired information may be displayed as &quot;unexpired&quot; or vice-versa. ZMC will perform various actions based on potentially incorrect assumptions.");
		if ((ZMC::$registry->ultra_turbo === true) && (ZMC::$registry->use_cache !== true))
			self::addWarning($pm, 'ultra_turbo', "Displaying stale results is <b>NOT</b> possible, because Turbo Mode has been disabled.");
		$pm->test_internet_connectivity_on = ''; 
		$pm->test_internet_connectivity_off = 'checked';
		$pm->addDefaultInstruction('Administer Preferences - adjust personal preferences');
		$pm->userSessionTimeout = ZMC_User::get('session_timeout');
		$pm->boolConfigs = self::$boolConfigs;
		foreach(self::$boolConfigs as $key => $ignored)
		{
			$pm[$key . '_on'] = (empty(ZMC::$registry[$key]) ? '' : 'checked="checked"');
			$pm[$key . '_off'] = (empty(ZMC::$registry[$key]) ? 'checked="checked"' : '');
		}
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', 'ZMC - Preferences', 'preferences'); 
		ZMC_HeaderFooter::$instance->addYui('zmc-utils', array('dom', 'event', 'connection'));
		return 'AdminPreferences';
	}

	private static function addEscapedWarning($pm, $flag, $warn)
	{
		$pm->flagWarnings[$flag] = true;
		$pm->addEscapedWarning($warn);
	}

	private static function addWarning($pm, $flag, $warn)
	{
		$pm->flagWarnings[$flag] = true;
		$pm->addWarning($warn);
	}
	
	private static function addError($pm, $flag, $err)
	{
		$pm->flagErrors[$flag] = true;
		$pm->addWarnError($err); 
	}
	
	public static function opUserPreferences($pm)
	{
		$t = intval($_POST['UserSessionTimeout']);
		
		if (ZMC::isValidIntegerInRange($t, 0, $pm->sessionTimeout))
		{
			ZMC_User::set($_SESSION['user_id'], 'session_timeout', $t);
			if ($t == 0) $t = $pm->sessionTimeout;
			$pm->addMessage("Your session will timeout after $t minutes of no activity.");
		}
		else
			self::addError($pm, 'UserSessionTimeout', "Session timeout cannot exceed {$pm->sessionTimeout} minutes.");
	
		if ($_POST['show_help_pages'] === 'Yes')
		{
			ZMC_User::set($_SESSION['user_id'], 'show_starter_page', 1);
			$pm->addMessage('Dismissed informational and help pages will be displayed.');
		}
	}
	public static function opGlobalInputDefaults($pm)
	{

		if(empty($_POST['default_vtape_device_path']))
			$pm->addError("Please specify default vtape device path.");
		if(empty($_POST['default_holding_disk_path']))
			$pm->addError("Please specify default holding disk path.");
		if(empty($_POST['default_vmware_restore_temp_path']))
			$pm->addError("Please specify default vmware restore temperory path.");
			
		if($pm->isErrors()){
			return false;
		}
		if($err1 = ZMC::mkdirIfNotExists($_POST['default_vtape_device_path'])){
			if($err1 != 1)
				$pm->addError($err1);
		}
		
		if($err2 =  ZMC::mkdirIfNotExists($_POST['default_holding_disk_path'])){
			if($err2 != 1)
				$pm->addError($err2);
		}


		if($pm->isErrors()){
			return false;
		}

		$_POST['default_holding_disk_path'] = rtrim($_POST['default_holding_disk_path'], "/ ")."/";
		$_POST['default_vtape_device_path'] = rtrim($_POST['default_vtape_device_path'], "/ ")."/";
		$_POST['default_vmware_restore_temp_path'] = rtrim($_POST['default_vmware_restore_temp_path'], "/ ")."/";

		ZMC::$registry['default_holding_disk_path'] = $_POST['default_holding_disk_path'];
		$overrides['default_holding_disk_path'] = $_POST['default_holding_disk_path'];
		
		ZMC::$registry['default_vtape_device_path'] = $_POST['default_vtape_device_path'];
		$overrides['default_vtape_device_path'] = $_POST['default_vtape_device_path'];
		
		ZMC::$registry['default_vmware_restore_temp_path'] = $_POST['default_vmware_restore_temp_path'];
		$overrides['default_vmware_restore_temp_path'] = $_POST['default_vmware_restore_temp_path'];
		ZMC::$registry->setOverrides($overrides);
	}

	public static function opGlobalDefaults($pm)
	{
		if (!ZMC_User::hasRole('Administrator'))
			return $pm->addError('Only ZMC administrators may perform this action.');

		if (($_POST['debug'] === 'No') && ZMC::$registry->debug)
		{
			$tmp = ZMC::$registry->tmp_path;
			$uploadFn = $tmp . '/upload_logs.txt';
			unlink($uploadFn);
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_help = 'Take a snapshot of AE configuration and log files.';
			$pm->addMessage('Snapshotting log files captures recent events. Uploading the logs to Zmanda helps the Zmanda Support Team respond to help requests.');
			$pm->prompt = 'Upload logs and backup set configuration files to Zmanda?';
			$pm->confirm_action = 'UploadConfirm';
			$pm->yes = 'Upload';
			$pm->no = 'No';
		}

		if ($_POST['dev_only'] === 'Yes')
			$_POST['debug'] = 'Yes';

		$overrides = array();
		foreach(self::$boolConfigs as $key => $bconfig)
		{
			if (!strncmp($_POST['action'], 'Reset', 5))
				$_POST[$key] = $bconfig[self::RECOMMENDED] ? 'Yes' : 'No';
			if (!empty($_POST[$key]))
			{
				$overrides[$key] = ($_POST[$key] === 'Yes');
				if (ZMC::$registry->$key !== $overrides[$key])
				{
				   	ZMC::$registry->$key = $overrides[$key];
					$pm->addMessage(str_replace('?', ': ', $bconfig[self::PROMPT]) . ' ' . $_POST[$key]);
				}
				
			}
		}

		if ($_POST['debug'] === 'No')
		{
		   	if (is_dir(ZMC::$registry->debug_logs_dir))
				ZMC::rmrdir(ZMC::$registry->debug_logs_dir);
			$overrides['debug_level'] = null;
		}

		$_POST['low_memory'] = floatval($_POST['low_memory']);
		if (($_POST['low_memory'] > 1) || ($_POST['low_memory'] < 0.25))
			$_POST['low_memory'] = 0.75;
		$_POST['license_expiration_warning_weeks'] = intval($_POST['license_expiration_warning_weeks']);
		if ($_POST['license_expiration_warning_weeks'] < 1)
			$_POST['license_expiration_warning_weeks'] = 1;
		$_POST['display_max_files'] = intval($_POST['display_max_files']);
		if ($_POST['display_max_files'] > 10000000 || $_POST['display_max_files'] < 500)
			self::addWarning($pm, 'display_max_files', '"Maximum Files to Display" should be between 500 and 10,000,000');
		$_POST['warning_disk_space_threshold'] = intval($_POST['warning_disk_space_threshold']);
		$_POST['critical_disk_space_threshold'] = intval($_POST['critical_disk_space_threshold']);
		$_POST['disk_space_check_frequency'] = intval($_POST['disk_space_check_frequency']);
		$_POST['sql_time_limit'] = intval($_POST['sql_time_limit']);
		$_POST['proc_open_short_timeout'] = intval($_POST['proc_open_short_timeout']);
		$_POST['proc_open_ultrashort_timeout'] = intval($_POST['proc_open_ultrashort_timeout']);
		$_POST['proc_open_long_timeout'] = intval($_POST['proc_open_long_timeout']);
		$_POST['cache_cloud_list_of_buckets'] = intval($_POST['cache_cloud_list_of_buckets']);
		$_POST['part_cache_max_size'] = intval($_POST['part_cache_max_size']);
		if (($_POST['part_cache_max_size'] > 3584) && (!ZMC::$registry['64bit']))
		{
			self::addWarning($pm, 'part_cache_max_size', '32bit AE maximum "Backup Split-Cache RAM" can not exceed 3584 MiB! Reduced setting to 3584 MiB.');
			$_POST['part_cache_max_size'] = 3584;
		}
		if ($_POST['part_cache_max_size'] < 100)
		{
			self::addWarning($pm, 'part_cache_max_size', 'Increased "Backup Split-Cache RAM" to the mimimum of 100 MiB.');
			$_POST['part_cache_max_size'] = '100';
		}
		$_POST['part_cache_max_size'] .= 'm';
		
		if (empty($_REQUEST['locale_sort']))
			$_REQUEST['locale_sort'] = "C";

		ZMC::setlocale($_REQUEST['locale_sort']);
		$_POST['php_memory_limit'] = intval(trim($_POST['php_memory_limit']));
		if ($_POST['php_memory_limit'] === -1)
			self::addWarning($pm, 'php_memory_limit', 'PHP per-process memory limit removed.');
		else
		{
			if (($_POST['php_memory_limit'] < 64) || ($_POST['php_memory_limit'] > 256))
				self::addWarning($pm, 'php_memory_limit', 'PHP per-process memory limit less than 64 MiB or greater than 256 MiB is NOT supported or recommended.');
			$_POST['php_memory_limit'] .= 'm';
		}
		$_POST['max_execution_time'] = intval($_POST['max_execution_time']);
		if (($_POST['max_execution_time'] < 120) || ($_POST['php_memory_limit'] > 300))
			self::addWarning($pm, 'max_execution_time', 'PHP script execution time limit less than 120 seconds or greater than 300 seconds is NOT recommended.');
		$_POST['date_timezone'] = str_replace(' ', '_', trim($_POST['date_timezone']));
		if (empty($_POST['date_timezone']))
			$tzError = $pm->addError('Please specify your local timezone.');
		elseif (ZMC::$registry['date_timezone'] !== $_POST['date_timezone'])
			if (!@timezone_open($_POST['date_timezone']) || !date_default_timezone_set($_POST['date_timezone']))
				$tzError = self::addError($pm, 'date_timezone', "Invalid Time Zone: \"$_POST[date_timezone]\".  Please enter a valid time zone.");

		if (false === ($phpini = file_get_contents($phpIniFn = $_SERVER['PHPRC'] . DIRECTORY_SEPARATOR . 'php.ini')))
			$pm->addError('Unable to read ZMC PHP settings.');

		if (empty($tzError))
		{
			$new = preg_replace(
				array(
					'#^memory_limit\s*=.*$#m',
					'#.*max_execution_time.*$#m',
					'#.*date.timezone.*$#m',
				),
				array(
					"memory_limit=$_POST[php_memory_limit]",
					"max_execution_time=$_POST[max_execution_time]",
					"date.timezone=\"$_POST[date_timezone]\"",
				),
				$phpini);
	
			if ($new !== $phpini)
				if (false === file_put_contents($phpIniFn, $new))
				{
					$pm->addError("Unable to save php.ini change(s): " . ZMC::getPermHelp($phpIniFn, 'file', '', 'chown amandabackup:root ' . $phpIniFn . '; chmod 660 ' . $phpIniFn));
					$pm->flagErrors['php_memory_limit'] = true;
					$pm->flagErrors['max_execution_time'] = true;
					$pm->flagErrors['date_timezone'] = true;
				}
				else
					$pm->addWarning("PHP settings changes saved, but will not take effect until ZMC has been restarted:  /etc/init.d/zmc_aee restart", ZMC_Registry_MessageBox::STICKY_RESTART);
		}

		if ($_POST['sql_time_limit'] < 15)
		{
			self::addWarning($pm, 'sql_time_limit', 'DB query time limits have been *DISABLED*.');
			$_POST['sql_time_limit'] = 0;
		}

		if ($_POST['proc_open_ultrashort_timeout'] < 2 || $_POST['proc_open_ultrashort_timeout'] > 150)
			self::addError($pm, 'proc_open_ultrashort_timeout', 'Ultra Short Task Timeout range is 2 to 150 seconds (15 seconds recommended).');
		if ($_POST['proc_open_short_timeout'] < 15 || $_POST['proc_open_short_timeout'] > 300)
			self::addError($pm, 'proc_open_short_timeout', 'Short Task Timeout range is 15 to 300 seconds (60 seconds recommended).');
		if ($_POST['proc_open_long_timeout'] < 300 || $_POST['proc_open_long_timeout'] > 1200)
			self::addError($pm, 'proc_open_long_timeout', 'Long Task Timeout range is 300 to 1200 seconds (300 recommended).');

		if ($_POST['cache_cloud_list_of_buckets'] < 5 || $_POST['cache_cloud_list_of_buckets'] > 86400)
			self::addError($pm, 'cache_cloud_list_of_buckets', 'Cache Cloud List of Buckets timeout range is 5 to 86400 seconds.');


		if ($_POST['critical_disk_space_threshold'] > $_POST['warning_disk_space_threshold'])
			self::addError($pm, 'critical_disk_space_threshold', 'Critical space warning threshold must be less than the warning space threshold.');
		elseif ($_POST['critical_disk_space_threshold'] > 90)
			self::addError($pm, 'critical_disk_space_threshold', 'Critical space warning threshold must not exceed 90% (recommended 15%.');
		elseif ($_POST['warning_disk_space_threshold'] > 90)
			self::addError($pm, 'warning_disk_space_threshold', 'Warning space warning threshold must not exceed 90% (recommended 15%).');
		elseif ($_POST['critical_disk_space_threshold'] < 1)
			self::addError($pm, 'critical_disk_space_threshold', 'Critical space warning threshold must be greater than 0% (recommended: 10%)');
		elseif ($_POST['warning_disk_space_threshold'] < 1)
			self::addError($pm, 'warning_disk_space_threshold', 'Warning space warning threshold must be greater than 0% (recommended: 15%)');

		if (!$pm->isErrors())
			foreach(array('low_memory', 'license_expiration_warning_weeks', 'display_max_files', 'warning_disk_space_threshold', 'critical_disk_space_threshold', 'disk_space_check_frequency', 'sql_time_limit', 'proc_open_ultrashort_timeout', 'proc_open_short_timeout', 'proc_open_long_timeout', 'cache_cloud_list_of_buckets', 'part_cache_max_size', 'php_memory_limit', 'max_execution_time', 'date_timezone', 'registry_key' ) as $key)
			{
				if (empty($_POST[$key]))
					continue;
				if ($key === 'registry_key') 
				{
					$key = $_POST['registry_key'];
					$_POST[$key] = $_POST['registry_value'];
				}
				ZMC::$registry[$key] = $_POST[$key];
				$overrides[$key] = $_POST[$key];
			}

	   	if (!empty($_POST['SessionTimeout']))
		{
			$t = intval(trim($_POST['SessionTimeout']));
			if (ZMC::isValidIntegerInRange($t, 1, 10080)) 
			{
				if (ZMC::$registry->session_timeout !== $t)
					$pm->addMessage("The maximum allowed session time limit has been changed to $t minutes of no activity.");
				$overrides['session_timeout'] = $t;
				$pm->sessionTimeout = $t;
			}
			else
				self::addError($pm, 'SessionTimeout', "The maximum allowed session time limit ($t) must be between 1 minute and 10,080 minutes (1 week).");
		}

		if ($_POST['test_internet_connectivity'] === 'Yes')
		{
			$result = ZMC::testConnectivity();
			if ($result === true)
				$pm->addMessage("Internet connectivity test was successful.");
			else
				self::addError($pm, 'test_internet_connectivity', "Internet connectivity test failed: $result");
		}

		ZMC::$registry->setOverrides($overrides);
	}
}
