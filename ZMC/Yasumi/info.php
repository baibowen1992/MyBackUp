<?
















ignore_user_abort();
$port = $_SERVER['SERVER_PORT'];
$protocol = ($_SERVER['HTTPS'] === 'on' ? 'https':'http');
$server = $_SERVER['HTTP_HOST'];
$URL = "$protocol://$server:$port";
$time = time(); 

if (!empty($_GET['yasumi_read']))
{
	header("Location: $URL/Yasumi/index.php/disklist/read/$time?amanda_configuration_name="
		. $_GET['yasumi_read'] . '&callback_username=rest&callback_password=changeme&message_type=789&human=1&readTest=1');
	
	exit;
}

if (!empty($_GET['yasumi_readwrite']))
{
	header("Location: $URL/Yasumi/index.php/disklist/readwrite/$time?amanda_configuration_name="
		. $_GET['yasumi_readwrite'] . '&callback_username=rest&callback_password=changeme&message_type=789&human=1&readTest=1');
	
	exit;
}

ob_start();
phpinfo(E_ALL);
$info = ob_get_clean();
list($head,$body) = explode('<body>', $info);
echo $head, "\n<body>\n<h3>", $date = date("F j, Y, g:i:s a"), "</h3>\n";

session_name('ZMCaee');
$GLOBALS['session_started'] = session_start();

opRmDebug();
opSearch();

echo <<<EOD
	<h3><a href="/server-status">Server Status</a></h3>
	<h3><a href="/server-info">Server Info</a></h3>
	<form><input type=text name=yasumi_read /> <input type=submit name=submitButton> Directly invoke Yasumi to read Amanda Config </form>
	<form><input type=text name=yasumi_readwrite /> <input type=submit name=submitButton> Directly invoke Yasumi to read & write Amanda Config </form>
	<form><input type=text name=form_text_field /> <input type=submit name=submitButton> Submit a text form field to this script
	<form><input type=hidden name=hidden_text_field value="foo+bar" />
</form>
EOD;
echo "dirname(dirname(__FILE__)) = ", dirname(dirname(__FILE__)), "<br>\n";
echo "timezone_name_from_abbr(get_cfg_var('date.timezone')) = ", timezone_name_from_abbr(get_cfg_var("date.timezone")), "<br>\n";
echo "date_default_timezone_get() = ", date_default_timezone_get(), "<br>\n";

if (count($_REQUEST) === 0)
{
	$post = file_get_contents('php://input');
	if ($post[0] === '{')
		echo '<h3>JSON POST DATA</h3><pre>', print_r(json_decode($post, true), true), '</pre>';
}

foreach(array('_GET', '_POST', '_COOKIE', '_SESSION', '_SERVER', '_REQUEST') as $varname)
{
	ksort($$varname);
	echo "<h3>${varname}</h3><pre>", htmlspecialchars(print_r($$varname, true), ENT_QUOTES, 'UTF-8');
}
echo '<h3>ls -al ~</h3>';
passthru('ls -al ~');
echo '</pre>';

echo "$body";


function opRmDebug()
{
	if (!empty($_GET['rmdebug']))
	{
		ob_start();
		passthru("cd ..; rm -r debug/logs");
		$out = ob_get_clean();
		if (empty($out))
		{
			header('Location: /debug/logs/');
			exit;
		}
		echo $out;
	}
	else
		echo "<h3><a href='?rmdebug=1'>Remove Yasumi Debug Logs</a></h3>\n";
}

function opSearch()
{
	if (empty($_GET['search_zmc']))
		$_GET['search_zmc'] = '';

	echo <<<EOD
	<form>
	<input type=text name=search_zmc value="$_GET[search_zmc]">
	<input type=submit value="Search ZMC Source Code">
	</form>
EOD;

	if (!empty($_GET['search_zmc']))
	{
		ob_start();
		passthru("cd ..; find . -path '*/.??*' -prune -o -path '*/.svn' -prune -o -path '*/yui*' -prune -o -path ./symfony -prune , -type f -a  \( -name '*.php' -o -name '*.css' -o -name '*.js' \) -print0 | xargs -0 grep -iEn --color=always '$_GET[search_zmc]' | head -n 1000");
		echo "===\n";
		passthru("cd ../../symfony_project; find . -path '*/.svn' -prune , -type f -a  \( -name '*.php' -o -name '*.css' -o -name '*.js' \) -print0 | xargs -0 grep -iEn --color=always '$_GET[search_zmc]' | head -n 1000");
		$data = htmlspecialchars(ob_get_clean(), ENT_QUOTES, 'UTF-8');
		$data = str_replace("\x1b\x5b\x30\x31\x3b\x33\x31\x6d", '<span style="background-color:yellow">', $data);
		$data = str_replace("\x1b\x5b\x30\x30\x6d\x1b\x5b\x4b", '</span>', $data);
		$data = str_replace("\x1b\x5b\x30\x30\x6d\x1b\x5b", '</span>', $data);
		$data = str_replace("\x1b\x5b\x30\x30\x6d", '</span>', $data);
		$lines = explode("\n", $data);
		foreach($lines as &$line)
		{
			if (!empty($line))
			{
				if ($line === "===")
				{
					$line = '<h2>./symfony_project:</h2>';
					continue;
				}
				list($file, $lineno, $rest) = explode(':', $line, 3);
				$file = substr($file, 2);
				$file = "<a href='http://zmcxen.zmanda.com/trac/trac/browser/zmanda-ui/trunk/ZMC/$file#L$lineno'>$file</a>:$lineno";
				$line = $file . $rest;
			}
		}
		$data = join("\n", $lines);
		echo "\n<hr>\n<pre>$data\n</pre><hr>\n";
	}
}
