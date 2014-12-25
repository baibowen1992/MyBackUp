<?













class ZMC_Admin_Devices extends ZMC_Form
{
	static $instance = null;

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		if (isset($_POST['action']) && ($_POST['action'] === 'List') && ZMC_User::hasRole('Administrator'))
		{
			$pm->selected_ids = $_POST['selected_ids'];
			return ZMC::redirectPage('ZMC_X_Cloud', $pm);
		}
		$pm->rows = $pm->lsscsi = $pm->edit = $pm->goto = null;
		ZMC_BackupSet::start($pm);
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', 'ZMC - Device Management', 'devices');
		ZMC_HeaderFooter::$instance->addYui('zmc-utils', array('dom', 'event', 'connection'));
		ZMC_HeaderFooter::$instance->addYui('zmc-messagebox', array('dom', 'event', 'connection'));
		$pm->state = (empty($_REQUEST['action']) ? '' : $_REQUEST['action']);
		if (!strncmp($pm->state, 'Refresh', 7) && !empty($_POST['_key_name']))
			$pm->state = $_POST['pm_state'];

		$devicesPage = new self($pm);
		$template = $devicesPage->runState($pm);
		$devicesPage->getPaginator($pm);
		return (empty($template) ? 'AdminDevices' : $template);
	}

	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		$update = false;
		if ($state !== null)
			$pm->state = $state;

		switch($pm->state)
		{
			case 'Expert':
				reset($_POST['selected_ids']);
				return ZMC::headerRedirect("/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_ags/device_profiles/" . key($_POST['selected_ids']) . ".yml", __FILE__, __LINE__);

			case 'Update': 
				$update = true;
			case 'Add': 
				if (!ZMC_User::hasRole('Administrator'))
				{
					$pm->addError('Only a ZMC administrator may ' . $pm->state . ' devices.');
					return;
				}

				if(isset($_POST['changer:changerdev_other']))
					$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers', 'data' => array('additional_changer_device_path' => array("'" . $_POST['changer:changerdev_other'] . "'" => 1))));
					
				$pm->binding = ZMC_DeviceFilter::filter($pm, 'input', $_POST, 'ZMC_Type_Devices');
				$realpath = realpath($pm->binding['changer:changerdev']);
				if ($rw = ZMC::is_readwrite($realpath, false))
					$pm->addError("Unable to use changerdevice at '{$pm->binding['changer:changerdev']} => $realpath' because the linkpoints to destination lacking read and/or write permissions.\n$rw");
				if (!ZMC::isalnum_($pm->binding['id']))
					$pm->addError('Use only alphanumeric characters or the underscore character for device names.  Illegal device name: ' . $pm->binding['id']);

				if (ZMC_BackupSet::getId($pm->binding['id']))
					$pm->addWarnError('A backup set named ' . $pm->binding['id'] . ' already exists. ZMC storage devices can not have the same name as a backup set.  Please choose a different device name.');

				if($pm->binding['_key_name'] === 'changer_ndmp' && !preg_match("/^.+@.+$/", $pm->binding['changerdev']))
					$pm->addError('Invalid "Application Location".  Must be of format &lt;IP&gt;@&lt;location&gt;.');
				
				if( $pm->binding['_key_name'] == "attached_storage" && (int)$pm->binding['tapetype:length'] < 0)
					$pm->addError("Tape size should not be negative value.");
				
				ZMC_BackupSet::isValidName($pm, $pm->binding['id']);
				if ($pm->isErrors() || $_REQUEST['action'] !== $pm->state)
				{
					$this->buildFormWrapper($pm);
					break;
				}

				try
				{
					$result = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => '/Device-Profile/' . ($update ? 'merge' : 'create'), 
						'data' => array(
							'commit_comment' => 'Admin|devices add/update device profile',
							'message_type' => 'Device Profile Edit',
							'device_profile_list' => array($pm->binding['id'] => $pm->binding)
						),
					));
					$pm->merge($result);
					ZMC_Type_Devices::addExpireWarnings($pm);
					ZMC_DeviceFilter::filterNamedList($pm, $pm->device_profile_list);
				}
				catch(Exception $e)
				{}

				if (empty($pm->fatal))
				{
					!$update && ZMC_Paginator_Reset::reset('last_modified_time'); 
					$pm->addMessage($msg = "Device '" . $pm->binding['id'] . ($update ? "' updated." : "' added."));
					ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
					$this->runState($pm, 'Create1');
					break;
				}

				if (empty($e))
					ZMC::auditLog(($update ? 'Edit' : 'Create') . ' of device "' . $pm->binding['id'] . "\" failed: " . $pm->getAllMerged(), 500, null, ZMC_Error::ERROR);

				$this->buildFormWrapper($pm);
				break;

			case 'Edit':
				$this->getDeviceList($pm);
				ZMC_BackupSet::addEditWarning($pm, true);
				$id = self::getEditId($pm, 'edit_id');
				if (empty($id))
					return $this->runState($pm, 'Create1');
				$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers', 'data' => array('additional_changer_device_path' => array("'" . $pm->binding['changer:changerdev'] . "'" => 1))));
				if (isset($pm->device_profile_list[$id]))
				{
					$pm->binding = $pm->device_profile_list[$id];
					if (!empty($_POST['pm_state'])) 
						$pm->binding = ZMC_DeviceFilter::filter($pm, 'input', $_POST, 'ZMC_Type_Devices');
					$pm->form_type = ZMC_Type_Devices::get($pm->binding['_key_name'], false);
					$this->buildFormWrapper($pm);
				}
				else
				{
					$pm->addError("Device '$id' does not exist.");
					$pm->state = 'Create1';
					$this->runState($pm);
				}
				break;

			case 'Delete':
				if (!ZMC_User::hasRole('Administrator'))
					$pm->addError('Only a ZMC administrator may ' . $pm->state . ' devices.');
				$used = array();
				foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
					if (count(ZMC_BackupSet::getNamesUsing($name, false)))
					{
						$used[] = $name;
						$tmp =& ZMC::$userRegistry['selected_ids']; 
						unset($tmp[$name]);
					}

				if (!empty($used))
				{
				   	$pm->addWarnError('Only unused devices may be deleted.  Delete all backup sets using a device, before deleting the device.  These devices are currently used by backup sets, and can not be deleted: ' . implode(', ', $used));
					if (empty(ZMC::$userRegistry['selected_ids'])) 
						return $this->runState($pm, 'Refresh');
				}
				$pm->confirm_template = 'ConfirmationWindow';
				$pm->addWarning('There is no undo.');
				$pm->prompt ='Are you sure you want to DELETE the device(s)?<br /><ul>'
					. '<li>'
					. implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
					. "\n</ul>\n";
				$pm->confirm_action = 'DeleteConfirm';
				$pm->yes = 'Delete';
				$pm->no = 'Cancel';
				break;

			case 'DeleteConfirm':
				
				if (!isset($_POST['ConfirmationYes']))
					$pm->addWarning("Edit/Add cancelled.");
				else
				{
					try
					{
						$hosts = $disks = $device_profile_list = array();
						foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore)
							$device_profile_list[$id] = null;
	
						$names = implode(', ', array_keys($device_profile_list));
						$result = ZMC_Yasumi::operation($pm, array(
							'pathInfo' => '/Device-Profile/delete',
							'data' => array(
								'commit_comment' => 'Delete device(s)',
								'message_type' => 'Device Profile',
								'device_profile_list' => $device_profile_list,
							),
						));
						$pm->merge($result);
						ZMC::auditLog("Deleted device(s) $names", 0, null, ZMC_Error::NOTICE);
					}
					catch (Exception $e)
					{
						ZMC::auditLog("Deletion of device(s) $names failed.", $e->getCode(), null, ZMC_Error::ERROR);
					}
				}
				

			case 'Cancel':
				if ($pm->state === 'Cancel') 
					$pm->addWarning("Edit/Add cancelled.");
			default:
			case 'Refresh':
			case 'Refresh Table':
			case '': 
				$pm->state = 'Create1';
			case 'Create1':
				$pm->addDefaultInstruction('Administer storage devices for backup sets by choosing a type of device to add, or clicking on a device below to edit an existing device.');
				break;

			case 'Create2': 
				if (!empty($_POST['state']))
					$pm->binding = ZMC_DeviceFilter::filter($pm, 'input', $_POST, 'ZMC_Type_Devices');
				elseif (!empty($_REQUEST['_key_name']))
					$pm->binding = array('_key_name' => $_REQUEST['_key_name']);
				else
				{
					$pm->state = 'Create1';
					break;
				}
				ZMC_Type_Devices::mergeCreationDefaults($pm->binding, true);
				$pm->form_type = ZMC_Type_Devices::get($_REQUEST['_key_name'], false); 
				$this->buildFormWrapper($pm);
				break;
		}
	}
	
	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		$this->getDeviceList($pm); 
		if (empty($pm->device_profile_list))
			return;

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('id', false);

		$flattened =& ZMC::flattenArrays($pm->device_profile_list);
		foreach($flattened as &$device)
			$device = ZMC_DeviceFilter::filter($pm, 'output', $device);
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'_key_name',
			'id',
			'stderr',
			'changer:changerdev',
			'changer:comment',
			'private:used_with',
			'private:last_modified_time',
			'private:last_modified_by',
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}

	public function &getDeviceList(ZMC_Registry_MessageBox $pm, $name = '')
	{
		$return = false;
		if (empty($pm->device_profile_list))
		{
			try
			{
				$pm->device_profile_list = array();
				$result = ZMC_Yasumi::operation($pm, array(
					'pathInfo' => "/Device-Profile/read_profiles",
					'data' => array(
						'message_type' => 'admin devices read',
					),
				));
				unset($result['request']);
				$pm->merge($result);
				ZMC_Type_Devices::addExpireWarnings($pm);
				ZMC_DeviceFilter::filterNamedList($pm, $pm->device_profile_list);
			}
			catch(Exception $e)
			{
				$pm->addInternal("Unable to read device list: $e");
				return $return;
			}
		}
		if (empty($name))
			return $pm->device_profile_list;
		if (isset($pm->device_profile_list[$name]))
		{
			$device =& $pm->device_profile_list[$name];
			return $device;
		}
		return $return;
	}

	public static function &get(ZMC_Registry_MessageBox $pm, $name)
	{
		if (empty(self::$instance))
			self::$instance = new self($pm);
		return self::$instance->getDeviceList($pm, $name);
	}

	

















}
