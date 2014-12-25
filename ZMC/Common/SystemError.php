<?


















require 'ZMC/Common/HeaderAndFooter.php';
ZMC_Loader::renderTemplate('Header', new ZMC_Registry_MessageBox(array('tombstone' => 'SystemError', 'title' => 'System Error')));
ZMC_Loader::renderTemplate('SystemError', new ZMC_Registry_MessageBox(array(
	'body' => true,
	'page' => true,
	'code' => ((isset($_GET['code'])) ? $_GET['code'] : ''),
	'raw_message' => $_GET['e']
)));
echo '</body></html>';

