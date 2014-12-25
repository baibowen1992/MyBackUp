<?













class ZMC_Yasumi_Job_Restore extends ZMC_Yasumi_Job
{
private $ndmpRestore = false;
private $abort = false;
private $data_session_id = 0;
private $express = false;
private $packets = array();
private $nStreamSelect = 0;
private $nPollClient = 0;
const SOCKET_BLOCK_SIZE = 32768; 
const MAX_PAYLOAD_SIZE	= 32768;
const PACKET_HEADER_SIZE= 32;
const INFO_TYPE_MESSAGE	= 1;
const INFO_TYPE_STATUS	= 2;
const INFO_TYPE_QUERY	= 3;
const INFO_TYPE_RESTORE_LIST	= 4;
const INFO_TYPE_DATA	= 5;
const HDR_PKT_TYPE		= 0;
const HDR_FLAG			= 1;
const HDR_INFO_TYPE		= 2;
const HDR_SIZE			= 3;
const HDR_VERSION		= 4;
const HDR_PAYLOAD_SIZE	= 5;
const HDR_SESSION_ID	= 6;

public function __construct(ZMC_Yasumi $yasumi = null, $args = array(), ZMC_Registry_MessageBox $reply = null)
{
	parent::__construct($yasumi, $args, $reply);
	$this->jobType = 'Restore';
	$this->read_timeout = 120;
	$this->max_read_timeout = ZMC::$registry->proc_open_ultrashort_timeout * 36; 
}

protected function opStart()
{
	if (!$this->prepare(ZMC_Yasumi_Job::$notRunning, $waitForState = ZMC_Yasumi_Job::FINISHED))
		return;
	$this->express = ($this->job['restore_type'] === 'express'); 
	
	
	
	
	
	if (false !== stripos($this->job['zmc_type'], 'vmware'))
	{
		$this->vmware = true;
		if (!preg_match('/^\/\/(.+)\/(.+)$/', $this->job['disk_device'], $matches))
			if (!preg_match('/^\\\\\\\\(.+)\\\\(.+)$/', $this->job['disk_device'], $matches))
				throw new ZMC_Exception("Unrecognized disk device format: " . $this->job['disk_device']);
		if (0 === strpos($this->job['target_dir'], '//')) 
			$this->job['target_dir'] .= "/" . $matches[2] . "/" . $this->job['config'];
		elseif (0 === strpos($this->job['target_dir'], '\\\\'))
			$this->job['target_dir'] .= "\\" . $matches[2] . "\\" . $this->job['config'];
		
	}

	$this->job['task_id'] = time(); 
	try
	{
		if ($this->start())
			return; 

		if (!empty($this->vmware))
			$this->setState(null, "Target Location = {$this->job['target_dir']}\nESX Host = $matches[1]\nVirtual Machine Name = $matches[2]");

		if (false !== stripos($this->job['zmc_amanda_app'], 'ndmp')) 
		{
			$this->ndmpRestore = true;
			$this->fetchAll($this->attempt('NDMP Restore Process')); 
		}
		else
		{
			$this->setState('Connecting to ' . ($th = $this->job['target_host']));
			$host = $th;
			
			
			for ($sleep = 1; $sleep <= 8; sleep($sleep), $sleep *= 2)
			{
				if (false !== ($this->restoreClientSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
					if (false !== socket_connect($this->restoreClientSocket, $host, $this->job['target_port']))
						return $this->zmrecover();

				$this->setState(null, "Unable to connect to $th:{$this->job['target_port']} using the ZMC restore protocol. Will try again in $sleep seconds. " . $this->socketError());
			}
			throw new ZMC_Exception("Destination Host Connection Failure: $th not running the Amanda Enterprise Client (or network unreachable).");
		}
	}
	catch(Exception $e)
	{
		$this->debugLog("Client Polls: " . $this->nPollClient . '; AmfetchDump Polls: ' . $this->nStreamSelect);
		$this->exception = $e;
		switch(get_class($e))
		{
			case 'ZMC_Exception':
				$this->endState = self::FAILED;
				break;
			case 'ZMC_ProcOpen_Terminate':
			case 'ZMC_Exception_ProcOpen':
			case 'ZMC_Exception_YasumiFatal':
			case 'Exception':
			default:
				$this->endState = self::CRASHED;
				break;
		}
		if ($this->aborting)
			$this->endState = self::ABORTED;
	}
	if (is_resource($this->restoreClientSocket))
	{
		$arrOpt = array('l_onoff' => 1, 'l_linger' => 30);
		socket_set_block($this->restoreClientSocket);
		socket_set_option($this->restoreClientSocket, SOL_SOCKET, SO_LINGER, $arrOpt);
		socket_close($this->restoreClientSocket);
	}
	if($this->job['zmc_type'] === 'ndmp' && $this->job['remove_ndmp_credentials']){
		$host = $this->job['ndmp_filer_host_name'];
		$vol = $this->job['ndmp_volume_name'] . $this->job['ndmp_directory'];
		$username = $this->job['ndmp_username'];
		$password = '6G!dr' . base64_encode($this->job['ndmp_password']);
		$auth = $this->job['ndmp_filer_auth'];
		$line = "\"$host\" \"$vol\" \"$username\" \"$password\" $auth\n";
		$passfile_path = ZMC::$registry->etc_amanda . $this->job['config'] . "/ndmp_filer_shares";
		$passfile_content = file_get_contents($passfile_path);
		file_put_contents($passfile_path, str_replace($line, "", $passfile_content));
	}
}

protected function socketError()
{ return ('Problem reading reply from Destination Host Client: ' . socket_strerror(socket_last_error($this->restoreClientSocket))); }

protected function attempt($emsg)
{
	$this->debugLog("Attempting: $emsg");
	return $emsg;
}

private function zmrecover()
{
	$this->setState('Initializing Destination Host');
	$this->sendToRestoreClient($this->attempt('Send status request'), self::INFO_TYPE_QUERY, $this->attempt(ZMC_Restore::clientStatus($this->job, $this->data['user_id'], $this->data['username'], $this->debug ? '1':'0', $this->historyFileName)));
	$this->readFromRestoreClient($this->attempt('Is Destination Host "ready"?'), 'READY FOR REQUEST', $this->read_timeout);
	$this->sendToRestoreClient($this->attempt('Send Amanda server version to the Destination Host'), self::INFO_TYPE_MESSAGE, "<MSG>\nVERSION AMANDA SERVER " . ZMC::$registry->amanda_release . "\n</MSG>\n");
	$payload = $this->readFromRestoreClient($this->attempt('Get version of Destination Host'), 'VERSION AMANDA CLIENT', $this->read_timeout);
	if (!preg_match($expect = '/VERSION AMANDA CLIENT\s*(\S+)\s+INFO\s+OSTYPE\s*([^<]+)/i', $payload, $matches))
		if (!preg_match($expect = '/VERSION AMANDA CLIENT\s*([^\n]+)\s+INFO\s+OSTYPE\s*([^<]+)/i', $payload, $matches))
			throw new ZMC_Exception_YasumiFatal("Expecting '$expect'.  Server received unparsed packet: $payload");
	$distro = ZMC::distro2abbreviation($matches[2]);
        $matches_gci=str_replace("Zmanda", "ChinaUnicom",$matches);
	$this->setState(null, "ChinaUnicom Cloud Backup Version: $matches_gci[1]\nDestination Host OS: $distro");
	$found = "  Found $distro ($matches[1]).";
	$zwc = !strncasecmp($distro, 'windows', 7);
	if (!empty(ZMC_Type_AmandaApps::$dirTypes[$this->job['target_dir_selected_type']]['zwc_only']))
	{
		if (!$zwc)
			throw new ZMC_Exception("输入的目录类型是windows系统下的，但是选择的还原客户端却不是windows 类型 $found");
	}
	elseif ($zwc && !empty(ZMC_Type_AmandaApps::$dirTypes[$this->job['target_dir_selected_type']]['unix_only']))
		throw new ZMC_Exception("输入的目录类型不是windows系统下的，但是选择的还原客户端是windows 类型 $found");
	if (!$this->job['zwc'] && $zwc)
		throw new ZMC_Exception("这个备份镜像无法还原到windows客户端 ($found). 请尝试还原到 *nix 系统客户端。");
	$this->readFromRestoreClient($this->attempt('Check version compatibility of Destination Host'), 'SUCCESS VERSION-COMPATIBLE', $this->read_timeout);
	$this->sendToRestoreClient($this->attempt('Sending login information'), self::INFO_TYPE_MESSAGE, "<MSG>\nLOGIN USER {$this->job['user_name']}\n</MSG>\n");
	$this->readFromRestoreClient($this->attempt('Read login response'), 'SUCCESS USER-AUTHENTICATION', $this->read_timeout);
	
	$this->sendRestoreSessionInfo($this->attempt('Send Restore Session Information'));
	$this->sendLists($this->attempt('Send Restore/Exclude Lists'));
	$this->pollClient(__LINE__, 0);
	$this->fetchAll();
	$this->sendToRestoreClient($this->attempt('Send END-OF-RESTORE'), self::INFO_TYPE_MESSAGE, "<MSG>\nEND-OF-RESTORE {$this->job['task_id']}\n</MSG>\n");
	$this->readFromRestoreClient($this->attempt('Read end of restore reply'), 'SESSION-ID', $this->max_read_timeout);
	$this->debugLog("Client Polls: " . $this->nPollClient . '; AmfetchDump Polls: ' . $this->nStreamSelect);
}

private function zmrecover_quote($dir)
{ return '\\"' . $dir . '\\"'; } 

private function sendRestoreSessionInfo()
{
	$this->pollClient(__LINE__, 0);
	
	$payload = "<MSG>\nRESTORE-SESSION-INFO START\nFILE-COUNT "
		. ($this->express ? '-1' : max(1, $this->job['file_count'])) . "\n";
		
	if($this->job['zmc_type'] === 'windowsexchange') {
		$payload .= "RESTORE-APPLICATION msexchange\n";
		$point_in_time = $this->job['point_in_time'] ? '1' : '0';
		$payload .= "POINT-IN-TIME " . $point_in_time . "\n";
	}
	
	if($this->job['zmc_type'] === 'windowshyperv')
		$payload .= "RESTORE-APPLICATION mshyperv\n";
	
	if($this->job['zmc_type'] === 'windowssqlserver'){
		$payload .= "RESTORE-APPLICATION mssql\n";
		if($this->job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME && !empty($this->job['sql_alternate_name'])){
			$restore_alternate_path = "restore-to-alternate-path=";
			$restore_alternate_name = "restore-to-alternate-name=";
			foreach($this->job['sql_alternate_name'] as $db){
				$restore_alternate_path .= "\"{$db['original_path']}\":\"{$db['new_path']}\";";
				$restore_alternate_name .= "\"{$db['original_path']}\":\"{$db['new_name']}\";";
			}
			$payload .= $restore_alternate_path . "\n";
			$payload .= $restore_alternate_name . "\n";
		} elseif ($this->job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH && !empty($this->job['sql_alternate_path'])){
			$restore_alternate_path = "restore-to-alternate-path=";
			foreach($this->job['sql_alternate_path'] as $db)
				$restore_alternate_path .= "\"{$db['original_path']}\":\"{$db['new_path']}\";";
			$payload .= $restore_alternate_path . "\n";
		}
	}
	
	if(!strncasecmp($this->job['zmc_type'], 'windows', 7)){
		$payload .= "BACKUPSET_NAME " . $this->job['config'] . "\n";
		$payload .= "HOST_NAME " . $this->job['client'] . "\n";
		$payload .= "DISK_NAME " . $this->job['disk_name'] . "\n";
	}
		
	$payload .= "RESTORE-PATH "; 
	
	if (($this->job['zmc_type'] === 'windowsexchange') && !empty($this->job['exchange_db']))
		$payload .= "\nRECOVERY-DATABASE " . $this->zmrecover_quote($this->job['target_dir']) . "\n";
	else
		$payload .= $this->zmrecover_quote($this->job['target_dir']) . "\n";

	if (!empty($this->job['temp_dir']))
		$payload .= "TEMPORARY-RESTORE-LOCATION " . $this->zmrecover_quote($this->job['temp_dir']) . "\n";
	
	if(isset($this->job['encrypt-algo']))
		$payload .= "ENCRYPT-ALGO " . $this->job['encrypt-algo'] . "\n";

	$payload .= "POLICY {$this->job['conflict_dir_selected']} {$this->job['conflict_file_selected']}\n";
	if (!empty($raw) || ($this->job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_RAW_IMAGE))
		$payload .= "RAW-RESTORE {$this->job['extension']}\n";

	$payload .= "RESTORE-SESSION-INFO END\n</MSG>\n";
	$this->sendToRestoreClient($this->attempt("Send RESTORE-SESSION-INFO END: $payload"), self::INFO_TYPE_MESSAGE, $payload);
	$this->readFromRestoreClient($this->attempt('Receive RESTORE-SESSION-INFO acknowledgement'), 'READY FOR DATA', $this->read_timeout);
}

private function sendLists()
{
	$this->sendToRestoreClient($this->attempt('Starting send of include/exclude lists'), self::INFO_TYPE_MESSAGE, "<MSG>\nRESTORE-LIST START\n</MSG>\n");
	$listPrefix = "/etc/amanda/{$this->job['config']}/restore/";
	$lists = glob("$listPrefix/*list*");
	ksort($this->job['index_files']);
	foreach(array_merge(array_keys($this->job['index_files']), array('00000000000000_000.gz')) as $indexFileName)
		foreach(array('rlist', 'elist') as $listType)
		{
			$level = substr($indexFileName, strrpos($indexFileName, '_') +1, -3);
			$listFileName = $listPrefix . $listType . $level;
			if ($listType === 'elist')
			{
				if (!file_exists($listFileName))
					continue;
				$stat = stat($listFileName);
				if (!$stat || ($stat['size'] === 0))
					continue;
			}
			elseif($level === '000')
				continue;

			$this->sendList($this->attempt("Sending $listType: $listFileName for $indexFileName"), strtoupper($listType), $indexFileName, $listFileName);
			$this->pollClient(__LINE__, 0);
		}

	if (!$this->express)
		$this->sendList($this->attempt("Sending rbox"), 'RLIST', '99999999999999_0', $listPrefix . 'rbox');

	$this->sendToRestoreClient($this->attempt('Finishing send of include/exclude lists'), self::INFO_TYPE_MESSAGE, $payload = "<MSG>\nRESTORE-LIST END\n</MSG>\n");
}

private function sendList($emsg, $listType, $indexFileName, $listFileName)
{
	touch($listFileName); 
	if (false === ($fp = fopen($listFileName, 'r')))
	   throw new ZMC_Exception("$emsg\nUnable to open the include/exclude file list named '$listFileName': " . posix_strerror(posix_get_last_error()));

	$prefix = "<$listType>\n<INDEX_FILENAME>$indexFileName</INDEX_FILENAME>\n";
	$postfix = "\n</$listType>\n";
	$max = self::MAX_PAYLOAD_SIZE - 4096;
	do
	{
		$list = '';
		while((strlen($list) < $max) && !feof($fp) && (false !== ($line = stream_get_line($fp, 2048, "\n"))))
			if (strlen($line))
			{
				if (empty($list)) $list = "$prefix\n<FILENAMES>";
				$list .= $line . "\0"; 
			}
		if (empty($list))
		{
			if (!empty($once)) break;
			$list = $prefix . $postfix;
		}
		else
			$list .= "\n</FILENAMES>$postfix"; 

		$this->sendToRestoreClient($emsg, self::INFO_TYPE_RESTORE_LIST, $list);
		$once = true;
	} while(true);
	fclose($fp);
}

private function pollClient($line, $timeout = 0)
{
	static $last = null;
	if (($timeout === 0) && (time() === $last))
		return;
	$last = time();
	if (!is_resource($this->restoreClientSocket))
		throw new ZMC_Exception("Aborting, because Destination Host resource has gone away (#$line): " . $this->socketError());
	if (($code = socket_last_error($this->restoreClientSocket)) && ($code !== SOCKET_EAGAIN) && ($code !== SOCKET_EWOULDBLOCK))
		throw new ZMC_Exception("Aborting, because we lost communication to the Destination Host (#$line): " . $this->socketError());

	$gotPackets = $i = $errors = 0;
	$startTime = time();
	$timeToQuit = $startTime + $timeout;
	do
	{
		$bytes_read = $except = $write = null;
		$read = array($this->restoreClientSocket);
		$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read; BEFORE socket_select");
		
		$this->nPollClient++;
		if ($result = socket_select($read, $write, $except, $timeout, 10))
		{
			$bytes_read = socket_recv($this->restoreClientSocket, $tmp, 32, MSG_PEEK);
			$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read");
			
			if ($bytes_read === 32)
			{
				$this->queuePacketFromRestoreClient("#$line");
				$gotPackets++;
				$timeout = 0; 
				$timeToQuit = time() +1;
			}
		}
		elseif ($result === false)
		{
			$errors++;
			$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read; RETURN $gotPackets");
			if ($timeout === 0)
				return $gotPackets;
		}
		elseif ($timeout)
		{
			$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read; RETURN $gotPackets; result = " . print_r($read, true));
			if ($timeout === 0)
				return $gotPackets;
		}
		else
			$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read");
	} while (
		(time() < $timeToQuit) &&
		(!empty($bytes_read)) &&
		($gotPackets++ < 5)); 
	$this->debugLog(__FUNCTION__ . __LINE__ . ":$line i=$i; e=$errors; t=$timeout; gotPackets=$gotPackets; bytes_read=$bytes_read; RETURN $gotPackets");
	return $gotPackets;
}

private function &socketReadWrapper($expected_size)
{
	$i = $total_bytes_read = 0;
	$tmp = $buffer = '';

	while ($total_bytes_read < $expected_size)
	{
		$bytes_read = socket_recv($this->restoreClientSocket, $tmp, $expected_size - $total_bytes_read, MSG_DONTWAIT);
		if (empty($bytes_read))
			if (($code = socket_last_error($this->restoreClientSocket)) && ($code !== SOCKET_EAGAIN) && ($code !== SOCKET_EWOULDBLOCK))
				throw new ZMC_Exception('(read ' . __LINE__ . '): ' . $this->socketError());

		if ($bytes_read)
			$i = 0;
		elseif ($i++ > 100) 
			if ($i > 460)
				throw new ZMC_Exception("$emsg: " . __FUNCTION__ . "() read from socket failed (3 minute timeout)");
			else
				usleep(500000);

		$buffer .= $tmp;
		$total_bytes_read += $bytes_read;
	}
	return $buffer;
}

private function sendToRestoreClient($emsg, $infoType, $payload, $feof = false)
{
	static $id = 0;
	if (!empty($this->aborting))
		$payload = '';
	$flag = $feof ? 2:0;
	$header = array(
		'CCnnnNNNNNN', 
		7, 
		empty($this->aborting) ? $flag:1 , 
		$infoType, 
		32, 
		1, 
		strlen($payload), 
		$this->job['task_id'], 
		$id++, 
		0, 
		0, 
		empty($this->aborting) ? 0:$this->aborting); 

	if (empty($payload) && empty($this->aborting))
		throw new ZMC_Exception_YasumiFatal("$emsg: " .__FUNCTION__ . "() empty payload for $infoType");
	
	if (!empty(ZMC::$registry->raw_restore_log)) file_put_contents($this->historyFileName . '.out', $payload, FILE_APPEND);
	$pkt = call_user_func_array('pack', $header) . $payload;
	ZMC::socket_put_contents($this->restoreClientSocket, $pkt, self::SOCKET_BLOCK_SIZE, $emsg, $this->job['amclient_timelimit']);
	$this->pollClient(__LINE__, 0);
	if ($this->aborting)
		throw new ZMC_Exception("Abort request sent to Destination Host. ZMC server now aborting server restore process.");
}

private function queuePacketFromRestoreClient($emsg)
{
	$r = 0;
	$payload =& $this->socketReadWrapper(self::PACKET_HEADER_SIZE);
	$this->debugLog(__FUNCTION__ . __LINE__ . "; header=$payload");
	$header = array_combine(array(0,1,2,3,4,5,6,7,8,9,10), array_values(unpack("C2C/n3n/N6N", $payload)));
	if ($header[self::HDR_PKT_TYPE] != 7 || 
		$header[self::HDR_SIZE] != 32 ||
		$header[self::HDR_VERSION] != 1 ||
		$header[self::HDR_PAYLOAD_SIZE] > self::MAX_PAYLOAD_SIZE)
		throw new ZMC_Exception_YasumiFatal("$emsg: Corrupt Header Received: PKT_TYPE : " . 
		print_r($header, true) 
			. quoted_printable_encode($payload . stream_get_contents($this->restoreClientSocket, self::MAX_PAYLOAD_SIZE)));

	if ($header[self::HDR_SESSION_ID] != $this->job['task_id'])
		throw new ZMC_Exception_YasumiFatal("$emsg: Packet session id ({$header[self::HDR_SESSION_ID]}) does not match task id ({$this->job['task_id']})!");

	unset($payload); 
	$payload =& $this->socketReadWrapper($header[self::HDR_PAYLOAD_SIZE]);
	if (stripos($payload, 'unexpected packet'))
		$this->setState("Destination Host did not understand part of the restore request (server $expecting; host sent $payload).");

	$this->debugLog(__FUNCTION__ . __LINE__ . "; payload=$payload");
	if (!empty(ZMC::$registry->raw_restore_log)) file_put_contents($this->historyFileName . '.in', "HEADER:\n" . print_r($header, true) . "\n\n" . $payload, FILE_APPEND);
	
	{
		$messages = array();
		foreach(array('ERROR_MESSAGE' => 'Client Error', 'WARNING_MESSAGE' => 'Client Warning', 'INFO_MESSAGE' => 'Client') as $tag => $key)
			if (false === ($i = stripos($payload, "<$tag>")))
				continue;
			else
			{
				$i += strlen($tag) + 2;
				if ($i < ($pos = stripos($payload, "</$tag>", $i)))
					if (strlen($status = trim(substr($payload, $i, $pos - $i))))
					{
						$messages[$key] = $key . ': ' . $status;
						if (false !== stripos($status, 'FAILURE'))
							$err = $messages[$key];
					}
			}

		if ($this->debug && !strncasecmp($payload, '<STATUS>', 8))
			if ($pos = stripos($payload, 'INFO '))
				if (strlen($status = trim(str_replace(array('<MSG>', '</MSG>', "\n"), array('', '', ' '), substr($payload, 8, $pos - 8)))))
					$messages['Client Status'] = "Client Status: $status";

		if (!empty($messages)) $this->setState($messages);
	}

	if (!empty($err))
		throw new ZMC_Exception("$emsg: $err");

	if (false !== stripos($payload, "<STATUS>\nHEARTBEAT"))
	{
		$beat = intval(substr($payload, 21, 5));
		$this->debugLog($payload);
		$this->sendToRestoreClient($msg = $this->attempt("HEARTBEAT ACK $beat"), self::INFO_TYPE_MESSAGE, $msg);
		return; 
	}
	$this->packets[] =& $payload;
}

private function comparePacket($emsg, $expecting)
{
	while(!empty($this->packets))
	{
		$this->last_packet_received = array_shift($this->packets);
		if (($expecting !== self::INFO_TYPE_STATUS) && (false === strpos($this->last_packet_received, $expecting)))
		{
			$this->debugLog("Skipping packet, because it does not match: $expecting;\n" . $this->last_packet_received);
			continue;
		}
		return $this->last_packet_received;
	}
	return false;
}

private function readFromRestoreClient($emsg, $expecting, $timeout)
{
	$gotPackets = true;
	while($gotPackets)
		foreach(array(0, $timeout) as $t)
		{
			$gotPackets = $this->pollClient(__LINE__, $t);
			if (false !== ($payload = $this->comparePacket($emsg, $expecting)))
				return $payload;
		}

	if ($timeout)
		throw new ZMC_Exception_YasumiFatal("Destination Host did not send $expecting within timeout ($timeout seconds). Last packet received: " . $this->last_packet_received);
}

private function fetchAll()
{
	$this->setState($this->attempt('Fetch all backup image(s).'));
	$this->total_bytes_sent = 0;

	ksort($this->job['index_files']); 

	$vaultMediaList = array();
	$vaultNamePattern = '/^' . $this->job['config'] . '-.+' . '-vault-[0-9][0-9][0-9]$/';
	foreach($this->job['media_needed'] as $tape){
		if(preg_match($vaultNamePattern, $tape['tape_label'])){
			$components = explode('-', $tape['tape_label']);
			$changerName = $components[1];
			$vaultMediaList[$tape['index']] = $changerName;
		}
	}
		
	foreach($this->job['index_files'] as $indexFileName => $localFlag)
	{
		list($this->timestring, $this->level) = explode('_', $indexFileName);
		$this->level = intval($this->level);
		$amfetchdump = "/usr/sbin/amfetchdump";
		$args = array_filter(array_merge(explode(',', $localFlag), array("-hp", $this->job['config'], '^' . $this->job['client'] . '$', '^' . $this->job['disk_name'] . '$', $this->timestring)));
		if ($this->ndmpRestore)
		{
			if(empty($this->job['target_dir'])) 
				$this->job['target_dir'] = $this->job['disk_name'];
			
			if($this->express){ 
				$args = array('--extract', '--directory', $this->job['target_dir'], $this->job['config'], '^' . $this->job['client'] . '$', '^' . $this->job['disk_name'] . '$', $this->timestring);
			} else {
				$rlistFile = "/etc/amanda/" . $this->job['config'] . "/restore/rlist" . $this->level;
				$args = array('--extract', '--directory', $this->job['target_dir'], '--application-property', 'include-list=' . $rlistFile, $this->job['config'], '^' . $this->job['client'] . '$', '^' . $this->job['disk_name'] . '$', $this->timestring);
			}
		}
		
		if(array_key_exists($indexFileName, $vaultMediaList)){
			$args[] = '-o';
			$args[] = 'tpchanger=' . $vaultMediaList[$indexFileName];
		}

		$this->setState('Scanning L' . $this->level . ' backup image dated ' . ($this->human_date = ZMC::humanDate(ZMC::mktime($this->timestring))));
		$this->debugLog(__FUNCTION__ . __LINE__ . ' ' . $amfetchdump . ' ' . implode(" ", $args));
		$command = ZMC_ProcOpen::procOpen($cmd = 'amfetchdump',  $amfetchdump, $args,
			$stdout, $stderr, "$cmd command failed unexpectedly", null,
			'/var/tmp', ZMC::$registry->proc_open_short_timeout, array($this, 'amfetchOneImage'));
		if ($this->debug) $this->setState("Retrieve backup image using command:\n$command");
		list($display_bytes, $display_unit) = $this->getDisplayInfo($this->total_bytes_sent);
		$this->updateProgress($this->state['progress'] = "ChinaUnicom Cloud Backup client scanned a total of $display_bytes $display_unit from " . ((count($this->job['index_files']) > 1) ? 'all backup images.' : 'the backup image.'));
		if (!$this->ndmpRestore)
		{
			$this->sendToRestoreClient($this->attempt("Sending $indexFileName RESTORE-DATA-SESSION END"), self::INFO_TYPE_MESSAGE,
				"<MSG>\nRESTORE-DATA-SESSION END {$this->job_session_id}\n</MSG>", true);
			$this->readFromRestoreClient($this->attempt("Read reply: Received and extracted $indexFileName"), 'RESTORE-DATA-SESSION', $this->read_timeout * 10);
			$this->pollClient(__LINE__, 0);
		}
	}
}

public function amfetchOneImage($streams, &$stdout, &$stderr, $tv_sec) 
{
	stream_set_read_buffer($streams['stderr'], 0);
	$this->job_session_id++;
	$header = true;
	$amfetch_bytes = 0;
	$amfetch_buffer = '';
	$more = array($streams['stdout'], $streams['stderr']);

	try
	{
		while (!empty($more))
		{
			$i=0;
			$count = true;
			$errors = 0;
			$reportInterval = 5;
			$lastReport = $startTime = time();
			$timeToQuit = $startTime + $this->job['amclient_timelimit'];
			do
			{
				$i++;
				if ($count === false)
					$errors++;
				if ($count === false || $count === 0)
				{
					if (0===($i % 10)) $this->debugLog("Waited $waited seconds for storage device to begin retrieving data ($i;$errors).");
					if (time() > ($lastReport + $reportInterval))
					{
						$lastReport = time();
						$waited = time() - $startTime;
						$this->setState("Waited $waited seconds for storage device to begin retrieving data ($i;$errors).");
						if (($reportInterval < 300) && ($waited > 60))
							$reportInterval += 5;
					}
				}
				$read = $more;
				$except = $write = null;
				
				if (!$this->ndmpRestore) $this->pollClient(__LINE__, 0);
				$this->nStreamSelect++;
				if (false === ($count = stream_select($read, $write, $except, 5, 10)))
					throw new ZMC_Exception_Amfetchdump("Restore failed while trying to get backup image from Amanda storage device (code #" . __LINE__. ')');
			}while(empty($count) && time() < $timeToQuit); 

			if (($count > 0) && ($reportInterval !== 5))
				$this->setState("Now receiving data. Waited " . (time() - $startTime) . " total seconds ($i;$errors).");
			if (!$this->ndmpRestore) $this->pollClient(__LINE__, 0);
			if ($i > 1) $this->debugLog("Exit stream_select() with loops=$i; count=$count; elapsed=" . (time()- $startTime));
			if ($count === false)
				throw new ZMC_Exception_Amfetchdump("Restore failed while trying to get backup image from Amanda storage device (code #" . __LINE__. ')');
			if ($count === 0)
				throw new ZMC_Exception_Amfetchdump("No results received when trying to fetch Amanda backup image, after waiting " . ($i * ZMC::$registry->proc_open_short_timeout) . ' seconds. Restore aborted.');

			foreach($read as $stream)
			{
				if ($stream === $streams['stdout'])
				{
					$this->copyFromAmfetch2Client($stream, $amfetch_buffer, $amfetch_bytes, $header);
				}
				elseif ($stream === $streams['stderr'])
				{
					if ($action = $this->handleAmfetchError(fgets($stream, 4096), $amfetch_bytes))
						fputs($streams['stdin'], $action);
					
				}
				else $this->debugLog(__LINE__ . " ERROR: unknown $stream in \$read");

				if (feof($stream))
				{
					$more = array_diff($more, array($stream));
					$this->debugLog(__LINE__ . " found feof for " . array_search($stream, $streams, true));
				}
			}
		}
	} catch (Exception $e)
	{ throw new ZMC_ProcOpen_Terminate($e->getMessage(), $e->getCode(), $e); }
	$this->total_bytes_sent += $amfetch_bytes;
}

private function copyFromAmfetch2Client($stream, &$buffer, &$total_bytes_read, &$header)
{
	static $prev_reported = 0;
	if ($total_bytes_read === 0)
		$prev_reported = 0;

	if (false === ($data = fread($stream, self::MAX_PAYLOAD_SIZE - strlen($buffer))))
		throw new ZMC_Exception_Amfetchdump('Read failure: ' . posix_strerror(posix_get_last_error()));
	
	if ($this->ndmpRestore) 
		return;

	$buffer .= $data;
	if (0 === ($buflen = strlen($buffer)))
		return;

	if (!feof($stream) && ($buflen < self::MAX_PAYLOAD_SIZE))
		return; 

	static $emsg = 'Copy amfetchdump data to Destination Host';
	if (!$header)
		$this->sendToRestoreClient($emsg, self::INFO_TYPE_DATA, $buffer);
	else
	{
		$buffer = str_replace("\0", '', $buffer);
		$this->sendToRestoreClient($this->attempt("RESTORE-DATA-SESSION START"), self::INFO_TYPE_MESSAGE,
			"<MSG>\nRESTORE-DATA-SESSION START {$this->job_session_id}\n</MSG>\n");
		$this->sendToRestoreClient($this->attempt("RESTORE-FILE-INFO START + END"), self::INFO_TYPE_MESSAGE, 
			"<MSG>\nRESTORE-FILE-INFO START {$this->job_session_id}\n$buffer\nRESTORE-FILE-INFO END\n</MSG>\n");
		$header = false;
	}

	$buffer = '';
	$total_bytes_read += $buflen;
	if ($prev_reported < ($total_bytes_read - 1048576))
	{
		$prev_reported = $total_bytes_read;
		list($display_bytes, $display_unit) = $this->getDisplayInfo($total_bytes_read);
		$this->updateProgress("ChinaUnicom Cloud Backup client scanned $display_bytes $display_unit of L" . $this->level . ' backup image');
	}
}

private function handleAmfetchError($line, &$amfetch_bytes)
{
	static $emptyLines = 0;
	static $reserved = 0;
	$info = $tapelist = array();
	$insert_count = 2;
	$line = trim($line);
	if (empty($line))
	{
		$this->debugLog("got empty line from amfetchdump STDERR");
		if ($emptyLines++ > 25)
			throw new ZMC_Exception_Amfetchdump("Too many errors processing output of amfetchdump. Aborting.");
		return;
	}

	if (strpos($line, ' is reserved'))
	{
		if ($reserved++ > 60)
			throw new ZMC_Exception_Amfetchdump('Media is "reserved", and possibly locked or currently in use by Amanda.  Please wait some time, and try again.');
		$this->setState($line);
		sleep(5); 
		return;
	}
	if (strpos($line, ' is deprecated.'))
		return;
	if (strpos($line, ' volumes are needed:')) 
		return $this->setState($line);
	$this->debugLog(__FUNCTION__ . __LINE__ . "() - line=$line;");
	if (false !== stripos($line, 'Press enter'))
		return "\n";
	elseif (false !== stripos($line, 'assword:')) 
		return $this->job['password'] . "\r\n";
	elseif (false !== stripos($line, 'Permission denied'))
		throw new ZMC_Exception_Amfetchdump("Invalid Password for {$this->job['user_name']}\@{$this->job['target_host']}");
	elseif ((false !== stripos($line, 'does not match expectations')) || (false !== stripos($line, 'No matching dumps')))
		throw new ZMC_Exception_Amfetchdump("No matching backup image: $line");
	elseif (false !== ($pos = stripos($line, 'Insert \s+ into slot ')))
	{
		
		if ($insert_count != 2)
			$insert_count++;
		else
		{
			$tapelist = array();
			$line2 = trim(substr($line, $pos));
			$userPrompt = "Incorrect backup media.  Please insert/mount backup media ";
			if (empty($line2))
				throw new ZMC_Exception_Amfetchdump("Could not get media label and slot number");
			else
				$userPrompt .= " at slot " . ZMC::filterDigits($line2);
			$this->setState('Need User Input', $userPrompt);
			$signal = pcntl_sigtimedwait(array(SIGUSR1), $info, $this->job['user_input_timeout']);
			$this->debugLog("Got signal $signal, while waiting for $userPrompt");
			$insert_count = 0;
		}
		return "\n";
	}
	elseif (preg_match('/could not load slot (\d+):Drive not ready/i', $line, $matches))
		throw new ZMC_Exception_Amfetchdump("Slot $matches[1] not ready");
	elseif (preg_match('/Insert \S+ labeled (.*) in device (.*)/i', $line, $matches))
	{
		$tapelist = array();
		if (!empty($matches[1]))
		{
			array_push($tapelist, $matches[1]);
			$this->setState('Need User Input', "Please make backup medium labeled $tapes[0] available at $matches[2]");
			$signal = pcntl_sigtimedwait(array(SIGUSR1), $info, $this->job['user_input_timeout']);
			$this->debugLog("Got signal $signal, while waiting for $userPrompt");
			return "\n";
		}
	}
	elseif (false !== stripos($line, 'unexpected end of file'))
		throw new ZMC_Exception_Amfetchdump("Error uncompressing the backup data image");
	elseif (preg_match('/could not exec\s*(.*)/i', $line, $matches))
		throw new ZMC_Exception_Amfetchdump("Error running tool: $matches[1]");
	elseif (preg_match('/amfetch(.*)restor\w+(.*)date\s+(\d+)/i', $line, $matches))
		$this->setState("Restoring from backup image dated: " . ($this->restore_image_date = ZMC::humanDate(ZMC::mktime($matches[3]))));
	elseif (
			(false !== stripos($line, 'gpg: no valid OpenPGP data found'))
		||	(preg_match('/secret key\s.*\snot found/', $line))
		||	(preg_match('/Insert \S+ labeled/i', $line))
		||	(preg_match('/perl:\s+/i', $line)) 
		||	(preg_match('/amfetch\w+:\s+/i', $line)) 
		||	(false !== stripos($line, ' busy')) 
		||	(false !== stripos($line, 'already running'))
		||	(false !== stripos($line, 'header not found'))
		||	(false !== stripos($line, 'I/O Error'))
		||	(0 === stripos($line, 'ERROR')))
		throw new ZMC_Exception_Amfetchdump($line);
	elseif (strpos($line, 'volume(s)') < strpos($line, 'needed for restoration'))
		return; 
	elseif (!strncmp('READ SIZE:', $line, 10))
	{
		if (empty($this->last_read_size) || ($this->last_read_size !== $line))
		{
			$this->last_read_size = $line;
			$this->setState('received ' . substr($line, 11) . ' of the backup image');
			if($this->job['zmc_type'] === 'ndmp')
				$amfetch_bytes = 1024 * intval(str_replace(' kb', '', substr($line, 11)));
		}
	}
	elseif(preg_match("/err connect-auth-(.*)-failed/",$line, $matches)){
		$this->setState($auth_err_msg = "Connection refused. Authentication method '". $matches[1]."' not recognized.");
		throw new ZMC_Exception_Amfetchdump($auth_err_msg);
	}elseif(preg_match("/Operation ended OKAY/", $line) || preg_match("/Application stdout/", $line)){
		if ($this->debug){
			$this->setState("Unrecognized amfetch output: $line");
		}
		$this->debugLog("Unrecognized amfetch output: $line");
		return;
	}
	else{
		if(preg_match("/stderr|error|failed|fail|crashed/i", $line)){
			$this->setState("Unrecognized amfetch output: $line");
		}else{
			$this->debugLog("Unrecognized amfetch output: $line");
			if ($this->debug)
				$this->setState("Unrecognized amfetch output: $line");
		}

	}
}

private function getDisplayInfo($bytes)
{
	if ($bytes > 1073741824.0)	return array(round($bytes/1073741824.0, 1), "GiB");
	if ($bytes > 1048576.0)		return array(round($bytes/1048576.0, 1), "MiB");
	if ($bytes > 1024.0)		return array(round($bytes/1024.0, 1), "KiB");
	return array($bytes, "Bytes");
}
}

class ZMC_Exception_Amfetchdump extends ZMC_Exception
{}
