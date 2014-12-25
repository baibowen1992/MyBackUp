<?













class ZMC_Backup_Where extends ZMC_Backup {

protected $zmc_type_class = 'ZMC_Type_Where';

public static function run(ZMC_Registry_MessageBox $pm)
{
	ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - 选择备份存储设备', 'where');
	$pm->addDefaultInstruction('为备份集设置和编辑存储设备信息 (备份集如何使用存储设备)。');
	$wherePage = new self($pm);

	$wherePage->runState($pm);
    if (!empty($pm->binding))
	{
		$names = ZMC_Type_Devices::getPrettyNames();
		$pm->pretty_name = $names[$pm->binding['_key_name']];

		if($pm->binding['_key_name'] === "changer_library"){
			$verify_lock = ZMC::$registry->etc_amanda. $pm->binding['config_name']. "/binding-".$pm->binding['private']['zmc_device_name'].".verify_tape_drive_lock";
			if(file_exists($verify_lock)){
				$pm->addMessage("<img id='progress_spinner' style='float:right; margin:3px;' title='验证设备是否使用中' src='/images/global/calendar/icon_calendar_progress.gif'>验证存储设备是否使用中，这将花费不少的时间去检查每一个设备。");
				$pm->addWarning('警告：请确保只有一个用户在本页面执行交互操作，请等到操作完成后再进行下一个操作。');
			}
		}
	}
	if ($pm->state === 'Use1' && empty($pm->device_profile_list))
	{
		$pm->addError('请添加存储设备。');
		return ZMC::redirectPage('ZMC_Admin_Devices', $pm);
	}
	$pm->addMessage('在选好存储设备点击 添加 后，系统会尝试连接存储设备，受连接速度影响，这个过程可能花费一些时间。');
	$wherePage->getPaginator($pm);
	if (!empty($pm->binding) && $wherePage->isTape($pm, $pm->binding))
		ZMC_HeaderFooter::$instance->injectYuiCode('YAHOO.zmc.utils.show_lsscsi()');

	return 'BackupWhere';
}

protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
{
    //echo "==============>>3333333<<=====================";
	if (!empty($state))
		$pm->state = $state;
    //test zhoulin 20141127
    //$pm->state = 'Use';
    //$pm->selected_device='dtest22222';
    //print_r($pm);
    //第一次点击目的地导航时依次跑了Edit->runState->Use1;
	switch($pm->state)
	{
		case 'Expert':
			reset($_POST['selected_ids']);
			return ZMC::headerRedirect("/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$_REQUEST[config_name]/binding-" . $_REQUEST['private:zmc_device_name'] . ".yml", __FILE__, __LINE__);

		case 'Update':
			if($_POST['_key_name'] === 'changer_library'){
				$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers'));
				$pm->merge($reply);
				$_POST['changer:slotrange'] = $this->validateSlotRange($_POST['changer:slotrange'], $pm, $pm['changerdev_list'][$_POST['changer:changerdev']]['tape_slots']);
			}
			
			if($_POST['_key_name'] === 'changer_ndmp'){
				$_POST['property_list:use_slots'] = trim($_POST['property_list:use_slots']);
				if(!preg_match('/^[0-9]+-[0-9]+$/', $_POST['property_list:use_slots'])){
					$pm->addWarnError("Invalid Slots: \"{$_POST['property_list:use_slots']}\". Slots must be of format #-#");
				}	
				
				$_POST['property_list:tape_device'] = trim(str_replace("\"", "", $_POST['property_list:tape_device']));
				if(!preg_match('/^([0-9]+=ndmp:[\S]+@[\S]+[\s]+)*([0-9]+=ndmp:[\S]+@[\S]+){1}$/', $_POST['property_list:tape_device']))
					$pm->addWarnError("Invalid Tape Device: \"{$_POST['property_list:tape_device']}\". Tape Device must be a space-delimited list of &lt;digit&gt;=ndmp:&lt;IP&gt;@&lt;location&gt;");		
			}
			
			$this->updateAdd($pm, true);
			$pm->next_state = 'Edit';
			break;
		case 'Update & Verify Tape Drive':
			if($_POST['_key_name'] === 'changer_library'){

				$verify_lock = ZMC::$registry->etc_amanda. $pm->edit['configuration_name']. "/binding-".$pm->edit['device'].".verify_tape_drive_lock";
				list($date, $pid) = file($verify_lock, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
				if (empty($pid) && !file_exists("/proc/$pid")){
					$pm->addWarning("Found lock file $verify_lock, but no associated process.  Removed lock file."); 
					unlink($verify_lock);
				}
				if (!empty($pid) && file_exists("/proc/$pid")){
					$pm->addWarnError("Another ZMC task is using this changer. " . ucFirst($pm->state) . " aborted.  Please try again later."); 
					$pm->state = 'Edit';
					$this->runState($pm, 'Edit');
					return false;
				}
				
				$reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers'));
				$pm->merge($reply);
				$_POST['changer:slotrange'] = $this->validateSlotRange($_POST['changer:slotrange'], $pm, $pm['changerdev_list'][$_POST['changer:changerdev']]['tape_slots']);
		
				$this->updateAdd($pm, true);

				try{
					
					$result = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => '/label/verify_tape_drive/' . $pm->binding['config_name'],
						'data' => array(
						'commit_comment' => "Backup|Where Verify Tape Drive ",
						'binding_name' => $pm->binding['private']['zmc_device_name'],
						'tapedev' => $pm->binding['changer']['tapedev'],
						),
					));
					unset($result['request']);
				}catch (Exception $e){
					$pm->addError("An unexpected problem occurred while verifying tape devices:'. $e");
					return false;
				}
				$pm->next_state = 'Edit';
				$this->runState($pm, 'Edit');
			}
			
			
			
			

			break;

		case 'Add':
			if($_POST['_key_name'] === 'changer_library'){
				 $reply = ZMC_Yasumi::operation($pm, array('pathInfo' => '/Tape-Drive/discover_changers'));
				 $pm->merge($reply);
				 $_POST['changer:slotrange'] = $this->validateSlotRange($_POST['changer:slotrange'], $pm, $pm['changerdev_list'][$_POST['changer:changerdev']]['tape_slots']);
			}
			
			if($_POST['_key_name'] === 'changer_ndmp'){
				$_POST['property_list:use_slots'] = trim($_POST['property_list:use_slots']);
				if(!preg_match('/^[0-9]+-[0-9]+$/', $_POST['property_list:use_slots'])){
					$pm->addWarnError("Invalid Slot: \"{$_POST['property_list:use_slots']}\". Slots must be of format #-#");
				}
				
				$_POST['property_list:tape_device'] = trim(str_replace("\"", "", $_POST['property_list:tape_device']));
				if(!preg_match('/^([0-9]+=ndmp:[\S]+@[\S]+[\s]+)*([0-9]+=ndmp:[\S]+@[\S]+){1}$/', $_POST['property_list:tape_device']))
					$pm->addWarnError("Invalid Tape Device: \"{$_POST['property_list:tape_device']}\". Tape Device must be a space-delimited list of &lt;digit&gt;=ndmp:&lt;IP&gt;@&lt;location&gt;");		
			}
			
			$this->updateAdd($pm, false);
			if (!empty($pm->fatal)) 
			{
				$pm->selected_device = (empty($_REQUEST['private:zmc_device_name']) ? '' : $_REQUEST['private:zmc_device_name']);
				if (	empty($pm->selected_name) || empty($pm->selected_device)
					|| (!empty($_GET['action']) && $_GET['action'] === '添加' && empty($_REQUEST['config_name'])))
				{
					$this->runState($pm, 'Use1');
					break;
				}
				$reply = ZMC_Yasumi::operation($pm, array(
					'pathInfo' => '/Device-Binding/defaults/' . $pm->selected_name,
					'data' => array(
						'binding_name' => $pm->selected_device,
					),
				));
				$pm->binding =& $reply['binding_conf'];
				unset($reply);
				ZMC_Type_Where::mergeCreationDefaults($pm->binding, true);
				$pm->binding = ZMC_DeviceFilter::filter($pm, 'read', $pm->binding);
				try { $this->buildFormWrapper($pm); }
				catch (Exception $e)
				{
					$pm->addError("Unabled to use device: " . $pm->selected_device . "\n$e");
					return $this->runState($pm, 'Edit');
				}
				if (!strncmp($pm->binding['_key_name'], 'changer', 7) || !strncmp($pm->binding['_key_name'], 'tape', 4))
					if (empty($pm->tapedev_list))
						break;
		
				$pm->next_state = "添加";
				$pm->state = "Use";
				break;

			}
			if ($pm->binding['dev_meta']['media_type'] === 'vtape')
				if (!ZMC_BackupSet::modifyConf($pm, $pm->selected_name, array('dumpcycle' => '7 days')))
					$pm->addWarnError("Unable to set Backup Cycle.");
			break;

		default:
		case 'Refresh Table':
		case 'Refresh':
		case '':
		case 'Edit':
			if (!$this->getSelectedBinding($pm))
			{
				$pm->state = '';
				$this->runState($pm, 'Use1'); 
				break;
			}
			if(!isset($pm->binding['taperscan'])){ 
				$pm->binding['taperscan'] = array('plugin' => 'traditional');
			}
			if (!empty($pm->device_profile_list[$pm->binding['private']['zmc_device_name']]['private']['tape_drives']))
				$pm->addMessage('This changer library appears to have ' . $pm->device_profile_list[$pm->binding['private']['zmc_device_name']]['private']['tape_drives']. ' tape drive units.');
			$pm->form_type = call_user_func(array($this->zmc_type_class, 'get'), $pm->binding['_key_name']);
			$this->buildFormWrapper($pm);
			break;

		case 'Cancel':
			if ($pm->state === 'Cancel')
			{
				ZMC_BackupSet::cancelEdit();
				$pm->addWarning("编辑/添加 取消.");
			} 
		case 'Use1':
			$pm->state = 'Use1';
			$adminDevice = new ZMC_Admin_Devices($pm);
            //下面会获取所有存储设备列表，将其存入$pm->device_profile_list中
			$adminDevice->getDeviceList($pm, empty($_REQUEST['selected_device']) ? '' : $_REQUEST['selected_device']);
            //print_r($pm);
			break;

		case 'Use':
			$pm->selected_device = (empty($_REQUEST['selected_device']) ? '' : $_REQUEST['selected_device']);
			if (	empty($pm->selected_name) || empty($pm->selected_device)
				|| (!empty($_GET['action']) && $_GET['action'] === 'Use' && empty($_GET['ConfigurationName'])))
			{
				$this->runState($pm, 'Use1');
				break;
			}

			$adminDevice = new ZMC_Admin_Devices($pm);
			$reply = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => '/Device-Binding/defaults/' . $pm->selected_name,
				'data' => array(
					'binding_name' => $pm->selected_device,
				),
			));
			$pm->binding =& $reply['binding_conf'];
			unset($reply);
			ZMC_Type_Where::mergeCreationDefaults($pm->binding, true);
			$pm->form_type = ZMC_Type_Where::get($pm->binding['_key_name'], true);
			$pm->binding = ZMC_DeviceFilter::filter($pm, 'read', $pm->binding);
			try { $this->buildFormWrapper($pm); }
			catch (Exception $e)
			{
				$pm->addError("Unabled to use device: " . $pm->selected_device . "\n$e");
				return $this->runState($pm, 'Edit');
			}
			if (!strncmp($pm->binding['_key_name'], 'changer', 7) || !strncmp($pm->binding['_key_name'], 'tape', 4))
				if (empty($pm->tapedev_list))
					$pm->addEscapedWarning('没有存储设备，请去管理页面新建存储设备');
			break;
	}
}





public function getPaginator(ZMC_Registry_MessageBox $pm)
{
	$this->getBindingList($pm);
	if (empty($pm->binding_list))
		return;

	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('config_name', false);

	$flattened =& ZMC::flattenArrays($pm->binding_list);
	$showVtapes = $showTapes = null;
	foreach($flattened as &$row)
		if ($row['dev_meta:media_type'] !== 'vtape')
			$showTapes = true;
		else
		{
			if (!empty($row['media:partition_total_space']))
			{
				$row['autolabel'] = 'Yes';
				$showVtapes = true;
			}
		}

	$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
		'config_name',
		'_key_name',
		'private:zmc_device_name',
		'changer:changerdev',
		'dev_path' => $showTapes,
		'schedule:runtapes' => $showTapes,
		'media:partition_total_space' => $showVtapes,
		'autolabel',
		'private:last_modified_time',
		'private:last_modified_by',
		'comment',
	));
	$paginator->createColUrls($pm);
	
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
	if (!empty($pm->rows))
		foreach($pm->rows as &$row)
		{
			$row = ZMC_DeviceFilter::filter($pm, 'output', $row);
			
			
			if (!empty($pm->sets))
				unset($pm->sets[$row['config_name']]);
		}
}
public function validateSlotRange($range, ZMC_Registry_MessageBox $pm, $max_slots=''){
	if(!empty($range)){
		if(!preg_match('/^((\d+(-\d+)?,?)?){1,}$/', $range)){
			$pm->addError("请指定正确的格式 i.e 1-4,6-8,12,17,34-21,etc...");
		}else{
			$range = rtrim($range, " , ");
			$err_msg = "范围应该介于 1-$max_slots 内.";
			if(preg_match('/,/', $range)){
				$spl_slot = explode(",", $range);
				foreach($spl_slot as $key => $value){
					if(preg_match('/-+/', $value)){
						$dash_slot = explode("-", $value);
						if($dash_slot[0] > $dash_slot[1]){
							$tmp = '';
							$tmp = $dash_slot[0];
							$dash_slot[0] = $dash_slot[1];
							$dash_slot[1] = $tmp;
							$spl_slot[$key] = implode('-', $dash_slot);
							if($max_slots && ( $dash_slot[0] > $max_slots || $dash_slot[1] > $max_slots))
								$pm->addError($err_msg);	
						}
					}
					else
						if($max_slots && $value > $max_slots)
							$pm->addError($err_msg);	

				}
				$range = implode(',', $spl_slot);
			}else{
				if(preg_match('/-+/', $range)){
					$dash_slot = explode("-", $range);
					if($dash_slot[0] > $dash_slot[1]){
						$tmp = '';
						$tmp = $dash_slot[0];
						$dash_slot[0] = $dash_slot[1];
						$dash_slot[1] = $tmp;
						$range = implode('-', $dash_slot);
					}
					if($max_slots && ( $dash_slot[0] > $max_slots || $dash_slot[1] > $max_slots))
						$pm->addError($err_msg);
				}
				else
					if($max_slots && $range > $max_slots)
						$pm->addError($err_msg);	

			}

		}
	}
	return $range;
}
}
