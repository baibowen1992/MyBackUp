<?













class ZMC_Templates_MessageBoxHelper
{

public static $boxNumber = 0;

public static function display($icon, $type, &$messages, &$escapedMessages, $float)
{
	if (empty($messages) && empty($escapedMessages))
		return true;

	$escapedMessages = array_unique($escapedMessages);
	$messages = array_unique($messages);

	if ($icon)
		$icon = "<img onclick=\"this.parentNode.style.display='none'\" style='cursor:pointer' src='/images/global/calendar/$icon.gif' style='vertical-align:text-top; padding-right:3px' alt='$type' />&nbsp;";
	$txt = '';
	if (!empty($escapedMessages))
	{
		if (!is_array($escapedMessages))
			$txt .= self::format($icon, $type, $escapedMessages, false);
		else
		{
			$msgs = array_filter($escapedMessages);
			foreach($msgs as $msg)
				if (!empty($msg))
					$txt .= self::format($icon, $type, $msg, false);
		}
	}

	if (!empty($messages))
	{
		if (!is_array($messages))
			$txt .= self::format($icon, $type, $messages, true);
		else
		{
			$msgs = array_filter($messages);
			foreach($msgs as $msg)
				if (!empty($msg))
				{
					if (($type === 'Details') && ($txt !== ''))
						$txt .= "\n<hr>";
					$txt .= self::format($icon, $type, $msg, true);
				}
		}
	}

	if (0 === strlen(trim($txt)))
		return true;

	$txt = trim(str_replace(array("\n\n", "\n", "\r"), array("<br />", "<br />", '<br />'), $txt));
	$Type = ucfirst($type);
	if ($type === 'Details')
		echo <<<EOD
			<div class="instructions" id="showDetails" style='float:right'>(<a href="javascript:
				gebi('msgBox$Type').style.display='block';
				gebi('showDetails').style.display='none';
				void('');"
			>Advanced Details</a>)</div>
			<div class='zmcMessages zmcUser$Type' id='msgBox$Type' style='float:right;'>
				<div class="zmcMsgBox" onclick="this.parentNode.style.display='none'" >X</div>
				$txt
			</div>
EOD;
	else 
		echo <<<EOD
			<div class='zmcMessages zmcUser$Type' id='msgBox$Type' style='float:$float'>
				<div class="zmcMsgBox" onclick="this.parentNode.style.display='none'" >X</div>
				$txt
			</div>
EOD;
	return false;
}

protected static function format($icon, $type, $msg, $escape = false)
{
	$onclick = "<div class='zmcMsgWarnErr'>";
	if ($type !== 'Details')
	{
		if (strpos($msg, "\0"))
			$msg = str_replace("\0", ' <span style="font-weight:normal; color:black;">', $msg) . '</span>';
		return $onclick . $icon . trim($msg) . '</div>';
	}

	if ($msg instanceof Exception || $msg[0] !== '@')
		return $msg;

	list($ignore, $pre, $msg, $post) = explode('@', trim($msg));
	return  $onclick . '<pre>' . $pre . ($escape ? ZMC::escape($msg) : $msg) . $post . '</pre>';
}
}
