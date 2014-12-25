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
		
		'form_classes' => 'zmcLongInput zmcShortLabel',
		'advanced_form_classes' => 'zmcShortestInput zmcShortLabel',
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
			'id' => array(10, 'VERMpaW', '', 'Name', 'User-friendly name for this ZMC device.'),
			'changer:comment' => array(20, 'VErMpaw', '', 'Comments', 'Notes about this device.', 'textarea'),
			
			'private:zmc_show_advanced' =>	array(998, 'vERMPaw', 0,	'Show advanced settings', 'sticky preference', 'hidden'),
			'device_output_buffer_size' => array(307, 'VERMPAw', 2, 'Output Buffer', '', 'zmcUltraShortInput', null, false, 'maxlength="5"'), 
			'device_output_buffer_size_display' => array(308, 'VERMPAw', array('m' => 'MiB','k'=>'KiB'), '', 'Specify the unit size', 'selectUltraShortInput', '(2 MiB recommended)'),
		)
	);

	
	self::$zmcTypes = array(
		
		'attached_storage' => array(
			'form_classes' => 'zmcLongerInput',
			'advanced_form_classes' => 'zmcLongerInput',
			'license_group' => 'disk',
			'name' => 'Disk/NAS/SAN',
			'icon' => '.png',
			'device_type' => self::TYPE_ATTACHED,
			'erasable_media' => true,
			'media_type' => 'vtape',
			'human_group' => 'Attached Storage',
			'media_name' => 'Virtual DLE Slot',
			'media_names' => 'Virtual DLE Slots',
			'dev_prefix' => 'file',
			'form' => array(
				'media:filesystem_reserved_percent' => array(35, 'VERMPaw',
					(isset(ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform]) ? ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform] : ZMC::$registry->filesystem_reserved_percent['default']),
					'Reserved Percent', 'ext3 filesystems typically have 5 Percent of the space reserved for use only by root (Solaris typically has 10%).', 'textShortestInput', '', 

					'% &nbsp; <div class="contextualInfoImage"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/>
						<div class="contextualInfo">
							<p>
								tune2fs on Linux can report and set the amount of space reserved for use by a special user (typically &quot;root&quot;).
								ZMC can not auto-detect the amount of space reserved.  Thus, the total space reported as free on the device often is not all available for use by AEE and the amandabackup user.
								Linux "ext" filesystems typically default to 5% reserved, and Solaris commonly defaults to 10% reserved.
							</p>
						</div>
					</div>', 'maxlength="2"'),

				'changer:changerdev_prefix' =>	array(30, 'VERMPaw', ZMC::$registry->default_vtape_device_path ? ZMC::$registry->default_vtape_device_path: '/var/lib/amanda/vtapes/', 'Root Path', 'Exported directory path of network share on client', '', '', 
					'<div class="contextualInfoImage"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/>
						<div class="contextualInfo">
							<p>
								Each backupset will use a subdirectory under this root path. If you need a different root path for a different backup set, then create a new device profile.
								<br><br>
								<a target="_blank" href="http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/ZMC_Users_Manual/AdminDevices#Disk_Options">Root Path is discussed in more detail here</a>.
							</p>
						</div>
					</div>'),
				'max_slots' => array(9990, 'VERMPAw', 2000, 'Maximum Total DLEs', '', 'zmcUltraShortInput', '', 'Hard limit for total number of DLE backup images.', 'maxlength="5" style="margin-left: 15px"'),
				'tapetype:length' =>    array(9991,  'VErMpAw', '0', 'Tape Size:', 'Size of every tape used with this tape device', 'textShorterInput', null, false),
				'tapetype:length_display'=>array(9992, 'VErMpAw',array('m' => 'MiB','g' => 'GiB','t' => 'TiB',),'', 'Specify the unit size', 'selectUltraShortInput', 'Native Size'),
				
			)
		),

		's3_compatible_cloud' => array(
			'form_classes' => 'zmcLongerInput zmcLongLabel',
			'advanced_form_classes' => 'zmcLongerInput zmcLongLabel',
			'license_group' => 's3compatiblelic',
			'name' => 'S3 Compatible Cloud Storage',
			'human_group' => 'Cloud Storage',
			'icon' => '.png',
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
					array(25, 'VERMpaW', '', 'Endpoint (Host Name)'),
				'device_property_list:USE_API_KEYS' =>
					array(28, 'VERMPaw', array('checked' => true, 'on' => 'on', 'off' => 'off'), 'Use API Credentials', 'Credentials are API access and secret keys?', 'checkbox', '(API keys recommended over username/password)'),
				'device_property_list:S3_ACCESS_KEY' =>
					array(30, 'VERMpaW', '', 'Access Key', '', 'textarea', '', '', 'onMouseOver=""'),
				'device_property_list:S3_SECRET_KEY' =>
					array(40, 'VERMpaw', '', 'Secret Key', '', 'textarea', '', '', 'onMouseOver=""'),
				'device_property_list:REUSE_CONNECTION' => 
					array(412, 'VErMPAw', array('checked' => true, 'on' => 'on', 'off' => 'off'), 'Reuse Connections', 'Use reuse connections when cloud object size is smaller', 'checkbox', '', ''),
				'device_property_list:S3_SSL' =>
					array(110, 'VERMPAw', array('checked' => true, 'on' => 'on', 'off' => 'off'), 'Secure Communications', 'Use secure communications when transmitting data to/from the cloud', 'checkbox', '(recommended, but using IPSec/VPN is faster)'),
				'device_property_list:S3_SERVICE_PATH' =>
					array(340, 'VErMpAw', '', 'Cloud URL Path', 'do not include hostname', '', 'Leave blank if not sure.'),
				'device_property_list:S3_SUBDOMAIN' => array(360,  'VERMPAw',
					array('on' => array('default' => true, 'label' => 'Use DNS'),
						'off' => array('default' => false, 'label' => 'Use URL path')), 'Bucket Names', '', 'radio', '', '<br />'),
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
					'Cloud Object Size', 'split backups into cloud objects of this size', 'selectUltraShortInput', null, false
				),
				'device_property_list:BLOCK_SIZE_display' => array(375, 'VeRmPAw', array('m' => 'MiB'), '', '', 'selectUltraShortInput',
					'<div style="float:right; text-align:right;"><table class="dataTable" style="border:1px solid black; float:right;">
						<caption>1 TiB Backup</caption
						<tr><th>Size</th><th># Objects</th><th title="Example ideal PUT/upload *request* charges, excluding bandwidth cost, resumed object upload transfers (if any), storage costs, etc.">0.01/1000</th><th>Time</th></tr>
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
			'form_classes' => 'zmcLongerInput zmcLongLabel',
			'advanced_form_classes' => 'zmcLongerInput zmcLongLabel',
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
			'form_classes' => 'zmcLongerInput zmcLongLabel',
			'advanced_form_classes' => 'zmcLongerInput zmcLongLabel',
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
			'form_classes' => 'zmcLongerInput zmcLongLabel',
			'advanced_form_classes' => 'zmcLongerInput zmcLongLabel',
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

		'changer_library' => array(
			'license_group' => 'changer',
			'name' => 'Changer Library',
			'icon' => '.png',
			'human_group' => 'Tape Storage',
			'media_type' => 'tape',
			'media_name' => 'Tape',
			'media_names' => 'Tapes',
			'device_type' => self::TYPE_MULTIPLE_TAPE,
			'erasable_media' => false,
			'form_classes' => 'zmcLongInput',
			'advanced_form_classes' => 'zmcUltraShortInput',
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
				'max_slots' => array(305, 'VERMPAw', 10, 'Total Slots', '', 'zmcUltraShortInput', 'tape slots available', '', 'maxlength="5"'),
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
			'form_classes' => 'zmcLongInput',
			'advanced_form_classes' => 'zmcShortestInput',
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
		),
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
