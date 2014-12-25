<?













class ZMC_Registry_MessageBox extends ZMC_Registry
{
	const STICKY_RESTART = 1; 
	const STICKY_SESSION = 2; 
	const STICKY_USER = 3; 
	const STICKY_ONCE = 4; 

	private $prefix = '';

	private static $keys2icons = array(
		'details'	=> '', 
		'bombs'	=> 'failure', 
		'errors'	=> 'failure', 
		'instructions'		=> 'success',
		'internals'	=> 'failure', 
		'messages'	=> 'success', 
		'warnings'	=> 'warning', 
		
		'escapedDetails'	=> '',
		'escapedErrors'		=> 'failure',
		'escapedInstructions'=> 'success',
		'escapedInternals'	=> 'failure',
		'escapedMessages'	=> 'success',
		'escapedWarnings'	=> 'warning',
	);

	private static $types2label = array(
			'internals' => 'Internal ERROR',
			'escapedInternals' => 'Internal ERROR',
			'instructions' => 'Instructions',
			'messages' => 'Information',
			'escapedMessages' => 'Information',
			'warnings' => 'Warning',
			'escapedWarnings' => 'Warning',
			'errors' => 'ERROR',
			'escapedErrors' => 'ERROR',
		);

	public function __construct($array = null, $commandMode = false)
	{
		parent::__construct($array, null);
		$this['command_mode'] = $commandMode;
		foreach(self::$keys2icons as $key => $ignored)
			if (!isset($this[$key]))
				$this[$key] = array();
	}

	public function getPrefix()
	{	return $this->prefix; }

	public function setPrefix($prefix)
	{
		$old = $this->prefix;
		$this->prefix = $prefix;
		return $old;
	}

	public function isErrors()
	{
		return count($this['bombs'])
			+ count($this['errors']) + count($this['escapedErrors'])
			+ count($this['internals']) + count($this['escapedInternals']);
	}

	public function isErrorsOrWarnings()
	{
		return count($this['warnings']) + count($this['escapedWarnings']);
			+  $this->isErrors();
	}

	public function getAllMerged()
	{
		$result = '';
		foreach(array_keys(self::$keys2icons) as $key)
			if (!empty($this[$key]))
				$result .= "$key: " . (is_array($this[$key]) ? (implode("\n", $this[$key])) : $this[$key]);

		return $result;
	}

	public function getErrors()
	{
		return implode(' ', $this['internals']) . implode(' ', $this['escapedInternals'])
			.  implode(' ', $this['errors']) . implode(' ', $this['escapedErrors']);
	}

	public function getMessages()
	{
		return implode(' ', $this['messages']) . implode(' ', $this['escapedMessages']);
	}
	
	public function __toString()
	{
		$result = array();
		foreach(self::$types2label as $key => $prefix)
			if (!empty($this[$key]))
				if (!is_array($this[$key]))
					ZMC::quit($this);
				else
				{
					$msgs = trim(implode("\n", $this[$key]));
					if (!empty($msgs))
						$result[] = $prefix . ': ' . $msgs;
				}

		return implode("\n", $result);
	}

	public function toCommentBox()
	{
		$result = array();
		foreach(self::$keys2icons as $key => $icon)
			if (!empty($this[$key])){
				 if(!preg_match("/Updating AE crontab/", implode("\n<br />", $this[$key]))){
				$result[$key] = '<div><img onclick="this.parentNode.style.display = \'none\'" style="cursor:pointer; vertical-align:text-bottom; padding-right:3px" src="/images/global/calendar/icon_calendar_'
					. $icon . '.gif" alt="' . $icon . '" /> '
					. ZMC::moreExpand(implode("\n<br />", $this[$key]))
					. '</div>';
				 }
			}
		return implode("\n", $result);
	}

	private function add($msgs, $type, $prepend = false, $details = null, $file = null, $line = null)
	{
		if (empty($msgs))
			return;

		if (!is_array($msgs))
			$msgs = array($msgs);

		if ($this->command_mode)
			foreach($msgs as $msg)
			{
				$msg = trim($msg);
				if (!empty($msg))
					echo self::$types2label[$type] . ': ' . $msg . "\n";
			}

		if (!is_array($this[$type])) 
			if (ZMC::$registry->debug)
				ZMC::quit(array("corrupt key: $type" => $this));
			else
				ZMC::headerRedirect(ZMC::$registry->bomb_url, __FILE__, __LINE__);

		if (ZMC::$registry->dev_only && !empty($file))
			foreach($msgs as &$m)
				$m = "$file:$line $m";

		if (!empty($this->prefix))
			foreach($msgs as &$m)
				$m = $this->prefix . "\0" . $m;

		foreach(array_keys($msgs) as $key)
			if (!is_string($key))
			{
				$msgs[hash('adler32', $msgs[$key])] = $msgs[$key];
				unset($msgs[$key]);
			}

		if ($prepend) 
			$this[$type] = $msg = array_merge($msgs, $this[$type]);
		else
			foreach($msgs as $key => $msg) 
				$this[$type][$key] = (is_array($msg) ? print_r($msg, true) : $msg);

		if (!empty($details))
			if (strlen($details) > 1) 
				$this->addDetail($details, $file, $line);
			else
				switch($details)
				{
					case self::STICKY_ONCE:
						ZMC::$registry->setOverrides(array('sticky_once' => array_merge(ZMC::$registry->sticky_once, array($type => $msgs))));
						break;

					case self::STICKY_RESTART:
						ZMC::$registry->setOverrides(array('sticky_restart' => array_merge(ZMC::$registry->sticky_restart, array($type => $msgs))));
						break;

					case self::STICKY_SESSION:
					case self::STICKY_USER:
					default:
						throw new ZMC_Exception("Sticky mode '$details' not implemented.");
				}

		return $msg;
	}

	public function injectStickyMessages() 
	{
		foreach(array(	ZMC::$registry->sticky_restart,
						ZMC::$registry->sticky_session,
						ZMC::$registry->sticky_user,
						ZMC::$registry->sticky_once,
						ZMC::$registry->sticky_once_done) as $stickies)
			if (is_array($stickies))
				foreach($stickies as $type => $msgs)
					$this->add($msgs, $type, true); 
		if (!empty(ZMC::$registry->sticky_once_done))
			ZMC::$registry->setOverrides(array('sticky_once_done' => ZMC::$registry->sticky_once)); 
		if (!empty(ZMC::$registry->sticky_once))
			ZMC::$registry->setOverrides(array('sticky_once' => null)); 
	}

	public function addDefaultInstruction($msg)
	{
		if (empty($this['instructions']) && empty($this['escapedInstructions']))
			$this->addInstruction($msg);
	}

	public function addInstruction($msg)
	{
		return $this->add($msg, 'instructions');
	}

	public function addDefaultEscapedInstruction($msg)
	{
		if (empty($this['instructions']) && empty($this['escapedInstructions']))
			$this->addInstruction($msg);
	}

	public function addEscapedInstruction($msg)
	{
		return $this->add($msg, 'escapedInstructions');
	}

	public function prependInternal($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'internals', true, $details, $file, $line);
	}

	public function addBomb($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'bombs', false, $details, $file, $line);
	}

	public function addInternal($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'internals', false, $details, $file, $line);
	}

	public function addEscapedInternal($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'escapedInternals', false, $details, $file, $line);
	}

	public function prependError($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'errors', true, $details, $file, $line);
	}

	public function addError($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'errors', false, $details, $file, $line);
	}

	public function addEscapedError($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'escapedErrors', false, $details, $file, $line);
	}

	public function prependMessage($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'messages', true, $details, $file, $line);
	}

	public function addMessage($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'messages', false, $details, $file, $line);
	}

	public function addEscapedMessage($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'escapedMessages', false, $details, $file, $line);
	}

	public function addWarning($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'warnings', false, $details, $file, $line);
	}

	public function addWarnError($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, ZMC::$registry->safe_mode ? 'errors':'warnings', false, $details, $file, $line);
	}

	public function addDetail($msg, $file = null, $line = null)
	{
		return $this->add($msg, 'details', false, null, $file, $line);
	}

	public function addEscapedWarning($msg, $details = null, $file = null, $line = null)
	{
		return $this->add($msg, 'escapedWarnings', false, $details, $file, $line);
	}

	public function addYasumiServiceException($exception)
	{
		if (empty($exception))
			return;
		$this->details = $exception->getMessage();
		if (false === strpos($this->details, 'OCC:'))
		{
			$fields = explode("~", $this->details);
			if (count($fields) >= 7)
				$msg = $fields[7];
			else
			{
				$msg = $this->details;
				unset($this->details);
			}
		}
		else
		{
			$url = ZMC::getUrl($_SERVER['SCRIPT_NAME']);
			
			$pos = strpos($url, ".php");
			if (false !== $pos)
				$url = substr($url, 0, $pos + 4);
					
			$msg = "Concurrent access detected:<a href='$url'>Reload page and try again.</a>";
		}
			
		$this->addEscapedError($msg);
	}

	




























	



	public function merge($array, $props = null, $boxOnly = false)
	{
		foreach (self::$keys2icons as $key => $ignored)
			if (isset($array[$key]))
			{
				if (is_array($array[$key]) || $array[$key] instanceof ArrayObject)
					foreach($array[$key] as &$msg)
						$this[$key][] =& $msg;
				else
					$this[$key][] = $array[$key]; 

				unset($array[$key]); 
			}

		if (!$boxOnly && !empty($array))
			parent::merge($array, $props);

		return $this;
	}

	












	public function normalizeForMessageBox()
	{
		foreach (self::$keys2icons as $key => $ignored)
		{
			if ($this->offsetExists($key))
			{
				if (!is_array($this[$key]))
					$this[$key] = array($this[$key]);
			}
			else
			{
				$this[$key] = array();
			}
		}
	}

	
	public function unsetKeys($emptyOnly = true)
	{
		foreach (self::$keys2icons as $key => $ignored)
			if (!$emptyOnly || (isset($this[$key]) && empty($this[$key])))
				$this->offsetUnset($key);
	}

	public static function __set_state($array)
	{
		return new static($array);
	}

	public function getArrayCopy($box = null)
	{
		if ($box === null)
			return parent::getArrayCopy();
		if ($box === true)
		{
			$array = array();
			foreach (self::$keys2icons as $key => $ignored)
				$array[$key] = $this->$key;
		}
		else
		{
			$array = parent::getArrayCopy();
			foreach (self::$keys2icons as $key => $ignored)
				unset($array[$key]);
		}

		return $array;
	}

	public function cloneErrorsAndWarnings()
	{
		$array = array();
		foreach(array('errors', 'internals', 'warnings', 'escapedErrors', 'escapedInternals', 'escapedWarnings') as $key)
			if (!empty($this->$key))
				$array[$key] = $this->$key;

		return(empty($array) ? false : (new self($array)));
	}
}
