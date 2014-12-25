<?




















	$name = 'gtest'; 




chdir('/opt/zmanda/amanda/apache2/htdocs/zmanda-aee');
require '/opt/zmanda/amanda/apache2/htdocs/zmanda-aee/ZMC/Error.php';
ZMC_Error::installHandlers();
ZMC_Error::installObHack();
require '/opt/zmanda/amanda/apache2/htdocs/zmanda-aee/ZMC/ZMC.php';
require '/opt/zmanda/amanda/apache2/htdocs/zmanda-aee/ZMC/Loader.php';
ZMC_Loader::register();
ZMC_ConfigHelper::getRegistry('aee');
$_SESSION['user'] = 'zmcscript';
$_SESSION['user_id'] = 9999;
try
{
	
	$result = ZMC_Yasumi::operation($pm, array('pathInfo' => '/conf/read/$name?what=disklist.conf')); 
	$conf = $result->conf;
	echo "List of DLEs (<id> => <disk_name>):";
	foreach($conf['dle_list'] as $id => &$dle)
	{
		echo "$id => $dle[disk_name]\n";
		
		$dle['compress'] = 'compress server fast'; 
	}
	$service = new ZMC_YasumiService($name);
	$result2 = $service->doPost("/conf/write/$name",
		array(
			'what' => 'disklist.conf',
			'message_type' => 'edit',
			'commit_comment' => 'set compression for all dles'
		),
		json_encode(array('conf' => $conf))
	);
	print_r($result2);
}
catch(ZMC_Exception_YasumiService $e)
{
	echo "$e";
}
