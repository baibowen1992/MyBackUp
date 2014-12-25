<?













class ZMC_Yasumi_Label extends ZMC_Yasumi
{
	CONST LOCK_FILE = '.lock_progress'; 

	CONST ABORT_LABEL = '.abort_label';

	CONST LABEL_STATUS = '.label_status';

	CONST VERIFY_TAPE_DRIVE_STATUS = '.verify_tape_drive_status';
	
	CONST VERIFY_TAPE_DRIVE_LOCK_FILE = '.verify_tape_drive_lock';

	CONST SCAN_SLOTS_STATUS = '.scan_slots_status'; 

	static private $labelStatus = array();
	private $singleTapeDevice = false;
	private $multipleTapeDevice = false;
	private $vtape1VolumeDevice = false;

	protected function init()
	{
		parent::init();
		$this->standardFieldCheck(array('binding_name'));
		$results = $this->command(array('pathInfo' => "/Device-Binding/defaults/" . $this->getAmandaConfName(), 'data' => $this->data, 'post' => null, 'postData' => null));
		$this->bindings = $results->binding_conf; 
		$this->singleTapeDevice   = ($this->bindings['dev_meta']['device_type'] === ZMC_Type_Devices::TYPE_SINGLE_TAPE);
		$this->multipleTapeDevice = ($this->bindings['dev_meta']['device_type'] === ZMC_Type_Devices::TYPE_MULTIPLE_TAPE);
		$this->vtape1VolumeDevice = ($this->bindings['dev_meta']['device_type'] === ZMC_Type_Devices::TYPE_ATTACHED);
		$this->vtape1VolumeDevice |= ($this->bindings['dev_meta']['device_type'] === ZMC_Type_Devices::TYPE_CLOUD);
		$this->bindingPath = $this->getAmandaConfPath(true) . 'binding-' . $this->data['binding_name'];
		$this->lockFile = $this->bindingPath . self::LOCK_FILE;
		$this->labelFile = $this->bindingPath . self::LABEL_STATUS;
		$this->tapeDriveFile = $this->bindingPath . self::VERIFY_TAPE_DRIVE_STATUS;
		$this->verifyTapeDriveLockFile =  $this->bindingPath . self::VERIFY_TAPE_DRIVE_LOCK_FILE;
		$this->i = 0;
	}

	protected function isBusy($includeLabelStatus = true)
	{
		if (file_exists($this->lockFile))
		{
			list($date, $pid, $percent) = file($this->lockFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
			if (!empty($pid) && file_exists("/proc/$pid"))
				return true; 
			$this->reply->addWarnError("Found lock file $this->lockFile, but no associated process.  Removed lock file."); 
			unlink($this->lockFile);
		}
		if ($includeLabelStatus)
			$this->cleanupLabelFile();
		return false;
	}

	protected function busy($percent)
	{
		file_put_contents($this->lockFile, ZMC::humanDate(false) . "\n" . $this->pid . "\n$percent\n");
		if (ZMC::$registry->dev_only) file_put_contents('/tmp/zmc' . self::LOCK_FILE . $this->i++, ZMC::humanDate(false) . "\n" . $this->pid . "\n$percent\n");
	}

	protected function opAbortLabeling()
	{
		if ($this->isBusy())
		{
			$this->reply->addMessage("Abort pending ...");
			file_put_contents($this->bindingPath . self::ABORT_LABEL, 'Abort requested: ' . ZMC::humanDate(true));
			return;
		}

		$this->cleanupLabelFile();
	}

	protected function cleanupLabelFile()
	{
		if (!file_exists($this->labelFile))
			return;

		require $this->labelFile;
		foreach(array_keys(self::$labelStatus) as $slot)
			if (self::$labelStatus[$slot]['result'] === 'progress')
				unset(self::$labelStatus[$slot]);

		if (empty(self::$labelStatus))
			unlink($this->labelFile);
		else
			$this->writeLabelFile();
	}

	private function writeLabelFile()
	{
		if (false === file_put_contents($this->labelFile, '<? ZMC_Yasumi_Label::$labelStatus = ' . var_export(self::$labelStatus, true) . ';'))
			throw new ZMC_Exception_YasumiFatal($this->reply->addError(ZMC::getFilePermHelp($this->labelFile)));
	}


	protected function opVerifyTapeDrive(){
		if ($this->multipleTapeDevice)
		{
			if(file_exists($this->verifyTapeDriveLockFile)){
				list($date, $pid) = file($this->verifyTapeDriveLockFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
				if (empty($pid) && !file_exists("/proc/$pid"))
					$this->reply->addWarnError("Found lock file $this->verifyTapeDriveLockFile, but no associated process.  Removed lock file."); 
					unlink($this->verifyTapeDriveLockFile);
			}

			if ($this->isBusy()){
				$this->reply->addWarnError("Another ZMC task is using this changer. " . ucFirst($this->operation) . " aborted.  Please try again later.");
				if (ZMC::$registry->safe_mode) return;
			}

			if (!$this->synchronousBegin(true)) 
				return;
			$this->busy(0);
			file_put_contents($this->verifyTapeDriveLockFile,  ZMC::humanDate(false) . "\n" . $this->pid);
			if (ZMC::$registry->dev_only) file_put_contents('/tmp/zmc' . self::VERIFY_TAPE_DRIVE_LOCK_FILE . $this->i++, ZMC::humanDate(false) . "\n" . $this->pid);

			try{
				if(file_exists($this->tapeDriveFile))
					unlink($this->tapeDriveFile);
				$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($this->getAmandaConfName(), 'verify'),
					$stdout, $stderr, "'$cmd verify' command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());
			}
			catch(ZMC_Exception_ProcOpen $e){
				throw new ZMC_Exception_YasumiFatal($this->reply->addError("$command failed unexpectedly (code #2)"));
			}

			
			
			
			$this->reply['stdout'] = $stdout;
			$this->reply['stderr'] = $stderr;
			$this->reply['command'] = $command;
			$out = $err = array();
			if (!empty($stdout) || !empty($stderr)){
				$out = explode("\n", $stdout);
				$err = explode("\n", $stderr);
				$lines = array_merge($out, $err);

				$selected_tape_drive = $this->data['tapedev'];

				foreach($selected_tape_drive as $tape => $drive){
					$tape = trim($tape, ' \'"'); 
					if($drive == "skip")
						continue;
					$final[$tape] = array('good' => '', 'error' => '', 'hint' => '', 'suggestion' => '');
					foreach($lines as $output){
						if(!empty($output)){
							if(preg_match("/^ERROR(.*)?Drive\s+$drive(.*)?/i", $output)){
								$final[$tape]['error'] = $output;
							}
							if(preg_match("/^HINT(.*)?Drive\s+$drive(.*)?/i", $output)){
								$final[$tape]['hint'] = $output;
							}
							if(preg_match("/^GOOD(.*)?Drive\s+$drive(.*)?/i", $output)){
								$final[$tape]['good'] = $output;
							}
							if(preg_match("/^property(.*)?TAPE-DEVICE(.*)?/i", $output)){
								$final[$tape]['suggestion'] = $output;
							}
							$final[$tape]['current_drive'] = $drive;
						}
					}
				}
				$result = array($this->data['binding_name'] => $final);
				$result['last_verified'] = ZMC::humanDate(true);

				if (false === file_put_contents($this->tapeDriveFile,'<?php return '. var_export($result, true).'; ?>'))
					throw new ZMC_Exception_YasumiFatal($this->reply->addError(ZMC::getFilePermHelp($this->tapeDriveFile)));
					
			}

			unlink($this->verifyTapeDriveLockFile);
			unlink($this->lockFile);
		}

	}	

	





	protected function opScanSlots()
	{
		if ($this->multipleTapeDevice)
		{
			if ($this->isBusy())
			{
				$this->reply->addWarnError("Another ZMC task is using this changer. " . ucFirst($this->operation) . " aborted.  Please try again later.");
				if (ZMC::$registry->safe_mode) return;
			}
			$currentSlot = $this->getCurrentSlot();
			$rawYamlBinding = $this->loadYaml($this->bindingPath . '.yml');
			$this->slots = $rawYamlBinding['changer']['slots'];
			if (!$this->synchronousBegin(true)) 
				return; 
	
			$this->busy(0);
			try
			{
				$command = ZMC_ProcOpen::procOpen(
					$cmd = 'amtape',
					ZMC::getAmandaCmd($cmd),
					array($this->getAmandaConfName(), 'update', $rawYamlBinding['changer']['slotrange']),
					$stdout,
					$stderr,
					"$cmd command failed unexpectedly",
					$this->getLogInfo(),
					$this->getAmandaConfPath(),
					ZMC::$registry->proc_open_short_timeout,
					array($this, 'parseAmtapeOutput')
				);
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->reply->addInternal($e->getStdout() . "; " . $e->getStderr());
			}
			self::resetSlot($currentSlot);
			unlink($this->lockFile); 
			if (false === (file_put_contents($fn = $this->bindingPath . self::SCAN_SLOTS_STATUS, $this->getFilteredReplyData())))
				throw new ZMC_Exception_YasumiFatal($this->reply->addError(ZMC::getFilePermHelp($fn)));
		}
		$this->opInventory();
	}

	public function opListMountedVtapes() 
	{
		
		try
		{
			
			if (empty($this->reply->state))
				$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($this->amanda_configuration_name, 'show'),
					$stdout, $stderr, "$cmd command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());

			$this->reply->mounted_media_list = array();
			if (!empty($this->reply->state))
				foreach($this->reply->state['slots'] as $slotId => $slotInfo)
				{
					$parts = explode('-', $slotId);
					$numParts = count($parts);
					$cn = substr($parts[$numParts-2], strrpos($parts[$numParts-2], '/')+1);
					if ($cn !== $this->amanda_configuration_name)
						continue;
					$slot = substr($parts[count($parts)-1], 4);
					$this->reply->mounted_media_list[$slot] = array('slot' => $slot, 'last_used' => 'Unknown-1', 'label' => $slotInfo['label']);
					if (!empty($slotInfo['device_error']))
						$this->reply->addWarnError("Slot $slot reports: " . $slotInfo['device_error']);
				}
			elseif(!empty($stderr))
			{
				$lines = explode("\n", $stderr);
				foreach($lines as &$line)
					if (!strncmp($line, 'slot', 4))
					{
						strtok($line, " \t:"); 
						$slot = strtok(" \t:");
						$field3 = strtok(" \t:"); 
						if ($field3 === 'unlabeled')
							continue;
						if (strpos($line, 'header not found'))
						{
							$date = '';
							$label = 'unlabeled';
						}
						else
						{
							$date = strtok(" \t:");
							strtok(" \t:"); 
							$label = strtok(" \t:");
						}
						$this->reply->mounted_media_list[$slot] = array('slot' => $slot, 'last_used' => $date, 'label' => $label, 'volume' => 'Z');
					}
			}
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->reply->addInternal($e->getStdout() . "; " . $e->getStderr());
		}
	}

	public function parseAmtapeOutput($outStream , $inStream , $errStream , &$stdout , &$stderr , $tv_sec)
	{
		fclose($outStream); 
		$completed = -1;
		$err = '';
		while(!feof($errStream) || !feof($inStream))
		{
			$output = '';
			$write = null;
			$except = null;
			$read = array($inStream, $errStream);
			$count = stream_select($read, $write, $except, $tv_sec);
			if ($count === false || $count === 0)
			{
				$this->reply->addWarnError("Tape scan failed (code #" . __LINE__ . ");" . ($count === false ? ': failure' : ": timeout ($tv_sec seconds)"));
				error_log(__LINE__ . 'break');
				break;
			}
			if (!feof($errStream))
				$output .= fgets($errStream, 2048);
			if (!feof($inStream)) 
				$output .= fgets($inStream, 2048);
			if (empty($output))
				continue;

			foreach(explode("\n", $output) as $line)
			{
				$parts = explode(' ', $line);
				if (strpos($line, 'complete'))
					$this->busy(100);
				elseif ($parts[0] !== 'scanning')
					$err .= $line;
				else
				{
					$completed++;
					$percent = round($completed * 100 / $this->slots);
					$this->busy($percent);
				}
			}
		}
		if (!empty($err))
		{
			$this->reply->addWarnError($err); 
			error_log(__FILE__ . __LINE__ . $err);
		}
	}

	protected function opInventory($includeLabelStatus = true)
	{
		$this->reply->state =& ZMC::perl2php($this->bindings['changerfile'], 'STATE');
		if ($includeLabelStatus)
		{
			if (file_exists($this->labelFile))
				require $this->labelFile;

			$this->reply['label_status'] = self::$labelStatus;
		}

		if ($this->vtape1VolumeDevice)
			ZMC::quit();  

		$this->inventoryChanger();
		if (!$this->singleTapeDevice)
			return false; 

		try
		{
			$command = ZMC_ProcOpen::procOpen($cmd = 'amdevcheck', ZMC::getAmandaCmd($cmd), array('--label', $this->getAmandaConfName()),
				$stdout, $stderr, "$cmd command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());
			



			$this->reply['slots2labels'] = array(1 => "$stdout$stderr");
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$out = "";
			$error = "";
			try
			{
				$command = ZMC_ProcOpen::procOpen($cmd = 'amdevcheck', ZMC::getAmandaCmd($cmd), array($this->getAmandaConfName()), $out, $error);
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Internal error. Unable to check tape drive status using amdevcheck.~$e"));
			}
			$matches = array();
			preg_match("/^MESSAGE\h([\\s|\\S]*)$/", $out.$error, $matches);
			$this->debugLog("amdevcheck --label " . $this->getAmandaConfName() . ': '.$matches[1]);
			$this->reply['slots2labels'] = array(1 => "Drive Status: $matches[1]"); 
		}
	}

	protected function inventoryChanger()
	{
		$slots2labels = $slots2barcodes = array();
		if (!empty($this->reply->state['slots']))
			foreach($this->reply->state['slots'] as $i => $slot)
			{
				$slots2labels[$i] = $slot['label'];
				$slots2barcodes[$i] = $slot['barcode'];
			}
		$this->reply->slots2labels =& $slots2labels;
		$this->reply->slots2barcodes =& $slots2barcodes;
		return;

		$this->reply['slots2barcodes'] = $this->getBarcodesFromMtx();
		$this->reply['slots2labels'] = array();
		try
		{
			$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($this->getAmandaConfName(), 'inventory'),
				$stdout, $stderr, "'$cmd inventory' command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			throw new ZMC_Exception_YasumiFatal($this->reply->addError("$command failed unexpectedly (code #2)"));
		}

		$lines = explode("\n", $stdout);
		foreach($lines as $line)
		{
			if (empty($line) || strncmp($line, 'slot', 4))
				continue;
			$parts = explode(' ', $line);
			if ($parts[2] === 'unknown')
				continue;
			if ($parts[2] !== 'label')
				throw new ZMC_Exception_YasumiFatal($this->reply->addError("$command output parse failure at: $line"));
			$this->reply['slots2labels'][intval($parts[1])] = $parts[3];
		}
		return;
	}

	protected function getBarcodesFromMtx()
	{
		$barcodes = array();
		if (!$this->multipleTapeDevice || !$this->data['barcodes_enabled'])
			return array();

		$args = array('-info');
		try
		{
			ZMC_ProcOpen::procOpen('mtx', $cmd = ZMC::$registry->getAmandaConstant('MTX'), array('-f', $this->bindings['changer']['changerdev'], 'status'),
				$stdout, $stderr, "$cmd command failed unexpectedly", $this->getLogInfo());
			












			$lines = explode("\n", $stdout);
			foreach($lines as $line)
			{
				if (empty($line) || (false === ($pos = strpos($line, 'Storage Element'))))
					continue;

				if (false === strpos($line, 'mpty', $pos))
				{
					if (preg_match('/Storage Element\s+([0-9]+).*VolumeTag\s*=\s*(\S+)/i', $line, $matches))
					$barcodes[$matches[1]] = $matches[2];
				}
				else
				{
					if (preg_match('/Storage Element\s+([0-9][0-9]*)/', $line, $matches))
					{
						if (empty($barcodes[$matches[1]]))
							$barcodes[$matches[1]] = null;
					}
					else
						throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to parse mtx output line: $line"));
				}
			}
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->errorLog("ZMC_ProcOpen::procOpen($cmd " . join(" ", $args) . "; stdout: " . $e->getStdout() . "; stderr: " . $e->getStderr(), __FILE__, __LINE__);
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("$cmd: " . $e->getStdout() . "; " . $e->getStderr()));
		}
		ksort($barcodes);
		return $barcodes;
	}

	protected function opSaveLabels()
	{
		$this->data['include_barcode'] = $this->data['binding_conf']['include_barcode'];
		$this->data['overwrite_media'] = $this->data['binding_conf']['overwrite_media'];
		unset($this->data['binding_conf']);
		$this->standardFieldCheck(array('binding_name', 'include_barcode', 'overwrite_media', 'media_list'));
		if (!$this->singleTapeDevice && !$this->multipleTapeDevice)
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal('Currently, "label" operation does not support devices of type: ' . $this->bindings['dev_meta']['_key_name']));

		if ($this->opInventory(false))
			return;

		if (file_exists($abortFile = $this->bindingPath . self::ABORT_LABEL)) 
			unlink($abortFile); 

		$cmd = ZMC::getAmandaCmd('amlabel');
		$name = $this->getAmandaConfName(true);
		if ($this->multipleTapeDevice)
			$currentSlot = $this->getCurrentSlot();
		$this->calculateNewLabels();
		$this->writeLabelFile();
		$this->busy(0);
		if (!$this->synchronousBegin(true)) 
			return;

		$i = 1;
		foreach($this->data['media_list'] as $slot => $label)
		{
			$record = self::$labelStatus[$slot];
			$args = array($name, $record['label']);
			if ($this->multipleTapeDevice)
			{
				$args[] = 'slot';
				$args[] = $slot;
			}

			if (!empty($this->data['overwrite_media']))
				array_unshift($args, '-f');

			if (file_exists($abortFile)) 
			{
				$this->updateStatus('failure', $slot, $record['label'], '', '', 'Aborted at user request');
				$this->noticeLog("Aborting label request ($abortFile exists)", __FILE__, __LINE__);
				continue;
			}
			try
			{
				$command = ZMC_ProcOpen::procOpen('amlabel', $cmd, $args, $stdout, $stderr, 'amlabel command failed unexpectedly', $this->getLogInfo());
				$this->noticeLog("ZMC_ProcOpen::procOpen('amlabel', $cmd " . join(" ", $args) . "; stdout: $stdout; stderr: $stderr ...", __FILE__, __LINE__);
				$this->updateStatus('success', $slot, $record['label'], $command, $stdout, $stderr);
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->updateStatus('failure', $slot, $record['label'], $cmd . ' ' . implode(' ', $args), $e->getStdout(), $e->getStderr());
			}

			if (false && !empty($this->mode))
			{
				$this->postResponse(array(
					'results' => $job,
					'options' => $this->data['options'],
					'barcodes' => &$barcodes,
					'amanda_configuration_name' => $this->getAmandaConfName(),
					'name' => $this->bindings['private']['zmc_device_name'],
					'_key_name' => $this->bindings['dev_meta']['_key_name']
				));
			}
			$this->busy($i++ / count($this->data['media_list']));
		}
		if ($this->multipleTapeDevice)
			self::resetSlot($currentSlot);
	}

	protected function calculateNewLabels()
	{
		$name = $this->getAmandaConfName(true);
		$maxTapeNumber = 0;
		foreach($this->data['media_list'] as $slot => &$label)
		{
			$tapeNumber = $comment = '';
			$label = trim($label);
			if ($label !== '')
			{
				if ('ASCII' !== ($encoding = mb_detect_encoding($label)))
				{
					$this->updateStatus('failure', $slot, $label, '', '', 'Labels can only contain ASCII characters. Encoding detected: ' . $encoding);
					continue;
				}

				if (!ZMC_BackupSet::isValidName($this->reply, $label))
				{
					$this->updateStatus('failure', $slot, $label, '', '', ZMC_BackupSet::BAD_NAME);
					continue;
				}

				
				
				
				






















			}


			if (($pos === false) || empty($tapeNumber))
				$tapeNumber = '@';
			elseif ($pos)
				$maxTapeNumber = max($maxTapeNumber, $tapeNumber);








			
			

			
			$label = trim(str_replace(array('---', '--'), array('-', '-'), $label), '-');
			self::$labelStatus[$slot] = array(
				'result' => 'progress',
				'slot' => $slot,
				'label' => $label,
				'timestamp' => time(),
			);
		}
		ZMC_BackupSet::getTapeList($this->reply, $tapelist, $name);
		foreach($tapelist['tapelist'] as $record)
		{
			$parts = explode('-', $record['label']);
			if (count($parts) > 1)
				$maxTapeNumber = max($maxTapeNumber, $parts[1]);
		}

		foreach(self::$labelStatus as &$record) 
			if (strpos($record['label'], '@'))
			{
				$maxTapeNumber++;
				$record['label'] = str_replace('@', str_pad((string)$maxTapeNumber, (strlen($maxTapeNumber) > 4)? strlen($maxTapeNumber): 4, '0', STR_PAD_LEFT), $record['label']);
			}
	}

	private function resetSlot($slot)
	{
		$slot = (integer)$slot; 
		if (!($slot > 0))
			return;

		try
		{
			$command = ZMC_ProcOpen::procOpen($cmd = 'amtape', ZMC::getAmandaCmd($cmd), array($this->getAmandaConfName(), 'slot', $slot),
				$stdout, $stderr, "'$cmd slot $slot' command failed unexpectedly", $this->getLogInfo(), $this->getAmandaConfPath());
		}
		catch (ZMC_Exception_ProcOpen $e) 
		{
		}
	}

	protected function updateStatus($result, $slot, $label, $command = '', $stdout = '', $stderr = '')
	{
		self::$labelStatus[$slot] = array(
			'result' => $result,
			'slot' => $slot,
			'label' => $label,
			'command' => $command,
			'stdout' => $stdout,
			'timestamp' => time(),
			'stderr' => $stderr,
		);
		if (false === file_put_contents($this->labelFile, '<? ZMC_Yasumi_Label::$labelStatus = ' . var_export(self::$labelStatus, true) . ';'))
			$this->errorLog(__CLASS__ . ": Unable to write to '" . $this->labelFile . "'.");
	}

	protected function opArchive()
	{
		$this->standardFieldCheckMediaList();
		ZMC_BackupSet::getTapeList($this->reply, $tapeList, $this->getAmandaConfName());
		
		$tapeKey = ($this->multipleTapeDevice ? 'timestring' : 'label');
		foreach($tapeList['tapelist'] as &$tape)
			if (isset($this->data['media_list'][$tape[$tapeKey]]))
				$tape['reuse'] = ($tape['reuse'] === 'reuse' ? ($archived = 'no-reuse') : 'reuse');

		if (!empty($archived))
			if($this->bindings['_key_name'] === 'changer_library')
				$this->reply->addEscapedWarning('When archiving media, <a target="_blank" href="' . ZMC::$registry->lore  . '505">refill the set of tapes available</a>.');
		ZMC_BackupSet::putTapeList($this->reply, $tapeList['tapelist'], $this->getAmandaConfName());
	}

	protected function opRecycle()
	{
		self::rmtape(true);
	}

	protected function opDrop()
	{
		self::rmtape(false);
	}

	protected function rmtape($recycle)
	{
		if ($this->multipleTapeDevice)
			$currentSlot = $this->getCurrentSlot();
		$this->optional[] = 'want_num_empty';
		$this->standardFieldCheckMediaList();
		$args = array('--cleanup', '--verbose');
		if ($recycle)
			$args[] = '--keep-label';
	
		$cmd = ZMC::getAmandaCmd('amrmtape');
		ZMC_BackupSet::getTapeList($this->reply, $tapeList, $this->getAmandaConfName());
		
		$tapesListInventory = ZMC_BackupSet::getTapeListForBackupSet($this->data['binding_conf']['config_name']);
		$erased = 0;
		
		try
		{
			$result = ZMC_Yasumi::operation(new ZMC_Registry_MessageBox(), array(
					'pathInfo' => "/Device-Profile/read_profiles",
					'data' => array(
							'message_type' => 'admin devices read',
					),
			));
			$device_profile_list = $result['device_profile_list'];
		}
		catch(Exception $e)
		{
			$this->reply->addError("Unable to read device list: $e");
		}
		
		if($this->bindings['_key_name'] === 'changer_library' || $this->bindings['_key_name'] === 'changer_ndmp'){
			foreach($this->data['media_list'] as $selected_tape => $ignored)
			{
				$selectedLabel = '';
				foreach($tapeList['tapelist'] as $label => $tape){
					if($label === $selected_tape || $tape['timestring'] === $selected_tape){
						$selectedLabel = $label;
						break;
					}
				}
				if(empty($selectedLabel))
					continue;
		
				$cmdArgs = $args;
				$isVaultMedia = false;
				$pattern = '/^' . $this->getAmandaConfName() . '-.+' . '-vault-[0-9][0-9][0-9]$/';
				if(preg_match($pattern, $selectedLabel)){ 
					$isVaultMedia = true;
				}
				
				if($isVaultMedia){
					$components = explode('-', $selectedLabel);
					$deviceName = $components[1];
					
					$deviceType = $device_profile_list[$deviceName]['_key_name'];
					$deviceDefinition = ZMC_Type_Devices::get($deviceType);
					if ($deviceDefinition['erasable_media']) $cmdArgs[] = '--erase'; 
					
					
					$cmdArgs[] = '--changer';
					$cmdArgs[] = $deviceName;
				}
							
				$cmdArgs[] = $this->getAmandaConfName();
				$cmdArgs[] = trim(ZMC_Yasumi_Parser::quote($selectedLabel), '"'); 
				try
				{
					$stdout=$stderr=$command= 'disabled';
					$command = ZMC_ProcOpen::procOpen('amrmtape', $cmd, $cmdArgs, $stdout, $stderr, 'Drop or Recycle operation did not succeed (amrmtape command failed unexpectedly)', $this->getLogInfo());
					$this->dump(array('command' => $command, 'STDOUT' => $stdout, 'STDERR' => $stderr));
					if ($this->debug)
						$this->reply->addDetail("$command\n\n$stdout\n\n$stderr");
					if ($selectedLabel[0] !== '/')
						$erased++;
				}
				catch (ZMC_Exception_ProcOpen $e)
				{
					if ($this->debug)
						$this->reply->addDetail("$e");
						
					$this->reply->addError($e->getStderr());
					return;
				}
			}
		} else {
			foreach($this->data['media_list'] as $label => $ignored)
			{
				$cmdArgs = $args;
				$isVaultMedia = false;
				$pattern = '/^' . $this->getAmandaConfName() . '-.+' . '-vault-[0-9][0-9][0-9]$/';
				if(preg_match($pattern, $label)){ 
					$isVaultMedia = true;
				}
				
				if($isVaultMedia){
					$components = explode('-', $label);
					$deviceName = $components[1];
						
					$deviceType = $device_profile_list[$deviceName]['_key_name'];
					$deviceDefinition = ZMC_Type_Devices::get($deviceType);
					if ($deviceDefinition['erasable_media']) $cmdArgs[] = '--erase'; 
						
					
					$cmdArgs[] = '--changer';
					$cmdArgs[] = $deviceName;
				} else {
					$deviceDefinition = ZMC_Type_Devices::get($this->bindings['dev_meta']['_key_name']);
					if ($deviceDefinition['erasable_media'])
						$cmdArgs[] = '--erase'; 
					if(!in_array($label, $tapesListInventory)){ 
						$cmdArgs = array_diff($cmdArgs, array("--erase"));
					}
					
					if (empty($tapeList['tapelist'][$label]) || empty($tapeList['tapelist'][$label]['timestring']))
						if (($label[0] !== '/') || !file_exists($label))
							continue;
				}
				
				$cmdArgs[] = $this->getAmandaConfName();
				$cmdArgs[] = trim(ZMC_Yasumi_Parser::quote($label), '"'); 
				try
				{
					$stdout=$stderr=$command= 'disabled';
					$command = ZMC_ProcOpen::procOpen('amrmtape', $cmd, $cmdArgs, $stdout, $stderr, 'Drop or Recycle operation did not succeed (amrmtape command failed unexpectedly)', $this->getLogInfo());
					$this->dump(array('command' => $command, 'STDOUT' => $stdout, 'STDERR' => $stderr));
					if ($this->debug)
						$this->reply->addDetail("$command\n\n$stdout\n\n$stderr");
					if ($label[0] !== '/')
						$erased++;
				}
				catch (ZMC_Exception_ProcOpen $e)
				{
					if ($this->debug)
						$this->reply->addDetail("$e");
			
					$this->reply->addError($e->getStderr());
					return;
				}
			}
		}
		$current_state = array();
		if($this->data['binding_conf']['_key_name'] === "attached_storage"){
			$current_state =& ZMC::perl2php($this->data['binding_conf']['changer']['changerdev']."/state", "STATE");
		}
		if ($this->vtape1VolumeDevice && $recycle && !empty($this->data['want_num_empty']))
			if ($this->data['want_num_empty'] > $erased)
			{
				$slots_used = $num_empty = 0;
				foreach($tapeList['tapelist'] as $tape){
					if (empty($tape['timestring'])){
						if(!empty($current_state['meta']) && $this->data['binding_conf']['_key_name'] === "attached_storage"){
							if($current_state['meta'] === $tape['meta'] && empty($tape['timestring']))
								$num_empty++;
							else
								continue;
						}
						else
							$num_empty++;
					}
					else
						$slots_used++;

				}
				
				$num_new_needed = ($this->data['want_num_empty'] - ($erased + $num_empty)) + 10 ;
				if ($num_new_needed > 0)
				{
					$this->data['binding_conf']['changer']['slots'] += $num_new_needed;
					$results = $this->command(array('pathInfo' => "/Device-Binding/merge_and_apply/" . $this->getAmandaConfName(),
						'post' => null, 'postData' => null), $this->reply);
					unset($this->reply->request);
				}
				if (ZMC::$registry->debug && (empty($results) || empty($results['fatal'])))
					$this->reply->addMessage("$slots_used backup image(s).  " . ($this->data['binding_conf']['changer']['slots'] - $slots_used) . " empty DLE containers ready for new backups");
			}

		if ($this->multipleTapeDevice && !empty($command))
			self::resetSlot($currentSlot);
	}

	private function standardFieldCheckMediaList()
	{
		$this->standardFieldCheck(array('options', 'media_list'));
	}

	private function getCurrentSlot(&$slots = null)
	{
		$state =& ZMC::perl2php($this->bindings['changerfile'], 'STATE');
		$slots = array_keys($state['slots']);
		return $state['current_slot'];
	}
}
