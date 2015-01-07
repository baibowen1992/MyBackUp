<?













class ZMC_Type_What extends ZMC_Type
{
protected static $init = false;
protected static $zmcTypes = array();
protected static $defaultTypeArray = array();
protected static $labelFixes = array(
	'estimate' => array(
		'client' => '准确可靠',
		'client calcsize' => '准确可靠',
		'client server' => '准确可靠',
		'calcsize' => '通常可靠',
		'calcsize client' => '通常可靠',
		'calcsize server' => '通常可靠',
		'server' => '历史平均',
		'server calcsize' => '历史平均',
		'server client' => '历史平均'
	),
	'encrypt' => array(
		'none' => '无',
//		'client' => '客户端',
		'server' => '服务端',
//		'pfx' => '客户端PFX认证',
//		'zwcaes' => '客户端AES 256-bit',
	),
	'compress' => array(
		
		'none' => '无',
		'client fast' => '客户端最快',
		'client best' => '客户端最佳',
//		'client custom' => '客户端自定义',
		'server fast' => '服务端最快',
		'server best' => '服务端最佳',
//		'server custom' => '服务器端自定义',
//		'server idle' => '服务端 (云上传)',
	),
	'data_source' => array(
		'all' => '选择所有',
		'manually_type_in' => '手动',
	)
);

protected static function init()
{
	if (self::$init) return;
	
	self::$defaultTypeArray = array(
		
		'indexes_explorable' => true, 
		'form_classes' => 'wocloudLongInput wocloudShortLabel',
		'advanced_form_classes' => 'wocloudLongInput wocloudShortLabel',
		'form' => array(
			'property_list:zmc_type' =>	array(1, 'veRmpaw', '', '备份类型', 'zmc_type DLE property'),
			'property_list:zmc_disklist' =>	array(2, 'veRmpaw', '', '备份系统客户端列表名', 'zmc_disklist DLE property'),
			'property_list:zmc_version' =>	array(3, 'veRmpaw', '', '备份系统版本', 'zmc_version DLE property'),
			'natural_key_orig' =>	array(4, 'vermpaw', '', 'ZMC DLE natural key (id)', 'internal use only'),
			'host_name' =>			array(50, 'VERMpaw', '', '备份主机名', '备份节点主机名(推荐使用完整正式域名)', ''),
			'disk_device' =>		array(60, 'VERMPaw', '/', '备份目录', '备份节点需要备份的目录', ''),
			'exclude' =>			array(70, 'VErMpaw', '',	'<a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_aee/dle_unix.deny">排除的文件</a>: <a id="exclude_help_link" style="float:right;" class="wocloudHelpLink wocloudHelpLinkHug" target="_blank" href="' . ZMC::$registry->wiki . 'Backup_What#Exclude_Specifications"></a>', 'Newline separated GNU tar/glob style exclude patterns specifying directories/files to skip. Please refer to documentation as the format differs for each DLE type.', 'textarea'),
			'floatright_pre' =>		array(10, 'VErmpaw', '<div style="float:right">', '', '', 'html'),
			'encrypt' =>			array(20, 'VErMPaw', array(
					'none' => 1,
					'server' => 0,
//					'client' => 0,
				), '加密', '', 'selectShorterInput', '',
				'
				<div id="client_info_icon" class="contextualInfoImage"><a id="client_info_help1" target="_blank" href=""><img width="18" height="18" align="top" alt="" title="" src="/images/icons/icon_info.png"/></a>
					<div style="position:absolute; top:15px; right:50px;" class="contextualInfo">
						<p>
							请查看<a id="client_info_help2" target="_blank">备份系统手册</a>获取加密过程中客户端需要使用的加密密钥副本。
						</p>
					</div>
				</div>
				<a class="wocloudIconWarning" id="encrypt_keys_link" href="/ZMC_Keys?foo=bar" target="_blank">下载<img style="vertical-align:text-top" src="/images/icons/icon_key.png" alt="key" title="请下载加密密钥并保存到安全的位置。" /></a>
			<script>
				var encrypt_keys_link = function()
				{
					var e=gebi("encrypt")
					var c=gebi("compress")
					var server=gebi("encrypt_keys_link")
					var client=gebi("client_info_icon")
					if (e.value === "server")
					{
						server.style.visibility = "visible"
					}
					else
					{
						server.style.visibility = "hidden"
					}

					if (e.value === "client")
					{
						client.style.visibility = "visible"
						if (c.value === "server fast" || c.value === "server best" || c.value === "server custom")
						{
							c.value = "none"
							alert("不能在客户端加密在服务器端压缩。压缩被忽略。")
						}
					}
					else
					{
						client.style.visibility = "hidden"
					}

					server.search = e.value
				}
				gebi("client_info_help1").href = gebi("client_info_help2").href = gebi("wocloudHelpLinkId").href.replace(/#.*/, "#Compression_and_Encryption")
				encrypt_keys_link();
			</script>
				', 'onChange="encrypt_keys_link()"'
			),
			




			'compress' => array(30, 'VErMPaw',
				array(
					'none' => 1,
					'client fast' => 0,
					'client best' => 0,
					'server fast' => 0,
					'server best' => 0,
					
				),
				'压缩', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'无法实现客户端加密而在服务端压缩。加密选项失效。\') }"'
			),
			'floatright_post' =>	array(49, 'VErmpaw', '</div>', '', '', 'html'),

			
			'estimate' =>			array(100, 'VErMPAw', array('server' => 1), 
				'预估', '', 'radio', '<img src="/images/section/backup/slow_fast_slider.png" width="560" height="20" /><br />', '<br />',
			),
			'app_caption_pre' =>	array(129, 'property_list:zmc_custom_app', '<fieldset style="float:right;"><legend>备份系统客户端应用程序</legend>', '', '', 'html'),
			'property_list:zmc_extended_attributes' => array(130, 'VErmPAw', array('checked' => false, 'on' => 'star', 'off' => 'gtar'), '扩展属性', '', 'checkbox', '', '', 'onChange="zmcRegistry.app_set_recommended(); zmcRegistry.app_highlight_recommended();"'),
			'property_list:zmc_amanda_app' => array(135, 'VeRmPAw', 'gtar', '推荐', '备份系统建议的客户端应用程序', '', '', '', ''),
			'property_list:zmc_custom_app' => array(140, 'VErMpAw', '', '重定义', '备份客户算覆盖策略', 'select', '', '', 'onKeyUp="zmcRegistry.app_highlight_recommended()" onChange="zmcRegistry.app_highlight_recommended();"'),
			'property_list:zmc_override_app' => array(141, 'VErMpAw', '', ' ', '自定义备份客户算覆盖策略', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.app_highlight_recommended()"'),
			'app_caption_post' =>	array(145, 'property_list:zmc_custom_app', '</fieldset>', '', '', 'html'),
			'disk_name' =>			array(150, 'VErMpAw', '', '别名', '为备份项选择一个易读且唯一的名字(DLE).', ''),
			'maxdumps' =>			array(170, 'VErMpAw', '', '客户端最大备份数', '', 'textShortestInput', '', '客户端允许的同时执行的最大备份项数目</a>', 'style="width:40px;"       maxlength="2"'),
			'strategy' =>			array(180, 'VERMpAw',
				array(
					'default' => '默认',
					'standard' => '标准，允许全量和增量备份',
					'skip' => '跳过，不备份这个备份项',
					'nofull' => '不做全量，仅做一级增量备份',
					'noinc' => '不做增量，全是全量备份',
				),
				'策略', '', 'selectLongerInput'),
			'holdingdisk' =>		array(190, 'VErMPAw', array('auto' => '如果可能就使用缓存', 'never' => '不适用缓存', 'required' => '需要(如空间不足将不执行备份)'), '分步', '', 'selectLongerInput', '', ''),
			'property_list:zmc_comments' =>		array(200, 'VErMpAw', '', '备注', '', 'textareaLongerInput'),
			'property_list:zmc_show_advanced' => array(998, 'vERMPAw', 0, '查看高级设置', 'sticky preference', 'hidden'),
		)
	);
	
	







	self::$zmcTypes = array(
/*		'vmware' => array(
			'name' => 'VMWare ESX Virtual Machine',
			'category' => 'Applications',
			'browseable' => false,
			'searchable' => false,
			'form_classes' => 'wocloudLongInput wocloudLongLabel',
			'amanda_pass' => 'esxpass', 
			'form' => array(
				'host_name' =>		array('vermpaw' => 'vermPaw', 'default' => '127.0.0.1'),
				'exclude' =>		null, 
				'encrypt' =>		array('default' => array(
					'none' => 1,
					'server' => 0,
					'client' => null,
					),
				),
				'compress' =>		array('default' => array(
						'none' => 1,
						'client fast' => null,
						'client best' => null,
						'server fast' => 0,
						'server best' => 0,
						
					), 'html_after' => '<script>gebi("client_info_icon").style.display="none";</script>'
				),
				'holdingdisk' =>	array('html_after' => 'Staging is not efficient for VMs, but necessary to ensure proper filling of LTO-size tapes, when using physical tape media.'),
				'property_list:esx_host_name' =>	array(60 , 'VERMpaw', '', 'ESX Host Name', 'Hostname or IP address of ESX server'),
				'property_list:esx_vm' =>	array(62, 'VERMpaw',  '', 'Virtual Machine Name'),
				'property_list:esx_datastore' =>	array(64, 'VERMpaw',  '', 'Datastore Name'),
				'note' =>		array(65, 'VErmpaw', '<div class="p"><p><span class="wocloudUserErrorsText">Requirements:</span> Vmdks must exist in the same folder as<br />the vmx files; config.version 7+; CBT; <a target="_blank" id="note_link_id">More</a>.</div><script>gebi("note_link_id").href = gebi("wocloudHelpLinkId").href</script>', '', '', 'html'),
				'property_list:esx_username' =>	array(12, 'VErMpaw', '', 'ESX server user', '', 'text'),
				'property_list:esx_password' => array(14, 'property_list:esx_username', '', 'ESX server password <input style="float:none;" title="Show Password" id="property_list:esx_password_box" type="checkbox" onclick="this.form[\'property_list:esx_password\'].type = (this.form[\'property_list:esx_password\'].type === \'password\' ? \'text\' : \'password\');" />', '', 'password'),
				'property_list:zmc_amanda_app' =>		array('default' => 'vmware_quiesce_on'),
				'property_list:zmc_extended_attributes' => null,
				'property_list:zmc_custom_app' =>		array('form_type' => ''),
				'property_list:zmc_override_app' =>		null,
				'property_list:zmc_quiesce' =>	array(175, 'VErMpAw',
						array(
								'YES' => 'Do quiesce',
								'NO' => 'Do not quiesce',
						),
						'Quiesce', '', 'selectLongerInput'),
			),
		),
*/
		
		
		
		
		
		
		


		'cifs' => array(
			'license_group' => 'cifslic',
			'name' => 'Share',
			'pretty_name' => 'Network/CIFS Share',
			'element_name' => 'file/folder',
			'element_names' => 'files/folders',
			'category' => 'File Systems',
			'amanda_pass' => 'cifs_network_shares', 
			'form' => array(
				'host_name' =>		array('default' => '127.0.0.1', 'tip' => 'Fully qualified domain name of machine (running Amanda CIFS client) with access to the network share'),
				'disk_device' =>	array(60, 'VERMpaw',  '\\\\', '共享名', 'Exported directory path of network share on client', ''),
				'exclude' =>		array('html_after' => '<br /><br />注意：请勿直接在*nix文件系统下挂载CIFS。<br />如果是直接挂载的请选择使用 Linux/*nix 文件系统类型的备份项。<br />'),
				'property_list:zmc_share_username' =>	array(12, 'VErMpaw', '', '用户名', '', 'text'),
				'property_list:zmc_share_password' =>	array(14, 'property_list:zmc_share_username', '', '密码 <input style="float:none;" title="Show Password" id="property_list:zmc_share_password_box" type="checkbox" onclick="this.form[\'property_list:zmc_share_password\'].type = (this.form[\'property_list:zmc_share_password\'].type === \'password\' ? \'text\' : \'password\');" />', '', 'password'),
				'property_list:zmc_share_domain' =>		array(16, 'property_list:zmc_share_username', '', '网络域名', '', 'text'),
				'estimate' =>		array('default' =>	array('client server' => 0, 'server' => null, 'server client' => 1)),
				'property_list:zmc_amanda_app' =>		array('default' => 'cifs'),
				'property_list:zmc_extended_attributes' => null,
				'property_list:zmc_custom_app' =>		array('form_type' => ''),
				'property_list:zmc_override_app' =>		null,
			),
		),

		'unix' => array(
			'name' => 'Linux/*nix',
			'pretty_name' => 'Linux',
			'element_name' => 'file/directory',
			'element_names' => 'files/directories',
			'category' => 'File Systems',
			'form' => array(
				'exclude' => array(70, 'VErMpaw', '',	'排除文件: ', '每行一个匹配模式，使用tar/glob 格式匹配需要排除的文件和目录，举例："./*.bak"', 'textarea', '', ''),
				'estimate' => array('default' => array(
					'client server' => 0,
					'calcsize server' => 0,
					'server calcsize' => 1,
					'server' => null)),
				'property_list:zmc_custom_app' => array('default' => array(
					'' => '--NONE--',
					'0' => '--CUSTOM--',
					'gtar' => 'GNU tar',
				   	'star' => 'Schily star',
					
				)),
				'app_caption_post' =>	array(145, 'property_list:zmc_custom_app', '<div id="star_note" style="visibility:hidden; clear:left;" />Note: Using &quot;star&quot; requires manual installation on each client.</div></fieldset>', '', '', 'html'),
				'compress' => array(30, 'VErMPaw',
					array(
						'none' => 1,
						'client fast' => 0,
						'client best' => 0,
//						'client custom' => 0,
						'server fast' => 0,
						'server best' => 0,
//						'server custom' => 0,
					),
					'压缩', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'Can not encrypt on the client and compress on the server.  Encryption disabled.\') } else {zmcRegistry.adjust_custom_compress();}"'
				),
				'property_list:zmc_custom_compress' => array(31, 'VErMpaw', '/bin/pigz', '自定义压缩', '输入自定义压缩程序', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.adjust_custom_compress()"'),
			)
		),

		'nfs' => array(
			'license_group' => 'nfslic',
			'name' => 'NFS/iSCSI/Lustre',
			'element_name' => 'file/directory',
			'element_names' => 'files/directories',
			'category' => 'File Systems',
			'form' => array(
				'host_name' =>		array('vermpaw' => 'vermPaw', 'default' => '127.0.0.1'),
				'disk_device' =>	array('label' => '挂载目录', 'default' => '/NFS-mounted-directory'),
				'estimate' => 'unix', 
				'exclude' => 'unix',
				'property_list:zmc_custom_app' => 'unix',
			)
		),


		
		'windowsbase' => array(
			'license_group' => 'windows',
			'name' => 'WindowsBase',
			'category' => null, 
			'form' => array(
				'estimate' => 'unix', 
				
				'exclude' => array(70, 'VErMpaw', '', '排除目录<br />和文件', '使用换行符来分隔多个需要排除的目录或者文件，支持通配符。例如： C:\Data or C:\Data\*.jpg or foo.? or *.doc', 'textarea', '', ''),
				'encrypt' => array('default' => array(
						'none' => 1,
						'pfx' => 0,
						'zwcaes' => 0,
						'server' => 0,
						'client' => null,
					),
				),
				'compress' => array(30, 'VErMPaw',
					array(
						'none' => 1,
						'client fast' => 0,
						'client best' => 0,
						'server fast' => 0,
						'server best' => 0,
//						'server custom' => 0,
					),
					'压缩', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'Can not encrypt on the client and compress on the server.  Encryption disabled.\') } else {zmcRegistry.adjust_custom_compress();}"'
				),
				'property_list:zmc_custom_compress' => array(31, 'VErMpaw', '/bin/pigz', '自定义压缩', '自定义压缩软件', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.adjust_custom_compress()"'),
				'property_list:zmc_amanda_app' => array('default' => 'Zmanda Windows Client'),
				'property_list:zmc_custom_app' => array('form_type' => ''),
				'property_list:zmc_override_app' =>	null,
				'property_list:zmc_extended_attributes' => array('vermpaw' => 'VermPAw', 'default' => array('checked' => true, 'on' => 'Zmanda Windows Client')),
			),
		),

		'windows' => array(
			'name' => 'Windows',
			'element_name' => 'file/folder',
			'element_names' => 'files/folders',
			'pretty_name' => 'Windows NTFS/ReFS', 
			'category' => 'File Systems',
			'form' => array(
				'disk_device' =>	array('default' => 'C:\\'),
				'estimate' => 'unix', 
				'exclude' =>								'windowsbase',
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' =>			'windowsbase',
				'property_list:zmc_custom_app' =>			'windowsbase',
				'property_list:zmc_override_app' =>			'windowsbase',
				'property_list:zmc_extended_attributes' => array('vermpaw' => 'VermPAw', 'default' => array('checked' => true, 'on' => 'Zmanda Windows Client'), 'html_after' =>
					'<div class="contextualInfoImage"><a target="_blank" id="extended_attrib_link1" href=""><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
						<div class="contextualInfo">
							<p>
								扩展文件属性备份对于UNIX / Linux文件系统是可选的，对于Windows文件系统则是自动的。
								Unix/Linux文件系统需要Schily tar (star) or Solaris tar已经安装在客户端
								参考<a target="_blank" id="extended_attrib_link2" href="">documentation</a> 查看可选项。
							</p>
						</div>
					</div>
					<script>gebi("extended_attrib_link1").href = gebi("extended_attrib_link2").href = gebi("wocloudHelpLinkId").href.replace(/#.*/, "#Advanced_Options")</script>
					',),
			),
		),

/*		'windowsss' => array(
			'license_group' => 'windowsss',
			'name' => 'Windows System State',
			'browseable' => false,
			'searchable' => false,
			'conflict_resolvable' => array(
				ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING),
				ZMC_Type_AmandaApps::DIR_WINDOWS => array( ZMC_Type_AmandaApps::KEEP_EXISTING, ZMC_Type_AmandaApps::RENAME_RESTORED, ZMC_Type_AmandaApps::RENAME_EXISTING, ZMC_Type_AmandaApps::OVERWRITE_EXISTING,),
				ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => false,
				ZMC_Type_AmandaApps::DIR_UNIX => false, 
			),
			'target_dir_types' => array(ZMC_Type_AmandaApps::DIR_ORIGINAL, ZMC_Type_AmandaApps::DIR_WINDOWS),
			'restore_to_original_requires_express' => true,
			'element_name' => 'file/folder',
			'element_names' => 'files/folders',
			'category' => 'File Systems',
			'form' => array(
				'disk_device' => array(60, 'vermpaw' => 'VeRmPaw', 'ZMC_SystemState', 'Data Source'),
				'exclude' => null,
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'estimate' => 'unix', 
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' =>			'windowsbase',
				'property_list:zmc_custom_app' =>			'windowsbase',
				'property_list:zmc_override_app' =>			'windowsbase',
				'property_list:zmc_ad_roles' => array(300, 'VErMPAw', array('checked' => false, 'on' => 'on', 'off' => null), 'AD?', '', 'checkbox', '', ' Does this system have any Active Directory roles?'),
			)
		),
*/
		'windowstemplate' => array(
			'license_group' => 'windowstemplate',
			'name' => 'Windows Template',
			'browseable' => true,
			'searchable' => false,
			'element_name' => 'file/folder',
			'element_names' => 'files/folders',
			'category' => 'File Systems',
			'form' => array(
				'disk_device' =>	array('default' => '客户端Template名', 'label' => '客户端Template'),
				'all_local_drives' => array(61, 'VErMpaw', array('checked' => false, 'on' => 'on', 'off' => null), '全部本地磁盘', '备份客户端上所有本地磁盘', 'checkbox', '', '', 'onChange=zmcRegistry.adjust_windowstemplate();'),
				'estimate' => 'unix', 
				'exclude' =>								'windowsbase',
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' =>			'windowsbase',
				'property_list:zmc_custom_app' =>			'windowsbase',
				'property_list:zmc_override_app' =>			'windowsbase',
			)
		),

		
		



















/*		'linuxoracle' => array(
			'name' => 'Oracle on Linux/Solaris',
			'browseable' => false,
			'searchable' => false,
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Databases',
			'form' => array(
				'property_list:zmc_extended_attributes' => null,
				'disk_device' => array('default' => 'Oracle', 'label' => 'SID List Name', 'tip' => 'The Oracle sid list name configured in amanda-client.conf on the Oracle server', 'html_after' => '<a style="float:left; position:relative;" class="wocloudHelpLink" target="_blank" href="http://docs.wocloud.cn"></a>'),
				'exclude' => null,
				'property_list:zmc_amanda_app' => array('default' => 'rman'),
				'property_list:zmc_custom_app' => array('default' => array(
					'' => '--NONE--',
					'0' => '--CUSTOM--',
					'oracle' => 'oracle',
				   	'rman' => 'RMAN',
				)),
			)
		),
*/
		'windowsoracle' => array(
			'name' => 'Oracle on Windows',
			'browseable' => false,
			'searchable' => false,
			'conflict_resolvable' => array(
				ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
				ZMC_Type_AmandaApps::DIR_WINDOWS => true,
				ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => true,
				ZMC_Type_AmandaApps::DIR_UNIX => false, 
			),
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Databases',
			'form' => array(
				'exclude' => null,
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'estimate' => 'unix', 
				'disk_device' => array('visible' => false, 'default' => 'ZMC_Win-Oracle', 'label' => 'SID List Name', 'tip' => 'The Oracle sid list name configured in amanda-client.conf on the Oracle server', 'html_after' => '<a style="float:left; position:relative;" class="wocloudHelpLink" target="_blank" href="http://docs.wocloud.cn"></a>'),
				'property_list:zmc_extended_attributes' => null,
				'property_list:zmc_amanda_app' => array(135, 'VeRmPAw', 'ZwcInc','Recommended', 'ZMC recommended Amanda backup client application', '', '', '', ''),
				'property_list:zmc_custom_app' => array('default' => array(
					'' => '--NONE--',
					'0' => '--CUSTOM--',
					'zwcinc' => 'ZWC VSS Incremental',
				   	'zwcdiff' => 'ZWC VSS Differential',
				)),
			),
		),
/*
		'postgresql' => array(
			'license_group' => 'postgres',
			'name' => 'PostgreSQL',
			'browseable' => false,
			'searchable' => false,
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Databases',
			'indexes_explorable' => false,
			'form' => array(
				'disk_device' => array(60, 'vermpaw' => 'VERMPaw', '/var/lib/pgsql/data', 'Data Directory', 'The location of the PostgreSQL data directory'),
				'estimate' => array('default' => array('client server' => 1, 'calcsize' => null, 'server client' => 0)),
				'property_list:zmc_extended_attributes' => null,
				'property_list:zmc_amanda_app' => array('default' => 'postgresql'),
				'property_list:zmc_override_app' =>	array('visible' => false),
				'property_list:zmc_custom_app' => array('form_type' => ''),
				'exclude' => null,
			),
		),
*/
		'windowssqlserver' => array(
			'name' => 'Microsoft SQL Server',
			'browseable' => true,
			'searchable' => false,
			'conflict_resolvable' => array(
				ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
				ZMC_Type_AmandaApps::DIR_WINDOWS => false,
				ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => true,
				ZMC_Type_AmandaApps::DIR_UNIX => false, 
	 			ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING),
	 			ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING),
			),
			'target_dir_types' => array(ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH, ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME),
			'restore_to_original_requires_express' => true,
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Databases',
			'form' => array(
				'host_name' => array(50, 'vermpaw' => 'VERMpaw',  '', 'Host Name'),
				'disk_device' => array(60, 'vermpaw' => 'veRmPaw', 'ZMC_MSSQL', 'Data Source'),
				'estimate' => 'unix', 
				'exclude' => null,
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' => array(135, 'VeRmPAw', 'ZwcDiff','推荐', '推荐的备份客户端应用', '', '', '', ''),
				'property_list:zmc_custom_app' =>			'windowsoracle',
				'application_version' => array(61, 'VErMpaw', '', 'Version', '格式必须是 MSSQL-&lt;year&gt; (e.g. MSSQL-2012)', '', '', ''),
				'server_name' => array(62, 'VErMpaw', '', 'Server Name', '', ''),
				'instance_name' => array(63, 'VErMpaw', '', 'Instance Name', '', ''),
				'database_name' => array(64, 'VErMpaw', '', 'Database Name', '', ''),
				'data_source' => array(56, 'VErMPaw',
					array(
						'all' => 1,
						'manually_type_in' => 0,
					),
					'Databases', '', 'selectShorterInput', '<div style="width:500px; overflow-x:scroll;">',
					'</div><div style="padding-left:120px">Click "Discover" button to discover available databases</div>', 'size="10"; style="float:none; padding:0; min-width:498px; width:auto;" onChange=zmcRegistry.adjust_zmc_windowssqlserver();'),
				'discovered_components' =>	array(57, 'vErMpaw', '', '', ''),
				'level_1_backup_disabled' => array(179, 'VErMpAw',
						array(
								'zwcdiff' => 'Differential',
								'zwclog' => 'Log'
						),
						'Level 1 Backup', '', 'selectLongerInput', '',
						'Select backup level between full backups. Can\'t be changed later.', 'style="float:none; padding:0;"'),
				'property_list:level_1_backup' => array('vermpaw' => 'vermpaw', 'default' => 'zwcdiff'),
			)
		),

/*		
		'windowsexchange' => array(
			'name' => 'Microsoft Exchange',
			'browseable' => true,
			'searchable' => false,
			'conflict_resolvable' => array(
				ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
				ZMC_Type_AmandaApps::DIR_WINDOWS => array( ZMC_Type_AmandaApps::KEEP_EXISTING, ZMC_Type_AmandaApps::RENAME_RESTORED, ZMC_Type_AmandaApps::RENAME_EXISTING, ZMC_Type_AmandaApps::OVERWRITE_EXISTING,),
				ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => true,
				ZMC_Type_AmandaApps::DIR_UNIX => false, 
	 			ZMC_Type_AmandaApps::DIR_MS_EXCHANGE => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
			),
			'target_dir_types' => array(ZMC_Type_AmandaApps::DIR_MS_EXCHANGE, ZMC_Type_AmandaApps::DIR_WINDOWS),
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Applications',
			'form' => array(
				'disk_device' => array(60, 'vermpaw' => 'veRmPaw', 'ZMC_MSExchange', 'Data Source'),
				'exclude' => null,
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'estimate' => 'unix', 
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' =>			'windowsoracle',
				'property_list:zmc_custom_app' =>			'windowsoracle',
				'application_version' => array(61, 'VErMpaw', '', 'Version', 'Must be of format MSEXCHANGE-&lt;year&gt; (e.g. MSEXCHANGE-2012)', '', '', ''),
				'server_name' => array(62, 'VErMpaw', '', 'Server Name', '', ''),
				'database_name' => array(63, 'VErMpaw', '', 'Database', 'Must be of format &lt;Storage Group Name&gt; or &lt;Database Name&gt; (e.g. "First Storage Group" or "Employees Mailbox Database")', '', '', ''),
				'data_source' => array(56, 'VErMPaw',
					array(
						'all' => 1,
						'manually_type_in' => 0,
					),
					'Data Source', '', 'selectShorterInput', '<div style="width:500px; overflow-x:scroll;">',
					'</div><div style="padding-left:120px">Click "Discover" button to discover available databases</div>', 'size="10"; style="float:none; padding:0; min-width:498px; width:auto;" onChange=zmcRegistry.adjust_zmc_windowsexchange();'),
				'discovered_components' =>	array(57, 'vErMpaw', '', '', ''),
				'level_1_backup_disabled' => array(179, 'VErMpAw',
					array(
							'zwcinc' => 'Incremental',
							'zwcdiff' => 'Differential'
					),
					'Level 1 Backup', '', 'selectLongerInput', '',
					'Select backup level between full backups. Can\'t be changed later.', 'style="float:none; padding:0;"'),
				'property_list:level_1_backup' => array('vermpaw' => 'vermpaw', 'default' => 'zwcinc'),
			)
		),
			
		'windowshyperv' => array(
				'name' => 'Microsoft Hyper-V Server',
				'browseable' => true,
				'searchable' => false,
				'conflict_resolvable' => array(
						ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
						ZMC_Type_AmandaApps::DIR_WINDOWS => array( ZMC_Type_AmandaApps::KEEP_EXISTING, ZMC_Type_AmandaApps::RENAME_RESTORED, ZMC_Type_AmandaApps::RENAME_EXISTING, ZMC_Type_AmandaApps::OVERWRITE_EXISTING,),
						ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => true,
						ZMC_Type_AmandaApps::DIR_UNIX => false, 
				),
				'target_dir_types' => array(ZMC_Type_AmandaApps::DIR_WINDOWS),
				'element_name' => 'file',
				'element_names' => 'files',
				'category' => 'Applications',
				'form' => array(
						'disk_device' => array(60, 'vermpaw' => 'veRmPaw', 'ZMC_MSHyperV', 'Data Source'),
						'exclude' => null,
						'encrypt' => 'windowsbase',
						'compress' => 'windowsbase',
						'property_list:zmc_custom_compress' => 'windowsbase',
						'estimate' => 'unix', 
						'property_list:zmc_extended_attributes' =>	'windowsbase',
						'property_list:zmc_amanda_app' =>			'windowsoracle',
						'property_list:zmc_custom_app' =>			'windowsoracle',
						'application_version' => array(61, 'VErMpaw', '', 'Version', '', '', '', ''),
						'server_name' => array(62, 'VErMpaw', '', 'Server Name', '', ''),
						'instance_name' => array(63, 'VErMpaw', '', 'Instance Name', '', ''),
						'database_name' => array(64, 'VErMpaw', '', 'Database Name', '', ''),
						'data_source' => array(56, 'VErMPaw',
								array(
										'all' => 1,
										'manually_type_in' => 0,
								),
								'Data Source', '', 'selectShorterInput', '<div style="width:500px; overflow-x:scroll;">',
								'</div><div style="padding-left:120px">Click "Discover" button to discover available VMs</div>', 'size="10"; style="float:none; padding:0; min-width:498px; width:auto;" onChange=zmcRegistry.adjust_zmc_windowshyperv();'),
						'discovered_components' =>	array(57, 'vErMpaw', '', '', ''),
						'strategy' => array('default' => array('default' => 'Default', 'standard' => null, 'skip' => 'Skip. Do not backup this DLE.', 'nofull' => null, 'noinc' => null,
								)),
				)
		),

		'windowssharept' => array(
			'name' => 'Microsoft Sharepoint',
			'browseable' => false,
			'searchable' => false,
			'conflict_resolvable' => array(
				ZMC_Type_AmandaApps::DIR_ORIGINAL => array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING, ZMC_Type_AmandaApps::KEEP_EXISTING),
				ZMC_Type_AmandaApps::DIR_WINDOWS => array( ZMC_Type_AmandaApps::KEEP_EXISTING, ZMC_Type_AmandaApps::RENAME_RESTORED, ZMC_Type_AmandaApps::RENAME_EXISTING, ZMC_Type_AmandaApps::OVERWRITE_EXISTING,),
				ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE => true,
				ZMC_Type_AmandaApps::DIR_UNIX => false, 
			),
			'category' => 'Applications',
			'element_name' => 'file',
			'element_names' => 'files',
			'form' => array(
				'disk_device' => array(60, 'vermpaw' => 'VeRmPaw', 'ZMC_MSSharePoint', 'Data Source'),
				'exclude' => null,
				'encrypt' => 'windowsbase',
				'compress' => 'windowsbase',
				'property_list:zmc_custom_compress' => 'windowsbase',
				'estimate' => 'unix', 
				'property_list:zmc_extended_attributes' =>	'windowsbase',
				'property_list:zmc_amanda_app' =>			'windowsoracle',
				'property_list:zmc_custom_app' =>			'windowsoracle',
			)
		),
*/
	);
	parent::init();
	foreach(self::$zmcTypes as &$type)
		if (isset($type['form']['exclude']))
			if (!empty($type['form']['exclude']['5']) && !strncmp($type['form']['exclude']['5'], 'textarea', 8))
			{
				 @$type['form']['exclude']['8'] = ' class="wocloudLongerInput" rows="5"';
			}
}
}
