<?


















require 'ZMC/Exception.php';

class ZMC
{
	


	public static $registry = null; 

	public static $userRegistryStarted = false;
	
	
	const TMP_PATH = '/etc/zmanda/zmc/zmc_aee/data/'; 

	


	public static $userRegistry = null; 

	


	public static $started = null;
	
	
	public static function session_start($file, $line)
	{
		if (!empty($GLOBALS['disable_sessions']))
		{
			error_log(__FILE__.__LINE__. " session_start($file, $line), but GLOBALS[disable_sessions] set");
			return;
		}
		
		

		session_name('ZMCaee');
		ZMC_Error::$session_status_hack = false;
		$GLOBALS['session_request_file'] = $file;
		$GLOBALS['session_request_line'] = $line;
		$return = session_start();
		$result = ZMC_Error::$session_status_hack;
		if (!$result && $return)
		{
			$GLOBALS['session_started'] = true;
			$GLOBALS['session_started_file'] = $file;
			$GLOBALS['session_started_line'] = $line;
			$reply = 'OK';
			return true;
		}
		if (empty($GLOBALS['session_started']))
			error_log('pid ' . posix_getpid() . " session_start($file, $line) ERROR - GLOBALS['session_started'] not set, but session already started, or session_start() failure");
		return false;
	}

	
	public static function session_write_close($file, $line)
	{
		if (false) 
		{
			error_log('pid ' . posix_getpid() . " 3session_write_close($file, $line) attempting ...");
			ZMC_Error::$session_status_hack = false;
			$GLOBALS['session_request_file'] = __FILE__;
			$GLOBALS['session_request_line'] = __LINE__;
			session_start(); 
			$result = ZMC_Error::$session_status_hack; 
			if ($result) 
				error_log('pid ' . posix_getpid() . " 3session_write_close($file, $line) OK");
			else 
				error_log('pid ' . posix_getpid() . " 3session_write_close($file, $line) ERROR - session not started");
		}
		session_write_close(); 
		unset($GLOBALS['session_started']);
		unset($GLOBALS['session_started_file']);
		unset($GLOBALS['session_started_line']);
	}

	
	public static function startup($autoload = null)
	{
		if (self::$started)
			throw new ZMC_Exception('ZMC already started!');

		self::$started = true;
		mb_internal_encoding("UTF-8");
		if (!mb_detect_order("ASCII,JIS,UTF-8,EUC-JP,SJIS"))
			ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex('PHP mb_detect_order() failure'), __FILE__, __LINE__);
		if (empty($GLOBALS['session_started']))
			ZMC::session_start(__FILE__, __LINE__);

		require 'ZMC/Loader.php';
		if ($autoload || empty($GLOBALS['BootstrapRegistry']['disable_autoloader']))
			ZMC_Loader::register();
		$pm = new ZMC_Registry_MessageBox();
		ZMC_ConfigHelper::getRegistry($pm, 'aee');
		if (!empty($_SESSION['user_id']))
			self::getUserRegistry();

		ignore_user_abort();
		return $pm;
	}

	





	public static function getUserRegistry()
	{
		if (ZMC::$registry['user_registry'] === 'files')
		{
			$filename = ZMC::TMP_PATH . DIRECTORY_SEPARATOR . intval($_SESSION['user_id']);
			ZMC::$registry['user_orig'] = null;
			$reg = false;
			if (is_readable($filename)) 
				$reg = unserialize(ZMC::$registry['user_orig'] = file_get_contents($filename)); 
			ZMC::$registry['user'] = ($reg ? $reg : array());
		}
		else
			throw new ZMC_Exception('Unsupported storage mode for "user_registry": ' . ZMC::$registry['user_registry']);
	}

	







	public static function perControllerStartup($tombstone, $subnav)
	{
		$subnav = "$tombstone.$subnav";
		if (empty($_SESSION['user_id']))
			return;

		if (!isset(ZMC::$registry['user']))
			ZMC::$registry['user'] = array();
		if (!isset(ZMC::$registry['user'][$subnav])) 
			ZMC::$registry['user'][$subnav] = new ZMC_Registry();

		ZMC::$userRegistry = & ZMC::$registry['user'][$subnav]; 
		self::$userRegistryStarted = true;
	}

	






	public static function shutdown($quit = true, $strict = true)
	{
		if (self::$started !== true)
		{
			if ($strict)
				throw new ZMC_Exception('ZMC not started or already shutdown!');
			if ($quit)
				exit;

			return;
		}
		self::$started = false;

		if (empty($_SESSION['user_id']) || !isset(ZMC::$registry['user'])) 
			exit;

		if (!empty(ZMC::$userRegistry) && self::$userRegistryStarted !== true)
			throw new ZMC_Exception("userRegistry used, but not started (see ZMC_HeaderFooter_Aee::header() docblock, add $subnav).  userRegistry=" . print_r(ZMC::$userRegistry, true));

		$del = null;
		foreach(array_keys(ZMC::$registry['user']) as $tombstone => $controllerRegistry)
			if (empty(ZMC::$registry->user[$tombstone]))
				unset(ZMC::$registry->user[$tombstone]);

		$serialized = serialize(ZMC::$registry['user']);
		if (empty(ZMC::$registry['user_orig']) || ZMC::$registry['user_orig'] !== $serialized) 
		{
			if (ZMC::$registry['user_registry'] === 'files')
			{
				if (false === file_put_contents($filename = ZMC::TMP_PATH . DIRECTORY_SEPARATOR . intval($_SESSION['user_id']), $serialized, LOCK_EX))
					throw new ZMC_Exception("Internal Error: Unable to write: '$filename' " . self::getFilePermHelp($filename));
			}
			else
				throw new ZMC_Exception('Unsupported storage mode for "user_registry": ' . ZMC::$registry['user_registry']);
		}

		ZMC_Timer::logElapsed();
		ZMC::debugLog('MySQL Total Queries: ' . ZMC_Mysql::$queries);
		if (!$quit)
			return;

		$cacheFn = 'logSpaceUsageStats';
		if (self::useCache(null, null, $cacheFn, false, 300))
			self::logSpaceUsageStats();

		exit;
	}

	private static function logSpaceUsageStats()
	{
		if (is_readable('/proc/meminfo'))
			ZMC::debugLog("Using /proc/meminfo instead of vmstat:\n" . file_get_contents('/proc/meminfo'));
		elseif (empty($_SESSION['vmstat_off']))
		{
			try
			{
				ob_end_flush();
				flush();
				ZMC_ProcOpen::procOpen('vmstat', 'vmstat',  array(), $stdout, $stderr);
				ZMC::debugLog("vmstat:\n$stdout $stderr");
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				ZMC::debugLog("vmstat/df failed: $e");
				$_SESSION['vmstat_off'] = true;
			}
		}
		try
		{
			ZMC_ProcOpen::procOpen('df', 'df', array('-h'), $stdout, $stderr, null, null, null, ZMC::$registry['proc_open_ultrashort_timeout']);
			ZMC::debugLog($msg = "Mounted filesystem space usage ('df -h'):\n$stdout $stderr");
			$options = ('solaris' === ZMC::$registry->platform) ? array('-o', 'i') : array('-hi');
			ZMC_ProcOpen::procOpen('df', 'df', $options, $stdout, $stderr, null, null, null, ZMC::$registry['proc_open_ultrashort_timeout']);
			ZMC::debugLog($msg = "Mounted filesystem inode usage ('df " . implode(' ', $options) . "'):\n$stdout $stderr");
			return $msg;
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			ZMC::debugLog($df = "'df -h' failed: $e");
			return $df;
		}
	}
	
	public static function inputLog()
	{
		if (!ZMC::$registry->qa_mode)
		{
			$filteredGet = self::filterPasswords($_GET);
			$filteredPost = self::filterPasswords($_POST);
			$filteredSession = self::filterPasswords($_SESSION);
		}
		$export = json_encode(array(
			'apache' => date('D M d G:i:s Y'),
			'date' => ZMC::dateNow(true),
			'time' => time(),
			'pid' => posix_getpid(),
			'request_uri' => $_SERVER['REQUEST_URI'],
			'get' => $_GET,
			'post' => $_POST,
			'session' => $_SESSION)) . "\n";
		if (false === file_put_contents(ZMC::$registry->input_log, $export, FILE_APPEND))
			throw new ZMC_Exception(ZMC::getFilePermHelp(ZMC::$registry->input_log));
		if (!ZMC::$registry->qa_mode)
		{
			self::unfilterPasswords($filteredPost);
			self::unfilterPasswords($filteredGet);
			self::unfilterPasswords($filteredSession);
		}
	}

	private static function filterPasswords(&$export)
	{
		$filtered = array();
		self::filterPasswordsWrapped($export, $filtered);
		foreach($filtered as $triple)
		{
			$key = $triple[1];
			unset($triple[0][$key]);
			$triple[0][$key] = 'censored';
		}
		return $filtered;
	}

	private static function filterPasswordsWrapped(&$export, &$filtered)
	{
		foreach($export as $key => &$value)
			if (is_array($value)) 
				self::filterPasswordsWrapped($value, $filtered);
			elseif (false !== stripos($key, 'password'))
				$filtered[] = array(&$export, $key, &$value);
	}

	private static function unfilterPasswords($filtered)
	{
		foreach($filtered as $triple)
			$triple[0][$triple[1]] = &$triple[2];
	}

	






	public static function debugLog($message, $code = null, $logInfo = null, $level = null)
	{
		if ($level === null)
			$level = ZMC_Error::DEBUG;
		return self::log($message, $code, $logInfo, (ZMC::$registry ? ZMC::$registry->debug_log : null), $level);
	}

	






	public static function errorLog($message, $code = null, $logInfo = null, $level = null)
	{
		if ($level === null)
			$level = ZMC_Error::ERROR;
		return self::log($message, $code, $logInfo, (ZMC::$registry ? ZMC::$registry->error_log : null), $level);
	}

	






	public static function auditLog($message, $code = null, $logInfo = null, $level = null)
	{
		if ($level === null)
			$level = ZMC_Error::NOTICE;
		return self::log($message, $code, $logInfo, (ZMC::$registry ? ZMC::$registry->audit_log : null), $level);
	}

	


















	public static function log($message, $code, $logInfo, $copyTo, $level = null)
	{
		if (is_array($message))
		{
			$result = '';
			foreach($message as $oneMessage)
				if (!empty($oneMessage))
					$result = self::log($oneMessage, $code, $logInfo, $copyTo, $level);
			return $result;
		}

		if (strlen($message) > 4096) 
			$message = substr($message, 0, 4096);

		$message = trim($message);
		$userId = $username = $facility = $tags = $config = '';

		if (isset($_SESSION['user']))
		{
			$username = $_SESSION['user'];
			$userId = $_SESSION['user_id'];
		}

		if (isset($_SESSION['configurationName']))
			$config = $_SESSION['configurationName'];

		if (!empty($logInfo))
		{
			if (!empty($logInfo['level']))
				$level = $logInfo['level'];
			if (!empty($logInfo['user']))
				$username = $logInfo['user'];
			if (!empty($logInfo['username']))
				$username = $logInfo['username'];
			if (!empty($logInfo['user_name']))
				$username = $logInfo['user_name'];
			if (!empty($logInfo['user_id']))
				$userId = $logInfo['user_id'];
			if (!empty($logInfo['facility']))
				$facility = $logInfo['facility'];
			if (!empty($logInfo['tags']))
			{
				if (is_array($logInfo['tags']))
					$tags = join('_', array_keys($logInfo['tags']));
				else
					$tags = $logInfo['tags'];
			}
			if (!empty($logInfo['config']))
				$config = $logInfo['config'];
		}

		$status = 'OK';
		if (($code === false) || (is_integer($code) && $code !== 0))
			$status = 'ERROR';

		$pid = ZMC::getPid();
		$message = self::dateNow() . '|' . (ZMC::$registry['tz']['offsetSeconds'] / 60) . "|$pid|$username|$config|$message|$status|$userId|$code|$facility|$tags|$level\n";
		
		if (($level < ZMC_Error::NOTICE) || ($copyTo === null))
			error_log($message);
		if ($copyTo)
			error_log($message, 3, $copyTo); 

		return $message;
	}

	




	public static function renderException(Exception $exception = null)
	{
		if ($exception === null)
			return;

		ZMC_Loader::renderTemplate('SystemError', array(
			'exception' => $exception,
			'code' => $exception->getCode(),
			'user_messages' => ($exception instanceof ZMC_Exception) ? $exception->getUserMessages() : $exception->getMessage(),
			'raw_message' => $exception->getMessage()
		));
	}

	















	public static function quit($data = null)
	{
		if ((self::$started === true) && !ZMC::$registry->debug)
		{
			if (is_string($data))
				error_log($code = substr($data, 0, 2047));
			else
				error_log($code = substr(print_r($data, true), 0, 2047));
			if (!($data instanceof Exception))
				error_log(substr(print_r(debug_backtrace(), true), 0, 2047));
			ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex("$code"), __FILE__, __LINE__);
		}
		echo "<div style='margin:1em'>\n";
		if ($data instanceof ZMC_Registry)
			$data->displayKeys();
		$back = debug_backtrace();
		echo "<hr>ZMC::quit() called from ", $back[0]['function'], " in ", $back[0]['file'], " line #", $back[0]['line'];
		if (PHP_SAPI != 'cli')
			echo "\n\n\n<div style='overflow:auto; word-wrap:break-word; white-space: pre-wrap; font-family: Arial, Helvetica, Courier, monospace; font-size: 8pt; color:black'>\n";

		if (!class_exists('ZMC_Loader', false))
			print_r($data);
		else
		{
			if (is_array($data)) 
			{
				reset($data);    
				if (is_integer(key($data))) 
				{
					self::dump($data, "<br />Dumping var: <br />\n");
					$data = null;
				}
			}

			if ($data !== null)
			{
				if (is_array($data))
					foreach($data as $name=>$var)
						self::dump($var, '<br />Dumping var <b>' . ($name === '' ? 'empty string' : $name) . "</b>: <br />\n");
				else
					self::dump($data);
			}
		}
		array_shift($back);
		echo '<br /><h3>Abridged Backtrace</h3><pre>', ZMC_Error::backtrace(0, $back);
		
		echo '</pre></div></div></div></div></div></div></div></html>';
		exit; 
	}

	





	public static function escape($data, $trim = false)
	{
		if (!is_array($data))
		{
			if ($trim)
				$data = trim($data);

			
			if( $data === 0 || $data === '0' )
				return '0'; 

			if (empty($data))
				return '';

			
			$result = $data;
			if (!empty($result))
				return($result);

			self::convert($data);
			return $data;
		}

		$result = '';
		foreach ($data as $val)
		{
			if ($trim)
				$val = trim($val);

			if (!empty($val))
			{
				
				$escaped = $val;
				if (empty($escaped))
					self::convert($escaped);
				$result .= $escaped;
			}
		}
		return $result;
	}

	private static function convert(&$string)
	{
		if (false === ($encoding = mb_detect_encoding($string)))
			throw new ZMC_Exception("Unknown character encoding (can not proceed):  $string");

		if ($encoding === 'UTF-8' || $encoding === 'ASCII')
			return;

		$test = "Test original text for validity using:\niconv -f UTF-8 file_containing_text -o /dev/null";
		if (false === ($converted = mb_convert_encoding($string, 'UTF-8', $encoding)))
			throw new ZMC_Exception("Conversion to UTF-8 failed (can not proceed):  $string\n$test");

		$string = htmlspecialchars($converted, ENT_QUOTES, 'UTF-8');
		if (empty($string))
			throw new ZMC_Exception("Formatting string for UTF-8 display failed (can not proceed):  $converted\n$test");
	}

	public static function inputFilter(ZMC_Registry_MessageBox $pm, &$string, $removeBackslashes = false)
	{
		if (ZMC::$registry->input_filters === false)
			return;

		if (false === ($encoding = mb_detect_encoding($string)))
		{
			$s = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
			return $pm->addEscapedError("Invalid/unknown character encoding: " . (empty($s) ? $string:$s));
		}

		if ($encoding !== 'UTF-8' && $encoding !== 'ASCII')
		{
			if (false === ($converted = mb_convert_encoding($string, 'UTF-8', $encoding)))
			{
				$s = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
				return $pm->addEscapedError("Conversion to UTF-8 failed: " . (empty($s) ? $string:$s));
			}
			$string = $s;
		}

		if ($removeBackslashes)
			if (false !== mb_strpos($string, '\\'))
				$string = mb_ereg_replace('\\\\', '', $string);

		$string = trim($string);
	}

	




	public static function headerRedirect($url, $file, $line)
	{
		if (empty($url))
			throw new ZMC_Exception('Missing URL');

		$url .= strpos(substr($url, 0, 800), '?') ? '&' : '?';
		$url = 'Location: ' . self::getUrl($url) . 'file=' . urlencode($file) . '&line=' . intval($line);
		ZMC::debugLog("Redirecting to '$url' ($file:$line). ");
		ZMC_Error::flushObs();
		header($url);
	}

	


	public static function getUrl($url = '', $minimalPort = true)
	{
		$http = 'http';
		$port = ':' . $_SERVER['SERVER_PORT'];
		
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
		{
			$http = 'https';
			if ($pos = strpos($_SERVER['HTTP_HOST'], ':'))
				$port = ':' . substr($_SERVER['HTTP_HOST'], $pos+1);
		}

		if ($minimalPort)
		{
			if ($http === 'http' && $port == ':80')
				$port = '';
			elseif ($http === 'https' && $port == ':443')
				$port = '';
		}

		
		
		$host = explode(':', $_SERVER['HTTP_HOST']); 
		return "$http://$host[0]$port$url";
	}

	public static function getPageUrl(ZMC_Registry $pm, $tombstone = null, $subnav = null, $link = null)
	{
		if (empty($link) && ($tombstone === $pm->get('tombstone', null)) && ($subnav === $pm->get('subnav', null)))
			return 'form below'; 

		$t = $tombstone ? $tombstone : $pm->get('tombstone', $tombstone);
		$s = $subnav ? $subnav : $pm->get('subnav', $subnav);
		if ($link === null)
			$link = "$t|$s page";
		if (is_object(ZMC_HeaderFooter::$instance))
			return '<a href="' . self::getUrl('/' . ZMC_HeaderFooter::$instance->getUrl($t, $s)) . "\">$link</a>";
		else
			return "$t|$s";
	}

	
	public static function getPid()
	{
		if (isset($_SERVER['WINDIR']))
			return time();
		else
			return posix_getpid();
	}

	public static function kill($pid, $signal)
	{
		if (isset($_SERVER['WINDIR']))
			return false; 
		else
			posix_kill($pid, $signal);
	}

	





	public static function escapedJson($data)
	{
		if (!is_array($data))
			$data = (array)$data;
		$escaped = addcslashes(json_encode($data), '\\\'');
		return $escaped;
	}

	public static function getAmandaCmd($cmd)
	{
		return self::getToolPath(ZMC::$registry->cnf->amanda_bin_path, $cmd);
	}

	public static function getZmcTool($cmd)
	{
		return self::getToolPath(ZMC::$registry->cnf->zmc_bin_path, $cmd);
	}

	private static function getToolPath($dir, $cmd)
	{
		$cmd = $dir . DIRECTORY_SEPARATOR . $cmd;
		if (!is_readable($dir) || !is_executable($dir))
			if (file_exists($dir))
				throw new ZMC_Exception("Installation problem: $dir permissions issue");
			else
				throw new ZMC_Exception("Installation problem: $dir missing, when looking for $cmd");

		if (!file_exists($cmd))
		{
			throw new ZMC_Exception("Installation problem: '$cmd' missing");
		}
		elseif (!is_executable($cmd))
			throw new ZMC_Exception("Installation problem: $cmd permissions issue");

		return $cmd;
	}

	









	public static function array_move(&$from, &$to, $keys)
	{
		foreach($keys as $oldKey => $newKey)
		{
			$key = (is_integer($oldKey) ? $newKey : $oldKey);
			if (array_key_exists($key, $from)) 
			{
				$to[$newKey] =& $from[$key];
				unset($from[$key]);
			}
		}
	}

	public static function escapeGet($get, $arrayOk = true)
	{
		$result = $sep = '';
		foreach($get as $key => $value)
		{
			if ($value instanceof ArrayObject)
				$value = $value->getArrayCopy();
			if (is_array($value))
			{
				if (!$arrayOk)
					throw new ZMC_Exception(__FUNCTION__ . "($result, Array not allowed)");
				elseif (count($value) === 1)
				{
					$vv = current($value);
					if (is_array($vv) || ($vv instanceof ArrayObject))
						$vv = self::escapeGet($vv, $arrayOk);
					$result .= $sep . $key . '=array(' . key($value) . "=>$vv)";
				}
				else
					$result .= $sep . $key . '=ArraySkipped';
			}
			elseif ($value === NULL)
				$result .= $sep . $key . '=';
			elseif (is_string($value) || is_float($value) || is_int($value))
				$result .= $sep . $key . '=' . urlencode($value);
			
			$sep = '&';
		}
		return $result;
	}

	static $httpGetCount = 0;
	public static function httpGet(&$rawResult, &$details, &$httpCode, $url, $headers = array(), $post, $cainfo = null, $capath = null)
	{
		self::$httpGetCount++;
		reset($headers);
		foreach($headers as $key => &$header)
			if (is_string($key))
				$header = ucfirst(strtolower($key)) . ": $header";
		if (is_array($post))
			$post = json_encode($post);
		$ch = curl_init();
		if (empty($cainfo))
			$cainfo = ZMC::$registry->curlopt_cainfo;
		if (empty($capath))
			$capath = ZMC::$registry->curlopt_capath;
		if (!is_readable($cainfo))
			throw new ZMC_Exception("Unable to read '$cainfo'. Please check permissions/existence of this file and parent directories.");
		curl_setopt_array($ch, array(
			CURLOPT_URL				=> $url,
			CURLOPT_HEADER			=> 1,
			CURLOPT_HTTPHEADER		=> $headers,
			CURLOPT_CONNECTTIMEOUT	=> ZMC::$registry->proc_open_ultrashort_timeout,
			CURLOPT_TIMEOUT			=> ZMC::$registry->proc_open_ultrashort_timeout,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_SSL_VERIFYPEER	=> 0, 
			CURLOPT_CAINFO			=> $cainfo,
			CURLOPT_CAPATH			=> $capath,
		));
		if (!empty($post))
			curl_setopt_array($ch, array(
				CURLOPT_POST			=> 1,
				CURLOPT_POSTFIELDS		=> $post,
			));
		if (ZMC::$registry->dev_only) file_put_contents('/tmp/j' . __FUNCTION__ . self::$httpGetCount, print_r(array('url' => $url, 'post' => $post), true));
		$rawResult = curl_exec($ch);
		
		
		
		$curlErr = curl_errno($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$details = '';
		if ($curlErr)
		{
			if ($curlErr > 4 && $curlErr < 8)
			{
				$urlParts = parse_url($url);
				$details .= " Unable to connect to $urlParts[host]; ";
				$connectFailure = true;
			}
			$details .= $curlErr . ':' . curl_error($ch) . '; ';
		}
		curl_close($ch);
		ZMC::auditLog(print_r($headers, true) . ";$url?" . (ZMC::$registry->dev_only ? $post:preg_replace('/"password":[^"]+/', 'password":"filtered', substr($post, 0, 256))) . "; code:$httpCode; error:$details; raw result:$rawResult",
			$curlErr, array('facility' => __CLASS__, 'tags' => array('ZN' => true, 'external' => true))
		);
		if (empty($details) && empty($rawResult))
			$details = 'Empty response received.';
	}

	
	public static function rmrdir($dirname)
	{
		if ($dirname === '/') ZMC::quit($dirname); 
		$dirname = rtrim($dirname, '/');
		if ($result = self::sanityCheckPath($dirname, __FUNCTION__, 'Can not delete directory'))
			return $result;

		if (false === ($lines = file($deny = ZMC::$registry->etc_zmanda . 'zmc_aee/delete.deny', FILE_IGNORE_NEW_LINES)))
			return "Unable to read file '$deny'.";

		if (!empty($lines))
			foreach($lines as $line)
				if ($line === $dirname)
					return "Refusing to delete '$dirname'.  Denied by '$deny'. If you are certain, then manually delete the directory.";

		$cwd = getcwd();
		$result = self::rmrdir_wrapped($dirname);
		chdir($cwd);
	}

	private static function rmrdir_wrapped($dirname)
	{
		if (false === (@$dirHandle = opendir($dirname)))
			return (is_dir($dirname) ? "Can not delete '$dirname'.  Missing 'x' permission?" . self::getDirPermHelp($dirname) : false);
		chdir($dirname);
		while ($file = readdir($dirHandle))
		{
			if ($file == '.' || $file == '..')
				continue;
			if (is_dir($file))
			{
				if ($result = self::rmrdir_wrapped($file))
					return $result;
			}
			elseif (!unlink($file)) return "Unable to delete '$file'";
		}
		closedir($dirHandle);
		chdir('..');
		return (rmdir($dirname) ? false : "Unable to unlink '$dirname'");
	}

	public static function lfs_du($dirname, $recursing=false)
	{
		try
		{ 
			$total = '0';
			$cwd = getcwd();
			chdir($dirname);
			ZMC_ProcOpen::procOpen('du', 'du', array('-bs', $dirname),$stdout, $stderr, 'du command failed unexpectedly');
			chdir($cwd);
			return $total = (int)$stdout;
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
                	ZMC::errorLog(__FUNCTION__ . "()du command failed unexpectedly: $stderr on '$dirname': " . posix_strerror($errno));
		}



	}

	public static function du($dirname, $recursing=false)
	{
		if (!$recursing)
			if ($result = self::sanityCheckPath($dirname, __FUNCTION__, "Can not calculate space usage for this directory"))
				return $result;

		if(ZMC::$registry->large_file_system === false)
			if (!is_readable($dirname) || !is_dir($dirname))
				return false;

		if (false === ($dirHandle = opendir($dirname)))
			return false;

		if (!$recursing)
			ZMC_Timer::logElapsed(__FUNCTION__, true);

		$total = '0';
		$cwd = getcwd();
		chdir($dirname);
		while ($file = readdir($dirHandle))
		{
			if ($file === '..')
				continue;

			if (!is_dir($file) || ($file === '.')) 
			{
				$filesize = sprintf('%u', filesize($file)); 
				$errno = posix_get_last_error();
				if ($errno)
					ZMC::errorLog(__FUNCTION__ . "() - filesize() yielded $errno on '$dirname / $file': " . posix_strerror($errno));
				if ($errno === 75)
					$filesize = '4294967296'; 
				$total = bcadd($total, $filesize);
			}
			elseif (is_dir($file))
				$total = bcadd($total, self::du($file, true));
		}
		closedir($dirHandle);
		chdir($cwd);
		if (!$recursing)
			ZMC_Timer::logElapsed(__FUNCTION__, false);
		return $total;
	}

	public static function isDirEmpty($dirname)
	{
		if ($result = self::sanityCheckPath($dirname, __FUNCTION__, 'Unable to check if this directory is empty'))
			return $result;

		if (!file_exists($dirname))
			return true;

		if (!($dirHandle = opendir($dirname)))
			return false;

		while ($file = readdir($dirHandle))
		{
			if ($file === '..' || $file == '.')
				continue;
			return true;
		}
		closedir($dirHandle);
		return false;
	}
 
	



	public static function dateNow($cached = false)
	{
		static $now = null;
		if ($cached === true && $now)
			return $now;

		if (!is_bool($cached))
			return date('YmdHis', $cached);

		return $now = date('YmdHis'); 
	}

	



	public static function humanDate($cached = false) 
	{
		static $now = null;
		if ($cached === true && $now)
			return $now;

		if (!is_bool($cached))
			return date('Y-m-d H:i:s', $cached);

		
		return $now = date('Y-m-d H:i:s'); 
	}

	public static function humanDate2AmandaDate($human) 
	{
		return substr(str_replace('-', '', (str_replace(':', '', $human))), 0, -2);
	}

	public static function amandaDate2humanDate($amanda) 
	{
		if (strlen($amanda) < 12)
			return '-';
		return substr($amanda, 0, 4) . '-' . substr($amanda, 4, 2) . '-' . substr($amanda, 6, 2) . ' ' . substr($amanda, 8, 2) . ':' . substr($amanda, 10, 2); 
	}

	
	public static function merge(&$result, array $array, $overwrite = true)
	{
		foreach ($array as $key => $value)
		{
			if ($value === null)
			{
				if ($overwrite)
					unset($result[$key]);
				continue;
			}

			if (!isset($result[$key]))
			{
				$result[$key] = $value;
				continue;
			}

			if (!is_array($result[$key]))
			{
				if ($overwrite)
					$result[$key] = $value;
			}
			else
			{
				if (is_array($value))
					self::merge($result[$key], $value);
				elseif ($overwrite)
					$result[$key] = $value;
			}
		}
	}

	
	
	public static function arrayAssocCmpRecursive(array $a1, array $a2)
	{
		foreach($a1 as $key => $value)
		{
			if (!isset($a2[$key])) 
				return false;
			if (is_array($value))
				if (!is_array($a2[$key]))
					return false;
				else
				{
					if (!self::arrayAssocCmpRecursive($value, $a2[$key]))
						return false;
				}
			if ($value !== $a2[$key])
				return false;
			unset($a2[$key]);
		}

		if (empty($a2))
			return true;
		return false;
	}

	public static function isArraySubsetDebug(array $subset, array $set)
	{
		foreach($subset as $key => $value)
		{
			if (!isset($set[$key]))
				ZMC::quit(array('reason' => "R1: !isset(set[$key]", 'subset' => $subset, 'set' => $set));

			if (is_array($value))
				if (is_array($set[$key]))
				{
					if (self::isArraySubset($value, $set[$key]))
						continue;
				}
				else
					ZMC::quit(array('reason' => "R2: !is_array(set[$key]", 'subset' => $subset[$key], 'set' => $set[$key]));

			if ($value !== $set[$key])
				ZMC::quit(array('reason' => "R3: subset[$key] = $value != set[$key]", 'subset' => $subset, 'set' => $set));
		}

		return true;
	}

	public static function isArraySubset(array $subset, array $set)
	{
		foreach($subset as $key => $value)
		{
			if (!isset($set[$key]))
				return false;

			if (is_array($value))
				if (is_array($set[$key]))
				{
					if (self::isArraySubset($value, $set[$key]))
						continue;
					else
						return false;
				}
				else
					return false;

			if ($value !== $set[$key])
				return false;
		}

		return true;
	}

	



	public static function mktime($zmcDate)
	{
		if (empty($zmcDate))
			return false;

		
		
		if ($zmcDate[4] === '-' && $zmcDate[7] === '-' && $zmcDate[10] === ' ')
			return mktime((strlen($zmcDate) > 12 ? intval($zmcDate[11].$zmcDate[12]) : 0), 
				(strlen($zmcDate) > 15 ? intval($zmcDate[14].$zmcDate[15]) : 0), 
				(strlen($zmcDate) === 19 ? $zmcDate[17].$zmcDate[18] : 0), 
				intval($zmcDate[5].$zmcDate[6]), 
				intval($zmcDate[8].$zmcDate[9]), 
				substr($zmcDate, 0, 4)); 

		if (!ctype_digit($zmcDate))
			throw new ZMC_Exception("Invalid date format: $zmcDate");

		
		
		
		return mktime(
			(strlen($zmcDate) > 9 ? intval($zmcDate[8].$zmcDate[9]) : 0),
			(strlen($zmcDate) > 11 ? intval($zmcDate[10].$zmcDate[11]) : 0),
			(strlen($zmcDate) === 14 ? $zmcDate[12].$zmcDate[13] : 0),
			intval($zmcDate[4].$zmcDate[5]),
			intval($zmcDate[6].$zmcDate[7]),
			substr($zmcDate, 0, 4));
	}

	




	public static function mergeKeys(&$array, $keys)
	{
		$result = array();
		foreach($array as $value)
			$result[array_shift($keys)] = $value;
		foreach($keys as $key)
			$result[$key] = '';
		$array = $result;
	}

	
	public static function isalnum_($s, $ok = '_')
	{
		return ctype_alnum(strtr($s, $ok, str_repeat('a', strlen($ok))));
	}

	





	public static function flattenArray(&$result, array $array, $recurse = true,  $path = '')
	{
		if (empty($path))
			$result = array();
		foreach($array as $key => $value)
			if ($recurse && is_array($value))
				
				
				
				self::flattenArray($result, $value, true, $path . $key . ':');
			else
				$result["$path$key"] = $value;
	}

	public static function &flattenArrays(array $arrays, $recurse = true)
	{
		$result = array();
		foreach($arrays as $key => $array)
			self::flattenArray($result[$key], $array, $recurse);
		return $result;
	}

	





	public static function unflattenArray(&$array, $filter = false)
	{
		foreach(array_keys($array) as $key)
			if (strpos($key, ':'))
			{
				
				if (!$filter || $array[$key] !== null)
					eval($eval = '$array[\'' . str_replace(':', "']['", $key) . '\']=$array[$key];');
				unset($array[$key]);
			}
			elseif($filter && $array[$key] === null)
				unset($array[$key]);
	}

	public static function assertColons(array $array)
	{
		foreach($array as &$val)
			if (is_array($val))
				if (ZMC::$registry->debug)
					ZMC::quit($array);
				else
					throw new ZMC_Exception('Internal Error');
	}

	public static function assertNoColons(array $array)
	{
		if (strpos(implode(';', array_keys($array)), ':'))
			if (ZMC::$registry->debug)
				ZMC::quit($array);
			else
				throw new ZMC_Exception('Internal Error');
	}

	
	
	public static function is_readwrite($fn = null, $expecting_dir = true)
	{
		if ($result = self::sanityCheckPath($fn, __FUNCTION__, null, $expecting_dir))
			return $result;

		if (!file_exists($fn))
		{
			
			
			return "File '$fn' does not exit.";
		}
		if(ZMC::$registry->large_file_system === false)
		{
			if ($expecting_dir && !is_dir($fn))
			{
				
				
				
			}
		}

		if (!empty(ZMC::$registry->readwrite_ignore_list[$fn]))
			return false; 

		
		
		if ($expecting_dir !== true)
		{
			$suffix = "by either 'amandabackup' user or '" . ZMC::$registry->cnf['amanda_group'] . "' group. If you believe '$fn' permissions are ok, and it exists, and is writable by the 'amandabackup' user, then add '$fn' to the whitelist on Admin|preferences page";
			if (!is_readable($fn))
			{
				
				
				return "File '$fn' does not look readable $suffix" . self::getFilePermHelp($fn);
			}

			if (!ZMC::is_writable($fn))
			{
				
				
				return "File '$fn' does not look writable $suffix" . self::getFilePermHelp($fn);
			}

			return false;
		}

		
		$result = file_put_contents($test = "$fn/.zmc_test_readwrite" . posix_getpid(), $contents = uniqid());
		if (strlen($contents) !== $result)
		   	$lastError = ZMC_Error::error_get_last();
		else
		{
			$result = file_get_contents($test);
			if ($result === false)
			   	$lastError = ZMC_Error::error_get_last();
			elseif (unlink($test) && ($result === $contents))
				return false;
		}
		return "The directory '$fn' exists, but the 'amandabackup' user can not create and delete files in this directory. " . self::getDirPermHelp($fn, $lastError);
	}

	public static function is_writable($fn)
	{
		if (!file_exists($fn))
			return false;
		if (is_writable($fn))
			return true;
		$stat = @stat($fn);
		if ($stat === false) 
			return true;
		$mode = base_convert($stats['mode'] & 438, 10, 8);
		$mode3 = substr($mode, -3);
		if ($mode3[2] === '6')
			return true;
		$amandaUser = ZMC::$registry->cnf['amanda_user'];
		$getpwnam = posix_getpwnam($amandaUser);
		if (($mode3[0] === '6') && ($stat['uid'] != $getpwnam['uid']))
			return true;
		if ($mode3[1] !== '6')
			return false;
		return (array_search($stat['gid'], posix_getgroups()));
	}

	private static function sanityCheckPath($dir, $function, $explanation = null, $expecting_dir = true)
	{
		if (empty($explanation))
			$explanation = 'Invalid ' . ($expecting_dir ? 'directory':'filename');

		if (($dir === null) || !is_string($dir) || ($dir === ''))
		{
			if (is_object(ZMC::$registry) && ZMC::$registry->debug)
				ZMC::quit("$function(): Invalid argument: $dir");
			else
				return "$explanation. Please specify a valid name.";
		}

		if (false !== strpos($dir, '/../') || (substr($dir, -3) === '/..'))
			return "$explanation '$dir'. It must not include a path component '..' (parent directory).";

		if ($expecting_dir && ($dir[0] !== '/'))
			return "$explanation '$dir'. It must begin with a forward slash.";
	}

	





	public static function mkdirIfNotExists($dir, $mode = 0700)
	{
		if ($result = self::sanityCheckPath($dir, __FUNCTION__, 'Can not create directory'))
			return $result;
		$result = false;
		umask(077); 
		$dirs = explode('/', $dir = str_replace('//', '/', $dir));
		$path = '';
		foreach($dirs as $part)
		{
			$path .= "/$part";
			if (!file_exists($path))
				if (false === mkdir($path, $mode, false))
					return "Creation of '$path' failed" . ($path === $dir ? '. ':", while trying to create '$dir'.") . self::getDirPermHelp($path);
				else
				{
					self::auditLog("mkdir($path, $mode, false)");
					$result = true;
				}
	
			if(ZMC::$registry->large_file_system === false)
				if (!is_dir($path))
					return "A file named '$path' already exists. This file prevents ZMC from creating the directory '$dir'. Please examine the content of the file and move or delete the file named '$dir', or change the location you are trying to use to a different directory name.";
	
		}

		if ($test = ZMC::is_readwrite($dir))
			return $test;
		return $result; 
	}

	public static function getFilePermHelp($file, $addendum = '')
	{
		return self::getPermHelp($file, 'file', $addendum);
	}

	public static function getDirPermHelp($dir = '/etc/?manda', $addendum = '')
	{
		return self::getPermHelp($dir, 'dir', $addendum);
	}

	public static function getPermHelp($dir = '/etc/?manda', $type = 'dir', $addendum = '', $commandOverride = null)
	{
		if (($dir === '') || ($dir === '/') || ($dir === '/dev') || ($dir === '/etc/')) return ''; 
		if (ZMC::$registry->debug)
			$df = str_replace("\n", "\r", self::logSpaceUsageStats())."\r\n";
		$file = 'directory';
		$File = 'Directory';
		$command = "mkdir -p '$dir'";
		if ($type === 'file')
		{
			$file = 'file';
			$File = 'File';
			$command = "touch '$dir'; chown amandabackup:disk '$dir'; chmod 600 '$dir'";
		}
		else
		{
			$command .= "; chown -R amandabackup:disk '$dir'; chmod -R 700 '$dir'";
			if (!strncmp($dir, ZMC::$registry->etc_zmanda, strlen(ZMC::$registry->etc_zmanda)))
				$command .= "; chmod 755 '$dir'"; 

			if (!strncmp($dir, '/etc/amanda', 11))
				$command .= "; chmod 750 '$dir'";
		}
	
		if (empty($addendum))
			$addendum = "This $file should be owned by the \"amandabackup\" user with sufficient privileges for the owner.  For example, run the following command as the root user on this AEE server:";
		if(is_array($addendum )){
			if (ZMC::$registry->debug)
				ZMC::debugLog(print_r($addendum, true));
			$addendum = '';
		}

		if (!empty($commandOverride))
			$command = $commandOverride;

		$err = '';
		if ($lastError = ZMC_Error::error_get_last())
		{
			if (ZMC::$registry->debug)
				$err = $lastError['message'];
			if (ZMC::$registry->dev_only)
				$File = $lastError['file'] . ':' . $lastError['line'] . ' - ' . $File;
		}
		return <<<EOD
$err. Please manually create/correct permissions for the $file '$dir' as follows.  $addendum\r
$command\r\n
$df
EOD;
	}

	



	public static function expandIfGlobHasWild($glob)
	{
		return (strpbrk($glob, '{}?*i[]') ? glob($glob) : array($glob));
	}

	public static function titleHelpBar($pm, $title, $anchor = '', $class = '', $style = '', $appendToTitle = '')
	{
		if (empty($anchor))
			$anchor = $title;

		if (strncmp($anchor, 'http:', 5))
		{
			
			
			if ($pos = strpos($anchor, ' ', min(strlen($anchor), 16)))
				$anchor = substr($anchor, 0, $pos);
			else
				$anchor = substr($anchor, 0, 19);

			$anchor = ZMC::$registry->wiki . $pm->tombstone . '+' . ucFirst($pm->subnav) . '#' . urlencode($anchor);
		}
		
		if (ZMC::$registry->dev_only)
			if (!empty($pm->rows) && (($count = count($pm->rows)) > 5))
				$title = "($count) $title";

		if (!empty($appendToTitle))
			$title = "<div style='float:left;'>$title</div>$appendToTitle";

		
















		echo <<<EOD
			<div class='wocloudTitleBar $class' style='position:relative; $style'>
				$title
				<a class='wocloudHelpLink' target='_blank' href='$anchor'></a>
			</div>

EOD;
	}

	public static function moreExpand($text, $max = 200, $more = 'More ..')
	{
		if (strlen($text) < $max)
			return $text;
		if (false === ($pos = strpos($text, ' ', $max -1)))
			$pos = $max;
		$first = substr($text, 0, $pos);
		if (false !== ($tag = strrpos($first, '<')))
			if ($tag < $pos)
				$first = substr($text, 0, $pos = $tag); 
		$last = substr($text, $pos);
		return self::moreExpandLinkOnly($first, $last, $more);
	}

	public static function moreExpandLinkOnly($first, $last, $more = 'More ..')
	{
		$id = uniqid();
		return <<<EOD
			$first <a id='more$id' href="" onclick="noBubble(event); gebi('more$id').style.display='none'; gebi('hide$id').style.display='block'; return false;"> $more</a><span id='hide$id' style='display:none'>$last</span>
EOD;
	}

	public static function filterDigits($s, $default = '')
	{
		$result = '';
		$len=strlen($s);
		for($i=0; $i < $len; $i++)
			if ($s[$i] >= '0' && $s[$i] <= '9')
				$result .= $s[$i];

		return (($result==='') ? $default : $result);
	}

	
	
	
	public static function normalizeWindowsDle(&$dle)
	{
		if (strlen($dle) > 1 && ctype_alpha($dle[0]) && $dle[1] === ':')
		{
			if (strlen($dle) === 2)
				$dle = ucFirst($dle[0]) . ':/';
			elseif ($dle[2] === '/' || $dle[2] === '\\')
				$dle = ucFirst($dle[0]) . ':/' . mb_ereg_replace('\\\\', '/', substr($dle, 3));
		}
	}

	public static function assertValidAmandaPath(ZMC_Registry_MessageBox $pm, $path) 
	{
		if (false !== strpbrk($path, '{}^$[*%"?'))
		{
			$pm->addWarnError('备份项路径中不能包含 {, }, ^, $, [, *, %, \, ", or ?');
			return str_replace(array('{', '}', '^', '$', '[', '*', '%', '\\', '"', '?'), array(), $path);
		}

		if (strlen($path) > 255)
			$pm->addWarnError('Paths in Amanda must not exceed 255 characters.');

		return $path;
	}

	public static function isValidHostname($hostname)
	{
		if (filter_var("http://$hostname", FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED) === FALSE)
			if (filter_var($hostname, FILTER_VALIDATE_IP) === FALSE)
				return false;
		return true;
	}

	public static function rmFormMetaData(&$form) 
	{
		unset($form['goto_page_sort']);
		unset($form['rows_per_page_sort']);
		unset($form['rows_per_page_orig_sort']);
	}

	public static function checkDiskSpace(ZMC_Registry_MessageBox $pm)
	{
		foreach(ZMC::$registry->free_space as $key)
			$pathList[] = ZMC::$registry->cnf->$key;

		$pathList[] = ini_get('session.save_path'); 
		$pathList[] = '/tmp';
		array_unshift($pathList, '/');
		self::enoughDiskFree($pm, $pathList);
	}

	




	private static function enoughDiskFree(ZMC_Registry_MessageBox $pm, array $pathList)
	{
		$numErrors = $pm->isErrors();
		$errorLow = Array();
		$warningLow = Array();
		foreach($pathList as $path)
		{
			if (!file_exists($path))
			{
				$pm->addError("ZMC will not function correctly, because the directory '$path' is missing.  An installation problem may exist.");
				continue;
			}

			if (!is_readable($path))
			{
				$pm->addError("ZMC will not function correctly, because the directory '$path' is not readable. " . self::getDirPermHelp($path));
				continue;
			}

			if ($path !== '/' && $path !== ZMC::$registry->cnf->mysql_path && $path !== ZMC::$registry->cnf->zmc_pkg_base)
				if (!ZMC::is_writable($path))
					$pm->addError("ZMC will not function correctly, because the directory '$path' is not writable by the 'amandabackup' user account. " . self::getDirPermHelp($path));

			if ($path !== '/')
				$path = rtrim($path, DIRECTORY_SEPARATOR); 

			if(ZMC::$registry->large_file_system === false)  
				if (!is_dir($path))
					$pm->addError("ZMC will not function correctly, because '$path' exists, but is not a directory.");
					

			if (false === self::diskFreeSpace($pm, $path, $diskFreeSpace, $diskTotalSpace, $diskUsedSpace))
				return;

			$percentFree = round(bcdiv($diskFreeSpace, $diskTotalSpace, 3) * 100, 0);
			if($percentFree < ZMC::$registry->critical_disk_space_threshold)
				$errorLow[] = $path;
			elseif($percentFree < ZMC::$registry->warning_disk_space_threshold)
				$warningLow[] = $path;
		}

		if(count($errorLow) > 0)
		{
			$stringForm = implode(', ', $errorLow);
			$pm->addError("ZMC may not function correctly, and login may fail.  Critical low free disk space in: $stringForm");
			$pm->critical_free_space = true;
		}

		if(count($warningLow) > 0)
		{
			$stringForm = implode(', ', $warningLow);
			$pm->addWarning("Warning: Free disk space approaching critically low levels in: $stringForm");
		}

		$_SESSION['disk_space_check_errors'] = ($numErrors < $pm->isErrors() ? true : false);
	}

	public function addMessageToInstallationPage(ZMC_Registry_MessageBox $pm, $message, $type){
		$type = strtolower($type);
		if(isset($_SESSION['check_server']) && $_SESSION['check_server'] == "show_check_installation_page"){
			if($type == "success")
				$pm->addMessage($message);
			if($type == "warning")
				$pm->addWarning($message);
			if($type == "error")
				$pm->addError($message);
		}
    }


	













	public static function diskFreeSpace(ZMC_Registry_MessageBox $pm, $path, &$diskFreeSpace, &$diskTotalSpace, &$diskUsedSpace, $reservedPercent = null)
	{
		if ($result = self::sanityCheckPath($path, __FUNCTION__, 'Can not check free space of this directory', false))
		{
			$pm->addWarning($result);
			return false;
		}
		$diskUsedSpace = $diskFreeSpace = $diskTotalSpace = 'Unknown';
		if ($reservedPercent === null)
			$reservedPercent = (ZMC::$registry->platform === 'solaris') ? 10:5; 

		if (ZMC::$registry->large_file_system === true){
			$args = array("-Pm $path | awk 'NR>1{print int($4)}'");
			ZMC_ProcOpen::procOpen('df', 'df', $args ,$f, $f_err, 'df command failed unexpectedly', '','','','',false);
			if (!empty($f_err))
			{
				self::logPosixErr(__FUNCTION__ . __LINE__);
				$pm->addWarning("Unable to check for sufficient free disk space on '$path' due to a limitation in PHP.");
				return false;
			}
			$args =  array("-Pm $path | awk" ,"'NR>1{print int($2)}'");
			ZMC_ProcOpen::procOpen('df', 'df', $args,$t, $t_err, 'df command failed unexpectedly', '','','','',false);
			if (!empty($t_err))
			{
				self::logPosixErr(__FUNCTION__ . __LINE__);
				$pm->addWarning("Unable to check total space for '$path' due to a limitation in PHP.");
				return false;
			}
			$f = intval($f);
			$t = intval($t);
		}else{
			if (false === ($f = disk_free_space($path)))
			{
				self::logPosixErr(__FUNCTION__ . __LINE__);
				$pm->addWarning("Unable to check for sufficient free disk space on '$path' due to a limitation in PHP.");
				return false;
			}
			if (false === ($t = disk_total_space($path)))
			{
				self::logPosixErr(__FUNCTION__ . __LINE__);
				$pm->addWarning("Unable to check total space for '$path' due to a limitation in PHP.");
				return false;
			}
			$f = bcdiv($f, '1048576', 0);
			$t = bcdiv($t, '1048576', 0);
		}
		$diskTotalSpace = bcdiv(bcmul($t, (string)(100 - $reservedPercent), 0), '100', 0);
		$diskUsedSpace = bcsub($diskTotalSpace, $f); 
		$diskFreeSpace = $f;
		ZMC::debugLog("diskFreeSpace($path): reserved => $reservedPercent, path => $path, t => $t, f => $f, diskTotal => $diskTotalSpace, diskUsed => $diskUsedSpace, diskFree => $diskFreeSpace");
		return true;
	}

	public static function logPosixErr($where)
	{
		if ($errno = posix_get_last_error())
		{
			$msg = "$where() $errno: " . posix_strerror($errno);
			ZMC::errorLog($msg);
			return $msg;
		}
		return '';
	}

	public static function isShellOk(ZMC_Registry_MessageBox $pm)
	{
		$results = posix_getpwnam('amandabackup');
		












		if (false !== strpos($results['shell'], 'bash'))
			return true;

		$pm->addError("The amandabackup user's shell is not \"bash\".  Please edit /etc/passwd and change the default shell to bash for the amandabackup user account. ZMC requires bash -e.g. support for UTF8");
		return false;
	}

	public static function isServerOk(ZMC_Registry_MessageBox $pm)
	{
		if (file_exists($fn = '/var/run/reboot-required'))
		{
			$pm->addError("Before using ZMC, reboot this server: " . file_get_contents($fn) . " required by " . file_get_contents('/var/run/reboot-required.pkgs'));
			return false;
		}

		foreach(array('/var/run/zmc/php.pid', '/opt/zmanda/amanda/apache2/logs/httpd.pid') as $pidfile)
		{
			$pid = file_get_contents($pidfile);
			$maps = file_get_contents("/proc/$pid/maps");
			if (!empty($maps) && strpos($maps, ' (deleted)'))
			{
				$pm->addError("Before using ZMC, reboot this server. Some of the shared libraries have been deleted/updated.");
				return false;
			}
		}

		
		return true;
	}

	public static function testConnectivity($host = 'network.wocloud.cn')
	{
		if ($fp = fsockopen($host, 80, $errno, $errstr, 15))
		{
			fwrite($fp, "HEAD / HTTP/1.1\r\nHost: network.wocloud.cn\r\nConnection: Close\r\n\r\n");
			$response = '';
			
			while (!feof($fp))
				$response .= fgets($fp, 512);
		    fclose($fp);
			if (strpos($response, '200 OK'))
				return true;
			return "在接收主机 $host 响应的时候出现未知错误。";
		}
		
		
		ZMC::debugLog(__FUNCTION__ . "(): $errstr ($errno)");
		return "$errstr ($errno)";
	}

	







	public static function useCache($pm, $dependencies, &$cacheFn, $expireCacheIfNoSrc = false,  $timeLimit = false, $touch = true)
	{
		if (!self::isCacheValid($dependencies, $cacheFn, $expireCacheIfNoSrc,  $timeLimit, $touch))
		{
			
			return false;
		}

		if (is_object(ZMC::$registry) && !ZMC::$registry['use_cache'])
		{
			if ($pm !== null)
				$pm->addWarning("Ignored cache $cacheFn, because ZMC \"Turbo Mode\" has been disabled on the Admin|Preferences page.");
			
			return false;
		}

		
		return true;
	}

	private static function isCacheValid(&$dependencies, &$cacheFn, $expireCacheIfNoSrc,  $timeLimit, $touch)
	{
		$cacheFn = rtrim($cacheFn, DIRECTORY_SEPARATOR);
		if (empty($cacheFn)) 
			throw new ZMC_Exception('Missing cache filename');
		if ($cacheFn[0] !== '/')
			$cacheFn = (is_object(ZMC::$registry) ? ZMC::$registry->tmp_path : ZMC::TMP_PATH) . $cacheFn;

		$cacheTime = filemtime($cacheFn);
		if (empty($dependencies))
		{
			if (empty($cacheTime))
			{
				if ($touch)
					touch($cacheFn);
				
				return false;
			}
			if ($timeLimit && ((time() - $cacheTime) > $timeLimit))
			{
				
				return false;
			}
			
			return true;
		}
		if (empty($cacheTime))
		{
			
			return false;
		}
		if (!is_array($dependencies))
			$dependencies = array($dependencies);

		reset($dependencies);
		$srcFn = current($dependencies);
		if (is_object(ZMC::$registry) && (ZMC::$registry->dev_only)) 
			$dependencies = array_merge($dependencies, glob(__DIR__ . '/*.php', GLOB_NOSORT), glob(__DIR__ . '/*/*.php', GLOB_NOSORT));
		foreach($dependencies as $fn)
			if (!is_string($fn) || empty($fn))
				throw new ZMC_Exception(__FUNCTION__ . "(): invalid depenency '$fn'");
			elseif ($dependTime = filemtime($fn))
			{
				if ($timeLimit && ((time() - $cacheTime) > $timeLimit))
				{
					
					return false;
				}
				if ($dependTime >= $cacheTime)
				{
					if ($dependTime == $cacheTime) 
						self::debugLog(__FUNCTION__ . __LINE__ . ":$fn == $cacheFn");

					return false;
				}
			}
			elseif ($expireCacheIfNoSrc && ($fn === $srcFn))
				unlink($cacheFn);
		
		
		return true;
	}

	public static function ilookup($key, $map)
	{
		$key = strtolower($key);
		return (isset($map[$key]) ? $map[$key] : $key);
	}

	public static function isValidIntegerInRange($int, $min, $max)
	{
		return filter_var($int, FILTER_VALIDATE_INT, array('options' => array('min_range' => $min, 'max_range' => $max)));
	}

	public static function checkAmandaLibs(ZMC_Registry_MessageBox $pm)
	{
		try
		{
			foreach(array(ZMC::getAmandaCmd('amadmin'), '/usr/lib/amanda/amandad') as $cmd)
			{
				ZMC_ProcOpen::procOpen('ldd', 'ldd ' . $cmd, array(), $stdout, $stderr);
				if (strpos($stdout, 'not found'))
				{
					$matches = $libs = null;
					preg_match_all('/\S+.=>.not.found/', $stdout, $matches);
					foreach($matches[0] as $match)
						$libs[substr($match, 0, -12)] = true;
					$err = "Missing system libraries needed for \"$cmd\": " . implode(', ', array_keys($libs));
					$pm->addWarnError($err);
					$pm->addDetail("$cmd:\n$stdout");
				}
				elseif ($pos = strpos($stdout, 'libcurl'))
				{
					if (!strpos(  substr($stdout, $pos, strpos($stdout, "\n", $pos) - $pos), '/opt/zmanda/amanda/common/lib/libcurl.so.'))
						$pm->addWarnError($err = "Amanda appears to link against the wrong libcurl:\n$ ldd $cmd\n$stdout");
				}
			}
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$pm->addWarnError("$e");
		}
		if (empty($err))
			return false;
		return true;
	}

	
	public static function occTime()
	{
		if (!function_exists('microtime'))
			return substr(time(), -7) . '0000';
		$m = microtime();
		return substr($m, -7) . substr($m, 2, 4);
	}
	public static function convertToSecondTimeout($value, $unit)
	{
		if(empty($params) && empty($unit))
			return;

		switch($unit)
		{
			case "hours":
				$value = round($value* 3600);
				break;
			case "minutes":
				$value = round($value* 60);
				break;
			case "seconds";
				$value = $value;
				break;

		}
		return $value;

	}

	public static function convertToDisplayTimeout($value, $unit)
	{
		if(empty($params) && empty($unit))
			return;

		switch($unit)
		{
			case "hours":
				$value = round(bcdiv($value, 3600), 1);
				break;
			case "minutes":
				$value = round(bcdiv($value, 60), 1);
				break;
			case "seconds";
				$value = $value;
				break;

		}
		return $value;

	}

	
	public static function convertToDisplayUnits(&$params)
	{
		if (!is_array($params)) ZMC::quit($params);
		foreach($params as $key => &$value)
		{
			if (is_array($value))
			{
				self::convertToDisplayUnits($value);
				continue;
			}

			$displayKey = $key . '_display';
			if (!isset($params[$displayKey]) || $value[strlen($value)-1] !== 'm')
				continue;

			$v1 = $value;
			$value = substr($value, 0, -1); 
			switch(strtolower(ZMC::$registry->units['storage_equivalents'][strtolower($params[$displayKey])])) 
			{
				case 'k':
					$value = bcmul($value, 1024);
					break;
				case 'm': 
					break;
				case 'g':
					$value = round(bcdiv($value, 1024, 2), 1);
					break;
				case 't':
					$value = round(bcdiv($value, 1024 * 1024, 2), 1);
					break;
				case '%':
					$value = max(0, min(100, $value));
					break;
				default:
					self::errorLog("Unsupported display unit ({$displayKey}) used with value ($value) for key '$key'.");
					
					break;
			}
			$value = "$value";
			
		}
	}

	public static function setlocale($locale)
	{
		if (ZMC::$registry->get('locale_sort', null) !== $locale)
		{
			ZMC::$registry->setOverrides(array('locale_sort' => $locale));
			setlocale(LC_ALL, $locale);
		}
	}

	public static function getServerIp()
	{
		if (!empty($_SESSION['server_ip']))
			return $_SESSION['server_ip'];

		$_SESSION['server_ip'] = ZMC::$registry->server_ip;
	}

	public static function redirectPage($class, ZMC_Registry_MessageBox $pm, $_request = array(), $_get = array(), $_post = array())
	{
		if ($pm->offsetExists('redirected_page'))
			ZMC::Quit('Double redirection aborted.');
		if (!empty($_REQUEST['action']))
			ZMC_Events::add(str_replace('ZMC_', '', trim($_SERVER['REQUEST_URI'], '/')) . '=>' . $_REQUEST['action'],
				($errs = $pm->isErrors()) ? ZMC_Error::ERROR : ZMC_Error::NOTICE,
				(($errs || ZMC::$registry->debug) ? $pm : $pm->cloneErrorsAndWarnings()));
		ob_clean();
		$_REQUEST = $_request;
		$_GET = $_get;
		$_POST = $_post;
		return $pm->redirected_page = call_user_func(array($class, 'run'), $pm); 
	}

	public static function arrayFilterByKey($array, $key)
	{
		if (empty($array))
			return $array;

		$elem = current($array);
		if (isset($elem[$key]))
		{
			$result = array();
			foreach($array as $element) 
				if (isset($element[$key]))
					$result[] = $element[$key];

			return $result;
		}
		else
			for($i = 0; $i < count($array); $i++)
				if (!isset($array[$i]))
					return array_keys($array);

		return $array;
	}

	public static function execv($cmd, $args, $wait = false)
	{






		if ($cmd === 'bash')
		{
			$cmd = ZMC::$registry->svn->zmc_bash;
			$args = array('-c', $args);
		}
		elseif ($cmd[0] !== '/')
			throw new ZMC_Exception("Must use absolute path: $cmd $args");
		elseif (is_string($args))
		   $args = explode(' ', $args);

		$command = $cmd . ' ' . implode(' ', $args);
		$pid = pcntl_fork();
		if ($pid == -1)
			throw new ZMC_Exception("Could not fork: $command"); 
		elseif ($pid)
		{
			ZMC::auditLog("Forked pid $pid: $command");
			if (!$wait)
				return $pid;

			$status = null;
			pcntl_waitpid($pid, $status);
			return $status;
		}

		ini_set('error_log', ZMC::$registry->error_log); 
		if (posix_setsid() < 0)
			error_log('ZMC: child pid ' . posix_getpid() . ' could not detach: ' . posix_strerror(posix_get_last_error()));

		pcntl_exec($cmd, $args,
			array('LD_LIBRARY_PATH' => getenv('LD_LIBRARY_PATH'), 'PATH' => getenv('PATH')));
		error_log("FAILED: pcntl_exec: $command");
	}

	public static function waitpids()
	{
		$limit = 10; 
		while ($limit-- && ($retval = pcntl_wait($status, WNOHANG)) > 0) 
			if (is_object(ZMC::$registry) && ZMC::$registry->debug)
			{
				$code = 'standard AGS child shutdown'; 
				if (pcntl_wifexited($status)) 
					$code = 'code ' . pcntl_wexitstatus($status);
				ZMC::debugLog("Process $retval finished with $code", (($retval === -1) ? ZMC_Error::ERROR : ZMC_Error::NOTICE),
					__FILE__, __LINE__, 'process', $code);
			}
	}

	protected static function fileAppend($where, $what)
	{
		if (true !== ($result = self::mkdirIfNotExists(dirname($where))))
			throw new ZMC_Exception("Can not append to $where. " . ZMC::getDirPermHelp($dir));
		if ($fp = fopen($where, 'a'))
		{
			$result = fwrite($fp, $what);
			fclose($fp);
		}
		if ($fp === false || $result === false)
			throw new ZMC_Exception("Can not append to '$where'. " . ZMC::getFilePermHelp($where));
	}

	public static function distro2abbreviation($distro)
	{
		return ucFirst(str_replace(array('  ', 'release', 'Red Hat Enterprise Linux Server '), array(' ', '', 'RHEL'), $distro));
	}

	public static function &php2perl($array)
	{
		$result = "{\n";
		foreach($array as $key => $value)
		{
			$result .= "'$key'=>";
			if (is_array($value))
				$result .= "\t" . self::php2perl($value) . ",\n";
			else
				$result .= "'" . str_replace(array('{', '}'), array('\\{', '\\}'), $value) . "',\n";
		}
		$result .= '}';
		return $result;
	}

	public static function &perl2php($fn, $var = false)
	{
		$result = false;
		if (false === ($contents = file_get_contents($fn)))
			return $result; 

		$contents = str_replace(array('undef', '     ', '{', '[', '}', ']'), array('null', ' ', 'array(', 'array(', ')', ')'), $contents);
		$result = eval($contents);
		if ($var)
			$result =& $$var;
		return $result;
	}

	
	
	
	
	
	
	public static function parseShare($share, &$host, &$name, &$path)
	{
		$host = $name = $path = '';
		$share = rtrim($share, '\\');
		$parts = explode('\\', $share);
		if (count($parts) < 3)
			return false;
		$host = $parts[2];
		$name = $parts[3];
		if (count($parts) > 4)
			$path = substr($share, $len = strlen($host) + strlen($name) + 4);
		return true;
	}

	public static function isLocalHost($host)
	{ return (isset(ZMC::$registry->myHostNames[$host]) || $host == '127.0.0.1'); }

	






	public static function checkPath($paths, $path, &$matches)
	{
		$err = false;
		if ($path[0] !== '/')
			return "Path '$path' does not begin with a forward slash ('/') character.";

		if (false !== strpos($path, '/../') || (substr($path, -3) === '/..'))
			return "Path '$path' may not include a path component '..' (parent directory).";

		if (false === ($lines = file($paths, FILE_IGNORE_NEW_LINES)))
			return "Unable to read file '$paths'.";

		if (!empty($lines))
			foreach($lines as $line)
				if (!strncmp($path, $line, strlen($line)))
				{
					$matches[] = $line;
					$err = "Path '$path' blocked by '$line' in '$paths'";
				}

		return $err;
	}

	public static function dump($var, $label=null, $echo=true)
	{
		$label = ($label===null) ? '' : rtrim($label) . ' ';
		if ($echo === 'pretty')
		{
			$output = preg_replace('/Array\s+\(/i', '', print_r($var, true));
			$output = preg_replace('/^\s*\)\s*$/m', '', $output);
			return $output;
		}
		else
		{
			ob_start();
			var_dump($var);
			$output = ob_get_clean();
			$output = preg_replace('/]=>\n\s+(?:(array.0[^\}]+)\n([^\}]+\}\n))/', "] => \$1\$2", $output);
			$output = preg_replace('/]=>\n\s+(\W+)/', '] => $1', $output);
		}
		$len = strlen($output); 
		$result = '';
		for($i = 0; $i < $len; $i++)
		{
			if (false === ($pos = strpos($output, "\n", $i)))
			{
				$result .= substr($output, $i);
				break;
			}
			$result .= substr($output, $i, $pos - $i +1);
			$i = $pos +1;
			while (($i < $len) && ($output[$i] === ' '))
			{
				$result .= "\t";
				$i++;
			}
			$i--;
		}
		unset($output);
		if (PHP_SAPI == 'cli')
			$result = PHP_EOL . $label . PHP_EOL . $result . PHP_EOL;
		else
			$result = "<div>$label " . self::escape($result) . "</div>";
		
		if ($echo === true) echo($result);
		return $result;
	}

	public static function socket_put_contents($socket, $pkt, $blockSize = 32768, $emsg, $timeout = 180)
	{
		$i = $errno = $bytesSent = 0;
		$wrote = -1;
		$timeToQuit = time() + $timeout;
		$length = strlen($pkt);
		$type = get_resource_type($socket);
		static $last_emsg = null;
		static $last_emsg_count = 0;
		if ($last_emsg === $emsg)
		{
			if (0 === ($last_emsg_count % 1000))
				ZMC::errorLog(__FUNCTION__ . __LINE__ . ": attempting (repeated $last_emsg_count): $last_emsg)");
		}
		else
		{
			if (!empty($last_emsg) && $last_emsg_count > 1)
				ZMC::errorLog(__FUNCTION__ . __LINE__ . ": attempting (repeated $last_emsg_count): $last_emsg)");
			ZMC::errorLog(__FUNCTION__ . __LINE__ . ": attempting: $emsg)");
			$last_emsg = $emsg;
			$last_emsg_count = 0;
		}
		$last_emsg_count++;
		$errors = 0;
		while ($bytesSent < $length) 
		{
			$wrote = $except = $read = null;
			$write = array($socket);
			if ($type === 'Socket')
				$count = socket_select($read, $write, $except, 30, 10);
			else
				$count = stream_select($read, $write, $except, 30, 10);

			if ($count > 0)
			{
				if ($type === 'Socket')
					$wrote = socket_write($socket, $s = substr($pkt, $bytesSent, $blockSize), strlen($s));
				else
					$wrote = fwrite($socket, $s = substr($pkt, $bytesSent, $blockSize), strlen($s));

				if (($wrote === false))
				{
					$errno = socket_last_error($socket);
					if ($type === 'Socket')
						throw new ZMC_Exception("$emsg (code #$errors:" . __LINE__ . "): Problem reading reply: wrote=false; errors=$errors; errno=$errno; " . socket_strerror($errno));
					else
						return "$emsg (code #$errors:" . __LINE__ . ")";
				}
				$bytesSent += $wrote;
			}
			
			
			elseif ($count === false)
				if ($type === 'Socket')
					$errors++;
				else
					throw new ZMC_Exception("$emsg (code #$errors:" . __LINE__ . "): socket_select failed");

			if (time() > $timeToQuit)
				if ($type === 'Socket')
					throw new ZMC_Exception("$emsg (code #$errors:" . __LINE__ . "): Problem sending status request to Destination Host: " . socket_strerror(socket_last_error($socket)));
				else
					return "$emsg (code #$errors:" . __LINE__ . "): timeout sending status request to Destination Host";

			if (empty($wrote))
				sleep(1);
		}
	}

	public static function socket_get_contents($socket, $emsg, $timeOut = 180)
	{
		$result = '';
		$timeToQuit = time() + $timeOut;
		while(!feof($socket)) {
			$current = '';
			$current = fread($socket, 8192);
			$result .= $current;
			if(time() > $timeToQuit){
				if(!empty($current))
					$timeToQuit = time() + $timeOut;
				else
					throw new ZMC_Exception("$emsg" . socket_strerror(socket_last_error($socket)));
			}

		}
		return $result;
	}

	public static function prettyPrintByteCount($bytes)
	{
		$mib = intval(bcdiv($bytes, 1024 * 1024, 2));
		if ($mib > 10)
			return round($mib / 1024, 1) . ' GiB';
		return "$mib MiB";
	}

	public static function getDisplayInfoKB($kbytes)
	{
		if ($kbytes > 1073741824.0)	return array(round($kbytes/1073741824.0, 1), "TiB");
		if ($kbytes > 1048576.0)		return array(round($kbytes/1048576.0, 1), "GiB");
		if ($kbytes > 1024.0)		return array(round($kbytes/1024.0, 1), "MiB");
		return array($kbytes, "KiB");
	}

	public static function getDisplayInfoBytes($bytes)
	{
		if ($bytes > 1073741824.0)	return array(round($bytes/1073741824.0, 1), "GiB");
		if ($bytes > 1048576.0)		return array(round($bytes/1048576.0, 1), "MiB");
		if ($bytes > 1024.0)		return array(round($bytes/1024.0, 1), "KiB");
		return array($bytes, "Bytes");
	}

	public static function print_me($val, $exit = false){
		echo '<pre>';
		print_r($val);
		echo '</pre>';
		if($exit == true)
			die;
	}
	public static function dropdown($title, $name, $list ,  $selected="", $default = "", $aditional = "", $event = ''){
		if(empty($name) && empty($list))
			return;
		$dr = "<select id='$name' name='$name' $event $aditional  title='$title'>";
		if(!empty($list) && count($list) > 0){
			foreach($list as $key =>$val){
				$sel = '';
				if( $selected === $key)
					$sel= " selected='selected' ";
				elseif($default === $key)
						$sel= " selected='selected'";

				$dr .="<option value='$key'  $sel >$val</option>";
			}
		}
		$dr .= "</select>";
		return $dr;
	}

	public static function mediasummaryunits($value, $requestedunit = 'm'){
	if(empty($value))
		return;

	$value_in_unit = strtolower(substr($value, -1));
	$units = array( 'b' => 1, 'k' => 2, 'm' =>3, 'g' => 4, 't' =>5 , 'p' => 6, 'e' => 7, 'z' =>8, 'y' => 9);


	if($units[$value_in_unit] < $units[$requestedunit]){
		return round((int)$value / pow(1024, (abs($units[$requestedunit] - $units[$value_in_unit]))));
	}
	if($units[$value_in_unit] > $units[$requestedunit]){
		return round((int)$value * pow(1024, (abs($units[$requestedunit] - $units[$value_in_unit]))));
	}
	else 
		return (int)$value;
	}

	public static function match($path, $pattern, $type = "default_match", $ignoreCase = TRUE) {
		$pattern = trim($pattern, '*');
		$expr = preg_replace_callback('/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function($matches) {
		switch ($matches[0]) {
			case '*':
				return '.*';
			case '?':
				return '.';
			default:
				return '\\'.$matches[0];
		}
		}, $pattern);
		$first = '';
		if($type == "default_match"){
			$expr = $expr;
		}elseif($type == "starts_with"){
		    $expr = "(\/)+$expr";
		}elseif($type == "ends_with"){

		    $last = (substr($expr, -1) != "*")? '$': '';
		    $expr = $expr.$last;
		}elseif($type == "exact_match"){
		    $expr = "^((.*)?\/)?".$expr."(\/(.*)?)?$";
		}

		
		
		
		
		$expr = '/'.$expr.'/';
		if ($ignoreCase) {
			$expr .= 'i';
		}
	    return (bool) preg_match($expr, $path);
	}
	public static function match_wildcard( $wildcard_pattern, $haystack ) {
		$regex = str_replace(
			array("\*", "\?"), 
			array('.*','.'),   
			preg_quote($wildcard_pattern)
		);

		return preg_match('/^'.$regex.'$/is', $haystack);
	}
}

function bless($array) 
{ return current($array['value']); }



