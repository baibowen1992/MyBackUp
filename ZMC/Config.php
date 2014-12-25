<?
























class ZMC_Config extends ZMC_Registry
{
	
	public function __construct($overridesFromZmcDotPhp = null)
	{
		$ds = DIRECTORY_SEPARATOR;
		$ps = PATH_SEPARATOR;
		if (empty($overridesFromZmcDotPhp['name']))
			$overridesFromZmcDotPhp['name'] = 'Splash';
		if (empty($overridesFromZmcDotPhp['short_name_lc']))
			$overridesFromZmcDotPhp['short_name_lc'] = '';
		$overridesFromZmcDotPhp['etc_zmanda'] = ZMC_ConfigHelper::$etc_zmanda;
		$overridesFromZmcDotPhp['etc_zmanda_product'] = $overridesFromZmcDotPhp['etc_zmanda'] . 'zmc_' . $overridesFromZmcDotPhp['short_name_lc'] . $ds;
		$overridesFromZmcDotPhp['zmc_path'] = __DIR__ . $ds; 
		$overridesFromZmcDotPhp['install_path'] = '/opt/zmanda/amanda/';
		$overridesFromZmcDotPhp['pear_path'] = $overridesFromZmcDotPhp['install_path'] . 'php' . $ds . 'lib' . $ds . 'php' . $ds; 
		$overridesFromZmcDotPhp['zn_host'] = 'network'; 
		if (!isset($_SESSION['zmanda_network_id']))
			$_SESSION['zmanda_network_id'] = '';

		
		parent::__construct(array(
			'64bit' => false, 
			'admin_task_commands' => array( 
				'amadmin',
				'amcheckdb',
				'amcleanup',
				'amdump',
				'amflush',
				'amlabel',
				'amlabel',
				'amreport',
				'amrmtape',
				'bzip2',
				'chgrp',
				'chmod',
				'chown',
				'cp',
				'date',
				'df',
				'diff',
				'du',
				'echo',
				'env',
				'file',
				'find',
				'grep',
				'gzip',
				'head',
				'ls',
				'lsattr',
				'lsscsi',
				'man',
				'md5sum',
				'mkdir',
				'mt',
				'mtx',
				'mv',
				'nslookup',
				'ping',
				'ps',
				'pstree',
				'sha1sum',
				'sha224sum',
				'sha256sum',
				'sha384sum',
				'sha512sum',
				'sort',
				'star',
				'stty',
				'tail',
				'tar',
				'top',
				'traceroute',
				'tree',
				'uname',
				'uptime',
			), 
			'apache_http_port' => 80,
			'apache_http_if' => '*',
			'apache_https_port' => 443,
			'apache_https_if' => '*',
			'audit_log'	=> $overridesFromZmcDotPhp['install_path'] . 'logs' . $ds . 'zmc_audit.log',
			'bomb_url' => '/Common/internal_error.html', 
			'bomb_url_php' => '/Common/internal_error.php', 
			'cache_cloud_list_of_buckets' => 86400, 
			'check_localhost_tar_version' => false, 
			'curlopt_cainfo' => $overridesFromZmcDotPhp['install_path'] . 'common' . $ds . 'share' . $ds . 'curl' . $ds . 'curl-ca-bundle.crt',
			'curlopt_capath' => $overridesFromZmcDotPhp['install_path'] . 'common' . $ds . 'share' . $ds . 'curl' . $ds,
			'database' => new ZMC_Registry( array(
				'name' => 'zmc',
				'user' => 'zmc',
				'host' => 'localhost', 
			)),
			'date_timezone' => ini_get('date.timezone'),
			'debug_log'		=> $overridesFromZmcDotPhp['install_path'] . 'logs' . $ds . 'zmc_gui_debug.log',
			'debug_logs_dir' => __DIR__ . '/debug/logs/',
			'display_max_files' => 10000000, 
			'critical_disk_space_threshold' => 10, 
			'warning_disk_space_threshold' => 15, 
			'disk_space_check_frequency' => 60, 
			'email_address' => 'support@zmanda.com', 
			'email_notify' => false, 
			'sql_time_limit' => 300, 
			'error_log'		=> $overridesFromZmcDotPhp['install_path'] . 'logs' . $ds . 'zmc_gui_errors.log',
			'find_hostnames' => true, 
			'gnutar_recommended' => array(1, 20, 0), 
			'gnutar_required' => array(1, 15, 1), 
			'install_path'	=> $overridesFromZmcDotPhp['install_path'], 
			'images_path'	=> $overridesFromZmcDotPhp['zmc_path'] . 'images' . $ds,
			'internet_connectivity' => true, 
			'dns_server_check' => false,
			'input_log'		=> $overridesFromZmcDotPhp['install_path'] . 'logs' . $ds . 'input' . $ds . 'zmc_gui_input.' . substr(ZMC::dateNow(true), 0, 6) . '.log',
			'input_filters'	=> true, 
			'license_expiration_warning_weeks' => 3, 
			'links' => array(
				'download'	=> 'http://' . $overridesFromZmcDotPhp['zn_host'] . '.zmanda.com/index.php?redirect=download.php&username=' . $_SESSION['zmanda_network_id'],
				'feedback'	=> 'http://www.zmanda.com/feedback.php?feature=' . ZMC::escape(str_replace(' ', '_', $overridesFromZmcDotPhp['name'])) . '&email='
					. ZMC::escape($_SESSION['zmanda_network_id']) . "&r=$overridesFromZmcDotPhp[amanda_svn_info]",
				'home'		=> 'http://' . $overridesFromZmcDotPhp['zn_host'] . '.zmanda.com/index.php?redirect=home.php&username=' . $_SESSION['zmanda_network_id'],
				'shopping'	=> 'http://' . $overridesFromZmcDotPhp['zn_host'] . '.zmanda.com/index.php?redirect=/shop/home.php?cat=1,3&username=' . $_SESSION['zmanda_network_id'],
				'support'	=> 'http://' . $overridesFromZmcDotPhp['zn_host'] . '.zmanda.com/index.php?redirect=support.php',
			),
			'locale_sort'	=> 'C', 
			'log_slow_queries' => true, 
			'lore'			=> 'http://network.zmanda.com/lore/article.php?id=',
			'low_memory'	=> 0.75, 
			'max_execution_time' => 300, 
			'netusage'		=> 2000000, 
			'part_cache_max_size' => '256m',
			'pear_path'		=> $overridesFromZmcDotPhp['pear_path'], 
			'php_memory_limit' => '256m', 
			'prefix' => '', 
			'proc_open_ultrashort_timeout' => 15, 
			'proc_open_short_timeout' => 60, 
			'proc_open_long_timeout' => 300, 
			'qa_team' => false, 
			
			'readwrite_ignore_list' => array(), 
			'filesystem_reserved_percent' => array('solaris' => 10, 'windows' => 10, 'default' => 5),
			'safe_mode' => true, 
			'security_warnings' => true, 
			'scripts' => '/scripts-' . ZMC::filterDigits('$Revision: 25294$') . '/',
			'space_warnings' => true, 
			'session_timeout' => 15, 
			'sticky_restart' => array(), 
			'sticky_session' => array(), 
			'sticky_user' => array(), 
			'sticky_once' => array(), 
			'sticky_once_done' => array(), 
			'support_email' => '<a href="mailto:support@zmanda.com">support@zmanda.com</a>',
			'support_email_txt' => 'Please contact Zmanda Customer Support at support@zmanda.com.',
			'support_email_html' => 'Please contact Zmanda Customer Support at <a href="mailto:support@zmanda.com">support@zmanda.com</a>.',
			'svn_overlay' => '',
			
			'tmp_path'		=> $overridesFromZmcDotPhp['etc_zmanda'] . 'zmc_aee/data' . $ds, 
			'trim_white_space' => true, 
			'tz'			=> array('offsetSeconds' => 0, 'zone' => null), 
			'units' => array( 
				'storage' => array( 
					'k' => 'KiB', 
					'm' => 'MiB',
					'g' => 'GiB',
					't' => 'TiB',
					'%' => '%',
				),
				'storage_equivalents' => array( 
					'%' => '%',
					'b' => 'b',
					'byte' => 'b',
				   	'bytes' => 'b',
					'k' => 'k',
					'kib' => 'k',
					'kb' => 'k',
					'kbyte' => 'k',
					'kbytes' => 'k',
					'kilobyte' => 'k',
					'kilobytes' => 'k',
					'm'=>'m',
					'mb' => 'm',
					'mib' => 'm',
					'meg' => 'm',
					'mbyte' => 'm',
					'mbytes' => 'm',
					'megabyte' => 'm',
					'megabytes' => 'm',
					'g' => 'g',
					'gb' => 'g',
					'gib' => 'g',
					'gig' => 'g',
					'gbyte' => 'g',
					'gbytes' => 'g',
					'gigabyte' => 'g',
					'gigabytes' => 'g',
					't' => 't', 
					'tb' => 't',
					'tib' => 't',
					'tera' => 't',
					'tbyte' => 't',
					'tbytes' => 't',
					'terabyte' => 't',
					'terabytes' => 't',
					'week' => 'week',
					'weeks' => 'week',
					'day' => 'day',
					'days' => 'day'
				),
				'rate' => array( 
					'bps' => 'bytes per second',
					'kbps' => 'kilobytes per second',
					'mbps' => 'megabytes per second',
				)
			),
			'url_zn_auth' => 'https://' . $overridesFromZmcDotPhp['zn_host'] . '.zmanda.com/WebServices/authenticate.php',
			'user_registry' => 'files', 
			'ultra_turbo' => false, 
			'use_cache' => true, 
			'verify_installed_files' => true,
			'verbose_logs' => true, 
			'wiki'			=> 'http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/ZMC_Users_Manual/',
			'zmc_backupset_version' => '3.3',
			'zn_frequency'	=> 60 * 60 * 24, 
			'zn_frequency'	=> 60 * 60 * 24, 
			'large_file_system' => true,
			'default_check_server_installation' => true,
			'default_sync_backupset' => true,
			'default_holding_disk_path' => '/var/lib/amanda/staging/',
			'default_vtape_device_path' => '/var/lib/amanda/vtapes/',
			'default_vmware_restore_temp_path' => '/tmp/amanda/',
		));

		if (!is_dir(__DIR__ . $this->scripts))
			if (is_dir($d = __DIR__ . '/scripts'))
				$this->scripts = '/scripts/';
			else
				ZMC::quit("Can not find scripts directory: " . $this->scripts);

		if ($overridesFromZmcDotPhp !== null && count($overridesFromZmcDotPhp))
			$this->merge($overridesFromZmcDotPhp);
		
		$this->debug_level = ZMC_Error::NOTICE;
		if ($this->offsetExists('debug'))
			$this->debug_level = ($this->debug ? ZMC_Error::DEBUG : ZMC_Error::NOTICE);
	}

	public function getAmandaConstant($key)
	{
		if ($this->offsetExists($key))
			return $this[$key]; 

		if (!isset($this->amanda_constants)) 
		{
			if (!is_readable($fn = $this->etc_zmanda_product . 'amanda_constants.php'))
			{
				$lines = file(ZMC::$registry->cnf->amanda_lib_path . 'perl/Amanda/Constants.pm', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				foreach($lines as $constant)
				{
					if ($constant[0] !== '$')
						continue;
					$parts = explode('"', $constant);
					if ((count($parts) < 2) || (substr($parts[1], 0, 1) === '@'))
						continue;
					$constants[trim($parts[0], "\$ \t=")] = $parts[1];
				}
				if (false === file_put_contents($fn, '<? return ' . var_export($constants, true) . ';'))
					throw new ZMC_Exception("Unable to write to $fn:" . ZMC::getFilePermHelp($fn));
			}
			$this->amanda_constants = require $fn;
		}

		if (!empty($this->amanda_constants[$key]))
			return ($this->amanda_constants[$key]);

		throw new ZMC_Exception("No value found for Amanda constant: $key");
	}

	public function setOverrides(array $changes)
	{
		if (empty($changes))
			return;

		if (is_readable($this->overrides_fn))
			$overrides = include($this->overrides_fn);
		else
		{
			ZMC::mkdirIfNotExists(dirname($this->overrides_fn));
			$overrides = array();
		}

		foreach($changes as $key => &$value)
		{
			if ($value !== null)
				ZMC::$registry->$key = $value; 

			if (!isset($overrides[$key]) || $overrides[$key] !== $value)
			{
				if ($value === null)
					unset($overrides[$key]);
				else
					$overrides[$key] = $value;
	
				$replace = true;
			}
		}

		if (empty($replace))
			return;

		ksort($overrides); 
		if (false === file_put_contents($this->overrides_fn, '<? return ' . var_export($overrides, true) . ';', LOCK_EX))
			throw new ZMC_Exception('Unable to write to "' . $this->overrides_fn . '".  ' . ZMC::getFilePermHelp($this->overrides_fn));
		file_exists($this->cache_fn) && unlink($this->cache_fn);
	}

	public function mergeOverride($key, $value)
	{
		if (!is_array($value))
			$value = array($value => true);

		if ($this->offsetExists($key))
			if (is_array($this->$key))
				return self::setOverrides(array($key => array_merge($this->$key, $value)));
			elseif ($this->$key instanceof ZMC_Registry)
				return self::setOverrides(array($key => $this->$key->merge($value)));

		self::setOverrides(array($key, $value));
	}
}
