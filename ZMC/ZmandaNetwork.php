<?













class ZMC_ZmandaNetwork
{
	
	protected static $registry = array(
		
	   	
		'curl_connectivity' => '<a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> user name and/or password could not be validated (potentially a connectivity/server issue). ',

		'connectivity' => '<a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> user name and/or password could not be validated.',
		'help_connectivity' => 'ZMC was not able to connect to the Zmanda Network and won\'t be able to alert you for any updates, including security fixes. While this does not impact immediate functionality of @NAME@, we recommend that you check if there is a firewall or other network issue preventing this authentication from the Amanda server to https://network.zmanda.com.',

		'zn_internal_error' => '<a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> user name and/or password could not be validated (internal error).',

		'auth_invalid' => '<a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> user name and/or password is not valid. Zmanda Network won\'t be able to alert you for any updates, including security fixes. While this does not impact immediate functionality of @NAME@, we recommend that you register your Zmanda Network account with ZMC.',

		'auth_missing' => 'It appears that you have not entered either the user name or the password for your <a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> account. While this does not impact immediate functionality of @NAME@, we recommend that you register your Zmanda Network account with ZMC.',

		'need_different_text' => 'Please enter the correct user name and password.',

		'need_different_link' => '<a href="?zn">Please enter a different name and password</a>. ',

		'help_missing' =>
			'<a href="?zn">Please enter</a> your <a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> user name and password to connect to the Zmanda Network and receive update alerts, including security fixes.',

		'help_network' =>'<a href="?zn=1">Please try again later</a>. If this condition persists please check internet connectivity between your Amanda Server and <a target="ZmandaInfo" href="https://network.zmanda.com/">Zmanda Network</a> or contact Zmanda Customer Support at <a href="mailto:support@zmanda.com">support@zmanda.com</a>.',

		'help_later' =>'<a href="?zn=1">Please try again later<a/>. If this condition persists please contact Zmanda Customer Support at <a href="mailto:support@zmanda.com">support@zmanda.com</a>.',

		'' => ''
		);

	



 
	protected static function setResult(ZMC_Registry_MessageBox $pm, $action)
	{
		
		
		static $actions = array(
			'first_view_of_zn_login_box' => array(
				'escapedErrors' => '',
				'escapedWarnings' => 'help_missing',
				'zn_status' => false
			),
			'cancel_button' => array(
				'escapedErrors' => 'help_missing',
				'escapedWarnings' => '',
				'zn_status' => false
			),
			'missing_name_or_password' => array(
				'escapedErrors' => 'auth_missing',
				'escapedWarnings' => 'help_missing',
				'zn_status' => false
			),
			'http_code' => array(
				'escapedErrors' => 'zn_internal_error',
				'escapedWarnings' => 'help_later',
				'zn_status' => false
			),
			
			'curl_code' => array(
				'escapedErrors' => 'curl_connectivity',
				'escapedWarnings' => 'help_network',
				'zn_status' => false
			),
			
			'curl_connect' => array(
				'escapedErrors' => 'connectivity',
				'escapedWarnings' => 'help_connectivity',
				'zn_status' => false
			),
			
			'empty_response' => array(
				'escapedErrors' => 'zn_internal_error',
				'escapedWarnings' => 'help_later',
				'zn_status' => false
			),
			
			'decode_failure' => array(
				'escapedErrors' => 'zn_internal_error',
				'escapedWarnings' => 'help_later', 
				'zn_status' => false
			),
			'zn_internal_connection_error' => array( 
				'escapedErrors' => 'zn_internal_error',
				'escapedWarnings' => 'help_later',
				'zn_status' => false
			),
			'invalid_username_or_password_popup' => array( 
				'escapedWarnings' => 'auth_invalid',
				'escapedErrors' => 'need_different_text',
				'zn_status' => false
			),
			'invalid_username_or_password_page' => array( 
				'escapedWarnings' => 'auth_invalid',
				'escapedErrors' => 'need_different_link',
				'zn_status' => false
			)
		);

		$messages = $actions[$action];
		$messages['escapedErrors'] = str_replace('@NAME@', ZMC::$registry->name, self::$registry[$messages['escapedErrors']]);
		$messages['escapedWarnings'] = str_replace('@NAME@', ZMC::$registry->name, self::$registry[$messages['escapedWarnings']]);
		$pm->merge($messages);
		if (ZMC::$registry->debug) 
		{
			$msg = " [debug enabled: $action using server: " . ZMC::$registry->url_zn_auth . ']';
			$pm->addDetail($msg);
		}
	}

	





	public static function form(&$pagePm)
	{
		$_SESSION['zmanda_network_last'] = time();
		if (!empty($_POST['zncancel']))
			return self::setResult($pagePm, 'cancel_button');

		if (empty(ZMC::$registry->internet_connectivity))
			return ZMC::debugLog('ZN: skipping, internet_connectivity false');

		if (empty(ZMC::$registry->url_zn_auth))
			return ZMC::debugLog('ZN: skipping, url_zn_auth empty');

		$pm = new ZMC_Registry_MessageBox();
		if (!empty($_POST['formName']) && $_POST['formName'] == 'zmandaNetworkLogin')
		{
			if (empty($_POST['zmandaNetworkID']) || empty($_POST['zmandaNetworkPassword']))
				self::setResult($pm, 'missing_name_or_password');
			else
			{
				self::verifyAndSave($pagePm, $_POST['zmandaNetworkID'], $_POST['zmandaNetworkPassword'], $_SESSION['user_id']);
				$_SESSION['zmanda_network_status'] = $pagePm->zn_status;
				$_SESSION['zmanda_network_id'] = $_POST['zmandaNetworkID'];
				return;
			}
		}
		else
		{
			self::verifyEntitlement($pm); 
			$_SESSION['zmanda_network_status'] = $pm->zn_status;
		}

		if (!isset($_GET['zn']) && ($_SESSION['zmanda_network_status']))
			return;

		
		
		ob_start();
		self::quiet($pm);
		$pm->url = $pagePm->url;
		ZMC_Loader::renderTemplate('ZmandaNetworkLogin', $pm);
		$pagePm->zmandaNetworkLogin = ob_get_clean();
	}

	










	public static function verifyAndSave(ZMC_Registry_MessageBox $pm, $networkId, $networkPassword, $userId = 1)
	{
		$crypt = crypt($networkPassword, substr($networkId, 0, 2));
		ZMC_Mysql::query($sql = "UPDATE users SET network_ID='" . ZMC_Mysql::escape($networkId)
			. "', network_sessionID='" . ZMC_Mysql::escape($crypt)
			. "' WHERE user_id =   $userId", $msg = 'Unable to save Zmanda Network information.');
		ZMC::debugLog("verifyAndSave($networkId, " . (ZMC::$registry->dev_only ? $networkPassword : '***') . ", $userId)");

		if (!empty(ZMC::$registry->url_zn_auth))
		{
			self::login($pm, $networkId, $crypt, $userId, 'page');

			if ($pm->zn_status)
			{
				$pmUserId1 = new ZMC_Registry_MessageBox();
				
			   	if ($userId != 1 && ZMC_User::hasRole('Administrator')) 
					self::verifyEntitlement($pmUserId1, 1, 'page');
				if (empty($pmUserId1->zn_status)) 
				{
					ZMC_Mysql::query(substr($sql, 0, -4) . '1', $msg);
					ZMC::debugLog("Used 'admin' instead of $userId for verifyAndSave($networkId, '***', 1)");
				}
			}
		}

		ZMC::debugLog("ZN: verifyAndSave - zn_status=$pm->zn_status; networkId = $networkId; userId=$userId");
		return $pm;
	}

	














	protected static function verifyEntitlement(ZMC_Registry_MessageBox $pm, $userId = 1, $where = 'entitlement')
	{
		$network = self::get($userId);
		self::login($pm, $network['network_ID'], $network['network_sessionID'], $userId, $where);
		ZMC::debugLog(__FUNCTION__ . " of ZMC user id: $userId ZN id:$network[network_ID]; zn_status=$pm->zn_status");
	}
	
	





	protected static function get($userId = 1)
	{
		return ZMC_Mysql::getOneRow("SELECT network_ID, network_sessionID FROM users WHERE user_id = $userId", 'Unable to retrieve Zmanda Network settings from DB.');
	}

	public static function getNetworkId()
	{
		if (isset($_SESSION['zmanda_network_id']))
			return $_SESSION['zmanda_network_id'];
		return 0;
	}

	










	public static function getAndSave(ZMC_Registry_MessageBox $pm, $name, $dest, $err, $userId = 1, $where = 'popup')
	{
		$network = self::get();
		self::login($pm, $network['network_ID'], $network['network_sessionID'], $userId, $where, $name);

		if ($pm->zn_status) 
		{
			


			if (is_array($pm->zn_result['data']))
				$pm->data = join("\n", $pm->zn_result['data']);
			else
				$pm->data =& $pm->zn_result['data'];

			$length = file_put_contents($dest, $pm->data, LOCK_EX);
			if ($length !== strlen($pm->data))
				$pm->addError($err);
		}
	}

	









	protected static function login(ZMC_Registry_MessageBox $pm, $networkId, $cryptedPassword, $userId = null, $where = 'popup', $get = '')
	{
		$pm->zn_status = false;
		if (empty($userId))
			$userId = $_SESSION['user_id'];

		if (empty($networkId) || empty($cryptedPassword))
		{
			ZMC::debugLog(__CLASS__ . '::' . __FUNCTION__ . "(networkid=$networkId, userId=$userId) missing: " . (empty($networkId) ? 'ZN network id ' : '') . (empty($cryptedPassword) ? ' password ' : ''));
			self::setResult($pm, ($where === 'entitlement' ? 'first_view_of_zn_login_box' : 'missing_name_or_password'));
		}

		if (!empty($get))
			$get = "get=$get&";

		$post = $get . 'format=json'
			. '&application=' . urlencode(ZMC::$registry->short_name)
			. '&strUserName=' . urlencode($networkId)
			. '&strPassword=' . urlencode($cryptedPassword)
			. '&ip=' . $_SERVER['REMOTE_ADDR'];
		$ch = curl_init();
		$zn = ZMC::$registry->url_zn_auth;
		if (!is_readable($cainfo = ZMC::$registry->curlopt_cainfo))
			throw new ZMC_Exception("Unable to read '$cainfo'. Please check permissions/existence of this file and parent directories.");
		curl_setopt_array($ch, array(
			CURLOPT_URL		=> $zn,
			CURLOPT_POST		=> 1,
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_CONNECTTIMEOUT	=> 8,
			CURLOPT_TIMEOUT		=> 9,
			CURLOPT_RETURNTRANSFER	=> 1,
			CURLOPT_SSL_VERIFYPEER	=> (ZMC::$registry->debug ? 0:1),
			CURLOPT_CAINFO		=> ZMC::$registry->curlopt_cainfo,
			CURLOPT_CAPATH		=> ZMC::$registry->curlopt_capath,
		));
		
		$rawResult = curl_exec($ch);
		
		
		

		$curlErr = curl_errno($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code != 200)
		{
			$pm->addDetail(" HTTP response $code; ");
			$action = 'http_code';
		}
		if ($curlErr)
		{
			$action = 'curl_code';
			if ($curlErr > 4 && $curlErr < 8)
			{
				$url = parse_url($zn);
				$pm->addDetail(" Unable to connect to $url[host]; ");
				$action = 'curl_connect';
			}
			$pm->addDetail($curlErr . ':' . curl_error($ch) . '; ');
		}
		curl_close($ch);
		ZMC::auditLog( __FUNCTION__ . "($networkId, " . ((ZMC::$registry->debug) ? $cryptedPassword : '***')
			. ", $userId) - $zn?" . preg_replace('/strPassword=[^\&]*/', 'strPassword=***', $post) . "; error:$pm->details; raw result:$rawResult",
			$curlErr, array('facility' => __CLASS__, 'tags' => array('ZN' => true, 'external' => true))
		);

		if (empty($pm->details))
		{
			if (empty($rawResult))
			{
				$pm->addDetail(' Empty response received from ZN servers.');
				$action = 'empty_response';
			}
			else
			{
				$pm->result = json_decode($rawResult, true);
				if (empty($pm->result) || !isset($pm->result['jsonResponse']) || !isset($pm->result['jsonResponse'][0]) || !isset($pm->result['jsonResponse'][0]['codes']))
				{
					$pm->addDetail(" Unable to decode ZN authentication results.");
					if (ZMC::$registry->debug)
						$pm->addDetail(" Shown because 'debug' mode enabled: raw result=$rawResult; decoded result=".serialize($pm->result));
					$action = 'decode_failure';
				}
				else
				{
					


























					$pm->zn_result = $pm->result['jsonResponse'][0]; 
					$codes = array_flip($pm->zn_result['codes']);
					if (isset($codes[5]))
						self::setResult($pm, 'zn_internal_connection_error');
					elseif (isset($codes[1]) || isset($codes[3]) || isset($codes[6]))
					{
						self::setResult($pm, ($where === 'page' ? 'invalid_username_or_password_page' : 'invalid_username_or_password_popup'));
						if (isset($codes[6]) && stripos('vader', $zn))
							$pm->addDetail(' (shown only to Zmanda devs: Are you sure you have a ZN account on Vader.zmanda.com?)');
					}
					elseif (in_array(0, $codes))
					{
						$pm->zn_status = true;
						$pm->addEscapedMessage('Zmanda Network: now authenticated as <b>' . ZMC::escape(ZMC_User::get('user', $userId)) . '</b>');
					}
				}
			}
		}

		if (!empty($action))
			self::setResult($pm, $action);
		ZMC::debugLog(__FUNCTION__ . "pm=" . print_r($pm, true));
	}

	


	protected static function quiet(ZMC_Registry_MessageBox $pm)
	{
		if (!empty(ZMC::$registry->zn_quiet)
			&& (!empty($pm['escapedErrors']) || !empty($pm['errors']) || !empty($pm['messages']) || !empty($pm['escapedMessages'])))
		{
			$msg = '';
			foreach (array('escapedErrors', 'errors', 'messages', 'escapedMessages') as $key)
			{
				if (isset($pm[$key]))
				{
					$msg .= print_r($pm[$key], true) . '; ';
					unset($pm[$key]);
				}
			}
			ZMC::debugLog('config "zn_quiet" override, so ignoring: ' . $msg);
		}
	}
}
