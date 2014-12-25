<?














class ZMC_RestoreJob_Amgtar extends ZMC_Registry
{







public static function unescape($string)
{
	$double = false;
	if (false !== strpos($string, '\\\\'))
	{
		$string = str_replace('\\\\', "\0", $string);
		$double = true;
	}

	if (false !== strpos($string, '\\'))
	{
		$pieces = explode('\\', $string);
		$string = $pieces[0];
		array_shift($pieces);
		foreach($pieces as &$piece)
			if ((3 <= ($len = strlen($piece))) &&
				(		($piece[0] >= '0' && $piece[0] <= '9')
					&& ($piece[1] >= '0' && $piece[1] <= '9')
					&& ($piece[2] >= '0' && $piece[2] <= '9')))
					$string .= chr(octdec($len === 3 ? $piece : substr($piece, 0, 3))) . substr($piece, 3);
			else
				$string .= '\\' . $piece;
	}

	if ($double)
		$string = str_replace("\0", '\\', $string);

	return $string;
}

public static function escape($string)
{
	$len = strlen($string);
	$byte127 = chr(127);
	$result = '';
	for($i=0; $i < $len; $i++)
		if ($string[$i] >= $byte127)
			$result .= '\\' . decoct(ord($string[$i]));
		else
			$result .= $string[$i];

	return $result;
}



}
