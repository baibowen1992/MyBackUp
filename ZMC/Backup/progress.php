<?













require 'ZMC/Common/ajax.php';

$status = 'Finished';
$output = $status;
$glob = glob("/etc/amanda/$amandaConf/*.lock_progress");
foreach($glob as $fn)
	if (file_exists($fn) && $progress = file_get_contents($fn))
	{
		list($date, $pid, $percent) = explode("\n", $progress);
		$output = $percent;
		$status = 'Running';
	}

echo json_encode(array(
	'status' => $status,
	'output' => $output,
)); 
ZMC_Error::disableObCheck(); 
ZMC_Timer::logElapsed();
