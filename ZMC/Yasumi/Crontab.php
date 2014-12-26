<?
















class ZMC_Yasumi_Crontab extends ZMC_Yasumi
{
	const CRONDIR = '/etc/zmanda/zmc/zmc_aee/cron';

	protected function runFilter()
	{
		if ($name = $this->getAmandaConfName())
			if ($this->isActivated($name, $this->data['cron']))
				$this->reply['active'] = true;
	}

	protected function isActivated($confName, $cron)
	{
		return file_exists($this->getActiveCronFilename($confName, $cron));
	}
	
	private function getActiveCronFilename($confName, $cron)
	{
		return self::CRONDIR . "/$confName.$cron.cron";
	}

	public function opGetAllStatus()
	{
		$result = array();
		foreach(ZMC_BackupSet::listConfigs() as $config)
			$result[$config] = array('active' => false, 'backup_running' => false, 'restore_running' => false);

		foreach(array_merge(
			glob(ZMC::$registry->etc_amanda . '*/logs/amflush'),
			glob(ZMC::$registry->etc_amanda . '*/logs/amdump')) as $found)
			@$result[basename(dirname(dirname($found)))]['backup_running'] = true;
		
		foreach(glob(ZMC::$registry->etc_amanda . '*/logs/log') as $found){
			$contents = file_get_contents($found);
			if(strpos($contents, "amvault") !== false){ 
				@$result[basename(dirname(dirname($found)))]['vault_running'] = true;
			} else {
				@$result[basename(dirname(dirname($found)))]['backup_running'] = true;
			}
		}

		foreach(glob(ZMC::$registry->etc_amanda . '*/jobs/saved/*.pid') as $found)
		{
			$pid = intval(file_get_contents($found));
			if (!empty($pid) && file_exists("/proc/$pid"))
				@$result[basename(dirname(dirname(dirname($found))))]['restore_running'] = true;
		}

		foreach (glob(self::CRONDIR . '/*.cron') as $cron)
		{
			$name = basename($cron);
			if(strpos($name, '.vault-'))
				continue;
			$name = substr($name, 0, strpos($name, '.binding-'));
			if (isset($result[$name]))
				$result[$name]['active'] = true;
			else
				unlink($cron);
		}

		$this->reply->all_status =& $result;
	}

	private function getCronFilename($confName, $cron)
	{
		return ZMC::$registry->etc_amanda . $confName . DIRECTORY_SEPARATOR . $cron . '.cron';
	}

	
	public function opSync()
	{
		$this->reply->setPrefix("更新定时任务 .. ");
		$this->mkdirIfNotExists(self::CRONDIR);
		$oldCron = $stdout = '';
		try
		{
			foreach(array('/var/spool/cron/amandabackup', '/var/spool/cron/crontabs/amandabackup') as $fn)
				if (is_readable($fn))
					if ($liveCron = file_get_contents($fn))
						break;

			if (empty($liveCron))
				ZMC_ProcOpen::procOpen('crontab', 'crontab', array('-l'), $liveCron, $stderr, "Error status returned when reading crontab for 'amandabackup' user");
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			
			
			$stderr = $e->getStderr();
			if (!strpos($stderr, 'open your crontab') && !strpos($stderr, 'o crontab for amandabackup'))
			{
				$this->reply->addError("$e");
				return false;
			}
		}

		if (file_exists($oldCronFilename = ZMC::$registry->crontab) && (false === ($oldCron = file_get_contents($oldCronFilename))))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Master ZMC crontab schedule unreadable." . ZMC::getFilePermHelp($oldCronFilename)));

		$this->filterCron($liveCron, $liveCronZmc, $liveCronExtra);
		
		$this->filterCron($oldCron,  $oldCronZmc,  $oldCronExtra);
		
		$backupFilename = ZMC::$registry->var_log_zmc . DIRECTORY_SEPARATOR . $oldCronFilename . '-' . ZMC::dateNow(true);
		
		
		if ($liveCronZmc !== $oldCronZmc) 
			$warn = $this->reply->addWarning("Live crontab contains unexpected changes.  The affected entries are managed by ZMC and will be regenerated (replaced with current entries using backup schedules for ZMC backup sets).\nLive=$liveCronZmc\nExpected=$oldCronZmc");
		if ($liveCronExtra !== $oldCronExtra) 
			$warn = $this->reply->addWarning("Live crontab has been altered outside of ZMC.\nLive=$liveCronExtra\nExpected=$oldCronExtra");

		if (!empty($warn))
		{
			
			$this->commit($oldCronFilename, $backupFilename, null ,
				"$liveCronExtra$liveCronZmc"); 
		}

		if ($confName = $this->getAmandaConfName())
		{
			$cronFilename = $this->getCronFilename($confName, $this->data['cron']);
			if (!file_exists($cronFilename) || !is_readable($cronFilename))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Schedule ($cronFilename) missing or unreadable." . ZMC::getFilePermHelp($cronFilename)));
			if (isset($this->data['activate']))
			{
				$activeCronFilename = $this->getActiveCronFilename($confName, $this->data['cron']);
				if (file_exists($activeCronFilename) && (false === unlink($activeCronFilename)))
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to alter cron/schedule activation status for \"$confName\"." . ZMC::getFilePermHelp($activeCronFilename)));
				if ($this->data['activate'] && (false === link($cronFilename, $activeCronFilename)))
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to mark \"$confName\" active. " . ZMC::getFilePermHelp($activeCronFilename)));
			}
		}

		$newCron= '';
		$total = 0;
		foreach (glob(self::CRONDIR . '/*.cron') as $cron)
			if (false === ($contents = file_get_contents($cron)))
				$this->reply->addError("Unable to read '$cron': " . ZMC_Error::error_get_last());
			else
			{
				$total++;
				$newCron.= $contents;
			}

		$this->filterCron($newCron, $newCronZmc, $ignored);
		
		$newCron = $liveCronExtra . $newCronZmc; 
		
		
		if ($newCronZmc !== $liveCronZmc)
		{
			$this->commit($oldCronFilename, $backupFilename . '.2', null, $newCron);
			try
			{
				ZMC_ProcOpen::procOpen('crontab', 'crontab', array($oldCronFilename), $stdout, $stderr, "Error status returned when installing new crontab for 'amandabackup' user.");
				$msg = '用户的定时任务更新成功。';
				if (empty($newCron))
					$msg = "用户的定时任务为空.\n不存在激活的备份集。";
				
					
				$this->reply->addMessage("$msg\r总共有 $total 个激活的备份集。\n$stdout $stderr");
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->reply->addError($e->getMessage());
				$this->reply->addDetail("$e");
				throw new ZMC_Exception_YasumiFatal($e);
			}
		}
		elseif (!empty($newCron))
			$this->reply->addDetail("Crontab unchanged:\n$ crontab -l amandabackup\n==============\n$newCron");
	}

	public function filterCron($src, &$zmc, &$extra)
	{
		$zmc = array();
		$extra = array();
		foreach(explode("\n", $src) as $line)
		{
			if (empty($line))
				continue;
			if (strpos($line, ' --zmcdev ') || strpos($line, 'amvault') || strpos($line, 'ZMC Vault Job'))
				$zmc[] = $line . "\n";
			elseif (!empty($line) && !  
				(		strpos($line, 'DO NOT EDIT')
					||	strpos($line, 'zmanda/zmc_aee/crontab installed on')
					||	strpos($line, 'MANAGED BY ZMC')
					||	(strpos($line, '/tmp/crontab.') && strpos($line, 'installed on'))
					||	strpos($line, 'Cron version V')))
				$extra[] = $line . "\n";
		}

		$zmc = (empty($zmc) ? '' : "### MANAGED BY ZMC ###\n" . implode('', $zmc));
		$extra = implode('', $extra) . (empty($extra)? '' : "\n\n");
	}
}
