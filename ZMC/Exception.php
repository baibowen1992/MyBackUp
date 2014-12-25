<?




















class ZMC_Exception extends Exception
{
	const ZMC_FILE_PREFIX_LEN = 49; 

	
	protected $function;

	
	public $date;

	public function __construct($message = '', $code = 0, $file = null, $line = null, $depth = 0)
	{
		if (isset(ZMC::$registry) && (ZMC::$registry->debug || ZMC::$registry->dev_only))
			$message = '{' . ZMC::$registry->zmc_svn_info . '} ' . $message;
		if (is_object($message))
			$message = $message->__toString();
		parent::__construct(is_array($message) ? implode('; ', $message) : $message, $code);
		if ($file !== null)
			$this->file = substr($file, self::ZMC_FILE_PREFIX_LEN);
		if ($line !== null)
			$this->line = $line;
		if ($depth)
		{
			list ($this->function2, $this->file2, $this->line2) = ZMC_Error::getFileLine($this->function, $this->file, $this->line, $depth );
			$this->file2 = substr($this->file2, self::ZMC_FILE_PREFIX_LEN);
		}
		$msg = $this->errorLog();
	}

	public function getLocation($verbose = false)
	{
		$loc = '+' . $this->line . ' ' . ($verbose ? $this->file : basename($this->file));
		if (!empty($this->file2))
			$loc .= ";\n+" . $this->line2 . ' ' . ($verbose ? $this->file2 : basename($this->file2));

		return $loc;
	}

	protected function errorLog()
	{
		$this->date = strtok(ZMC::errorLog(__CLASS__ . __LINE__ . $this->toString(true)), '|');
	}

	public function addMessage($message)
	{
		$this->message .= "; $message";
	}

	public function __toString()
	{
		return $this->toString();
	}

	public function toString($verbose = false)
	{
		$debug = is_object(ZMC::$registry) && ZMC::$registry->debug;
		$file = ($debug || $verbose) ? basename($this->file) . '#' : '';
		$msg = $this->date . " Exception $file$this->line: $this->message\n";
		if ($verbose)
			$msg .= "\n" . $this->getLocation(true) . "\n";
		if ($debug || $verbose)
			return $msg . "\n" . substr(str_replace(__DIR__, '', $this->getTraceAsString()), 0, 2048) . '...';
		return $msg;
	}
}
