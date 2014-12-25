<?













class ZMC_Admin_Login
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$exception = null;
		try
		{
			$request = new HttpRequest($url = 'https://127.0.0.1:' . ZMC::$registry->apache_https_port . '/server-status', HttpRequest::METH_GET);
			$request->setOptions(array('timeout' => 90, 'useragent' => 'YasumiService'));
			$result = $request->send();
			ZMC::debugLog("$url: " . $body = strip_tags($result->getBody()));
			if (($code = $result->getResponseCode()) != 200)
				$pm->addEscapedError("Installation Problem.\n" . ZMC::$registry->support_email_html . "\n$body");
		}
		catch(Exception $e)
		{
			ZMC::debugLog($url . ": $e"); 
		}
		unset($_SESSION['check_server']);
		$_SESSION['check_server'] = '';
		$pm->url = ZMC_HeaderFooter::$instance->getUrl('Login');
		$pm->tombstone = 'Admin';
		$pm->subnav = 'Login';
		if (ZMC::$registry->debug)
			$pm->addWarning("ZMC Debug Mode is <b>ON</b>. If you are not experiencing any issues with ZMC, please disable the debug mode on the " . ZMC::getPageUrl($pm, 'Admin', 'preferences'));
		if ($code == 200)
		{
			$result = '';
			if (empty($_POST))
				session_regenerate_id(true); 
			try
			{
				if (empty($GET['cookies_checked']))
					$result = self::runState($pm);
			}
			catch(Exception $e)
			{	$pm->addError("$e"); }
		}
		ZMC_Yasumi_Check::dumpTypesInstalled($pm);

		if (($code != 200) || empty($result) || is_string($result) || is_bool($result))
			$result = array('template', 'Login');

		$result[1] = trim($result[1], '/');
		if ($result[0] === 'redirect')
			redirect($result[1], __FILE__, __LINE__);
		elseif ($result[0] !== 'require')
		{
			$pm->title = 'Welcome to the Zmanda Management Console';
			ZMC_Loader::renderTemplate('Header', $pm);
			return (empty($result) ? 'Login' : $result[1]);
		}
		else
		{
			$pm->post_login = true;
			if ($result[1] === 'Login')
				$pm->skip_backupset_start = false;

			return call_user_func(array($result[1], 'run'), $pm);
		}
	}

	public static function runState(ZMC_Registry_MessageBox $pm)
	{
		$starttime = strtotime(date('Y-m-d h:i:s'));
		if (!file_exists($fn = '/etc/zmanda/zmanda_license'))
			return $pm->addError("Zmanda License not found. Please install the license file to the location: $fn.  Download your license from http://network.zmanda.com/");

		if (!is_readable($fn))
			return $pm->addError("Please enable read access to the license file: chmod a+r $fn");

		if (isset($_GET['action']) && ($_GET['action'] == 'lostPassword'))
			return ($pm['lostPassword'] = true);
	
		if (!empty($_POST) && (empty($_POST['username']) || ('' === ($username = trim($_POST['username'])))))
			return $pm->addError('Please enter your user name.');

		if (isset($_POST['RetrievePassword']))
		{
			if ($user = ZMC_User::getByName($username))
			{
				ZMC_User::set($user['user_id'], 'password', sha1($password = uniqid()));
				if (false === mail($user['email'], 'Your ZMC Password Reset Request', "The ZMC password for $username has been reset.\nThe new password is: $password\nLogin to ZMC at " . ZMC::$registry->fqdn . " with this password.\n"))
					$pm->addError("An error occurred when trying to send your new password to $user[email]. Please check your mail subsystem and retry.");
				else
					$pm->addWarning("The password for $username has been reset.\n");
				return;
			}
			else
				$pm->addError("User '$username' not found.");
	
			return ($pm['lostPassword'] = true);
		}
	
		self::checkDbVersion($pm);
		ZMC::checkDiskSpace($pm);
		self::checkSelinux($pm);
		if (ZMC_Startup::initZmcOverrides($pm) || self::checkAmandaRevision($pm) || self::upgradeZmc($pm))
			return; 

		$pm->last_page = (isset($_REQUEST['last_page']) ? ZMC::escape($_REQUEST['last_page']) : '');
		if (isset($_GET['logout']))
			$_SESSION['logout'] = true;
	
		if (empty($_COOKIE["zmc_cookies_enabled"]))
			return ($pm['noCookies'] = true);

		if (empty($_POST))
			return;

		if (empty($_POST['password']) || ('' === ($password = trim($_POST['password']))))
			return $pm->addError('Please enter your password.');
	
		
		$userId = ZMC_User::authenticateUser($username, $password, ZMC::$registry->short_name_lc);
		if (!(intval($userId) > 0))
		{
			$pm->addError("The user name '$username' and/or password is not correct.");
			if (strtoupper($_POST['password']) === $_POST['password'])
				$pm->addWarning('Is the CAPS Lock key on?');
			return;
		}
		elseif (ZMC::$registry->debug) 
			ZMC_Mysql::logVars($pm); 
	
		if (!ZMC::isServerOk($pm))
			return;

		
		
		if (!isset($_REQUEST['resume']) || !isset($_SESSION['user_id']) || $userId != $_SESSION['user_id'])
		{
			$save = array();
			$saveKeys = array('disk_space_check_errors');
			ZMC::array_move($_SESSION, $save, $saveKeys);
			$_SESSION = array();
			ZMC::array_move($save, $_SESSION, $saveKeys);
		}
	
		$_SESSION['lastTimeStamp'] = time();
		unset($_SESSION['logout']);
		
		$_SESSION['user_id'] = $userId;
		$_SESSION['user'] = $username;
		unset($_SESSION['check_server']);
		$_SESSION['check_server'] = '';

		
		if(isset($_REQUEST['check_server'])){

			$_SESSION['check_server'] = "show_check_installation_page";
			$cacheFn = '127.0.0.1-check_server_installation';
			if (ZMC::useCache(null, null, $cacheFn, false, 300) && !ZMC::$registry->dev_only && !ZMC::$registry->qa_mode)
				$reply = unserialize(file_get_contents($cacheFn));
			else
			{
				$file = new ZMC_Sed($pm, $cacheFn);
				$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/check/server_installation/', 'data' => array('check_server' => 'show_check_installation_page'))); 
				
				$file->close(serialize($reply));
			}
			if(ZMC::$registry->debug){
				$endtime = strtotime(date('Y-m-d h:i:s'));
				$totaltime =  ($endtime - $starttime);
				$pm->addMessage("Total Time to Verify Server Installation: ". $totaltime. ' seconds');
			}
		}
		if (is_array($reply->overrides))
			ZMC::$registry->setOverrides($reply->overrides);

		$pm->merge($reply, null, true);
		
		if(isset($_REQUEST['sync_backupset'])){
			if(ZMC::$registry->debug)
				$starttime_sync_backpsets = strtotime(date('Y-m-d h:i:s'));
			$notok = ZMC_BackupSet::syncAmandaConfig($pm);
			if(ZMC::$registry->debug){
				$endtime = strtotime(date('Y-m-d h:i:s'));
				$totaltime =  ($endtime - $starttime_sync_backpsets);
				$pm->addMessage("Total Time to Sync Backup Sets: ". $totaltime. ' seconds');
			}
			if ($pm->isErrors() || $notok){
				
			}
		}
		if(ZMC::$registry->debug){
			$endtime = strtotime(date('Y-m-d h:i:s'));
			$totaltime =  ($endtime - $starttime);
			$pm->addMessage("Total Time to Login : ". $totaltime. ' seconds');
		}
		if(isset($_SESSION['check_server']) && $_SESSION['check_server'] != null){

			return array('require', '/ZMC_Installcheck');
		}

		$starterPage = ZMC_HeaderFooter::$instance->getUrl('Starter');
		if (ZMC_User::get('show_starter_page', $userId) && ($_SERVER['REQUEST_URI'] !== $starterPage))
			
			return array('require', ZMC::$registry->start_page);

		if (!empty($_REQUEST['last_page']))
		{
			ZMC::debugLog("Redirecting to $_REQUEST[last_page] " . __FILE__ . __LINE__);
			return array('require', $_REQUEST['last_page']);
			
		}
	
		if (!empty($_SESSION['last_page']))
		{
			ZMC::debugLog("Redirecting to $_SESSION[last_page] " . __FILE__ . __LINE__);
			return array('require', $_SESSION['last_page']);
			
		}
	
		ZMC::debugLog('Redirecting to start_page ' . __FILE__ . __LINE__);
		return array('require', ZMC::$registry->start_page);
	}

	protected static function checkDbVersion(ZMC_Registry_MessageBox $pm)
	{
		$gotVersions = ZMC_Mysql::getAllOneValueMap('SELECT product, zmc_db_version FROM zmc_metadata', 'Unable to retriever user name');
		if (ZMC::$registry->zmc_db_version != $gotVersions['ZMC'])
			return $pm->addEscapedError("Incompatible ZMC MySQL DB version. Found v$gotVersions[ZMC], but need v"
				. ZMC::$registry->zmc_db_version . ". " . ZMC::$registry->support_email_html);
	}

	protected static function checkAmandaRevision(ZMC_Registry_MessageBox $pm)
	{
		
	}

	
	public static function upgradeZmc()
	{
		if (empty(ZMC::$registry['upgrade_zmc_from_revision']) || (ZMC::$registry->svn->zmc_svn_revision >= ZMC::$registry['upgrade_zmc_from_revision']))
			return;
		
		$pm->addWarning("Upgraded from version ", ZMC::$registry->upgrade_zmc_from_revision, ' to version ', ZMC::$registry->svn->zmc_svn_revision, ZMC_Registry_MessageBox::STICKY_RESTART);

		

		ZMC::$registry->setOverrides(array(
			'upgrade_zmc_from_revision' => null, 
		));
	}

	
	
	public static function checkSelinux(ZMC_Registry_MessageBox $pm)
	{
		if (!file_exists($getenforce = '/usr/sbin/getenforce'))
			return;

		try
		{
			ZMC_ProcOpen::procOpen('selinux check', $getenforce, array(), $stdout, $stderr);
			if (false === stripos($stdout, 'Disabled'))
				$pm->addEscapedWarning("<a target='_blank' href='http://network.zmanda.com/lore/search.php?keyword=selinux&article=Article+%23&version=Version+%23&submit=Go'>SELinux</a>: " . ZMC::escape("$getenforce reported: $stdout\n$stderr"));
			ZMC::debugLog(__FUNCTION__ . "(): SeLinux $stdout.$stderr");
		}
		catch(Exception $e)
		{
			ZMC::errorLog("$e");
		}
	}
}
