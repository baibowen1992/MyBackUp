<?

















class ZMC_Yasumi_Amadmin extends ZMC_Yasumi
{
	
	protected function init()
	{
		$this->getAmandaConfName();
		parent::init();
	}

	





	protected function opFind()
	{
		$this->amadmin(array('find'));
	}

	
	
	protected function opFindTapes()
	{
		$this->checkFields(array('host', 'disk_name'), array('date', 'time', 'debug', 'timezone', 'timestamp', 'user_id', 'username', 'human'));
		$this->data['date'] = ZMC::filterDigits($this->data['date'], date('Ymd'));
		$this->data['time'] = ZMC::filterDigits($this->data['time'], '235959');
		$this->amadmin(array('find'), true);
		$records = array();
		foreach($this->records as $key => &$record)
			if (($record['host'] === $this->data['host']) && ($record['disk_name'] === $this->data['disk_name']))
				$records[$key] =& $record;

		if (empty($records))
			return $this->reply->addWarning(" 在选定的主机、备份项、日期和时间上没有找到备份记录");

		unset($record);
		$timestamp = ZMC::mktime($this->data['date'] . $this->data['time']);
		$this->reply['needed'] = array();
		
		foreach($records as &$record)
		{
			if($record['status'] === 'PARTIAL')
				continue;
			if ($record['timestamp'] > $timestamp)
				continue;

			if (empty($last))
			{
				$last = $record['timestamp'];
				$level = $record['level'];
			}

			if ($level == 0 && $record['timestamp'] !== $last) 
				break;

			if ($record['level'] > $level) 
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Missing backup. Can not proceed."));

			if ($record['level'] < $level)
			{
				$last = $record['timestamp'];
				$level = $record['level'];
			}

			if ($level === $record['level']){
				if ($last === $record['timestamp']) {
					$this->reply['needed'][$record['tape_label']] = $record;
				} else if (($this->data['_key_name'] === 'windowssqlserver' || $this->data['_key_name'] === 'windowsexchange') && $level > 0) { 
					$headerFile = ZMC::$registry->etc_amanda . $this->amanda_configuration_name . DIRECTORY_SEPARATOR. 'index'
							. DIRECTORY_SEPARATOR . $this->data['host'] . DIRECTORY_SEPARATOR . str_replace("\\", "_", $this->data['disk_name'])
							. DIRECTORY_SEPARATOR . str_replace('.gz', '.header', $record['index']);
					$found = false;
					foreach(file($headerFile) as $line){
						if($found){
							if(strpos($line, "<value>log</value>") !== false || strpos($line, "<value>incremental</value>") !== false){
								$this->reply['needed'][$record['tape_label']] = $record;
								$last = $record['timestamp'];
							}
							break;
						}
						if(strpos($line, "level-1-backup") !== false)
							$found = true;
					}
				}
			}
			
		}
	}

	protected function opFlush()
	{
		try
		{
			ZMC_ProcOpen::procOpen('amflush', $cmd = ZMC::getAmandaCmd('amflush'), array('-b', $this->getAmandaConfName(true), '-o', 'flush-threshold-dumped=0', '-o',
				'flush-threshold-scheduled=0','-o', 'taperflush=0'),
				$stdout, $stderr, 'amadmin command failed unexpectedly', $this->getLogInfo(), null, ZMC::$registry->proc_open_short_timeout);
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to process '$cmd': $e"));
		}
		exec(ZMC::$registry->cnf->zmc_bin_path . "backup_monitor  2>&1 &");
		$this->reply->flush = $stdout.$stderr;
	}

	protected function opHoldingList()
	{
		$this->reply->holding_list = null;
		$this->reply->holding_list =& $this->amadmin(array('holding', 'list', '-l'));
	}

	protected function &opGetRecords()
	{ return $this->amadmin(array('find'), true); }

	protected function &amadmin(array $args, $getRecords = false)
	{
		$stdout = '';
		try
		{
			array_unshift($args, $confName = $this->getAmandaConfName(true));
			$this->cacheFn = "$confName.amadmin";
			$dependencies = array();
			if (isset($this->data['holding_directory']))
			{
				$dependencies = glob($this->data['holding_directory'] . '/*');
				$dependencies[] = $this->data['holding_directory']; 
			}
			$dependencies[] = "/etc/amanda/$confName/tapelist";
			$holdOnly = ($args[1] === 'holding');
			if (!$holdOnly && ZMC::useCache($this->reply, $dependencies, $this->cacheFn, true))
			{
				$include1 = (include $this->cacheFn);
				$this->records = array();
				
				if ($getRecords)
				{
					$this->records = (include $this->cacheFn . '.records');
					$this->reply->records =& $this->records;
				}
				if (($include1 === true) && is_array($this->records))
					return $stdout;
			}
			if (!$holdOnly && ($this->cacheFn[0] === '/'))
				$file = new ZMC_Sed($this->reply, $this->cacheFn);

			//$this->reply->addWarning("不使用 $this->cacheFn");   comment by zhoulin 20141102
			ZMC_ProcOpen::procOpen('amadmin', $cmd = ZMC::getAmandaCmd('amadmin'), $args, $stdout, $stderr,
				'amadmin command failed unexpectedly', $this->getLogInfo(), null, ZMC::$registry->proc_open_short_timeout,
				$holdOnly ? null : array($this, 'processAmadminFind'));

			if (!empty($stderr))
				$this->reply->addWarnError("$cmd: $stderr");

			$this->noticeLog("ZMC_ProcOpen::procOpen('amadmin', $cmd " . join(" ", $args) . "; stdout: $stdout; stderr: $stderr", __FILE__, __LINE__);
			if (!$holdOnly && ($this->cacheFn[0] === '/'))
			{
				$file->close("<?\n\$this->reply->merge(" . var_export($this->reply->getArrayCopy(false), true) . ");\nreturn true;\n");
				file_put_contents($this->cacheFn . '.records', "<?\nreturn " . var_export($this->records, true) . ";\n");
			}
			if ($getRecords)
				$this->reply->records =& $this->records;
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to process '$cmd': $e"));
		}

		return $stdout;
	}

	public function processAmadminFind($pipes, &$stdout, &$stderr, $tv_sec)
	{
		$stdin = $pipes['stdin'];
		$outs = $pipes['stdout'];
		$errs = $pipes['stderr'];











		fclose($stdin);
		$write = null;
		$except = null;
		$read = array($outs, $errs);
		$count = stream_select($read, $write, $except, $tv_sec);
		if ($count === false || $count === 0)
			throw new ZMC_Exception_ProcOpen(__CLASS__ . ($count === false ? ': failure' : ": timeout ($tv_sec seconds)"));
		$this->debugLog(__FUNCTION__ . "(); stream_select() returned: $count = " . print_r($read, true));
		$i = 0;
		$hosts = $oldest = $this->records = array();
		$this->reply['level0_tapelist'] = array();
		$this->reply['used_tapelist'] = array();
		while(false !== ($line = fgets($outs, 8192)))
		{
			if ($line === 'No dump to list')
				continue;

			$len = strlen($line);
			if (($len < 5) ||  (($line[$len-4] !== 'O' && $line[$len-3] !== 'K') && (substr($line, -9, 7) != 'PARTIAL'))) 
			{
				if ($line[0] === 'd') $pos = strpos($line, 'file part') -1;
				continue;
			}

			if (!strncmp($line, 'Warning:', 8) || strpos($line, 'matches neither a host nor a disk'))
			{
				$this->reply->addWarning($line);
				continue;
			}

			if (strpos($line, '(dumper)'))
				continue;

			if (isset($this->records[$key = substr($line, 0, $pos)]))
				continue;

			preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|\S+/', $line, $matches);
			$record = array_combine(array('date', 'time', 'host', 'disk_name', 'level', 'tape_label', 'file', 'part', 'status'), array_slice($matches[0], 0, 9));
			if (count($matches) > 9)
				$record['message'] = implode(' ', array_slice($matches[0], 10));

			foreach($record as &$value)
				if ($value[0] === '"')
					$value = ZMC_Yasumi_Parser::unquote($value);
			
			if((substr($line, -9, 7) == 'PARTIAL'))
				$record['status'] = 'PARTIAL';

			$record['timestamp'] = ZMC::mktime("$record[date] $record[time]");
			$record['datetime'] = str_replace('-', '', $record['date']) . str_replace(':', '', $record['time']);
			$record['index'] = str_replace('-', '', $record['date']) . str_replace(':', '', $record['time']) . '_' . $record['level'] . '.gz';
			$this->reply['used_tapelist'][$record['tape_label']] = true;
			$this->reply['status'][$record['tape_label']] = $record['status'];
			if ($record['level'] === '0')
			{
				$this->reply['level0_tapelist'][$record['tape_label']] = str_replace(array(':', '-'), array(), "$record[date]$record[time]");
				if (empty($oldest[$key]) || $record['timestamp'] < $oldest[$key])
					$oldest[$key] = $record['timestamp'];
			}
			if (is_array($this->records))
				$this->records[$key] = $record;
			$uniqueHosts[$record['host']][$record['disk_name']] = null;
			if (($i++ % 5000) === 0) 
				if (ZMC_Timer::testMemoryUsage())
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Insufficient memory.  "Increase memory_limit" in: /opt/zmanda/amanda/php/etc/php.ini'));
		}
		$dates = $times = $hosts = $disk_names = array();
		foreach($this->records as &$record)
		{
			$dates[] = $record['date'];
			$times[] = $record['time'];
			$hosts[] = $record['host'];
			$disk_names[] = $record['disk_name'];
		}
		array_multisort($dates, SORT_DESC, $times, SORT_DESC, $hosts, SORT_DESC, $disk_names, SORT_DESC, $this->records);
		$stdout = $line; 
		$stderr = fread($errs, 4096);
		$hostList = array();
		if (!empty($uniqueHosts))
			foreach($uniqueHosts as $host => $dles)
				$hostList[$host] = array_keys($dles);
		
		$this->reply['dle_list'] = $hostList;
		$this->reply['retention_timestamp'] = (empty($oldest) ? 'NA' : max($oldest));
	}
	
	



	public function stringArrayMerge()
	{
		$arrays = func_get_args();
		$result = array();
		foreach($arrays as $array)
			foreach($array as $key => $val)
				if ($val !== '')
				{
					$result[$key] = $val;
					
				}
		ksort($result);
		return $result;
	}

	protected function opDisklist()
	{
		try
		{
			ZMC_ProcOpen::procOpen('amadmin', $cmd = ZMC::getAmandaCmd('amadmin'), $args = array($this->getAmandaConfName(true), 'disklist'),
				$stdout, $stderr, 'amadmin command failed unexpectedly', $this->getLogInfo());
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to process 'amadmin disklist' (" . $e->getStderr() . $e->getStdout() . ")"));
		}

		$this->reply['disklist'] = $stdout;
	}
}
