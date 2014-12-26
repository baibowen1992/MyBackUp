<?













class ZMC_Keys
{
	public static function run()
	{
		if (isset($_GET['client']))
		{
			echo '请回顾文档了解如何获得一份用于每个客户端的加密密钥';
			exit;
		}
		$parts = posix_getpwuid(posix_geteuid());
		$content = file_get_contents($fn = $parts['dir'] . DIRECTORY_SEPARATOR . '.am_passphrase');
		$stat = stat($fn);
		$names = posix_uname();
		
		$date = date("F j, Y, g:i:s a", $stat['mtime']);
		$wiki = ZMC::$registry->wiki;
//		if (!ZMC_User::hasRole('Administrator'))
//		{
//			echo '只有 Administrators 能够下载encryption key.';
//			exit;
//		}
		echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 8]> <html xml:lang="en" class="ie ie7" lang="UTF-8"> <![endif]-->
<!--[if IE 8]>    <html xml:lang="en" class="ie ie8" lang="UTF-8"> <![endif]-->
<!--[if IE 9]> <html xml:lang="en" class="modern_browser ie9" lang="UTF-8"> <![endif]-->
<!--[if gt IE 9]><!--> <html xml:lang="en" class="modern_browser" lang="UTF-8"> <!--<![endif]-->
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<link rel="shortcut icon" href="/images/icons/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="/scripts/wocloud.css" type="text/css" />
<title>Cloudbackup Key</title></head>
<body>
<p>See: <a href="{$wiki}Backup_What#Compression_and_Encryption">Cloudbackup Compression and Encryption</a></p>
<p>保存和打印这个passphrase 的硬拷贝在一个安全的地方.</p>
<p>加密程序: <a href="http://en.wikipedia.org/wiki/Advanced_Encryption_Standard">symmetric AES 256</a></p>
<h3>==&gt;$names[nodename]:$fn&lt;===</h3>
<pre>
$date
$content
</pre>
</body>
</html>
EOD;
		return 'MessageBox';
	}
}
