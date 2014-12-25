<?

































class ZMC_Yasumi_Parser 
{
	
	const GLOBAL_DEVICE_PROPS = true;

	
	public static $comments = true;

	



	public static function parse($filename, &$result, ZMC_Yasumi $yasumi)
	{
		if (false === ($lines = file($filename)))
			throw new ZMC_Exception_YasumiFatal("Unable to read file: '$filename'");
		self::parseLines($filename, $lines, $result, $yasumi);
		
	}

	private static function parseLines($filename, array $lines, &$result, ZMC_Yasumi $yasumi)
	{
		$block =& $result;
		$comment = '';
		$state = 'outside';
		$disklist = !strncasecmp(basename($filename), 'disklist', 8); 
		$config = basename(dirname($filename));

		foreach($lines as $key => $line)
		{
			$lineno = $key + 1;
			if (!empty($appLines))
			{
				if (false === strpos($line, '}'))
				{
					$appLines[$lineno -1] = $line;
					continue;
				}
				$pieces = explode('}', $line);
				$appLines[$lineno -1] = $pieces[0];
				$line = $pieces[1];

				self::parseLines($filename . '/application', $appLines, $block, $yasumi);
				unset($appLines);

			}

			$trimmed = trim($line);
			if ($state === 'need_open_curly')
			{
				if (false !== strpos($trimmed, '{'))
				{
					$trimmed = str_replace('{', '', $trimmed);
					$state = 'inside';
				}
			}

			if (empty($trimmed))
			{
				if (!empty($comment) && isset($dumptypeDefsComment))
				{
					self::append($dumptypeDefsPlace, $dumptypeDefsComment, $comment, "\n");
					$comment = '';
				}
				continue;
			}

			if ($trimmed[0] === '#')
			{
				$comment = (empty($comment) ? $trimmed : "$comment\n$trimmed");
				continue;
			}
			$line = $trimmed;
			if (($state === 'inside') && strncmp($line, 'property', 8) && (false !== ($pos = strpos($line, '}')))) 
			{
				unset($dumptypeDefsComment);
				$state = 'outside';
				if ($disklist)
				{
					$spindle = strtok(substr($line, $pos + 1), " \t"); 
					if ($spindle !== false)
					{
						$block['spindle'] = $spindle; 
						$interface = strtok(" \t");
						if ($interface !== false)
							$block['interface'] = $interface;
					}
					$key = "$config|$block[host_name]|$block[disk_name]";
					if (isset($dleKeys[$key]))
						throw new ZMC_Exception_YasumiFatal("Found duplicate key '$key' in another DLE. See $filename");
					$dleKeys[$key] = true;
					$result[$type][$key] =& $block;
					$dumptypeDefsPlace =& $block;
					if (!array_key_exists('_tmp', $result[$type])) 
					{
					
						error_log(__FUNCTION__ . ':' . __LINE__ . " _tmp key missing!\n");
					}
					unset($result[$type]['_tmp']);
				}

				$type = '';
				unset($block); 
				$block =& $result;
				continue;
			}

			if (!strncasecmp($line, 'holdingdisk', 11))
			{
				$hdValue = trim(substr($line, 12));
				if (empty($hdValue) || $hdValue[0] === '{') 
				{
					$appLines = array(($lineno -1) => "define $line");
					continue;
				}
			}

			if (!strncasecmp($line, 'application', 11) && (false === strpos($line, '"', 12))) 
			{
				$appLines = array(($lineno -1) => "define $line");
				continue;
			}

			if ($disklist && $state !== 'inside')
				$line = 'define dle ' . $line; 

			if (!strncasecmp($line, 'define ', 7) || !strncasecmp($line, "define\t", 7))
			{
				strtok($line, " \t");
				if ($state === 'inside')
					throw new ZMC_Exception_YasumiFatal("Line #$lineno ($line) improper nested 'define' in: $filename");
				$state = strpos($line, '{') ? 'inside' : 'need_open_curly';
				$type = str_replace('-', '_', strtok(" \t{") . '_list'); 
				$name = strtok(" \t{");
				if ($disklist)
				{
					$dumptypeDefsPlace =& $result[$type]['_tmp']; 
					$dumptypeDefsPlace['_line'] = $lineno;
					$dumptypeDefsPlace['host_name'] = $name;
					$parts = self::parseQuotedStrings(strtok('{'), $comment, false);
					$dumptypeDefsPlace['disk_name'] = $parts[0];
					$dumptypeDefsPlace['disk_device'] = (empty($parts[1]) ? $dumptypeDefsPlace['disk_name'] : $parts[1]);
				}
				elseif ($name === false) 
				{
					$dumptypeDefsPlace =& $result[$type]; 
					$dumptypeDefsPlace = array('_line' => $lineno);
				}
				else
				{
					$dumptypeDefsPlace =& $result[$type][$name]; 
					$dumptypeDefsPlace = array('name' => $name, '_line' => $lineno);
				}
				$block =& $dumptypeDefsPlace;
				if (!empty($comment))
					self::append($dumptypeDefsPlace, '_comment', $comment, "\n");
				$comment = '';

				continue;
			}

			$prop = false;
			if (!strncasecmp($line, 'device_property', 15))
			{
				$line = ltrim(substr($line, 16));
				$prop = 'device_property_list';
			}
			elseif (!strncasecmp($line, 'property', 8))
			{
				$line = ltrim(substr($line, 9));
				$prop = 'property_list';
			}
			elseif (!strncasecmp($line, 'script', 6))
			{
				$line = ltrim(substr($line, 7));
				$prop = 'script_list';
			}

			$space = strpos($line, ' ');
			$tab = strpos($line, "\t");
			if (($line === 'exclude') || ($line === 'include') || ($line === 'compress') || ($line === 'estimate') || ($line === 'encrypt')) 
				continue; 
			if (($space === false) && ($tab === false))
			{
				$unquoted = self::unquote($line);
				self::warnIfNotDefined($yasumi, $type, $unquoted, $result, $filename, $lineno, $line);
				$param = ($prop === 'script_list') ? 'append' : 'inherits';
				$value = $unquoted;
			}
			else
			{
				$param = substr($line, 0, $pos = min($space ? $space:$tab, $tab ? $tab:$space));
				$value = ltrim(substr($line, $pos));

				if ($cpos = strrpos($value, '#'))
				{
					if ($value[0] === '"')
					{
						if(!strrpos(substr($value, $cpos), '"')){ 
							$qpos = strrpos(substr($value, 0, $cpos), '"');
							if ($qpos === 0)
								throw new ZMC_Exception_YasumiFatal("Line #$lineno missing trailing '\"' in: $filename");
							if ($cpos > $qpos)
							{
								if (!empty($comment))
									$comment .= "\n";
								$comment .= substr($value, $cpos);
								$value = substr($value, 0, $cpos); 
							}
						}
					} else {
						if (!empty($comment))
							$comment .= "\n";
						$comment .= substr($value, $cpos);
						$value = rtrim(substr($value, 0, $cpos -1));
					}
				}

				if ($value === '')
					throw new ZMC_Exception_YasumiFatal("Line #$lineno missing value for $param in $filename.");

				if ($value[0] === '#')
				{
					$block['inherits'][$param . '_comment'] = $value;
					$value = $param;
					$param = 'inherits';
					self::warnIfNotDefined($yasumi, $type, $line, $result, $filename, $lineno, $line);
				}
			}

			unset($dumptypeDefsPlace);
			$append = false;
			if ($prop)
			{
				if ($param === 'append')
				{
					$param = strtok($value, " \t");
					$value = strtok('');
					$append = true;
				}
				$param = self::unquote($param);
				$dumptypeDefsPlace =& $block[$prop];
			}
			else
				$dumptypeDefsPlace =& $block;

			if (!strcasecmp($param, 'includefile'))
				$dumptypeDefsPlace['includefiles'][self::unquote($value)] = $lineno; 
			elseif ($param === 'inherits') 
				$dumptypeDefsPlace['inherits'][self::unquote(strtolower($value))] = $lineno; 
			else
			{
				if ($prop || !strcasecmp($param, 'exclude') || !strcasecmp($param, 'include'))
				{
					$word = strtok($value, " \t");
					if ($word === 'list') 
						$param = $param . '_list_a_file';
					else
					{
						if ($word === 'file') 
							$value = strtok(''); 
						$word = strtok($value, " \t");
						if ($word === 'append')
						{
							$value = strtok('');
							$append = true;
						}
					}
				}
				if ($prop !== 'property_list'
					&& $prop !== 'script_list'
					&& $param !== 'exclude'
					&& $param !== 'include'
					&& $param !== 'exclude_list_a_file'
					&& $param !== 'include_list_a_file')
					$dumptypeDefsPlace[$param] = self::unquote($value);
				else
				{
					if ($prop === 'property_list')
						$value = implode(' ', self::parseQuotedStrings($value, $comment, false));
					else
						$value = implode(' ', self::parseQuotedStrings($value, $comment));

					if (!isset($dumptypeDefsPlace[$param]))
						$dumptypeDefsPlace[$param] = $value;
					else
					{
						if ($prop === 'property_list' && $dumptypeDefsPlace[$param][0] !== '"')
							$dumptypeDefsPlace[$param] = self::quote($dumptypeDefsPlace[$param]);
						$dumptypeDefsPlace[$param] .= ' ' . $value;
					}
				}

				$dumptypeDefsPlace[$param . '_line'] = $lineno;
			}

			$dumptypeDefsComment = $param . '_comment';
			if (!empty($comment))
				self::append($dumptypeDefsPlace, $dumptypeDefsComment, $comment, "\n");

			$comment = '';
		}
	}

	protected static function warnIfNotDefined(&$yasumi, &$type, &$unquoted, &$result, $filename, $lineno, $line)
	{
		switch($type)
		{
			case 'dumptype_list':
			case 'dle_list':
				if ((substr($unquoted, 0, 4) !== 'zmc_') && (!isset($result['dumptype_list'][$unquoted])))
					$yasumi->errorLog("WARNING: dumptype used, but not defined in $filename on #$lineno: $line", __FILE__, __LINE__);
				break;

			case 'application_list':
			case 'application_tool_list':
				if ((substr($unquoted, 0, 4) !== 'zmc_') && (!isset($result['application_tool_list'][$unquoted])))
					$yasumi->errorLog("WARNING: application used, but not defined in $filename on line #$lineno: $line", __FILE__, __LINE__);
				break;

			case 'script_list':
			case 'script_tool_list':
				if ((substr($unquoted, 0, 4) !== 'zmc_') && (!isset($result['script_tool_list'][$unquoted])))
					$yasumi->errorLog("WARNING: script used, but not defined in $filename on line #$lineno: $line", __FILE__, __LINE__);
				break;

			case 'holdingdisk_list':
				if ($unquoted === 'never')
					break;

			default:
				$yasumi->debugLog('Parse result up to point of problem: ' . print_r($result, true), __FILE__, __LINE__);
				throw new ZMC_Exception_YasumiFatal("Trying to parse item of type '$type' (see Yasumi debug log). In $filename , line #$lineno not understood: $line");
				break;
		}
	}

	






	public static function parseQuotedStrings($line, &$comment, $quote = true, $limit = 999)
	{
		$length = strlen($line);
		$parts = array();
		$quoting = $slash = $wantSpace = false;
		for($i=0; ($i<$length) && (count($parts) <= $limit); $i++)
		{
			if (!$quoting)
			{
				if (ctype_space($line[$i]))
					continue;

				if ($line[$i] === '#')
				{
					if (!empty($comment))
						$comment .= "\n";
					$comment .= substr($line, $i);
					break;
				}

				unset($part);
				$part = ($quote ? '"' : '');
				$parts[] =& $part;
				$quoting = true;
				if ($line[$i] === '"')
					continue;
				$wantSpace = true; 
			}

			if ($wantSpace && ctype_space($line[$i]))
			{
				$quoting = $wantSpace = false;
				if ($quote)
					$part .= '"';
				continue;
			}

			if (!$slash && $line[$i] === '"')
			{
				if ($quote)
					$part .= '"';
				$quoting = $wantSpace = false;
				continue;
			}
			elseif ($slash)
				$slash = false;
			elseif ($line[$i] === '\\')
				$slash = true;

			$part .= $line[$i];
		}
		if (!$quote)
			foreach($parts as &$part)
				$part = self::unquote($part);
		elseif ($quoting)
			$part .= '"';
		return $parts;
	}

	



	public static function &data2conf($data, $indent = 0)
	{
		$dumptypeDefs = array();
		if (empty($data))
		{
			$result = '';
			return $result;
		}
		$lineAppend = 9999;
		$listAppend = 5000;
		$result = array();
		$globalDeviceProps = '';
		foreach($data as $key => $value)
		{
			if ($value === null) 
				continue;
			if (self::ignoreKey($key))
				continue;
			$lineno = self::lineno($data, $key, $lineAppend);

			
			if ($key === 'includefiles')
				foreach($data[$key] as $filename => $includeLineNo)
				{
					self::append($result, $includeLineNo, 'includefile ' . self::quote($filename) . "\n");
					if ($filename === ZMC::$registry->etc_zmanda . '/zmc_aee/zmc_user_dumptypes')
						$dumptypeFirst = true;
				}
			elseif ($key === 'device_property_list' || $key === 'property_list')
				$globalDeviceProps .= self::data2proplist($result, $lineAppend, $key, $value, $indent);
			elseif (substr($key, -5) !== '_list')
				self::append($result, $lineno, self::data2param($data, $key, $indent));
			elseif ($key === 'dumptype_list' && isset($value['zmc_default_dev'])) 
				self::data2list($dumptypeDefs, $listAppend, "define " . substr($key, 0, -5), $value, 1);
			else 
				self::data2list($result, $listAppend, "define " . substr($key, 0, -5), $value, 1); 
		}
		ksort($result);
		$result = implode('', $result) . $globalDeviceProps;
		if (empty($dumptypeFirst))
			$result .= implode('', $dumptypeDefs);
		else
			$result = implode('', $dumptypeDefs) . $result;
		return $result;
	}

	




	public static function quote($s, $raw = false)
	{
		
		if ($s === false)
			return '"off"';
		if ($s === true)
			return '"on"';

		$raw = ($raw ? '' : '"');
		if ($s === null || $s === false)
		{
			$msg = 'Parse problem with quoting: ' . print_r($s);
			if (ZMC::$registry->debug)
				$msg .= "\n" . ZMC_Error::backtrace(-10, $s);
			throw new ZMC_Exception_YasumiFatal($msg);
		}
		if ($s === 0)
			return '"0"';
		return empty($s) ? $raw.$raw : $raw . str_replace(array('\\', "\n", "\r", "\t", "\f", '"'), array('\\\\', '\\n', '\\r', '\\t', '\\f', '\\"'), $s) . $raw;
	}

	



	public static function unquote($s)
	{
		if (!is_string($s))
			ZMC::quit($s);
		return str_replace(array('\\"', '\\\\', '\\n', '\\r', '\\t', '\\f', chr(0)), array('"', chr(0), "\n", "\r", "\t", "\f", '\\'), trim(trim($s), '"'));
	}


	
	protected static function lineno($data, $name, &$lineAppend)
	{
		if (isset($data[$name . '_line']))
			$lineno = $data[$name . '_line'];
		else
			$lineno = $lineAppend++;
		return $lineno;
	}
	
	
	protected static function ignoreKey($key)
	{
		if ((substr($key, -5) === '_line') || (substr($key, -8) === '_comment') || (substr($key, -8) === '_display'))
			return true;

		switch($key)
		{
			case 'name':
			case 'devicetype':
			case 'tape_splitsize_auto':
			case 'tape_splitsize_percent':
			case 'part_size_auto':
			case 'part_size_percent':
				return true; 
			default:
				return false;
		}
	}

	public static function quoteKey($key, $value)
	{
		switch($key)
		{
			case 'amrecover_changer':
			case 'amrecover_changer':
			case 'application':
			case 'auth':
			case 'changerdev':
			case 'changerfile':
			case 'client_custom_compress':
			case 'client_decrypt_option':
			case 'client_encrypt':
			case 'client_username':
			case 'columnspec':
			case 'comment':
			case 'directory':
			case 'diskfile':
			case 'displayunit':
			case 'dumporder':
			case 'dumpuser':
			
			case 'indexdir':
			case 'infofile':
			case 'krb5keytab':
			case 'krb5principal':
			case 'label_new_tapes':
			case 'labelstr':
			case 'lbl_templ':
			case 'logdir':
			case 'mailer':
			case 'mailto':
			case 'meta_autolabel':
			case 'org':
			case 'part_cache_dir':
			case 'plugin':
			case 'printer':
			case 'program':
			case 'rawtapedev':
			case 'script':
			case 'server_custom_compress':
			case 'server_decrypt_option':
			case 'server_encrypt':
			case 'server_encrypt':
			case 'split_diskbuffer':
			case 'ssh_keys':
			case 'tapedev':
			case 'tapelist':
			case 'tapetype':
			case 'tpchanger':
			case 'taperscan':
				return self::quote($value);
				break;
		}
		return $value;
	}

	
	protected static function data2param($data, $key, $indent)
	{
		$value = self::quoteKey($key, $data[$key]);
		$commentKey = $key . '_comment';
		$comment = (isset($data[$commentKey]) ? $data[$commentKey] . "\n" : '');
		$padding = ($indent > 0) ? str_repeat("\t", $indent) : '';

		if (!empty($comment))
		{
			$tmp = ltrim($comment);
			if ($tmp[0] !== '#')
				$comment = '#' . $comment;
			$comment = "$padding$comment";
		}

		if ($key === 'exclude_list_a_file' || $key === 'include_list_a_file')
		{
			$key = substr($key, 0, 7);
			if ('list' === trim(strtok($value, " \t"), '"'))
			{
				if ('optional' === trim($tok = strtok(" \t"), '"'))
					$value = 'list optional ' . strtok('');
				else
					$value = 'list ' . $tok . strtok('');
			}
		}

		return (self::$comments ? $comment : '' ) . "$padding$key\t$value\n";
	}

	
	protected static function data2list(&$result, &$lineAppend, $begin, $data, $indent)
	{
		$lineAppend = 5000;
		foreach($data as $name => $definition)
		{
			$prepend = '';
			if (self::ignoreKey($name))
				continue;

			if ($definition === '' || $definition === null)
				continue; 

			if (!is_array($definition))
			{
				$msg = "Parse problem with '$begin $name' definition '$definition' (should be a list)";
				if (ZMC::$registry->debug)
					$msg .= "\n" . ZMC_Error::backtrace(-10);
				throw new ZMC_Exception_YasumiFatal($msg);
			}

			$where = $lineAppend++;
			if (isset($definition['_line']))
				$where = $definition['_line'];

			$appendAfterCurly = '';
			if ($begin === 'define dle') 
			{
				$disk_device = (isset($definition['disk_device']) ? ' ' . self::quote($definition['disk_device']) : '');
				$prepend = $definition['host_name'] . ' ' . self::quote($definition['disk_name']) . $disk_device . " {\n";
				unset($definition['}']);
				$appendAfterCurly .= ' ' . @$definition['spindle'] . ' ' . @$definition['interface'] . "\n";
				unset($definition['host_name']);
                unset($definition['disk_name']);
                unset($definition['disk_device']);
				unset($definition['interface']);
				unset($definition['spindle']);
			}
			elseif ($begin === 'define dumptype')
				$prepend = "$begin $name {\n"; 
			elseif ($begin === 'define script_tool')
				$prepend = "$begin \"".str_replace("'","",str_replace('"',"",$name))."\" {\n"; 
			elseif ($begin === 'define changer')
				$prepend = "$begin \"".str_replace("'","",str_replace('"',"",$name))."\" {\n"; 
			else
				$prepend = "$begin \"$name\" {\n"; 

			$tabIndent = str_repeat("\t", $indent -1);
			if (!empty($definition['_comment']))
				$prepend = $tabIndent . str_replace("\n", "\n" . $tabIndent,  $definition['_comment']) . "\n$prepend";
			if (!empty($data[$name . '_comment']))
				$prepend = $tabIndent . str_replace("\n", "\n" . $tabIndent, $data[$name . '_comment']) . "\n$prepend";
			self::append($result, $where, $prepend . self::list2conf($lineAppend, $definition, $indent, $appendAfterCurly));
		}
	}
			
	protected static function list2conf(&$lineAppend, &$definition, $indent, $appendAfterCurly)
	{
		$body = array();
		$appendBeforeCurly = $dumptypes = $deviceProps = '';
		$tabsClose = $indent ? str_repeat("\t", $indent -1) : '';
		$tabs = $tabsClose . "\t";
		foreach($definition as $key => $value)
		{
			if ($value === null) 
				continue;
			if (self::ignoreKey($key))
				continue;

			$lineno = self::lineno($definition, $key, $lineAppend);

			switch($key)
			{
				case 'inherits': 
					$comment = '';
					if (!empty($definition[$key . '_comment']))
						$comment = $tabs . $definition[$key . '_comment'];

					foreach($value as $inherit => $i)
					{
						if (self::ignoreKey($inherit))
							continue;

						if (!empty($value[$inherit . '_comment']))
							$comment .= $tabs . $value[$inherit . '_comment'];
						if (!empty($comment))
							$comment = str_replace("\n", "\n$tabs", $comment) . "\n";

						if (!ctype_alnum(str_replace('_', '', $inherit))) 
							$quoted = self::quote($inherit);
						else
							$quoted = $inherit;

						if (ctype_digit("$i"))
						{
							self::append($body, $i, (self::$comments ? $comment : '' ) . "$tabs$inherit\n");
							$comment = '';
							continue;
						}

						$parts = explode('_', $inherit);
						if ($parts[0] === 'zmc' && ($parts[2] === 'base' || $parts[2] === 'auth'))
							$dumptypes .= (self::$comments ? $comment : '' ) . "$tabs$inherit\n";
						else 
							$appendBeforeCurly = (self::$comments ? $comment : '' ) . "$tabs$inherit\n$appendBeforeCurly";
						$comment = '';
					}
					break;

				case 'application_tool_list':
				case 'application_list':
					$begin = (($key === 'application_list') ? 'application' : 'define application_tool');
					$appendBeforeCurly = "$appendBeforeCurly$tabs$begin {\n" . self::list2conf($lineAppend, $value, $indent +1, '');
					
					break;

				case 'property_list':
				case 'device_property_list':
					$deviceProps .= self::data2proplist($body, $lineAppend, $key, $value, $indent);
					break;

				case 'exclude':
				case 'include':
					if (!is_array($value))
						$value = array($value);
					foreach($value as &$part)
					{
						if ($part === '""' || $part === '') 
							continue;
						if ($part[0] !== '"')
							$part = self::quote($part);
					}
					self::append($body, $lineno, "$tabs$key\t" . implode(' ', $value) . "\n"); 
					break;

				default: 
					if ($key === 'script_list')
						self::data2proplist($body, $lineAppend, $key, $value, $indent);
					elseif (substr($key, -5) === '_list') 
						self::append($body, $lineno, $tabs . 'define ' . substr($key, 0, -5) . " {\n" . self::data2conf($value, $indent +1) . "$tabs}\n");
					else
						self::append($body, $lineno, self::data2param($definition, $key, $indent));
					break;
			}
		}
		ksort($body);
		
		return $dumptypes . implode('', $body) . $appendBeforeCurly . (empty($deviceProps) ? '' : "\n$deviceProps") . $tabsClose . '}' . $appendAfterCurly . "\n\n";
	}

	
	protected static function data2proplist(&$body, &$lineAppend, $key, $value, $indent)
	{
		$deviceProps = '';
		foreach($value as $prop => $val)
		{
			$lineno = self::lineno($value, $prop, $lineAppend);
			if ($value === null) 
				continue;
			if (self::ignoreKey($prop))
				continue;
			$tabs = str_repeat("\t", $indent);
			$quoted = $comment = '';
			if (!empty($value[$prop . '_comment']))
			{
				$comment = trim($value[$prop . '_comment']);
				if ($comment[0] !== '#')
					$comment = '#' . $comment;
				if (!empty($comment))
					$comment = "$tabs$comment";
			}
			if (!empty($comment))
				$comment = str_replace("\n", "\n" . $tabs, $comment) . "\n";
			if (is_array($val))
				foreach($val as $expr)
					$quoted .= ' ' . self::quote($expr);
			elseif (strlen($val))
				$quoted = (($val[0] === '"') ? $val : self::quote($val));
			elseif ($key === 'script_list')
				$quoted = '';
			else
				$quoted = '""';
			$propVal = (self::$comments ? $comment : '') . $tabs . substr($key, 0, -5) . "\t" . self::quote($prop) . "\t$quoted\n";
			if (self::GLOBAL_DEVICE_PROPS && $key === 'device_property_list')
				$deviceProps .= $propVal;
			else
				self::append($body, $lineno, $propVal);
		}
		return $deviceProps;
	}

	protected static function append(&$array, $key, $value, $sep = '')
	{
		if (isset($array[$key]))
			$array[$key] .= $sep . $value;
		else
			$array[$key] = $value;
	}
}
