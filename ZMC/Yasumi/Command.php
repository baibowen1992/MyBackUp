<?




















if (empty($_SERVER['PATH_INFO'])) 
{
	echo 'No REST request received.';
	exit;
	$_SERVER['PATH_INFO'] = '/disklist/migrate263to264';
	require_once 'ZMC/Error.php';
	$_SERVER['QUERY_STRING'] = 'amanda_configuration_name=localtest&debug=' . ZMC_Error::DEBUG; 
}


$_SERVER['REQUEST_URI'] = '/Yasumi/index.php' . $_SERVER['PATH_INFO'] . '?' . $_SERVER['QUERY_STRING'];
parse_str($_SERVER['QUERY_STRING'], $_GET);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REDIRECT_REMOTE_USER'] = 'rest';
$_SERVER['SERVER_PORT'] = '443';

if (class_exists('ZMC', false) && isset(ZMC::$registry)) 
{
	$YasumiSaveZmcRegistry =& ZMC::$registry;
	$nothing = null;
	ZMC::$registry =& $nothing;
}
require 'ZMC/Yasumi/index.php'; 
if (!empty($YasumiSaveZmcRegistry))
{
	ZMC::$registry =& $YasumiSaveZmcRegistry;
	unset($YasumiSaveZmcRegistry);
}
