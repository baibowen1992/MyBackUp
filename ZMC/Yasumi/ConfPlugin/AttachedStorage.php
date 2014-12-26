<?














class ZMC_Yasumi_ConfPlugin_AttachedStorage extends ZMC_Yasumi_ConfPlugin_Chgdisk
{
protected function adjustSlots()
{
	$changerdev = $this->bindings['changer']['changerdev'];
	$slots = glob("$changerdev/slot*", GLOB_NOSORT);
	if (($this->action === 'create') && count($slots))
	{
		$files = glob("$changerdev/slot*/*", GLOB_NOSORT);
		if (count($files)) 
		{
			$this->reply->addWarnError("Previously used " . $this->bindings['dev_meta']['name'] . " found at '$changerdev'. Please manually move or remove '$changerdev', before continuing.");
			if (ZMC::$registry->safe_mode) return;
		}
	}

	$this->slotsRequested = (empty($this->bindings['changer']['slots']) ? 1 : $this->bindings['changer']['slots']);
	if ($this->tooManySlotsRequested($this->slotsRequested))
		return;

	$this->dirOk = array();
	$error = false;
	$this->sorted = array();

	if (!empty($slots))
	{
		foreach($slots as $slot)
			$this->sorted[substr($slot, strpos($slot, '/slot') + 5)] = $slot;
		krsort($this->sorted, SORT_NUMERIC);
		
		$lastSlot = key($this->sorted);
		if ($lastSlot > $this->slotsRequested)
			$this->bindings['changer']['slots'] = $this->slotsRequested = $lastSlot; 
		
		if ($lastSlot > $this->bindings['max_slots']) {
			$error = "The existing DLE container '$slot' exceeds the maximum permitted for this device ({$this->bindings['max_slots']}). The maximum can be increased using the advanced settings on the Admin|devices page.";
		} else {
			foreach($this->sorted as $slotNumber => $slot) {
 				if (!is_integer($slotNumber) || ($slotNumber < 1)) {
					$error = "DLE container '$slot' is not a valid name.";
					break;
				}
	
				if ($result = ZMC::is_readwrite($slot))	{
				 	$error = "DLE container \"' . $slot . '\" is not writable. $result";
					break;
				}
	
				if (file_exists("$slot/00000." . $this->amanda_configuration_name . '-' . str_pad($slotNumber, (strlen($slotNumber) > 3)? strlen($slotNumber): 3, '0', STR_PAD_LEFT)))
					$this->dirOk[$slotNumber] = true;
			}
		}
	}
	$this->bindings['changer']['slots'] = min($this->slotsRequested, $this->bindings['max_slots']);
	parent::adjustSlots();

	if ($error)
		return $this->reply->addWarnError($error);
}

public function makeChanger(&$conf)
{
	$conf['changerfile'] = $conf['changer']['changerdev'] . '/state';
	$state =& ZMC::perl2php($conf['changerfile'], 'STATE'); 
	$this->metaCounter = $this->reply->binding_conf['metalabel_counter'];
	if (empty($state))
		$state = array();
	if (!empty($state['meta']))
		$this->metaCounter = $state['meta'];
	else
	{
		$letters = substr($this->metaCounter, -2);
		$c0 = ord($letters[0]);
		$c1 = ord($letters[1]) + 1;
		if ($c1 > ord('Z'))
		{
			$c0++;
			$c1 = ord('A');
		}
		
		$state['meta'] = $this->metaCounter;
		$this->reply->binding_conf['metalabel_counter'] = chr($c0) . chr($c1); 
		file_put_contents($conf['changerfile'], '$STATE = ' . ZMC::php2perl($state) . "\n;\n");
	}
	parent::makeChanger($conf);
}



public function createAndLabelSlots()
{
	$labeled = 0;
	$numErrors = $this->reply->isErrors();
	$config = $this->getAmandaConfName(true);
	$changerdev = $this->reply->binding_conf['changer']['changerdev'];
	ZMC_BackupSet::getTapeList($this->reply, $this->tapeList, $config);
	$tapeList = array();
	for($slot = 1; $slot <= $this->reply->binding_conf['changer']['slots']; $slot++)
	{
		if (!empty($this->sorted) && isset($this->dirOk[$slot]))
			continue;
		
		$check_dir = exec("if [ -d $changerdev/slot$slot ]; then echo 'Exists'; else echo 'Not found'; fi");
		if(preg_match("/Not\s+found/", $check_dir))
			$result = $this->mkdirIfNotExists($dir = "$changerdev/slot$slot");
		$slotPadded = str_pad($slot, (strlen($slot) > 3)? strlen($slot): 3, '0', STR_PAD_LEFT);
		$label = $this->amanda_configuration_name . '-' . $this->metaCounter . "-$slotPadded";
		$fn = "$changerdev/slot$slot/00000.$label";
		if (file_exists($fn))
			continue;
		$labelContents = "AMANDA: TAPESTART DATE X TAPE $label\n\f\n";
		$labelContents .= str_repeat("\0", 32768 - strlen($labelContents));
		if (false === file_put_contents($fn, $labelContents))
		{
			$this->reply->addWarnError("Unable to create container for DLE backup image at: $fn  " . ZMC::getFilePermHelp($fn));
			break;
		}
		$labeled++;
		$this->tapeList['tapelist'][$label] = $tapeList[$label] = array(
			'timestring' => 0,
			'label' => $label,
			'reuse' => 'reuse',
			'meta' => $this->metaCounter,
			
		);
	}

	if ($labeled)
	{
		if (ZMC::$registry->debug) $this->reply->addMessage("为备份镜像创建 $labeled 个新的标记");
		ZMC_BackupSet::putTapeList($this->reply, $tapeList, $config, true);
	}

	$link = false;
	$dataLink = "$changerdev/data";
	if(ZMC::$registry->large_file_system === false){
		if (is_link($dataLink))
			$link = readlink($dataLink);
		elseif (file_exists($dataLink))
		{
			$this->reply->addWarnError("文件 '$dataLink' 并不是指向当前备份镜像标记的链接，请在继续运行前删除。");
			if (ZMC::$registry->safe_mode) return;
		}
	}else{
		$testdatalink = exec("if [ -L $dataLink ]; then echo 'symbolic'; fi");
		if(preg_match("/symbolic/", $testdatalink))
			$link = exec("readlink $dataLink");
	}
	













	if ($link === false)
	{
		if(ZMC::$registry->large_file_system === false){
			if (false === symlink("$changerdev/slot1", "$changerdev/data"))
				$this->reply->addWarnError("Unable to create data/ symlink to the first DLE backup image container. Failed command:  ln -sf '$changerdev/slot1' '$changerdev/data'");
		}
		else
		{
			try 
			{
				$command = ZMC_ProcOpen::procOpen($cmd = 'ln', $cmd, array('-sf', "$changerdev/slot1","$changerdev/data"),
				$stdout, $stderr, "$cmd command failed unexpectedly");
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->reply->addInternal($e->getStdout() . "; " . $e->getStderr());
			}


		}
	}

	if ($this->reply->isErrors() > $numErrors)
		throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Failed to create all DLE backup image containers in the ZMC cloud bucket."));
}
}
