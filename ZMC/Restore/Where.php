<?













class ZMC_Restore_Where extends ZMC_Restore
{
	public static function runWrapped(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Restore', 'ZMC - Where would you like to restore to?', 'where');
		$pm->addDefaultInstruction('Configure where to perform restore (e.g. new vs. original locations).');
		if (!($configName = ZMC_BackupSet::assertSelected($pm)))
			return 'MessageBox'; 
		if (!ZMC_BackupSet::hasBackups($pm, $configName))
			return 'MessageBox'; 

		$pm->rows = $pm->state = '';
		if (!empty($_REQUEST['action']))
			$pm->state = $_REQUEST['action'];
		unset($_REQUEST['action']);
		if (!empty($_REQUEST['target_dir_selected_type']) && $_REQUEST['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL)
			$_REQUEST['safe_mode'] = 0; 

		$job = new self($pm, $configName);
		if ($job->redirect_page)
			return $job->redirect_page;

		
		

		$pm->target_dir_types = ZMC_Type_AmandaApps::getTargetDirTypes($pm->restore['target_dir_types']);
		ZMC_Type_AmandaApps::setTempDirDefault($pm, $job->restore_job);
		if (!empty($_POST['action']))
			$template = $job->runState($pm);

		return (empty($template) ? 'RestoreWhere' : $template);
	}

	protected function runState($pm)
	{
		switch($_POST['action'])
		{
			case 'Apply':
			case 'Apply Previous':
				$applyPrevious = true;
			case 'Apply Next':
				$this->hostChecks();

				if ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_RAW_IMAGE)
				{
					$pm->addWarning('When restoring Windows backup image files created by ZWC to non-Windows systems, only the raw image file (ZIP formatted) can be restored. Please make sure adequate space exists for restoring the entire backup image file.');
					$pm->addWarning('Conflict resolution does not apply for raw image restores. Restoring a raw image file will overwrite any existing raw image file having the same name at the same location as "Destination Location"');
					$this->restore_job['save_conflict_resolvable'] = $this->restore_job['conflict_resolvable'];
					$this->restore_job['conflict_resolvable'] = false; 
				}
				else
				{
					if (!empty($this->restore_job['save_conflict_resolvable']))
					{
						$this->restore_job['conflict_resolvable'] = $this->restore_job['save_conflict_resolvable'];
						$this->restore_job['save_conflict_resolvable'] = null;
					}
					if (!empty($this->restore_job['temp_dir_selected_type']))
						if (empty($this->restore_job['zwc'])) 
							$this->restore_job['temp_dir'] = ZMC_Type_AmandaApps::assertValidDir($pm, $this->restore_job['temp_dir'], $this->restore_job['temp_dir_selected_type'], 'Temporary Directory');
						if($this->restore_job['_key_name'] === 'vmware'){
							if(empty($this->restore_job['temp_dir'])){
								$pm->addError('Please choose a Temperory Directory Location');
							}else if($pm->restore['temp_dir'] == "/tmp/amanda" && !empty(ZMC::$registry['default_vmware_restore_temp_path'])){
								$pm->restore['temp_dir'] = ZMC::$registry['default_vmware_restore_temp_path'];
							}   
						}
						if($this->restore_job['_key_name'] === 'solaris' && $this->restore_job['program'] == "amzfs-sendrecv"){
							if(empty($this->restore_job['temp_dir'])){
								$pm->addError('Please choose a Temperory Directory Location');
							}else if($pm->restore['temp_dir'] !== "/tmp" && $pm->restore['temp_dir'][0] !== "/"){
								$pm->restore['temp_dir'] = "/tmp";
							}   
						}
				}
				if($this->restore_job['_key_name'] === 'cifs' && $this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL){
					$this->restore_job['target_host'] = '127.0.0.1';
					$this->restore_job['target_dir'] = $this->restore_job['disk_name'];
				}
					
				$this->filterHostname($this->restore_job['target_host']);

				if ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL)
				{
					if ($this->restore_job['_key_name'] !== 'ndmp')
						$this->restore_job['target_dir'] = '';
 					if ($this->restore_job['_key_name'] == 'vmware')
						$this->restore_job['target_dir'] = $this->restore_job['disk_device'];
					if ($this->restore_job['restore_type'] !== ZMC_Restore::EXPRESS)
						if ($this->restore_job['restore_to_original_requires_express'])
							$pm->addError('Please choose a Destination Location other than "Original Location", or use '
								. ZMC_Restore::$buttons[ZMC_Restore::EXPRESS]
								. ' to restore to the orginal location. "' . $this->restore_job['pretty_name'] . '" can not be restored onto a "live" location piecemeal.');
				}
				else {
					if ($this->restore_job['_key_name'] === 'cifs'){
						if ($this->restore_job['target_dir_selected_type'] != ZMC_Type_AmandaApps::DIR_UNIX)
						{
							$this->restore_job['target_host'] = '127.0.0.1';
							if ($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL)
								$this->restore_job['target_dir'] = '\\\\' . $this->restore_job['target_host'] . substr($this->restore_job['disk_device'], strpos($this->restore_job['disk_device'], '\\', 3));
						}
					}
					if($this->restore_job['_key_name'] === 'ndmp'){
						if(empty($this->restore_job['ndmp_filer_host_name']))
							$pm->addWarnError('Filer Host Name is required.');
						if(empty($this->restore_job['ndmp_volume_name']))
							$pm->addWarnError('Volume Name is required.');
						if(empty($this->restore_job['ndmp_directory']))
							$pm->addWarnError('Directory is required.');
						$this->restore_job['target_dir'] = "//" . $this->restore_job['ndmp_filer_host_name'] .  $this->restore_job['ndmp_volume_name'] . $this->restore_job['ndmp_directory'];
					} elseif($this->restore_job['_key_name'] === 'vmware') {
						if(empty($this->restore_job['esx_host_name']))
							$pm->addWarnError('ESX Host Name is required.');
						if(empty($this->restore_job['virtual_machine_name']))
							$pm->addWarnError('Virtual Machine Name is required.');
						if(empty($this->restore_job['datastore_name']))
							$pm->addWarnError('Datastore Name is required.');
						$this->restore_job['target_dir'] = "\\\\" . $this->restore_job['esx_host_name'] .  "\\" . $this->restore_job['datastore_name'] .  "\\" . $this->restore_job['virtual_machine_name'];
					} elseif($this->restore_job['_key_name'] === 'windowssqlserver'){
						$rows =& ZMC_Mysql::getAllRows('SELECT * FROM ' . $this->restore_job['tableName'] . 'WHERE (restore = ' . ZMC_Restore_What::SELECT . ' OR restore = ' . ZMC_Restore_What::IMPLIED_SELECT . ') AND type = 2 ORDER BY id');
						if($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME){
							$this->restore_job['sql_alternate_name'] = array();
							foreach($rows as $row)
								$this->restore_job['sql_alternate_name'][$row['id']] = array('original_path' => $row['filename'], 'new_name' => $this->restore_job['sql_alternate_name_new_name_' . $row['id']], 'new_path' => $this->restore_job['sql_alternate_name_new_path_' . $row['id']]);
						} elseif($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH){
							$this->restore_job['sql_alternate_path'] = array();
							foreach($rows as $row)
								$this->restore_job['sql_alternate_path'][$row['id']] = array('original_path' => $row['filename'], 'new_path' => $this->restore_job['sql_alternate_path_new_path_' . $row['id']]);
						}
					} else {
						$this->restore_job['target_dir'] = ZMC_Type_AmandaApps::assertValidDir($pm, $this->restore_job['target_dir'], $this->restore_job['target_dir_selected_type'], 'Destination Location');
					}
				}

				if (false === ZMC_User::filterHostUsername($pm, $this->restore_job['user_name']))
					$pm->addWarnError('Invalid User Name.');
		
				if (!$pm->isErrors())
					$this->restore_job['configured_where'] = true;

				$conflictResolvable = $this->isConflictResolutionConfigurable();
				$this->restore_job['destination_location'] = $this->restore_job[($this->restore_job['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL) ? 'disk_device' : 'target_dir'];
				if ($this->restore_job['safe_mode'] && ($this->restore_job['restore_type'] === ZMC_Restore::EXPRESS))
					$this->restore_job['configured_how'] = true;
				$this->mergeToDisk();
				if ($pm->isErrors())
					return;

				$pm->addMessage('Restore|Where changes applied.');
				if (!empty($applyPrevious))
					return ZMC::redirectPage('ZMC_Restore_What', $pm);
				if ($conflictResolvable)
					return ZMC::redirectPage('ZMC_Restore_How', $pm);

				return ZMC::redirectPage('ZMC_Restore_Now', $pm);

			default:
				break;
		}
	}
}
