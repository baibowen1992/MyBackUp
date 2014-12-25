<?










































































class ZMC_Error
{
	const ZMC_FILE_PREFIX_LEN = 23; 
	
	
	const EMERGENCY = 1;
	const ALERT = 2;	
	const CRITICAL = 3;	
	const ERROR = 4;	
	const WARNING = 5;	
	const NOTICE = 6;	
	const INFO = 7;		
	const DEBUG = 8;	

	public static $severity2text = array(
	   	self::ERROR => 'Error',
	   	self::WARNING => 'Warning',
		self::NOTICE => 'Notice',
		self::INFO => 'Info',
	   	self::DEBUG => 'Debug'		
	);

	public static $error2severity = array(
		
		
		
		'ERROR'		=> self::ERROR,
		'WARNING'	=> self::WARNING,
		'NOTICE'	=> self::NOTICE,
		'INFO'		=> self::INFO,
		'DEBUG'		=> self::DEBUG
	);

	public static $severity2icon = array(
		self::EMERGENCY => 'failure',
		self::ALERT => 'failure',
		self::CRITICAL => 'failure',
		self::ERROR => 'failure',
		self::WARNING => 'warning',
		self::NOTICE => 'success',
		self::INFO => 'success',
		self::DEBUG => null,
	);

	const MAX_BACKTRACE_DEPTH = 12;

	
	public static $silent = false; 

	
	public static $verbose = false; 

	
	public static $httpCode = 200; 

	
	protected static $obStartCallbackDisabled = true;

	
	protected static $error_get_last = null;

	public static $session_status_hack;

	public static function error_get_last()
	{
		return self::$error_get_last;
	}

	









	public static function zmcErrorHandler($errNumber, $errMessage, $errFile, $errLine, $context)
	{
		if (!strncmp($errMessage, 'include_once():', 15))
			return false;
		if (error_reporting() === 0) 
			return false; 

		
		if (self::$session_status_hack = !strncmp($errMessage, 'A session had already been started', 34))
			$errMessage .= " Session start request received from $GLOBALS[session_request_file]:$GLOBALS[session_request_line], but already started by $GLOBALS[session_started_file]:$GLOBALS[session_started_line]";

		$errType = array (
			E_ERROR              => 'Error', 
			E_WARNING            => 'Warning',
			E_PARSE              => 'Parse Error', 
			E_NOTICE             => 'Notice',
			E_CORE_ERROR         => 'Core Error', 
			E_CORE_WARNING       => 'Core Warning', 
			E_COMPILE_ERROR      => 'Compile Error', 
			E_COMPILE_WARNING    => 'Compile Warning', 
			E_USER_ERROR         => 'User Error',
			E_USER_WARNING       => 'User Warning',
			E_USER_NOTICE        => 'User Notice',
			E_STRICT             => 'Strict/Runtime Notice',
			E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
		);
	
		$errFile = substr($errFile, self::ZMC_FILE_PREFIX_LEN);
		$uniqid = uniqid(); 
		$errMessage = trim($errMessage);
		$set = ((empty($_SESSION) || empty($_SESSION['configurationName'])) ? '':$_SESSION['configurationName']);
		error_log($msg = __FUNCTION__ . ":$uniqid:$errType[$errNumber]:$errFile:$errLine-$set-$errMessage"); 
		self::$error_get_last = array(
			'type' => $errType[$errNumber],
			'message' => $msg,
			'file' => $errFile,
			'line' => $errLine,
		);
		$ZMC = class_exists('ZMC', false) && isset(ZMC::$registry);

		
		
		if ($ZMC && ZMC::$registry->debug >= self::DEBUG)
		{
			
			$message = "$uniqid {$errType[$errNumber]} for $set in '$errFile' on line $errLine: $errMessage";
			if (self::$verbose)
			{
				$message .= ' Backtrace=' . self::backtrace();

				if (!class_exists('sfContext', false)) 
				{
					$variables = print_r($context, true) . "\n";
					if (!ZMC::$registry->qa_mode)
					{
						$variables = preg_replace('/PHP_AUTH_PW.*?\[/si', '', $variables);	
						
						
						$variables = preg_replace('/(.*\[[^\]]*password[^\]]*\]).*/i', '\\1 => <password censored>', $variables);
					}
					$message .= $variables;
				}
			}
			ZMC::debugLog($message); 
		}

		if ($errNumber === E_NOTICE || $errNumber === E_USER_NOTICE || $errNumber === E_WARNING || $errNumber === E_STRICT)
			return true; 

		self::silentExit(__FUNCTION__);
		if (self::$httpCode !== 200)
			self::returnStatus(__LINE__ . $msg);

		$renderMessage = empty($message) ? __LINE__ . $msg : __LINE__ . $message;
		global $pm;
		if ($pm->command_mode)
		{
			echo $renderMessage;
			exit(1);
		}

		self::bomb($message);
	}

	






	public static function zmcExceptionHandler($exception)
	{
		if ($exception === null)
			return;
	
		$set = ((empty($_SESSION) || empty($_SESSION['configurationName'])) ? '':"\r\nBackup Set: $_SESSION[configurationName]");
		$ZMC = class_exists('ZMC', false) && isset(ZMC::$registry);
		if ($ZMC && (class_exists('ZMC_Exception', false)) && ($exception instanceof ZMC_Exception))
			$message = "$set: $exception";
		else
		{
			$message = "$set: uncaught exception in "
				.  $exception->getFile()
				. ':' . $exception->getLine()
				. ' code=' . $code = $exception->getCode() . ZMC::$registry->zmc_svn_info
				. ' ' . $exception->getMessage()
				. "\n" . substr(str_replace(__DIR__, '', $exception->getTraceAsString()), 0, 2048);
		}

		self::silentExit(__FUNCTION__);
		if (self::$httpCode !== 200)
			self::returnStatus($message);

		if ($ZMC && ZMC::$registry->debug)
			ZMC::quit($message);
		self::bomb($message);
	}
	
	private static function silentExit($msg)
	{
		if (self::$silent) 
		{
			error_log(__FUNCTION__ . ' ' . $msg); 
			self::flushObs();
			exit(-1);
		}
	}

	private static function bomb($message)
	{
		self::flushObs();
		ZMC::headerRedirect('/Common/internal_error.php?error=' . bin2hex($message), __FILE__, __LINE__);
		exit(-1); 
	}
	
	










	public static function zmcObend($buffer) 
	{
		
		if (!empty(self::$obStartCallbackDisabled))
			return $buffer; 
	
		if (strlen($buffer) && strpos($buffer, '</html>', max(0, strlen($buffer) -32)) !== false)
			return $buffer;
	
		
		$fn = '/opt/zmanda/amanda/apache2/logs/error_log';
		$fp = @fopen($fn, 'r');
		if ($fp)
		{
			fseek($fp, -4096, SEEK_END);
			$line = '';
			while (!feof($fp))
			{
				$err = $line;
				$line = fgets($fp, 4096);
			}
			if (!empty($line))
				$err = $line;
		}
		else
			$err = "unknown / unable to read error log ($fn)";
	
		
	
		if (!strncmp($_SERVER['REQUEST_URI'], '/Common/SystemError', 18))
		{
			$origError = '';
			if (isset($_GET['e'])) 
				$origError = '<h2>System Error (' . __LINE__ . ')</h2><p>' . $_GET['e'] . '</p><hr>';
	
			return <<<EOD
				<html><head><title>System Error</title></head><body>
					<img src="/images/global/zmc-crash-header.gif">
					<div class='userError'>
						$origError
						<p>Additionally, an error occurred while trying to render the System Error Page:</p>
						<p>$err</p>
					</div>
				</body></html>
EOD;
		}
		header("Location: /Common/SystemError.php?app=zmc&e=" . urlencode($err), 
			(class_exists('ZMC', false) && isset(ZMC::$registry) && ZMC::$registry->debug) ? 303 : 302);
		self::quit(-1); 
	}

	



	public static function disableObCheck()
	{
		self::$obStartCallbackDisabled = true;
	}

	public static function flushObs()
	{
		self::disableObCheck(); 
		while (ob_get_level() && ob_end_clean()); 
	}

	






	public static function returnStatus($message = null, $code = -1)
	{
		self::silentExit($message); 
		if ($code === -1)
			$code = self::$httpCode;

		switch($code)
		{
			case 200:
				break;
			case 400:
				$error = 'Bad Request';
				break;
			case 401:
				$error = 'Unauthorized';
				break;
			case 403:
				$error = 'Forbidden';
				break;
			case 404:
				$error = 'Not Found';
				break;
			case 501:
				$error = 'Not Implemented';
				break;
			case 500:
			default:
				$code = 500;
				$error = 'Internal Server Error';
				break; 
		}
		if ($code != 200) 
		{				  
			header("HTTP/1.0 $code $error");
			echo "$code~$message\n";
			exit(-1);
		}

		echo $message;
		exit(0);
	}

	public static function installHandlers()
	{
		
		set_error_handler(array('ZMC_Error', 'zmcErrorHandler'));
		
		
		if (null === set_error_handler(array('ZMC_Error', 'zmcErrorHandler')))
		{
			echo 'ERROR: Unable to instal ZMC error handler (' . __FILE__ . ':' . __LINE__ . ')';
			exit(-1);
		}

		
		set_exception_handler(array('ZMC_Error', 'zmcExceptionHandler'));
		
		
		if (null === set_exception_handler(array('ZMC_Error', 'zmcExceptionHandler')))
		{
			echo 'ERROR: Unable to instal ZMC error handler (' . __FILE__ . ':' . __LINE__ . ')';
			exit(-1);
		}
	}

	




	public static function installObHack()
	{
		
		ob_start(array('ZMC_Error', 'zmcObend'));
	}

	


	public static function var_dump(&$something)
	{
		ob_start();
		var_dump($something);
		return ob_get_clean();
	}

	






	public static function backtrace($level = 0, $trace = null)
	{
		if ($trace === null)
		{
			$trace = debug_backtrace();
			array_shift($trace); 
		}
		elseif ($trace instanceof ArrayObject)
			$trace = $trace->getArrayCopy();
		elseif (is_object($trace))
			return 'Tracing objects not yet supported'; 

		$pad = $msg = '';
		if ($level > 0)
			$pad = str_repeat("\t", $level);
		$i = 0;
		foreach($trace as $tkey => &$value)
		{
			if ($level < 0)
				$msg .= "==>$tkey<==\n";
			if ($i === self::MAX_BACKTRACE_DEPTH || ($level < 0 && ($i + $level === 0)))
				return $msg .= "\n{$pad}\tSkipped Remainder\n";
			$i++;
			if (is_array($value))
			{
				if ($level < 0) 
				{
					foreach($value as $key => &$val)
						if (is_array($val))
						{
							$body = "$key => Array(\n";
							foreach($val as $k => $v)
							{
								if (is_object($v))
									$append = 'class ' . get_class($v);
								elseif ($v === true)
									$append .= 'true';
								elseif ($v === false)
									$append .= 'false';
								elseif (is_array($v))
									$append = "x2Array(" . implode(', ', array_keys($v));
								elseif (is_string($v))
									$append = '{' . $v . '}'; 
								else
									$append = "{unknown}";

								if (strlen($append) > 1024)
									$append = substr($append, 0, 1000) . ' ... SKIPPED)';
								$body .= "\t$k => $append,\n";
							}
							
							
							$msg .= "\n$body\t)";
						}
						elseif(is_object($val))
							$msg .= "\n$key => class " . get_class($val);
						elseif ($val === true)
							$msg .= "$key => true";
						elseif ($val === false)
							$msg .= "$key => false";
						else
							$msg .= "\n$key => $val";
				}
				elseif ($level >= 4)
					$msg = "\n$pad$tkey => skipped";
				elseif (empty($trace[$tkey]))
					$msg .= "\n$pad$tkey => Array()";
				else
					$msg .= "\n$pad$tkey => Array(" . self::backtrace($level +1, $value) . ' )';
			}
			else
			{
				if (is_object($value))
					$value = 'class ' . get_class($value);
				elseif ($value === true)
					$value = 'true';
				elseif ($value === false)
					$value = 'false';
				elseif ($value === '')
					$value = '<empty string>';
				$msg .= "\n$pad$tkey => $value";
			}
		}
		return $msg;
	}

	









	public static function getFileLine(&$function, &$file, &$line, $depth = 0, $trace = null)
	{

		if ($trace === null)
			$trace = debug_backtrace();

		$depth = min(count($trace) -1, $depth +1) +1;
		$function = $trace[$depth]['function'];
		if (array_key_exists('file', $trace[$depth])) 
		{
			$file = $trace[$depth]['file'];
			if (0 === strpos($file, '/opt/zmanda/amanda/ZMC/'))
				$file = substr($file, self::ZMC_FILE_PREFIX_LEN);
			$line = $trace[$depth]['line'];
		}
		else
		{
			$file = $function;
			$line = $trace[$depth]['class'];
			
		}
		$depth++;
		if (!isset($trace[$depth]))
			return array(null,null,null);

		$file2 = $line2 = null;
		$function2 = $trace[$depth]['function'];
		if (array_key_exists('file', $trace[$depth])) 
		{
			$file2 = $trace[$depth]['file'];
			if (0 === strpos($file2, '/opt/zmanda/amanda/ZMC/'))
				$file2 = substr($file2, self::ZMC_FILE_PREFIX_LEN);
			$line2 = $trace[$depth]['line'];
		}
		else
		{
			$file2 = $function2;
			$line2 = $trace[$depth]['class'];
		}


		return array($function2, $file2, $line2);
	}
}

class ZMC_Timer
{
	public static $mstartTime = null;
	public static $accumulator = 0;
	public static $methodStack = array();
	public static $elapsedStack = array();
	public static $peakUsage = 0;

	public static function mtime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return $sec . substr($usec, 1);
	}

	public static function init()
	{
		self::$mstartTime = self::mtime();
	}

	public static function elapsed()
	{
		return bcsub(self::mtime(), self::$mstartTime, 5);
	}

	public static function accumulate($elapsed)
	{
		self::$accumulator += $elapsed;
	}

	public static function logElapsed($function = '', $start = true)
	{
		$mtime = self::mtime();
		if (!empty($function))
		{
			if ($start)
			{
				self::$methodStack[] = $function;
				self::$elapsedStack[] = $mtime;
			}
			else
			{
				array_pop(self::$methodStack);
				$elapsed = array_pop(self::$elapsedStack);
			}
		}

		if (ZMC::$registry->debug)
		{
			if (empty($function) || $start)
			{
				$time = bcsub($mtime, self::$mstartTime, 5);
				$text = "; total elapsed time = $time";
			}
			else
			{
				$time = bcsub($mtime, $elapsed, 5);
				$text = "; Function $function() elapsed time = $time";
			}

			if (self::$accumulator[0] !== '0') 
				$sqlTime = (empty(self::$accumulator) ? '' : '; total SQL execution time = ' . self::$accumulator);

			if ((self::$peakUsage > 10) || ($time[0] !== '0') || !empty($sqlTime))
				ZMC::debugLog(__CLASS__
					. '; ' . $_SERVER['REQUEST_URI']
					. '; memory_get_peak_usage(true) = ' . self::$peakUsage
					. $text
					. $sqlTime
				);
		}
	}

	public static function testMemoryUsage()
	{
		self::$peakUsage = 1 + intval(memory_get_peak_usage(true) / 1024 / 1024);
		$percent = self::$peakUsage / intval(ini_get('memory_limit'));
		if ($percent > ZMC::$registry->low_memory)
			return round($percent * 100, 1);
		return false;
	}
}
ZMC_Timer::init();
