<?













global $pm;
?>


<form id="js_auto_refresh_form" method="post" action="<?= $pm->url ?>" xmlns="http://www.w3.org/1999/html">
<div class="wocloudLeftWindow" style="width:550px;">
	<? ZMC::titleHelpBar($pm, '备份监控 ', 'Monitor Backups '); ?>
	<div class="wocloudFormWrapper">
		<div class='dataTable' style='border:1px solid #5C706E; margin-bottom:10px; float:right;'><table class='datatable'>
			<tr>
				<th><label><b>备份类型</b></label></th>
				<th><b>隐藏?</b></th>
			</tr><tr>
				<td class="p"><label for="monitor_completed">已完成： <b><?= $pm->backup_stats['completed'] ?></b> </label></td>
				<td>
					<input type="hidden" name="monitor_filters[completed]" value="" />
					<input id="monitor_completed" type="checkbox" title="Hides backups that completed successfully." name="monitor_filters[completed]" <?= empty(ZMC::$userRegistry['monitor_filters']['completed']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr><tr>
				<td class="p"><label for="monitor_staging">缓存区： <b><?= $pm->backup_stats['staging'] ?></b></label></td>
				<td>
					<input type="hidden" name="monitor_filters[staging]" value="" />
					<input id="monitor_staging" type="checkbox" title="Hide backups that are in staging areas." name="monitor_filters[staging]" <?= empty(ZMC::$userRegistry['monitor_filters']['staging']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr><tr>
				<td class="p"><label for="monitor_failed">失败： <b><?= $pm->backup_stats['failed'] ?></b></label></td>
				<td>
					<input type="hidden" name="monitor_filters[failed]" value="" />
					<input id="monitor_failed" type="checkbox" title="Hides all failed backups." name="monitor_filters[failed]" <?= empty(ZMC::$userRegistry['monitor_filters']['failed']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr><tr>
				<td class="p"><label for="monitor_progress">备份中： <b><?= $pm->backup_stats['progress'] ?></b></label></td>
				<td>
					<input type="hidden" name="monitor_filters[progress]" value="" />
					<input id="monitor_progress" type="checkbox" title="Hides all active backups." name="monitor_filters[progress]" <?= empty(ZMC::$userRegistry['monitor_filters']['progress']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr><tr>
				<td class="p"><label for="monitor_recent">历史备份 <b><?= $pm->backup_stats['older'] ?></b></label></td>
				<td>
					<input type="hidden" name="monitor_recent" value="" />
					<input id="monitor_recent" type="checkbox" title="Hides all except most recent backup for each backup set." name="monitor_recent" <?= empty(ZMC::$userRegistry['monitor_recent']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr><tr>
				<td class="p"><label for="details">详细</label></td>
				<td>
					<input type="hidden" name="monitor_details" value="" />
					<input id="details" type="checkbox" title="Hide text descriptions of backup status and progress." name="monitor_details" <?= empty(ZMC::$userRegistry['monitor_details']) ? '' : 'checked="checked"' ?> />
				</td>
			</tr>
			</table>
		</div>
		<div class="p">
			<label>查看备份</label>
			<select class="wocloudShortestInput" name="monitor_when">
			<?
				foreach(array(
					'--no filter--',
					'older than',
					'newer than',
					) as $option)
				echo "<option ", ((ZMC::$userRegistry['monitor_when'] === $option) ? 'selected="selected"' : ''), ">$option</option>";
			?>
				</select><input class="wocloudUltraShortInput" type="text" name="monitor_days" value="<?= ZMC::$userRegistry['monitor_days'] ?>" maxlength="3" size="3" /> days
		</div><div class="p">
			<label for="monitor_which">所属备份集?</label>
			<input type="hidden" name="monitor_which" value="" />
			<select id="monitor_which" name="monitor_which">
				<?
                    if (ZMC_User::hasRole('Administrator'))
                        echo "<option value=''>ALL</option>";
					foreach($pm->sets as $name => $set)
					{
						$selected = ($set['configuration_id'] === ZMC::$userRegistry['monitor_which']) ? 'selected="selected"' : '';
						echo "<option $selected value='$set[configuration_id]'>" . ZMC::escape($name) . "</option>\n";
					}
				?>
			</select>
			<div style="clear:left;"></div>
		</div>
		<div class="p">
			<label for="auto_refresh">自动刷新?</label>
			<?
				$checked = '';
				if (!empty(ZMC::$userRegistry['monitor_refresh']))
				{
					$secs = max(ZMC::$userRegistry['monitor_refresh'], 5);
					$interval = $secs * 1000;
					$checked = 'checked="checked"';
					echo <<<EOD
<script>
	var monitor_countdown = $secs;
	setTimeout(function () { gebi('js_auto_refresh_form').submit(); }, $interval)
	function mcountdown()
	{
		var o = gebi('monitor_countdown')
		if (o)
			o.innerHTML = '&bull;'.repeat(monitor_countdown--)
		if (monitor_countdown < 1)
			monitor_countdown = $secs
		setTimeout( 'mcountdown()', 1000 )
	}
	mcountdown()
</script>
EOD;
				}
			?>
			<span id='monitor_countdown'></span>
			<input type="hidden" name="monitor_refresh" value="0" />
			<input id="auto_refresh" type="checkbox" name="monitor_refresh" value="15" <?= $checked ?> />
			<div style="clear:left;"></div>
		</div>
		<div class="p">
			<label>总备份数: <b><?= count($pm->rows) ?></b></label>
		</div>
		<div style="clear:left;"></div>
	</div>
	<div class="wocloudButtonBar">
		<button type='submit' name='action' value='Refresh' />刷新</button>
	</div>
</div>



<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '图例：备份级别/状态'); ?>
	<img src="/images/section/monitor/legend_monitor.gif" />
</div>



<?
$pm->tableTitle = '监控时间表';
$pm->disable_onclick = true;
$pm->disable_checkboxes = true;
$pm->buttons = array('Refresh Table' => true);
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
