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
		
		'form_classes' => 'wocloudLongInput wocloudLongLabel',
		'advanced_form_classes' => 'wocloudShortInput wocloudLongLabel',
		'form' => array(
			'private:zmc_device_name' => array(10, 'VeRmpaw', '', '设备名', '用户友好的设备名', 'long'),
			'config_name' => array(15, 'veRmpaw', '', 'Backup Set', '', 'long'),
			'private:zmc_show_advanced' => array(998, 'vERMPaw', 0, '高级设置', 'sticky preference', 'hidden'),
			'private:profile_occ' => array(990, 'vERMPaw', 0, 'Device Binding OCC', 'tracks concurrency version', 'hidden'),
			'private:occ' => array(991, 'vERMPaw', 0, 'Device Profile OCC', 'tracks concurrency version', 'hidden'),
			'holdingdisk_list:zmc_default_holding:filesystem_reserved_percent' => array(57, 'VErMPaw', 5, '为系统预留', '仅允许系统使用的空间百分比', 'textUltraShortInput', '% &nbsp; (推荐5% )'),
			'holdingdisk_list:zmc_default_holding:directory' =>	array(58, 'VERmpaw', '', '备份临时缓存区位于', '在本页面提交前，目录必须对服务器是可见的。', 'wocloudLongerInput', '', '
				<div class="contextualInfoImage"><a target="_blank" href="http://localhost"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
					<div class="contextualInfo">
						<p>
							默认会在家目录下新建一个以备份集名字命名的文件夹来存储备份。
							如果想为不同备份集准备不同的备份目录，请重新建一个设备，并修改设备属性的家目录。
							推荐：该目录应该与前面配置的备份存储目录分开。并请确保没有备份集在该目录中。
							<br><br>
							<a target="_blank" href="http://localhost">根目录的详细讨论点这里。</a>.
						</p>
					</div>
				</div>',
				''),
			'autoflush' => array(35, 'VErMPaw', array('checked' => true, 'on' => 'on', 'off' => 'off'), '自动刷新', '', 'checkbox', '', '自动将临时缓存区的备份写入备份设备中'),
			'holdingdisk_list:zmc_default_holding:strategy' => array(40, 'VErMPaw',
					array(
                        'disabled' => '禁用',
						'no_more_than' => '不超过',
						'all_except' => '预留',
					),
					'缓存空间限制', '限制的缓存空间大小 (所有备份集共同使用这部分空间).', 'selectShorterInput', null, false
			),
			'holdingdisk_list:zmc_default_holding:use_request' => array(50,  'VERMpaw', array('ZMC_Backup_Staging', 'getDefaultStagingSize'), false, '', 'textShortestInput', null, false),
			'holdingdisk_list:zmc_default_holding:use_request_display' => array(55, 'VERMpaw',
				array(
					'm' => 'MiB',
					'g' => 'GiB',
					't' => 'TiB',
					'%' => '% 分区',
				),
				'', '指定空间大小的单位', 'select', '', '</div><a target="_blank" href="http://en.wikipedia.org/wiki/Gibibyte"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a><br /><div class="p"><label>&nbsp;</label>推荐：缓存空间应该大于需要备份空间。</div>', 'style="width:inherit"'
			),
			'fieldset_pre' => array(59, 'VErmpaw', '<br clear="all" /><fieldset><legend>动态统计(MB)</legend>', '', '', 'html'),
			'holdingdisk_list:zmc_default_holding:partition_total_space' => array(60, 'VeRmpaw', '', '分区总空间', '', 'textShortestInput', '', '<div style="float:right;"><span class="wocloudUserErrorsText"> 注意：</span> 在备份之前都会预先给备份分配空间，<br />除非缓存功能被禁用<br /></div>'),
			'holdingdisk_list:zmc_default_holding:partition_free_space' => array(65, 'VeRmpaw', '', '分区剩余空间', '', 'textShortestInput', '', '',),
			'holdingdisk_list:zmc_default_holding:partition_used_space' => array(70, 'VeRmpaw', '', '分区已用空间', '', 'textShortestInput', '', '',),
			'fieldset_post' => array(71, 'VErmpaw', '</fieldset><div style="clear:both;"></div>', '', '', 'html'),
			'reserve' => array(150, 'VErMPAw', '100', '增量备份预留', '', 'wocloudUltraShortInput', '', '% (推荐值：100% )<br style="clear:both;" /><label>&nbsp;</label><div class="wocloudAfter">缓存区间留给增量备份的空间百分比。</div>',),
			
		)
	);

	
	self::$zmcTypes = array(
		'attached_storage' => array(
			'license_group' => 'disk',
			'creationDefaults' => array(),
			'form' => array(
				
				'holdingdisk_list:zmc_default_holding:strategy' => array('default' => array(
						'disabled' => '禁用 (推荐)',
						'no_more_than' => '不超过',
						'all_except' => '预留',
				)),
				'holdingdisk_list:zmc_default_holding:use_request_display' => array('html_after' => '</div><br /><div class="p"><label>&nbsp;</label><div style="float:right;">推荐：禁用缓存，并允许备份系统<br />将备份镜像直接写入备份存储设备。</div></div><div style="clear:both"></div>'),
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
			'advanced_form_classes' => 'wocloudLongLabel wocloudUltraShortInput',
			'license_group' => 'changer',
			'form' => array(
				'fieldset_pre' => array(500, 'VErmpAw', '<br clear="all" /><fieldset><legend>Retaining Backups in Staging Area</legend>', '', '', 'html'),
					'fieldset_kb' => array(501, 'VErmpAw', '<div style="margin:auto; text-align:justify; width:450px;">These setting allow you to choose to fill tapes, or leave the last tape of each backup run partially empty.  Keeping each backup run on separate tapes can simplify some tasks, such as managing your retention period, and physically rotating tapes to the shelf or off-site.<br /><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png" /> <a target="_blank" href="http://network.wocloud.cn/lore/article.php?id=525">How to write multiple backup images to one tape.</a></div>', '', '', 'html'),
					'flush_threshold_dumped' => 
						array(510, 'VErMPAw', 50, 'Flush at', '', 'text', '', '<div class="wocloudAfter">%  Start writing to tape, when staging contains enough<br />to fill this much of a tape.'),
					'flush_threshold_scheduled' => 
						array(520, 'VErMPAw', 100, 'Flush Scheduled', '', 'text', '', '<div class="wocloudAfter">%  Before writing to tape, accumulate until staging<br />contains enough to fill this much of a tape.</div>'),
					'taperflush' => 
						array(530, 'VErMPAw', 100, 'Taper Flush', '', 'text', '', '<div class="wocloudAfter">%  0 = flush staging contents at end of backup run.<br />100 = flush only if enough to fill a tape</div>'),
				'fieldset_post' => array(550, 'VErmpAw', '<p><span class="wocloudUserErrorsText">Note:</span> Set each box to 0 to cause Amanda to write to tape on every run</p></fieldset><div style="clear:both;"></div>', '', '', 'html'),
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
