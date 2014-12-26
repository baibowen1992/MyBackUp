<?













$html = file_get_contents(dirname(__FILE__) . '/internal_error.html');
$parts = explode('<!-- insert internal_error.php -->', $html);
$registry = include('/etc/zmanda/zmc/zmc_aee/zmc.php');

$data = pack("H*" , $_GET['error']);
$error = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
if (empty($error))
	$error = $data;

$code = (empty($_GET['code']) ? $error:$_GET['code']);
$display = ($registry['debug'] ? $error : wordwrap($_GET['error'], 60, "\n", true));
if (!empty($code))
	$display = "code:\n<a href='http://network.wocloud.cn/zmc2lore.php?svnrev=" . $registry['zmc_svn_info'] . "&branch=" . $registry['zmc_build_version'] . "&code=$code'>$display</a>";

if (!empty($_GET['date']))
	$display .= "\ndate: " . $_GET['date'];

echo $parts[0];
echo <<<EOD
		<div style='clear:both;'></div>
		<div class='wocloudUserErrors' style='width:478px;' id='msgBoxWarns2'>
			<div class="wocloudMsgBox" onclick="gebi('msgBoxWarns2').style.display='none';" >X</div>
			<div class="wocloudMsgWarnErr"><img onclick="this.parentNode.style.display = 'none'" style="cursor:pointer" src="/images/global/calendar/icon_calendar_failure.gif" style="vertical-align:text-top; padding-right:3px" alt="ERROR" />
				$display
			</div>
		</div>
EOD;
echo $parts[1];
