<?













class ZMC_Backup_How extends ZMC_Backup
{
static $param2where = array(
		'ctimeout' => 'amanda.conf',
		'dtimeout' => 'amanda.conf',
		'dumporder' => 'amanda.conf',
		'dumptype_free_style_key' => 'zmc_backupset_dumptypes',
		'etimeout' => 'amanda.conf',
		'global_free_style_key' => 'amanda.conf',
		'inparallel' => 'amanda.conf',
		'mailto' => 'amanda.conf',
		'maxdumps' => 'amanda.conf',
		'record' => 'zmc_backupset_dumptypes',
		'reserved_tcp_port' => 'amanda.conf',
		'taperalgo' => 'amanda.conf',
		'ctimeout_display' => 'binding_conf',
		'dtimeout_display' => 'binding_conf',
		'etimeout_display' => 'binding_conf',
		'taper_parallel_write' => 'binding_conf',

);

public static function run(ZMC_Registry_MessageBox $pm)
{
	ZMC_HeaderFooter::$instance->header($pm, 'Backup', 'ZMC - How would you like to make a backup?', 'how');
	$howPage = new self($pm);
	$howPage->getSelectedBinding($pm);
	$howPage->runState($pm);

	ZMC_BackupSet::getPaginator($pm);
	
	





	foreach(self::$param2where as $name => $where)
		if (!empty($_POST[$name]))
			$pm->conf[$name] = $_POST[$name]; 
	
	if(!isset($pm->conf['record']) && isset($pm->conf['dumptype_list']['zmc_backupset_dumptype']['record']))
		$pm->conf['record'] = $pm->conf['dumptype_list']['zmc_backupset_dumptype']['record'];

	if (empty($_POST['record']))
		$pm->record = (empty($pm->conf['record']) || ($pm->conf['record'] === 'on') || ($pm->conf['record'] === 'Yes'));
	else
		$pm->record = ($_POST['record'] === 'Yes');

	if (isset($pm->conf['record']))
		if ($pm->conf['record'] === 'off' || $pm->conf['record'] === 'No')
			$pm->addWarning('Client hosts will not record backups for this backup set.  If another backup set asks one of the Backup|What client hosts to perform a "recorded" backup, the client will calculate which files to include in the backup without using history of backups from this backup set. The "record" feature only works for filesystem type entries on the Backup|What page.');

	return 'BackupHow';
}

protected function runState(ZMC_Registry_MessageBox $pm)
{
	
	
	switch($pm->state)
	{
		case 'Update': 
			$pm->next_state = 'Edit';
			try
			{

				$this->filter($pm, $_POST);
				$pm->conf = $_POST;
				if (!$pm->isErrors())
				{
					if (ZMC_User::hasRole('Administrator')) 
						foreach(array(	'global_free_style_key' => 'global_free_style_value',
										'dumptype_free_style_key' => 'dumptype_free_style_value') as $key => $value)
							if (($_POST[$key]))
							{
								$userKey = str_replace('-', '_', $_POST[$key]);
								self::$param2where[$userKey] = self::$param2where[$key];
								$_POST[$userKey] = (($_POST[$value] === '') ? null : $_POST[$value]);
							}

					foreach(array('global_free_style_value', 'global_free_style_key', 'dumptype_free_style_value', 'dumptype_free_style_key') as $key)
						unset($_POST[$key]);

					$globalParams = $_POST;
					$msg = '';
					ZMC::array_move($globalParams, $dumptypeParams, array_keys(self::$param2where, 'zmc_backupset_dumptypes'));
					ZMC::array_move($globalParams, $bindingParams, array_keys(self::$param2where, 'binding_conf'));
					if (ZMC_BackupSet::modifyConf($pm, $pm->edit['configuration_name'], $globalParams, 'amanda.conf'))
						$pm->addMessage($msg = "Global updates saved.");
					else
						$pm->addWarnError($msg = "Global updates failed.");

					if (!empty($dumptypeParams))
					{
						if (ZMC_BackupSet::modifyConf($pm, $pm->edit['configuration_name'], array('dumptype_list' => array('zmc_backupset_dumptype' => $dumptypeParams)), 'zmc_backupset_dumptypes'))
							$pm->addMessage($msg .= " Dumptype updates saved.");
						else
							$pm->addWarnError($msg .= " Dumptype updates failed.");
					}

					ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
					if(!empty($bindingParams)){
						$this->getBindingList($pm);

						$yml_path = ZMC::$registry->etc_amanda.$pm->edit['profile_name']."/binding-".$pm->edit['profile_name'].".yml";
						foreach($bindingParams as $key => $val){

							if($key === "taper_parallel_write"){
								if($val <= 0){
									$pm->addError("Media parallel write ($val) must be greater than 0.");
									break;
								}
								if($pm->binding['_key_name'] === 'changer_library'){
									if(isset($pm->binding['changer']['tapedev']) && count($pm->binding['changer']['tapedev']) > 0){
										$fil_array= array();
										foreach ($pm->binding['changer']['tapedev'] as $k=>$v){
											if( $v !== 'skip')
												$fil_array[$k] = $v;
										}
										if(count($fil_array) < $val){
											$pm->addError("Media parallel write ($val) should not exceeds with total number of selected tape drives (". count($fil_array).").");
											break;
										}
									}
								}	
								$pm->binding_list[$yml_path][$key] = $val;
								$pm->binding[$key] = $val;
							}
							else
								$pm->binding['backup_timeout_list'][$key] = $val;
						}
						$update = 'merge_and_apply';
						if($pm->binding['_key_name'] == "s3_cloud"){
							unset($pm->binding['device_property_list']['MAX_RECV_SPEED']);
							unset($pm->binding['device_property_list']['MAX_SEND_SPEED']);
						}
						$result = ZMC_Yasumi::operation($pm, array(
							'pathInfo' => '/Device-Binding/' . ($update ? 'merge_and_apply/' : 'create/') . $pm->selected_name,
							'data' => array(
								'commit_comment' => $pm->tombstone . '|' . $pm->subnav . ' add/update device binding',
								'binding_name' => $pm->binding['private']['zmc_device_name'], 
								'binding_conf' => $pm->binding,
								),
							));	
						$pm->merge($result);
					}

					break;
				}
			}
			catch (Exception $e)
			{
				$pm->addYasumiServiceException($e); 
				ZMC::auditLog('Edit of backup set "' . $pm->edit['configuration_name'] . "\" failed: $e", $e->getCode(), null, ZMC_Error::ERROR);
			}
			break;

		case 'Cancel':
			ZMC_BackupSet::cancelEdit();
			$pm->offsetUnset('edit');
			$pm->addWarning("Edit/Add cancelled.");
			break;

		default:
			case 'Edit':
			if(empty($pm->binding['backup_timeout_list']['ctimeout_display']) && empty($pm->binding['backup_timeout_list']['dtimeout_display']) && empty($pm->binding['backup_timeout_list']['etimeout_display']))
				$pm->binding['backup_timeout_list']['ctimeout_display'] = $pm->binding['backup_timeout_list']['dtimeout_display'] = $pm->binding['backup_timeout_list']['etimeout_display'] = "minutes";

			if(!isset($pm->binding['taper_parallel_write'])){
				if($pm->binding['_key_name'] === 'changer_library')
					if(isset($pm->binding['changer']['tapedev']))
					{
						$i =0;
						foreach($pm->binding['changer']['tapedev'] as $k=> $v)
							if($v != 'skip')
								$i++;
						$pm->binding['taper_parallel_write'] = $i;
					}else
						$pm->binding['taper_parallel_write'] = 0;
				elseif($pm->binding['_key_name'] === 'changer_ndmp')
					$pm->binding['taper_parallel_write'] = 0;
				elseif($pm->binging['_key_name'] == 'attached_storage')
					$pm->binding['taper_parallel_write'] = 4;
				else
					$pm->binding['taper_parallel_write'] = 1;
			}else{
				if($pm->binding['_key_name'] === 'changer_library'){
					if(isset($pm->binding['changer']['tapedev']))
					{
						$i =0;
						foreach($pm->binding['changer']['tapedev'] as $k=> $v){
							if($v != 'skip'){
								$i++;
							}
						}
						if($i <= $pm->binding['taper_parallel_write'])
							$pm->binding['taper_parallel_write'] = $i;
					}else
						$pm->binding['taper_parallel_write'] = 0;
				}

			}
			$pm->addDefaultInstruction('Fine tune advanced settings that affect how backups are done for this backup set.');
			if (empty($pm->edit))
				return $pm->addDefaultInstruction('Choose a backup set to fine tune advanced settings that affect how backups are done for this backup set.');
			$_POST = null; 
			ZMC_BackupSet::readConf($pm, $pm->selected_name, null, 'read', 'zmc_backupset_dumptypes');
			ZMC_BackupSet::readConf($pm, $pm->selected_name);
			break;
	}
}
protected function anuj($var){
	return $var;
}

protected function filter(ZMC_Registry_MessageBox $pm, &$post)
{
	unset($post['action']);
	unset($post['selected_ids']);
	if (!empty($post))
	{
		$warn = 'Timeouts apply to each DLE.  If a problem arises causing Amanda to wait for the entire timeout, backups could be delayed this long for each DLE triggering the timeout.  Thus, backups of other, successful DLEs might be significantly delayed. Instead, move DLEs requiring very long timeouts into their own backup set.';
		if(isset($_POST['etimeout']))
			$_POST['etimeout'] = ZMC::convertToSecondTimeout($_POST['etimeout'], $_POST['etimeout_display']);
		if(isset($_POST['ctimeout']))
			$_POST['ctimeout'] = ZMC::convertToSecondTimeout($_POST['ctimeout'], $_POST['ctimeout_display']);
		if(isset($_POST['dtimeout']))
			$_POST['dtimeout'] = ZMC::convertToSecondTimeout($_POST['dtimeout'], $_POST['dtimeout_display']);
		


		if (!ZMC::isValidIntegerInRange($post['etimeout'], 3, 3600))
			$pm->addWarning($warn);
		if (!ZMC::isValidIntegerInRange($post['etimeout'], 3, 259200))
			$pm->addWarnError('Backup Estimate Time Out should be an integer between 3 and 259200 (seconds) (max 600 seconds recommended).');
	
		if (!ZMC::isValidIntegerInRange($post['ctimeout'], 0, 9999))
			$pm->addWarnError('Verification Time Out should be an integer between 0 and 9999 (seconds) (max 60 seconds recommended).');
	
		if (!ZMC::isValidIntegerInRange($post['dtimeout'], 0, 3600))
			$pm->addWarning($warn);
		if (!ZMC::isValidIntegerInRange($post['dtimeout'], 0, 259200))
			$pm->addWarnError('Data Time Out should be an integer between 0 and 259200 (seconds) (max 1800 seconds recommended).');
	
		if (empty($post['taperalgo']))
			$pm->addWarnError('Please enter a value for Media Utilization.');
		else
			$post['taperalgo'] = strtolower($post['taperalgo']);
	
		if (!ZMC::isValidIntegerInRange($post['inparallel'], 1, 999))
			$pm->addWarnError('Server Parallel Backups should be an integer between 1 and 999 (less than 10 recommended).');
	
		if (empty($post['dumporder']))
			$pm->addWarnError('Please enter a value for Backup Order.');
	
		$pattern = '[^[sStTbB]+$]';
		if (!preg_match($pattern, $post['dumporder']))
			$pm->addWarnError('Invalid characters in Backup Order.  Allowable characters are \'s\', \'S\', \'t\', \'T\', \'b\' and \'B\'');
	
		if (!ZMC::isValidIntegerInRange($post['maxdumps'], 1, 63))
			$pm->addWarnError('Client Parallel Backups should be an integer between 1 and 63 (less than 10 recommended).');

		if (empty($post['reserved_tcp_port']))
			$post['reserved_tcp_port'] = '700-710';
		else
		{
			$ports = preg_split('/\D+/', trim($post['reserved_tcp_port']));
			if (count($ports) !== 2 || empty($ports[0]) || $ports[1] < $ports[0] || $ports[0] < 1 || $ports[1] > 1023)
				$pm->addWarnError("Port range \"$ports[0]-$ports[1]\" must specify a valid range of TCP ports (e.g. \"1000-1023\"), some of which should not be listed in /etc/services (only unlisted ports will be used).");
			else
				$post['reserved_tcp_port'] = "$ports[0],$ports[1]";
		}

		if (!empty($post['mailto']))
		{
			$cleaned = array();
			$emails = explode(",", strtr($post['mailto'], "\r\n ", ',,,'));
			foreach($emails as $email)
			{
				$trimmed = trim($email);
				if (empty($trimmed))
					continue;
				if(!ZMC_User::isValidAmandaEmail($trimmed))
					$pm->addWarnError('The address '.$email.' is not a valid email address');
				$cleaned[] = $trimmed;
			}
			$post['mailto'] = join(' ', $cleaned);
		}
	}
}
} 
