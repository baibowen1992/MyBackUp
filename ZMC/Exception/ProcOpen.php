<?













class ZMC_Exception_ProcOpen extends ZMC_Exception
{
	
	protected $tool = null;

	
	protected $command = null;

	
	protected $args = null;
	
	
	protected $stderr = null;

	
	protected $stdout = null;

	
	protected $logInfo = null;

	
	

	public function __construct($message, $code, $stdout, $stderr, $tool, $command, $args, $logInfo = null)
	{
		$this->stdout = trim($stdout);
		$this->stderr = trim($stderr);
		$this->tool = $tool;
		$this->command = $command;
		$this->args = $args;
		$this->logInfo = $logInfo;













		parent::__construct(is_object($message) ? $message->getMessage() : $message, $code, null, null, 1);
	}

	protected function errorLog()
	{
		ZMC::errorLog(__CLASS__ . ' ' . $this->getMessage() . "; Tool: $this->tool; Command: $this->command " . implode(' ', $this->args) 
			. "; stderr: $this->stderr; stdout: $this->stdout", $this->code ? $this->code:1, $this->logInfo);
	}

	public function getStdout()
	{
		return $this->stdout;
	}

	public function getStderr()
	{
		return $this->stderr;
	}

	public function getCommand()
	{
		return $this->command;
	}

	public function getAll()
	{
		return array('message' => $this->getMessage(), 'code' => $this->getCode(), 'tool' => $this->tool,
			'command' => $this->command, 'args' => $this->args, 'stdout' => $this->stdout, 'stderr' => $this->stderr);
	}

	
	public function __toString()
	{
		if (ZMC::$registry->debug)
			return "Exception " . basename($this->file) . "#$this->line: $this->message\namandabackup \$ $this->command " . implode(' ', $this->args)
				. (empty($this->stdout) ? '' : "\nstdout=$this->stdout")
				. (empty($this->stderr) ? '' : "\nstderr=$this->stderr");
		else
			return 'Tool "' . $this->tool . '" experienced problems: ' . $this->message . "\n" . $this->stdout . $this->stderr;
	}
}
