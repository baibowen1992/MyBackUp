<?













class ZMC_Startup
{
public static function startup(ZMC_Registry_MessageBox $pm, $argv)
{
	if (empty($argv) || empty($argv[1]))
		return $pm->addError(__FILE__ . ': missing argument');

	if ($argv[1] === 'post')
	{
		$pm->addMessage("Preparing ZMC DB for use by ZMC.");
		self::sql_time_limit($pm);
		
	}
	else
	{
		$pm->addMessage("Preparing to start ZMC DB.");
		if (file_exists('/etc/zmanda/zmc/zmc_aee/device_profiles.yml'))
		{
			$pm->addError('This version of ZMC does not include support for /etc/zmanda/zmc data from previous releases of ZMC.');
			return true;
		}
		$oldConfigs = glob('/etc/amanda/*/advanced.conf');
		if (!empty($oldConfigs))
		{
			$pm->addError('This version of ZMC does not include support for ZMC Backup Sets (/etc/amanda configurations) from previous releases of ZMC.');
			return true;
		}
		if (self::initZmcOverrides($pm))
			return true;
		if (self::setPath($pm))
			return true;
	}
	echo ZMC::debugLog(__FILE__ . __LINE__ . ': Done ');
}

public static function sql_time_limit($pm)
{
	try {
		
		$sql_time_limit = ZMC::$registry->sql_time_limit;
		ZMC_Mysql::query("SET GLOBAL event_scheduler=ON");
		ZMC_Mysql::query("DROP EVENT IF EXISTS sql_time_limit");
		if ($sql_time_limit < 5)
			return;
		ZMC_Mysql::query("CREATE EVENT IF NOT EXISTS sql_time_limit
			ON SCHEDULE EVERY 90 SECOND
			ON COMPLETION PRESERVE ENABLE
			DO CALL sql_time_limit($sql_time_limit)");
	} catch (Exception $e) {
		$pm->addError("$e");
	}
}

public static function initZmcOverrides(ZMC_Registry_MessageBox $pm)
{
	$overrides = array('amanda_version' => null); 
	$overrides['amanda_release'] = trim(file_get_contents($fn = ZMC::$registry->cnf['amanda_release_file'], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES));

	if (is_readable('/etc/resolv.conf') && (preg_match('/^search.*wocloud.cn/m', file_get_contents('/etc/resolv.conf'))))
		$overrides['qa_team'] = true;
	elseif(ZMC::$registry->qa_team)
		$overrides['qa_team'] = false;

	if (self::getApachePort($pm, $overrides, $port, $http, $ignored1, $ignored2))
		return true;
	self::getAmandaRevision($pm, $overrides);
	self::checkRelease($pm, $overrides);
	if (self::getSvnInfo($pm, $overrides))
		return true;
	ZMC::$registry->setOverrides($overrides);
}

public static function checkRelease(ZMC_Registry_MessageBox $pm, array &$overrides) 
{
	foreach(array(
		'/etc/redhat-release' => 'RPM',
		'/etc/debian_version' => 'Debian',
		'/etc/lsb-release' => 'Ubuntu',
		
	) as $fn => $type)
		if (file_exists($fn))
			$release = trim(file_get_contents($fn));

	if (empty($release))
		$release = trim(exec('uname -sr'));

	if (empty($release))
	{
		$pm->addWarning('Unable to determine platform type (e.g. Linux, Solaris, etc.).');
		return;
	}

	if (!empty(ZMC::$registry['distro_release']))
	{
		if (ZMC::$registry['distro_release'] === $release)
			return;
		
		$rnew = substr($release, -2);
		$rold = substr(ZMC::$registry['distro_release'], -2);
		if ($rnew === '64' || $rold === '64')
			if ($rnew !== $rold)
				$bit = 'FYI, changing betwee 32 bit and 64 bit host OS requires re-installing Amanda Enterprise. ';
		ZMC::auditLog('The host OS has been changed from "' . ZMC::$registry['distro_release']
			. '" to "' . $release . '"). ' . ZMC::$registry->support_email_html);
	}

	$overrides['distro_release'] = $release;
}

private static function getAmandaRevision(ZMC_Registry_MessageBox $pm, array &$overrides)
{
	
	
	
	
	try
	{
		ZMC_ProcOpen::procOpen('amadmin', $cmd = ZMC::getAmandaCmd('amadmin'), $args = array('show', 'version'),
			$stdout, $stderr, '"amadmin show version" failed unexpectedly');
	}
	catch (ZMC_Exception_ProcOpen $e)
	{
		$pm->addError("Installation problem: $stderr \n $stdout");
		ZMC::checkAmandaLibs($pm);
		return true;
	}
	$pos = strpos($stdout, 'BUILT_REV');
	$revision = intval(substr($stdout, $pos + 11, 6));
	$pos = strpos($stdout, 'BUILT_DATE');
	$date = substr($stdout, $pos + 12, 28);
	$date = date_parse($date);
	$overrides['amanda_svn_info'] = "$date[year]-$date[month]-$date[day]:r$revision";
}






private static function getSvnInfo(ZMC_Registry_MessageBox $pm, array &$overrides)
{
	$svnrev = ZMC::$registry->svn->zmc_svn_revision;
	$overrides['zmc_build_version'] = ZMC::$registry->svn->zmc_build_version;
	$overrides['zmc_svn_info'] = substr(ZMC::$registry->svn->zmc_svn_build_date, 0, 16) . 'R' . $svnrev;
   	if (!ZMC::$registry['debug'] && (ZMC::$registry->svn->zmc_build_branch !== 'tags')) 
		
		$overrides['sticky_restart'] = 'This build of ZMC is not an official release, and DEBUG mode is OFF.';

	if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . '.svn'))
		$overrides['svn_overlay'] = '<b style="color:red">Subversion Overlay Detected!</b>';

	if (!ZMC::$registry->offsetExists('upgrade_zmc_from_revision'))
		if (ZMC::$registry->offsetExists('zmc_svn_revision'))
			if (ZMC::$registry->zmc_svn_revision < $svnrev)
				ZMC::$registry->upgrade_zmc_from_revision = ZMC::$registry->zmc_svn_revision;
			elseif (ZMC::$registry->zmc_svn_revision > $svnrev)
				ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex("Downgrading ZMC from " . ZMC::$registry->zmc_svn_revision . " to $svnrev is not supported."), __FILE__, __LINE__);

	$overrides['zmc_svn_revision'] = $svnrev;
}


public static function getApachePort($pm, &$overrides, &$defaultPort, &$defaultHttp, &$httpPort, &$httpsPort, $filename = '/opt/zmanda/amanda/apache2/conf/ssl.conf')
{
	if (false === ($lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
	{
		$pm->addError("Unable to read file $filename.");
		return true;
	}
	$default = false;
	foreach($lines as $line)
	{
		if (!strncasecmp(trim($line), 'LISTEN', 6))
		{
			if ($pos = strrpos($line, ':'))
			{
				$httpsPort = trim(substr($line, $pos), ':');
				$ip = trim(str_ireplace('listen', '', substr($line, 0, $pos)));
				if ($ip === '127.0.0.1')
					$yasumiPortOk = true;
			}
			else 
			{
				$httpsPort = intval(substr(trim($line), 6));
				$yasumiPortOk = true;
			}
		}
		elseif (!strncasecmp($line, '<virtualhost', 12)) 
		{
			$vhost = strtok($line, " \t"); 
			$vhost = strtok(" \t"); 
			$parts = explode(":", $vhost);
			$host = trim($parts[0]);
			switch($host)
			{
				case '_default_':
				case '127.0.0.1':
				case 'localhost.localdomain':
				case 'localhost':
				case '*':
				case '0:0:0:0:0:0:0:1':
				case '::1':
					$default = true;
					break;
			}
		}
	}

	$url = 'http://network.wocloud.cn/lore/article.php?id=';
	if ($default !== true) 
	{
		$pm->addError('ZMC Apache <virtualhost ...> configuration is broken. There must be a <virtualhost _default_:###> or <virtualhost 127.0.0.1:###>.  If you need ZMC Apache to only LISTEN on certain IPs, do not change <virtualhost>, but instead ADD a LISTEN 127.0.0.1:443, and another LISTEN for the IP needed.');
		$pm->addError("Please see: <a href='{$url}367' target='_blank'>{$url}367</a>");
		$release = exec('uname -s');
		if (false !== stripos($release, 'sunos'))
			$pm->addError("Please see: <a href='{$url}318' target='_blank'>{$url}318</a>");
		$exit = true;
	}
	if (empty($yasumiPortOk))
	{
		$pm->addError('ZMC Apache must have a LISTEN 127.0.0.1:PORT, where PORT is the same port as all other LISTEN directives in ssl.conf for Apache (typically 443).');
		$pm->addError("Please see: <a href='{$url}367' target='_blank'>{$url}367</a>");
		$exit = true;
	}
	if (!empty($exit))
		return true;

	if (false === ($lines = file($filename = str_replace('ssl', 'httpd', $filename), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
	{
		$pm->addError("Unable to read file: $filename");
		return true;
	}

	foreach($lines as $line)
	{
		$line = trim($line);
		if (!strncasecmp($line, 'LISTEN', 6))
		{
			$defaultPort = $httpPort = intval(substr($line, 6));
			$defaultHttp = 'http';
		}
		elseif (!strncasecmp($line, 'DocumentRoot', 12) && strpos($line, 'Common/redirect'))
		{
			$defaultPort = $httpsPort;
			$defaultHttp = 'https';
		}
	}

	if ($defaultHttp === 'http' && $defaultPort == '80')
		$defaultPort = '';
	elseif ($defaultHttp === 'https' && $defaultPort == '443')
		$defaultPort = '';

	if (!(intval($httpsPort) > 0))
	{
		$pm->addError("Invalid Apache HTTPS port '$httpsPort' found in Apache's config.");
		return true;
	}

	if (!is_object(ZMC::$registry))
		return;
	if ($httpsPort !== ZMC::$registry->apache_https_port)
		$overrides['apache_https_port'] = $httpsPort;
	if ($httpPort !== ZMC::$registry->apache_http_port)
		$overrides['apache_http_port'] = $httpPort;
}

private static function setPath(ZMC_Registry_MessageBox $pm)
{
	$PATHS = array();
	$base = ZMC::$registry->cnf->zmc_pkg_base;
	
	$paths = array( 
		$base . 'bin',
		$base . 'common/bin',
		$base . 'perl/bin',
		$base . 'mysql/bin',
		'/opt/csw/bin',
	);
	foreach (explode(':', getenv('PATH')) as $path) 
		$paths[$path] = $path;
	foreach ($paths as $path)
		if (is_dir($path))
			$PATHS[$path] = $path;

	$PATH = implode(':', array_reverse($PATHS)) . "\n"; 
	$content = 'PATH=' . implode(':', $PATHS) . "\nLC_ALL=" . getenv('LC_ALL') . "\n";
	if (false === (file_put_contents($fn = ZMC::$registry->etc_zmanda_product . 'path.cnf', $content)))
	{
		$pm->addError("Unable to update $fn. ZMC may not function correctly.");
		return true;
	}
}
}
