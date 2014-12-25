<?
//zhoulin-admin-device












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
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', '云备份 - 设备管理', 'devices');
		ZMC_HeaderFooter::$instance->addYui('wocloud-utils', array('dom', 'event', 'connection'));
		ZMC_HeaderFooter::$instance->addYui('wocloud-messagebox', array('dom', 'event', 'connection'));
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
			case 'create':
//--edit by zhoulin,normal user can create device
//				if (!ZMC_User::hasRole('Administrator'))
//				{
//					$pm->addError('Only a ZMC administrator may ' . $pm->state . ' devices.');
//					return;
//				}

				if(isset($_POST['changer:changerdev_other']))
					$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers', 'data' => array('additional_changer_device_path' => array("'" . $_POST['changer:changerdev_other'] . "'" => 1))));
					
				$pm->binding = ZMC_DeviceFilter::filter($pm, 'input', $_POST, 'ZMC_Type_Devices');
				$realpath = realpath($pm->binding['changer:changerdev']);
				if ($rw = ZMC::is_readwrite($realpath, false))
					$pm->addError("不能在路径'{$pm->binding['changer:changerdev']} => $realpath' 下新建设备，因为缺少读写权限.\n$rw");
				if (!ZMC::isalnum_($pm->binding['id']))
					$pm->addError('仅支持数字字母以及下划线给设备命名，该设备名无效: ' . $pm->binding['id']);

				if (ZMC_BackupSet::getId($pm->binding['id']))
					$pm->addWarnError('名为 ' . $pm->binding['id'] . ' 的备份集已经存在，存储设备名不能和备份集名相同，请更换');

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
                //echo "johnnytest0>>>>>>>>>".$pm;
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
                    //echo "<br>johnnytest1>>>>>>>>>".$result;
					$pm->merge($result);
                    //echo '<br>johnnytest2>>>>>>>>>'.$result;
					ZMC_Type_Devices::addExpireWarnings($pm);
					ZMC_DeviceFilter::filterNamedList($pm, $pm->device_profile_list);

                    //插入设备拥有者表
                    $jid = ZMC_User::insertDrivesOwner($_SESSION['user'],$pm->binding['id']);
                    //echo '插入设备拥有者表：'.$jid;
				}
				catch(Exception $e)
				{}

				if (empty($pm->fatal))
				{
					!$update && ZMC_Paginator_Reset::reset('last_modified_time'); 
					$pm->addMessage($msg = ($update ? " 更新." : " 新增")." 设备 '" . $pm->binding['id'] ."‘ 成功" );
					ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
					$this->runState($pm, 'Create1');
					break;
				}

				if (empty($e))
					ZMC::auditLog(($update ? 'Edit' : 'New') . ' 设备 "' . $pm->binding['id'] . "\" 失败: " . $pm->getAllMerged(), 500, null, ZMC_Error::ERROR);

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
					$pm->addError("设备 '$id' 不存在.");
					$pm->state = 'Create1';
					$this->runState($pm);
				}
				break;

			case 'Delete':
//				if (!ZMC_User::hasRole('Administrator'))
//					$pm->addError('只有管理员用户才能 ' . $pm->state . ' 设备.');
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
				   	$pm->addWarnError('只有不在使用中的设备才能被删除，请先删除所有绑定到该设备的备份集： ' . implode(', ', $used));
					if (empty(ZMC::$userRegistry['selected_ids'])) 
						return $this->runState($pm, 'Refresh');
				}
				$pm->confirm_template = 'ConfirmationWindow';
				$pm->addWarning('操作不可撤销');
				$pm->prompt ='你确定要删除这个设备吗?<br /><ul>'
					. '<li>'
					. implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
					. "\n</ul>\n";
				$pm->confirm_action = 'DeleteConfirm';
				$pm->yes = 'Delete';
				$pm->no = 'Cancel';
				break;

			case 'DeleteConfirm':
				
				if (!isset($_POST['ConfirmationYes']))
					$pm->addWarning("编辑/新增  取消");
				else
				{
					try
					{
						$hosts = $disks = $device_profile_list = array();
                        //add by johnny 20141030,delete devices.========start========
                        $delete_devices_name = '';
						foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore){
                            //echo $id.'< -------johnny test-------- >'.$ignore;
                            $delete_devices_name .= "'".$id."',";
							$device_profile_list[$id] = null;
                        }
                        //echo '<br>'.$delete_devices_name.' -------- > johnny test2';
                        $delete_devices_name=substr($delete_devices_name,0,strlen($delete_devices_name)-1);
                        //echo '<br>'.$delete_devices_name.' -------- > delete space johnny test3';
                        //add by johnny 20141030,delete devices.========end========


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

                        //执行删除操作  开始   johnny
                        ZMC_User::deleteDrivesOwner($delete_devices_name,$_SESSION['user']);
                        //执行删除操作  结束   johnny

						ZMC::auditLog("Deleted device(s) $names", 0, null, ZMC_Error::NOTICE);
					}
					catch (Exception $e)
					{
						ZMC::auditLog("Deletion of device(s) $names failed.", $e->getCode(), null, ZMC_Error::ERROR);
					}
				}
				

			case 'Cancel':
				if ($pm->state === 'Cancel')
					$pm->addWarning("编辑/新增  取消");
			default:
			case 'Refresh':
			case 'Refresh Table':
			case '': 
				$pm->state = 'Create1';
			case 'Create1':
				$pm->addDefaultInstruction('添加一种备份设备存储备份集需要备份的数据，或者编辑已经存在的备份设备。');
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
				$pm->addInternal("无法读取设备列表: $e");
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



    /*
     * add by Johnny
    ------------------------------------------------------
    参数：
    $str_cut    需要截断的字符串
    $length     允许字符串显示的最大长度
    程序功能：截取全角和半角（汉字和英文）混合的字符串以避免乱码
    ------------------------------------------------------
    */
    public static function substr_cut($str_cut,$length)
    {
        if (strlen($str_cut) > $length)
        {
            for($i=0; $i < $length; $i++)
                if (ord($str_cut[$i]) > 128)    $i++;
            $str_cut = substr($str_cut,0,$i)."..";
        }
        return $str_cut;
    }



}
