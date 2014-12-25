<?













function checkForGenuineSession($js = null, $resetLastTimeStamp = true)
{
	$lastPage = $error = $timeout = null;
	if (!empty($GLOBALS['session_started']) && empty($_SESSION['user_id']))
	{
		$error = 'We do not know who you are.  Please login.';
		ZMC::debugLog(__FUNCTION__ . " - user_id was not set, so user session was destroyed" . print_r(debug_backtrace(), true));
		session_destroy();
		$GLOBALS['session_started'] = false;
	}

	if (isset($_SESSION['logout']))
		$error = "Logout detected";
	elseif (isset($_SESSION['lastTimeStamp'])) 
	{
		if ($js === null || class_exists('ZMC_Mysql', false))
		{
			$systemTimeout = ZMC::$registry->session_timeout;
			$userTimeout = ZMC_User::get('session_timeout');
		}
		else
			$systemTimeout = $userTimeout = 15 * 60; 

		if (ZMC::$registry->qa_mode) $systemTimeout = $userTimeout = 9999;
		if (empty($userTimeout) || $userTimeout > $systemTimeout)
			$userTimeout = $systemTimeout; 
		$userTimeout = 60 * $userTimeout; 
		if (time() > $_SESSION['lastTimeStamp'] + $userTimeout)
		{
			$error = "Session timeout for $_SESSION[user] ($_SESSION[user_id]) at $userTimeout seconds. Last page seen: $_SESSION[last_page]";
			$timeout = '&timeout=1';
		}
	}

	if ($js !== null && $error) 
	{
		ZMC::debugLog(__FUNCTION__ . " ignoring ajax request because: $error ($_SERVER[REQUEST_URI])", null, null, ZMC_Error::NOTICE);
		ZMC_Error::returnStatus($error, 401);
	}

	if ($js === null
		&& strncmp($_SERVER['SCRIPT_NAME'], '/ZMC_Admin_About', 16)
		&& strncmp($_SERVER['SCRIPT_NAME'], '/index.php', 10)
		&& strncmp($_SERVER['SCRIPT_NAME'], '/ZMC_Admin_Login', 15))
			$lastPage = $_SERVER['SCRIPT_NAME']; 

	if ($error)
	{
		ZMC::debugLog(__FUNCTION__ . " - $error");
		
		$userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
		
		
		
		if (empty($lastPage) && !empty($GLOBALS['session_started']) && !empty($_SESSION['last_page']))
			$lastPage = $_SESSION['last_page'];

		if (!empty($_REQUEST['last_page']))
			$lastPage = $_REQUEST['last_page'];

		redirect(ZMC_HeaderFooter::$instance->getUrl('Login') . '?'
			. (empty($lastPage) ? '' : 'last_page=' . ZMC::escape($lastPage) . '&')
			. "$timeout"
			. (isset($_SESSION['application']) ? '&login=' . $_SESSION['application'] : '')
			, __FILE__, __LINE__);
	}

	if ($resetLastTimeStamp)
		$_SESSION['lastTimeStamp'] = time();

	if ($js === null)
	{
		if (!empty($lastPage))
			$_SESSION['last_page'] = $lastPage;
		if(isset($_SESSION['CURRENT_PAGE']))
			$_SESSION['HTTP_REFERER'] = $_SESSION['CURRENT_PAGE'];

		$_SESSION['CURRENT_PAGE'] = getURL($_SERVER['SCRIPT_NAME']);
	}
}







function getURL($url)
{ return '/' . ltrim($url, '/.'); } 






function redirect($url, $file, $line)
{			
	$url = getURL($url);
	error_log("Redirecting to $url from $file line#$line (zmc_gui_debug.log has details)");
	ZMC::headerRedirect($url, __FILE__, __LINE__); 
	ZMC_Error::disableObCheck(); 

	while (ob_get_level()) 
	{
		$out = ob_get_contents();
		
		if (!ob_end_clean())
			break;
	}
	exit(); 
}
