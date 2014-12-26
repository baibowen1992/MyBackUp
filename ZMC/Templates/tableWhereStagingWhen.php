<?
//zhoulin-backup-where 201409191744
//zhoulin-backup-staging 201409192103
//zhoulin-backup-when 201409191338
//zhoulin-monitor-backup 201409191157









global $pm;
if((!preg_match("/Manage Media for/i",$pm->tableTitle) && empty($pm->rows)))
	return;


$only1user = (ZMC_User::count() === 1);
if (empty($pm->csv))
{
	echo "<div>"; 
	echo <<<EOD
<script>
function zmc_timestamp2locale(timestamp)
{
    var dateObject = new Date(timestamp * 1000)
    if (0 == timestamp)
        return "Never"

    return dateObject.toLocaleString()
}
</script>
EOD;
	ZMC::titleHelpBar($pm, $pm->goto . $pm->tableTitle, $pm->tableTitle, 'wocloudTitleBarTable');

	if (!empty($pm->prepend_html) && empty($pm->csv))
		echo $pm->prepend_html;
}

static $col2titles = array(
	'active' => array('Backup in Progress', '<img style="vertical-align:middle;" src="/images/icons/icon_calendar_progress.gif" height="18" width="20" />'),
	'age' => array('Age in Days', 'Age', 'center'),
	'autoflush' => array('在备份运行过程中自动刷新缓存?', '刷新'),
	'autolabel' => array(null, '自动标记'),
	'backup_level' => array('备份级别', '级别', 'center'),
	'bucket_name' => array(null, 'Bucket 名'),
	'bucket_size' => array(null, 'Bucket 容量'),
	'bucket_objects' => array(null, '# 对象', 'right'),
	'backuprun_date_time' => array(null, '日期和时间'),
	'barcode' => array(null, 'Barcode'),
	'changer:changerdev' => array(null, '备份存储目录'),
	'creationdate' => array(null, '创建 (服务器时间)', 'white-space:nowrap;'),
	'comment' => array('备注', '备注'),
	'compress' => array(null, '压缩'),
	'config_name' => array(null, '备份集', 'min-width:100px;'),
	'configuration_id' => array(null, '备份集', 'min-width:100px;'),
	'datetime' => array(null, 'Written', null),
	'dev_path' => array(null, 'Device Path'),
	'directory' => array(null, '目录'),
	'dle_state_id' => array(null, '#'),
	'estimate' => array(null, '检查<br />备份计划', 'center'),
	'encrypt' => array(null, '加密'),
	'endpoint' => array(null, '地址'),
	'etag' => array(null, 'ETag'),
	'failed' => array(null, '失败原因', 'max-width:250px;'),
	'filename' => array(null, '汽配文件名 (或数目)'),
	'flush' => array(null, '清理缓存区域', 'center'),
	'host' => array(null, '主机名'),
	'holding_disk' => array(null, '传递备份到服务器', 'min-width:70px;'),
	'holdingdisk_list:zmc_default_holding:directory' => array('存放缓存数据的目录', '缓存目录'),
	
	'holdingdisk_list:zmc_default_holding:use' => array('已用空间和总空间', '缓存使用统计', 'width:150px'),
	'holdingdisk_list:zmc_default_holding:partition_total_space' => array('已用空间和总空间', '缓存统计', 'width:150px'),
	
	'media:partition_total_space' => array('Media partition usage', 'Media Partition Statistics'),
	'hostname' => array(null, '主机名'),
	'id' => array('日志序号', '序号'),
	'job' => array(null, '还原任务描述', 'min-width:375px;'),
	'key' => array(null, 'Key'),
	'_key_name' => array('设备类型', '设备'),
	'label' => array('Last seen Amanda label in this slot', 'Current/New Label'),
	'labels' => array(null, 'Media Labels / Barcodes'),
	'last_used' => array(null, '修改人Written When'),
	'lastmodified' => array(null, '最后修改'),
	'location_constraint' => array(null, 'Location Constraint'),
	'media_usage' => array(null, 'Media Usage'),
	'media_label' => array(null, 'Media Label'),
	'message' => array(null, '说明'),
	'media:allocated_space' => array('该备份集使用的最大空间', 'Max Media Space'),
	'media:partition_total_space' => array('使用空间和总空间', '存储空间统计', 'width:150px'),
	'media' => array(null, '写入备份介质', 'min-width:70px;'),
	'nb' => array(null, '备份项', 'width:35px;'),
	'nc' => array(null, 'Parts', 'width:35px;'),
	'org' => array(null, '说明'),
	'owner' => array(null, '所属者'),
	'percent_use' => array(null, '已使用', 'right'),
	'prehtml' => array(null, 'Log'),
	'private:last_modified_by' => array('最后一次修改人', '修改'),
	'private:last_modified_time' => array('最后一次修改时间', '最后一次修改'),
	'private:zmc_device_name' => array(null, '设备名'),
	'prune_reason' => array('Is this media Prunable?', 'Prunable', 'center'),
	'reuse' => array('Archive media or reuse?', 'Archived?', 'center'),
	'region' => array(null, 'Location', 'white-space:nowrap;'),
	'schedule:archived_media' => array('存档介质数量和使用量', '存档和使用', 'center'),
	'schedule:desired_retention_period' => array('D=需要的 / H=历史的 / E=预计保留周期', '保留%', 'width:22%;'),
	'schedule:dumpcycle' => array(null, '备份周期', 'center'),
	'schedule:hours' => array('备份将在下述小时数时执行', '时', 'right'),
	'schedule:minute' => array('备份将在既定的小时数的下述分钟数后执行。', '分', 'right'),
	'schedule:runtapes' => array('Media Per Backup', 'MPB'),
	'schedule:schedule_type' => array(null, '计划类型'),
	'schedule:status' => array('计划健康状态', '状态'),
	'schedule:when' => array('备份执行的日期(举例M=周一, A=自动, I=增量, F=完整, &mdash;=没有备份计划)', '日期', 'width:5%;', 'nowrap'),
	'severity' => array(null, '日志级别'),
	'size' => array(null, '容量', 'right'),
	'slot' => array(null, 'Slot'),
	'state' => array(null, '状态', 'center'),
	'status' => array(null, '状态', 'center'),
	'storageclass' => array(null, 'Storage Class'),
	'subsystem' => array(null, '来源'),
	'summary' => array(null, '概要', null),
	'time_duration' => array('Total Backup Runtime', 'HH:MM', 'width:50px;'),
	'timestamp2locale' => array(null, 'Creation Date', null),
	'tlast_used' => array(null, 'Written', null),
	'timestamp' => array(null, '日期和时间'),
	'user_id' => array(null, '用户'),
	'zmc_type' => array(null, '类型', 'center'),
	'zmc' => array(null, 'ZMC', 'center'),
);

if ($pm->subnav === 'custom')
	ZMC::merge($col2titles, ZMC_Report_Custom::$cols2group);

if (empty($pm->csv))
{
?>
	<div class="dataTable" <? if (!empty($pm->data_table_div_attribs)) echo $pm->data_table_div_attribs; ?>>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" <? echo 'id="', (empty($pm->data_table_id) ? ($tableId=uniqid()) : ($tableId = $pm->data_table_id)), '" '; if (!empty($pm->data_table_attribs)) echo $pm->data_table_attribs; ?>>
			<thead style="display:table-header-group;"><tr>
<?
}
else
	$fields = array();

if (empty($pm->disable_checkboxes) && empty($pm->csv))
	ZMC_Form::thAll();


foreach(array_intersect($pm->columns, array('failed', 'comment')) as $col => $name)
{
	$showColumn = false;
	foreach($pm->rows as $id => $row)
		if (!empty($row[$name]))
			$showColumn = true;
	if (!$showColumn)
		unset($pm->columns[$col]);
}

foreach($pm->columns as $as => $col)
{
	$key = (is_string($as) ? $as : $col);
	$lckey = strtolower($key);
	if ($key === 'private:last_modified_by' && $only1user)
		continue;
	if (!isset($col2titles[$lckey]))
	{
		ZMC::errorLog("Unknown column: $key");
		ZMC::errorLog($pm->columns);
		if (ZMC::$registry->debug)
			ZMC::Quit(array('Unknown column' => $key, $pm->columns));
		$col2titles[$lckey] = array(null, $key);
	}
	$titles =& $col2titles[$lckey];
	if ($pm->tableTitle === 'Event Viewer' && $key === 'id')
		echo '<th><img height="18" width="18" title="点击数字查看详细" alt="详细信息" src="/images/icons/icon_info.png"/></th>';
	elseif (empty($pm->csv))
	{
		echo '<th ';
		if (!empty($titles[2]))
			if ($titles[2] === 'right')
				echo " style='text-align:right;'";
			elseif ($titles[2] === 'center')
				echo ' class="wocloudCenterNoLeftPad"';
			else
				echo " style='$titles[2]'";

		if (!empty($titles[0]))
			echo " title='$titles[0]'";
		echo '>';

		if ($pm->sortImageIdx === $key)
			echo $pm->sortImageUrl;
		echo '<a href="', $pm->colUrls[$key], '">', $titles[1], "</a></th>";
	}
	else
		$fields[] = $titles[1];
}

if (empty($pm->csv))
	echo "\t\t\t\t</tr></thead><tbody id='" . ($tbodyId=uniqid()) . "' >";
else
	fputcsv($pm->fp, $fields);

$i = 0;

$vtapeRetentionBar = null;
if (isset($pm->cols['media_label']))
{
	if (!strncmp(ZMC::$userRegistry->sort_vtapelist[0], 'datetime:', 9))
		$vtapeRetentionBar = (substr(ZMC::$userRegistry->sort_vtapelist[0], -1) === '0');
	elseif (!strncmp(ZMC::$userRegistry->sort_vtapelist[0], 'age:', 4))
		$vtapeRetentionBar = (substr(ZMC::$userRegistry->sort_vtapelist[0], -1) === '1');
}

foreach($pm->rows as $id => $row)
{
	$i++;
	$visibleCheckBox = $disabledCheckBox = false;
	if (!isset($row['isa'])) 
	{
		$row['id'] = empty($row['slot']) ? $id : $row['slot']; 
		$encName = $id;
		$editUrl = "$pm[url]?edit_id=$encName&amp;action=Edit";
		if (isset($row['label']) && $row['label'] !== 'unknown')
			$visibleCheckBox = $disabledCheckBox = true;
	}
	else
	{
		$row['id'] = $row['config_name'];
		$encName = urlencode($row['id']);
		$editUrl = "$pm[url]?edit_id=$encName&amp;action=Edit";
	}

	if (empty($row['next']))
		$color = (($i % 2) ? 'stripeGray':'');
	elseif ($row['next'] === 'retention')
		$color = 'stripeRed'; 
	elseif ($row['next'] === 'tapecycle')
		$color = 'stripeRed'; 
	else
		$color = (($i % 2) ? 'stripeGraySkip' : 'stripeWhiteSkip'); 

	if ($vtapeRetentionBar !== null)
		if (($vtapeRetentionBar && ($row['age'] <= $row['initial_retention'])) || (!$vtapeRetentionBar && ($row['age'] >= $row['initial_retention'])))
		{
			if (($i !== 1) && ($i !== count($pm->rows)))
				$color .= ' topbar';
			$vtapeRetentionBar = null;
		}

	if (empty($pm->csv))
	{
		if (empty($pm->disable_onclick))
			
			
			
			
			
				echo "<tr style='cursor:pointer' class='$color' onclick=\"noBubble(event); window.location.href = '$editUrl'; return true;\" >";
		else
			echo "<tr class='$color' >";
	}

	if (empty($pm->disable_checkboxes) && empty($pm->csv))
	{
		if ($row['id'] === '99990000000000') 
			echo '<td></td>';


		elseif(isset($row['status']))
			echo ZMC_Form::tableRowCheckBox($row['id'], empty($pm->checkbox_qualifier) ? '' : $pm->checkbox_qualifier, $disabledCheckBox, $visibleCheckBox, $row['status']);
		else
			echo ZMC_Form::tableRowCheckBox($row['id'], empty($pm->checkbox_qualifier) ? '' : $pm->checkbox_qualifier, $disabledCheckBox, $visibleCheckBox);
	}

	$fields = array();
	foreach($pm->columns as $key)
	{
		$lckey = strtolower($key);
		$td = '<td ';
		if (!empty($col2titles[$lckey][3]))
			if (intval($col2titles[$lckey][3]))
				$td .= 'width="' . $col2titles[$lckey][3] . '"';
			else
				$td .= $col2titles[$lckey][3];
		$td .= ' >';
		if (!empty($col2titles[$lckey][2]))
			switch ($style = $col2titles[$lckey][2])
			{
				case null:
					break;
				case 'right':
					$td = str_replace('>', ' align=right>', $td);
					break;
				case 'center':
					$td = str_replace('>', ' class="wocloudCenterNoLeftPad" style="width:5%;">', $td);
					break;
				default:
					$td = str_replace('>', " style='$style'>", $td);
			}

		switch ($key) 
		{
			case 'id':
				if ($pm->tableTitle === 'Event Viewer')
				{
					echo "<td></td>";
					continue 2;
				}
				break;
		}

		if (isset($row[$key]) && (is_array($row[$key]) || is_object($row[$key])))
			$escapedTd = '';
		else
		{
			$escaped = (isset($row[$key]) ? ZMC::escape($row[$key]) : '');
			if ($key === 'holdingdisk_list:zmc_default_holding:use_request')
				$escaped = '&lt;' . $escaped . ZMC::$registry->units['storage'][$row[$key . '_display']];

			$escapedTd = $td . $escaped . "</td>";
		}

		if (!empty($pm->csv))
			$fields[] = $row[$key];
		else
		switch($key)
		{
			case 'percent_use':
					echo $td, $row[$key], '%', "</td>";
				break;

			case 'prune_reason':
				if (empty($row['prune_reason']))
					echo ($row['retained_reason'] === 'Unexpired') ? $td : '<td style="background-color:#ff9; font-weight:bold;" class="wocloudCenterNoLeftPad">', $row['retained_reason'], "</td>";
				else
					echo '<td style="background-color:#fcc; font-weight:bold;" class="wocloudCenterNoLeftPad">',
						'<img style="vertical-align:middle;" src="/images/icons/icon_calendar_warning.gif" />',
						$row[$key], "</td>";
				break;

			case 'age':
				$age = round($row[$key]);
				echo $td, ($age > $row[$key]) ? '&lt;' : '&gt;', $age, ZMC::$registry->dev_only ? ';' . $row[$key]:'', "</td>";
				break;

			case 'holdingdisk_list:zmc_default_holding:partition_total_space':
				if ($row['holdingdisk_list:zmc_default_holding:strategy'] === 'disabled')
					echo $td, '禁用</td>';
				else
					echoSpaceUsedBar($td, $row, $key, $row['holdingdisk_list:zmc_default_holding:filesystem_reserved_percent'], 'holdingdisk_list:zmc_default_holding:partition_used_space');
				break;

			case 'holdingdisk_list:zmc_default_holding:use':
				if ($row['holdingdisk_list:zmc_default_holding:strategy'] === 'disabled')
				{
					echo $td, "禁用</td>";
					break;
				}
				$reserved = 0;
				if ($row[$key] == 0)
				{
					$row[$key] = $row['holdingdisk_list:zmc_default_holding:partition_total_space'];
					$reserved = $row['holdingdisk_list:zmc_default_holding:filesystem_reserved_percent'];
				}
				elseif ($row[$key] < 0)
					$row[$key] = bcsub($row['holdingdisk_list:zmc_default_holding:partition_total_space'], abs(rtrim($row[$key], 'mMgGtTkK')));
				echoSpaceUsedBar($td, $row, $key, $reserved, 'holdingdisk_list:zmc_default_holding:used_space');
				break;

			case 'media:partition_total_space':
				echoSpaceUsedBar($td, $row, $key, isset($row['media:filesystem_reserved_percent']) ? $row['media:filesystem_reserved_percent'] : 0, 'media:partition_used_space', 'schedule:total_tapes_available', 'schedule:used_tapelist_count');
				break;

			case 'compress':
				if (empty($row[$key]))
				{
					echo "$td-</td>";
					break;
				}
				static $compress2human = array(
					0 => 'none',
					1 => 'client fast',
					2 => 'client best',
					3 => 'client custom',
					4 => 'server fast',
					5 => 'server best',
					6 => 'server custom',
				);
				echo $td, $compress2human[$row[$key]], "</td>";
				break;

			case 'encrypt':
				if (empty($row[$key]))
				{
					echo "$td-</td>";
					break;
				}
				static $encrypt2human = array(
					0 => 'none',
					1 => 'client',
					2 => 'server',
				);
				echo $td, $encrypt2human[$row[$key]], "</td>";
				break;

			case 'filename':
				echo $td, "<a href='/ZMC_Restore_What?ConfigurationSwitcher=$row[config_name]&amp;client=$row[host]&amp;disk_name=$row[directory]&amp;date_time_human=$row[datetime]'>", $escaped, '</a></td>';
				break;

			case 'job':
			case 'prehtml':
				echo $td, '<pre>', $row[$key], '</pre></td>';
				break;

			case 'failed':
				if (strlen($escaped) < 16)
					$escaped = str_replace(' ', '&nbsp;', $escaped);
				echo $td, $escaped, "</td>";
				break;

			case 'media':
			case 'flush':
			case 'holding_disk':
			case 'estimate':
				$escaped = substr($escaped, 0, 128);
				$title = '';
				if (!empty(ZMC::$userRegistry['monitor_details']))
				{
					$title = "title='$escaped'";
					$escaped = '';
				}
				$key .= '_bar';
				$bar = (isset($row[$key]) ? "<div $title class='wocloud" . $row[$key] . "Bar'></div>" : '');
				echo $td, $bar, $escaped, "</td>";
				break;

			case 'directory':
				echo $td, str_replace('/', '<wbr/>/', $escaped), '</td>';
				break;

			case 'state':
				if ($row['state'] === 'Failed')
					echo $td, '<img style="vertical-align:middle;" title="备份失败" src="/images/global/calendar/icon_calendar_failure.gif"></td>';
				elseif ($row['active'])
					echo $td, '<img style="vertical-align:middle;" title="备份运行中" src="/images/icons/icon_calendar_progress.gif" /></td>';
				else
					echo $td, '<img style="vertical-align:middle;" title="备份成功" src="/images/icons/icon_calendar_success.gif" /></td>';
				break;

			case 'zmc_type':
				if (empty($row[$key]))
				{
					echo "$td-</td>";
					break;
				}
				echo $td;
				if (isset($pm->lstats) && isset($pm->lstats['over_limit'][$row[$key]]))
					echo '<img style="vertical-align:text-top; padding:0; margin:0" src="/images/global/calendar/icon_calendar_failure.gif" title="License limit exceeded. DLE disabled." />';
				echo ZMC_Type_What::getName($row[$key]), "</td>";
				break;

			case 'datetime':
				echo $td, (empty($row[$key]) ? '-' : ZMC::amandaDate2humanDate($row[$key])), "</td>";
				break;

			case 'tlast_used':
			case 'timestamp2locale':
				if ($row[$key] > 0)
					echo "$td<script>document.write(zmc_timestamp2locale($row[$key]))</script></td>";
				else
					echo "$td-</td>";
				break;

			case 'backuprun_date_time':
			case 'timestamp':
				$ecolor = '';
				if (empty($row['event_id']))
					$value = ZMC::escape(substr($row[$key], 0, -3));
				elseif (empty($prevEventId) || ($prevEventId !== $row['event_id']))
					{
						$prevEventId = $row['event_id'];
						$ecolor = (empty($ecolor) || $ecolor === ' style="background-color:#999;"' ? ' style="background-color:#CCC;"' : ' style="background-color:#999;"');
						$value = ZMC::escape(substr($row[$key], 0, -3));
					}
					else
						$value = '';
                if(ZMC_User::hasRole('Administrator'))
				    echo '<td nowrap', $ecolor, '><a onclick="noBubble(event)" href="/ZMC_Report_Backups?viewDate=', substr($row[$key], 0, -8), '">', $value, "</a></td>";
				else
                    echo '<td nowrap', $ecolor, '>', $value, "</td>";
				break;

			case 'backup_level': 
				if ($row[$key] === null)
					echo "$td-</td>";
				else
					echo $td, "<img title='Level $row[$key]' src='", ZMC_Report::getLevelImage($row[$key]), "'></td>";
				break;

			case 'configuration_id':
				if (empty($row[$key]))
				{
					echo "$td</td>";
					break;
				}
				$row[$key] = ZMC_BackupSet::getName($row[$key]);
			case 'config_name':
				if (empty($row[$key]) || $row[$key] === '-')
				{
					echo "<td>$row[$key]</td>";
					break;
				}
				$active = ($row['active'])? $row['active']: '';
				echo $td, $icon = ZMC_BackupSet::getStatusIconHtml($row[$key], '', $active), ' ',
					ZMC_BackupSet::displayName($row[$key]), "</td>";
				break;

			case 'log_message':
				echo $td, $row[$key], '</td>';
				break;

			case 'message':
				echo $td;
				if ((ord($row[$key][0]) === 120) && (ord($row[$key][1]) === 156))
				{
					
					ob_start();
					ZMC_Loader::renderTemplate('MessageBox', unserialize(gzuncompress($row[$key])));
					
					echo ob_get_clean();
				}
				else
					echo $row[$key];
				echo '</td>';
				break;

			case 'status':
				if ($row[$key] === 'PARTIAL')
					echo $td, '<span style="color: red;"><b>PARTIAL</b></span></td>';
				else
					echo $td, '<span style="color: green;"><b>OK</b></span></td>';
				break;	
			case 'status_summary':
			case 'severity':
				if (empty($row[$key]))
				{
					echo $td, 'NA</td>';
					break;
				}

				$text = ZMC_Error::$severity2text[$row[$key]];
				if (isset(ZMC_Error::$severity2icon[$row[$key]]))
				{
					$sev = ZMC_Error::$severity2icon[$row[$key]];
					echo "<td nowrap><img style='vertical-align:text-bottom; padding:0; margin:0' src='/images/global/calendar/icon_calendar_$sev.gif' />$text</td>";
				}
				else
					echo $td, '<img src="/images/icons/icon_help.png" />', $row[$key], '</td>';

				break;

			case 'barcode':
				echo "<td id='slot_barcode_$row[id]'>", (empty($row[$key]) ? '-' : $row[$key]), "</td>";
				break;

			case 'label':
				$img = '';
				if (!empty($row['label_status']['result']))
					$img = '<img style="vertical-align:text-bottom; padding:0; margin:0" src="/images/global/calendar/icon_calendar_'
					. $row['label_status']['result'] . '.gif" />';
				if ($row[$key] === 'empty' || $row[$key] === 'empty slot')
					echo $td, "$img $escaped</td>";
				else
					echo $td, "<input onFocus=\"if (this.value === 'unknown') this.value=''; else this.select(); \" onKeyUp=\"this.style.backgroundColor='#ffffcc'\" class='wocloudShortInput' type='text' name='label[$row[id]]' id='label[$row[id]]' value='", ZMC::escape($escaped), "' />$img</td>";
				break;

			case 'reuse':
				if ($row[$key] === 'reuse')
					echo $td, "No</td>";
				else
					echo $td, "Archived</td>";
				break;

			case 'schedule:archived_media':
				echo $td, $row[$key], '/', $row['schedule:total_tapes_available'] - $row['schedule:used_tapelist_count'], "</td>"; 
				
				break;

			case 'media_label':
				$ml_pre = $ml_post = '';
				if (!empty($row['L0_missing']))
				{
					$ml_pre = '<span class="wocloudUserErrorsText wocloudIconError">';
					$ml_post = '</span>';
				}
				elseif ($row['backup_level'] == 0)
				{
					$ml_pre = '<b>';
					$ml_post = '</b>';
				}
				$label = $row[$key];
				if ($label[0] === '/')
					$label = 'Staging: ' . str_replace('/', '/<wbr/>', $row['comment']);
				echo $td, $ml_pre, $label, $ml_post, "</td>";
				break;

			case 'labels':
				echo $td;
				if (is_array($row[$key]))
				foreach(array_reverse($row[$key], true) as $label => $details) 
				{
					if (isset($pm->level0_tapelist[$label]))
						echo '<b>L0</b> ';

					if (	isset($pm->labels2slots) 
						&&	!empty($pm->labels2slots[$label]) 
					   )
							echo '(slot #', $pm->labels2slots[$label], ') ';

					$pre = $post = '';
					if ($details['tapecycle'])
					{
						$pre = '<span style="background-color:#ccffcc;">';
						$post = '</span>';
					}

					if (	isset($pm->labels2barcodes) 
						&&	!empty($pm->labels2barcodes[$label]) 
						&&	!strpos($label, $pm->labels2barcodes[$label]) 
					   )
					{
						echo "$pre$label$post / " . $pm->labels2barcodes[$label];
						if (!empty($details['comment']))
							echo "<br />$details[comment]<br />";
					}
					else
						echo "$pre$label$post $details[comment]<br />";
				}
				echo "</td>";
				break;

			case 'schedule:status':
				if (empty($row[$key]))
					echo '<td style="max-width:275px;"><img style="vertical-align:text-bottom; padding:0; margin:0" src="/images/global/calendar/icon_calendar_success.gif" /> OK</td>';
				elseif (is_object($row[$key]))
					echo '<td style="max-width:275px;">', $row[$key]->toCommentBox(), "</td>";
				break;

			case 'ignore':
				break;

			case 'autoflush':
				if (!isset($row[$key]))
					echo $td, "?</td>";
				elseif ($row[$key] === 'on')
					echo $td, "yes</td>";
				else
					echo $td, "no</td>";
				break;

			case 'changer:changerdev':
				if (empty($row[$key]))
					echo $td, '-</td>';
				else
					echo $escapedTd;
				break;





			case 'private:zmc_device_name':
				echo $td, '<a onclick="noBubble(event)" href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '?' . 'action=Edit&amp;edit_id=' . urlencode($row[$key]) . '">' . ZMC::escape($row[$key]) . "</a></td>";
				break;

			case '_key_name':
				echo $td, ZMC_Type_Devices::getIcon($pm, $row[$key], $disabled, 'width=\'auto\' height=\'21\''), "</td>";
				break;

			case 'creation_date':
				echo $td, ZMC::escape(substr($row[$key], 0, -9)), "</td>";
				break;

			case 'last_used':
				if (strlen($row[$key]) < 10)
					echo $escapedTd;
				else
					echo $td, substr($row[$key], 0, -3), "</td>";
				break;

			case 'live':
				echo $td, $row[$key] ? 'Yes' : 'No', "</td>";
				break;

			case 'schedule:when':
				if (empty($row[$key]))
					echo $td, "none</td>";
				else
					if (strpos($row[$key], ':'))
						echo $escapedTd;
					else
						echo $td, "<pre>S|M|T|W|R|F|S<br />", $row[$key], "</pre></td>";
				break;

			case 'schedule:desired_retention_period':
				$multiplier = (($row[$key] <= 31) ? 2 : 1);
				$multiplier = (($row[$key] > 200) ? 0.5 : $multiplier);
				$multiplier = (($row[$key] > 500) ? 0.25 : $multiplier);
				$dwidth = round($row[$key] * $multiplier);
				$max = 0;
				foreach(array($row[$key], $row['schedule:historical_retention_period'], $row['schedule:estimated_retention_period']) as $val)
					$max = ($val === 'NA' ? $max : max($max, $val));
				$dwidth = round($row[$key] * 100 / $max);
				echo "$td
					<div style='position:relative;'>
						<div style='position:absolute; background-color:transparent; font-weight:bold; z-index:9;'>&nbsp;D", $row['schedule:desired_retention_period'], "&nbsp;days</div>
						<img style='filter:alpha(opacity=75); opacity:0.75; padding-top:2px; width:$dwidth%; height:13px;' src='/images/section/monitor/success.gif' />
					</div>";
				$hwidth = 0;
				if ($row['schedule:historical_retention_period'] !== 'NA')
				{
					$hwidth = round($row['schedule:historical_retention_period'] * $multiplier);
					$color = ($hwidth < $dwidth ? 'CC6666' : '66CC66');
					$barColor = ($hwidth < $dwidth ? 'failure' : 'success');
					$hwidth = round($row['schedule:historical_retention_period'] * 100 / $max);
					echo "<div style='position:relative; padding-top:1px;'>";
					if (empty($row['schedule:historical_retention_period']))
						echo 'No backups found.';
					else
						echo "
							<div style='position:absolute; background-color:transparent; font-weight:bold; z-index:9;'>&nbsp;H", $row['schedule:historical_retention_period'], "&nbsp;days</div>
							<img style='filter:alpha(opacity=75); opacity:0.75; padding-top:1px; width:$hwidth%; height:13px;' src='/images/section/monitor/$barColor.gif' />";
					echo "</div>";
				}
				elseif(ZMC_BackupSet::isActivated($row['config_name']))
					echo '<div style="background-color:#CC6666; margin:2px 0 2px 0; font-weight:bold; height:13px;">&nbsp;H&nbsp;0&nbsp;days</div>';

				if ($row['schedule:estimated_retention_period'] !== 'NA')
				{
					$ewidth = round($row['schedule:estimated_retention_period'] * $multiplier);
					$color = ($ewidth < $dwidth ? 'CC6666' : '66CC66');
					$barColor = ($hwidth < $dwidth ? 'failure' : 'success');
					$ewidth = round($row['schedule:estimated_retention_period'] * 100 / $max);
					echo "<div style='position:relative; padding-top:1px;'>
							<div style='position:absolute; background-color:transparent; font-weight:bold; z-index:9;'>&nbsp;E", $row['schedule:estimated_retention_period'], "&nbsp;days</div>
							<img style='filter:alpha(opacity=75); opacity:0.75; padding-top:1px; width:$ewidth%; height:13px;' src='/images/section/monitor/$barColor.gif' />
					</div>";
				}
				echo "</td>";
				break;

			case 'schedule:dumpcycle':
				$tapecycle = '';
				
				
				echo "$td$escaped$tapecycle</td>";
				break;

			case 'schedule:minute':
				if (empty($row['schedule:full_hours_same']))
					echo $td, $escaped, '<br />', (strpos($row['schedule:when'], 'F') ? "({$row['schedule:full_minute']})" : ''), "</td>";
				else
					echo $td, $row[$key], '</td>';
				break;

			case 'schedule:hours':
				if (empty($row['schedule:full_hours_same']))
					echo $td, $escaped, "<br />", (strpos($row['schedule:when'], 'F') ? "({$row['schedule:full_hours']})" : ''), "</td>";
				else
					echo $td, $row[$key], '</td>';
				break;

			case 'user_id':
				echo $td, (empty($row[$key]) ? 'system' : ZMC_User::get('user', $row[$key])), '</td>';
				break;

			case 'private:last_modified_by':
				if ($only1user) 
					break;

			default:
				if (empty($escapedTd))
					echo $td, ZMC::escape($row[$key]), '</td>';
				else
					echo $escapedTd;
		}
	}
	if (empty($pm->csv))
		echo "</tr>";
	else
		fputcsv($pm->fp, $fields);
}

if (!empty($pm->csv))
	return;

echo "      </tbody></table>
	</div><!-- dataTable -->";

if (!empty($pm->tbody_height))
	echo "<script>var tb=gebi('$tbodyId'); if (tb.clientHeight > ", $pm->tbody_height, ") tb.style.height = '", $pm->tbody_height, "px';</script>";
	

if (empty($pm->disable_button_bar))
	ZMC_Loader::renderTemplate('tableButtonBar', $pm);

echo "</div>";
if (empty($pm->no_form_close))
	echo "</form>";

function echoSpaceUsedBar($td, $row, $totalKey, $reserved, $usedKey, $fallbackTotalKey = null, $fallbackUsedKey = null)
{
	$cloud = ($row['dev_meta:device_type'] === 'type_cloud');
	$infinity = '<b style="font-size:24pt;">&#8734;</b>';
	$class = '';
	if (!empty($row[$totalKey]))
	{
		$used  = $row[$usedKey] = rtrim($row[$usedKey], 'mMgGkKtT');
		$used  = bcdiv($used, 1024, 1);
		$unit = 'G';
		$total = $row[$totalKey] = rtrim($row[$totalKey], 'mMgGkKtT');
		if (empty($total))
			$total = $infinity;
		else
		{
			$total = bcdiv($total, 1024, 1);
			if ($reserved === null)
				$reservedPercent = (isset(ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform]) ? ZMC::$registry->filesystem_reserved_percent[ZMC::$registry->platform] : ZMC::$registry->filesystem_reserved_percent['default']) / 100;
			else
				$reservedPercent = $reserved / 100;

			if (($reservedPercent < 0) || ($reservedPercent > .95))
				throw new ZMC_Exception("Invalid reserved perecent: \n" . print_r($row, true), 1, __FILE__, __LINE__);
			$errAt  = bcmul($row[$totalKey], max(0.02, 1 - $reservedPercent - 0.03));
			$warnAt = bcmul($errAt, 0.80);
			if (bccomp($row[$usedKey], $errAt) >= 0)
				$class="wocloudUserErrorsText wocloudIconError";
			elseif (bccomp($row[$usedKey], $warnAt) >= 0)
				$class="wocloudUserWarningsText wocloudIconWarning";

		}
	}
	else
	{
		if ($cloud)
			return print("$td$infinity</td>");
		if ($fallbackTotalKey === null)
			return print("{$td}NA</td>");
		$total = intval($row[$fallbackTotalKey]);
		$used = intval($row[$fallbackUsedKey]);
		$unit = ' tapes';

	}
	$tw = 150;
	$uwidth = ($total === '&#8734;' ? 50 : round($used / $total * $tw, 0));
	$twidth = $tw-$uwidth;
	if ($total !== '&#8734;')
	{
		if ($total > (10 * 1024))
		{
			$total = round($total / 1024);
			$total .= 'T';
		}
		else
			$total .= $unit;
	}

	if ($used > (10 * 1024))
	{
		$used = round($used / 1024);
		$used .= 'T';
	}
	else
		$used .= $unit;


	echo <<<EOD
	$td
	<div style="position:relative; padding-top:1px;">
		<div style="position:absolute; background-color:transparent; font-weight:bold; z-index:9; width:{$tw}px"><span class="$class">&nbsp;$used&nbsp;</span><div style="float:right;">$total</div></div>
			<img style="filter:alpha(opacity=75); opacity:0.75; padding-top:1px; width:{$uwidth}px; height:13px;" src="/images/section/monitor/success.gif"><img style="filter:alpha(opacity=75); opacity:0.75; padding-top:1px; width:{$twidth}px; height:13px;" src="/images/section/monitor/warning.gif">
	</div>
	</td>
EOD;
}
