<?














class ZMC_Yasumi_ConfPlugin_Chgdisk extends ZMC_Yasumi_ConfPlugin
{
	protected $changerPrefix = 'chg-disk'; 

	protected $tape_name = 'Virtual Tape'; 

	protected $tapes_name = 'Virtual Tapes'; 

	



	protected function addSyntheticKeys()
	{
		parent::addSyntheticKeys();
		$this->bindings['max_dle_by_volume'] = 1;
		if (empty($this->bindings['changer']['changerdev_suffix']))
			$this->bindings['changer']['changerdev_suffix'] = $this->getAmandaConfName(true);

		if (empty($this->bindings['changer']['changerdev_prefix']))
			return;

		$location = $this->bindings['changer']['changerdev'] = '/' .  trim($this->bindings['changer']['changerdev_prefix'], '/') . '/' . $this->bindings['changer']['changerdev_suffix'];
		if ($location === '/dev/null') ZMC::quit($location);
		ZMC::assertValidAmandaPath($this->reply, $location);
		if ($this->action !== 'create' && $this->action !== 'defaults')
		{
			if (!file_exists($location))
				$this->reply->addWarnError("Vtape storage location '$location' does not exist (or can not access parent directory). " . ZMC::getDirPermHelp($location));
			if ($result = ZMC::is_readwrite($location))
				$this->reply->addWarnError("Vtape storage location '$location' is not readable/writable (permission problems?). $result");
		}

		if ($result = $this->checkPath(ZMC::$registry->vtapes_deny, $location, $this->action === 'defaults' || $this->action === 'read'))
			return $this->reply->addError($result);

		$this->addFreeSpaceKeys($this->bindings['media'], $location);
		if ($this->action !== 'defaults')
		{
			
			if( isset($this->bindings['tapetype']['length']) && $this->bindings['tapetype']['length'] <=1){
				$this->bindings['tapetype']['length'] = '10485760m';
				if (!empty($this->bindings['media']) && !empty($this->bindings['media']['partition_free_space']))
					$this->bindings['tapetype']['length'] = intval($this->bindings['media']['partition_free_space'] * (1.0 - ($this->bindings['media']['filesystem_reserved_percent']/100)) - 10) . 'm';

				if(intval($this->bindings['tapetype']['length']) <= 0){
					$this->reply->addWarnError("There is not enough space in '$location'. Please add more storage space. ");

					if (ZMC::$registry->safe_mode) return;
				}
			}
			try{
				$args = array("-Pm $location | awk 'NR>1{print int($2)}'");
				ZMC_ProcOpen::procOpen('df', 'df', $args ,$f, $f_err, 'df command failed unexpectedly', '','','','',false);
				
				if($f >= 4194304 && ZMC::$registry->large_file_system == false){
					$this->reply->addWarning("ZMC has detected that the path '$location' is in a filesystem larger than 4 TiB;  LFS support on the ". ZMC::getPageUrl($this->reply, 'Admin', 'preferences') ." page may need to be enabled for ZMC to correctly work with this filesystem.");
				}
			}catch(ZMC_Exception_ProcOpen $e){}



		}

		$this->command(array('pathInfo' => '/conf/read/' . $this->amanda_configuration_name, 'data' => array('what' => 'amanda.conf')), $this->reply);
		if(empty($this->bindings['taper_parallel_write'] ))
			$this->bindings['taper_parallel_write'] = 4;
	}

	public function cleanup()
	{
		if ($this->action === 'create' && !empty($this->bindings['changer']['changerdev'])) 
			foreach(glob($this->bindings['changer']['changerdev'] . '/slot*', GLOB_NOSORT) as $dir)
				rmdir($dir);

		parent::cleanup();
	}


	public function purgeMedia()
	{
		$vtapeDir = realpath($this->reply['binding_conf']['changer']['changerdev']);
		if(ZMC::$registry->large_file_system === false){ 
			if (!is_link($dataLink = "$vtapeDir/data") || !file_exists($statefile = realpath("$vtapeDir/state"))) 
			{
				$this->reply->addWarnError("Refusing to delete directory $vtapeDir, because it does not sufficiently resemble an Amanda Enterprise vtape storage location. Please confirm and delete manually, if you wish to purge vtapes at this location.");
				if (ZMC::$registry->safe_mode) return;
			}
		}
		$slotDirs = glob("$vtapeDir/slot*");
		foreach($slotDirs as $dir)
			if ($result = ZMC::rmrdir($realdir = realpath($dir)))
			{
				$this->reply->addWarnError("Unable to delete directory: $dir" . ($realdir !== $dir ? " (real path: $realdir)":'') . "\n$result");
				if (ZMC::$registry->safe_mode) return;
			}
		unlink($dataLink);
		unlink("$vtapeDir/state");
		
		$vaultDirs = glob("$vtapeDir/*");
		foreach($vaultDirs as $dir)
			if ($result = ZMC::rmrdir($realdir = realpath($dir)))
			{
				$this->reply->addWarnError("Unable to delete directory: $dir" . ($realdir !== $dir ? " (real path: $realdir)":'') . "\n$result");
				if (ZMC::$registry->safe_mode) return;
			}
		
	}

	public function &getBindingYaml() 
	{
		$conf =& parent::getBindingYaml();
		if (!empty($conf['autolabel']) && !strpos($conf['autolabel'], 'volume_error'))
			$conf['autolabel'] .= ' volume_error';
		return $conf;
	}

	public function makeChanger(&$conf)
	{
		parent::makeChanger($conf);
		$conf['schedule']['runtapes'] = 1000;
		return $conf;
	}
}
