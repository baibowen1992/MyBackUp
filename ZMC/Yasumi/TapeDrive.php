<?













class ZMC_Yasumi_TapeDrive extends ZMC_Yasumi
{
protected $tapedev = null;
	protected function init()
	{
		parent::init();
	}

	





	public function opDiscoverChangersAndTapes()
	{
		$this->opDiscoverChangers();
		$this->opDiscoverTapes();
	}

	




	public function opDiscoverTapes()
	{
		$this->lsscsi();
		try
		{
			ZMC_ProcOpen::procOpen('mt', ZMC::$registry->getAmandaConstant('MT'), ZMC::$registry->platform === 'solaris' ? array() : array('-v'), $stdout, $stderr);
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			try
			{
				ZMC_ProcOpen::procOpen('mt', ZMC::$registry->getAmandaConstant('MT'), ZMC::$registry->platform === 'solaris' ? array() : array('-V'), $stdout, $stderr);
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				if (ZMC::$registry->platform !== 'solaris' || false === strpos($e->getStderr(), 'usage: mt'))
				{
					$this->reply->addWarnError(ZMC::$registry->getAmandaConstant('MT') . " failed to run correctly! Make sure 'mt' package has been installed and made accessible to the 'amandabackup' user. Also make sure a tape media has been inserted into the tape drive.");
					$this->reply->addDetail("$e");
					return;
				}
			}
		}
		$other = array();
		if(!empty($this->data['tape_dev'])){
			foreach($this->data['tape_dev'] as $i=>$j)
				$other[$i."*"] = '/^'.basename($i).'$/';
		}
		foreach (array_merge( ZMC::$registry->get('tapedev_user', array()), ZMC::$registry->tapedev_globs, $other) as $globexpr => $devre)
		{
			$candidates = ZMC::expandIfGlobHasWild($globexpr);

			foreach ($candidates as $candidate)
			{
				
				if ($devre === true || (preg_match($devre, basename($candidate)) == 1))
				{
					$test = false;
					if (!file_exists($candidate))
					{
						
						$this->debugLog("Ignored tape device '$candidate', because it does not exist (spelling, filenames, perms, power on?).");
						$this->reply['tapedev_list'][$candidate] = null;
					}
					elseif ($rw = ZMC::is_readwrite($candidate, false))
					{
						$this->reply->addWarning($warning = "Unable to use device '$candidate', because the 'amandabackup' user lacks read and/or write access.");
						$this->reply['tapedev_list'][$candidate] = array('stderr' => $warning, 'stdout' => '');
					}
					else
						$test = true;

					if ($test || !ZMC::$registry->safe_mode)
					{
						$flags = array('-f', $candidate, 'status');
						try
						{
							ZMC_ProcOpen::procOpen('mt', ZMC::$registry->getAmandaConstant('MT'), $flags, $stdout, $stderr, null, null, null, ZMC::$registry['proc_open_ultrashort_timeout']);
							$this->reply['tapedev_list'][$candidate] = array('show' => true, 'stdout' => $stdout, 'stderr' => $stderr);
						}
						catch (ZMC_Exception_ProcOpen $e) 
						{
							$this->reply['tapedev_list'][$candidate] = array('stderr' => $stderr, 'stdout' => '');
						}
					}
				}
			}
		}

		$good = array();
		if (!empty($this->reply['tapedev_list']))
			foreach($this->reply['tapedev_list'] as $candidate => $status)
				if (!empty($status['show']))
					$good[] = $candidate;

		$this->reply->addMessage("Found " . count($good) . " tape drive(s) usable by the 'amandabackup' user: " . implode(', ', $good));

		
		
		
		
		
		
		ZMC::$registry->setOverrides(array('tapedev_list' => $this->reply['tapedev_list']));
		$this->reply['changerdev_list'] = empty(ZMC::$registry['changerdev_list']) ? array() : ZMC::$registry['changerdev_list']; 
	}

	protected function opDiscoverChangers()
	{
		$this->lsscsi();
		try
		{
			ZMC_ProcOpen::procOpen('mtx', ZMC::$registry->getAmandaConstant('MTX'),  array(), $stdout, $stderr);
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->reply->addWarnError("Make sure 'mtx' package has been installed and made accessible to the 'amandabackup' user. Also make sure a tape drive on the changer is present and accessible to the 'amandabackup' user.");
			$this->reply->addDetail("$e");
			return;
		}

		$this->reply['changerdev_list'] = $candidates = array();
		$optional_changer_device_path = array();
		if(!empty(ZMC::$registry->changer_device_path))
			if(is_array(ZMC::$registry->changer_device_path))
				$optional_changer_device_path = array_flip(ZMC::$registry->changer_device_path);
			else if( $keywords = preg_split("/[\s,;]+/", ZMC::$registry->changer_device_path ))
				if(!empty($keywords))
					$optional_changer_device_path = array_flip($keywords);
		if(!empty($this->data['additional_changer_device_path']))
			$optional_changer_device_path = array_merge($optional_changer_device_path, $this->data['additional_changer_device_path']);
		foreach (array_merge(ZMC::$registry->get('changerdev_user', array()), ZMC::$registry->changerdev_globs, $optional_changer_device_path) as $globexpr => $ignored)
			$candidates = array_merge($candidates, array_flip(ZMC::expandIfGlobHasWild($globexpr)));

		foreach (array_keys($candidates) as $candidate)
		{
			
			{
				$realpath = realpath($candidate);
				
				$warning = '';
				$this->reply['changerdev_list'][$realpath] = array('stderr' => $warning, 'stdout' => '', 'linked_from' => $candidate); 
				if ($rw = ZMC::is_readwrite($realpath, false))
				{
					$this->reply->addWarning($warning = "Unable to use changer device at '$candidate => $realpath' because the link points to destination lacking read and/or write permissions.\n$rw");
					$this->reply['changerdev_list'][$candidate] = array('stderr' => $warning, 'stdout' => ''); 
				}
				if (($rw === false) || !ZMC::$registry->safe_mode)
				{
					$this->reply['changerdev_list'][$candidate] = $this->countChangerDrives($candidate);
					$this->reply['changerdev_list'][$candidate]['linked_to'] = $realpath;
				}
			}
			
			
		}

		foreach(array_keys($this->reply['changerdev_list']) as $candidate)
		{
			if (empty($this->reply['changerdev_list'][$candidate]['show']))
				continue;
			if (!file_exists($candidate))
			{
				
				$this->debugLog("Ignored changer device '$candidate', because it does not exist (spelling, filenames, perms, power on?).");
				if (ZMC::$registry->safe_mode)
					$this->reply['changerdev_list'][$candidate] = null; 
				continue;
			}

			if ($rw = ZMC::is_readwrite($candidate, false))
			{
				$this->reply->addWarning($warning = "Unable to use changer device '$candidate', because the 'amandabackup' user lacks read and/or write access.\n$rw");
				$this->reply['changerdev_list'][$candidate] = array('stderr' => $warning, 'stdout' => '');
			}
			if (($rw === false) || !ZMC::$registry->safe_mode)
				$this->reply['changerdev_list'][$candidate] = $this->countChangerDrives($candidate);
		}

		$good = array();
		foreach($this->reply['changerdev_list'] as $candidate => &$status2)
			if (!empty($status2['show']))
				$good[] = $candidate;
		$this->reply->addMessage("Found " . count($good) . " changer(s) usable by the 'amandabackup' user: " . implode(', ', $good));

		$this->dump($this->reply['changerdev_list']);
		if ($this->debug_level > ZMC_Error::NOTICE)
			$this->reply['changerdev_list']['/dev/Debug_Mode_Dummy_Changer'] = array(
				'tape_drives' => 3,
				'tape_slots' => 25,
				'stdout' => 'Exists just for testing purposes when debug mode has been enabled. Not functional.',
				'stderr' => 'Dummy Device.',
			);

		ZMC::$registry->setOverrides(array('changerdev_list' => $this->reply['changerdev_list']));
	}

	
	
	protected function countChangerDrives($changer)
	{
		$args = array('-f', $changer, 'status');
		try
		{
			$command = ZMC_ProcOpen::procOpen('mtx', ZMC::$registry->getAmandaConstant('MTX'),
				$args, $stdout, $stderr, 'mtx command failed unexpectedly', $this->getLogInfo(), null, ZMC::$registry->proc_open_short_timeout);
			$this->debug && $this->dump("$command\nSTDOUT=$stdout\nSTDERR=$stderr");
			$length = strlen($stdout);
			$importExportSlots = 0;
			


			if (preg_match('/:\s*([0-9]+)\W+drives[^0-9]+([0-9]+)\W+slot(.*)/i', $stdout, $matches))
			{
				$drives = $matches[1];
				$slots = $matches[2];
				if (preg_match('/[^0-9]*([0-9]+)\W+(?:import|export)/i', $matches[3], $more_matches))
				{
					$slots -= $more_matches[1];
					$importExportSlots = $more_matches[2];
				}

				$this->debugLog('Matched drives and slots: ' . implode(' ', $args) . ": drives=$drives, slots=$slots; import/export=$importExportSlots");
			}
			else 
			{
				$this->debugLog('Could not match drives and slots using VTL output.');
				for ($i = 0, $drives = 0; $i < $length; $drives++, $i = $pos + 1)
					if (false === ($pos = strpos($stdout, 'Data Transfer Element', $i)))
						break;
				for ($i = 0, $slots = 0; $i < $length; $slots++, $i = $pos + 1)
					if (false !== ($pos = stripos($stdout, 'import', $i)))
						$importExportSlots++;
					elseif (false !== ($pos = stripos($stdout, 'export', $i)))
						$importExportSlots++;
					elseif (false === ($pos = strpos($stdout, 'Storage Element', $i)))
						break;
			}

			return array(
				'tape_drives' => $drives,
				'tape_slots' => $slots,
				'import_export_slots' => $importExportSlots,
				'stdout' => $stdout,
				'stderr' => $stderr,
				'show' => true,
			);
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->reply->addDetail("mtx -f $changer status: $e");
			$this->reply->addWarning($warning = "Ignoring changer device '$changer', because no drives found in 'mtx status' output for this changer device.");
			return array('stderr' => $warning, 'stdout' => '');
		}
	}

	protected function lsscsi()
	{
		if(ZMC::$registry->platform === 'solaris') 
			return;
		try
		{
			$command = ZMC_ProcOpen::procOpen('lsscsi', 'lsscsi', array('-g'), $lsscsi, $stderr);
			if (is_readable($fn = '/proc/scsi/scsi'))
			{
				$scsi = file_get_contents($fn);
				$lsscsi .= "\n" . $scsi;
			}
			ZMC::$registry->setOverrides(array('lsscsi' => $lsscsi));
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$this->reply->addDetail("lsscsi: $e");
			ZMC::$registry->setOverrides(array('lsscsi' => $lsscsi = 'Unable to run command "lsscsi".  Is package "lsscsi" installed and accessible to the \'amandabackup\' user?'));
		}
		$this->reply['lsscsi'] = $lsscsi;
	}
}
