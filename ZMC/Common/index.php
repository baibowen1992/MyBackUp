<?













unset($_REQUEST['zPref']);

require_once 'ZMC/Error.php';
ZMC_Error::installHandlers();
ZMC_Error::installObHack();



require_once 'ZMC/ZMC.php';
if (!empty($_SERVER['PATH_INFO']) && !strncmp($_SERVER['PATH_INFO'], '/ZMC_', 5))
{
	list($ignored, $class) = explode('/', $_SERVER['PATH_INFO']);
	ZMC::waitpids(); 
	$pm = ZMC::startup(); 
	require 'SessionHandling.php';
	
	new ZMC_HeaderFooter_Aee(); 
	if (class_exists($class))
		ZMC_HeaderFooter::$instance->runFrontController($class, $pm);
	redirect('/', __FILE__, __LINE__);
	exit(0);
}
require 'ZMC/Loader.php';
ZMC_Loader::register();
ZMC_Splash::splashRedirect();
exit(0); 
