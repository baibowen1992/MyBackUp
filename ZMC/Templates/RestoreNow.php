<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
$iconLink = '<a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '?' . 'action=Edit&amp;edit_id=' . urlencode($pm->set['profile_name']) . "\">" . $pm->restore['icon'] . "</a>";
$labels = '';
foreach(array_reverse($pm->restore['media_needed']) as $record)
{
	$labels .= "<tr><td>$record[level]</td><td>$record[size]</td><td>" . round($record['percent_use'], 0) . "</td><td>$record[date]</td><td>$record[time]</td><td>$record[tape_label]</td>";
	if ($pm->restore['restore_type'] !== ZMC_Restore::EXPRESS)
		$labels .= '<td>' . @$pm->restore['media_counts']['rlist'][$record['level']] . '</td>'
				.  '<td>' . @$pm->restore['media_counts']['elist'][$record['level']] . '</td>';
	$labels .= "</tr>\n";
}
?>
<input type="hidden" name="nopopupconfirm" value="1" />


<div class="wocloudRightWindow" style="width:48%;">
	<? ZMC::titleHelpBar($pm, '需要的介质', '', '', 'clear:none;'); ?>
	<div class="dataTable" style="border-right-width:0px">
		<table width="100%">
			<tr><th>级别</th><th>大小</th><th>%</th><th>日期</th><th>时间</th><th>标签</th><?= ($pm->restore['restore_type'] === ZMC_Restore::EXPRESS) ? '': '<th>+</th><th>-</th>' ?></tr>
			<?= $labels ?>
		</table>
	</div>
</div><!-- wocloudRightWindow -->



<div class="wocloudLeftWindow" style="width:48%; clear:left;">
	<? ZMC::titleHelpBar($pm, '从备份镜像中恢复'); ?>
	<div class="wocloudFormWrapper wocloudVarInput" style="min-height:60px;">
		<div style="float:right; padding:2px; margin:2px; border:solid blue 1px;"><?= $iconLink ?></div>
		<div class="p">
			<label>备份时间:</label>
			<b><?= $pm->restore['date_time_parsed'] ?></b>
		</div>
		<div class="p">
			<label>备份项类型:</label>
			<b><?= $pm->restore['pretty_name'] ?></b>
		</div>
		<div class="p">
			<label>源主机:</label>
			<input 
				type="text" 
				name="client" 
				title="备份来源主机" 
				value='<?= ZMC::escape($pm->restore['client']) ?>'
				disabled />
		</div>
		<? if ($pm->restore['disk_name'] === $pm->restore['disk_device']) { ?>
		<div class="p">
			<label>源目录:</label>
			<input 
				type="text" 
				name="disk_device" 
				title="备份来源目录" 
				value='<?= ZMC::escape($pm->restore['disk_device']) ?>'
				disabled />
		</div>
		<? } else { ?>
		<div class="p">
			<label>Alias:</label>
			<input 
				type="text" 
				name="disk_name" 
				title="tool tip" 
				value='<?= ZMC::escape($pm->restore['disk_name']) ?>'
				disabled />
		</div>
		<? } ?>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapper -->
</div><!-- wocloudLeftWindow -->



<div class="wocloudRightWindow" style="width:48%; clear:right;">
	<?
	ZMC::titleHelpBar($pm, '备份集 '.$pm->restore['config'] . ' 的恢复状态:  ' . $pm->restore_state['state'][0], '', '', '', '&nbsp;<span id="progress_dots"></span>');
		if (!empty($pm->show_old_result_warning))
			echo '<div class="wocloudUserWarningsText wocloudIconWarning" style="margin:10px 10px -10px 10px;">还原条件发生变更.查看 <b>前一次</b> 还原结果请去 <br /><span id="prior_restore_date">', ZMC::humanDate($pm->restore_state['timestamp_end']), '</span></div>';
		if (!empty($pm->restore_state['progress']))
			echo "<div class='wocloudSubHeadingSelect' style='margin:10px 10px -10px 10px;'>{$pm->restore_state['progress']}</div>\n";
	?>
	<div class="wocloudFormWrapperText" id="restore_status_summary">
		<?
		
		if ($pm->restore_state['state'][0] === ZMC_Yasumi_Job::UNBORN)
		{
			echo '恢复进程还没开始';
			?>
			<input type="image" name="dummy" value="button" title="Go" style="z-index:9; background-color:transparent;"
				onMouseOver="this.src='/images/3.1/start.png'"
				onMouseOut="this.src='/images/3.1/start_dark.png'"
				src="/images/3.1/start<?= $pm->restore_state['running'] ? '_disabled' : '_dark' ?>.png" class="wocloudWindowBackgroundimageRight" width="75" height="75"
				onclick="
					var rform = this.form;
					this.disabled='disabled';
					var o = gebi('abort_button'); if (o) o.disabled='';
					document.body.className = 'wait';
					this.src='/images/3.1/start_disabled.png';
					gebi('restore_status_summary').innerHTML = '<div class=links><img width=50 height=50 src=/images/icons/restoring.gif /><br />Starting Restore Process</div><br />';
					window.setTimeout(function() { gebi('restore_button').click() }, 1000);
					return false;" />
				<?
		}
		else
		{
			echo '<div style="overflow:auto; border-bottom:1px solid black; margin-bottom:7px;">';
			if ($pm->restore_state['running'])
				echo '<img style="float:right;" width=50 height=50 src=/images/icons/restoring.gif />';
			echo '<p style="padding-top:0;" id="job_output">';
			echo '还原开始: ', $pm->restore_state['date_started_human'], "<br />\n";
			echo "还原耗时: <span id='duration'>", $pm->restore_state['duration'], "</span><br />\n";
			if (!empty($pm->restore_state['output']))
			{
				echo str_replace("\n", "<br />\n", trim(ZMC::escape(implode("\n", $pm->restore_state['output']))));
				echo '</p>';
			}
			echo '</div><div style="height: 400px; overflow: auto;"><span id="job_status"></span>';
			$replacement =  '<span class="wocloudUserErrorsText stripeRed">*&nbsp;Client&nbsp;Error:</span>';
			echo str_replace(array('* Client Status: FAILURE', '* Client Error:', ' Client Error:', ' Client Warning:'),
				array('<span class="wocloudUserErrorsText stripeRed">* Client Status: Restore Failed.</span>', $replacement, $replacement, '<span class="wocloudIconWarning">Client&nbsp;Warning:</span>'),
				str_replace("\n", "<br />\n", trim(ZMC::escape(implode("\n", $pm->restore_state['status']))))), '</div>';
		}
		?>
	</div>
	<?
		$default_restore_timelimit = ($pm->conf['dtimeout'] != null)? $pm->conf['dtimeout'] : 1800;
		$value = (($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_VMWARE) && ($default_restore_timelimit < 6000)) ? 6000:$default_restore_timelimit;
		if (!empty($pm->restore['amclient_timelimit']))
			$value = $pm->restore['amclient_timelimit'];
		$pm->form_advanced_html = <<<EOD
<div class='p wocloudLongLabel'>
	<label for='amclient_timelimit'>不限制恢复活动时间<span class="required">*</span>:</label>
	<input type='text' name='amclient_timelimit' id='amclient_timelimit' value='$value' /> seconds
</div>
<div class="p"><label>恢复完之后执行校验?</label><br />
EOD;
		foreach(array('none' => '', 'MD5' => 'md5', 'SHA1' => 'sha1', 'SHA512' => 'sha512') as $name => $digest)
		{
			$rdigest = $pm->restore['digest'];
			$checked = ($rdigest === $digest ? 'checked="checked"' : '');
			$pm->form_advanced_html .= <<<EOD
				<div style="float:left;">
					<input id="digest_$name" type="radio" name="digest" value="$digest" $checked  />
					<label for="digest_$name">$name</label>
				</div>
EOD;
		}
		$pm->form_type = array('advanced_form_classes' => 'wocloudShortestLabel wocloudShortestInput');
		$pm->form_advanced_html .= "</div>\n";
		if (ZMC::$registry->qa_mode)
			$pm->form_advanced_html .= '<div class="p">
				<input type="hidden" name="dryrun" value="0" />
				<input id="dryrun" type="checkbox" name="dryrun" value="1" />
				<label for="dryrun">Dryrun</label>
				<div class="wocloudAfter">
					<ol>
					<li>恢复到临时目录</li>
					<li>尝试移动到目标位置</li>
					<li>删除临时位置</li>
					<li>确认上述移动过程 &quot;移动或合并&quot;日志文件到:<br />目标主机:/var/log/amanda/client/</li>
					</ol>
				</div></div>';
		
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
	?>
	<div class="wocloudButtonBar">
		<? if ($pm->restore_state['running'])
				echo '<button id="abort_button" type="submit" name="action" value="Abort" onclick="disableButton(this, \'Aborting \.\.\.\')" />取消</button>';
			elseif ($pm->restore_state['state'][0] !== ZMC_Yasumi_Job::UNBORN)
				echo '<button id="again_button" type="submit" name="action" value="Repeat Restore" onclick="disableButton(this, \'Restoring \.\.\.\'); return true;" />重试还原</button>';
			else
                echo '<button id="restore_button" type="submit" name="action" value="Restore" onclick="disableButton(this, \'Restoring \.\.\.\'); return true;" >还原</button>';
//        echo '<input id="restore_button" type="submit" name="action" value="Restore" onclick="disableButton(this, \'Restoring \.\.\.\'); return true;" />';
        ?>
	</div>
</div>



<div class="wocloudLeftWindow" style="width:48%; clear:left;">
	<? ZMC::titleHelpBar($pm, '恢复目的地：'); ?>
	<div class="wocloudFormWrapper wocloudVarInput">
		<div class="p">
			<label>目标主机:</label>
			<input 
				type="text" 
				name="target_host" 
				title="还原数据到该主机" 
				value='<?
if ($pm->restore['_key_name'] === 'vmware')
{
	ZMC::parseShare($pm->restore['target_dir'], $host, $name, $path);
	echo ZMC::escape($host);
	echo <<<EOD
			' disabled />
			</div>
			<div class="p">
				<label>Datastore:</label>
				<input type="text" disabled="disabled" value="$name" />
			</div>
			<div class="p">
				<label>VM Name:</label>
				<input type="text" disabled="disabled" value="$path" />
			</div>
EOD;
}
else
{
	echo ZMC::escape($pm->restore['target_host']);
?>'
				disabled />
		</div>
		<div class="p">
			<label>目标目录:</label>
			<?
				$warnErr = 'Warnings';
				$err = '';
				if (ZMC::isLocalHost($pm->restore['target_host']))
				{
					$testLocation = (empty($pm->restore['target_dir']) ? $pm->restore['disk_device'] : $pm->restore['target_dir']);
					








				}

				if (empty($pm->restore['target_dir'])){
					if($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH)
						echo "<div style='float:left;'>Alternate Path</div>";
					elseif($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME)
						echo "<div style='float:left;'>Alternate Name and Path</div>";
					else
						echo "<div class='wocloudUser{$warnErr}Text' style='float:left;'><img style='vertical-align:text-bottom;' src='/images/global/calendar/icon_calendar_warning.gif' /> 原始路径 $err</div>";
				} else {

			?>
				<input 
					type="text" 
					name="target_dir" 
					title="还原数据到该目录" 
					value='<? echo $pm->restore['target_dir'];
						if (strpos($pm->restore['zmc_type'], 'windows') === false && $pm->restore['safe_mode']) echo ($pm->restore['zmc_amanda_app'] === 'cifs' ? '\\':'/') . 'zmc.' . date('Y-m-d_H') . '-MM-SS';
					?>'
					disabled />
			<?		 if (!empty($err))
						echo "<div class='wocloudUserErrorsText' style='float:left;'><img style='vertical-align:text-bottom;' src='/images/global/calendar/icon_calendar_warning.gif' />$err</div>";
				} ?>
		</div> 
		<? } if (!$pm->restore['safe_mode'] && !empty($pm->restore['conflict_resolvable'])) { ?>
		<div class="p">
			<?
				$textKey = 'conflict_file_text';
				$policyKey = 'conflict_file_selected';
				foreach(explode('/', $pm->restore['element_name']) as $file_or_dir)
				{
//					echo "<label>", echo($file_or_dir), " 冲突:</label><div width:315px;'>\n";
                    if ($file_or_dir == 'file'){
                        echo "<label>", " 文件冲突:</label><div width:315px;'>\n";}
                    elseif($file_or_dir == 'folder')
                        echo "<label>", " 文件夹冲突:</label><div width:315px;'>\n";
					$cr = $pm->restore[$textKey];
					$icon = ZMC_Type_AmandaApps::$default_options[$pm->restore[$policyKey]];
					if ($icon === 'wocloudIconWarning')
						echo "<span class='wocloudIconWarning'>$cr</span> <span class='wocloudUserErrorsText'>(risky)</span>";
					elseif ($icon === 'wocloudIconSuccess')
						echo "<span class='wocloudIconSuccess'>$cr</span>";
					else
						echo $cr;
					echo '</div><div style="clear:left;"></div>';
					$textKey = 'conflict_dir_text';
					$policyKey = 'conflict_dir_selected';
					if (empty($pm->restore[$textKey])) 
						break;
				}
			?>
		</div> 
		<? } ?>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapper -->
</div><!-- wocloudLeftWindow -->


<?
if (($pm->restore['_key_name'] === 'vmware')
	|| (($pm->restore['restore_type'] === ZMC_Restore::EXPRESS) && (!$pm->restore['excludable'])))
{
	echo '</div></form>';
	return;
}

if (false && $pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
{
?>
	<div class='wocloudLeftWindow' style='clear:left; width:48%;'>
		<div class='wocloudFormWrapperText' style='font-size:24px; font-weight:bold; overflow:auto; max-height:300px; background-color:#<?= ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_SELECT] ?>'>*</div>
	</div>
	<div class='wocloudRightWindow' style='clear:right; width:48%; margin-$margin:0;'>
		<div class='wocloudFormWrapperText' style='overflow:auto; max-height:300px; background-color:#<?= ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_DESELECT] ?>'>*</div>
	</div>
<?
	return;
}

if ($pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
{
	$excludeMap = array();
	$excludeCount = $pm->restore['count_elist'];
	$pm->restoreMap = array(array('filename' => 'ALL', 'type' => 0));
}
else
{
	$excludeCount = $pm->restore['total_files_deselected'] + $pm->restore['total_indirs_deselected'];
}

restoreWindowHelper($pm, 'Exclude', 'elist', '排除  文件/文件夹/匹配模式: ', (empty($pm->deselected) ? array():$pm->deselected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_DESELECT], $excludeCount);

restoreWindowHelper($pm, 'Include', 'rlist', '恢复  文件/文件夹  数目：', (empty($pm->selected) ? array():$pm->selected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_SELECT], @$pm->restore['total_files_selected'] + @$pm->restore['total_indirs_selected']);

function restoreWindowHelper($pm, $listType, $list, $title, $map, $color, $count = 0)
{
	$margin   = ($listType === 'Include' ? 'right' : 'left');
	$clear = $position = ($listType === 'Include' ? 'Left' : 'Right');
	if (($listType !== 'Include') && $pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
		$clear = 'both';
	echo "<div class='zmc{$position}Window' style='clear:$clear; width:48%; margin-$margin:0;'>\n";
	ZMC::titleHelpBar($pm, $title . ($count ? (': ' . $count) : ''));

	echo '		<div class="wocloudFormWrapperText" style="overflow:auto; max-height:300px; background-color:#', $color, ';">';
	if (empty($count))
	{
		if ($listType === 'Exclude' && $count === 0)
			echo "<div class='wocloudUserMessagesText wocloudIconSuccess'>没有需要排除的文件或者文件夹</div>";
		elseif ($pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
			echo "<div class='wocloudIconSuccess' style='font-size:24px; padding-top:4px; font-weight:bold;'>*</div>";
		else
			echo "<div class='wocloudUserWarningsText wocloudIconError'>Nothing selected for restoration. To restore everything, except some items, use &quot;", ZMC_Restore::$buttons[ZMC_Restore::EXPRESS], "&quot restore on the Restore|what page.</div>";
	}
	else
	{
		if (($count = count($map)) > 100)
			echo "<div class='wocloudUserWarningsText wocloudIconWarning'>Too many selections ($count) to display.<br />Showing only the first 100 selections:</div>\n";
		if (!empty($pm->restore[$list]))
			echo "<div class='wocloudUserMessagesText wocloudIconWarning' style='font-size:16px; padding-top:4px; font-weight:bold;'> ", str_replace("\n", "<br />\n", $pm->restore[$list]), "</div>\n";
		if (!empty($map))
		{
			$i = 1;
			foreach($map as &$row) 
			{
				if ($i++ > 100) break;
				echo ZMC::escape($row['filename']), ($row['type'] == 1 ? '*' : ''), "<br />\n";
			}
		}
	}
		echo '	</div><!-- "wocloudFormWrapperText" -->';
	echo '</div><!-- wocloudLeftWindow" -->';
}
?>

</div>
</form>
