<?














class ZMC_Restore_Now extends ZMC_Restore
{
	public static function runWrapped(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Restore', '云备份 - 执行还原操作', 'now');
		$pm->addDefaultInstruction('开始执行恢复任务');
		if (!($configName = ZMC_BackupSet::assertSelected($pm)))
			return 'MessageBox'; 
		if (!ZMC_BackupSet::hasBackups($pm, $configName))
			return 'MessageBox'; 

		$pm->rows = $pm->state = '';
		if (!empty($_REQUEST['action']))
			$pm->state = $_REQUEST['action'];
		unset($_REQUEST['action']);

		$job = new self($pm, $configName);
		if ($job->redirect_page)
			return $job->redirect_page;

		if (empty($job->restore_job['configured_how']) && ($job->isConflictResolutionConfigurable()))
		{
			$pm->addEscapedError('在正式执行恢复前，请首先确认已经配置过怎样恢复。');
			return ZMC::redirectPage('ZMC_Restore_How', $pm);
		}
		
		$job->createRestoreLists();
		$job->mergeTapeStats();
		if ($job->restore_job['zmc_type'] === 'windowsexchange' && ($job->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL))
			$pm->addMessage("在开始还原前, use the Exchange System Management Tool to manually dismount all the databases (Stores) in the Storage group selected for restoration.\n\nThen, select the option \"This database can be overwritten by a restore\" from the Store properties.\nAlso disable Circular Logging, if enabled.\n\nLastly, once the Storage group(s) are dismounted, click on the OK button.");

		if ($job->restore_job['host_type'] !== ZMC_Type_AmandaApps::HOST_TYPE_WINDOWS)
			$pm->addWarning('如果客户端有SELinux，在恢复过程中它将会被禁用(non-enforcing)。');

		$template = $job->runState($pm);
		if (ZMC::$registry->dev_only) $pm->addDetail($pm->restore_state);
		$template = (empty($template) ? 'RestoreNow' : $template);
		if (($template === 'RestoreNow') && !empty($pm->restore_state['date_started_occ']))
			if ($pm->restore_state['date_started_occ'] < $job->restore_job['occ_mtime'])
			{
				$pm->show_old_result_warning = true;
				ZMC_HeaderFooter::$instance->injectYuiCode("gebi('prior_restore_date').innerHTML += '<br />' + YAHOO.zmc.utils.timestamp2locale(" . $pm->restore_state["timestamp_end"] . ")");
			}

		return $template;
	}

	protected function runState($pm)
	{
		if ($pm->state === 'RestoreConfirm')
		{
			if (isset($_POST['ConfirmationYes']))
				$pm->state = 'Restore';
			else
				return ZMC::redirectPage('ZMC_Restore_Where', $pm);
		}

		if ($pm->state === 'Abort')
		{
			$this->yasumiJobRequest('Abort');
			return;
		}

		if ($pm->state === 'Restore' || $pm->state === 'Repeat Restore')
		{
			if($pm->restore['zmc_type'] === 'ndmp' && !empty($pm->restore['ndmp_username']) && !empty($pm->restore['ndmp_password'])){
				$username = $pm->restore['ndmp_username'];
				$password = '6G!dr' . base64_encode($pm->restore['ndmp_password']);
				$destination = $pm->restore['destination_location'];
				if(strpos($destination, "//") !== 0)
					$pm->addEscapedError("Destination location for NDMP restore must begin with double forward slash \"//\".");
				$destination = substr($destination, 2);
				$filer_host_name = substr($destination, 0, strpos($destination, '/'));
				$vol_name = substr($destination, strpos($destination, '/'));
				$line = "\"$filer_host_name\" \"$vol_name\" \"$username\" \"$password\" {$pm->restore['ndmp_filer_auth']}";
				$passfile_path = ZMC::$registry->etc_amanda . $pm->restore['config'] . "/ndmp_filer_shares";
				$passfile_content = file_get_contents($passfile_path);
				if(strpos($passfile_content, $line) === false){
					file_put_contents($passfile_path,$line . "\n" . $passfile_content);
					$this->restore_job['remove_ndmp_credentials'] = true;
				} else {
					$this->restore_job['remove_ndmp_credentials'] = false;
				}
			}
			
			if (!empty($pm->disabled))
			{
				$pm->addEscapedError("存储设备'" . $pm->set['profile_name'] . "的许可证已过期。"
				. ZMC::getPageUrl($pm, 'Admin', 'licenses')
				. ' for license details.<br />Please see the '
				. ZMC::getPageUrl($pm, 'Backup', 'what')
				. ' for details about the affected DLEs.');
				return;
			}

			if (!empty($pm->set['backup_running'])) 
			{
				$pm->addWarning('目前有备份正在运行，从云端恢复较平常而言可能会花费很长时间restores from Changer Libraries may fail, if the tape drive used is busy.');
				
			}

			if ($pm->restore_state['running'])
			{
				$pm->addWarnError('恢复任务正在执行中，不能再启动新的恢复任务。');
				if (ZMC::$registry->safe_mode) return;
			}

			if (empty($this->restore_job['target_host']))
				$this->restore_job['target_host'] = 'localhost';

			if (empty($this->restore_job['target_dir']) && !isset($_POST['ConfirmationYes']))
			{

				if ($this->restore_job['client'] === $this->restore_job['target_host'])
					$pm->addMessage('恢复到源主机源目录的请求');
				else
					$pm->addWarning('恢复到不同主机但是源目录的请求。');

				$textKey = 'conflict_file_text';
				$policyKey = 'conflict_file_selected';
				$overwrite = '';
				foreach(explode('/', $pm->restore['element_name']) as $file_or_dir)
				{
					$cr = 'Conflict Resolution: ' . $this->restore_job[$textKey];
					if ($this->restore_job[$policyKey] === ZMC_Type_AmandaApps::OVERWRITE_EXISTING)
					{
						$pm->addWarning($cr);
						$overwrite = " and OVERWRITE original $file_or_dir";
					}
					else
						$pm->addMessage($cr);
					$textKey = 'conflict_dir_text';
					$policyKey = 'conflict_dir_selected';
				}

				$pm->prompt = "还原到已经存在的还原路径下$overwrite?<br />\n确定?";
				$pm->confirm_action = 'RestoreConfirm';
				$pm->yes = 'Restore On Top of Original Location';
				$pm->no = 'Cancel';
				$this->mergeToDisk();
				return $pm->confirm_template = 'ConfirmationWindow';
			}
			$this->restore_job['user'] = $_SESSION['user'];
			$this->startRestore();
			$this->mergeToDisk();
			return;
		}

		if (isset($_POST['restore_ok']))
		{
			if (empty($this->restore_job['task_id']))
				return $pm->addError("无法完成请求，还原任务未知.");

			$password = 'NULL';
			if (isset($_POST['restore_password']))
				$password = "'" . ZMC_Mysql::escape($_POST['restore_password']) . "'";

			if ($_POST['restore_flag'] == 1)
				ZMC_Mysql::query($query = 'INSERT INTO restore_info (id, info) VALUES (' . $this->restore_job['task_id'] . ", $password) ON DUPLICATE KEY UPDATE info=$password;", null, false, "Updated restore_info password ('info') for id " . $this->restore_job['task_id']);

			posix_kill($this->restore_job['restore_status']['launcher_id'], 0); 
			sleep(2); 
			return;
		}
	}
}
