<?













class ZMC_YasumiService
{
	protected $amandaConf;
	protected $host = '127.0.0.1';
	protected $port = 443;
	protected $protocol = 'https';
	protected $username = 'rest';
	protected $password;
	protected $basePath = '/Yasumi/index.php';
	protected $debugMode;
	protected $jsErrorTrigger = "Internal Server Error";
	protected $resumeSession = true;
	
	public function __construct($amandaConf = null)
	{
		$this->amandaConf = (empty($amandaConf) ? ZMC_BackupSet::getName() : $amandaConf);
		$this->password = ZMC::$registry->cnf->zmc_yasumi_passwd;
		$this->port = ZMC::$registry->apache_https_port;
	}

	public static function messageFromJSON($json, $id = null)
	{
		$array = json_decode($json, true);
		if (empty($array))
			throw new ZMC_Exception("Internal Error: JSON decode of the message failed: $json");

		if (!empty($id) && $id != $array['message_id'])
			throw new ZMC_Exception("./symfony_project/aee/apps/aee/modules/messaging/actions/actions.class.php uses 'setId()' .. why?");

		return new ZMC_Registry_Message($array);
	}

	public static function rest($command, $get = null, $resumeSession = null)
	{
		$instance = new self();
		return $instance->messageRead($command, $get, $resumeSession);
	}

	public static function restJson($command, $get = null, $resumeSession = null)
	{
		$instance = new self();
		return $instance->cachedRead($command, $get, $resumeSession);
	}

	protected function isRequestSuccess($result, $url)
	{
		$responseCode = $result->getResponseCode();
		
		$yasumi_status = $result->getHeader("Yasumi-Status");
		if (null === $yasumi_status) 
		{
			ZMC::errorLog("Yasumi-Status missing in response to $url; HTTP $responseCode; reply=" . print_r($result->getHeaders(), true) . '<<<' . print_r($result->getBody(), true) . '>>>');
			if (empty($_SESSION['symfony_rest']))
				ZMC::headerRedirect(ZMC::$registry->bomb_url, __FILE__, __LINE__);
			else
				echo $this->jsErrorTrigger;
			exit;
		}

		$success = false;
		if ($responseCode > 199 && $responseCode < 300)
			$success = true;
		elseif ($responseCode == 304)
			$success = true;
		return $success;
	}

	protected function doRequest($url, $get = null, $post = null, $method = null, $headers = null, $resumeSession = null)
	{
		if ($method === null)
			$method = HttpRequest::METH_GET;
		$url = $this->generateUrl($url, $get);
		
	    ZMC::debugLog(__CLASS__ . '::' . __FUNCTION__ . ' ' . substr($url, 55));
		$request = new HttpRequest($url, $method);
		$request->setOptions(array('timeout' => 90, 'useragent' => 'YasumiService'));
		if (!empty($post))
			$request->setRawPostData($post);

		try
		{
			if (!empty($GLOBALS['session_started']))
			{
				$sessionClosed = true;
				ZMC::session_write_close(__FILE__,__LINE__);
				$GLOBALS['session_started'] = false;
			}
			$result = $request->send();
			if (!empty($sessionClosed))
				$GLOBALS['session_started'] = ZMC::session_start(__FILE__, __LINE__);
		}
		catch(Exception $e)
		{
			ZMC::errorLog($e, $e->getCode());
			
			throw new ZMC_Exception_YasumiService('Could not connect to server using URL: ' . 
				preg_replace('/.*:.*@/', 'https://username:password@', $url));
		}

		if (!$this->isRequestSuccess($result, $url))
		{
			$errorString = $result->getBody();

			if (empty($errorString))
			{
				$errorString = $this->jsErrorTrigger;
				ZMC::errorLog('Yasumi reply missing. HTTP Headers = ' . serialize($result->getHeaders()));
			}

			throw new ZMC_Exception_YasumiService($errorString);
		}
		return $result;
	}

	public function doPost($url, $get = null, $post = null, $headers = null, $resumeSession = null)
	{
		$result = $this->doRequest($url, $get, $post, HttpRequest::METH_POST, $headers, $resumeSession);
		return $result->getBody();
	}

	protected function doHeaders($url)
	{
		return $this->doRequest($url, null, null, HttpRequest::METH_HEAD);
	}

	protected function messageRead($url, $get = null, $resumeSession = null)
	{
		$result = $this->cachedRead($url, $get, $resumeSession);
		return self::messageFromJSON($result);
	}

	




	protected function cachedRead($url, $get = null, $resumeSession)
	{
		$result = $this->doRequest($url, $get, null, null, null, $resumeSession); 
		return $result->getBody();
	}

	protected function parseHeaders($headers)
	{
		
		$array = explode("\n", $headers);
throw new ZMC_Exception(__FILE__ . __LINE__ .	array_shift($array));
		return ZMC_Yaml_sfYaml::load(implode("\n", $array));
	}

	protected function generateUrl($url, $query = null, $basePath = null)
	{
		if ($basePath === null)
			$basePath = $this->basePath;
		
		$query['debug'] = ZMC::$registry->debug_level;
		$query['dev_only'] = ZMC::$registry->dev_only;
		$query['timezone'] = ZMC::$registry->tz['timezone'];
		$query['timestamp'] = time();
		$query['user_name'] = $_SESSION['user'];
		$query['user_id'] = $_SESSION['user_id'];
		$queryString = '';
		if (!empty($query))
			$queryString = (strpos($url, '?') ? '&' : '?') . http_build_query($query);

		return "{$this->protocol}://{$this->username}:{$this->password}@{$this->host}:{$this->port}{$basePath}/$url$queryString";
	}

	protected function getCallbackUrl($destination)
	{
		return $this->generateUrl("restReceiver/$destination", null, '/RestReceiver/RestReceiverView.php');
	}
}

class ZMC_Exception_YasumiService extends ZMC_Exception
{}
