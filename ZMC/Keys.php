<?













class ZMC_Keys
{
	public static function run()
	{
		if (isset($_GET['client']))
		{
			echo 'Please review ZMC documentation for information about how to obtain a copy of each encryption key used by each AEE client.';
			exit;
		}
		$parts = posix_getpwuid(posix_geteuid());
		$content = file_get_contents($fn = $parts['dir'] . DIRECTORY_SEPARATOR . '.am_passphrase');
		$stat = stat($fn);
		$names = posix_uname();
		
		$date = date("F j, Y, g:i:s a", $stat['mtime']);
		$wiki = ZMC::$registry->wiki;
		if (!ZMC_User::hasRole('Administrator'))
		{
			echo 'Only Administrators can download the encryption key.';
			exit;
		}
		echo <<<EOD
<html><head><title>ZMC Key</title></head>
<body>
<p>See: <a href="{$wiki}Backup_What#Compression_and_Encryption">ZMC Compression and Encryption</a></p>
<p>Save and print a hardcopy of this passphrase in a safe and secure location.</p>
<p>Algorithm: <a href="http://en.wikipedia.org/wiki/Advanced_Encryption_Standard">symmetric AES 256</a></p>
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
