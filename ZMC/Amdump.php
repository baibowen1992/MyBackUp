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
	ini_set('error_log', ZMC::$registry->cnf->zmc_log_dir . 'zmc_cron_amdump.log');
try {
	ZMC_BackupSet::start($pm, true);
	$opts = ($human ? array('$0', $_GET['config']) : $argv);
	array_shift($opts);
	$set = ZMC_BackupSet::getByName($pm->selected_name = array_shift($opts));
	if (!empty($pm->selected_name))
	{
		if ($human) echo __FILE__, ': ', $pm->selected_name;
		if(in_array("--zmcdev", $opts)){
			while(count($opts) && (array_pop($opts) !== '--zmcdev'));
		}
		ZMC::session_write_close(__FILE__,__LINE__);
		switch($result = ZMC_BackupSet::startBackupNow($pm, $pm->selected_name, array(), implode(' ', $opts), true))
		{
			case ZMC_BackupSet::STARTED:
			case ZMC_BackupSet::FINISHED:
				ZMC::auditLog(__FILE__ . ": finished with result=$result; " . $pm->toCommentBox());
				exit;
		}
	}
} catch (Exception $e)
{ $pm->addError("$e"); }

$result .= $pm->toCommentBox();
if ($human){
	ZMC_Loader::renderTemplate('MessageBox', $pm);
}else{
	ZMC_Mysql::insert('backuprun_dle_state', array(
				'configuration_id' => (empty($pm->selected_name) ? 0:ZMC_BackupSet::getId($pm->selected_name)),
				'hostname' => '*',
				'directory' => '*',
				'disk_name' => '*',
				'backuprun_date_time' => ZMC::humanDate(),
				'state' => 'Failed',
				'failed' => $result,
		));
	ZMC_Events::add("CRON", 4, $result, $pm->selected_name);
}
	
ZMC::auditLog(__FILE__ . ": FAILED $result");
