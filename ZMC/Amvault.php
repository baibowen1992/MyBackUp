<?













require_once 'ZMC/Error.php';
ZMC_Error::installHandlers();
ZMC_Error::installObHack();
require_once 'ZMC/ZMC.php';
$pm = ZMC::startup();
$_SESSION['user'] = 'zmc';
$cron_user = ZMC_Mysql::getOneValue('SELECT user_id from users where user="'.$_SESSION['user'].'"');
$_SESSION['user_id'] = ($cron_user)? $cron_user: "2";
$human = (ZMC::$registry->dev_only && !empty($_GET['config']));
if (!$human)
	ini_set('error_log', ZMC::$registry->cnf->zmc_log_dir . 'zmc_cron_amvault.log');
try {
	$opts = $argv;
	array_shift($opts);
	$pm->selected_name = array_shift($opts);
	$timestamp = array_shift($opts);
	
	if (!empty($pm->selected_name))
	{
		if ($human) echo __FILE__, ': ', $pm->selected_name;
		ZMC::session_write_close(__FILE__,__LINE__);
		$result = ZMC_Vault_Jobs::startVaultJobNow($pm, array($timestamp => '1'), implode(' ', $opts));
		ZMC::auditLog(__FILE__ . ": finished with result=$result; " . $pm->toCommentBox());
	}
}
catch (Exception $e){
	$pm->addError("$e");
}

$result .= $pm->toCommentBox();
if ($human) {
	ZMC_Loader::renderTemplate('MessageBox', $pm);
} else {
	ZMC_Events::add("CRON", 4, $result, $pm->selected_name);
}

ZMC::auditLog(__FILE__ . ": FAILED $result");
