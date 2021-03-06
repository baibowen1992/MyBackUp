<?














class ZMC_Type_Where extends ZMC_Type
{
protected static $init = false;
protected static $zmcTypes = array();
protected static $defaultTypeArray = array();
protected static $labelFixes = array();




public static $cloudRegions = array(
	's3_cloud' => array(
		
		''				=> array('s3',  's3.amazonaws.com', 'US-Standard (Virginia or Washington)', 'US-Standard (Northern Virginia & Washington State)',), 
		's3'			=> array(true,  's3.amazonaws.com', 'US-Standard (Virginia or Washington)', 'US-Standard (Northern Virginia & Washington State)',), 
		'us-virginia'	=> array(false, 's3.amazonaws.com', 'US Virginia', 'US-Standard (Northern Virginia & Washington State)',),
		'us-west-1'		=> array(true,  's3-us-west-1.amazonaws.com', 'US West (Northern California)', 'US-West (Northern California)',),
		'us-northern-ca'=> array(false, 's3-us-west-1.amazonaws.com', 'US California (Northern)', 'US-West (Northern California)',),
		'us-west-2'		=> array(true,  's3-us-west-2.amazonaws.com', 'US West (Oregon)', 'US-West (Oregon)',),
		'us-oregon'		=> array(false, 's3-us-west-2.amazonaws.com', 'US Oregon', 'US-West (Oregon)',),
		'eu'			=> array(false, 's3-eu-west-1.amazonaws.com', 'European Union (Ireland)', 'European Union (Ireland)',),
		'eu-west-1'		=> array(true,  's3-eu-west-1.amazonaws.com', 'European Union (Ireland)', 'European Union (Ireland)',), 
		'eu-ireland'	=> array(false, 's3-eu-west-1.amazonaws.co', 'Ireland', 'European Union (Ireland)',),
		'ap-southeast-1'=> array(true,  's3-ap-southeast-1.amazonaws.com', 'Asia Pacific (Singapore)', 'Asia Pacific (Singapore)',),
		'asia-singapore'=> array(false, 's3-ap-southeast-1.amazonaws.com', 'Singapore', 'Asia Pacific (Singapore)',),
		'ap-northeast-1'=> array(true,  's3-ap-northeast-1.amazonaws.com', 'Asia Pacific (Japan Tokyo)', 'Asia Pacific (Japan Tokyo)',),
		'asia-tokyo'	=> array(false, 's3-ap-northeast-1.amazonaws.com', 'Japan Tokyo', 'Asia Pacific (Japan)',),
		'sa-east-1'		=> array(true,  's3-sa-east-1.amazonaws.com', 'South America (Sao Paulo)', 'South America (Sao Paulo)',),
		'south-america-sao-paulo' => array(false, 's3-sa-east-1.amazonaws.com', 'Sao Paulo', 'South America (Sao Paulo)',),
		'us-gov-west-1'	=> array(true,  's3-us-gov-west-1.amazonaws.com', 'US GovCloud', 'United States GovCloud',),
		'fips-us-gov-west-1' => array(true, 's3-fips-us-gov-west-1.amazonaws.com', 'US GovCloud FIPS', 'United States GovCloud FIPS 140-2 Region',),
		'ap-southeast-2' => array(true, 's3-ap-southeast-2.amazonaws.com', 'Australia (Sydney)', 'Australia (Sydney)',),
	),

	'google_cloud' => array(
		''				=> array('US', 'commondatastorage.googleapis.com', 'US', 'United States'),
		'us'			=> array(true, 'commondatastorage.googleapis.com', 'US', 'United States'),
		'eu'			=> array(true, 'commondatastorage.googleapis.com', 'EU', 'European Union'),
	),

	'iij_cloud' => array(
		''				=> array('JP-WEST1',  'gss.iijgio.com', 'Japan (West1)', 'Japan (West1)'),
		'jp-west1'		=> array(true,  'gss.iijgio.com', 'Japan (West1)', 'Japan (West1)'),
	),

	








);

public static function getCloudEndpoints($regionMap)
{
	$wocloudFormMap = array();
	foreach($regionMap as $region => $info)
		if ($info[0] === true)
			$wocloudFormMap[$region] = $info[2];
	arsort($wocloudFormMap);
	return $wocloudFormMap;
}

protected static function init()
{
	if (self::$init) return;
	
	self::$defaultTypeArray = array(
		
		'form_classes' => 'wocloudLongInput wocloudLongLabel',
		'advanced_form_classes' => 'wocloudLongInput wocloudShortLabel',
		'creationDefaults' => array(
			'autoflush' => 'on',
			'autolabel' => 'on',
			'autolabel_how' => 'empty volume_error',
			'autolabel_format' => '$c-$b',
			'labelstr' => '^@@ZMC_AMANDA_CONF@@(-.*)+$',
			'taperscan' => array(
					'plugin' => 'oldest'
					),
			'schedule' => array(
				'dumpcycle' => 7,
				'full_hours_same' => 1,
				'hours' => array(2),
				'minute' => 15,
				'retention_policy' => 7,
				'runtapes' => 1,
				'schedule_type' => 'Every Weekday'
			),
			'holdingdisk_list' => array(
				'zmc_default_holding' => array(
					'filesystem_reserved_percent' => 5,
					'use_request' => 25,
					'use_request_display' => '%',
					'directory' => ZMC::$registry->default_holding_disk_path ? ZMC::$registry->default_holding_disk_path.'@@ZMC_AMANDA_CONF@@' : '/var/lib/amanda/staging/'.'@@ZMC_AMANDA_CONF@@',
					'chunksize' => '2047m',
					'strategy' => 'disbale'
                    // 设置默认缓存为disable
//					'strategy' => 'no_more_than'
				)
			),
		),
		'form' => array(
			'private:zmc_device_name' => array(10, 'VeRmpaw', '', '设备名称', '友好的设备名。'),
			'config_name' => array(15, 'veRmpaw', '', 'Backup Set'),
			'comment' => array(20, 'VErMpaw', '', '注释', '有关备份集如何使用该设备的注释。', 'textarea'),
 			'taperscan:plugin' => array(21, 'VErMPaw',
					array(
							'oldest' => 'Oldest',
							'traditional' => 'Traditional',
							'lexical' => 'Lexical',
					),
					'设备扫描', '设备的扫描方式', 'selectShorterInput'),
			'private:profile_occ' => array(990, 'vERMPaw', 0, 'Device Binding OCC', 'tracks concurrency version', 'hidden'),
			'private:occ' => array(991, 'vERMPaw', 0, 'Device Profile OCC', 'tracks concurrency version', 'hidden'),
			'private:zmc_show_advanced' => array(998, 'vERMPaw', 0, '显示更多高级设置', 'sticky preference', 'hidden'),
		)
	);

	
	
	self::$zmcTypes = array(
		'attached_storage' => array(
			'name' => 'Disk/NAS/SAN',
			'license_group' => 'disk',
			'category' => 'vtape',
			'advanced_form_classes' => 'wocloudLongInput wocloudLongLabel',
			'creationDefaults' => array(
				'changer' => array(
					'ignore_barcodes' => 'on',
					'slots' => 5),
				'tapetype' => array(
					'length' => '1m', 
					'length_display' => 'g',
				),
				'media' => array(
					'allocated_space_display' => 'g',
				),
				'holdingdisk_list' => array(
					'zmc_default_holding' => array('strategy' => 'disabled')
				),
			),
			'form' => array(
				'changer:changerdev' =>	array(40, 'VeRmpaw', '', '备份存储目录', '在保存的时候请确保该目录对于服务器是可见的。', 'wocloudLongerInput', '', 
					'<div class="contextualInfoImage"><a target="_blank" href="http://localhost"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
						<div class="contextualInfo">
							<p>
								默认会在家目录下新建一个以备份集名字命名的文件夹来存储备份。
								如果想为不同备份集准备不同的备份目录，请重新建一个设备，并修改设备属性的家目录。
								<br><br>
								<a target="_blank"  href="http://localhost">备份存储位置</a>.
							</p>
						</div>
					</div>'),
				'media:partition_total_space'		=> array(50, 'VeRmPaw', '0', '分区总空间', '', 'textShortestInput', '', 'MiB'),
				'media:partition_free_space'		=> array(51, 'VeRmPaw', '0', '分区剩余空间', '', 'textShortestInput', '', 'MiB '),
				'media:used_space'=> array(52, 'VermPaw', '0', '已用空间', '', 'textShortestInput', '', 'MiB (这个备份集已经使用的)'),













			)
		),

		's3_compatible_cloud' => array(
			'name' => 'Cloud Storage Service',
			'category' => 'vtape',
			'advanced_form_classes' => 'wocloudLongLabel',
			'creationDefaults' => array(
				'changer' => array(
					'ignore_barcodes' => 'on',
					'slots' => 5),
				'tapetype' => array(
					'length' => '10485760m', 
					'length_display' => 't',
				),
				'media' => array(
					'allocated_space_display' => 'g',
				)
			),
			'form' => array(
				'device_property_list:S3_BUCKET_LOCATION' =>
					array(25, 'VErMpaW', '', '位置锁定', 'Optional'),
				'changer:changerdev' =>	array(40, 'VeRmpaw', '', '存储备份集于', '在保存的时候请确保其对于服务器是可见的。', 'wocloudLongerInput', '',
					'<div class="contextualInfoImage"><a target="_blank" href="http://localhost"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
						<div class="contextualInfo">
							<p>
								默认会在用户对象存储下新建一个以对象存储access-key加备份集名字命名的bucket来存储备份数据。
								如果想使用其他对象存储access key，请使用你想用的key重新建一个存储设备。
								<br><br>
								<a target="_blank"  href="http://localhost">备份存储位置</a>.
							</p>
						</div>
					</div>'),












				'device_property_list:S3_SSL' => array(110, 'VErMPAw', array('checked' => true, 'on' => 'on', 'off' => 'off'), '安全连接', '在对云设备进行数据存取的使用安全连接', 'checkbox', '', '(推荐)'),

				'private:bandwidth_toggle' => array(111, 'VErMPAw', array('checked' => true, 'on' => 'on', 'off' => 'off'), '不限速上传下载', '自定义备份还原速度', 'checkbox', '', '', 'onChange="return bandwidth_toggle();"'),
				'per_dle_pre' => array(115, 'VErmpAw', '<br /><br /><fieldset><legend>单线程速度</legend>', '', '', 'html'),
				'device_property_list:MAX_SEND_SPEED' => array(120, 'VErMPAw', 1024, '最大上传速度', '', 'textUltraShortInput', 'KiB/second&nbsp;<b>x</b>', '<div style="clear:both; height:2px;"></div>'),
				'device_property_list:MAX_RECV_SPEED' => array(130, 'VErMPAw', 1024, '最大下载速度', '', 'textUltraShortInput', 'KiB/second&nbsp;<b>x</b>'),
				'per_dle_post' => array(134, 'VErmpAw', '</fieldset>', '', '', 'html'),
				'per_set_pre' => array(135, 'VErmpAw', '<fieldset><legend>上传下载线程数</legend>', '', '', 'html'),
				'device_property_list:NB_THREADS_BACKUP' => array(140, 'VErMPAw', array(1=>1, 2=>2, 3=>3, 4 => 4, 8 => 8, 12 => 12, 16=>16), '并行备份', '', 'selectUltraShortInput', '', '线程'),
				'device_property_list:NB_THREADS_RECOVERY' => array(150, 'VErMPAw', array(1=>1, 2=>2, 3=>3, 4 => 4, 8 => 8, 12 => 12, 16=>16), '并行还原', '', 'selectUltraShortInput', '', '线程'),
				'per_set_post' => array(199, 'VErmpAw', '</fieldset>', '', '', 'html'),
			)
		),

		'changer_library' => array(
			'license_group' => 'changer',
			'name' => 'Tape Changer Device',
			'category' => 'tape',
			'advanced_form_classes' => 'wocloudUltraShortInput',
			'form' => array(
				'autolabel' => array(30, 'VErMPaw', array('checked' => true, 'on' => 'on', 'off' => 'off'), 'Auto Label Tapes', '', 'checkbox', null, false),
				'autolabel_how' => array(31, 'VErMPaw',
					array(
						'empty' => 'Only if empty (read returned 0 bytes)',
						'empty non_amanda' => 'Only if Amanda label not found',
						'other_config' => 'Only if already labelled for a different backup set',
						'other_config non_amanda empty' => 'Always',
					),
					'', 'Autolabel How?', 'selectLongerInput'),
				'changer:slotrange' => array(44, 'VERMPaw', array('1',array('ZMC_Type_Devices', 'opGetMaxSlots')), 'Slot Range', 'The slot range format 1-5,11-15,17,18,24-26, etc...', 'textShortInput'),
				'changer:changerdev' => array(49, 'VeRmpaw', '', 'Changer Device', '', '', '<a href="#" onclick="YAHOO.zmc.utils.show_mt(gebi(\'changer:changerdev\').value); return false;">MTX</a>'),
				'changer:tapedev' =>	array(50, 'VERMPaw', array('ZMC_Type_Devices', 'opDiscoverTapes'),
				'Tape Drive Device', 'Select a device, or click "Other" to display a manual entry field', 'multipleUltraShortInput',
					





























				),
				'changer:ignore_barcodes' => array(590, 'VErMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'), 'Ignore Bar Codes', '', 'checkbox', '', 'Not recommended.<br /><div class="wocloudAfter">Only use if tapes are missing barcode labels.</div>'),
			)
		),

		'changer_ndmp' => array(
			'license_group' => 'changer',
			'name' => 'NDMP Changer Device',
			'category' => 'changer',
			'form_classes' => 'wocloudLongInput',
			'advanced_form_classes' => 'wocloudShortestInput',
			'creationDefaults' => array(
					'holdingdisk_list' => array(
							'zmc_default_holding' => array('strategy' => 'disabled')
					),
			),
			'form' => array(
				'autolabel' => 'changer_library',
				'autolabel_how' => 'changer_library',
				'property_list:use_slots' => array(60,  'VERMpaw',  '', 'Slots', 'Specify range of slots to use (#-#)', 'text', '', 'Example: 1-10'),
				'property_list:tape_device' => array(70,  'VERMpaw',  '', 'Tape Device', 'example: "0=ndmp:172.17.46.91@nrst1h" "1=ndmp:172.17.46.91@nrst0h"', 'textarea'),
				'changer:ignore_barcodes' => array(510, 'VErMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'), 'Ignore Bar Codes', '', 'checkbox'),
			)
		),
	);

	self::$zmcTypes['s3_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['s3_cloud']['name'] = 'Amazon Simple Storage Service (S3)';
	self::$zmcTypes['s3_cloud']['form']['device_property_list:S3_SSL'][7] = '(recommended, unless running AE on EC2 at Amazon)';
	self::$zmcTypes['openstack_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['openstack_cloud']['name'] = 'Open Stack Swift Cloud';
	self::$zmcTypes['openstack_cloud']['form']['device_property_list:S3_BUCKET_LOCATION'][2] = array('ZMC_Type_Where', 'opGetRegionList');
	self::$zmcTypes['openstack_cloud']['form']['device_property_list:S3_BUCKET_LOCATION'][6] = 'select';
	self::$zmcTypes['hp_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['hp_cloud']['name'] = 'HP Cloud Services';
	self::$zmcTypes['cloudena_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['cloudena_cloud']['name'] = 'Cloudena Services';
	self::$zmcTypes['google_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['google_cloud']['name'] = 'Google Cloud Storage';
	self::$zmcTypes['iij_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['iij_cloud']['name'] = 'IIJ Cloud Storage';
	foreach(self::$cloudRegions as $cloudType => $regions){
		self::$zmcTypes[$cloudType]['form']['device_property_list:S3_BUCKET_LOCATION'] =
		array(25, 'VERMpaW', self::getCloudEndpoints($regions), 'Location Restriction', '', 'select');

		if($cloudType == "google_cloud"){
			self::$zmcTypes[$cloudType]['form']['private:google_durable_reduced_avaibility_storage'] = array(109, 'VErMPAw', array('checked' => false, 'on' => 'on', 'off' => 'off'), 'Durable Reduced <br /> Availability Storage', 'Durable Reduced Availability Storage enables you to store data at lower cost, with the tradeoff of lower availability than standard Google Cloud Storage', 'checkbox', '', '', 'onChange=""');
		}


	}


	parent::init();
}

public static function opGetRegionList(ZMC_Registry_MessageBox $pm)
{
	$za3 = ZMC_A3::createSingleton($pm, $pm->binding);
	$list = array();
	foreach(self::$cloudRegions[$pm->binding['_key_name']] as $name => $region)
		if ($region[0] === true)
			$list[$name] = $region[2];
	return $list;
}
}
