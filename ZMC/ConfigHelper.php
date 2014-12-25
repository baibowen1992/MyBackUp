<?













class ZMC_ConfigHelper
{
	public static $etc_zmanda = '/etc/zmanda/zmc/'; 

	private static function getRegistryName($app)
	{
		return array('ZMC_Config_' . ucfirst($app), 'zmc_' . strtolower($app) . DIRECTORY_SEPARATOR);
	}

	




	public static function getRegistry(ZMC_Registry_MessageBox $pm, $app = '')
	{
		$app = strtolower($app);
		if (ZMC::$registry !== null)
			return;

		list($name, $dir) = self::getRegistryName($app);
		$platform = self::getPlatform();
		if ($platform !== 'solaris' && $platform !== 'windows') 
			$platform = '';
		if (!empty($platform))
			$name .= '_' . ucfirst($platform);

		$dependencies = glob(__DIR__ . '/Config/*.php'); 
		array_push($dependencies, __DIR__ . '/Config.php');
		array_push($dependencies, $overridesFn = self::$etc_zmanda . $dir . 'zmc.php');
		array_push($dependencies, $cnfFilename = self::$etc_zmanda . "zmc_aee/zmc_aee.cnf");
		array_push($dependencies, $svnFilename = self::$etc_zmanda . "zmc_aee/zmc_aee_svn_info.cnf");
		if ($app !== 'aee') 
			array_push($dependencies, $aeeOverridesFn = self::$etc_zmanda . 'zmc_aee/zmc.php');

		array_unshift($dependencies, __FILE__); 
		$cacheFn = $name;
		if (ZMC::useCache($pm, $dependencies, $cacheFn, false, false)) 
		{
			if ((include $cacheFn) === true)
				return ZMC::$registry;
		}

		$file = new ZMC_Sed($pm, $cacheFn);
		$appOverrides = self::getOverrides($overridesFn);
		if ($app !== 'aee') 
			ZMC::merge($appOverrides, self::getOverrides($aeeOverridesFn)); 
		ZMC::$registry = new $name($appOverrides);
		ZMC::$registry->cache_fn = $cacheFn;
		ZMC::$registry->overrides_fn = $overridesFn;
		ZMC::$registry->platform = $platform;
		ZMC::$registry->cnf = self::getCnf($cnfFilename);
		ZMC::$registry->svn = self::getCnf($svnFilename);
		self::getMyHostNames(ZMC::$registry);
		self::setTimezone($pm, ZMC::$registry);





		if (!ZMC::$registry->offsetExists('dev_only')) 
			ZMC::$registry->dev_only = false; 
		if (ZMC::$registry->dev_only) 
			ZMC::$registry->debug = true;
		elseif (!ZMC::$registry->offsetExists('debug'))
			ZMC::$registry->debug = false; 

		if (ZMC::$registry->qa_team)
		{
			if (!ZMC::$registry->offsetExists('qa_mode')) 
				ZMC::$registry->qa_mode = true; 
		}
		else 
		{
			ZMC::$registry->dev_only = false;
			ZMC::$registry->qa_mode = false;
		}
		
		ZMC::mkdirIfNotExists(dirname(ZMC::$registry->input_log));
		if (false === $file->close('<? ZMC::$registry = ' . var_export(ZMC::$registry, true) . ";return true;\n"))
			$pm->addWarnError("Unable to cache registry! " . ZMC::getFilePermHelp($cacheFn));
		if (ZMC::$registry->debug)
			ZMC::$registry->ksort();
	}

	private static function getMyHostNames($registry)
	{
		if(true === ZMC::$registry->dns_server_check)
			ZMC::debugLog(__FILE__ . __LINE__ . ': Verifying hostnames, ZMC will also verify dns hostnames by looking into resolve.conf entries.');
		else
			ZMC::debugLog(__FILE__ . __LINE__ . ': Verifying hostnames, ZMC will skip dns hostname verification and avoid resolve.conf entries to increase performance.');

		$names = array(
			'localhost' => true,
			'localhost.' => true,
			'localhost.localdomain' => true,
			'localhost.localdomain.' => true,
			$gothostname = gethostname() => true,
		);
		$ips = array(
			'127.0.0.1' => true,
			'::1' => true,
		);

		if (!ZMC::$registry->find_hostnames)
			return;

		$found = array($gothostname);
		if(true === ZMC::$registry->dns_server_check)
		{
			exec('hostname --fqdn', $fqdn);
			if (count($fqdn) === 0)
				exec('hostname', $fqdn);
			if (count($fqdn))
			{
				$registry->fqdn = $fqdn;
				$found[] = $fqdn[0];
				$found[] = strtok($fqdn[0], '.');
			}
		}

		$solaris = (ZMC::$registry->platform === 'solaris');
		if (!is_executable($cmd = '/usr/bin/ifconfig'))
			if (!is_executable($cmd = '/sbin/ifconfig'))
				$cmd = '';

		if (empty($cmd))
			return ZMC::$registry->server_ip = 'unknown server ip';

		exec($cmd . ($solaris ? ' -a':''), $ifconfig);
		foreach($ifconfig as $line)
			if (preg_match($solaris ? '/^\s*inet\s+(\S+)/' : '/inet\d?\s+addr:\s*([^\/\s]+)/', $line, $matches))
				$ips[$matches[1]] = true;

		if(true === ZMC::$registry->dns_server_check)
		{
			foreach($ips as $ip => $ignored)
			{
				$names[gethostbyaddr($ip)] = $names[$ip] = true;
				if (!strncmp($ip, '192.168.', 8) || !strncmp($ip, '10.', 3))
					ZMC::$registry->server_ip = $ip;
			}
		}

		if (is_readable('/etc/hosts') && ($lines = file('/etc/hosts', FILE_IGNORE_NEW_LINES)))
		{
			foreach($lines as $hostnames)
			{
				$ip = strtok($hostnames, " \t");
				if (isset($ips[$ip]))
					foreach(preg_split("[\s]", $hostnames) as $hostname)
						if(!empty($hostname))
							$found[] = $hostname;
			}
		}

		if(true === ZMC::$registry->dns_server_check)
		{
			foreach($found as $hostname)
			{
				$names[$hostname] = true;
				if ($records = dns_get_record($hostname))
					foreach($records as &$record)
						if ($record['type'] === 'A')
							$names[$record['ip']] = true;
						elseif ($record['type'] === 'CNAME')
						{
							$names[$record['target']] = true;
							if ($records = dns_get_record($hostname))
								if ($record['type'] === 'A')
									$names[$record['ip']] = true;
						}
			}
		}

		ZMC::$registry->myHostNames = $names;
		ZMC::debugLog(__FILE__ . __LINE__ . ': Hostname Verification done');
	}

	



	private static function getCnf($filename)
	{
		$cnf = array('cnf' => $filename);
		$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!(count($lines) > 10))
			throw new ZMC_Exception("Failed to load $filename");

		foreach($lines as $line)
		{
			if ($line[0] === '#' || (false === strpos($line, '=')))
				continue;

			list($key,$value) = explode('=', $line);
			if(preg_match('/zmc_patch/', $line)){
				$cnf['zmc_patches'][] = trim(trim($value),'\'"');
			}     
			$cnf[$key] = trim(trim($value), '\'"');
		}
		ksort($cnf);
		return new ZMC_Registry($cnf);
	}

	private static function getOverrides($fn)
	{
		if (!file_exists($fn)) 
			return array();
		if (!is_readable($fn))
			throw new ZMC_Exception(ZMC::getFilePermHelp($fn));

		$overrides = include($fn);
		if (!is_array($overrides))
		{
			$overrides = array();
			$stat = stat($fn);
			if ($stat['size'] === 0)
				unlink($fn);
			else
			{
				rename($fn, $fn . '.corrupt');
				ZMC::errorLog($msg = $fn . ' is corrupted and has been renamed to '
					. $fn . '.corrupt.  Please refresh page to continue');
				echo $msg;
				exit;
			}
		}
		return $overrides;
	}

	private static function getPlatform()
	{
		switch(strtolower(PHP_OS)) 
		{
			case 'solaris':
			case 'sunos':
				return 'solaris';
		}
		if (isset($_SERVER['WINDIR']))
			return 'windows';
		return '';
	}

	




	private static function setTimezone(ZMC_Registry_MessageBox $pm, ZMC_Registry $registry)
	{
		$dateTimezone = ini_get('date.timezone');
		if (!empty($dateTimezone))
			if (!@timezone_open($dateTimezone) || !date_default_timezone_set($dateTimezone))
			{
				$pm->addError("Invalid Time Zone: \"$dateTimezone\".  Please enter a valid time zone on the Admin|Preferences page.");
				$dateTimezone='';
			}

		if (empty($dateTimezone)) 
		{
			if (file_exists($fn = '/etc/sysconfig/clock'))
			{
				$parts = explode('"', file_get_contents($fn));
				$dateTimezone = $parts[1];
				$server = true;
			}
			elseif (file_exists($fn = '/etc/default/init'))
			{
				$dateTimezone = getenv('TZ');
				$server = true;
			}
			elseif (file_exists($fn = '/etc/timezone'))
			{
				$parts = explode(' ', file_get_contents($fn));
				$dateTimezone = $parts[0];
				$server = true;
			}
			if (empty($dateTimezone) || !date_default_timezone_set($dateTimezone))
			{
				$default = true;
				date_default_timezone_set('America/Los_Angeles'); 
			}
		}

		$registry['tz'] = array(
			'timezone' => $dateTimezone, 
			'offsetSeconds'   => mktime(0, 0, 0, 1, 2, 1970) - gmmktime(0, 0, 0, 1, 2, 1970)
		 );

		if (!empty($default)) 
			$pm->addWarning('Please make sure your server has the correct timezone setting, and then choose your local timezone on the Admin|Preferences page. Using default timezone: America/Los_Angeles');
		elseif (!empty($server))
			$pm->addWarning('Please make sure your server has the correct timezone setting, and then choose your local timezone on the Admin|Preferences page. Using default timezone found on this server using "' . $fn . '": ' . $dateTimezone);

		return $dateTimezone;
	}
}
