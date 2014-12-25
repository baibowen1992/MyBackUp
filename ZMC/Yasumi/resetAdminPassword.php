<?













if (!isset($_SERVER['REDIRECT_REMOTE_USER']) || ($_SERVER['REDIRECT_REMOTE_USER'] !== 'rest'))
	exit; 

require 'ZMC/Error.php';
ZMC_Error::installHandlers();
session_name('ZMCaee');
$GLOBALS['session_started'] = session_start();
$_SESSION['user_id'] = 1; 
require 'ZMC/ZMC.php';
ZMC::startup();
require 'ZMC/Common/SessionHandling.php';
ZMC_Mysql::update('users', array('password' => sha1('admin')), 'user_id=1', 'Failed to reset password for "admin"');

header('Content-type: text/plain');
echo 'Password reset.  Please immediately login as "admin" and change the password.';
