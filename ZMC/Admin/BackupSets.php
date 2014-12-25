<?
//zhoulin-admin-backupset  201409172110












class ZMC_Admin_BackupSets extends ZMC_Backup
{
    const SUBNAV = 'backup sets';
    protected $editId = 'edit_id';

    public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Admin', $title = '云备份 - 备份集管理', $subnav = self::SUBNAV)
    {
        ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
        $pm->addDefaultInstruction('管理备份集 - 新增、编辑、查看、删除备份集');
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
                        $pm->addWarning("编辑/新增   取消");
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
                    case '立即激活':
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
        $pm->what = '<a href="'. ($pm->what = ZMC_HeaderFooter::$instance->getUrl('Backup', 'what')) . '">备份|来源 页面</a>';
        $pm->admin = '<a href="'. ZMC_HeaderFooter::$instance->getUrl('Admin', 'backup sets') . '">管理|备份集页面</a>';
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
                    $pm->addWarnError("已经存在一个名为'$name' 的备份集，请重新指定一个备份集名称。");
                }
                $pm->edit = $_POST;
                if ($pm->isErrors())
                    break;

                if (empty($name))
                {
                    $pm->addError('请输入一个有效的备份集名称。');
                    break;
                }

                $this->filterAndSave($pm, $name);
                if (!$pm->isErrors()){
                    $pm->next_state = 'Edit';
                    //获取s3主机
                    // $datahost =json_encode({"username":"zhanghz","password":"12345678","portalType":"public"});
                    // $urlhost =  "http://127.0.0.1:8083/pr/login";

                    // $token= json_decode($result[1],true);
                    ////$url  = "http://127.0.0.1:8083/instance/obs/getObsUser";  //http://$pm->resourcePool：8082/instance/obs/getObsUser
                    // $url  = "http://172.66.6.113:8082/instance/obs/getObsUser";  //http://$pm->resourcePool：8082/instance/obs/getObsUser
                    // $resultx = ZMC_User::http_post_data($url, $data);

                    //        /instance/obs/getObsUser
                    $url  = "http://172.66.6.113:8082/instance/obs/getObsUser";
                    session_start();
                    $token= $_SESSION['token'];

                    //获取设备的s3HostIp
                    $poolName = $_POST['respool'];
                    // echo $poolName;

                    //获取设备的s3HostIp
                    $hostip = "";//定义资源池名称，默认为空
                    //从配置文件里读取对应ip的资源池的拼音名称
                    $resourcejsonfile = file_get_contents("../json/resourcepool.json");
                    $allResPool=json_decode($resourcejsonfile);
                    for ($h = 0; $h < count($allResPool); $h++) {
                        $objvalue = $allResPool[$h]->value;
                        if ($poolName == $objvalue) {
                            $hostip = $allResPool[$h]->hostip;
                            continue;
                        }
                    }


                    //拼接json作为post的参数传递
                    // echo "====================".$token;
                    $data = json_encode(array('resourcePool'=>$poolName,'token'=>$token));
                    // echo "------data--------".$data;
                    $resultx = ZMC_User::http_post_data($url, $data);

                    //处理返回值
                    if(!empty($resultx)){
                        $s3data=json_decode($resultx[1],true);
                        if(array_key_exists('data',$s3data)){
                            $keydata=$s3data['data'][0];
                            $secretkey=$keydata['secretkey'];
                            $accesskey=$keydata['accesskey'];
                        }
                    }

                    // echo "hostip-------- > ".$hostip;
                    // echo "secretkey-------- > ".$secretkey;
                    // echo "accesskey-------- > ".$accesskey;

                    $pm_tmp=new ZMC_Registry_MessageBox();

                    $pm_tmp->binding=array('_key_name' =>"s3_compatible_cloud");
                    ZMC_Type_Devices::mergeCreationDefaults($pm_tmp->binding, true);//创建s3存储设备的默认值
                    $device_name = $name.date('YmdHis');//('Y-m-d H:i:s');
                    $tmparray =array(
                        "_key_name" => "s3_compatible_cloud",
                        "changer:comment",
                        "device_property_list:BLOCK_SIZE" =>256,
                        "device_property_list:BLOCK_SIZE_display" => "MiB",
                        "device_property_list:REUSE_CONNECTION" => "on",
                        "device_property_list:S3_ACCESS_KEY" => $accesskey,
                        "device_property_list:S3_HOST" => $hostip,
                        "device_property_list:S3_SECRET_KEY" => $secretkey,
                        "device_property_list:S3_SERVICE_PATH",
                        "device_property_list:S3_SSL" =>"off",
                        "device_property_list:S3_SUBDOMAIN" => "off",
                        "device_property_list:USE_API_KEYS" => "on",
                        "id" =>$device_name,
                        "max_slots" => 2000,
                        "private:zmc_show_advanced" => 0,
                        "zmc_version" => 3 ,
                        "device_output_buffer_size"=>"512m"
                    );

                    // print_r('AAAAAAAAAAAAAAAA');
                    $pm_tmp->binding = ZMC_DeviceFilter::filter($pm_tmp, 'input', $tmparray, 'ZMC_Type_Devices');
                    $realpath = realpath($pm_tmp->binding['changer:changerdev']);
                    if ($rw = ZMC::is_readwrite($realpath, false))
                        $pm_tmp->addError("不能在路径'{$pm_tmp->binding['changer:changerdev']} => $realpath' 下新建设备，因为缺少读写权限.\n$rw");
                    try
                    {
                        //print_r('新增存储设备');
                        $result = ZMC_Yasumi::operation($pm, array(
                            'pathInfo' => '/Device-Profile/' . ($update ? 'merge' : 'create'),
                            'data' => array(
                                'commit_comment' => 'Admin|devices add/update device profile',
                                'message_type' => 'Device Profile Edit',
                                'device_profile_list' => array($pm_tmp->binding['id'] => $pm_tmp->binding)
                            ),
                        ));
                        $pm_tmp->merge($result);
                        ZMC_Type_Devices::addExpireWarnings($pm_tmp);
                        ZMC_DeviceFilter::filterNamedList($pm_tmp, $pm_tmp->device_profile_list);
                        $jid = ZMC_User::insertDrivesOwner($_SESSION['user'],$pm_tmp->binding['id']);
                    }
                    catch(Exception $e)
                    {
                        if (empty($e))
                            ZMC::auditLog(($update ? 'Edit' : 'New') . ' 设备 "' . $pm_tmp->binding['id'] . "\" 失败: " . $pm_tmp->getAllMerged(), 500, null, ZMC_Error::ERROR);

                    }
                    if (empty($pm_tmp->fatal))//新增设备成功的信息
                    {
                        !$update && ZMC_Paginator_Reset::reset('last_modified_time');//重置上次修改时间
                        $pm_tmp->addMessage($msg = ($update ? " 更新." : " 新增")." 设备 '" . $pm_tmp->binding['id'] ."‘ 成功" );
                        ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
                        //print_r("新增存储设备成功");

                        //以下是绑定s3存储设备

                        $replyw = ZMC_Yasumi::operation($pm_tmp, array(
                            'pathInfo' => '/Device-Binding/defaults/' . $name,
                            'data' => array(
                                'binding_name' => $pm_tmp->binding['id'],
                            ),
                        ));
                        $result = ZMC_Yasumi::operation($pm_tmp, array(
                            'pathInfo' => '/Device-Binding/create/' . $name,
                            'data' => array(
                                'commit_comment' =>' add device binding',
                                'binding_name' => $pm_tmp->binding['id'],
                                'binding_conf' => $replyw['binding_conf'],
                            ),
                        ));


                        $status = ZMC_BackupSet::getStatus($pm_tmp, $name, false);
                        $status['profile_name'] =  $pm_tmp->binding['id'];
                        $status['device'] =  $pm_tmp->binding['id'];
                        ZMC_BackupSet::updateStatus($name, $status);

                        unset($replyw);
                    }

                }
                break;

            case 'Edit':
                $pm->edit = ZMC_BackupSet::getByName($pm->selected_name);
                if ($pm->edit['version'] !==  ZMC::$registry->zmc_backupset_version)
                {
                    $pm->addError('不能编辑 ' . $pm->edit['configuration_name'] . ', 你需要先从版本 '
                        . $pm->edit['version'] . ' 升级到 ' . ZMC::$registry->zmc_backupset_version);
                    $pm->edit = null;
                    return $this->runState($pm, 'Refresh');
                }
                if (empty($pm->edit))
                {
                    $pm->addError("找不到备份集 '$name'.");
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
                $pm->confirm_help = '确认停止备份集的备份还原操作';
                $pm->addMessage('终止备份集意味着会停止该备份集的所有备份还原记录和数据，并将其初始化到干净状态。');
                foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
                    $this->vtapesMessage($pm, $name);
                $pm->addWarning('操作不可逆.');
                $pm->prompt ='你确定你要终止备份集的备份还原操作?<br /><ul>'
                    . '<li>'
                    . implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
                    . "\n</ul>\n";
                $pm->confirm_action = 'AbortConfirm';
                $pm->yes = 'Abort';
                $pm->no = 'Cancel';
                break;

            case 'AbortConfirm':
                if (!isset($_POST['ConfirmationYes']))
                    $pm->addWarning('取消');
                else
                    foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
                        if (!ZMC_BackupSet::abort($pm, $name))
                            $pm->addError("无法中止备份集: $name");
                        else
                            $pm->addMessage("备份/还原: $name 被中止");
                break;

            case 'Delete':
                $pm->confirm_template = 'ConfirmationWindow';
                $pm->confirm_help = '删除备份集确认';
                $pm->addMessage('删除备份集会删除该备份集以及在云备份中关联到该备份集的设置，即使已经完成的备份也会被删除。磁盘备份可以手动删除，备份记录可以在 "备份|介质" 页面重新标记再使用。');
                foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
                    $this->vtapesMessage($pm, $name);
                $pm->addWarning('操作不可逆');
                $pm->prompt ='你确定要删除备份集？<br /><ul>'
                    . '<li>'
                    . implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
                    . "\n</ul>\n";


                $pm->prompt .= "<br style=\"clear:left\"><input id='purge_media' type='checkbox' name='purge_media' /><label for='purge_media'> 清除备份配置文件和缓存区域?</label>\n";
//			$pm->prompt .= "<br style=\"clear:left\"><input id='purge_vault_media' type='checkbox' name='purge_vault_media' /><label for='purge_vault_media'> Purge vault media?</label>\n";
                $pm->confirm_action = 'DeleteConfirm';
                $pm->yes = 'Delete';
                $pm->no = 'Cancel';
                break;

            case 'DeleteConfirm':
                $pm->selected_name = '';
                $pm->edit = null;
                if (!isset($_POST['ConfirmationYes']))
                    $pm->addWarning('删除被取消.');
                else
                    foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
                    {
                        $deletedevice=ZMC_BackupSet::getByName($name);
                        if (ZMC_BackupSet::rm($pm, $name, !empty($_POST['purge_media']), !empty($_POST['purge_vault_media'])))
                        {   //删除与备份集绑定的存储设备
                            if($_POST['purge_media']==='on')
                                if(array_key_exists('device',$deletedevice)&&!empty($deletedevice['device'])) {
                                    $device[$deletedevice['device']]=null;
                                    ZMC_Yasumi::operation($pm, array(
                                        'pathInfo' => '/Device-Profile/delete',
                                        'data' => array(
                                            'commit_comment' => 'Delete device(s)',
                                            'message_type' => 'Device Profile',
                                            'device_profile_list' => $device,
                                        ),
                                    ));
									$deldevice="'".$deletedevice['device']."'";
                                    ZMC_User::deleteDrivesOwner($deldevice, $_SESSION['user']);
                                    ZMC::auditLog('Deleted device(s) $deletedevice["device"]', 0, null, ZMC_Error::NOTICE);
                                    //ZMC_User::deleteDrivesOwner($delete_devices_name,$_SESSION['user']);
                                }
                        }
                        else {
                            $pm->addError("无法删除备份集: $name");
                        }
                    }
                if (ZMC_BackupSet::count() == 0)
                    $pm->addWarning("没有有效的备份集.");
                break;

            case 'Duplicate':
                $set = ZMC_BackupSet::getByName($name = key(ZMC::$userRegistry['selected_ids']));
                if ($set['version'] !== ZMC::$registry->zmc_backupset_version)
                {
                    $pm->addError("备份集 \"$name\" 在复制前必须迁移到版本 " . ZMC::$registry->short_name . ' ' . ZMC::$registry->long_name . '');
                    break;
                }
                $pm->confirm_template = 'ConfirmationWindow';
                $pm->confirm_action = 'DuplicateConfirm';
                $pm->confirm_help = 'Duplication';
                $pm->addMessage("复制 \"$name\"");
                $pm->addMessage('请为复制的备份集输入一个新名字.');
                $pm->prompt = '<div class="p"><label class="wocloudLongLabel">新备份集名称:</label>
				<input type="text" name="duplicate_backupset_name" title="" id="ordinal" class="wocloudLongInput" value="" />
					<input type="hidden" name="edit_id" id="ordinal" value="' . $name . '" /></div>';
                $pm->yes = "复制";
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
                $_POST['action'] = 'create';

                $newname = trim($_POST['duplicate_backupset_name']);

                if (ZMC_Admin_Devices::get($pm, $newname))
                {
                    $pm->addWarnError("系统已存在名为'$newname' 的备份集，请选择其他名字");
                }
                $pm->edit = $_POST;
                if ($pm->isErrors())
                    break;

                if (empty($newname))
                {
                    $pm->addError('请输入一个有效的备份集名称.');
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

            case '激活':
            case '反激活':
                foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
                    ZMC_BackupSet::activate($pm, $pm->state === '激活', $name);
                break;

            case '选择备份集':
                $pm->backup_how = $_POST['backup_how'];
                $pm->addMessage("选择需要备份的备份项：");
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
                    $pm->addError("在读取和处理对象列表是出现异常： '{$pm->selected_name}': $e");
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
                        echo '===================>'.$id.'<=====================';
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
            $pm->addWarning("$name: 虽然在磁盘上找不到该备份集，但依然存在与管理系统数据库中。将删除数据库中相关项。如果绑定到该备份集的存储数据还残留在磁盘上，该操作将不会删除它们，请在删除备份集之后手动删除相关目录或者移到其他目录作为长期档案。  ");

        if (empty($set['profile_name']) || $set['profile_name'] === 'NONE')
            return;

        return;
        if (ZMC::$registry->dev_only)
            ZMC::quit('@TODO (depends on bug #:11014');

        if (($set['type'] === 'disk') || ($set['type'] === 's3'))
        {
            return $pm->addMessage("删除备份集将不会删除备份数据，删除备份集后，你可以手动删除备份数据粗放目录或者移动到其他目录长期保存。 ");


            return "删除备份集将不会删除备份数据，目前还有 ".$device->slots." 份备份数据存放在 ".$device->tapedev."，删除备份集后，你可以手动删除备份数据粗放目录或者移动到其他目录长期保存。 ";
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
            $pm->addMessage("成功配置备份集 '$name' ");
        else
            $pm->addError("配置备份集  '$name' 失败。");
    }
}
