<?













class ZMC_Backup extends ZMC_Form {

protected $editId = 'edit_id';
protected $defaultEditAction = 'Edit';
protected $requireBackupSet = false;

protected function __construct($pm)
{
	if (!empty($pm->state))
		return $this;
	
	ZMC_HeaderFooter::$instance->addYui('wocloud-utils', array('dom', 'event', 'connection'));
	ZMC_HeaderFooter::$instance->addYui('wocloud-messagebox', array('dom', 'event', 'connection'));
	$pm->state = (empty($_REQUEST['action']) ? '' : $_REQUEST['action']);
	if($pm->state === 'DuplicateConfirm')
		$pm['duplicate_backupset_name'] = $_REQUEST['duplicate_backupset_name'];
	$creatingNewSet = ($pm->state === 'Add');
	if ($pm->state === 'Cancel')
		$_REQUEST[$this->editId] = null;
	$pm->edit = null;
	if (ZMC_BackupSet::select($pm, $this->requireBackupSet, $this->editId, !$creatingNewSet, $creatingNewSet) && !$creatingNewSet)
		$pm->edit = ZMC_BackupSet::getByName($pm->selected_name);
	if ((!$creatingNewSet && empty($pm->edit) && ZMC_BackupSet::count())
		&& (($pm->state === 'Delete') && (count($_REQUEST['selected_ids']) < 2)))
			$pm->addMessage('请选择一个备份集');
	$pm->goto = null;
	if ($pm->state === 'Refresh Table' || $pm->state === 'Refresh') 
		$pm->state = (empty($_POST['pm_state']) ? 'Refresh' : $_POST['pm_state']);
	if (empty($pm->state) && !empty($pm->selected_name))
		$pm->state = $this->defaultEditAction;
	
	$pm->sets = ZMC_BackupSet::getMySets();
	return $this;
}

protected function getBindingList(ZMC_Registry_MessageBox $pm, $tapelistConfName = '')
{
	if (isset($pm->binding_list))
		return;

	$pm->binding_list = array();
	if (!count(ZMC_BackupSet::getMyNames()))
		return;

	try
	{
		$result = ZMC_Yasumi::operation($pm, array(
			'pathInfo' => "/Device-Binding/all",
			'data' => array(
				'only_sets' => ZMC_BackupSet::getMyNames(),
				'errors_only_for' => empty($pm->selected_name) ? '' : $pm->selected_name,
				'tapelist' => $tapelistConfName
			),
		));
		unset($result['request']);
		ZMC_DeviceFilter::filterNamedList($pm, $result['binding_list']);
		if (!isset($_REQUEST['action']) || ($_REQUEST['action'] !== 'Update'))
			$pm->merge($result);
		else
		{
			$pm->lstats = $result['lstats'];
			$pm->binding_list = $result['binding_list'];
			$pm->device_profile_list = $result['device_profile_list'];
		}
	}
	catch(Exception $e)
	{
		return $pm->addInternal("An unexpected problem occurred while reading the list of devices associated with backup sets: $e");
	}
}

protected function getSelectedBinding(ZMC_Registry_MessageBox $pm)
{
	if (    empty($pm->selected_name)
		||  empty($pm->sets[$pm->selected_name])
		||  empty($pm->sets[$pm->selected_name]['profile_name'])
		||	$pm->sets[$pm->selected_name]['profile_name'] === 'NONE')
		return false;

	$this->getBindingList($pm, $pm->selected_name);
	$path = ZMC::$registry->etc_amanda . $pm->selected_name . '/';
	$length = strlen($path);
	foreach(array_keys($pm->binding_list) as $bid)
		if (!strncmp($bid, $path, $length))
		{
			if (empty($bid) || !isset($pm->binding_list[$bid]))
			{
				if ($pm->subnav !== 'where') 
					$pm->addEscapedWarning($bid . ' Device settings must be '
						. ZMC::getPageUrl($pm, 'Backup', 'where', 'created for the backup set \'' . ZMC::escape($pm->selected_name) . "'")
						. ", before &quot;$pm->subnav&quot; settings can be configured for this backup set.");
				if ($pm->state !== 'Refresh Table')
					$this->runstate($pm, 'Refresh Table');
				return false;
			}
			$pm->binding = $pm->binding_list[$bid];
			return $bid;
		}

	return false;
}

protected function inputFilter(ZMC_Registry_MessageBox $pm)
{
	return ZMC_DeviceFilter::filter($pm, 'input', $_POST, $this->zmc_type_class);
}

protected function updateAdd(ZMC_Registry_MessageBox $pm, $update)
{
	$pm->next_state = 'Edit'; 
	$pm->binding = $this->inputFilter($pm);
	if(preg_match('/\/ZMC_Backup_Staging/', $pm->url))
		$this->checkHoldingDiskSpace($pm);

	if ($pm->isErrors()){
		if(preg_match('/\/ZMC_Backup_When/', $pm->url)){
			$pm->next_state = 'Edit';
			$this->runstate($pm, 'Edit');
			return;
		}
		return $this->buildFormWrapper($pm);
	}

	$e = null;
	try
	{
		$this->validateForm($pm);
		
		if(isset($pm->binding['private:bandwidth_toggle']) && $pm->binding['private:bandwidth_toggle'] == 'on'){
			unset($pm->binding['device_property_list:MAX_SEND_SPEED']);
			unset($pm->binding['device_property_list:MAX_RECV_SPEED']);
			unset($pm->binding['device_property_list:NB_THREADS_BACKUP']);
			unset($pm->binding['device_property_list:NB_THREADS_RECOVERY']);
		}
		if($_POST['_key_name'] !== 'changer_ndmp' || $_POST['_key_name'] !== 'changer_library'){
			if(isset($pm['sets'][$pm->selected_name]['initial_retention'])){
				$pm->binding['private:initial_retention'] = $pm['sets'][$pm->selected_name]['initial_retention'] . ' days';
			}
		}

		$this->getBindingList($pm, $pm->selectedName);
		foreach($pm->binding_list as $key => &$binding){
			if($binding['config_name'] === $pm->selected_name){
				$orig_tapecycle = $binding['schedule']['tapecycle'];
				$orig_runtapes = $binding['schedule']['runtapes'];
				$orig_dumpcycle = $binding['schedule']['dumpcycle'];
				$orig_retention = $binding['schedule']['desired_retention_period'];
				break;
			}
		}

		if($_POST['tapecycle'] < $_POST['runtapes']){
			$pm->binding['schedule:tapecycle'] = $orig_tapecycle;
			$pm->binding['schedule:runtapes'] = $orig_runtapes;
		}
		
		if($_POST['dumpcycle'] > $_POST['desired_retention_period']){
			$pm->binding['schedule:dumpcycle'] = $orig_dumpcycle;
			$pm->binding['schedule:desired_retention_period'] = $orig_retention;
		}
		
		$result = ZMC_Yasumi::operation($pm, array(
			'pathInfo' => '/Device-Binding/' . ($update ? 'merge_and_apply/' : 'create/') . $pm->selected_name,
			'data' => array(
				'commit_comment' => $pm->tombstone . '|' . $pm->subnav . ' add/update device binding',
				'binding_name' => $pm->binding['private:zmc_device_name'], 
				'binding_conf' => $pm->binding,
			),
		));
		
		if(isset($_POST['tapecycle'])) {
			if($_POST['tapecycle'] <= 0) {
				$pm->addEscapedWarning("<b>Failed to update \"Tapes in Rotation\"!</b>\n
						There must be at least 1 tape in rotation.\n
						Reset \"Tapes in Rotation\" to recommended value.");
			} elseif($_POST['tapecycle'] < $_POST['runtapes']) {
				if($_POST['tapecycle'] != $orig_tapecycle)
					$pm->addEscapedWarning("<b>Failed to update \"Tapes in Rotation\"!</b>\n
							Number of \"Tapes in Rotation\" ({$_POST['tapecycle']}) cannot be less than \"Tapes per Backup\" ({$result->binding_conf['schedule']['runtapes']}).\n
							\"Tapes in Rotation\" has been reverted back to original setting ({$orig_tapecycle}).");
				if($_POST['runtapes'] != $orig_runtapes)
					$pm->addEscapedWarning("<b>Failed to update \"Tapes per Backup\"!</b>\n
							Number of \"Tapes per Backup\" ({$_POST['runtapes']}) cannot be more than \"Tapes in Rotation\" ({$result->binding_conf['schedule']['tapecycle']}).\n
				\"Tapes per Backup\" has been reverted back to original setting ({$orig_runtapes}).");
			} else {
				if($_POST['tapecycle'] === '1')
					$pm->addEscapedWarning("<b>Only 1 tape in rotation!</b>\n
							Every backup run will overwrite the previous backup!\n
							See Backup|When to determine how many tapes your retention period requires.\n
							See Backup|Where to add additional tapes to this backup set.");
				$result->binding_conf['schedule']['tapecycle'] = $_POST['tapecycle'];
			}
		}
		
		if(isset($_POST['dumpcycle']) && isset($_POST['desired_retention_period']) && $_POST['dumpcycle'] > $_POST['desired_retention_period']){
			if($_POST['dumpcycle'] != $orig_dumpcycle)
				$pm->AddEscapedWarning("<b>Failed to update \"Backup Cycle\"!</b>\n
						\"Backup Cycle\" ({$_POST['dumpcycle']}) cannot be more than \"Retention Period\" ({$result->binding_conf['schedule']['desired_retention_period']}).\n
						Reset \"Backup Cycle\" to original setting ({$orig_dumpcycle}).");
			if($_POST['desired_retention_period'] != $orig_retention)
				$pm->AddEscapedWarning("<b>Failed to update \"Retention Period\"!</b>\n
						\"Retention Period\" ({$_POST['desired_retention_period']}) cannot be less than \"Backup Cycle\" ({$result->binding_conf['schedule']['dumpcycle']}).\n
						Reset \"Retention Period\" to original setting ({$orig_retention}).");
		}
		
		
		
		if (!$update){ 
			$status = ZMC_BackupSet::getStatus($pm, $pm->selected_name, false); 
			$status['profile_name'] = $pm->binding['private:zmc_device_name'];
			$status['device'] = $pm->binding['private:zmc_device_name'];
			ZMC_BackupSet::updateStatus($pm->selected_name, $status);
		}

		unset($result['device_profile_list']);
		$pm->merge($result);
	}
	catch (ZMC_Exception $e)
	{
	}

	if (!empty($pm->fatal) || $pm->isErrors())
	{
		$pm->addError('备份集'. $msg = $pm->selected_name . '' . ($update ? '更新' : '新建') . '配置设备' . $pm->binding['private:zmc_device_name'] . "失败: $e");
		return $this->buildFormWrapper($pm);
	}

	$pm->binding = ZMC_DeviceFilter::filter($pm, 'read', $pm->binding_conf);
	unset($pm->binding_conf);
	$this->buildFormWrapper($pm);

	if (!$update)
		ZMC_Paginator_Reset::reset('last_modified_time'); 

	$pm->addMessage('备份集'. $msg = $pm->selected_name . ''. ($update ? '更新' : '创建') .'设备"' . $pm->binding['private']['zmc_device_name'] . '"的配置.');
	ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
}

protected function isTape(ZMC_Registry_MessageBox $pm, $binding)
{
	$deviceName = null;
	if (isset($binding['private:zmc_device_name']))
		$deviceName = $binding['private:zmc_device_name'];
	elseif (isset($binding['private']['zmc_device_name']))
		$deviceName = $binding['private']['zmc_device_name'];

	if (empty($deviceName) || empty($pm->device_profile_list) || empty($pm->device_profile_list[$deviceName]))
		return false;

	return ($pm->device_profile_list[$deviceName]['dev_meta']['media_type'] === 'tape');
}
}
