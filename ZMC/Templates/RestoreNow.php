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


<div class="zmcRightWindow" style="width:48%;">
	<? ZMC::titleHelpBar($pm, 'Media Needed', '', '', 'clear:none;'); ?>
	<div class="dataTable" style="border-right-width:0px">
		<table width="100%">
			<tr><th>Level</th><th>Size</th><th>%</th><th>Date</th><th>Time</th><th>Label</th><?= ($pm->restore['restore_type'] === ZMC_Restore::EXPRESS) ? '': '<th>+</th><th>-</th>' ?></tr>
			<?= $labels ?>
		</table>
	</div>
</div><!-- zmcRightWindow -->



<div class="zmcLeftWindow" style="width:48%; clear:left;">
	<? ZMC::titleHelpBar($pm, 'Restore from Backup Image of'); ?>
	<div class="zmcFormWrapper zmcVarInput" style="min-height:60px;">
		<div style="float:right; padding:2px; margin:2px; border:solid blue 1px;"><?= $iconLink ?></div>
		<div class="p">
			<label>Backup made before:</label>
			<b><?= $pm->restore['date_time_parsed'] ?></b>
		</div>
		<div class="p">
			<label>DLE/Object Type:</label>
			<b><?= $pm->restore['pretty_name'] ?></b>
		</div>
		<div class="p">
			<label>Original Host:</label>
			<input 
				type="text" 
				name="client" 
				title="tool tip" 
				value='<?= ZMC::escape($pm->restore['client']) ?>'
				disabled />
		</div>
		<? if ($pm->restore['disk_name'] === $pm->restore['disk_device']) { ?>
		<div class="p">
			<label>Original <?= $pm->folder_name ?>:</label>
			<input 
				type="text" 
				name="disk_device" 
				title="tool tip" 
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
	</div><!-- zmcFormWrapper -->
</div><!-- zmcLeftWindow -->



<div class="zmcRightWindow" style="width:48%; clear:right;">
	<?
	ZMC::titleHelpBar($pm, $pm->restore['config'] . ' Restore Status:  ' . $pm->restore_state['state'][0], '', '', '', '&nbsp;<span id="progress_dots"></span>');
		if (!empty($pm->show_old_result_warning))
			echo '<div class="zmcUserWarningsText zmcIconWarning" style="margin:10px 10px -10px 10px;">Restore criteria have been modified. Showing <b>previous</b> restore results from:<br /><span id="prior_restore_date">', ZMC::humanDate($pm->restore_state['timestamp_end']), '</span></div>';
		if (!empty($pm->restore_state['progress']))
			echo "<div class='zmcSubHeadingSelect' style='margin:10px 10px -10px 10px;'>{$pm->restore_state['progress']}</div>\n";
	?>
	<div class="zmcFormWrapperText" id="restore_status_summary">
		<?
		
		if ($pm->restore_state['state'][0] === ZMC_Yasumi_Job::UNBORN)
		{
			echo 'Restore not started.';
			?>
			<input type="image" name="dummy" value="button" title="Go" style="z-index:9; background-color:transparent;"
				onMouseOver="this.src='/images/3.1/start.png'"
				onMouseOut="this.src='/images/3.1/start_dark.png'"
				src="/images/3.1/start<?= $pm->restore_state['running'] ? '_disabled' : '_dark' ?>.png" class="zmcWindowBackgroundimageRight" width="75" height="75"
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
			echo 'Restore Started: ', $pm->restore_state['date_started_human'], "<br />\n";
			echo "Restore Duration: <span id='duration'>", $pm->restore_state['duration'], "</span><br />\n";
			if (!empty($pm->restore_state['output']))
			{
				echo str_replace("\n", "<br />\n", trim(ZMC::escape(implode("\n", $pm->restore_state['output']))));
				echo '</p>';
			}
			echo '</div><div style="height: 400px; overflow: auto;"><span id="job_status"></span>';
			$replacement =  '<span class="zmcUserErrorsText stripeRed">*&nbsp;Client&nbsp;Error:</span>';
			echo str_replace(array('* Client Status: FAILURE', '* Client Error:', ' Client Error:', ' Client Warning:'),
				array('<span class="zmcUserErrorsText stripeRed">* Client Status: Restore Failed.</span>', $replacement, $replacement, '<span class="zmcIconWarning">Client&nbsp;Warning:</span>'),
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
<div class='p zmcLongLabel'>
	<label for='amclient_timelimit'>No Activity Time Limit<span class="required">*</span>:</label>
	<input type='text' name='amclient_timelimit' id='amclient_timelimit' value='$value' /> seconds
</div>
<div class="p"><label>Compute checksums after restoring?</label><br />
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
		$pm->form_type = array('advanced_form_classes' => 'zmcShortestLabel zmcShortestInput');
		$pm->form_advanced_html .= "</div>\n";
		if (ZMC::$registry->qa_mode)
			$pm->form_advanced_html .= '<div class="p">
				<input type="hidden" name="dryrun" value="0" />
				<input id="dryrun" type="checkbox" name="dryrun" value="1" />
				<label for="dryrun">Dryrun</label>
				<div class="zmcAfter">
					<ol>
					<li>Restore to Temporary Location</li>
					<li>Pretend to move to Destination Location</li>
					<li>Delete Temporary Location</li>
					<li>Save the pretend restore &quot;move/merge&quot; log at:<br />Destination Host:/var/log/amanda/client/</li>
					</ol>
				</div></div>';
		
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
	?>
	<div class="zmcButtonBar">
		<? if ($pm->restore_state['running'])
				echo '<input id="abort_button" type="submit" name="action" value="Abort" onclick="disableButton(this, \'Aborting \.\.\.\')" />';
			elseif ($pm->restore_state['state'][0] !== ZMC_Yasumi_Job::UNBORN)
				echo '<input id="again_button" type="submit" name="action" value="Repeat Restore" onclick="disableButton(this, \'Restoring \.\.\.\'); return true;" />';
			else
				echo '<input id="restore_button" type="submit" name="action" value="Restore" onclick="disableButton(this, \'Restoring \.\.\.\'); return true;" />';
		?>
	</div>
</div>



<div class="zmcLeftWindow" style="width:48%; clear:left;">
	<? ZMC::titleHelpBar($pm, 'Restore to Destination'); ?>
	<div class="zmcFormWrapper zmcVarInput">
		<div class="p">
			<label>Destination Host:</label>
			<input 
				type="text" 
				name="target_host" 
				title="Restore files/objects to this host" 
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
			<label>Destination Location:</label>
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
						echo "<div class='zmcUser{$warnErr}Text' style='float:left;'><img style='vertical-align:text-bottom;' src='/images/global/calendar/icon_calendar_warning.gif' /> Original Location $err</div>";
				} else {

			?>
				<input 
					type="text" 
					name="target_dir" 
					title="Restore files/objects to this directory/destination path" 
					value='<? echo $pm->restore['target_dir'];
						if (strpos($pm->restore['zmc_type'], 'windows') === false && $pm->restore['safe_mode']) echo ($pm->restore['zmc_amanda_app'] === 'cifs' ? '\\':'/') . 'zmc.' . date('Y-m-d_H') . '-MM-SS';
					?>'
					disabled />
			<?		 if (!empty($err))
						echo "<div class='zmcUserErrorsText' style='float:left;'><img style='vertical-align:text-bottom;' src='/images/global/calendar/icon_calendar_warning.gif' />$err</div>";
				} ?>
		</div> 
		<? } if (!$pm->restore['safe_mode'] && !empty($pm->restore['conflict_resolvable'])) { ?>
		<div class="p">
			<?
				$textKey = 'conflict_file_text';
				$policyKey = 'conflict_file_selected';
				foreach(explode('/', $pm->restore['element_name']) as $file_or_dir)
				{
					echo "<label>", ucfirst($file_or_dir), " Conflicts:</label><div width:315px;'>\n";
					$cr = $pm->restore[$textKey];
					$icon = ZMC_Type_AmandaApps::$default_options[$pm->restore[$policyKey]];
					if ($icon === 'zmcIconWarning')
						echo "<span class='zmcIconWarning'>$cr</span> <span class='zmcUserErrorsText'>(risky)</span>";
					elseif ($icon === 'zmcIconSuccess')
						echo "<span class='zmcIconSuccess'>$cr</span>";
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
	</div><!-- zmcFormWrapper -->
</div><!-- zmcLeftWindow -->


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
	<div class='zmcLeftWindow' style='clear:left; width:48%;'>
		<div class='zmcFormWrapperText' style='font-size:24px; font-weight:bold; overflow:auto; max-height:300px; background-color:#<?= ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_SELECT] ?>'>*</div>
	</div>
	<div class='zmcRightWindow' style='clear:right; width:48%; margin-$margin:0;'>
		<div class='zmcFormWrapperText' style='overflow:auto; max-height:300px; background-color:#<?= ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_DESELECT] ?>'>*</div>
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

restoreWindowHelper($pm, 'Exclude', 'elist', 'Exclude ' . $pm->restore['element_names'] . '/patterns', (empty($pm->deselected) ? array():$pm->deselected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_DESELECT], $excludeCount);

restoreWindowHelper($pm, 'Include', 'rlist', 'Restore ' . $pm->restore['element_names'], (empty($pm->selected) ? array():$pm->selected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_SELECT], @$pm->restore['total_files_selected'] + @$pm->restore['total_indirs_selected']);

function restoreWindowHelper($pm, $listType, $list, $title, $map, $color, $count = 0)
{
	$margin   = ($listType === 'Include' ? 'right' : 'left');
	$clear = $position = ($listType === 'Include' ? 'Left' : 'Right');
	if (($listType !== 'Include') && $pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
		$clear = 'both';
	echo "<div class='zmc{$position}Window' style='clear:$clear; width:48%; margin-$margin:0;'>\n";
	ZMC::titleHelpBar($pm, $title . ($count ? (': ' . $count) : ''));

	echo '		<div class="zmcFormWrapperText" style="overflow:auto; max-height:300px; background-color:#', $color, ';">';
	if (empty($count))
	{
		if ($listType === 'Exclude' && $count === 0)
			echo "<div class='zmcUserMessagesText zmcIconSuccess'>Nothing explicitly excluded. Ok.</div>";
		elseif ($pm->restore['restore_type'] === ZMC_Restore::EXPRESS)
			echo "<div class='zmcIconSuccess' style='font-size:24px; padding-top:4px; font-weight:bold;'>*</div>";
		else
			echo "<div class='zmcUserWarningsText zmcIconError'>Nothing selected for restoration. To restore everything, except some items, use &quot;", ZMC_Restore::$buttons[ZMC_Restore::EXPRESS], "&quot restore on the Restore|what page.</div>";
	}
	else
	{
		if (($count = count($map)) > 100)
			echo "<div class='zmcUserWarningsText zmcIconWarning'>Too many selections ($count) to display.<br />Showing only the first 100 selections:</div>\n";
		if (!empty($pm->restore[$list]))
			echo "<div class='zmcUserMessagesText zmcIconWarning' style='font-size:16px; padding-top:4px; font-weight:bold;'> ", str_replace("\n", "<br />\n", $pm->restore[$list]), "</div>\n";
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
		echo '	</div><!-- "zmcFormWrapperText" -->';
	echo '</div><!-- zmcLeftWindow" -->';
}
?>

</div>
</form>
