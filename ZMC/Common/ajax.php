<?













require_once 'ZMC/Error.php';
ZMC_Error::installHandlers();
ZMC_Error::installObHack();

require_once 'ZMC/ZMC.php';
require 'ZMC/Common/SessionHandling.php';
checkForGenuineSession("js");
ZMC::quit(__FILE__ . __LINE__);
$pm = ZMC::startup(); 
ZMC::quit(__FILE__ . __LINE__);
ZMC_BackupSet::start($pm, true); 
$amandaConf = ZMC_BackupSet::getName();
if(empty($amandaConf))
	throw new ZMC_Exception("Missing backup set name in call to " . $_SERVER['SCRIPT_NAME']);
