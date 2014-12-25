<?













error_log(time() . '|' . posix_getpid() . '|' .  basename(__FILE__) . '|' . str_replace(array("        )\n", "    )\n", ")\n", "(\n"), array('', '', '', '', "\n"), trim(print_r($_REQUEST,true), " ,()\n"))); 
require_once 'ZMC/Error.php';


ZMC_Error::$httpCode = 500; 
ZMC_Error::installHandlers();
require_once 'ZMC/ZMC.php';
require_once 'ZMC/Loader.php';
ZMC_Loader::register();
require_once '../Yasumi.php';

if (!empty($_SERVER['REQUEST_URI'])) 
{
	$cacheFilename = '';
	if (!empty($_SERVER['FCGI_ROLE'])) 
	{
		ZMC_Yasumi::header('Content-type: text/plain');
		
		
		
	}
	mb_internal_encoding("UTF-8");
	if (!mb_detect_order("ASCII,JIS,UTF-8,EUC-JP,SJIS"))
		throw new ZMC_Exception('PHP mb_detect_order() failure');
	$yasumi = new ZMC_Yasumi();
	$yasumi->bootstrap(array(
		'_caller_source' => 'REST',
	   	'data' => $_GET,
		'pathInfo' => $_SERVER['PATH_INFO'],
	   	'post' => $_POST,
	   	'postData' => file_get_contents('php://input'),
		'requestUri' => $_SERVER['REQUEST_URI'],
	));
}

ZMC_Timer::logElapsed();
