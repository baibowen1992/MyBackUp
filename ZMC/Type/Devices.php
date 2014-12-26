<?













class ZMC_Type_Devices extends ZMC_Type
{
const TYPE_ATTACHED = 'type_attached';
const TYPE_CLOUD = 'type_cloud';
const TYPE_SINGLE_TAPE = 'type_single_tape';
const TYPE_MULTIPLE_TAPE = 'multiple_tape_device';
protected static $init = false;
protected static $labelFixes = array();
protected static $zmcTypes = array();
protected static $defaultTypeArray = array();


protected static function init()
{
	if (self::$init) return;
	self::$defaultTypeArray = array(
		
		'form_classes' => 'wocloudLongInput wocloudShortLabel',
		'advanced_form_classes' => 'wocloudShortestInput wocloudShortLabel',
		'creationDefaults' => array(
			
			'metalabel_counter' => 'AA',
			'device_property_list' => array(
				'LEOM' => 'on',
			),
			
			
			
			'private' => array(
				'profile' => true,
			)
		),
		'form' => array(
			'id' => array(10, 'VERMpaW', '', '设备名', '为新建的设备起一个易于使用的名'),
			'changer:comment' => array(20, 'VErMpaw', '', '备注', '设备备注', 'textarea'),
			
			'private:zmc_show_advanced' =>	array(998, 'vERMPaw', 0,	'高级设置项', 'sticky preference', 'hidden'),
			'device_output_buffer_size' => array(307, 'VERMPAw', 2, '输出缓存', '', 'wocloudUltraShortInput', null, false, 'maxlength="5"'), 
			'device_output_buffer_size_display' => array(308, 'VERMPAw', array('m' => 'MiB','k'=>'KiB'), '', '选择单位', 'selectUltraShortInput', '(推荐2 MiB)'),
		)
	);

	
	self::$zmcTypes = array(
		
		'attached_storage' => array(
			'form_classes' => 'wocloudLongerInput',
			'advanced_form_classes' => 'wocloudLongerInput',
			'license_group' => 'disk',
			'name' => '本地硬盘/NAS/SAN',
			'icon' => '.png',
			'device_type' => self::TYPE_ATTACHED,
			'erasable_media' => true,
			'media_type' => 'vtape',
			'human_group' => '本地存储',
			'media_name' => 'Virtual DLE Slot',
			'media_names' => 'Virtual DLE Slots',
			'dev_prefix' => 'file',
			'form' => array(
				'media:filesystem_reserved_percent' => array(35, 'VERMPaw',
					(isset(ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform]) ? ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform] : ZMC::$registry->filesystem_reserved_percent['default']),
					'空间保留百分比', 'ext3文件系统通常会给root账户保留5%的空间仅允许root使用(Solaris系统则为10%).', 'textShortestInput', '', 

					'% &nbsp; <div class="contextualInfoImage"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/>
						<div class="contextualInfo">
							<p>
								tune2fs on Linux can report and set the amount of space reserved for use by a special user (typically &quot;root&quot;).
								ZMC can not auto-detect the amount of space reserved.  Thus, the total space reported as free on the device often is not all available for use by AEE and the amandabackup user.
								Linux "ext" filesystems typically default to 5% reserved, and Solaris commonly defaults to 10% reserved.
							</p>
						</div>
					</div>', 'maxlength="2"'),

				'changer:changerdev_prefix' =>	array(30, 'VERMPaw', ZMC::$registry->default_vtape_device_path ? ZMC::$registry->default_vtape_device_path: '/var/lib/amanda/vtapes/', '备份存储根目录', '客户端备份文件的存储目录', '', '', 
					'<div class="contextualInfoImage"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/>
						<div class="contextualInfo">
							<p>
								Each backupset will use a subdirectory under this root path. If you need a different root path for a different backup set, then create a new device profile.
								<br><br>
								<a target="_blank" href="http://docs.wocloud.cn">根目录的详细讨论点这里。</a>.
							</p>
						</div>
					</div>'),
				'max_slots' => array(9990, 'VERMPAw', 2000, '允许的备份项总数', '', 'wocloudUltraShortInput', '', '备份项个数的硬限制', 'maxlength="5" style="margin-left: 15px"'),
				'tapetype:length' =>    array(9991,  'VErMpAw', '0', 'Tape Size:', 'Size of every tape used with this tape device', 'textShorterInput', null, false),
				'tapetype:length_display'=>array(9992, 'VErMpAw',array('m' => 'MiB','g' => 'GiB','t' => 'TiB',),'', 'Specify the unit size', 'selectUltraShortInput', 'Native Size'),
				
			)
		),

		's3_compatible_cloud' => array(
			'form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'advanced_form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'license_group' => 's3compatiblelic',
			'name' => '兼容S3云存储设备',
			'human_group' => '云存储',
			'icon' => '.png',
            'ower_name_johnny' => ''.$_SESSION['user'],
			'media_type' => 'vtape',
			'media_name' => 'Virtual DLE Slot',
			'media_names' => 'Virtual DLE Slots',
			'device_type' => self::TYPE_CLOUD,
			'erasable_media' => true,
			'creationDefaults' => array(
				'device_property_list' => array(
					'STORAGE_API' => 'S3',
				),
			),
			'form' => array(
				'device_property_list:S3_HOST' =>
					array(25, 'VERMpaW', '', '对象存储主机名'),
				'device_property_list:USE_API_KEYS' =>
					array(28, 'VERMPaw', array('checked' => true, 'on' => 'on', 'off' => 'off'), '使用API认证', '认证机制是access和secret keys?', 'checkbox', '(推荐使用API认证)'),
				'device_property_list:S3_ACCESS_KEY' =>
					array(30, 'VERMpaW', '', 'Access Key', '', 'textarea', '', '', 'onMouseOver=""'),
				'device_property_list:S3_SECRET_KEY' =>
					array(40, 'VERMpaw', '', 'Secret Key', '', 'textarea', '', '', 'onMouseOver=""'),
				'device_property_list:REUSE_CONNECTION' => 
					array(412, 'VErMPAw', array('checked' => true, 'on' => 'on', 'off' => 'off'), '连接复用', '当对象较小的时候使用连接复用', 'checkbox', '', ''),
				'device_property_list:S3_SSL' =>
					array(110, 'VERMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'), '安全连接', '使用加密连接进行数据传输', 'checkbox', '(推荐, 但是使用IPSec/VPN 会更快)'),
				'device_property_list:S3_SERVICE_PATH' =>
					array(340, 'VErMpAw', '', '云设备URL路径', '不要包含机器名', '', '如果不确定请置空'),
				'device_property_list:S3_SUBDOMAIN' => array(360,  'VERMPAw',
					array('on' => array('default' => false, 'label' => 'Use DNS'),
						'off' => array('default' => true, 'label' => 'Use URL path')), 'Bucket名', '', 'radio', '', '<br />'),
				'device_property_list:BLOCK_SIZE' => array(370,  'VERMPAw', 
					array(
						'512' => '512',
						'256' => '256',
						'128' => '128',
						'64' => '64',
						'32' => '32',
						'16' => '16',
						'8' => '8',
					),
					'云端对象大小', '将备份按照云端对象大小进行分割', 'selectUltraShortInput', null, false
				),
				'device_property_list:BLOCK_SIZE_display' => array(375, 'VeRmPAw', array('m' => 'MiB'), '', '', 'selectUltraShortInput',
					'<div style="float:right; text-align:right;"><table class="dataTable" style="border:1px solid black; float:right;">
						<caption>1 TiB Backup</caption
						><tr><th>大小</th><th># 对象</th><th title="不考虑带宽影响，理想PUT/upload的*request* 消耗，恢复对象上传时的存储消耗等。">0.01/1000</th><th>耗时</th></tr>
						<tr><td>256MiB</td><td>~4,000</td><td>.04</td><td>100%</td></tr>
						<tr><td>128MiB</td><td>~8,000</td><td>.08</td><td>102%</td></tr>
						<tr><td>64MiB</td><td>~16,000</td><td>.16</td><td>107%</td></tr>
						<tr><td>32MiB</td><td>~32,000</td><td>.32</td><td>116%</td></tr>
						<tr><td>16MiB</td><td>~64,000</td><td>.64</td><td>135%</td></tr>
						<tr><td>8MiB</td><td>~128,000</td><td>1.28</td><td style="background-color:#fbb;">171%</td></tr>
						</table></div>',
				),
				'max_slots' => 'attached_storage',
				'device_output_buffer_size' => null,
				'device_output_buffer_size_display' => null,
			)
		),

		'openstack_cloud' => array(
			'form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'advanced_form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'license_group' => 'openstackcloudlic',
			'name' => 'OpenStack Swift Cloud Storage',
			'human_group' => 'Cloud Storage',
			'icon' => '.png',
			'media_type' => 'vtape',
			'media_name' => 'Virtual Tape',
			'media_names' => 'Virtual Tapes',
			'device_type' => self::TYPE_CLOUD,
			'erasable_media' => true,
			'creationDefaults' => array(
				'device_property_list' => array(
					'STORAGE_API' => 'SWIFT-2.0',

				),
			),
			'form' => array(
				'device_property_list:S3_HOST' =>
					array(25, 'VErMPaw', '127.0.0.1', 'Identity/Auth Service'),
				'device_property_list:S3_SERVICE_PATH' =>
					array(340, 'VERMpAw', '/v2.0/tokens', 'Auth Plugin Path', 'do not include hostname', '', ''),
				'device_property_list:S3_SUBDOMAIN' => array(360,  'VERMPAw',
					array('on' => array('default' => false, 'label' => 'Use DNS'),
						'off' => array('default' => true, 'label' => 'Use URL path')), 'Bucket Names', '', 'radio', '', '<br />'),
				'device_property_list:USE_API_KEYS' => 's3_compatible_cloud',
				'device_property_list:TENANT_NAME' => array(29, 'VERMpaw', '', 'Tenant Name'),
				'device_property_list:S3_ACCESS_KEY' => 's3_compatible_cloud',
				'device_property_list:S3_SECRET_KEY' => 's3_compatible_cloud',
				
				
				
				
				'device_property_list:REUSE_CONNECTION' => 's3_compatible_cloud',
				'device_property_list:S3_SSL' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE_display' => 's3_compatible_cloud',
				'ssl_ca_cert_ignore' =>
					array(600, 'VERMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'), 'Use Untrusted Certificate', 'Ignoring untrusted site security certificates is not recommended.', 'checkbox', ''),
				'ssl_ca_cert' =>
					array(605, 'VErMpAw', '', 'Trusted Site Certificate', '', 'textarea', '', '', 'onMouseOver=""'),
				'max_slots' => 'attached_storage',
				'device_output_buffer_size' => null,
				'device_output_buffer_size_display' => null,
			)
		),

		'google_cloud' => array(
			'form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'advanced_form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'license_group' => 'googlecloudlic',
			'name' => 'Google Cloud Storage',
			'human_group' => 'Cloud Storage',
			'icon' => '.png',
			'media_type' => 'vtape',
			'media_name' => 'Virtual Tape',
			'media_names' => 'Virtual Tapes',
			'device_type' => self::TYPE_CLOUD,
			'erasable_media' => true,
			'form' => array(
				'device_property_list:S3_HOST' => null, 
				'device_property_list:S3_SERVICE_PATH' => null,
				'device_property_list:S3_ACCESS_KEY' => 's3_compatible_cloud',
				'device_property_list:S3_SECRET_KEY' => 's3_compatible_cloud',
				'device_property_list:REUSE_CONNECTION' => 's3_compatible_cloud',
				'device_property_list:S3_SSL' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE_display' => 's3_compatible_cloud',
				'max_slots' => 'attached_storage',
				'device_output_buffer_size' => null,
				'device_output_buffer_size_display' => null,
			)
		),

		'iij_cloud' => array(
			'form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'advanced_form_classes' => 'wocloudLongerInput wocloudLongLabel',
			'license_group' => 'iijcloudlic',
			'name' => 'IIJ GIO Storage Service FV/S',
			'human_group' => 'Cloud Storage',
			'icon' => '.png',
			'media_type' => 'vtape',
			'media_name' => 'Virtual Tape',
			'media_names' => 'Virtual Tapes',
			'device_type' => self::TYPE_CLOUD,
			'erasable_media' => true,
			'form' => array(
				'device_property_list:S3_HOST' => null, 
				'device_property_list:S3_SERVICE_PATH' => null,
				'device_property_list:S3_ACCESS_KEY' => 's3_compatible_cloud',
				'device_property_list:S3_SECRET_KEY' => 's3_compatible_cloud',
				'device_property_list:REUSE_CONNECTION' => 's3_compatible_cloud',
				'device_property_list:S3_SSL' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE' => 's3_compatible_cloud',
				'device_property_list:BLOCK_SIZE_display' => 's3_compatible_cloud',
				'max_slots' => 'attached_storage',
				'device_output_buffer_size' => null,
				'device_output_buffer_size_display' => null,
			)
		),

/*		'changer_library' => array(
			'license_group' => 'changer',
			'name' => 'Changer Library',
			'icon' => '.png',
			'human_group' => 'Tape Storage',
			'media_type' => 'tape',
			'media_name' => 'Tape',
			'media_names' => 'Tapes',
			'device_type' => self::TYPE_MULTIPLE_TAPE,
			'erasable_media' => false,
			'form_classes' => 'wocloudLongInput',
			'advanced_form_classes' => 'wocloudUltraShortInput',
			'dev_prefix' => 'tape',
			'creationDefaults' => array(
				'tapetype' => array(
					'length' => '0',
					'length_display' => 'g',
					'filemark' => '1024 kbytes',
					'filemark_display' => 'k',
				)
			),
			'form' => array(
				'changer:changerdev' =>	array(30,  'VERMPaw', array('ZMC_Type_Devices', 'opDiscoverChangers'),
					'Tape Changer',
					'Select a device, or click "Other" to display a manual entry field',
					'select',
					'',
					' <div style="float:right;" id="drive_slot_hint"></div>',
					'onChange="
						var dsh=gebi(\'drive_slot_hint\')
						var cod=gebi(\'changer:changerdev_other_div\')
						cod.style.visibility=\'hidden\'
						if (this.value === \'\' || zmcRegistry === undefined)
						{
							YAHOO.zmc.utils.show_lsscsi()
							dsh.innerHTML = \' \'
							return false
						}
						else if (this.value === \'0\')
						{
							YAHOO.zmc.utils.show_mt(gebi(\'changer:changerdev_other\').value);
							dsh.innerHTML = \'Unrecognized device.\'
							cod.style.visibility=\'visible\'
							return false
						}
						else if (undefined == (zmcRegistry[\'changerdev_list\'][this.value]))
						{
							dsh.innerHTML = \'Found <b>no</b> tape drives!\';
							return false
						}
						YAHOO.zmc.utils.show_mt(this.value)
						var changer = zmcRegistry[\'changerdev_list\'][this.value]
						var drives = changer[\'tape_drives\'];
						var message = \'Found <b>\' + drives + \'</b> drives<br />and \' + changer[\'tape_slots\'] + \' slots\'
						gebi(\'max_slots\').value = changer[\'tape_slots\']
						dsh.innerHTML = message;
						//zmc_print(changer); return false;
					"',
				),
				'changer:changerdev_other'=>array(35,  'VErMpaw',  '/dev/', 'Specify Changer', 'Enter the path to the changer device.', 'text', '', '', 'onBlur="gebi(\'changer:changerdev\').onchange()"'),
				'tapetype:length' =>	array(40,  'VERMpaw', '0', 'Tape Size:<br><small>(uncompressed)</small>', 'Size of every tape used with this tape device', 'textShorterInput', null, false),
				'tapetype:length_display'=>array(45, 'VERMpaw',
					array(
						'm' => 'MiB',
						'g' => 'GiB',
						't' => 'TiB',
					),
					'', 'Specify the unit size', 'selectUltraShortInput', 'Native Size'
				),

				'misc_pre' => array(300, 'VErmpAw', '<fieldset><legend>Misc.</legend>', '', '', 'html'),
				'max_slots' => array(305, 'VERMPAw', 10, 'Total Slots', '', 'wocloudUltraShortInput', 'tape slots available', '', 'maxlength="5"'),
				'changer:drive_choice' => array(310, 'VErMPAw',
					array(
						'lru' => 'Least Recently Used',
						'firstavail' => 'First Available'
					),
					'Next Drive Strategy', 'Specify how to choose the next drive to use.', 'selectLongInput'),
				'has_barcode_reader' => array(320, 'VErMPAw', array('checked' => true, 'on' => true, 'off' => false), 'Bar Code Reader', 'Check if both your changer and tapes support barcodes.', 'checkbox', '', ' Exists?'), 
				
				'changer:eject_before_unload' => array(330, 'VErMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'),	'"mt offline"', '', 'checkbox', '', 'Does your robot require an "mt offline",<br >before "mtx unload"?'),

				'misc_post' => array(399, 'VErmpAw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),

				'timing_pre' => array(400, 'VErmpAw', '<fieldset><legend>Timing in seconds</legend>', '', '', 'html'),
				'changer:status_interval' => array(410, 'VErMpAw', 2, 'Status Poll Interval', '', '', '', 'Delay period between checking status with MTX'),

				'changer:unload_delay' => array(420, 'VErMpAw', 0, 'Unload Delay', '', '', '', 'Delay period, after unloading a tape.'),
				'changer:eject_delay' => array(430, 'VErMpAw', 0, 'Eject Delay', '', '', '', 'Delay period, after ejecting a tape,<br />before unloading to storage slot.'),

				'changer:initial_poll_delay' => array(440, 'VErMpAw', 0, 'First Poll', '', '', '', 'Delay period, after loading a new tape,<br />before polling for status.'), 
				'changer:poll_drive_ready' => array(450, 'VErMpAw', 3,'Poll Frequency', '', '', '', 'Delay period, between polling for status.'), 
				'changer:max_drive_wait' => array(460, 'VErMpAw', 300, 'Max Wait for Ready', '', '', '', 'wait up this many seconds for the drive to be ready'), 
				'timing_post' => array(499, 'VErmpAw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),

				
				
				
				
				
				
			)
		),

		'changer_ndmp' => array(
			'license_group' => 'changer',
			'name' => 'NDMP Changer',
			'icon' => '.png',
			'human_group' => 'Tape Storage',
			'media_type' => 'tape',
			'media_name' => 'Tape',
			'media_names' => 'Tapes',
			'device_type' => self::TYPE_MULTIPLE_TAPE,
			'erasable_media' => false,
			'form_classes' => 'wocloudLongInput',
			'advanced_form_classes' => 'wocloudShortestInput',
			'form' => array(
				'tapetype:length' =>	array(40,  'VERMpaw', '0', 'Tape Size', 'Size of every tape used with this tape device', 'textShorterInput', null, false),
				'tapetype:length_display'=>array(45, 'VERMpaw',
					array(
						'm' => 'MiB',
						'g' => 'GiB',
						't' => 'TiB',
					),
					'', 'Specify the unit size', 'selectUltraShortInput'
				),


				'changerdev' => array(70,  'VERMpaw',  '', 'Appliance Location', 'Specify Changer Location of the format <IP>@<loc> (e.g. 10.0.1.20@/dev/scsi/changer)',),
				'property_list:ndmp_username' => array(80,  'VERMpaw',  '', 'Username', 'appliance username',),
				'property_list:ndmp_password' => array(82,  'VERMpaw',  '', 'Password: <input style="float:none;" title="Show Password" id="show_password" type="checkbox" onclick="this.form[\'property_list:ndmp_password\'].type = (this.form[\'property_list:ndmp_password\'].type === \'password\' ? \'text\' : \'password\');" />', '', 'password'),
				'property_list:ndmp_auth' => array(84, 'VERMpaw',
					array(
						'md5' => 'MD5',
						'text' => 'Text',
						'none' => 'Empty Authentication Attempt',
						'void' => 'No Authentication Attempt', 
					),
					'Auth type', 'Specify the authentication method', 'selectUltraShortInput'
				




				),
				'misc_pre' => array(300, 'VErmpAw', '<fieldset><legend>Misc.</legend>', '', '', 'html'),
				'changer:drive_choice' => 'changer_library',
				'has_barcode_reader' => 'changer_library',
 				'misc_post' => array(399, 'VErmpAw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),
				'timing_pre' => array(400, 'VErmpAw', '<fieldset><legend>Timing in seconds</legend>', '', '', 'html'),
				'changer:status_interval' => array(410, 'VErMpAw', 2, 'Status Poll Interval', '', '', '', 'Delay period between checking status with MTX'),
				
				'changer:unload_delay' => array(420, 'VErMpAw', 0, 'Unload Delay', '', '', '', 'Delay period, after unloading a tape.'),
				'changer:eject_delay' => array(430, 'VErMpAw', 0, 'Eject Delay', '', '', '', 'Delay period, after ejecting a tape,<br />before unloading to storage slot.'),
				
				'changer:initial_poll_delay' => array(440, 'VErMpAw', 0, 'First Poll', '', '', '', 'Delay period, after loading a new tape,<br />before polling for status.'), 
				'changer:poll_drive_ready' => array(450, 'VErMpAw', 3,'Poll Frequency', '', '', '', 'Delay period, between polling for status.'), 
				'changer:max_drive_wait' => array(460, 'VErMpAw', 300, 'Max Wait for Ready', '', '', '', 'wait up this many seconds for the drive to be ready'), 
				'timing_post' => array(499, 'VErmpAw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),
			)
		),*/
	);
	self::$zmcTypes['s3_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['s3_cloud']['license_group'] = 's3';
	self::$zmcTypes['s3_cloud']['name'] = 'Amazon Simple Storage Service (S3)';
	self::$zmcTypes['s3_cloud']['form']['device_property_list:S3_SSL'][7] = '(recommended, unless running AE on EC2 at Amazon)';
	self::$zmcTypes['s3_cloud']['icon'] = '.gif';
	self::$zmcTypes['s3_cloud']['form']['device_property_list:USE_API_KEYS'][1] = 'VeRmPAw';

	self::$zmcTypes['hp_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['hp_cloud']['license_group'] = 'hpcloudlic';
	self::$zmcTypes['hp_cloud']['name'] = 'HP Cloud Services';
	self::$zmcTypes['hp_cloud']['form']['device_property_list:S3_HOST'] = array(330, 'VERMPAw', 'region-a.geo-1.identity.hpcloudsvc.com:35357', 'Identity/Auth Service');
	self::$zmcTypes['hp_cloud']['form']['device_property_list:S3_SERVICE_PATH'][2] = '/v2.0/tokens';
	self::$zmcTypes['hp_cloud']['form']['device_property_list:S3_SERVICE_PATH'][6] = '(recommended: /v2.0/tokens)';
	self::$zmcTypes['hp_cloud']['form']['ssl_ca_cert'] = null;

	self::$zmcTypes['cloudena_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['cloudena_cloud']['name'] = 'Cloudena Services';
	self::$zmcTypes['cloudena_cloud']['license_group'] = 'cloudenacloudlic';

	foreach(array(
		'ssl_ca_cert' => null,
		'device_property_list:S3_HOST' => null, 
		'device_property_list:S3_USER_TOKEN' =>
			array(50, 'VErMpaW', '', 'User Token', '', 'textarea', '', '', 'onMouseOver=""'),
		'device_property_list:S3_STORAGE_CLASS' =>
			array(70, 'VErMpaW',
				array(
					'REDUCED_REDUNDANCY' => 'Reduced Redundancy (RRS)',
					'STANDARD' => 'Standard',
				),
				'Storage Option', '', 'select', '',
		),
		'device_property_list:S3_SERVER_SIDE_ENCRYPTION' =>
			array(380,  'VErMPAw', array(null => array('default' => true, 'label' => 'none'), 'AES256' => array('default' => false, 'label' => 'AES256')), 'Cloud Encrypt', 'Cloud automatically encrypts and decrypts data. Stored data in the cloud is encrypted.', 'radio', '', '<br />'),
			) as $key => $value)
			self::$zmcTypes['s3_cloud']['form'][$key] = $value;
		
	parent::init();
}
}
