<?





























class ZMC_Yasumi
{
	const ERROR_GENERIC = 'An internal exception has occurred.  The requested operation did not complete successfully.';

	
	
	private static $errors = array(
		'stats' => array(
			'read' => 'Problems with the /etc/zmanda/zmanda_license file prevented the operation from succeeding.',
		
		),
	);
	
	const YASUMI_LOG = '/opt/zmanda/amanda/logs/yasumi.log';

	
	const ETC_AMANDA = '/etc/amanda/';

	static protected $sid = false;
	protected $parser = null; 

	
	protected $amanda_configuration_name = null;

	
	protected $childPid = null;

	
	protected $data = null;
	
	
	protected $debug = false;

	
	protected $debug_level = null;

	
	protected $denormalizeKeys = array();

	
	protected $dump = false;

	
	protected $endedOnce = false;

	
	protected $cacheFilename = null;

	
	protected $facility = null;

	
	protected $filterKeys = array(
		'debug',
		'callback_location',
		'callback_username',
		'callback_password',
		'password',
		'readTest'
	);

	
	protected $human = false;

	
	
	
	protected $mode = null;

	
	protected $operation = null;

	
	protected $optional = array();

	
	
	protected $optionalOptions = array(
		'amandaConfigurationName' => 'amanda_configuration_name',
		'amanda_configuration_name' => null,
		'amandaConfigurationId' => 'amanda_configuration_id',
		'amanda_configuration_id' => null,
		'callbackLocation' => 'callback_location',
		'callback_location' => null,
		'callbackPassword' => 'callback_password',
		'callback_password' => null,
		'callbackUsername' => 'callback_username',
		'callback_username' => null,
		'debug' => null,
		'facility' => null,
		'human' => null,
		'mode' => null,
		'readTest' => null,
		'tags' => null,
		'throw_error' => null,
		'throwerror' => 'throw_error',
		'throwError' => 'throw_error',
		'returnError' => 'throw_error',
		'return_error' => 'throw_error',
		'timestamp' => null,
		'timezone' => null,
		'_caller_source' => null,
		'user_id' => null,
		'userId' => 'user_id',
		'userid' => 'user_id',
		'user_name' => 'username',
		'userName' => 'username',
		'username' => null
	);

	
	protected $options = null;

	
	protected $pathInfo = null;

	
	protected $parentPid = null;

	
	protected $pid = null;

	
	protected $post = null;

	
	protected $postData = null;

	
	protected $humanPostData= null;

	
	protected $reply = null;
	
	
	protected $required = array();
	
	
	protected $requestId = null;

	
	protected $requestUri = null;

	
	protected $tags = null;

	
	protected $timestamp = null;

	
	protected $timezone = null;

	
	protected $user_id = null; 

	
	protected $username = null;

	
	protected $_caller_source = null; 

	
	protected $what = null;

	public function getUrl()
	{
		$port = $_SERVER['SERVER_PORT'];
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https':'http');
		$server = $_SERVER['HTTP_HOST'];
		return "$protocol://$server:$port";
	}

	public function &bootstrap($args, $dev_only = false)
	{
		$reply = new ZMC_Registry_MessageBox();
		ZMC_ConfigHelper::getRegistry($reply, 'Ags'); 
		ZMC::$registry->dev_only = ZMC::$registry->dev_only || $dev_only;
		$this->dump = ZMC::$registry->dump;
		$this->pid = posix_getpid();
		$this->setRequestId();
		if (empty($args['requestUri']))
			ZMC::quit(); 
		if (false !== ($callback = strpos($args['requestUri'], 'callback_password=')))
			$args['requestUri'] = substr($args['requestUri'], 0, $callback)
				. substr($args['requestUri'], strpos($args['requestUri'], '&', $callback + 18));
		$this->debug_level = ZMC::$registry->debug_level;
		$this->debug = ZMC::$registry->debug;
		if (!empty($args['data']['human']))
			$this->debug = true; 
		if (isset($args['data']['debug']))
			$this->debug_level = $args['data']['debug'];
		if ($this->debug && $this->debug_level < ZMC_Error::DEBUG)
			$this->debug_level = ZMC_Error::DEBUG;
		$yasumi = $this->promote($args, $reply);
		if (!empty($args['cacheFilename']))
			$yasumi->cacheFilename = $args['cacheFilename'];
		if ($yasumi->debug && !($yasumi->debug_level >= ZMC_Error::EMERGENCY || $yasumi->debug_level <= ZMC_Error::DEBUG)) 
			$yasumi->debug_level = ZMC::$registry->debug_level; 
		
		ignore_user_abort(); 
		umask(037);
		$reply =& $yasumi->run();
		return $reply;
		
	}

	















	public function promote($args, $reply = null)
	{
		$args['options'] = explode('/', $args['pathInfo']);
		if (count($args['options']) < ($args['pathInfo'][0] === '/' ? 3:2))
			$this->returnStatus("Incomplete operation request: " . serialize($args, true), __FILE__, __LINE__, null, 400);

		if ($args['pathInfo'][0] === '/')
			array_shift($args['options']);

		$args['what'] = ucfirst(strtolower(array_shift($args['options'])));
		if (!ZMC::isalnum_($args['what'], '_-'))
			$this->returnStatus("Unable to process command '" . $args['what'] . "' (non alphanumeric characters).", __FILE__, __LINE__, null, 400);
		
		for($i = strlen($args['what']) -1; $i > 2; $i--)
			if ($args['what'][$i-1] === '_' || $args['what'][$i-1] === '-')
				$args['what'][$i] = strtoupper($args['what'][$i]);

		$args['operation'] = str_replace('_', '', array_shift($args['options']));
		if (!ctype_alnum($args['operation']))
			$this->returnStatus("Unable to process operation '$args[operation]' (non alphanumeric characters) for command '$args[what]'.", __FILE__, __LINE__, null, 400);
		
		$this->noticeLog($msg = posix_getpid() . " $args[_caller_source] REQUEST URI: $args[requestUri]" . (ZMC::$registry->debug_uri ? "  DEBUG URI: " . $this->getUrl() . "/debug/logs/$this->requestId.$args[what].$args[operation]/" : ''), __FILE__, __LINE__);
		if ($this->debug_level > ZMC_Error::NOTICE)
			error_log($msg);

		$class = 'ZMC_Yasumi_' . str_replace('-', '', $args['what']);
		try
		{ class_exists($class, true); }
		catch(Exception $e)
		{ $this->returnStatus("Invalid command '$args[what]'.", __FILE__, __LINE__, null, 400); }

		return new $class($this, $args, $reply) ; 
	}

	




	public function __construct(ZMC_Yasumi $yasumi = null, $args = array(), ZMC_Registry_MessageBox $reply = null)
	{
		$this->reply = (empty($reply) ? new ZMC_Registry_MessageBox() : $reply);
		if (!isset($this->reply['history']))
			$this->reply['history'] = '';
		if ($yasumi === null)
			return; 

		$this->debug = $yasumi->debug;
		$this->debug_level = $yasumi->debug_level;
		$this->requestId = $yasumi->requestId;
		$this->requestUri = $yasumi->requestUri;
		$this->pid = $yasumi->pid;
		foreach(array(
			'_caller_source',
			'amanda_configuration_name',
		   	'data',
		   	'debug',
		   	'operation',
		   	'options',
			'pathInfo',
		   	'post',
		   	'postData',
			'requestUri',
			'what',
		) as $key)
		{
			if (array_key_exists($key, $args))
				$this->$key = &$args[$key];
			elseif ($yasumi !== null)
				$this->$key = $yasumi->$key;
		}

		if (empty($this->_caller_source)) {echo __FILE__,__LINE__,"\n"; ZMC::quit($yasumi);} 

		$this->getData(); 
		$this->extractOptionalKeys(); 
		
		if (empty($_SESSION['user']))
			$_SESSION['user'] = $this->username; 
		if (empty($_SESSION['user_id']))
			$_SESSION['user_id'] = $this->user_id; 
		try
		{
			$this->dump(); 
		}
		catch(ZMC_Exception $e)
		{
			$this->reply->addInternal("$e");
		}
	}

	
	private static $invocationCount = 0;
	private static $requestPrefix = null;
	private function setRequestId()
	{
		if (self::$requestPrefix === null)
		{
			$mtime = microtime();
			self::$requestPrefix = substr($mtime, 15) . substr($mtime, 2, 8); 
		}

		$this->requestId = self::$requestPrefix . '.' . (self::$invocationCount++) . '.' . $this->pid;
	}

	private $nDumps = 0;
	private $dumpedFunctionCounts = array();
	private static $dumpDir = null;
	private static $mkDumpDir = true;
	private static $dumpPrefix = '';
	private function getDumpFilename($function)
	{
		if (self::$mkDumpDir)
		{
			self::$mkDumpDir = false;
			self::$dumpDir = ZMC::$registry->debug_logs_dir . ($uri = $this->requestId . '.' . $this->what . '.' . $this->operation);
			if (!file_exists(self::$dumpDir))
			{
				umask(037);
				$result = ZMC::mkdirIfNotExists(self::$dumpDir);
				if (is_string($result))
				{
					$this->reply->addInternal($result);
					return '/dev/null';
				}
				$header = ZMC::$registry->debug_logs_dir . '/HEADER.html';
				if (!file_exists($header))
					if (false === file_put_contents($header, file_get_contents(__DIR__ . '/Yasumi/HEADER.html')))
						$this->reply->addError("Unable to copy HEADER.html to '$header'. " . ZMC::getFilePermHelp($header));
			}
		}

		if (!isset($this->dumpedFunctionCounts[$function]))
			$this->dumpedFunctionCounts[$function] = 1;

		return self::$dumpDir. DIRECTORY_SEPARATOR . self::$dumpPrefix . ($this->nDumps++ < 9 ? $prefix = '0' . $this->nDumps : $this->nDumps)
			. ' ' . $function . ($this->parentPid ? $this->pid : '') . $this->dumpedFunctionCounts[$function]++;
	}

	




	protected function dump($content = null, $depth = 0, $file = null, $line = null)
	{
		if (!ZMC::$registry->dev_only) return; 
		if ($this->debug_level < ZMC_Error::NOTICE) 
			return;
		if ($this->debug_level < ZMC_Error::DEBUG) 
			return;

		$header = '';
		if ($content === null)
		{
			$header = "===> Dumping \$this <===\n";
			$postData =& $this->postData;
			unset($this->postData);
			$humanPostData =& $this->humanPostData;
			unset($this->humanPostData);
			$content = print_r($this, true);
			$this->postData =& $postData;
			$this->humanPostData =& $humanPostData;
		}

		if ($file === null) 
			list ($function2, $file2, $line2) = ZMC_Error::getFileLine($function, $file, $line, $depth);
		else 
			list ($function2, $file2, $line2) = ZMC_Error::getFileLine($function, $file0, $line0, $depth);

		$label = $this->what . ':' . $this->operation . ":$function:";
		if ($this->debug_level < ZMC_Error::DEBUG) 
		{
			
			ZMC::log($label . "$file:$line:" . substr(is_array($content)? serialize($content, true) : $content, 0, 8192),
				0, $this->getLogInfo(array('dump')), ZMC::$registry->production_dump_log, ZMC_Error::DEBUG);
			return false;
		}

		$filename = $this->getDumpFilename($label . $function);
		if (ZMC::$registry->dev_only) 
			$url = '/Yasumi/index.php' . $this->pathInfo . '?human=1&' . (is_array($this->data) ? preg_replace('/\&timezone=.*timestamp=/', '&skipped=', ZMC::escapeGet($this->data)) : $this->data);
		else
			$url = $this->getUrl() . '/Yasumi/index.php' . $this->pathInfo . '?' . (is_array($this->data) ? ZMC::escapeGet($this->data) : $this->data);

		$printed = "requestUri: {$this->requestUri} \n$url\nPOST Data Input (human friendly format)
========================================
{$this->humanPostData}

REPLY
========================================
$function() #$line ($file)
$function2() #$line2 ($file2)
$header
"
			. (is_array($content) ? print_r($content, true) : $content);

		if (strlen($printed) !== file_put_contents($filename, $printed))
			throw new ZMC_Exception_YasumiFatal(ZMC::getFilePermHelp($filename));

		return $filename;
	}

	protected function init()
	{
	}

	
	public function &run($cleanupKeys = true)
	{
		if (!empty($this->data['throw_error']))
			$this->returnStatus('Returning error because received request asked for error', __FILE__, __LINE__, null, 
				(intval($this->data['throw_error']) > 399 ? intval($this->data['throw_error']) : 400));

		
		try
		{
			$fatal = false;
			$op = ucfirst($this->operation);
			$this->init();
			call_user_func(array($this, 'op' . $op));
		}
		catch(Exception $e) 
		{
			$fatal = $e;
			$this->debugLog($e); 
			if (is_object($this->reply) && !$this->reply->isErrors())
			{
				$this->reply->addInternal($msg = str_replace('0', ' ', ucFirst($this->what)) . ': ' . $op . ' exception');
				$error = self::ERROR_GENERIC;
				if (isset(self::$errors[$this->what][$this->operation]))
					$error = self::$errors[$this->what][$this->operation];
				$this->reply->addError($this->getAmandaConfName(false) . ": $this->operation() => $error");
				$this->reply->addDetail($e->__toString());
			}
		}

		try
		{
			$this->runFilter();
		}
		catch(Exception $e) 
		{
			$fatal = $e;
			$this->debugLog($e); 
			if (is_object($this->reply))
			{
				$this->reply->addInternal($msg = 'Run filter threw an exception.'); 
				$this->reply->addDetail($msg = "$msg: $e");
			}
			$this->errorLog($msg);
		}

		if (is_object($this->reply) && ($percent = ZMC_Timer::testMemoryUsage()))
		{
			$this->reply->addWarning($msg = "Low Memory: $percent% of PHP memory used");
			ZMC::debugLog("Yasumi: $msg");
		}

		$this->synchronousEnd('run'); 
		$reply = '';

		if ($this->_caller_source === 'REST')
			$reply = $this->yasumiStatus();

		if (empty($this->reply))
		{
			if ($this->_caller_source === 'REST')
				$reply .= ($fatal ? '{"fatal":true}' : '{}');
			if ($fatal)
				$this->debugLog("Reply was empty and a fatal error occurred: $e");
			else
				$this->debugLog('Reply was empty.');
		}
		elseif (is_object($this->reply))
		{
			if ($cleanupKeys)
				$this->reply->unsetKeys(); 
			if ($fatal)
				$this->reply['fatal'] = "$fatal"; 
			$this->reply['request'] = array('uri' => $this->requestUri);
			$name = $this->getAmandaConfName();
			if ($name)
				$this->reply['request']['amanda_configuration_name'] = $name;
			if (!empty($this->data)) 
				$this->reply['request']['post'] = $this->data;
			if ($this->_caller_source !== 'REST')
				$reply =& $this->reply;
			else 
			{
				$reply .= json_encode($this->reply);
			}
		}
		else 
			$this->returnStatus('Internal Error (reply was a string): ' . $this->reply, __FILE__, __LINE__, null, 500);

		$this->dump && $this->dump("==>" . $this->what . '/' . $this->operation . " Reply<==\n" . print_r($this->reply, true));
		$this->human && print("==>" . $this->what . '/' . $this->operation . " Reply (#" . __FILE__ . __LINE__ . ")<==\n" . print_r($this->reply, true));
		if (!empty($this->cacheFilename))
			if (strlen($reply) !== file_put_contents($this->cacheFilename, $reply))
				$this->reply->addWarnError(ZMC::getFilePermHelp($this->cacheFilename));

		if ($this->_caller_source === 'REST')
		{
			$clean = ob_get_clean();
			if (!empty($clean))
				$err = $this->errorLog("REST request, but spurious output in output buffer: $clean");
			$this->debugLog("NORMAL REST REPLY: $reply");
			$this->human ? print($clean) : print $reply;
			$reply = '';
		}
		return $reply;
	}

	protected function runFilter()
	{
		if (!is_object($this->reply))
			ZMC::quit($this);
		if (!empty($this->lstats))
			$this->reply['lstats'] = $this->lstats; 
	}

	
	public function __call($method, $args)
	{
		$msg = (strncasecmp($method, 'op', 2) ? 'UNDEFINED method' : 'UNDEFINED REST request');
		$msg = "$msg: $method instanceof " . get_class($this) . ": component $this->what -> $this->operation";
		if ($this->reply instanceof ArrayObject)
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal($msg));
		$this->returnStatus($msg, __FILE__, __LINE__, null, 400);
	}
	
	







	protected function synchronousBegin($async = null)
	{
		if ($async !== true && empty($this->mode))
			return true;

		set_time_limit(1800);
		register_shutdown_function(array($this, 'synchronousEnd')); 
		declare(ticks = 1);
		
		if (!empty($GLOBALS['session_started'])) 
			ZMC::quit('$GLOBALS[session_started] not empty');
		$this->childPid = pcntl_fork();
		if ($this->childPid == -1)
		{
			$this->errorLog("fork FAILED: " . posix_strerror(posix_get_last_error()), __FILE__, __LINE__, null, false);
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('yasumi: could not fork'));
		}
		elseif ($this->childPid)
		{
			$this->debugLog($this->requestId . '.' . $this->what . '.' . $this->operation . ": Parent forked child. Child pid: " . $this->childPid, __FILE__, __LINE__);
			return false; 
		}
		else 
		{
			$GLOBALS['disable_sessions'] = true; 
			ZMC_Mysql::voidConnection(); 
			$this->parentPid = $this->pid;
			$this->pid = posix_getpid();
			ZMC::$registry->debug_log = ZMC::$registry->debug_child_log; 
			ini_set('error_log', ZMC::$registry->debug_child_log); 
			
			$this->requestId .= '.' . $this->pid; 
			if (class_exists('ZMC_Mysql', false)) 
				ZMC_Mysql::voidConnection(); 

			$this->debugLog($this->requestId . ': child ' . $this->pid . ' born from parent ' . $this->parentPid . ' for request ' . $this->requestId);
			ZMC_Error::$silent = true; 
			$sleep = 0;

			if ($sleep)
			{
				error_log($this->requestId . ": child of {$this->parentPid} after fork (sleeping $sleep seconds)", __FILE__, __LINE__);
				$sleep && sleep($sleep);
			}
			if (posix_setsid() < 0)
				error_log('yasumi: child pid ' . posix_getpid() . ' could not detach: ' . posix_strerror(posix_get_last_error()));
		}
		return true; 
	}

	






	public function synchronousEnd($shutdown = 'shutdown')
	{
		
		if ($this->endedOnce)
			return;
		$this->endedOnce = true;
		
		
		if ($this->childPid === 0) 
		{
			$this->debugLog('child process self-terminating normally ...', __FILE__, __LINE__, null, $shutdown);
			error_log($this->requestId . ': child ' . $this->pid . ' born from parent ' . $this->parentPid . ' self-terminating normally');
			$stdout = ob_get_clean();
			if ($stdout === false)
				$stdout = __FILE__ . __LINE__ . ' fixme';
			if (!empty($stdout))
			{
				error_log("WARNING: output detected for child process: ");
				error_log($stdout);
			}
			error_log("===================================================================\n\n");
			$result = posix_kill($this->pid, SIGKILL); 
			
			$this->errorLog("child failed to self-terminate (zombie born)", __FILE__, __LINE__, 'process', $result);
			exit(-1);
		}

		ZMC::waitpids();
	}

	




	private function getData()
	{
		$jsonPostData = '';
		if (!empty($this->postData) && ($this->postData[0] === '{')) 
		{
			$postData = json_decode($this->postData, true);
			if (empty($postData))
				$this->returnStatus('JSON input could not be decoded: ' . $this->postData, __FILE__, __LINE__, null, 400);

			$this->merge($this->data, $postData); 
			
			
			if ($this->dump && $this->debug_level > ZMC_Error::NOTICE)
			{
				$jsonPostData =& $this->postData;
				$humanPostData = print_r($postData, true);
			}
		}
		elseif (!empty($this->post))
		{
			if ($this->dump && $this->debug_level > ZMC_Error::NOTICE)
			{
				$jsonPostData = json_encode($this->post);
				$humanPostData = print_r($this->post, true);
			}
			
			switch (count($this->post))
			{
				case 0:	 
					break;
	
				case 1:
					$key = key($this->post);
					if (!is_array($this->post[$key]))
					{
						
						$post = urldecode($this->post[$key]);
						if (!empty($post))
						{
							$data = json_decode($post, true);
							if ($data !== false)
							{
								$this->merge($this->data, $data);
								break;
							}
							else
								$this->errorLog("Warning: posted data failed json_decode().  Using '$key' instead.", __FILE__, __LINE__);
						}
					}
					$this->data[$key] = current($this->post);
					break;

				default:
					$this->errorLog('Warning: received multiple form fields (' . implode(' ', array_keys($this->post))
						. '); merging $_POST and not json_decoding POST data for request: ' . $this->requestUri, __FILE__, __LINE__);
					$this->merge($this->data, $this->post);
					break;
			}
		}

		if ($this->dump && !empty($humanPostData) && ($this->debug_level > ZMC_Error::NOTICE) && ($fn = $this->dump("==>Raw Input POST Data<==\n$humanPostData")))
		{
			$this->humanPostData =& $humanPostData;
			file_put_contents("$fn.post", $jsonPostData);
			
			file_put_contents("$fn.wget", 'source ' . ZMC::$registry->cnf->cnf . '; '
				. 'wget -O wget.out --user=rest --password="$zmc_yasumi_passwd" --no-check-certificate --post-file='
				. $fn . '.post \'' . preg_replace('/127\.0\.0\.1/', 'localhost', $this->getUrl())
				. preg_replace('/&timestamp=[0-9]*/', '', $this->requestUri) . "'");
		}
	}
	
	



	protected function getFilteredReplyData()
	{
		foreach($this->filterKeys as $key)
			unset($this->reply[$key]);

		foreach($this->denormalizeKeys as $normalized => $denormalized)
			if (array_key_exists($normalized, $this->reply))
			{
				$this->reply[$denormalized] = $this->reply[$normalized];
				unset($this->reply[$normalized]);
			}

		return json_encode($this->reply);
	}

	protected function mkdirIfNotExists($dir, $failureOk = false, $mode = 0700)
	{
		$result = ZMC::mkdirIfNotExists($dir, $mode);
		if ($result === true || $result === false)
			return $result;

		if ($failureOk)
			$this->reply->addWarning($result);
		else
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal($result));

		return null;
	}

	protected function standardFieldCheck(array $fields = array('conf'))
	{
		$required = array_merge(array('commit_comment', 'username', 'user_id'), $fields);
		if ($this->mode)
		{
			$this->optional[] = 'callback_username';
			$this->optional[] = 'callback_password';
		}
		$this->checkFields($required);
	}

	








	protected function checkFields(array $required = null, array $optional = null)
	{
		$keys = array_keys($this->data);
		if (!empty($required))
			$this->required = array_merge($this->required, $required);
		if (!empty($optional))
			$this->optional = array_merge($this->optional, $optional);
		if (!empty($this->required))
		{
			$diff = array_diff($this->required, $keys);
			$missing = '';
			foreach($diff as $key)
				if (!isset($this->$key))
					$missing .= "$key, ";
			foreach($this->required as $key)
				if (array_key_exists($key, $this->data) && ($this->data[$key] === null || $this->data[$key] === ''))
					$missing .= "null/empty $key, ";
			if ($missing !== '')
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal($this->operation . '::' . $this->what . ' - missing field(s):' . $missing . ($this->debug ? "\nGot:" . print_r($this->data, true) : '')));
		}

		if (!empty($this->optional))
		{
			if (empty($this->required))
				$diff = array_diff($keys, $this->optional);
			else
				$diff = array_diff($keys, $this->required, $this->optional);

			
			
		}
		foreach($this->data as $key => $value)
			if ($value === '' || $value === null)
				$this->debugLog("Warning: input key '$key' empty", __FILE__, __LINE__);
	}

	



	protected function extractOptionalKeys()
	{
		if (!is_array($this->data))
			$this->returnStatus('$this->data is not an array! ' . $this->data, __FILE__, __LINE__, null, 500);

		foreach($this->optionalOptions as $property => $normalized)
		{
			if (array_key_exists($property, $this->data))
			{
				if ($this->data[$property] === null || $this->data[$property] === '')
					$this->returnStatus("'$property' field has empty value" , __FILE__, __LINE__, null, 400);
				if ($normalized)
				{
					$this->$normalized = $this->data[$normalized] = $this->data[$property];
					unset($this->data[$property]);
					if (!array_search($normalized, $this->filterKeys)) 
						$this->denormalizeKeys[$normalized] = $property; 
				}
				else
					$this->$property = $this->data[$property];
				
			}
		}

		
		if (!empty($this->callback_location))
		{
			if (strncmp($this->callback_location, 'https://', 8))
				$this->returnStatus('callback_location must start with "https://" (got ' . $this->callback_location . ')', __FILE__, __LINE__, null, 400);
			if (empty($this->callback_username) || empty($this->callback_password))
				$this->returnStatus('callback credentials must not be empty', __FILE__, __LINE__, null, 400);
		}
		if (!empty($this->timezone) && $this->timezone != ZMC::$registry->tz['timezone'])
		{
			$this->returnStatus("ZMC/Amanda/Yasumi server configuration problem: timezones do not match ({$this->timezone} != " . ZMC::$registry->tz['timezone'] . ')', __FILE__, __LINE__, null, 500);
		}
		
		if (!empty($this->timestamp) && (($this->timestamp < time() - ZMC::$registry->max_clock_skew) || ($this->timestamp > time() + ZMC::$registry->max_clock_skew)))
		{
			$this->returnStatus('ZMC/Amanda/Yasumi server time synchronization problem: check clock skew on server(s)', __FILE__, __LINE__, null, 500);
		}
	}

	




	public function noticeLog($message, $file = null, $line = null, $tags = array(), $status = 1)
	{
		return $this->log($message, ZMC_Error::NOTICE, $file, $line, $tags, $status);
	}

	




	public function errorLog($message, $file = null, $line = null, $tags = array(), $status = 1)
	{
		return $this->log($message, ZMC_Error::ERROR, $file, $line, $tags, $status);
	}

	



	public function debugLog($message, $file = null, $line = null, $tags = array(), $status = 0)
	{
		return $this->log($message, ZMC_Error::DEBUG, $file, $line, $tags, $status);
	}

	










	protected function log($message, $level, $file = null, $line = null, $tags = array(), $status = null, $logit = true)
	{
		if (empty($file))
			ZMC_Error::getFileLine($function, $file, $line, 2);
		$file = strrchr($file, '/'); 
		if (is_array($message))
			$message = print_r($message, true);
		$message = 'yasumi~' . $this->what . '~' . $this->operation . "~$file~$line~" . $this->requestId
			. '~' . str_replace("\n", "; ", $message);
		if ($logit && $level <= $this->debug_level) 
			ZMC::log($message, ($status === null ? 1:0), $this->getLogInfo($tags), ZMC::$registry->debug_log, $level);



		return $message;
	}

	









	private function returnStatus($message, $file = null, $line = null, $tags = '', $status = 200, $failureOk = false)
	{
error_log(__FUNCTION__ . "(): $message $file $line");
		if ($failureOk)
		{
			if ($this->debug_level < ZMC_Error::NOTICE)
				return false;

			$level = ZMC_Error::DEBUG;
		}
		else
		{
			$level = ($status === 200 ? ZMC_Error::INFO : ZMC_Error::ERROR);
			if ($this->_caller_source === 'REST')
				self::yasumiStatus();
		}

		$last = ZMC_Error::error_get_last();
		if (!empty($last['message']) && !strncmp($last['message'], 'Undefined variable:', 19))
			$message .= ' (' . $last['message'] . ')';

		if (empty($file))
			ZMC_Error::getFileLine($function, $file, $line, 2);
		$msg = str_replace('return status:', '', $this->log("return status: $message", $level, basename($file), $line, $tags, $status != 200, true));

		if ($failureOk)
			return false;

		if ($this->_caller_source !== 'REST')
			throw new ZMC_Exception_YasumiFatal($msg, $status, $file, $line); 

		ZMC_Error::returnStatus($msg, $status);
	}

	




	protected function getLogInfo($tags = array())
	{
		if (empty($tags))
			$tags = $this->tags;
		elseif (is_integer($tags))
			throw new ZMC_Exception_YasumiFatal(__FUNCTION__ . ' received integer input instead of string or array');
		elseif (!empty($this->tags))
			$tags = $this->mergeTags($this->tags, $tags);

		return array(
			'config' => $this->getAmandaConfName(),
			'facility' => $this->facility,
			'tags' => $tags,
			'user_id' => $this->getUserId(),
			'username' => (empty($this->username) ? (empty($this->data['user_name']) ? 'unknown' : $this->data['user_name']) : $this->username)
		);
	}

	protected function getUserId()
	{ return empty($this->user_id) ? (empty($this->data['user_id']) ? '-1' : $this->data['user_id']) : $this->user_id; }

	





	private function mergeTags($tag1, $tag2)
	{
		if (is_string($tag1))
			$tag1 = array($tag1 => true);
		if (is_string($tag2))
			$tag2 = array($tag2 => true);
		return array_merge($tag1, $tag2);
	}

	protected static function emptyReply($stats, $filename, $results)
	{
		self::yasumiStatus(); 
		echo json_encode($results);
		if (!empty($_GET['human']))
		{
			echo "\n'$filename' empty: \n";
			$stats && print_r(array_slice($stats, 13), true);
		}
		exit(0);
	}

	public static function header($header)
	{
		if (isset($_SERVER['REDIRECT_REMOTE_USER']))
			header($header);
	}

	





	public function permCheck($file, $missingOk = false)
	{
		if (!is_string($file) || $file === '')
			ZMC::quit($file);
		if ($file[0] === '/')
			$dir = dirname($file);
		else
		{
			$amandaConfig = $this->getAmandaConfName(true);
			$dir = ZMC::$registry->etc_amanda . $amandaConfig;
			$file = $dir . DIRECTORY_SEPARATOR . $file;
		}

		if (ZMC::is_readwrite($dir))
			$msg = $this->reply->addWarnError("Directory '$dir': either does not exist or permissions prevent reading and/or writing");
		elseif (ZMC::is_readwrite($file, false))
			if (file_exists($file))
				$msg = $this->reply->addInternal("Unable to read and/or write to '$file'");
			elseif ($missingOk === false)
				$msg = $this->reply->addInternal("Missing file: '$file'");

		if (!empty($msg) && ZMC::$registry->safe_mode)
			throw new ZMC_Exception_YasumiFatal($msg);

		return $file;
	}

	






	protected function commit($orig, $bak, $new = null, $contents = null, $callback = null)
	{
		if (empty($orig) || empty($bak))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal(__FUNCTION__ . '() called with empty filename'));

		if ($new === null)
			$new = $orig;

		$len=strlen(ZMC::$registry->var_log_zmc);
		if (!strncmp(ZMC::$registry->var_log_zmc, $bak, $len && $bak[$len] === DIRECTORY_SEPARATOR && !strpos($bak, '..')))
			$this->mkdirIfNotExists(dirname($bak));

		if (file_exists($orig) && (!copy($orig, $bak))) 
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Save failed. Unable to make a backup by copying '$orig' to '$bak'."));

		if ($contents === null) 
		{
			if (!file_exists($orig))
				return $this->reply->addWarning("Can not delete. File '$orig' not found (already deleted?).");

			if (false === unlink($orig))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Deleting '$orig' failed. Permission problems?"));
		}
		elseif (false === file_put_contents($new, $contents, LOCK_EX))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Write failed. Unable to write '$new'."));

		if ($orig !== $new)
		{
			if ($callback)
			{
				$result = call_user_func(array($this, $callback), $new);
				if ($result !== true)
				{
					unlink($new);
					throw new ZMC_Exception_YasumiFatal($this->reply->addError("Save of '$orig' refused: $result"
						. ($this->debug ? " by callback '$callback'" : '')));
				}
			}

			if (!rename($new, $orig))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Save failed. Unable to rename($new, $orig)."));
		}

		$this->noticeLog("Wrote '$orig'", __FILE__, __LINE__, 'DLE', 0);
	}

	
	protected function postResponse($filteredData)
	{
		if (is_array($filteredData))
		{
			$json = json_encode($filteredData);
			$data = &$filteredData;
		}
		else
		{
			$json = &$filteredData;
			$data = json_decode($filteredData, true);
		}

		$err = $this->what . '/' . $this->operation . ' missing callback_';
		if (empty($this->callback_location))
			$errSuffix = 'location';
		elseif (empty($this->callback_username))
			$errSuffix = 'username';
		elseif (empty($this->callback_password))
			$errSuffix = 'password';
		if (!empty($errSuffix))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal($err . $errSuffix));

		$this->dump(array(
			'Callback Location' => $this->callback_location,
			'POST Reply Data' => $data,
			'username' => $this->callback_username,
			'password' => (ZMC::$registry->dev_only ? $this->callback_password : '***')
			));

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => $this->callback_location,
			
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $json,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_TIMEOUT => 9,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false, 
			CURLOPT_SSL_VERIFYHOST => 1,
			CURLOPT_HEADER => 1,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $this->callback_username . ':' . $this->callback_password,
			
		));
		$rawResult = curl_exec($ch);
		if ($rawResult === '')
			$rawResult = 'Nothing received by Yasumi from ZMC ' . date("F j, Y, g:i:s a");
		
		
		

		$error = '';
		if (curl_errno($ch))
		{
			$error .= curl_error($ch) . '; ';
		}

		if (curl_errno($ch) > 4 && curl_errno($ch) < 8)
		{
			$url = parse_url($this->callback_location);
			$error .= "Unable to connect to {$url[host]}; ";
		}
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code != 200)
			$error .= "HTTP response $code; ";
		curl_close($ch);

		if (!empty($error))
			$this->errorLog(__FUNCTION__ . "() error=$error", __FILE__, __LINE__);

		$this->debugLog(__FUNCTION__ . '() callback=' . $this->callback_location
			. "=> HTTP code $code; error=$error result:" . substr($rawResult, 0, 64), __FILE__, __LINE__, empty($error));
	}
	
	




	protected function getAmandaConfName($required = false)
	{
		if (empty($this->amanda_configuration_name) && (!empty($this->options)))
				$this->amanda_configuration_name = array_shift($this->options);

		if ($required)
		{
			if (empty($this->amanda_configuration_name))
				throw new ZMC_Exception_YasumiFatal($this->reply->addError('Missing backup set name (code #' . __LINE__ . ').'));

			$fn = ZMC::$registry->etc_amanda . $this->amanda_configuration_name;
			if (!file_exists($fn))
				throw new ZMC_Exception_YasumiFatal($this->reply->addError("$fn: Backup set does not exist on disk."));
			if ($result = ZMC::is_readwrite($fn))
			{
				$msg = $this->reply->addError($result);
				if (ZMC::$registry->safe_mode)
					throw new ZMC_Exception_YasumiFatal($msg);
			}
		}

		return (empty($this->amanda_configuration_name) ? false : $this->amanda_configuration_name);
	}

	protected function getAmandaConfPath($required = true)
	{
		if (false === ($name = $this->getAmandaConfName($required)))
			return false;

		return ZMC::$registry->etc_amanda . $this->getAmandaConfName($required) . DIRECTORY_SEPARATOR;
	}

	public function merge(&$result, $array, $overwrite = true)
	{
		if (empty($array))
			return;

		if (!is_array($array) || !is_array($result))
		{
			if (ZMC::$registry->dev_only) ZMC::quit(array($array,$result));
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal(__FUNCTION__ . '() one or more args were not arrays'));
		}

		ZMC::merge($result, $array, $overwrite);
	}

	public function &loadYaml($filenames)
	{
		if (!is_array($filenames))
			$filenames = array($filenames);

		$results = array();

		foreach($filenames as $filename)
		{
			$readFilename = $this->permCheck($filename);
			$yaml = ZMC_Yaml_sfYaml::load($readFilename);
			if (!is_array($yaml))
				continue;
	
			if (count($filenames) === 1)
				return $yaml;

			foreach ($yaml as $key => &$value)
				$results[$key] =& $value;
		}

		return $results;
	}

	public function dumpYaml($content, $depth = 5)
	{
		ksort($content);
		return ZMC_Yaml_sfYaml::dump($content, $depth);
	}


	public function &yaml2conf($conf)
	{
		
		try
		{
			$contents =& ZMC_Yasumi_Parser::data2conf($conf);
		}
		catch (ZMC_Exception $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Parsing problem: ' . $e->getMessage() . " for input: $conf"));
		}
		
		return $contents;
	}

	protected function getMacros()
	{
		return array();
	}

	













	protected function normalizeParams(array &$params, $defaultUnit = null, $lc = -1, $depth = 0, $normKeys = true, $ancestor = null, $macros = null)
	{
		if ($macros === null)
			$macros = $this->getMacros();
		
		$new = array();
		foreach($params as $key3 => $value3)
		{
			switch(substr($key3, -8))
			{
				case '_comment':
				case '_line':
					$new[$key3] = $value3;
					continue 2;
			}

			if ($normKeys)
			{
				$newKey = str_replace('-', '_', $key3);
				if ($depth >= $lc)
					$newKey = strtolower($newKey);
			}
			else
				$newKey = $key3;

			if (!empty($macros) && is_string($value3) && (false !== strpos($value3, '@@'))) 
			{
				$newValue = str_replace(array_keys($macros), array_values($macros), $value3);
				if ($newValue !== $value3)
					$value3 = $newValue;
			}

			
			$new[$newKey] = $value3;
		}

		$display = array();


		foreach($new as $key => &$value)
		{
			
			
			

			if ($pos = strrpos($key, '_'))
			{
				$suffix = substr($key, $pos);
				if ($suffix === '_comment' || $suffix === '_display' || $suffix === '_line')
					continue;
			}

			$digitKey = ctype_digit($key);
			if ($normKeys && $digitKey)
				throw new ZMC_Exception_YasumiFatal("Corrupted key found: '$key'");

			if (is_array($value))
			{
				$newAncestor = $ancestor;
				if ((substr($key, -5) === '_list') || empty($ancestor) || substr($ancestor, -5) !== '_list') 
					$newAncestor = $key;

				if($key === "property_list"){
					if($this->reply['binding_conf']['_key_name'] == "changer_library"){
						if(!isset($new[$key]['slotrange'])){
							if(isset($new[$key]['firstslot']) && isset($new[$key]['lastslot'])){
								$new[$key]['slotrange']  = $new[$key]['firstslot'] ."-". $new[$key]['lastslot'];
								$new[$key]['use_slots']  = $new[$key]['firstslot'] ."-". $new[$key]['lastslot'];
								unset($new[$key]['firstslot']);
								unset($new[$key]['lastslot']);
							}
						}
					}
				}

				if ($key !== 'includefiles' && $key !== 'private' && $key !== 'schedule')
					$this->normalizeParams($value, $defaultUnit, $lc, $depth + 1, substr($key, -13) !== "property_list" && $key !== "dle_list" && $key !='tapedev', $newAncestor, $macros);
				continue;
			}

			if ($ancestor === 'device_property_list') 
				if (!isset($new[$key . '_display']))
					continue;

			switch($key)
			{
				case 'compress':
					if (trim($value) === '')
						$value = 'none';
					break;

				case 'encrypt':
					if (trim($value) === '')
						$value = 'none';
					break;

				
				case 'exclude':
				case 'include':
					if ($value !== null)
						$value = trim($value);
					if ($value === '')
						$value = null;
					break;

				case 'comment':
					$value = str_replace("\n", '  ', $value);
					break;

				
				
				case 'ignore':
					if (!$normKeys) 
						break;
				case 'amrecover_check_label':
				case 'autoflush':
				case 'autoclean':
				case 'eject_before_unload':
				case 'havereader':
				case 'holding':
				case 'index':
				case 'ignore_barcodes':
				case 'kencrypt':
				case 'LEOM': 
				case 'offline_before_unload':
				case 'record':
				case 'skip_full':
				case 'skip_incr':
				case 'usetimestamps':
				case 'ejectdelay':
				case 'gravity':
				case 'multieject':
				case 'needeject':

				
				case 'enabled':
				case 'part_size_auto':
				case 'tape_splitsize_auto':
				case 'zmc_show_advanced':
				case 'bandwidth_toggle':
					switch(strtolower($value))
					{
						case 'no':
						case 'false':
						case 'f':
						case 'off':
						case 'disabled':
						case '0':
						case 'n':
						case '':
							$value = 'off';
							break;
						default:
							$value = 'on';
							break;
					}
					break;

				
				case 'has_barcode_reader':
					if (empty($value))
						$value = false;
					else
						$value = true;
					break;

				
				
				case 'readblocksize':
					if (empty($value))
					{
						$value = null;
						break;
					}
					break;
				case 'maxdumpsize':
					if ($value === '-1') 
					{
						$value = '0m'; 
						break;
					}
				case 'use':
					if ($key === 'use')
						if ($ancestor === 'interface_list') 
							break;
						elseif ($ancestor === 'holdingdisk_list')
							if (empty($value))
							{
								$value = '0';
								break;
							}
				case 'use_request':
				case 'BLOCK_SIZE':
				case 'bumpsize':
				case 'chunksize':
				case 'device_output_buffer_size':
				case 'device_output_buffer_size':
				case 'fallback_splitsize':
				case 'fallback_splitsize_default':
				case 'filemark':
				case 'file_pad':
				case 'length':
				case 'part_cache_max_size':
				case 'speed':
				case 'tapebufs':
				case 'tape_splitsize':
				case 'partition_total_space':
				case 'partition_used_space':
				case 'partition_free_space':
				case 'used_space':
				case 'part_size':
					if ($value === null)
						break;

					if (empty($value))
					{
						$value = '1m';
						break;
					}

					if ($key === 'use_request' && isset($new['use_request_display']) && $new['use_request_display'] === '%')
					{
						$value = intval($value);
						if ($value < 1 || $value > 100)
							$this->reply->addWarnError("Illegal value '$value' for parameter '$key' (code " . __LINE__ . ')');
						$display[$key] = '%';
						break;
					}

					$this->getDigitsUnit($key, $value, $digits, $unit,
						((empty($defaultUnit) && isset($new[$key . '_display'])) ? $new[$key . '_display'] : ZMC::$registry->default_units));
					
					
					switch(strtolower(ZMC::$registry->units['storage_equivalents'][strtolower($unit)])) 
					{
						case 'b':
							$value = bcdiv($digits, 1024 * 1024, 0);
							$value = max($value, 1); 
							break;
						case 'k':
							$value = bcdiv($digits, 1024, 0);
							$value = max($value, 1); 
							break;
						case 'm':
							$value = $digits;
							break;
						case 'g':
							$value = bcmul($digits, 1024, 0);
							break;
						case 't':
							$value = bcmul($digits, 1024 * 1024, 0);
							break;
						default:
							$this->errorLog("Unsupported unit ($unit) used with value ($value) for key '$key'.", __FILE__, __LINE__, null, 499);
							$value = $digits;
							$unit = 'm';
							break;
					}
					$value .= 'm';
					if ($unit === 'b' || $unit === 'k') 
						$unit = 'm';
					$display[$key] = $unit;
					break;

				
				case 'maxpromoteday':
				case 'dumpcycle':
				case 'dumpdays':
				case 'bumpdays':
					$this->getDigitsUnit($key, $value, $digits, $unit, 'days');
					if (!strncasecmp($unit, 'week', 4))
						$digits *= 7; 
					$value = "$digits days";
					$display[$key] = $unit;
					break;

				
				case 'netusage':
					break;

				
				case 'cleanslot':
					$value = intval($value);
					break;
				case 'slotrange':
					if(!strncmp($this->data['binding_conf']['_key_name'], 'changer', 7))
					{
						$reply = self::operation($this->reply, array('pathInfo' => '/Tape-Drive/discover_changers'));
						$this->reply->merge($reply);
						$max_slots = 1;
						if(!empty($this->reply['changerdev_list'][$new['changerdev']]['tape_slots'])){
							$max_slots = $this->reply['changerdev_list'][$new['changerdev']]['tape_slots'];
							$this->validateSlotRange($value, '', $max_slots);
						}
					}
					$value = $value;
					break;

				
				case 'blocksize': 
				case 'bumpmult':
				case 'bumppercent':
				case 'ctimeout':
				case 'dtimeout':
				case 'etimeout':
				case 'inparallel':
				case 'max_dle_by_volume':
				case 'max_drive_wait':
				case 'max_slots':
				case 'maxdumps':
				case 'part_size_percent':
				case 'poll_drive_ready':
				case 'poll_frequency':
				case 'resend_mail':
				case 'retention_period':
				case 'runtapes':
				case 'slots':
				case 'spindle':
				case 'status_interval':
				case 'tapecycle':
				case 'timeout_mail':
				case 'total_tapes':
					if ($value === null) 
						break;
					$value = intval($value);
					if (!($value > 0))
						$this->reply->addWarnError("Illegal value '$value' for parameter '$key' (code " . __LINE__ . ')');
					break;

				
				case 'autocleancount':
				case 'driveslot':
				case 'eject_delay':
				case 'filesystem_reserved_percent':
				case 'initial_poll_delay':
				case 'max_drive_wait':
				case 'minutes': 
				case 'reserve':
				case 'unload_delay':
				case 'runspercycle':
				case 'used_space':
					if ($value === null) 
						break;
					$value = intval($value);
					if (!($value >= 0) || ($key === 'reserve' && $value > 100))
						$this->reply->addWarnError("Illegal value '$value' for parameter '$key'");
					break;

				case 'taper_parallel_write':
					$value = intval($value);
					if($value <= 0)
						$this->reply->addWarnError("Illegal value '$value' for parameter '$key' (code " . __LINE__ . ')');
					break;
						
				
				case 'data_path':
					if ($value !== 'directtcp' && $value !== 'amanda')
						$this->reply->addWarnError("Unsupported '$value' for parameter '$key'");
					break;

				case 'tape_splitsize_percent':
					$value = intval($value);
					if ($value < 1 || $value > 100)
						$this->reply->addWarnError("Illegal value '$value' for parameter '$key'");
					break;

				
				case '_key_name':
				case 'allocated_space':
				case 'amrecover_changer':
				case 'amrecover_check_label':
				case 'amrecover_do_fsf':
				case 'autolabel':
				case 'autolabel_how':
				case 'autolabel_format':
				case 'changerdev':
				case 'changerdev_prefix':
				case 'changerfile':
				case 'config_name':
				case 'creation_time':
				case 'directory':
				case 'diskfile':
				case 'disk_device':
				case 'disk_name':
				case 'displayunit':
				case 'driveorder':
				case 'dom': 
				case 'drive_choice':
				case 'dumporder':
				case 'dumpuser':
				case 'estimate':
				case 'free_space':
				case 'host_name':
				case 'holdingdisk':
				case 'id':
				case 'indexdir':
				case 'infofile':
				case 'labelstr':
				case 'label_new_tapes':
				case 'last_modified_by':
				case 'last_modified_date':
				case 'last_modified_time':
				case 'lbl_templ':
				case '_line':
				case 'license_group':
				case 'logdir':
				case 'mailto':
				case 'meta_autolabel':
				case 'name':
				case 'ndmp_auth':
				case 'ndmp_username':
				case 'ndmp_password':
				case 'occ':
				case 'org':
				case 'part_cache_type':
				case 'plugin':
				case 'request':
				case 'reserved_tcp_port':
				case 'schedule_type':
				case 'strategy':
				case 'tape_device':  
				case 'tapedev':
				case 'tapedev_prefix':
				case 'taperalgo':
				case 'tapetype':
				case 'tpchanger':
				case 'firstslot':
				case 'lastslot':
				case 'use_slots': 

				
				case 'natural_key':
				case 'natural_key_orig':
				case 'include_barcode':
				case 'overwrite_media':
					break;

				case 'zmc_type':
					$value = strtolower($value); 
				default:
					if (strncmp($key, 'zmc_', 3))
						$this->errorLog(__FUNCTION__ . "() - Unrecognized parameter: $key=" . (is_array($value) || ($value instanceof ArrayObject) ? 'Array' : $value) . " (ancestor=$ancestor)");
					
					break;
			}
		}

		foreach($display as $key2 => $value2) 
		{
			if ($value2 === null)
			{
				unset($new[$key2]);
				unset($new[$key2 . '_display']);
			}
			elseif (!isset($new[$key2 . '_display']))
				$new[$key2 . '_display'] = $value2;
		}

		$params = $new;
	}

	private function getDigitsUnit($key, $value, &$digits, &$unit, $defaultUnit)
	{
		$unit = $defaultUnit;
		$value = trim($value);
		for ($i=strlen($value) -1; $i >= 0; $i--)
			if ('0' <= $value[$i] && $value[$i] <= '9')
			{
				$digits = trim(substr($value, 0, $i+1));
				if (strlen($value) > $i+1) 
					$unit = trim(substr($value, $i+1));

				if (empty(ZMC::$registry->units['storage_equivalents'][strtolower($unit)]))
				{
					$this->debugLog("Key '$key' - Unknown unit type '$unit' used with value '$value'", __FILE__, __LINE__);
					$unit = '';
				}

				return;
			}
		$digits = 0;
	}

	protected function fileAppend($where, $what)
	{
		if ($fp = fopen($where, 'a'))
		{
			$result = fwrite($fp, $what);
			fclose($fp);
		}
		if ($fp === false || $result === false)
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to update '$where'.  File permissions?"));
	}

	




	public static function yasumiStatus()
	{
		$status = 'Yasumi-Status: 200'; 
		$reply = '';
		if (headers_sent())
			$reply = "\n$status\n";
		else
			self::header($status);
		return $reply;
	}

	



	public static function optimizeRead()
	{
		if (!empty($_REQUEST['nocache']))
			return;
		$options = explode('/', $_SERVER['PATH_INFO']);
		$post = file_get_contents('php://input');
		error_log('Y#' . __LINE__ . ': ' . substr($this->requestUri, 8) . str_replace("\n", '; ', print_r($_GET,true)) . '; POST=' . $post . "\n",
			3, self::YASUMI_LOG);
		if (!empty($_REQUEST['human']))
			return;
		if ($options[2] !== 'read') 
			return;

		$config = current($_GET); 
		(count($options) > 3) && !empty($options[3]) && $config = $options[3];
		if (!empty($_REQUEST['amanda_configuration_name']))
			$config = $_REQUEST['amanda_configuration_name'];

		switch($options[1])
		{
			case 'conf':
				$filename = self::ETC_AMANDA . "$config/" . $_REQUEST['what'];
				$emptyResults = array(
					'conf' => array()
				);
				break;

			default:
				!empty($_REQUEST['debug']) && error_log('Y#' . __LINE__ . ": caching not supported\n", 3, self::YASUMI_LOG);
				return;
		}
		$emptyResults['request'] = array('amanda_configuration_name' => $config, 'post' => $_POST, 'uri' => $this->requestUri);

		$stats = stat($filename);
		
		if ($stats === false)
			return;

		if (false && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			$pdate = strptime('D, d M Y H:i:s %Z', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
			
			$ifTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if ($stats['mtime'] <= $ifTime)
			{
				self::header('HTTP/1.0 304 Not Modified');
				self::yasumiStatus(); 
					!empty($_REQUEST['debug']) && error_log('Y#' . __LINE__ . ": not modified: '$cacheFilename'\n", 3, self::YASUMI_LOG);
				exit(0);
			}
		}

		
		
		self::header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $stats['mtime']) . ' GMT');
		($stats['size'] === 0) && self::emptyReply($stats, $filename, $emptyResults);
		$cacheFilename = ZMC::TMP_PATH . $config . '-' . basename($filename);
		$cacheStats = @stat($cacheFilename);
		if ($cacheStats && ($cacheStats['mtime'] > $stats['mtime']))
		{
			$results = file_get_contents($cacheFilename);
			if ($results !== false)
			{
				self::yasumiStatus(); 
				echo $results;
				!empty($_REQUEST['debug']) && error_log('Y#' . __LINE__ . ": Cached copy used: '$cacheFilename'\n", 3, self::YASUMI_LOG);
				exit(0);
			}
		}
		return $cacheFilename;
	}

	






	protected function checkPath($paths, $path, $readOnly = false)
	{
		$matches = array();
		$result = ZMC::checkPath($paths, $path, $matches);
		if (!empty($result))
			return $result;

		if (!$readOnly)
		{
			$result = $this->mkdirIfNotExists($path);
			if (!ZMC::$registry->security_warnings)
				return false;
			$stats = stat($path);
			$mode = base_convert($stats['mode'], 10, 8);
			$mode3 = substr($mode, -3);
			if ($mode3 === '700')
				return false;

			$warn = "$path has mode $mode";
			if ($result === true)
				$this->reply->addWarning("Warning: $warn. This warning can be disabled on the Admin|Preferences page (toggle security warnings).");
			else
				$this->reply->addMessage($warn);

		}
		return false;
	}

	



	protected function &command($args, $reply = null)
	{
		if (false === ob_start())
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('ob_start() failed'));

		if (empty($args['requestUri']) && isset($args['pathInfo']))
			$args['requestUri'] = "/Yasumi/index.php$args[pathInfo]?" . ZMC::escapeGet(isset($args['data']) ? $args['data'] : $this->data, true);
		$args['_caller_source'] = 'COMMAND'; 
		$dumpPrefix = self::$dumpPrefix;
		self::$dumpPrefix .= ($this->nDumps < 9 ? $prefix = '0' . $this->nDumps : $this->nDumps) . '.';
		$yasumiObject = $this->promote($args, $reply);
		$yasumiObject->setRequestId();
		$result =& $yasumiObject->run($reply === null);
		self::$dumpPrefix = $dumpPrefix;
		$stdout = ob_get_clean();
		if (!empty($stdout))
			$this->debugLog($stdout);
		return $result;
	}

	





	public static function &operation(ZMC_Registry_MessageBox $pm, $args, $merge = false)
	{
		








		if (empty($args['postData']))
			$args['postData'] = null;

		if (empty($args['post']))
			$args['post'] = null;

		if (empty($args['data']['user_id']) && !empty($_SESSION['user_id']))
		{
			$args['data']['user_id'] = $_SESSION['user_id'];
			$args['data']['username'] = $_SESSION['user'];
		}
		else
		{
			$args['data']['user_id'] = 0;
			$args['data']['username'] = 'Yasumi';
		}

		if (false === ob_start())
			self::returnStaticStatus('Internal error: ob_start() failed', __FILE__, __LINE__, null, 500);

		$dev_only = false;
		if (class_exists('ZMC', false) && isset(ZMC::$registry)) 
		{
			$dev_only = ZMC::$registry->dev_only;
			unset($YasumiSaveZmcRegistry);
			$YasumiSaveZmcRegistry =& ZMC::$registry;
			$notset = null;
			ZMC::$registry =& $notset;
		}

		if (empty($args['requestUri']) && isset($args['pathInfo']) && !empty($args['data']))
			$args['requestUri'] = "/Yasumi/index.php$args[pathInfo]?" . ZMC::escapeGet($args['data']);
		
		if (!empty($GLOBALS['session_started']))
		{
			$sessionClosed = true;
			ZMC::session_write_close(__FILE__, __LINE__); 
		}
		$_SESSION = array('user' => $args['data']['username'], 'user_id' => $args['data']['user_id']);

		try
		{
			self::$dumpPrefix = '';
			self::$mkDumpDir = true;
			$yasumi = new ZMC_Yasumi();
			$args['_caller_source'] = 'OPERATION'; 
			$reply =& $yasumi->bootstrap($args, $dev_only);
		}
		catch(Exception $exception)
		{
			$exceptionMessage = $exception->getMessage();
			error_log($msg = "Yasumi operation $args[pathInfo] threw exception in " . $exception->getFile()
				. " line #" . $exception->getLine() . ": $exceptionMessage");
			$pm->addDetail($msg);
			$pm->addInternal($exceptionMessage);
		}

		if (isset($YasumiSaveZmcRegistry))
			ZMC::$registry =& $YasumiSaveZmcRegistry;

		if (!empty($sessionClosed))
			ZMC::session_start(__FILE__, __LINE__);

		if (!empty($exceptionMessage))
			throw $exception;

		$result = ob_get_clean();
		if (!empty($result))
			ZMC::debugLog("Yasumi stdout: $result");

		if (is_string($reply) && strlen($reply) && $reply[0] === '{')
			$reply = json_decode($reply, true);

		if (!is_array($reply) && !($reply instanceof ZMC_Registry_MessageBox))
			throw new ZMC_Exception_YasumiFatal($pm->addInternal('Internal Error: invalid reply type:'. gettype($reply) . " ($reply)"));

		if ($percent = ZMC_Timer::testMemoryUsage())
		{
			$reply->addWarning($msg = "Low Memory: $percent% of PHP memory used");
			ZMC::debugLog("Yasumi: $msg");
		}

		if ($merge)
			$pm->merge($reply);

		if (!empty($reply['fatal'])) 
		{
			if (!$merge) 
				$pm->merge($reply, null, false); 
			if ($reply['fatal'] instanceof Exception)
				throw $reply['fatal'];
			throw new ZMC_Exception_YasumiFatal('Yasumi fatal error.' . $reply['fatal']);
		}
		return $reply;
	}

	public static function returnStaticStatus($message, $file, $line, $tags, $status = 200)
	{
		$last = ZMC_Error::error_get_last();
		if (!empty($last['message']) && !strncmp($last['message'], 'Undefined variable:', 19))
			$message .= ' (' . $last['message'] . ')';
		ZMC::debugLog("Yasumi: $message ($status" . basename($file) . '#' . $line . ')');
		throw new ZMC_Exception($message, $status, $file, $line);
	}

	public function addLastModified(&$array)
	{
		$array['last_modified_time'] = ZMC::humanDate(true);
		$array['last_modified_by'] = $this->username;
	}

	protected function &readDeviceState($fn)
	{
		static $STATE = null;
		if ($STATE || !file_exists($fn))
			return $STATE;

		$STATE =& ZMC::perl2php($fn, 'STATE');
		ZMC::quit($STATE);
		if (isset($STATE['slots']))
			ksort($STATE['slots']);
		return $STATE;
	}

	protected function pcntl_exec($cmd, $args)
	{
		$this->debugLog('debugLog: ' . $this->requestId . ': child ' . $this->pid . ' born from parent ' . $this->parentPid . ' for request ' . $this->requestId . "\npcntl_exec: $cmd  " . $argString = implode(' ', $args));
		pcntl_exec($cmd, $args);
		$this->errorLog("FAILED: pcntl_exec: $cmd $argString");
	}

	protected function &updateStatsCache($filename = '', $op = 'update_stats_cache')
	{
		$this->lstats =& $this->command(array(
			'pathInfo' => "/stats/$op",
			'data' => array(
				'filename_of_proposed_disklist_edit' => $filename
			),
			'post' => null,
			'postData' => null,
		));
		$this->reply->merge($this->lstats, null, true); 
		
		

		return $this->lstats;
	}
	public function validateSlotRange($range, $pm = '', $max_slots=''){
		    if(!empty($range)){
				if(!preg_match('/^((\d+(-\d+)?,?)?){1,}$/', $range)){
					$this->reply->addError("Please specify slot range in correct format i.e 1-4,6-8,12,17,34-21,etc...");    
				}else{
					$range = rtrim($range, " , ");
					$err_msg = "Slot range should be within in 1-$max_slots slots.";
					if(preg_match('/,/', $range)){
						$spl_slot = explode(",", $range);
						foreach($spl_slot as $key => $value){
							if(preg_match('/-+/', $value)){
								$dash_slot = explode("-", $value);
								if($dash_slot[0] > $dash_slot[1]){
									$tmp = '';
									$tmp = $dash_slot[0];
									$dash_slot[0] = $dash_slot[1];
									$dash_slot[1] = $tmp;
									$spl_slot[$key] = implode('-', $dash_slot);
								}
								if($max_slots && ( $dash_slot[0] > $max_slots || $dash_slot[1] > $max_slots))
									$this->reply->addError($err_msg); 
							}
							else
								if($max_slots && $value > $max_slots)
									$this->reply->addError($err_msg);
		                }
						$range = implode(',', $spl_slot);
					}else{
						if(preg_match('/-+/', $range)){
							$dash_slot = explode("-", $range);
							if($dash_slot[0] > $dash_slot[1]){
								$tmp = '';
								$tmp = $dash_slot[0];
								$dash_slot[0] = $dash_slot[1];
								$dash_slot[1] = $tmp;
								$range = implode('-', $dash_slot);
							}
							if($max_slots && ( $dash_slot[0] > $max_slots || $dash_slot[1] > $max_slots))
								$this->reply->addError($err_msg);
						}
						else
							if($max_slots && $range > $max_slots)
								$this->reply->addError($err_msg);	
					}
			}
		}
		return $range;
	}

}





class ZMC_Exception_YasumiFatal extends ZMC_Exception
{
}
