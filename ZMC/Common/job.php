<?














require_once 'ZMC/Error.php';
ZMC_Error::installHandlers();
ZMC_Error::installObHack();

require_once 'ZMC/ZMC.php';
require 'ZMC/Common/SessionHandling.php';
$pm = ZMC::startup(); 
checkForGenuineSession("js");
ZMC_BackupSet::start($pm, true); 
$amandaConf = ZMC_BackupSet::getName();
ZMC::session_write_close(__FILE__, __LINE__);
if(empty($amandaConf))
	throw new ZMC_Exception("Missing backup set name in call to " . $_SERVER['SCRIPT_NAME']);
if (empty($_GET['type']))
	throw new ZMC_Exception('Missing job type');
$result =& ZMC_Yasumi::operation($pm, array('pathInfo' => "/job_$_GET[type]/" . (empty($_GET['abort']) ? 'get_state' : 'abort') . "/$amandaConf", 'data' => array('job_name' => empty($_GET['job_name']) ? 'default' : $_GET['job_name'])));
echo (empty($_GET['human']) ? json_encode($result, true) : '<pre>' . print_r($result, true));
