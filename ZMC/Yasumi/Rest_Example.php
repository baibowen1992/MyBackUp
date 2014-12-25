<?

























$result = ob_start();
if (isset($_SERVER['REQUEST_URI']))
	$tmpUri = $_SERVER['REQUEST_URI'];
$_SERVER['REQUEST_URI'] = '';
$_SERVER['FCGI_ROLE'] = '';
$_SERVER['PATH_INFO'] = '/conf/read/gtest';
$_SERVER['QUERY_STRING'] = 'what=disklist.conf';
require 'Command.php';
if (!empty($tmpUri))
	$_SERVER['REQUEST_URI'] = $tmpUri;

$json = ob_get_clean(); 
$result = json_decode($json, true);
echo "JSON result:\n", $json, "\n";
echo "Human readable pretty print of the JSON result:\n";
print_r($result);
