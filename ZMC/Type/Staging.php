<?














class ZMC_Type_Staging extends ZMC_Type
{
protected static $init = false;
protected static $labelFixes = array();
protected static $zmcTypes = array();
protected static $defaultTypeArray = array();


protected static function init()
{
	if (self::$init) return;
	self::$defaultTypeArray = array(
		
		'form_classes' => 'zmcLongInput zmcLongLabel',
		'advanced_form_classes' => 'zmcShortInput zmcLongLabel',
		'form' => array(
			'private:zmc_device_name' => array(10, 'VeRmpaw', '', 'Device Name', 'User-friendly name for this ZMC device.', 'long'),
			'config_name' => array(15, 'veRmpaw', '', 'Backup Set', '', 'long'),
			'private:zmc_show_advanced' => array(998, 'vERMPaw', 0, 'Show advanced settings', 'sticky preference', 'hidden'),
			'private:profile_occ' => array(990, 'vERMPaw', 0, 'Device Binding OCC', 'tracks concurrency version', 'hidden'),
			'private:occ' => array(991, 'vERMPaw', 0, 'Device Profile OCC', 'tracks concurrency version', 'hidden'),
			'holdingdisk_list:zmc_default_holding:filesystem_reserved_percent' => array(57, 'VErMPaw', 5, 'Reserved for root', 'percentage of filesystem available only to "root" users', 'textUltraShortInput', '% &nbsp; (5% recommended; 10% for Solaris)'),
			'holdingdisk_list:zmc_default_holding:directory' =>	array(58, 'VERmpaw', '', 'Backup runs staged at', 'Location must be accessible to server when saving this form.', 'zmcLongerInput', '', '
				<div class="contextualInfoImage"><a target="_blank" href="http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/ZMC_Users_Manual/BackupWhere#Advanced_Options"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
					<div class="contextualInfo">
						<p>
							The name of the backup set is appended to the root path of the disk device.
							If you need a different root path for a different backup set, then create a new device profile.
							Recommended: The path should use a different partition than used for any vtapes (if any).  Make sure no DLEs include this location.
							<br><br>
							<a target="_blank" href="http://docs.zmanda.com/Project:Amanda_Enterprise_3.3/ZMC_Users_Manual/BackupWhere#Advanced_Options">Root Path is discussed in more detail here</a>.
						</p>
					</div>
				</div>',
				''),
			'autoflush' => array(35, 'VErMPaw', array('checked' => true, 'on' => 'on', 'off' => 'off'), 'Auto Flush', '', 'checkbox', '', 'automatically flush dumps from staging to media'),
			'holdingdisk_list:zmc_default_holding:strategy' => array(40, 'VErMPaw',
					array(
						'no_more_than' => 'No more than',
						'all_except' => 'All except',
						'disabled' => 'Disabled',
					),
					'Staging Size Limit', 'Size of staging area (shared by all backup sets using this partition).', 'selectShorterInput', null, false
			),
			'holdingdisk_list:zmc_default_holding:use_request' => array(50,  'VERMpaw', array('ZMC_Backup_Staging', 'getDefaultStagingSize'), false, '', 'textShortestInput', null, false),
			'holdingdisk_list:zmc_default_holding:use_request_display' => array(55, 'VERMpaw',
				array(
					'm' => 'MiB',
					'g' => 'GiB',
					't' => 'TiB',
					'%' => '% of partition',
				),
				'', 'Specify the unit size', 'select', '', '</div><a target="_blank" href="http://en.wikipedia.org/wiki/Gibibyte"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a><br /><div class="p"><label>&nbsp;</label>Recommended: Staging size is larger than the largest DLE.</div>', 'style="width:inherit"'
			),
			'fieldset_pre' => array(59, 'VErmpaw', '<br clear="all" /><fieldset><legend>Live Statistics</legend>', '', '', 'html'),
			'holdingdisk_list:zmc_default_holding:partition_total_space' => array(60, 'VeRmpaw', '', 'Partition total space', '', 'textShortestInput', '', '<div style="float:right;"><span class="zmcUserErrorsText">Note:</span> Space for each backup is pre-<br />allocated before starting the backup,<br />unless staging has been disabled.</div>'),
			'holdingdisk_list:zmc_default_holding:partition_free_space' => array(65, 'VeRmpaw', '', 'Partition free space', '', 'textShortestInput', '', '',),
			'holdingdisk_list:zmc_default_holding:partition_used_space' => array(70, 'VeRmpaw', '', 'Partition used space', '', 'textShortestInput', '', '',),
			'fieldset_post' => array(71, 'VErmpaw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),
			'reserve' => array(150, 'VErMPAw', '100', 'Reserve for Incrementals', '', 'zmcUltraShortInput', '', '% (100% recommended)<br style="clear:both;" /><label>&nbsp;</label><div class="zmcAfter">Percentage of staging area space reserved for incremental backup runs.</div>',),
			
		)
	);

	
	self::$zmcTypes = array(
		'attached_storage' => array(
			'license_group' => 'disk',
			'creationDefaults' => array(),
			'form' => array(
				
				'holdingdisk_list:zmc_default_holding:strategy' => array('default' => array(
						'disabled' => 'Disabled (recommended)',
						'no_more_than' => 'No more than',
						'all_except' => 'All except',
				)),
				'holdingdisk_list:zmc_default_holding:use_request_display' => array('html_after' => '</div><br /><div class="p"><label>&nbsp;</label><div style="float:right;">Recommended: Disable staging, and allow Amanda to<br />record backup images directly to the attached storage.</div></div><div style="clear:both"></div>'),
			)
		),

		's3_compatible_cloud' => array(
			'creationDefaults' => array(),
			'form' => array(
				'reserve' => array('default' => '0', 'html_after' => '% (0% recommended)'),
			)
		),

		'tape' => array(
			'form' => array(
			)
		),

		'changer_library' => array(
			'advanced_form_classes' => 'zmcLongLabel zmcUltraShortInput',
			'license_group' => 'changer',
			'form' => array(
				'fieldset_pre' => array(500, 'VErmpAw', '<br clear="all" /><fieldset><legend>Retaining Backups in Staging Area</legend>', '', '', 'html'),
					'fieldset_kb' => array(501, 'VErmpAw', '<div style="margin:auto; text-align:justify; width:450px;">These setting allow you to choose to fill tapes, or leave the last tape of each backup run partially empty.  Keeping each backup run on separate tapes can simplify some tasks, such as managing your retention period, and physically rotating tapes to the shelf or off-site.<br /><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png" /> <a target="_blank" href="http://network.zmanda.com/lore/article.php?id=525">How to write multiple backup images to one tape.</a></div>', '', '', 'html'),
					'flush_threshold_dumped' => 
						array(510, 'VErMPAw', 50, 'Flush at', '', 'text', '', '<div class="zmcAfter">%  Start writing to tape, when staging contains enough<br />to fill this much of a tape.'),
					'flush_threshold_scheduled' => 
						array(520, 'VErMPAw', 100, 'Flush Scheduled', '', 'text', '', '<div class="zmcAfter">%  Before writing to tape, accumulate until staging<br />contains enough to fill this much of a tape.</div>'),
					'taperflush' => 
						array(530, 'VErMPAw', 100, 'Taper Flush', '', 'text', '', '<div class="zmcAfter">%  0 = flush staging contents at end of backup run.<br />100 = flush only if enough to fill a tape</div>'),
				'fieldset_post' => array(550, 'VErmpAw', '<p><span class="zmcUserErrorsText">Note:</span> Set each box to 0 to cause Amanda to write to tape on every run</p></fieldset><div style="clear:both;"></div>', '', '', 'html'),
			)
		),

		'changer_ndmp' => array(
			'license_group' => 'changer',
			'form' => array(
			)
		),
	);
	self::$zmcTypes['s3_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['openstack_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['hp_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['cloudena_cloud'] = self::$zmcTypes['openstack_cloud'];
	self::$zmcTypes['google_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	self::$zmcTypes['iij_cloud'] = self::$zmcTypes['s3_compatible_cloud'];
	parent::init();
}
}
