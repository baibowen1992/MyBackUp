<?














class ZMC_Backup_What extends ZMC_Backup
{
	private $confDles = null;

	protected $zmc_type_class = 'ZMC_Type_What';
	protected $defaultEditAction = 'Create1';
	protected $requireBackupSet = true;

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - 备份源', 'what');
		$whatPage = new self($pm);
		if (empty($pm->selected_name))
		{
			$pm->addWarning("请先选择一个备份集");
			return 'MessageBox';
		}
		$pm->addEscapedInstruction(ZMC::$registry->tips['backup_what']);
		$pm->advanced_options_title = '高级参数';
		$pm->raw_binding = ZMC_Yaml_sfYaml::load(ZMC::$registry->etc_amanda . $pm->selected_name .  '/binding-' . $pm->edit['profile_name'] . '.yml'); 
		$pm->users = ZMC_User::$users; 
		
		
		
		
		
		
		$whatPage->runState($pm);
		$whatPage->loadDisklist($pm); 
		$whatPage->getLicenseStatus($pm);
		$whatPage->getPaginator($pm);
		return 'BackupWhat';
	}

	protected function runState(ZMC_Registry_MessageBox $pm)
	{
		$update = false;
		$check = false;

		switch($pm->state)
		{
			case 'Update':
				$update = true;
			case 'create':
				
				$adminDevice = new ZMC_Admin_Devices($pm);
				$adminDevice->getDeviceList($pm);
				$dle = ZMC_DleFilter::filter($pm, 'input', $_POST);
				
				$this->loadDisklist($pm);
				if(!empty($_POST['disk_name']) && strpos($_POST['disk_name'], '/') !== false)
					$pm->addError("别名中不允许使用正斜杠 \"/\" 请重新选择表明");

				$disk_name = empty($_POST['disk_name']) ? $_POST['disk_device'] : $_POST['disk_name'];
				$newID = "{$pm->selected_name}|{$_POST['host_name']}|$disk_name";
				$oldID = $_POST['natural_key_orig'];
				if(($update && ($newID !== $oldID)) || !$update) {
					foreach($pm->dles as $curDLE)
						if($curDLE['natural_key'] === $newID){
							$pm->addError("对应主机和对应目录已经存在于其他备份项中，请修改");
							break;
						}
				}
				
				if ($pm->isErrors())
				{
					if($update){
						$pm['selected_id'] = $_POST['natural_key_orig'];
						$pm->state = 'Edit';
						$this->runState($pm);
					} else {
						$this->buildFormWrapper($pm, $dle);
					}
					break;
				}
				
				if($dle['property_list:zmc_type'] === 'ndmp'){
					$dle['property_list:filer_password'] = '6G!dr' . base64_encode($dle['property_list:filer_password']);
				} else if ($dle['property_list:zmc_type'] === 'cifs') {
					$dle['property_list:zmc_share_password'] = '6G!dr' . base64_encode($dle['property_list:zmc_share_password']);
				}

				$this->confDles = null;
				try
				{
					$this->confDles = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => "/conf/merge_write/" . $pm->selected_name,
						'data' => array(
							'amanda_configuration_id' => $pm->selected_name,
							'what' => 'disklist.conf',
							'update' => $update,
							'commit_comment' => 'Backup|What add/update DLE',
							'mode' => 'asynchronous', 
							'verify' => true, 
							'conf' => array('dle_list' => array($dle['natural_key'] => $dle))
						),
					));
					$pm->merge($this->confDles);
				}
				catch (Exception $e)
				{
					$pm->addError("$e");
					ZMC::auditLog(($update ? 'Edit' : 'New') . ' of DLE "' . $dle['natural_key'] . "\" failed: $e", $e->getCode(), null, ZMC_Error::ERROR);
				}

				if ($pm->isErrors())
					$this->buildFormWrapper($pm, $dle);
				else
				{
					!$update && ZMC_Paginator_Reset::reset('last_modified_time'); 
					$pm->addMessage($msg = "备份项 '" . $dle['natural_key'] . ($update ? "' 更新" : "' 创建."));
					ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
					$pm->state = 'Create1';
					$this->runState($pm);
				}
				break;

			case 'Edit':
				if(isset($pm['selected_id'])){
					$id = $pm['selected_id'];
				} else {
					$id = ZMC_Form::getEditId($pm, 'edit_id', '编辑失败，没有选中备份集。');
				}
				$this->loadDisklist($pm);
				if (isset($pm->dles[$id]))
					$dle = $pm->dles[$id];
				else
				{
					$pm->addError("备份项 '$id' 不存在.");
					$pm->state = 'Create1';
					$this->runState($pm);
					break;
				}
								
				$type = trim($dle['property_list']['zmc_type']);
				if($type == 'windowsexchange' || $type == 'windowssqlserver' || $type == 'windowshyperv'){
					$discoveredComponent = file_get_contents('/etc/amanda/' . $dle['property_list']['zmc_disklist']
							. '/discovered_db_' . $dle['host_name'] . '_' . $dle['property_list']['zmc_type']);
					if($discoveredComponent)
						$dle['discovered_components'] = $discoveredComponent;
				}
				
				if($dle['property_list']['zmc_type'] === 'ndmp'){
					$password = $dle['property_list']['filer_password'];
					if(strpos($password, '6G!dr') !== false){
						$dle['property_list']['filer_password'] = base64_decode(substr($password, 5));
					}
				} else if($dle['property_list']['zmc_type'] === 'cifs') {
					$password = $dle['property_list']['zmc_share_password'];
					if(strpos($password, '6G!dr') !== false){
						$dle['property_list']['zmc_share_password'] = base64_decode(substr($password, 5));
					}
				}

				if (!empty($dle['property_list']['zmc_amcheck']))
				{
					$check = $dle['property_list']['zmc_amcheck'];
					$disklistContents = '';
					if (	!strpos($check, 'resolve_hostname')
						&&	(!strpos($check, ' 1 problem found') || !strpos($check, 'can not stat'))
						&&	!empty($dle['property_list']['zmc_amcheck_date'])
						&&	!strpos($check, 'hecking')
					   )
					{	
						$lineNumbers = array();
						$i = 0;
						while($i = strpos($check, 'line ', $i))
							if ($colon = strpos($check, ':', $i+6))
							{
								$lineNumbers[intval(substr($check, $i+5, $colon))] = true;
								$i = $colon+1;
							}
							else
								break;
						$prefix = ZMC::$registry->etc_amanda . $pm->selected_name . '/disklist.';
						$fn = $prefix . str_replace(' ', '_', $dle['property_list']['zmc_amcheck_date']) . '.conf';
						if (!is_readable($fn))
							$fn = $prefix . 'conf';
						$lines = explode("\n", rtrim(file_get_contents($fn)));
						$i = 1;
						foreach($lines as $line) {
                            $disklistContents .= (isset($lineNumbers[$i]) ? '<span style="background-color:#FCC;">' : '') . str_pad($i, 4) . (strpos($line, '"zmc_amcheck"') ? '' : $line) . (isset($lineNumbers[$i++]) ? '</span>' : '') . "\n";

                        }
						$disklistContents = "<h3>==&gt;" . ZMC::escape($fn) . "</a>&lt;==</h3>  \n";
//						$disklistContents = "<h3>==&gt;<a href='' onclick=\"var o = gebi('disklist_contents'); if (o.style.display == 'block') o.style.display='none'; else o.style.display='block'; return false;\">" . ZMC::escape($fn) . "</a>&lt;==</h3>
//							<pre id='disklist_contents' style='display:none'>$disklistContents\n</pre>\n";
					}
					$msg = ZMC::escape(ZMC_Yasumi_Parser::unquote($dle['property_list']['zmc_amcheck']));
                    //去掉错误输出中的下述字样，近显示关键错误信息  added by zhoulin 201411250044
                    $msg=str_replace("Amanda Backup Client Hosts Check", "",$msg);
                    $msg=str_replace("--------------------------------", "",$msg);
                    $msg=str_replace("(brought to you by Amanda 3.3.6)", "",$msg);
					if (strncmp($msg, 'checking', 8))
						$pm->addEscapedError($msg . $disklistContents);
					else
						$pm->addEscapedWarning($msg);
				}
				ZMC::flattenArray($flat, $dle);
				$this->buildFormWrapper($pm, $flat);
				break;

			case 'Move': 
				$pm->addError('在后续版本会支持.');
				break;

			case 'Copy To': 
				$pm->addError('在后续版本会支持');
				break;

			case 'Check Hosts':
				$check = true;
			case 'Delete':
				try
				{
					$dle_list = array();
					if (empty(ZMC::$userRegistry['selected_ids']))
					{
						if ($check)
							$pm->addMessage('检查所有备份项 .. 需要的时间取决于备份项数目.');
						else
						{
							$pm->addError('没有备份项被删除');
							$pm->state = 'Create1';
							$this->runState($pm);
							break;
						}
					}
					else
					{
						foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore)
						{
							list($list_name, $host_name, $disk_name) = explode('|', $id);
							$dle_list[$id] = null;
							if (!$check)
								$pm->addMessage($msg = "请求删除节点 $host_name 上的备份项 $disk_name ");
						}
						ZMC::auditLog(($check ? '检查' : 'Delete') . ': ' . implode(', ', array_keys($dle_list)), 0, null, ZMC_Error::NOTICE);
					}

					$this->confDles = ZMC_Yasumi::operation($pm, array(
						'pathInfo' => '/conf/' . ($check ? 'verify_dles' : 'merge_write') . '/' . $pm->selected_name,
						'data' => array(
							'amanda_configuration_id' => $pm->selected_name,
							'what' => 'disklist.conf',
							'commit_comment' => 'Backup|What ' . ($check ? 'Check Hosts' : 'Delete'),
							'mode' => ($check ? 'asynchronous' : '0'),
							'conf' => array('dle_list' => $dle_list)
						),
					));
					$pm->merge($this->confDles);
				}
				catch(ZMC_Mysql_Exception $e)
				{
					$pm->addError("$e");
				}
				catch (Exception $e)
				{
					$pm->addYasumiServiceException($e); 
					if (!$check)
						ZMC::auditLog("Deletion of object/DLE failed: $e", $e->getCode(), null, ZMC_Error::ERROR);
				}
				
			case 'Refresh Table':
			case 'Refresh':
			case 'Cancel':
				if ($pm->state === 'Cancel')
					$pm->addWarning("编辑/新增 取消");

				$pm->state = 'Create1';
			case 'Create1':
				$pm->addDefaultInstruction("编辑对象列表 '$pm->selected_name'.");
				break;

			case 'Create2': 
				for($i=1; $i <= 3; $i++)
					if (!empty($_REQUEST["selection$i"]))
						$zmc_type = $_REQUEST["selection$i"];

				if (empty($zmc_type))
					$pm->state = 'Create1';
				else{
					if($zmc_type === 'windowstemplate'){
						$pm->addMessage("模板必须在windows客户端上配置.");
						$pm->addMessage("检查 \"所有本地磁盘\" 来备份所有客户端上的驱动器.");
					}
					if($zmc_type === 'windows')
						$pm->addMessage("备份本地驱动器，请使用 \"Windows Template\" .");
					$this->buildFormWrapper($pm, array('property_list:zmc_type' => $zmc_type, 'property_list:zmc_version' => ZMC::$registry->zmc_version));
				}
				break;
			
			case 'Discover':
			case 'Rediscover':
				$adminDevice = new ZMC_Admin_Devices($pm);
				$adminDevice->getDeviceList($pm);
				$dle = ZMC_DleFilter::filter($pm, 'input', $_POST);
				$type = trim($_POST['property_list:zmc_type']);
				$dle['discovered_components'] = $this->getBackupComponent($_POST['property_list:zmc_disklist'], $_POST['host_name'], $type, $err, $result);
				if($pm->state === 'Discover')
					$pm->state = 'Create2';
				else
					$pm->state = 'Edit';
				
				if(!empty($err))
					$pm->addError($err);
				if(!empty($result))
					$pm->addMessage($result);
				
				$this->buildFormWrapper($pm, $dle);
				break;
			default:
				ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex(__CLASS__ . " - Unknown state: $pm->state"), __FILE__, __LINE__);
		}
	}
	
	private function getBackupComponent($backupset, $hostname, $type, &$err, &$result)
	{
		$requestFile = "/var/tmp/getbackupcomponent_request";
		$fh = fopen($requestFile, 'w');
		if(!$fh) return "";
		switch($type){
			case 'windowsexchange':
				$request = "<dle>\n<program>DUMP</program>\n<estimate>CALCSIZE</estimate>\n<disk>zmc_msexchange</disk>\n<auth>bsdtcp</auth>\n</dle>";
				break;
			case 'windowshyperv':
				$request = "<dle>\n<program>DUMP</program>\n<estimate>CALCSIZE</estimate>\n<disk>zmc_mshyperv</disk>\n<auth>bsdtcp</auth>\n</dle>";
				break;
			case 'windowssqlserver':
				$request = "<dle>\n<program>DUMP</program>\n<estimate>CALCSIZE</estimate>\n<disk>zmc_mssql</disk>\n<auth>bsdtcp</auth>\n</dle>";
				break;
			default:
				return;
		}
		
		fwrite($fh, $request);
		fclose($fh);
		
 		try
		{ 
			ZMC_ProcOpen::procOpen('amservice', ZMC::getAmandaCmd('amservice'),
				array('-f', '/var/tmp/getbackupcomponent_request', $hostname, 'bsdtcp', 'getbackupcomponent'),
			$stdout, $stderr, 'Failed to discover backup components');
			$result = "sucessfully discovered the following items:\n $stdout";
 		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$err = "Failed to discover backup components: $stdout";
			return;
		}

		$content = $stdout;
		switch($type){
			case 'windowsexchange':
				if(! stristr(strtolower($content), "<zmc_msexchange>") || !stristr(strtolower($content), "</zmc_msexchange>")){ 
					$err = "Failed to discover backup components: $stdout";
					$result = "";
					return;
				}
				
				$content = str_replace("<zmc_msexchange>", "", $content);
				$content = str_replace("</zmc_msexchange>", "", $content);
				break;
				
			case 'windowshyperv':
				if(! stristr(strtolower($content), "<zmc_mshyperv>") || !stristr(strtolower($content), "</zmc_mshyperv>")){ 
					$err = "Failed to discover backup components: $stdout";
					$result = "";
					return;
				}
			
				$content = str_replace("<zmc_mshyperv>", "", $content);
				$content = str_replace("</zmc_mshyperv>", "", $content);
				break;
				
			case 'windowssqlserver':
				if(! stristr(strtolower($content), "<zmc_mssql>") || !stristr(strtolower($content), "</zmc_mssql>")){ 
					$err = "Failed to discover backup components: $stdout";
					$result = "";
					return;
				}
				
				$content = str_replace("<zmc_mssql>", "", $content);
				$content = str_replace("</zmc_mssql>", "", $content);
				break;
			default:
				return;
		}
						
		$componentsArray = preg_split( "/[\n]+/", $content);
		$componentsStr = "";
		switch($type){
			case 'windowsexchange':
				foreach($componentsArray as $component){
					if(!empty($component) && substr_count($component, '\\') == 2)
						$componentsStr = $componentsStr . $component . ";";
				}
				break;
			case 'windowshyperv':
			case 'windowssqlserver':
				foreach($componentsArray as $component){
					if(!empty($component))
					$componentsStr = $componentsStr . $component . ";";
				}
				break;
			default:
				break;
		}
		
		$fh = fopen("/etc/amanda/$backupset/discovered_db_$hostname" . "_$type", 'w');
		fwrite($fh, $componentsStr);
		fclose($fh);
		
		return $componentsStr;
	}

	private function getLicenseStatus(ZMC_Registry_MessageBox $pm)
	{
		if (empty($pm->lstats))
            return $pm->licensesRemaining = '';
//			return $pm->licensesRemaining = 'No licenses left';
			
		if (!empty($pm->form_type))
		{
			$group = $pm->form_type['license_group'];
			$licenses =& $pm->lstats['licenses']['zmc'];
			if (empty($licenses['Licensed']) || empty ($licenses['Licensed'][$group])) 
			{
				if (empty($licenses['Expired']) || empty($licenses['Expired'][$group]))
				
					$pm->licensesRemaining = 'No licenses exist for: ' . ZMC::escape($pm->form_type['name']);
				else
				
					$pm->licensesRemaining = 'All licenses have expired for: ' . ZMC::escape($pm->form_type['name']);
			}
			else 
			{
				$remaining = empty($licenses['Remaining']) ? 0 : $licenses['Remaining'][$group];
				$pm->licensesRemaining = "$remaining of " . $licenses['Licensed'][$group] . ' new hostnames left';
			}
            $pm->licensesRemaining = '';
		}

		if (!empty($pm->lstats['dles_over_limit']) && !empty($pm->lstats['dles_over_limit'][$pm->selected_name]))
		{
			$groupTypes = array();
			foreach(array_keys($pm->lstats['group_over_limit'][$pm->selected_name]) as $groupType)
				if ($pm->state !== 'create' || $pm->lstats['over_limit'][$groupType] > 1)
					$groupTypes[] = $groupType;

//			if (!empty($groupTypes))
//				$pm->addEscapedError('Some DLEs exceed license limits (see ' . ZMC::getPageUrl($pm, 'Admin', 'licenses') . ').  DLEs of the following types have been disabled: ' . ZMC::escape(ZMC_Type_What::getNames($groupTypes)));
		}
	}

	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (empty($pm->dles))
			return;

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('host_name', false);
		
		ZMC_BackupSet::mergeFindTapelist($pm, $pm->raw_binding);
		$flattened =& ZMC::flattenArrays($pm->dles);
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'natural_key' => "disklist|host_name|disk_name",
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
		$pm->goto = $paginator->footer($pm->url);
		if (!empty($pm->rows))
		{
			foreach($pm->rows as &$row)
				$row = ZMC_DleFilter::filter($pm, 'output', $row);

			foreach ($pm->rows as &$row)
			{
				if (!empty($row['zmc_dle_template']))
					$pm->templates = true;

				if (!empty($row['property_list:zmc_comments']))
					$pm->comments = true;

				if (isset($row['disk_name']) && $row['disk_name'] !== $row['disk_device'])
					$pm->aliases = true;
				else
					$row['disk_name'] = $row['disk_device'];
			}
		}
	}

	public function loadDisklist(ZMC_Registry_MessageBox $pm)
	{
		$pm->dles = null;
		try
		{
			if (empty($this->confDles))
			{
				$this->confDles = ZMC_Yasumi::operation($pm, array(
					'pathInfo' => "/conf/read/{$pm->selected_name}",
					'data' => array(
						'what' => 'disklist.conf',
					)
				));
				$pm->merge($this->confDles);

			}

			if ($pm->offsetExists('conf'))
				$pm->offsetUnset('conf');

			if (!empty($this->confDles['conf']) && !empty($this->confDles['conf']['dle_list']))
				foreach($this->confDles['conf']['dle_list'] as $id => &$dle)
				{
					$dle['natural_key'] = $id;
					$pm->dles[$id] =& $dle;
				}
		}
		catch (Exception $e)
		{
			$pm->addError("读取和处理对象列表 '{$pm->selected_name}'的时候发生位置错误： $e");
		}
	}

	protected function buildFormWrapper(ZMC_Registry_MessageBox $pm, array $dle = null)
	{
		if (empty($dle))
			ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex(__FUNCTION__ . '(): dle empty'), __FILE__, __LINE__);

		if (empty($dle['property_list:zmc_type']))
			ZMC::quit('Unknown object type: ' . print_r($dle, true));

		if ($pm->state !== 'create' && $pm->state !== 'Create2' && empty($dle['natural_key_orig']))
			$dle['natural_key_orig'] = $dle['natural_key'];

		$dle['property_list:zmc_disklist'] = $pm->selected_name;
		$form = array();
		$pm->form_type = ZMC_Type_What::get($dle['property_list:zmc_type']);
		ZMC_Form::buildForm($pm, $form, $dle, $pm->state === 'create', 'ZMC_DleFilter');
		$pm->form_advanced_id = 'twirl_what';
		ZMC_HeaderFooter::$instance->injectYuiCode(<<<EOD
			var o1=gebi('property_list:zmc_show_advanced')
			if (o1 != null && (o1.value === '1' || o1.value === 'on'))
				YAHOO.zmc.utils.twirl('img_twirl_what', 'div_twirl_what')

			var f=function(e)
			{
				if (!e) var e = window.event
				if (this.name == 'disk_device' && gebi('disk_name').value != '') return
				var o2=gebi('zmcSubmitButton')
				if (o2)
					o2.disabled=false
			}
			for(var s in ['host_name', 'disk_name', 'disk_device'])
			{
				var o3=gebi(s)
				if (o3)
					o3.addEventListener('keypress', f, false)
			}
EOD
		);

		$on = (isset($pm->form_type['form']['property_list:zmc_extended_attributes']) ? $pm->form_type['form']['property_list:zmc_extended_attributes']['default']['on'] : '');
		$off = (isset($pm->form_type['form']['property_list:zmc_extended_attributes']) ? $pm->form_type['form']['property_list:zmc_extended_attributes']['default']['off'] : '');
		ZMC_HeaderFooter::$instance->injectYuiCode(<<<EOD

			zmcRegistry.app_set_recommended_checked = function()
			{
				return '$on'
			}

			zmcRegistry.app_set_recommended_unchecked = function()
			{
				return '$off'
			}

			zmcRegistry.app_highlight_recommended()

			zmcRegistry.adjust_custom_compress()
			
			zmcRegistry.initialize_data_source_selection()
						
			zmcRegistry.adjust_exchange_level_1_backup()
						
			zmcRegistry.adjust_sqlserver_level_1_backup()
			
			zmcRegistry.adjust_discover_button()
			
			zmcRegistry.update_discover_result()
			
			zmcRegistry.adjust_zmc_ndmpDataPathChanged()
			
			zmcRegistry.adjust_windowstemplate()
EOD
			);

		if (!empty($pm->form_type['form']['property_list:zmc_extended_attributes']))
				ZMC_HeaderFooter::$instance->injectYuiCode("zmcRegistry.app_set_recommended()\n");
	}
}

class ZMC_DleFilter
{
	static $keepBackslashes = array(
		'cifs' => true,
		'windows' => true,
		'vmware' => true,
	);

	public static function filter(ZMC_Registry_MessageBox $pm, $method, array $dle)
	{
		ksort($dle); 
		if ($method === 'input') 
			self::inputPre($pm, $dle);

		if (method_exists(__CLASS__, $methodOverride = $method . ucFirst($dle['property_list:zmc_type'])))
	        return call_user_func(array(__CLASS__, $methodOverride), $pm, $dle);
		else
		    return self::$method($pm, $dle);
	}

	protected static function inputPre(ZMC_Registry_MessageBox $pm, &$dle)
	{
		$removeBackslashes = !isset(self::$keepBackslashes[$dle['property_list:zmc_type']]);
		if (isset($dle['disk_device']))
			ZMC::inputFilter($pm, $dle['disk_device'], $removeBackslashes);
		if (isset($dle['disk_name']))
			ZMC::inputFilter($pm, $dle['disk_name'], $removeBackslashes);
		ZMC::inputFilter($pm, $dle['host_name'], true);
	}

	protected static function input(ZMC_Registry_MessageBox $pm, $post)
	{
		if (isset($post['strategy']) && $post['strategy'] === 'default')
			$post['strategy'] = null; 

 		if (isset($post['property_list:zmc_custom_app']) && $post['property_list:zmc_custom_app'] === '0') {
			$post['property_list:zmc_custom_app'] = $post['property_list:zmc_override_app']; 
		}
		elseif(isset($post['property_list:zmc_custom_app']) && $post['property_list:zmc_custom_app'] != null){
			if($post['property_list:zmc_type'] == 'ndmp')
				$post['property_list:zmc_amanda_app'] = $post['property_list:zmc_custom_app'];
			unset($post['property_list:zmc_override_app']);
		}

		
		
		
		$dle =& ZMC_Form::form2array($pm, ZMC_Type_What::get($post['property_list:zmc_type']), $post);
		
		if(isset($dle['compress']) && isset($dle['property_list:zmc_custom_compress'])) {
			if ($dle['compress'] === 'server custom') {
				$dle['server_custom_compress'] = $dle['property_list:zmc_custom_compress'];
			}
			if ($dle['compress'] === 'client custom') {
				$dle['client_custom_compress'] = $dle['property_list:zmc_custom_compress'];
			}
			unset($dle['property_list:zmc_custom_compress']);
		}

		if(isset($dle['encrypt'])){
			if($dle['encrypt'] === 'zwcaes'){
				$dle['encrypt'] = 'client';
				$dle['client_encrypt'] = 'zwcaes';
				$dle['client_decrypt_option'] = '-d';
			}
			if($dle['encrypt'] === 'pfx'){
				$dle['encrypt'] = 'client';
				$dle['client_encrypt'] = 'pfx';
				$dle['client_decrypt_option'] = '-d';
			}
		}

 		if($post['property_list:zmc_type'] === 'windowssqlserver'){
 			if($post['data_source'] === 'manually_type_in'){
	 			if(!empty($dle['application_version']) && !empty($dle['server_name']) && !empty($dle['instance_name']) && !empty($dle['database_name'])){
	 				$dle['disk_device'] = $dle['application_version'] . "\\" . $dle['server_name'] . "\\" . $dle['instance_name'] . "\\" . $dle['database_name'];
	 				unset($dle['application_version']);
	 				unset($dle['server_name']);
	 				unset($dle['instance_name']);
	 				unset($dle['database_name']);
 					unset($dle['discovered_components']);
	 			} else {
	 				$pm->addError("'Application Version', 'Server Name', 'Instance Name', and 'Database Name' need to be specified for selective Microsoft SQL Server backup");
	 			}
 			} else if($post['data_source'] === 'all') {
 				$dle['disk_device'] = 'ZMC_MSSQL';
 				unset($dle['application_version']);
 				unset($dle['server_name']);
 				unset($dle['instance_name']);
 				unset($dle['database_name']);
 				unset($dle['discovered_components']);
 			} else {
 				$dle['disk_device'] = $post['data_source'];
 				unset($dle['application_version']);
 				unset($dle['server_name']);
 				unset($dle['instance_name']);
 				unset($dle['database_name']);
 				unset($dle['discovered_components']);
 			}	
		} elseif ($post['property_list:zmc_type'] === 'windowsexchange'){
			if($post['data_source'] === 'manually_type_in'){
				if(!empty($dle['application_version']) && !empty($dle['server_name']) && !empty($dle['database_name'])){
					$dle['disk_device'] = $dle['application_version'] . "\\" . $dle['server_name'] . "\\" . $dle['database_name'];
					unset($dle['application_version']);
					unset($dle['server_name']);
					unset($dle['database_name']);
 					unset($dle['discovered_components']);
				} else {
					$pm->addError("'Application Version', 'Server Name', 'Database', and 'File Name' need to be specified for selective Microsoft Exchange backup");
				}
			} else if($post['data_source'] === 'all') {
				$dle['disk_device'] = 'ZMC_MSExchange';
				unset($dle['application_version']);
				unset($dle['server_name']);
				unset($dle['database_name']);
				unset($dle['discovered_components']);
			} else {
 				$dle['disk_device'] = $post['data_source'];
				unset($dle['application_version']);
				unset($dle['server_name']);
				unset($dle['database_name']);
				unset($dle['discovered_components']);
 			}	
		} elseif ($post['property_list:zmc_type'] === 'windowshyperv') {
			if($post['data_source'] === 'manually_type_in'){
				if(!empty($dle['application_version']) && !empty($dle['server_name']) && !empty($dle['instance_name']) && !empty($dle['database_name'])){
					$dle['disk_device'] = $dle['application_version'] . "\\" . $dle['server_name'] . "\\" . $dle['instance_name'] . "\\" . $dle['database_name'];
					unset($dle['application_version']);
					unset($dle['server_name']);
					unset($dle['instance_name']);
					unset($dle['database_name']);
					unset($dle['discovered_components']);
				} else {
					$pm->addError("'Application Version', 'Server Name', 'Instance Name', and 'Database Name' need to be specified for selective Microsoft Hyper-V backup");
				}
			} else if($post['data_source'] === 'all') {
				$dle['disk_device'] = 'ZMC_MSHyperV';
				unset($dle['application_version']);
				unset($dle['server_name']);
				unset($dle['instance_name']);
				unset($dle['database_name']);
				unset($dle['discovered_components']);
			} else {
				$dle['disk_device'] = $post['data_source'];
				unset($dle['application_version']);
				unset($dle['server_name']);
				unset($dle['instance_name']);
				unset($dle['database_name']);
				unset($dle['discovered_components']);
			}
		}
		unset($dle['data_source']);
		
		ZMC::assertValidAmandaPath($pm, $post['disk_device']);
		if (empty($dle['disk_name']) && !empty($dle['disk_device']))
			$dle['disk_name'] = $dle['disk_device'];



		else
			ZMC::assertValidAmandaPath($pm, $post['disk_name']);

		self::setNaturalKey($pm, $dle);
		if (!empty($post['natural_key_orig']))
			$dle['natural_key_orig'] = $post['natural_key_orig'];

		if (!empty($post['host_name']) && !ZMC::isValidHostname($post['host_name']))
		{
			$pm->addWarnError('Invalid characters in host name "' . $post['host_name'] . '". Do not use spaces. Use only alphanumeric characters and . - _ ');
			if (ZMC::$registry->safe_mode) return;
		}

		return $dle;
	}

	protected static function output(ZMC_Registry_MessageBox $pm, array $dle)
	{
		if (!empty($dle['exclude']))
		{
			$excludes = explode('" "', substr($dle['exclude'], 1, -1));
			sort($excludes);
			$dle['exclude'] = (trim(implode("\n", $excludes)));
		}
		self::setNaturalKey($pm, $dle);
		if (isset($dle['property_list:zmc_custom_app']))
			$dle['property_list:zmc_override_app'] = $dle['property_list:zmc_custom_app'];

		if (isset($dle['disk_name']) && $dle['disk_name'] === $dle['disk_device'])
			unset($dle['disk_name']);
		
		if(isset($dle['compress'])) {
			if($dle['compress'] === 'client custom') {
				$dle['property_list:zmc_custom_compress'] = $dle['client_custom_compress'];
				unset($dle['client_custom_compress']);
			} elseif ($dle['compress'] === 'server custom'){
				$dle['property_list:zmc_custom_compress'] = $dle['server_custom_compress'];
				unset($dle['server_custom_compress']);					
			}
		}
		
		if(isset($dle['encrypt']) && $dle['encrypt'] === 'client' && isset($dle['client_encrypt'])){
			if($dle['client_encrypt'] === 'zwcaes'){
				$dle['encrypt'] = 'zwcaes';
			}
			if($dle['client_encrypt'] === 'pfx'){
				$dle['encrypt'] = 'pfx';
			}
		}
		
		if($dle['property_list:zmc_type'] === 'windowssqlserver'){
			$disk_device = $dle['disk_device'];
			if($dle['disk_device'] !== 'ZMC_MSSQL'){
				$tmpArray = explode("\\", $dle['disk_device']);
				$dle['application_version'] = $tmpArray[0];
				$dle['server_name'] = $tmpArray[1];
				$dle['instance_name'] = $tmpArray[2];
				$dle['database_name'] = $tmpArray[3];
			}
			$dle['property_list:level_1_backup'] = $dle['property_list:zmc_amanda_app'];
			$dle['level_1_backup_disabled'] = $dle['property_list:zmc_amanda_app'];
		} elseif ($dle['property_list:zmc_type'] === 'windowsexchange'){
			if($dle['disk_device'] !== 'ZMC_MSExchange'){
				$tmpArray = explode("\\", $dle['disk_device']);
				$dle['application_version'] = $tmpArray[0];
				$dle['server_name'] = $tmpArray[1];
				$dle['database_name'] = $tmpArray[2];
			}
			$dle['property_list:level_1_backup'] = $dle['property_list:zmc_amanda_app'];
			$dle['level_1_backup_disabled'] = $dle['property_list:zmc_amanda_app'];
		} elseif ($dle['property_list:zmc_type'] === 'windowshyperv'){
			if($dle['disk_device'] !== 'ZMC_MSHyperV'){
				$tmpArray = explode("\\", $dle['disk_device']);
				$dle['application_version'] = $tmpArray[0];
				$dle['server_name'] = $tmpArray[1];
				$dle['instance_name'] = $tmpArray[2];
				$dle['database_name'] = $tmpArray[3];
			}
		} elseif($dle['property_list:zmc_type'] === 'windowstemplate' && $dle['disk_device'] === 'ALL_LOCAL_DRIVES')
			$dle['all_local_drives'] = 'on';

		return $dle;
	}

	protected static function outputNdmp(ZMC_Registry_MessageBox $pm, array $dle)
	{
		$dle = self::output($pm, $dle);
		return $dle;
	}

	protected static function outputWindows(ZMC_Registry_MessageBox $pm, array $dle)
	{
		$dle = self::output($pm, $dle);
		if (!empty($dle['disk_device']))
			$dle['disk_device'] = ucFirst(str_replace("/", "\\", $dle['disk_device'])); 

		if (!empty($dle['exclude'])) 
			$dle['exclude'] = str_replace('\\\\', '\\', $dle['exclude']);
		return $dle;
	}

	

	protected static function inputSolaris(ZMC_Registry_MessageBox $pm, array $post)
	{
		
		if (($post['property_list:zmc_extended_attributes'] === 'suntar') && !empty($post['exclude']))
			$pm->addEscapedError("Exclude patterns can not be used on Solaris, because extended attributes have been enabled.\n(<a href='http://wiki.wocloud.cn/man/amsuntar.8.html'>&quot;suntar&quot;</a> does not support extended attributes.)");

		return self::inputNixBase($pm, $post);
	}

	protected static function inputMac(ZMC_Registry_MessageBox $pm, array $post)
	{
		return self::inputNixBase($pm, $post);
	}

	protected static function inputUnix(ZMC_Registry_MessageBox $pm, array $post)
	{
		return self::inputNixBase($pm, $post);
	}

	protected static function inputPostgresql(ZMC_Registry_MessageBox $pm, array $post)
	{
		return self::inputNixBase($pm, $post); 
	}

	protected static function inputNixBase($pm, $post)
	{
		$post['disk_device'] = ZMC_Type_AmandaApps::assertValidDir($pm, $post['disk_device'], ZMC_Type_AmandaApps::DIR_UNIX, '目录');
		self::inputFilterCludes($pm, $post);
		return self::input($pm, $post);
	}

	protected static function inputWindowsss(ZMC_Registry_MessageBox $pm, array $post)
	{
		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindowsoracle(ZMC_Registry_MessageBox $pm, array $post)
	{
		if (!preg_match('/^[[:alnum:]_][-[:alnum:]._]*$/', $post['disk_device']))
		    $pm->addWarnError('SID List Names may only use letters, digits, the hyphen ("-") character, and the underscore character ("_").');

		return self::inputOracleBase($pm, $post);
	}

	protected static function inputLinuxoracle(ZMC_Registry_MessageBox $pm, array $post)
	{
		if (!ZMC::isalnum_($post['disk_device']))
		    $pm->addWarnError('SID List Names may only use letters, digits, and the underscore character ("_").');

		return self::inputOracleBase($pm, $post);
	}

	protected static function inputOracleBase(ZMC_Registry_MessageBox $pm, array $post)
	{

		return self::input($pm, $post);
	}

	protected static function inputWindowstemplate(ZMC_Registry_MessageBox $pm, array $post)
	{
		if(!empty($post['all_local_drives'])) {
			$post['disk_device'] = 'ALL_LOCAL_DRIVES';
			unset($post['all_local_drives']);
		} elseif (!ZMC::isalnum_($post['disk_device']))
		    $pm->addWarnError('Windows Template 名允许使用字母、数字和下划线 ("_").');

		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindows(ZMC_Registry_MessageBox $pm, array $post)
	{
		$post['disk_device'] = ZMC_Type_AmandaApps::assertValidDir($pm, $post['disk_device'], array(ZMC_Type_AmandaApps::DIR_WINDOWS, ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE));
		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindowssharept(ZMC_Registry_MessageBox $pm, array $post)
	{
		if ($post['property_list:zmc_amanda_app'] === 'Zmanda Windows Client') 
			$post['property_list:zmc_amanda_app'] = 'zwcdiff'; 
		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindowsexchange(ZMC_Registry_MessageBox $pm, array $post)
	{
		if ($post['property_list:zmc_amanda_app'] === 'Zmanda Windows Client'){ 
			if(isset($post['level_1_backup_disabled']))
				$post['property_list:zmc_amanda_app'] = $post['level_1_backup_disabled'];
			else
				$post['property_list:zmc_amanda_app'] = $post['property_list:level_1_backup'];
		}
		unset($post['property_list:level_1_backup']);
		unset($post['level_1_backup_disabled']);
		return self::inputWindowsBase($pm, $post);
	}
	
	protected static function inputWindowshyperv(ZMC_Registry_MessageBox $pm, array $post)
	{
		if ($post['property_list:zmc_amanda_app'] === 'Zmanda Windows Client'){ 
			$post['property_list:zmc_amanda_app'] = 'zwcdiff';
		}
		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindowssqlserver(ZMC_Registry_MessageBox $pm, array $post)
	{
		if ($post['property_list:zmc_amanda_app'] === 'Zmanda Windows Client'){ 
			if(isset($post['level_1_backup_disabled']))
				$post['property_list:zmc_amanda_app'] = $post['level_1_backup_disabled'];
			else
				$post['property_list:zmc_amanda_app'] = $post['property_list:level_1_backup'];
		}
		unset($post['property_list:level_1_backup']);
		unset($post['level_1_backup_disabled']);
		return self::inputWindowsBase($pm, $post);
	}

	protected static function inputWindowsBase(ZMC_Registry_MessageBox $pm, array $post)
	{
		if ($post['property_list:zmc_amanda_app'] === 'Zmanda Windows Client') 
			$post['property_list:zmc_amanda_app'] = 'windowsDump';

		foreach(array('include', 'exclude') as $field)
		{
			if (!empty($post[$field]))
			{
				
				if (strpos($post[$field], "\n"))
					$post[$field] = explode("\n", $post[$field]);
				else
					$post[$field] = ZMC_Yasumi_Parser::parseQuotedStrings(str_replace('\\', '/', $post[$field]), $comment);

				foreach($post[$field] as &$path)
				{
					$path = (($path[0] === '"') ? trim($path, "\"\r\n") : trim($path, ":\r\n"));
					if (empty($path)) continue;
					ZMC::normalizeWindowsDle($path); 
					$path = preg_replace("/(\/)+|\\+|\\\\+/", "\\", trim($path)); 
					$path = rtrim(str_replace("\\","\\\\", $path),"\\"); 
					if(strlen($path) > 1 && $path[0] === '\\' && $path[1] === '\\'){
						$path = preg_replace("/(\/)+|\\+|\\\\+/", "\\", trim($path)); 
						$path = "\\\\".trim(str_replace("\\","\\\\", $path),"\\"); 
                    }
				}
			}
			if (empty($post[$field]))
				$post[$field] = null;
			else{
				 $post[$field] = array_filter($post[$field]); 
				$post[$field] = '"' . implode('" "', $post[$field]) . '"';
			}
		}
		$post[$field] = "\"".trim($post[$field], "\" ")."\"";    
		return self::input($pm, $post);
	}

	protected static function inputVmware(ZMC_Registry_MessageBox $pm, array $post)
	{
		if (!ZMC::isValidHostname($post['property_list:esx_host_name']))
			$pm->addWarnError('ESX Host Name invalid: ' . $post['property_list:esx_host_name']);
		if (false !== strpos($post['property_list:esx_username'], '%'))
			$pm->addWarnError('Username must not contain a "%" character.');
		if (false !== strpos($post['property_list:esx_password'], ' '))
			$pm->addWarnError('Username must not contain a space character.');
		$post['disk_device'] = '\\\\' . $post['property_list:esx_host_name']
			. (empty($post['property_list:esx_datastore']) ? '' : '\\' . $post['property_list:esx_datastore'])
			. '\\' . $post['property_list:esx_vm'];
		if (preg_match('/^vmware/',$post['property_list:zmc_amanda_app'])){
			if($post['property_list:zmc_quiesce'] == 'NO')
				$post['property_list:zmc_amanda_app'] = 'vmware_quiesce_off';
			if($post['property_list:zmc_quiesce'] == 'YES')
				$post['property_list:zmc_amanda_app'] = 'vmware_quiesce_on';
		}
			
		ZMC_Type_AmandaApps::assertValidDir($pm, $post['disk_device'], ZMC_Type_AmandaApps::DIR_VMWARE);
		return self::input($pm, $post);
	}

	protected static function inputNdmp(ZMC_Registry_MessageBox $pm, array $post)
	{
		
		if (is_array($pm->raw_binding)) 
		{
			
			$device_type = $pm->device_profile_list[$pm->edit['profile_name']]['_key_name'];
			if ($post['data_path'] === 'directtcp')
			{
				if ($device_type !== 'changer_ndmp')
					$pm->addError('Data Path can not be "Direct TCP", unless using NDMP Changer device (see '
						. ZMC::getPageUrl($pm, 'Admin', 'Devices') . ').');
			}
			elseif ($device_type === 'changer_ndmp')
					$pm->addError('Data Path can not be "Amanda", when using NDMP Changer device (see '
						. ZMC::getPageUrl($pm, 'Admin', 'Devices') . ').');
		}
		$post['property_list:filer_volume'] = '/' . trim($post['property_list:filer_volume'], '/');
		$post['property_list:filer_directory'] = '/' . trim($post['property_list:filer_directory'], '/');
		$post['disk_device'] = '//' . $post['property_list:filer_host_name']
			. $post['property_list:filer_volume'] . $post['property_list:filer_directory'];
		return self::input($pm, $post);
	}

	protected static function inputCifs(ZMC_Registry_MessageBox $pm, array $post)
	{
		ZMC_Type_AmandaApps::assertValidDir($pm, $post['disk_device'], ZMC_Type_AmandaApps::DIR_CIFS);
		return self::input($pm, $post);
	}

	
	protected static function inputNfs(ZMC_Registry_MessageBox $pm, array $post) 
	{
		
		self::inputFilterCludes($pm, $post); 
		return self::input($pm, $post);
	}
	

	protected static function setNaturalKey($pm, &$dle)
	{
		if (!empty($dle['host_name']) && !empty($dle['disk_name']))
			$dle['natural_key'] = $pm->selected_name . '|' . $dle['host_name'] . '|' . $dle['disk_name'];
	}

	private static function inputFilterCludes($pm, &$dle)
	{
		foreach(array('include', 'exclude') as $field)
		{
			$tmp = trim($dle[$field]);
			$tmp = (empty($tmp) ? array() : explode("\n", $tmp));
			foreach($tmp as &$line){
				$line = rtrim($line, " \t\n\r");
				$line = trim($line, "\"\'");
			}
			$tmp = array_flip($tmp);
			if (ZMC::$registry->auto_exclude_unix_dirs)
				self::excludeNixDirs($pm, $dle['host_name'], $dle['disk_device'], $tmp);

			if (empty($tmp))
			{
				$dle[$field] = null;
				continue;
			}
			$tmp = array_keys($tmp);
			sort($tmp);
			foreach($tmp as &$line)
				$line = '"' . rtrim($line) . '"';
			$dle[$field] = implode(' ', $tmp);
			if (empty($dle[$field]))
				$dle[$field] = null;
		}
	}

	private static function excludeNixDirs($pm, $hostname, $dd, &$excludes) 
	{
		$dd = rtrim($dd, "/ \t\n\r") . '/';
		$ddlen = strlen($dd);
		$dirs = file(ZMC::$registry->dle_unix_deny, FILE_IGNORE_NEW_LINES);
		foreach($dirs as &$dir)
			$dir = rtrim($dir, "/ \t\n\r") . '/';

		
		{
			foreach($pm->device_profile_list as $profile)
				if (!empty($profile['changer']['changerdev_prefix']))
					$dirs[] = rtrim($profile['changer']['changerdev_prefix'], "/ \t\n\r") . '/';
			foreach(ZMC_BackupSet::getRawBindings($pm) as $binding)
				foreach($binding['holdingdisk_list'] as $holding)
				{
					$candidate = rtrim($holding['directory'], "/ \t\n\r") . '/';
					if (strncmp($candidate, '/var/lib/amanda/staging/', 24))
						$dirs[] = $candidate;
				}
		}

		foreach($dirs as $dir)
			if (!strncmp($dd, $dir, $ddlen))
			{
				$key = './' . rtrim(substr($dir, $ddlen), '/');
				if (!isset($excludes[$key]))
					if (is_dir($dir))
						$excludes[$key] = true;
			}
	}
}
