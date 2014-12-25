<?

























class ZMC_Yasumi_Job extends ZMC_Yasumi
{
private $jobFilename; 
private $jobName; 
private $stateFile; 
protected $job = null;
protected $aborting = 0;
protected $pidFile;
protected $exception = null;
protected $jobType = 'ZMC Job';

const ABORTED = 'Aborted'; 
const CANCELLED = 'Cancelled'; 
const CRASHED = 'Failed';
const FAILED = 'Failed';
const FINISHED = 'Finished';
const INITIALIZING = 'Initializing';
const UNBORN = 'Not Started';
const RUNNING = 'Running';

static $notRunning = array( 
		self::ABORTED => true,
		self::CANCELLED => true,
		self::CRASHED => true,
		self::FAILED => true,
		self::FINISHED => true,
		self::UNBORN => true,
);

protected function isRunningState($state = null)
{	return (!isset(self::$notRunning[($state === null) ? $this->reply->state['state'][0] : $state])); }

protected function init()
{
	$this->optional[] = 'job';
	$this->optional[] = 'job_name';
	parent::init();
	if (!empty($this->data['job']))
	{
		$this->job =& $this->data['job']; 
		unset($this->data['job']);
		if (!empty($this->job['job_name']))
			$this->jobName = $this->job['job_name'];
	}
	if (empty($this->jobName))
		$this->jobName = $this->data['job_name'];
	if (empty($this->jobName)) ZMC::quit($this);
	$this->jobPrefix = $this->getAmandaConfPath(true) . "jobs/saved/{$this->jobType}-{$this->jobName}";
	$this->jobFilename = $this->jobPrefix . '.yml';
	$this->mkdirIfNotExists(dirname($this->jobFilename));
	$this->jobHistoryPath = $this->getAmandaConfPath(true) . "jobs/history";
	$this->pidFile = $this->jobPrefix . '.pid';
	$this->stateFile = $this->jobPrefix . '.state'; 
	$this->initState();
}

private function initState()
{
	if (!isset($this->reply['state']))
		$this->reply['state'] = array(
			'fn' => $this->stateFile,
			'output' => array(),
			'pid' => 0,
			'running' => false,
			'state' => array(self::UNBORN),
			'status' => array(),
			'successful' => false,
			'timestamp_end' => 0,
			'date_started_timestamp' => 0,
		);
}

protected function opRead()
{
	$this->updateStatsCache();
	$this->reply['job'] = array();
	if (file_exists($this->jobFilename))
		$this->reply->job =& $this->loadYaml($this->jobFilename, null );
	$this->opGetState();
}

protected function opMerge()
{
	$this->opRead();
	if (!isset($this->job['occ']))
	{
		$msg = $this->reply->addWarnError("Missing OCC");
		if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
	}

	if (isset($this->reply['job']['occ']) && ($this->reply['job']['occ'] > $this->job['occ']))
		ZMC::Quit($this);

	$this->job['occ'] = ZMC::occTime();
	$this->reply['job']['lbox'] = null;
	$this->reply['job']['rbox'] = null;
	if (!empty($this->reply['job']['media_explored']))
		$this->reply['job']['media_explored'] = array(); 
	$this->merge($this->reply['job'], $this->job);
	$contents = $this->dumpYaml($this->reply['job']);
	$bak = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . $this->getAmandaConfName(true) . str_replace(DIRECTORY_SEPARATOR, '_', $this->jobFilename ) . ZMC::dateNow(true);
	if (file_exists($this->jobFilename))
		$this->permCheck($this->jobFilename);

	$this->commit($this->jobFilename, $bak, null, $contents);
}

protected function opDelete()
{
	if (!file_exists($this->jobFilename))
		throw new ZMC_Exception_YasumiFatal($this->reply->addError("Job not found at {$yml->jobFilename}.  Can not delete."));

	unlink($this->jobFilename);
	$this->opClear(); 
}

protected function opAbort()
{	$this->opGetState(true); }

protected function opClear()
{
	$this->opGetState(false);
	if ($this->reply->state['running'])
		return $this->debugLog('Job Running. Ignored "Clear" request for ' . $this->stateFile);
	@unlink($this->stateFile);
	@unlink($this->jobFilename);
	unset($this->reply['state']);
	$this->initState();
}


protected function opGetState($abort = false)
{
	if (!file_exists($this->stateFile))
	{
		$this->setState();
		return false;
	}

	$this->reply->state = require($this->stateFile);
	$this->duration();
	$this->reply->state['running'] = false; 
	if (file_exists($this->pidFile))
	{
		$state = self::CRASHED;
		$pid = intval(file_get_contents($this->pidFile));
		if (!empty($pid) && file_exists("/proc/$pid"))
		{
			$this->reply->state['running'] = true;
			if (!$abort)
				return true;
			$state = self::RUNNING;
			if (posix_kill($pid, SIGKILL)) 
			{
				$this->reply->state['running'] = false;
				$state = self::ABORTED;
				$this->debugLog('Aborted job ' . $this->jobFilename);
				unlink($this->pidFile);
				$ended = true;
			}
		}
		else
		{
			unlink($this->pidFile);
			if (empty($this->reply->state['timestamp_end']))
				$ended = true;
		}
		if (!empty($ended))
		{
			$this->reply->state['timestamp_end'] = time();
			$this->setState($state . __LINE__, null, $state);
		}
	}
	return $this->reply->state['running'];
}




protected function prepare($overwrite = false, $waitForState = self::FINISHED)
{
	ZMC::mkdirIfNotExists($this->jobHistoryPath);
	if ($overwrite !== true && file_exists($this->stateFile))
	{
		$this->opGetState();
		if ($overwrite === false || !isset($overwrite[$this->reply->state['state'][0]]))
		{
			$this->reply->addWarnError('Job "' . ucfirst(strtolower($this->jobName)) . '" ' . $this->jobType . ' is ' . $this->reply->state['state'][0] . ". Please wait until this job is $waitForState.");
			   
			if (ZMC::$registry->safe_mode) return false;
		}
	}

	$this->job['date_created_amanda'] = ZMC::dateNow(true);
	$this->job['date_created_human'] = ZMC::humanDate(true);
	$this->job['date_created_timestamp'] = time();
	$this->historyFileName = $this->jobHistoryPath . DIRECTORY_SEPARATOR . $this->job['date_created_amanda'] . '_' . $this->jobType;
	
	file_put_contents($this->historyFileName, json_encode($this->job));
	$this->reply->state = array(
		'output' => array(),
		'state' => array(),
		'status' => array(),
		'date_created_amanda' => ZMC::dateNow(true),
		'date_created_human' => ZMC::humanDate(true),
		'date_created_timestamp' => time(),
	);
	$this->starttime = $this->microtime_float();
	$this->setState($this->jobType . ' ' . $this->jobName . ' Initializing ..', null, self::INITIALIZING);
	return true;
}

protected function zmc_job_signal_handler($signal)
{
	$this->debugLog("Caught $signal");
	switch($signal)
	{
		case SIGTERM:
			$this->aborting = time();
			break;

		case SIGHUP:
			break;

		default:
			break;
	}
}

protected function start($status = null, $output = null, $state = self::RUNNING)
{
	if (empty($status))
		$status = 'Starting ' . ($this->jobName === 'default' ? '' : $this->job['job_name'] . ' ') . $this->jobType;
	$this->reply->state['date_started_amanda'] = ZMC::dateNow(true);
	$this->reply->state['date_started_human'] = ZMC::humanDate(true);
	$this->reply->state['date_started_timestamp'] = time();
	$this->reply->state['date_started_occ'] = ZMC::occTime();
	$this->setState($status, $output, $state); 
	$this->reply->state['running'] = $this->isRunningState($state);
	ZMC_Events::add("$status @TODO more details" . __FILE__ . __LINE__, ZMC_Error::NOTICE, $output, $this->getAmandaConfName(true), $this->reply->state['date_started_timestamp'], 'Restore', $this->getUserId());
	if (!$this->synchronousBegin(true)) 
		return $this->childPid; 
	
	declare(ticks = 1);
	pcntl_sigprocmask(SIG_BLOCK, array(SIGUSR1));
	pcntl_signal(SIGTERM, array($this, 'zmc_job_signal_handler'), true);
	$this->reply->state['pid'] = $this->pid;
	if (strlen($this->pid) !== file_put_contents($this->pidFile, $this->pid))
	{
		$msg = $this->reply->addWarnError(ZMC::getFilePermHelp($this->pidFile));
		if (ZMC::$registry->safe_mode) throw new ZMC_Exception_YasumiFatal($msg);
	}
	$this->setState(null, null, null); 
}

protected function runFilter()
{
	parent::runFilter();
	if (!file_exists($this->stateFile)) 
		return;

	if (empty($this->parentPid))
		return; 

	$this->reply->state['messages'] = $this->reply->getAllMerged(); 
	$this->reply->state['timestamp_end'] = time();
	if (empty($this->endState))
		if ($this->isRunningState())
			$this->endState = self::FINISHED; 
		else 
			$this->endState = $this->reply->state['state'][0];
	$this->reply->state['successful'] = ($this->endState === self::FINISHED);
	$summary = ($this->jobName === 'default' ? '' : $this->job['job_name'] . ' ') . $this->jobType . ' ' . $this->endState;
	$this->duration();
	$this->reply->state['running'] = false;
	$this->reply->state['user_id'] = $this->data['user_id'];
	$this->reply->state['username'] = $this->data['username'];
	
	if (file_exists($this->jobFilename))
		$restore_history =& $this->loadYaml($this->jobFilename, null );
	$this->reply->state['date_time_parsed'] = $restore_history['date_time_parsed'];
	unset($restore_history);
	$this->endtime = $this->microtime_float();
	$totaltime = $this->time_diff($this->starttime , $this->endtime);

	$this->setState($summary . " $totaltime.", empty($this->exception) ? null : "$this->exception", $this->endState);
	unlink($this->pidFile);
	
	file_put_contents($this->historyFileName . '.state', json_encode($this->reply->state));
	$hstatefilename = basename($this->historyFileName);
	exec(ZMC::$registry->cnf->zmc_bin_path . "restore_history --config=".$this->job['config']." --restore-state-file=$hstatefilename.state --restore-list > /dev/null 2>/dev/null &");
	ZMC_Mysql::connect(true);
	ZMC_Events::add($summary, $this->reply->state['successful'] ? ZMC_Error::NOTICE : ZMC_Error::ERROR, empty($this->reply->state['output']) ? '' : $this->reply->state['output'][0], $this->getAmandaConfName(true), $this->reply->state['date_started_timestamp'], 'Restore', $this->getUserId());
}

protected function duration()
{
	$duration = (empty($this->reply->state['timestamp_end']) ? time():$this->reply->state['timestamp_end']) - $this->reply->state['date_started_timestamp'];
	$hours = intval($duration / 3600);
	$mins = intval(($duration % 3600) / 60);
	$secs = $duration % 60;
	$secs = ($secs < 10 ? "0$secs":$secs);
	$mins = ($mins < 10 ? "0$mins":$mins);
	$hours = ($hours < 10 ? "0$hours":$hours);
	$this->reply->state['duration'] = "$hours:$mins:$secs";
}


protected function modifyState($status = null, $output = null, $state = null)
{
	$this->opGetState();
	$this->setState($status, $output, $state);
}

protected function updateProgress($progress)
{
	$this->reply->state['progress'] = $progress;
	$this->setState();
}

protected function setState($status = null, $output = null, $state = null)
{
	if ((empty($status) && empty($output) && empty($state)) && !($status === null && $output === null && $state === null))
	{
		$this->debugLog("setState called with empty/no args");
		ZMC::quit();
		return;
	}
	$this->reply->state['mtime_timestamp'] = $time = time();
	$this->reply->state['mtime_human'] = $humanDate = ZMC::humanDate($time);
	if (!empty($state))
		array_unshift($this->reply->state['state'], $state);
	if ($this->aborting)
	{
		$msg = "Abort requested " . (time() - $this->aborting) . " seconds ago.";
		if (empty($status))
			$status = $msg;
		elseif (is_array($status))
			$status[] = $msg;
		else
			$status .= "\n$msg";
	}
	if (!empty($status))
	{
		if (is_array($status))
			$status = implode("\n* ", $status);
		array_unshift($this->reply->state['status'], substr($humanDate, 11) . " $status");
	}
	if (!empty($output))
	{
		if (is_array($output))
			$output = implode("\n", $output);
		if(!strpos($output, 'Client Error'))
			array_unshift($this->reply->state['output'], trim($output));
	}

	foreach(array('zmc_message', 'date', 'time') as $key)
		if (!empty($this->data[$key]))
			$this->reply->state[$key] = $this->data[$key];

	$this->reply->state['running'] = $this->isRunning();
	ksort($this->reply->state);
	
	if (file_put_contents($this->stateFile . '.new', '<? return ' . var_export($this->reply->state, true) . ';'))
		rename($this->stateFile . '.new', $this->stateFile);
	else
		$this->reply->addInternal(ZMC::getFilePermHelp($this->stateFile));
}

private function isRunning()
{
	if (!isset($this->reply->state) || empty($this->reply->state['pid']))
		return false;
	if ($this->reply->state['pid'] === $this->pid) 
		return true;
	if (file_exists($this->pidFile))
	{
		$pid = intval(file_get_contents($this->pidFile));
		if (!empty($pid) && file_exists("/proc/$pid"))
			return true;
	}
	return false;
}
public function microtime_float()
{
	return microtime(true);
}

public function time_diff($start_time = 0 , $end_time = 0){
	$total_time = number_format($end_time - $start_time, 2, '.', ''); 
	if( $total_time <= 0 )
		return;
	if($total_time > 3600){
		$hour = number_format($total_time / 3600, 2, '.', '');
		return "($min hours)";
	}elseif($total_time > 60){
		$min = number_format($total_time / 60, 2, '.', '');
		return "($min minutes)";
	}else{
		return "($total_time seconds)";
	}
	return;
}
}

class ZMC_Exception_JobAbort extends ZMC_Exception {}
