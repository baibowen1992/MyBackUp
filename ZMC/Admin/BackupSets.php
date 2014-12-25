<?













class ZMC_Admin_BackupSets extends ZMC_Backup
{
const SUBNAV = 'backup sets';
protected $editId = 'edit_id';

public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Admin', $title = 'ZMC - Backup Set Management', $subnav = self::SUBNAV)
{
	ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
	$pm->addDefaultInstruction('Administer backup sets - create, edit, view, delete backup sets');
	$pm->users = ZMC_User::$users; 
	$page = new self($pm);
	if (!ZMC_User::hasRole('Administrator') && !ZMC_User::hasRole('Operator'))
	{
		ZMC_BackupSet::getPaginator($pm);
		return 'AdminBackupSets';
	}

	
	

	$isBackupActivate = !($pm->subnav === self::SUBNAV); 
	if (ZMC::$registry->sync_always && empty($pm->post_login)) 
	{
		$skip = '';
		
		if ($isBackupActivate) 
			switch($pm->state)
			{
				case 'Refresh Table':
				case 'Refresh':
					$problems = ZMC_BackupSet::syncAmandaConfig($pm, $pm->selected_name); 
					if (!empty($pm->selected_name))
						$pm->state = 'Edit';
					break;

				default:
				case 'Edit':
					$problems = ZMC_BackupSet::syncAmandaConfig($pm, null, $pm->selected_name); 
					break;

				case '':
					$problems = ZMC_BackupSet::syncAmandaConfig($pm); 
					if (!empty($pm->selected_name))
						$pm->state = 'Edit';
					break;

				case 'Cancel':
			}
		else 
			switch($pm->state)
			{
				case 'Abort':
				case 'Delete': 
					break;

				case 'AbortConfirm':
				case 'DuplicateConfirm':
				case 'DeleteConfirm':
					$problems = ZMC_BackupSet::syncAmandaConfig($pm, $pm->selected_name); 
					break;

				case 'Cancel':
					$pm->addWarning("Edit/Add cancelled.");
				case 'New':
					ZMC_BackupSet::cancelEdit();
					$pm->selected_name = '';
					$pm->edit = null;
					
					break;

				case 'Refresh Table':
				case 'Refresh':
					if (!empty($_POST['edit_id']))
						$pm->state = 'Update';
					$problems = ZMC_BackupSet::syncAmandaConfig($pm); 
					break;

				case 'Activate':
				case 'Activate Now':
				case 'Add':
				case 'Deactivate':
				case 'Deactivate Now':
				case 'Edit':
				case 'Migrate':
				case 'MigrateConfirm':
				case 'MigrateDone':
				case 'Start Backup Now':
				case 'Monitor Backup Now':
				case 'Update':
					if (ZMC::$registry->qa_mode && empty($pm->selected_name))
						$pm->addError("Unable to identify the active backup set", print_r(array('request' => $_REQUEST, 'pm' => $pm), true));
				default:
					$problems = ZMC_BackupSet::syncAmandaConfig($pm, null, $pm->selected_name); 
					break;
			}
	}

	return $page->runState($pm);
}

protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
{
	$pm->what = '<a href="'. ($pm->what = ZMC_HeaderFooter::$instance->getUrl('Backup', 'what')) . '">Backup|What page</a>';
	$pm->admin = '<a href="'. ZMC_HeaderFooter::$instance->getUrl('Admin', 'backup sets') . '">Admin|backup sets page</a>';
	if (!empty($state))
		$pm->state = $state;

	$template = 'AdminBackupSets';
	switch($pm->state)
	{
		case 'Disklist':
			if (empty($pm->selected_name)) ZMC::quit($pm);
			return ZMC::headerRedirect("/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/{$pm->selected_name}/disklist.conf", __FILE__, __LINE__);

		case 'Update': 
		case 'Add': 
			if ($pm->state === 'Add')
				$name = str_replace(' ', '_', trim($_POST[$this->editId]));
			else
				$name = ZMC_BackupSet::getName(); 

			if (ZMC_Admin_Devices::get($pm, $name))
			{
				$pm->addWarnError("A ZMC device named '$name' already exists. ZMC backup sets can not have the same name as a ZMC storage device.  Please choose a different backup set name.");
			}
			$pm->edit = $_POST;
			if ($pm->isErrors())
				break;

			if (empty($name))
			{
				$pm->addError('Please enter a valid backup set name.');
				break;
			}

			$this->filterAndSave($pm, $name);
			if (!$pm->isErrors())
				$pm->next_state = 'Edit';
			break;

		case 'Edit':
			$pm->edit = ZMC_BackupSet::getByName($pm->selected_name);
			if ($pm->edit['version'] !==  ZMC::$registry->zmc_backupset_version)
			{
				$pm->addError('Unable to edit ' . $pm->edit['configuration_name'] . ', because it must be upgraded from version '
					. $pm->edit['version'] . ' to version ' . ZMC::$registry->zmc_backupset_version);
				$pm->edit = null;
				return $this->runState($pm, 'Refresh');
			}
			if (empty($pm->edit))
			{
				$pm->addError("Unable to find backup set '$name'.");
				break;
			}
			if (!ZMC_BackupSet::readConf($pm, $pm->edit['configuration_name']))
				return $this->runState($pm, 'Refresh');
			$pm->edit['display_unit'] = 'm';
			if (isset($pm->conf))
			{
				$pm->edit['display_unit'] = (isset($pm->conf['displayunit']) ? $pm->conf['displayunit'] : 'm');
				$pm->edit['org'] = (isset($pm->conf['org']) ? $pm->conf['org'] : '');
			}
			break;

		case 'Abort':
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_help = 'Confirm Abort Backup/Restore Operation(s)';
			$pm->addMessage('Aborting a backup set will stop all backups and restores for the backup set and reset it to a clean state.');
			foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
				$this->vtapesMessage($pm, $name);
			$pm->addWarning('There is no undo.');
			$pm->prompt ='Are you sure you want to ABORT the backup/restore operation(s) for the backup set(s)?<br /><ul>'
				. '<li>'
				. implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
				. "\n</ul>\n";
			$pm->confirm_action = 'AbortConfirm';
			$pm->yes = 'Abort';
			$pm->no = 'Cancel';
			break;

		case 'AbortConfirm':
			if (!isset($_POST['ConfirmationYes']))
				$pm->addWarning('Abort cancelled.');
			else
				foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
					if (!ZMC_BackupSet::abort($pm, $name))
						$pm->addError("Unable to abort backup set: $name");
					else
						$pm->addMessage("Backup/Restore/Vault cancelled for: $name");
			break;

		case 'Delete':
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_help = 'Confirm Deletion of Backup Sets';
			$pm->addMessage('Deleting a backup set removes the given backup set and all the settings associated with it from the ZMC, although completed backups are not erased.  Disk backups can be manually deleted.  Tapes can be relabelled using the "Backup|Media" page.');
			foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
				$this->vtapesMessage($pm, $name);
			$pm->addWarning('There is no undo.');
			$pm->prompt ='Are you sure you want to DELETE the backup set(s)?<br /><ul>'
				. '<li>'
				. implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
				. "\n</ul>\n";
			
			
			$pm->prompt .= "<br style=\"clear:left\"><input id='purge_media' type='checkbox' name='purge_media' /><label for='purge_media'> Purge backup images and staging area?</label>\n";
			$pm->prompt .= "<br style=\"clear:left\"><input id='purge_vault_media' type='checkbox' name='purge_vault_media' /><label for='purge_vault_media'> Purge vault media?</label>\n";
			$pm->confirm_action = 'DeleteConfirm';
			$pm->yes = 'Delete';
			$pm->no = 'Cancel';
			break;

		case 'DeleteConfirm':
			$pm->selected_name = '';
			$pm->edit = null;
			if (!isset($_POST['ConfirmationYes']))
				$pm->addWarning('Deletion cancelled.');
			else
				foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
					if (!ZMC_BackupSet::rm($pm, $name, !empty($_POST['purge_media']), !empty($_POST['purge_vault_media'])))
						$pm->addError("Unable to delete backup set: $name");

			if (ZMC_BackupSet::count() == 0)
				$pm->addWarning("No usable backup sets remain.");
			break;

		case 'Duplicate':
			$set = ZMC_BackupSet::getByName($name = key(ZMC::$userRegistry['selected_ids']));
			if ($set['version'] !== ZMC::$registry->zmc_backupset_version)
			{
				$pm->addError("The backup set \"$name\" must be migrated to the current version of " . ZMC::$registry->short_name . ' ' . ZMC::$registry->long_name . ', before it can be duplicated.');
				break;
			}
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_action = 'DuplicateConfirm';
			$pm->confirm_help = 'Duplication';
			$pm->addMessage("Duplicating \"$name\"");
			$pm->addMessage('Please enter a name for the duplicated backup set.');
			$pm->prompt = '<div class="p"><label class="zmcLongLabel">New backup set name:</label>
				<input type="text" name="duplicate_backupset_name" title="" id="ordinal" class="zmcLongInput" value="" />
					<input type="hidden" name="edit_id" id="ordinal" value="' . $name . '" /></div>';
	        $pm->yes = "Duplicate";
	        $pm->no = "Cancel";
			break;

		case 'DuplicateConfirm':
			if (!isset($_POST['ConfirmationYes']))
			{
				$pm->addWarning('Abort cancelled.');
				break;
			}
			
			$oldname = trim($_POST['edit_id']);
			
			if (!ZMC_BackupSet::readConf($pm, $oldname)) 
				return $this->runState($pm, 'Refresh');
			if (isset($pm->conf))
			{
				$_POST['display_unit'] = (isset($pm->conf['displayunit']) ? $pm->conf['displayunit'] : 'm');
				$_POST['org'] = (isset($pm->conf['org']) ? $pm->conf['org'] : '');
			}
			$_POST['edit_id'] = $_POST['duplicate_backupset_name'];
			$_POST['action'] = 'Add';
			
			$newname = trim($_POST['duplicate_backupset_name']);

			if (ZMC_Admin_Devices::get($pm, $newname))
			{
				$pm->addWarnError("A ZMC device named '$newname' already exists. ZMC backup sets can not have the same name as a ZMC storage device.  Please choose a different backup set name.");
			}
			$pm->edit = $_POST;
			if ($pm->isErrors())
				break;
			
			if (empty($newname))
			{
				$pm->addError('Please enter a valid backup set name.');
				break;
			}
			
			$this->filterAndSave($pm, $newname);
			if ($pm->isErrors())
				break;
			
			$disklist_fh = fopen("/etc/amanda/$oldname/disklist.conf", "r");
			$disklist_contents = '';
			while(!feof($disklist_fh)){
				$line = fgets($disklist_fh);
				if (preg_match("/^$oldname/", $line)){
					$disklist_contents .= $line;
					continue;
				}elseif(preg_match("/$oldname/", $line)){
					$disklist_contents .= str_replace($oldname, $newname, $line);
				}else{
					$disklist_contents .= $line; 
				}
			}
			fclose($disklist_fh);
			file_put_contents("/etc/amanda/$newname/disklist.conf", $disklist_contents);
			
			if (ZMC_BackupSet::getName() === $newname)
			{
				ZMC_Paginator_Reset::reset('creation_date');
				$pm['selected_name'] = $newname;
				return $this->runState($pm, 'Edit');
			}
			break;

		case 'Migrate':
			ZMC_BackupSet_Migration::runState($pm, $pm->state);
			break;

		case 'MigrateConfirm':
			ZMC_BackupSet_Migration::runState($pm, $pm->state);
			break;

		case 'MigrateDone':
			ZMC_BackupSet_Migration::runState($pm, $pm->state);
			break;

		case 'Activate Now':
		case 'Deactivate Now':
			ZMC_BackupSet::activate($pm, $pm->state === 'Activate Now');
			$this->runState($pm, 'Edit');
			break;

		case 'Activate':
		case 'Deactivate':
			foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
				ZMC_BackupSet::activate($pm, $pm->state === 'Activate', $name);
			break;
			
		case 'Select DLEs':
			$pm->backup_how = $_POST['backup_how'];
			$pm->addMessage("Select DLE(s) to backup.");		
			$pm->dles = array();
			try
			{
				$result = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => "/conf/read/{$pm->selected_name}",
						'data' => array(
								'what' => 'disklist.conf',
						)
				));
				$pm->merge($result);
			
				if ($pm->offsetExists('conf'))
					$pm->offsetUnset('conf');
			
				if (!empty($result['conf']) && !empty($result['conf']['dle_list']))
				foreach($result['conf']['dle_list'] as $id => &$dle)
				{
					$dle['natural_key'] = $id;
					$pm->dles[$id] =& $dle;
				}
			}
			catch (Exception $e)
			{
				$pm->addError("An unexpected problem occurred while reading and processing the object list '{$pm->selected_name}': $e");
				break;
			}
			
			$_POST['rows_per_page_sort'] = 100;
			$_POST['rows_per_page_orig'] = 100;

			$flattened =& ZMC::flattenArrays($pm->dles);
			$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
					'natural_key',
					'property_list:zmc_disklist',
					'property_list:zmc_type',
					'disk_name',
					'property_list:zmc_comments',
					'host_name',
					'disk_device',
					'L0',
					'Ln',
					'property_list:zmc_amcheck',
					'property_list:zmc_amcheck_version',
					'property_list:zmc_amcheck_platform',
					'property_list:zmc_dle_template',
					'encrypt',
					'compress',
					'property_list:last_modified_time',
					'property_list:last_modified_by',
					'property_list:zmc_amcheck_date',
					'property_list:zmc_status',
					'strategy'
			));
			$paginator->createColUrls($pm);
			$pm->rows = $paginator->get();
			return 'SelectiveBackup';

		case 'Start Backup Now':
			if(!empty(ZMC::$userRegistry['selected_ids'])){
				$dles_list = array();
				foreach(ZMC::$userRegistry['selected_ids'] as $id => $selected){
					list($disklist, $hostname, $diskname) = explode("|", $id);
					if(!empty($hostname) && !empty($diskname))
						if(isset($dles_list[$hostname]))
							$dles_list[$hostname][] = $diskname;
						else
							$dles_list[$hostname] = array($diskname);
				}
			}
			ob_clean();
 			switch(ZMC_BackupSet::startBackupNow($pm, $pm->selected_name, $dles_list, $_POST['backup_how']))
			{
				case ZMC_BackupSet::ABORTED:
				case ZMC_BackupSet::FAILED:
				case ZMC_BackupSet::FINISHED:
					$_GET['dayClickTimeStamp'] = strtotime('today');
					return ZMC::redirectPage('ZMC_Report_Backups', $pm, array(), array('dayClickTimeStamp' => time()));
			}
		case 'Monitor Backup Now':
			return ZMC::redirectPage('ZMC_Monitor', $pm);
		
		case 'Monitor Vault Now':
			return ZMC::redirectPage('ZMC_Vault_Jobs', $pm);

		default:
	}
	ZMC_BackupSet::getPaginator($pm);
	if ($pm->edit && $pm->state === 'Migrate')
		unset($pm->rows[$pm->edit['configuration_name']]['status']); 
	return $template;
}

protected function vtapesMessage(ZMC_Registry_MessageBox $pm, $name)
{
	$set = ZMC_BackupSet::getByName($name);
	if ($set['code'] == 401)
		$pm->addWarning("$name: Although this backup set does not exist on disk, a ZMC Database entry still exists.  The ZMC entry will be deleted.  If there are any vtapes associated with this backup remaining on disk, the vtapes will not be deleted.  After deleting the backup set, you may manually delete the vtape directory (if any exists) or move the directory for long-term archival. ");

	if (empty($set['profile_name']) || $set['profile_name'] === 'NONE')
		return;

	return;
	if (ZMC::$registry->dev_only)
		ZMC::quit('@TODO (depends on bug #:11014');

	if (($set['type'] === 'disk') || ($set['type'] === 's3'))
	{
		return $pm->addMessage("Deleting a backup set will not delete the vtape media.  After deleting this backup set, you may manually delete the directory containing the vtape media, or manually move the directory for long-term archival. ");
		
		
		return "Deleting a backup set will not delete the vtape media.  There are ".$device->slots." vtape
media currently in ".$device->tapedev."
After deleting the backup set, you may manually delete this directory or manually move
the directory for long-term archival. ";
	}
}

protected function filterAndSave(ZMC_Registry_MessageBox $pm, $name)
{
	$_POST['configuration_notes'] = (empty($_POST['configuration_notes']) ? '' : trim($_POST['configuration_notes']));
	$_POST['ownerSelect'] = (empty($_POST['ownerSelect']) ? $_SESSION['user_id'] : $_POST['ownerSelect']);
	$pm->edit = ($pm->state === 'Update' ? ZMC_BackupSet::get() : $pm->edit = array('configuration_name' => $name));
	$pm->edit['template'] = (empty($_POST['templateSelect']) ? '' : $_POST['templateSelect']);
	$pm->edit['owner_id'] = $_POST['ownerSelect'];
	$pm->edit['org'] = substr(trim($_POST['org']), 0, 24);
	if (empty($pm->edit['org']))
		$pm->edit['org'] = $name;
	$pm->edit['configuration_notes'] = $_POST['configuration_notes'];
	$pm->edit['display_unit'] = $_POST['display_unit'];

	if ($pm->state === 'Update')
	{
		ZMC_BackupSet::update($pm, $name, $_POST['configuration_notes'], $_POST['ownerSelect']); 
		if (((boolean)ZMC_BackupSet::isActivated($name)) != isset($_POST['active']))
			if (ZMC_BackupSet::activate($pm, isset($_POST['active']), $name))
				$pm->edit['active'] = isset($_POST['active']);
	}
	else
	{
		ZMC_Paginator_Reset::reset('creation_date');
		if (!ZMC_BackupSet::create($pm, $name, $_POST['configuration_notes'], $_POST['ownerSelect']))
			return;
	}

	switch ($_POST['display_unit'])
	{
		case 'k':
		case 'm':
		case 'g':
		case 't':
			break;

		default:
			$_POST['display_unit'] = 'm';
	}
	
	if (ZMC_BackupSet::modifyConf($pm, $name, array('org' => $pm->edit['org'], 'displayunit' => $pm->edit['display_unit'])))
		$pm->addMessage($pm->state . " backup set '$name' finished.");
	else
		$pm->addError($pm->state . " backup set '$name' failed.");
}
}
