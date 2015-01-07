<?
//zhoulin-admin-preference 201409172220
//高级社会主页面部分   数组循环












class ZMC_Admin_Preferences
{
	const PROMPT = 0;
	const RECOMMENDED = 1;

	private static $boolConfigs = array(
		
		
		'sync_always' => array('自动同步手动修改的配置?', true),
		'security_warnings' => array('安全警告?', true),
		'space_warnings' => array('容量警告?', true),
		'always_show_switcher' => array('显示备份集选择器?', false),
		'allow_dropping_vtapes' => array('允许删除虚拟存储设备', false),
		'find_hostnames' => array('识别主机名别名?', true),
		'internet_connectivity' => array('服务器有Internet连接?', true),
		'dns_server_check' => array('DNS服务器检测?', true),
		'test_internet_connectivity' => array('测试Internet连接?', true),
		'trim_white_space' => array('自动删除空字符?', true),
		'check_localhost_tar_version' => array('检测本地tar版本?', true),
		'use_cache' => array('加速模式?', true),
		'log_slow_queries' => array('日志慢速查询?', true),
		'ultra_turbo' => array('显示过期结果?', false),
		'verbose_logs' => array('详细日志?', true),
		'verify_installed_files' => array('校验安装文件?', false),
		'enable_monitor_role' => array('启用监控用户?', false),
		'enable_restore_role' => array('启用恢复用户?', false),

		'debug' => array('调试模式?', false),
		'input_filters' => array('输入过滤器?', true),
		'safe_mode' => array('安全模式?', true),
		
		'auto_exclude_unix_dirs' => array('自动排除*nix系统下备份项中的某些目录?', true),
		'auto_exclude_windows_dirs' => array('自动排除windows系统下备份项中的某些目录?', false),
		'large_file_system' => array('大文件系统 (LFS)', true),
		'default_check_server_installation' => array('检查服务器安装? <br />(登陆界面)', true),
		'default_sync_backupset' => array('同步备份集? <br />(登陆界面)', true),
	);

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		if (ZMC::$registry->offsetExists('qa_team'))
		{
			self::$boolConfigs['dev_only'] = array('开发模式?', false);
			self::$boolConfigs['raw_restore_log'] = array('原始恢复日志?', false);
			self::$boolConfigs['qa_mode'] = array('系统 QA 模式?', false);
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
				$pm->addWarning(" 收集并压缩日志以及备份配置文件");
				ZMC::execv('bash', "cd $tmp; /opt/zmanda/amanda/bin/zm-support  > $uploadFn &");
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
			self::addEscapedWarning($pm, 'input_filters', "不要禁用输入过滤,除非管理员请求配合支撑的时候，然后尽快开启.");
		if (ZMC::$registry->safe_mode === false)
			self::addEscapedWarning($pm, 'safe_mode', "不要禁用安全模式，除非管理员请求配合支撑的时候，然后尽快开启.");
		if (ZMC::$registry->dev_only === true)
			self::addEscapedWarning($pm, 'dev_only', "开发者模式在生产环境是不被支撑的，此时debug模式会自动开启.");
		if (ZMC::$registry->qa_mode === true)
			self::addWarning($pm, 'qa_mode', "QA模式在生产环境是不被支撑的.");
		if (ZMC::$registry->use_cache !== true)
			self::addEscapedWarning($pm, 'use_cache', "Turbo模式在所有生产环境推荐启用");
		if (ZMC::$registry->ultra_turbo === true)
			self::addEscapedWarning($pm, 'ultra_turbo', "展示历史数据。但是一些信息仅支持每隔 " . ZMC::$registry->proc_open_long_timeout . " 秒更新.有效信息显示为 &quot;unexpired&quot; ");
		if ((ZMC::$registry->ultra_turbo === true) && (ZMC::$registry->use_cache !== true))
			self::addWarning($pm, 'ultra_turbo', "因为turbo模式未开启，展示历史数据将失效。");
		$pm->test_internet_connectivity_on = ''; 
		$pm->test_internet_connectivity_off = 'checked';
		$pm->addDefaultInstruction('管理参数 - 调整个人参数');
		$pm->userSessionTimeout = ZMC_User::get('session_timeout');
		$pm->boolConfigs = self::$boolConfigs;
		foreach(self::$boolConfigs as $key => $ignored)
		{
			$pm[$key . '_on'] = (empty(ZMC::$registry[$key]) ? '' : 'checked="checked"');
			$pm[$key . '_off'] = (empty(ZMC::$registry[$key]) ? 'checked="checked"' : '');
		}
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', '云备份 - 系统设置', 'preferences'); 
		ZMC_HeaderFooter::$instance->addYui('wocloud-utils', array('dom', 'event', 'connection'));
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
			$pm->addMessage("你已设置你的登陆会话保留时间为 $t 分钟。");
		}
		else
			self::addError($pm, 'UserSessionTimeout', "登陆超时时间不能超过 {$pm->sessionTimeout} minutes.");
	
		if ($_POST['show_help_pages'] === 'Yes')
		{
			ZMC_User::set($_SESSION['user_id'], 'show_starter_page', 1);
			$pm->addMessage('重置信息，帮助页面将显示.');
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
			return $pm->addError('仅允许管理员进行该操作.');

		if (($_POST['debug'] === 'No') && ZMC::$registry->debug)
		{
			$tmp = ZMC::$registry->tmp_path;
			$uploadFn = $tmp . '/upload_logs.txt';
			unlink($uploadFn);
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_help = 'Take a snapshot of AE configuration and log files.';
			$pm->addMessage('快照日志文件中捕获最近发生的事件。上传日志来调整有助于调整支持团队的帮助请求。');
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
                    if ($_POST[$key] === 'Yes')
					    $pm->addMessage(str_replace('?', ': ', $bconfig[self::PROMPT]) . ' 是' );
                    elseif ($_POST[$key] === 'No')
                        $pm->addMessage(str_replace('?', ': ', $bconfig[self::PROMPT]) . ' 否' );
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
					$pm->addWarning("PHP设置更改已保存，再重启云备份服务后生效。", ZMC_Registry_MessageBox::STICKY_RESTART);
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
					$pm->addMessage("会话最大保留时间已更改为 $t 分钟，需要重启激活");
				$overrides['session_timeout'] = $t;
				$pm->sessionTimeout = $t;
			}
			else
				self::addError($pm, 'SessionTimeout', "最大会话保留时间 ($t) 必须是 1 ～10,080 分钟 (1 周).");
		}

		if ($_POST['test_internet_connectivity'] === 'Yes')
		{
			$result = ZMC::testConnectivity();
			if ($result === true)
				$pm->addMessage("Internet连接测试成功");
			else
				self::addError($pm, 'test_internet_connectivity', "Internet连接测试失败");
		}

		ZMC::$registry->setOverrides($overrides);
	}
}
