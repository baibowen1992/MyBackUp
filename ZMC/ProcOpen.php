<?













class ZMC_ProcOpen
{
	private static $process = null;
	






























	public static function procOpen($tool, $executable, $args, &$stdout, &$stderr, $errMsg = null, $logInfo = null, $cwd = null, $tv_sec = 0, $callback = null, $escape = true)
	{
		$e = $stderr = $stdout = $reason = '';
		$uniqid = posix_getpid() . time();

		foreach($args as &$arg)
			if($escape)
				$arg = escapeshellarg(str_replace('\\', '\\\\', $arg)); 

		$command = $executable . ' ' . implode(' ', $args);
		
		if ($cwd === null && ZMC::$registry)
			$cwd = ZMC::$registry->tmp_path;

		static $PATH = null;
		if ($PATH === null)
			$PATH = getenv('PATH');

		static $LD_LIBRARY_PATH = null;
		if ($LD_LIBRARY_PATH=== null)
			$LD_LIBRARY_PATH = getenv('LD_LIBRARY_PATH');

		ZMC::auditLog(__FUNCTION__ . "\$START\$$uniqid\$$tool\$$command\$$cwd\$$PATH", true, $logInfo);
		$retval = false;
		$process = proc_open($command,
			array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
			$pipes,
			$cwd,
			array('LD_LIBRARY_PATH' => $LD_LIBRARY_PATH, 'PATH' => $PATH),
			
			
			
			array('bypass_shell' => true));

		if (is_resource($process))
		{
			self::$process = $process; 
			$pipes = array('stdin' => $pipes[0], 'stdout' => $pipes[1], 'stderr' => $pipes[2]);
			if (!$callback)
			{
				fclose($pipes['stdin']);
				if ($tv_sec)
				{
					$write = null;
					$except = null;
					$read = array($pipes['stdout'], $pipes['stderr']);
					$count = stream_select($read, $write, $except, $tv_sec);
					if ($count === false || $count === 0)
					{
						if (count === 0)
							self::terminate($tool, "Termination requested for $tool because it did not complete before the timeout ($tv_sec seconds).");

						throw new ZMC_Exception_ProcOpen($errMsg . ($count === false ? ': failure' : ': timeout'), $retval, $stdout, $stderr, $tool, $executable, $args, $logInfo);
					}
				}
			}
			try
			{
				$info = proc_get_status(self::$process);
				if ($callback) 
					call_user_func($callback, $pipes, $stdout, $stderr, $tv_sec);
				else
				{
					$stdout = stream_get_contents($pipes['stdout']);
					$stderr = stream_get_contents($pipes['stderr']);
					
					$regexp = '/^.* line .*Keyword.split.diskbuffer is deprecated.*$/m';
					if (!empty($stdout) && strpos($stdout, 'deprecated'))
						$stdout = preg_replace($regexp, '', $stdout);
					if (!empty($stderr) && strpos($stderr, 'deprecated'))
						$stderr = preg_replace($regexp, '', $stderr);
				}

			}
			catch(ZMC_ProcOpen_Terminate $e)
			{
				$terminate = true;
				$reason = 'TERM Exception - ';
			}
			catch(Exception $e) 
			{ $reason = 'OTHER Exception - '; }

			foreach($pipes as &$pipe)
				if (is_resource($pipe))
					fclose($pipe);

			if (!empty($terminate))
				self::terminate($tool, "Termination requested for $tool via exception: $e");

			$retval = 1; 
			$msg = '';
			pcntl_waitpid($info['pid'], $status);
			if (pcntl_wifexited($status))
			{
				$retval = pcntl_wexitstatus($status);
				$reason .= "exited status $retval\n";
			}
			elseif (pcntl_wifsignaled($status))
				$reason .= "killed by signal " . pcntl_wtermsig($status);
			elseif (pcntl_wifstopped($status))
				$reason .= "stopped by signal " . pcntl_wstopsig($status);

			self::$process = null; 
		}

		$devOnly = '';
		










		if ($retval === 0)
			$errMsg = $reason;
		elseif ($retval === false)
			$errMsg = "$errMsg (Unable to launch '$tool')";
		elseif (empty($errMsg))
			$errMsg = "$executable $reason";
		ZMC::auditLog(__FUNCTION__ . "\$END  \$$uniqid\$$tool\$$command\$$errMsg", $retval, $logInfo);

		if ($e)
			if (empty($stdout) && empty($stderr))
				throw $e;
			else
				throw new ZMC_Exception_ProcOpen($e, $retval, substr($stdout, 0, 32768), substr($stderr, 0, 32768), $tool, $executable, $args, $logInfo);
		if ($retval !== 0)
			throw new ZMC_Exception_ProcOpen($errMsg, $retval, substr($stdout, 0, 32768), substr($stderr, 0, 32768), $tool, $executable, $args, $logInfo);
		return $command;
	}

	




	private static function terminate()
	{
		if (!is_resource(self::$process))
			return;

		proc_terminate(self::$process);
		for($i = 3; $i--; $i && sleep(1))
		{
			$stat = proc_get_status(self::$process);
			if (!$stat['running'])
				return;
		}
		error_log(__CLASS__ . __LINE__ . "() terminating $tool via SIGKILL");
		proc_terminate(self::$process, 9);
	}

	public static function status()
	{
		if (is_resource(self::$process))
			return proc_get_status(self::$process);
		return false;
	}
}

class ZMC_ProcOpen_Terminate extends ZMC_Exception
{}
