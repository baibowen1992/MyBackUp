<?















$ssl = file_get_contents('/opt/zmanda/amanda/apache2/conf/ssl.conf');

preg_match("/.*\n\s*Listen[\s]+([^:]*:)?([0-9]+)/i", $ssl, $listen); 

$port = intval($listen[2]);
$host = rtrim($listen[1], ':');

if ($port === 443)
	$port = ''; 
else
	$port = ':' . $port;



$host = explode(':', $_SERVER['HTTP_HOST']); 
error_log(__FILE__ . __LINE__ . ($redir='Location: https://' . $host[0] . $port . $_SERVER['REQUEST_URI']));
header($redir);
