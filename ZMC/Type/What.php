<?













class ZMC_Type_What extends ZMC_Type
{
protected static $init = false;
protected static $zmcTypes = array();
protected static $defaultTypeArray = array();
protected static $labelFixes = array(
	'estimate' => array(
		'client' => 'Reliably Accurate',
		'client calcsize' => 'Reliably Accurate',
		'client server' => 'Reliably Accurate',
		'calcsize' => 'Usually Accurate',
		'calcsize client' => 'Usually Accurate',
		'calcsize server' => 'Usually Accurate',
		'server' => 'Historical Average',
		'server calcsize' => 'Historical Average',
		'server client' => 'Historical Average'
	),
	'encrypt' => array(
		'none' => 'none',
		'client' => 'on client',
		'server' => 'on server',
		'pfx' => 'PFX certificate on client',
		'zwcaes' => 'AES 256-bit on client',
	),
	'compress' => array(
		
		'none' => 'none',
		'client fast' => 'on client fast',
		'client best' => 'on client best',
		'client custom' => 'on client custom',
		'server fast' => 'on server fast',
		'server best' => 'on server best',
		'server custom' => 'on server custom',
		'server idle' => 'on server (for cloud upload)',
	),
	'data_source' => array(
		'all' => 'Select All',
		'manually_type_in' => 'Type-in Manually',		
	)
);

protected static function init()
{
	if (self::$init) return;
	
	self::$defaultTypeArray = array(
		
		'indexes_explorable' => true, 
		'form_classes' => 'zmcLongInput zmcShortLabel',
		'advanced_form_classes' => 'zmcLongInput zmcShortLabel',
		'form' => array(
			'property_list:zmc_type' =>	array(1, 'veRmpaw', '', 'ZMC Backup Type', 'zmc_type DLE property'),
			'property_list:zmc_disklist' =>	array(2, 'veRmpaw', '', 'ZMC Disklist Name', 'zmc_disklist DLE property'),
			'property_list:zmc_version' =>	array(3, 'veRmpaw', '', 'ZMC DLE Version', 'zmc_version DLE property'),
			'natural_key_orig' =>	array(4, 'vermpaw', '', 'ZMC DLE natural key (id)', 'internal use only'),
			'host_name' =>			array(50, 'VERMpaw', '', 'Host Name', 'Amanda client host name (FQDN preferred)', ''),
			'disk_device' =>		array(60, 'VERMPaw', '/', 'Directory/Path', 'Amanda client directory path containing data to back up', ''),
			'exclude' =>			array(70, 'VErMpaw', '',	'<a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_aee/dle_unix.deny">Exclude Files</a>: <a id="exclude_help_link" style="float:right;" class="zmcHelpLink zmcHelpLinkHug" target="_blank" href="' . ZMC::$registry->wiki . 'Backup_What#Exclude_Specifications"></a>', 'Newline separated GNU tar/glob style exclude patterns specifying directories/files to skip. Please refer to documentation as the format differs for each DLE type.', 'textarea'),
			'floatright_pre' =>		array(10, 'VErmpaw', '<div style="float:right">', '', '', 'html'),
			'encrypt' =>			array(20, 'VErMPaw', array(
					'none' => 1,
					'server' => 0,
					'client' => 0,
				), 'Encrypt', '', 'selectShorterInput', '',
				'
				<div id="client_info_icon" class="contextualInfoImage"><a id="client_info_help1" target="_blank" href=""><img width="18" height="18" align="top" alt="" title="" src="/images/icons/icon_info.png"/></a>
					<div style="position:absolute; top:15px; right:50px;" class="contextualInfo">
						<p>
							Please review <a id="client_info_help2" target="_blank">ZMC documentation</a> for information about how to obtain
							a copy of each encryption key used by each AEE client.
						</p>
					</div>
				</div>
				<a class="zmcIconWarning" id="encrypt_keys_link" href="/ZMC_Keys?foo=bar" target="_blank">Download <img style="vertical-align:text-top" src="/images/icons/icon_key.png" alt="key" title="Download encryption key and store in a safe place." /></a>
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
							alert("Can not encrypt on the client and compress on the server.  Compression disabled.")
						}
					}
					else
					{
						client.style.visibility = "hidden"
					}

					server.search = e.value
				}
				gebi("client_info_help1").href = gebi("client_info_help2").href = gebi("zmcHelpLinkId").href.replace(/#.*/, "#Compression_and_Encryption")
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
				'Compress', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'Can not encrypt on the client and compress on the server.  Encryption disabled.\') }"'
			),
			'floatright_post' =>	array(49, 'VErmpaw', '</div>', '', '', 'html'),

			
			'estimate' =>			array(100, 'VErMPAw', array('server' => 1), 
				'Estimate', '', 'radio', '<img src="/images/section/backup/slow_fast_slider.png" width="560" height="20" /><br />', '<br />',
			),
			'app_caption_pre' =>	array(129, 'property_list:zmc_custom_app', '<fieldset style="float:right;"><legend>Amanda Backup Client Application</legend>', '', '', 'html'),
			'property_list:zmc_extended_attributes' => array(130, 'VErmPAw', array('checked' => false, 'on' => 'star', 'off' => 'gtar'), 'Extended Attributes', '', 'checkbox', '', '', 'onChange="zmcRegistry.app_set_recommended(); zmcRegistry.app_highlight_recommended();"'),
			'property_list:zmc_amanda_app' => array(135, 'VeRmPAw', 'gtar', 'Recommended', 'ZMC recommended Amanda backup client application', '', '', '', ''),
			'property_list:zmc_custom_app' => array(140, 'VErMpAw', '', 'Override', 'Amanda backup client override', 'select', '', '', 'onKeyUp="zmcRegistry.app_highlight_recommended()" onChange="zmcRegistry.app_highlight_recommended();"'),
			'property_list:zmc_override_app' => array(141, 'VErMpAw', '', ' ', 'Custom Amanda backup client override', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.app_highlight_recommended()"'),
			'app_caption_post' =>	array(145, 'property_list:zmc_custom_app', '</fieldset>', '', '', 'html'),
			'disk_name' =>			array(150, 'VErMpAw', '', 'Alias', 'Choose a human readable, unique name for this object (DLE).', ''),
			'maxdumps' =>			array(170, 'VErMpAw', '', 'Client Max Backups', '', 'textShortestInput', '', 'max allowed <a href="http://network.zmanda.com/lore/article.php?id=476">simultaneous DLE backups for this Amanda client</a>', 'style="width:40px;"       maxlength="2"'),
			'strategy' =>			array(180, 'VERMpAw',
				array(
					'default' => 'Default',
					'standard' => 'Override default and allow both full and incremental/differential backups.',
					'skip' => 'Skip. Do not backup this DLE.',
					'nofull' => 'Never do full backups, only level 1 incremental/differential',
					'noinc' => 'Never do incremental/differential backups, only full backups.',
				),
				'Strategy', '', 'selectLongerInput'),
			'holdingdisk' =>		array(190, 'VErMPAw', array('auto' => 'Use staging, if possible', 'never' => 'Never use staging (no parallel backups if using tapes)', 'required' => 'Required (no backup if insufficient space)'), 'Staging', '', 'selectLongerInput', '', ''),
			'property_list:zmc_comments' =>		array(200, 'VErMpAw', '', 'Comments', '', 'textareaLongerInput'),
			'property_list:zmc_show_advanced' => array(998, 'vERMPAw', 0, 'Show advanced settings', 'sticky preference', 'hidden'),
		)
	);
	
	







	self::$zmcTypes = array(
		'vmware' => array(
			'name' => 'VMWare ESX Virtual Machine',
			'category' => 'Applications',
			'browseable' => false,
			'searchable' => false,
			'form_classes' => 'zmcLongInput zmcLongLabel',
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
				'note' =>		array(65, 'VErmpaw', '<div class="p"><p><span class="zmcUserErrorsText">Requirements:</span> Vmdks must exist in the same folder as<br />the vmx files; config.version 7+; CBT; <a target="_blank" id="note_link_id">More</a>.</div><script>gebi("note_link_id").href = gebi("zmcHelpLinkId").href</script>', '', '', 'html'),
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

		
		
		
		
		
		
		
		'ndmp' => array(
			'name' => 'NDMP',
			'category' => 'File Systems',
			'amanda_pass' => 'ndmp_filer_shares',
			'form' => array(
				'host_name' =>		array('vermpaw' => 'veRmPaw', 'default' => '127.0.0.1', 'tip' => 'Fully qualified domain name of server running Amanda NDMP client'),
				'data_path' =>		array(185, 'VERMpAw',
					array(
						'amanda' => 'Amanda',
						'directtcp' => 'Direct TCP',
					),
					'Data Path', '', 'selectLongInput', '', '', 'onchange="zmcRegistry.adjust_zmc_ndmpDataPathChanged();"'),
				'property_list:filer_host_name' =>	array(60 , 'VERMpaw', '', 'Filer Host Name', 'Filer appliance hostname or IP address'),
				'property_list:filer_volume' =>	array(62, 'VERMpaw',  '/vol', 'Volume Name'),
				'property_list:filer_directory' =>	array(64, 'VERMpaw',  '/', 'Directory'),
				'exclude' =>		null,
				'compress' =>		array(
						'vermpaw' => 'VErMPAw',
						'default' => array(
								'none' => 1,
								'client fast' => null,
								'client best' => null,
								'server fast' => 0,
								'server best' => 0)),
				'encrypt' =>		array(
						'vermpaw' => 'VErMPAw',
						'default' => array(
								'none' => 1,
								'server' => 0,
								'client' => null)),
				'property_list:filer_username' =>	array(12, 'VERMpaw', '', 'Username', '', 'text'),
				'property_list:filer_password' =>	array(14, 'property_list:filer_username', '', 'Password: <input title="Show Password" style="float:none;" id="property_list:filer_password_box" type="checkbox" onclick="this.form[\'property_list:filer_password\'].type = (this.form[\'property_list:filer_password\'].type === \'password\' ? \'text\' : \'password\');" />', '', 'password'),
				'property_list:filer_auth' => array(18, 'VERMpaw',
					array(
						'md5' => 'MD5',
						'text' => 'Text',
					),
					'Auth type', 'Specify the authentication method', 'selectUltraShortInput'
				),
				'holdingdisk' =>	array('default' => array('checked' => true, 'on' => 'never', 'off' => null), 'html_after' => ' (not supported with Direct TCP Data Path)', 'attributes' => 'onclick="if (gebi(\'data_path\').value === \'directtcp\') { alert(\'Staging support is not possible for Direct TCP Data Path.\'); return false;} return true;"'),
				'property_list:zmc_amanda_app' => array(135, 'verMpaw', '', '', '', '', '', '', ''),
				'property_list:zmc_extended_attributes' => null,
				'app_caption_pre' =>	null,
				'app_caption_post' =>	null,
				'property_list:zmc_custom_app' => array('advanced' => false, 'required' => true, 'label' => 'Vendor', 'default' => array(
					'' => '--Select a Vendor--',
					'ndmpnetapp' => 'NetApp',
				   	'ndmpbluearc' => 'BlueArc',
				   	'ndmpsun' => 'Sun Unified Storage',
				   	'ndmpemc' => 'EMC Celerra', 
					'0' => '--CUSTOM--',
				)),
				'property_list:zmc_override_app' => array('advanced' => false, 'label' => 'Custom Application'),
			),
		),

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
				'disk_device' =>	array(60, 'VERMpaw',  '\\\\', 'Share Name', 'Exported directory path of network share on client', ''),
				'exclude' =>		array('html_after' => '<br /><br />Note: Do not directly mount CIFS files on any *nix filesystem.<br />If directly mounted, use a Linux/*nix Filesystem type DLE.<br />'),
				'property_list:zmc_share_username' =>	array(12, 'VErMpaw', '', 'Username', '', 'text'),
				'property_list:zmc_share_password' =>	array(14, 'property_list:zmc_share_username', '', 'Password <input style="float:none;" title="Show Password" id="property_list:zmc_share_password_box" type="checkbox" onclick="this.form[\'property_list:zmc_share_password\'].type = (this.form[\'property_list:zmc_share_password\'].type === \'password\' ? \'text\' : \'password\');" />', '', 'password'),
				'property_list:zmc_share_domain' =>		array(16, 'property_list:zmc_share_username', '', 'Network Domain', '', 'text'),
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
				'exclude' => array(70, 'VErMpaw', '',	'<a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_aee/dle_unix.deny">Exclude Files</a>: <a id="exclude_help_link" style="float:right;" class="zmcHelpLink zmcHelpLinkHug" target="_blank" href="' . ZMC::$registry->wiki . 'Backup_What#Exclude_Specifications"></a>', 'Newline separated GNU tar/glob style exclude patterns specifying directories/files to skip. Example: "./*.bak"', 'textarea', '', ''),
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
						'client custom' => 0,
						'server fast' => 0,
						'server best' => 0,
						'server custom' => 0,
					),
					'Compress', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'Can not encrypt on the client and compress on the server.  Encryption disabled.\') } else {zmcRegistry.adjust_custom_compress();}"'
				),
				'property_list:zmc_custom_compress' => array(31, 'VErMpaw', '/bin/pigz', 'Custom Compression', 'Enter a Custom Compression Application', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.adjust_custom_compress()"'),
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
				'disk_device' =>	array('label' => 'Mounted directory', 'default' => '/NFS-mounted-directory'),
				'estimate' => 'unix', 
				'exclude' => 'unix',
				'property_list:zmc_custom_app' => 'unix',
			)
		),

		'solaris' => array(
			'license_group' => 'unix',
			'name' => 'Solaris',
			'element_name' => 'file/directory',
			'element_names' => 'files/directories',
			'category' => 'File Systems',
			'form' => array(
				'estimate' => 'unix', 
				'exclude' => 'unix',
				'property_list:zmc_extended_attributes' => array('default' => array('checked' => false, 'on' => 'suntar', 'off' => 'gtar')),
				'property_list:zmc_custom_app' => array('default' => array(
					'' => '--NONE--',
					'0' => '--CUSTOM--',
					'gtar' => 'GNU tar',
				   	'suntar' => 'SUN Tar',
					
					'zfssendrecv' => 'ZFS Sendrecv',
					'zfssnapshot' => 'ZFS Snapshot',
				)),
				'app_caption_post' => 'unix',
			)
		),

		'mac' => array(
			'license_group' => 'unix',
			'name' => 'Mac',
			'element_name' => 'file/folders',
			'element_names' => 'files/folders',
			'category' => 'File Systems',
			'form' => array(
				'estimate' => 'unix', 
				'exclude' => 'unix',
				'property_list:zmc_custom_app' => array('default' => array(
					'' => '--NONE--',
					'0' => '--CUSTOM--',
					'bsdtar' => 'BSD tar',
					
					
				)),
				'property_list:zmc_extended_attributes' => array('default' => array('checked' => true, 'on' => 'bsdtar')),
			)
		),

		
		'windowsbase' => array(
			'license_group' => 'windows',
			'name' => 'WindowsBase',
			'category' => null, 
			'form' => array(
				'estimate' => 'unix', 
				
				'exclude' => array(70, 'VErMpaw', '', 'Exclude Folders<br />and Files', 'Newline separated exclude patterns specifying folders or files to skip. Examples: C:\Data or C:\Data\*.jpg or foo.? or *.doc', 'textarea', '', ''),
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
						'server custom' => 0,
					),
					'Compress', '', 'selectShorterInput', '', '', 'onChange="var e=gebi(\'encrypt\'); if (e.value === \'client\' && (this.value === \'server fast\' || this.value === \'server best\' || this.value === \'server custom\')) { e.value = \'none\'; alert(\'Can not encrypt on the client and compress on the server.  Encryption disabled.\') } else {zmcRegistry.adjust_custom_compress();}"'
				),
				'property_list:zmc_custom_compress' => array(31, 'VErMpaw', '/bin/pigz', 'Custom Compression', 'Enter a Custom Compression Application', '', '', '', 'style="visibility:hidden;" onKeyUp="zmcRegistry.adjust_custom_compress()"'),
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
								Extended file attribute backup is an option for Unix/Linux file systems, and
								happens automatically for Windows file systems. Unix/Linux file systems require
								Schily tar (star) or Solaris tar to be installed on the client host.
								See the <a target="_blank" id="extended_attrib_link2" href="">documentation</a> to view implications of these options.
							</p>
						</div>
					</div>
					<script>gebi("extended_attrib_link1").href = gebi("extended_attrib_link2").href = gebi("zmcHelpLinkId").href.replace(/#.*/, "#Advanced_Options")</script>
					',),
			),
		),

		'windowsss' => array(
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

		'windowstemplate' => array(
			'license_group' => 'windowstemplate',
			'name' => 'Windows Template',
			'browseable' => true,
			'searchable' => false,
			'element_name' => 'file/folder',
			'element_names' => 'files/folders',
			'category' => 'File Systems',
			'form' => array(
				'disk_device' =>	array('default' => 'ZWC Client Template Name', 'label' => 'ZWC Template'),
				'all_local_drives' => array(61, 'VErMpaw', array('checked' => false, 'on' => 'on', 'off' => null), 'All Local Drives', 'Backup all local drives on the client', 'checkbox', '', '', 'onChange=zmcRegistry.adjust_windowstemplate();'),
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

		
		



















		'linuxoracle' => array(
			'name' => 'Oracle on Linux/Solaris',
			'browseable' => false,
			'searchable' => false,
			'element_name' => 'file',
			'element_names' => 'files',
			'category' => 'Databases',
			'form' => array(
				'property_list:zmc_extended_attributes' => null,
				'disk_device' => array('default' => 'Oracle', 'label' => 'SID List Name', 'tip' => 'The Oracle sid list name configured in amanda-client.conf on the Oracle server', 'html_after' => '<a style="float:left; position:relative;" class="zmcHelpLink" target="_blank" href="http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/Zmanda_App_modules/Oracle11i"></a>'),
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
				'disk_device' => array('visible' => false, 'default' => 'ZMC_Win-Oracle', 'label' => 'SID List Name', 'tip' => 'The Oracle sid list name configured in amanda-client.conf on the Oracle server', 'html_after' => '<a style="float:left; position:relative;" class="zmcHelpLink" target="_blank" href="http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/Zmanda_App_modules/Oracle11i"></a>'),
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
				'property_list:zmc_amanda_app' => array(135, 'VeRmPAw', 'ZwcDiff','Recommended', 'ZMC recommended Amanda backup client application', '', '', '', ''),
				'property_list:zmc_custom_app' =>			'windowsoracle',
				'application_version' => array(61, 'VErMpaw', '', 'Version', 'Must be of format MSSQL-&lt;year&gt; (e.g. MSSQL-2012)', '', '', ''),
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
	);
	parent::init();
	foreach(self::$zmcTypes as &$type)
		if (isset($type['form']['exclude']))
			if (!empty($type['form']['exclude']['5']) && !strncmp($type['form']['exclude']['5'], 'textarea', 8))
			{
				 @$type['form']['exclude']['8'] = ' class="zmcLongerInput" rows="5"';
			}
}
}
