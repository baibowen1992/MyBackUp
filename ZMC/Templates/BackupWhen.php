<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
if ($pm->state === 'Edit')
{
	$action = rtrim($pm->state, '012');
?>
<input type='hidden' name='config_name' value='<?= ZMC::escape($pm->binding['config_name']) ?>' />
<input type='hidden' name='private[zmc_device_name]' value='<?= ZMC::escape($pm->binding['private']['zmc_device_name']) ?>' />
<input type='hidden' name='_key_name' value='<?= ZMC::escape($pm->binding['_key_name']) ?>' />
<input type='hidden' name='private[occ]' value='<?= ZMC::escape($pm->binding['private']['occ']) ?>' />
<input type='hidden' name='private[profile_occ]' value='<?= ZMC::escape($pm->binding['private']['profile_occ']) ?>' />
<div class="zmcLeftWindow" style='max-width:575px;'>
	<? ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . ' Backup Schedule ' . $pm->binding['private']['zmc_device_name'] . ' for: ' . $pm->binding['config_name'], $pm->state);
?>
	<div class="zmcFormWrapper zmcUltraShortInput">
		<img id="schedule_icon" style="position:absolute; left:10px; top:35px;" src="/images/section/admin/schedule-transparent-80.png">
		<div class="p">
			<label><b>Backup Start Time:<span class="required">*</span></b></label>
			<? showHours($pm); ?>
		</div>

<? if ($pm->binding['dev_meta']['media_type'] !== 'vtape') { ?>
		<div class="p">
			<label><b>Backup Cycle:<span class="required">*</span></b></label>
			<input type="text" name="dumpcycle" value="<?= isset($pm->binding['schedule']['dumpcycle']) ? ceil(trim(str_replace('days', '', $pm->binding['schedule']['dumpcycle'])) ) : '1'; ?>" /><div class="zmcAfter">days</div>
			<label style='clear:left;'>&nbsp;</label>
			<div class="zmcAfter" style="width:385px;">
				How often do you want to have at least one full backup?
				<br />Note: At least one full backup will be performed during this period. Incrementals will be performed in between, <u>unless</u> you use a &quot;Custom&quot; Schedule Type below.
			</div>
		</div>

		<div class="p">
			<label><b>Retention Period:</b></label>
			<input type="text" name="desired_retention_period" value="<?= empty($pm->binding['schedule']['desired_retention_period']) ? $pm->binding['schedule']['dumpcycle'] : $pm->binding['schedule']['desired_retention_period'] ?>"> days (your target goal)
		</div>

		<div class="p">
		<?
		$pm->form_type = array('advanced_form_classes' => 'zmcFormWrapperLeft zmcUltraShortInput');
		$runtapes = $pm->binding['schedule']['runtapes'];
		$tape = $pm->binding['dev_meta']['media_name'];
		$tapes = $pm->binding['dev_meta']['media_names'];
		switch($pm->binding['dev_meta']['media_type'])
		{
			case "tape":
				?><label><?= $tapes ?> in Rotation:<span class="required">*</span></label>
					<input type="text" name="tapecycle" value="<?= isset($pm->binding['schedule']['tapecycle']) ? $pm->binding['schedule']['tapecycle'] : $pm->binding['schedule']['tapes_per_dumpcycle'] ?>">
					<div class="zmcAfter">Determines backup retention period.</div>
					<div class="contextualInfoImage"><a id="client_info_help1" target="_blank" href=""><img width="18" height="18" align="top" alt="" title="" src="/images/icons/icon_info.png"/></a>
						<div class="contextualInfo">
							<p>Each tape allocated to this backup set always belongs to either the &quot;list of tapes&quot; in rotation, or the set of tapes available for use during the next backup run, or the set of &quot;archived&quot; tapes.  Tapes in the rotation list and archived tapes are never used for new backups.  The &quot;list of tapes&quot; works like a checkout line at a store, where Amanda removes the oldest tape from the front of the list, adding it to the set of tapes available, before starting each backup run.</p>
							<p>Removed tapes are added to the set of available tapes, which can be reused at any time.  Just used vtapes are added to the end of the list.  Usually, the available tapes are used in the same order they were removed from the &quot;list of tapes&quot; in rotation to help avoid using any particular tape much more than other tapes.</p>
						</div>
					</div>
					<label style='clear:left;'>&nbsp;</label>
					<div class="zmcAfter" style='width:385px;'>
					<?php 
						if ($pm->binding['schedule']['total_tapes_needed'] > $pm->binding['schedule']['tapecycle'])
							echo "<font color='#800000'><div class='p'><span style='color:#cc0000'>Warning:</span> Insufficient $tapes in rotation list for desired retention period. ";
						else
							echo "<font color='#000000'><div>";
?>
						<? echo $pm->binding['schedule']['total_tapes_needed'], ' ', $tapes; ?> or more are recommended for a retention period of <?= $pm->binding['schedule']['desired_retention_period'] ?> days.
						</div>
						</font>
						Use a number equal to the number of <?= $tape ?> slots plus any
						manually rotated <?= $tapes ?> on the &quot;shelf&quot; minus the number
						of archived<?= $tapes ?> minus number of <?= $tapes ?> used per backup.
					</div>
					<div style='clear:left'></div>
		<?
				$pm['form_advanced_html'] = <<<EOD
				<div foobar class="p" style="margin-left:-90px;">
					<label><b>$tapes Per Backup:<span class="required">*</span></b></label>
					<input type="text" name="runtapes" title="The number of media allocated for each backup run." value="$runtapes" /> Sets upper limit for this backup set.
				</div>
EOD;
				break;

			case "vtape":
?>
					<label><b><?= $tapes ?><br />in Rotation:<span class="required">*</span></b></label>
					<input type="text" name="tapecycle" value="<?= isset($pm->binding['schedule']['tapecycle']) ? $pm->binding['schedule']['tapecycle'] : $pm->binding['schedule']['tapes_per_dumpcycle'] ?>">
					<div class="zmcAfter">Determines backup retention period.</div>
					<div class="contextualInfoImage">
						<a id="client_info_help1" target="_blank" href=""><img width="18" height="18" alt="" title="" src="/images/icons/icon_info.png"/></a>
						<div class="contextualInfo">
							<p>Each tape allocated to this backup set always belongs to either the &quot;list of vtapes&quot; in rotation, or the set of vtapes available for use during the next backup run, or the set of &quot;archived&quot; tapes.  Tapes in the rotation list and archived tapes are never used for new backups.  The &quot;list of vtapes&quot; works like a checkout line at a store, where Amanda removes the oldest tape from the front of the list, adding it to the set of tapes available, before starting each backup run.</p>
							<p>Removed vtapes are added to the set of available vtapes, which can be reused at any time.  Just used vtapes are added to the end of the list.  Usually, the available vtapes are used in the same order they were removed from the &quot;list of vtapes&quot; in rotation to help avoid using any particular tape much more than other vtapes.</p>
						</div>
					</div>
					<label style='clear:left;'>&nbsp;</label>
					<div class="zmcAfter" style='width:385px;'>
						Use <? echo $pm->binding['schedule']['tapes_per_dumpcycle'], ' ', $tapes; ?> or more for a retention period of <?= $pm->binding['schedule']['dumpcycle'] ?> days.
						<br />This number may <b>not</b> exceed 
							the total number of <? echo "$tapes available (", $pm->binding['schedule']['total_tapes_available'],
							") minus the number of archived $tapes (", $pm->binding['schedule']['archived_media'],
							") minus the number of $tapes used each backup run (", $pm->binding['schedule']['tapes_per_backup_run'], ")"; ?>
							- needed to protect the oldest full backup when replacing it with a new full backup.
					</div>

		<?
				$name = $pm->binding['dev_meta']['name'];
				$devType = ucFirst($pm->binding['_key_name']);
				if ($pm->binding['dev_meta']['device_type'] !== ZMC_Type_Devices::TYPE_CLOUD)
				$pm['form_advanced_html'] = <<<EOD
				<div foobar class="p" style="margin-left:-90px;">
					<label>Media per backup:<span class="required">*</span></label>
					<input type="text" disabled="disabled" name="runtapes" title="The number of $tapes allocated for each backup run."  value="$runtapes" />$name devices use exactly one $tape per backup run.
					<div style="clear:left;"></div>
				</div>
EOD;
				break;

			default:
				
				ZMC::quit(array("Unknown media type" => $pm->binding['_key_name'], $pm));
		}
		?>
		<div style='clear:left;'></div>
		</div>
		<?
		if (isset($pm->binding['runspercycle']))
			echo "<div class='p'><label>Minimum $tape needed for this backup set is: {$pm->binding['runspercycle']} x {$pm->binding['runtapes']} media per backup + 1 = " . '@TODO@' . "</label><div style='clear:left;'></div></div>\n";
} 
		?>



		<div class="p">
			<label><b>Schedule Type:<span class="required">*</span></b></label>
			<select id="zmc_schedule_type" class="zmcLongerInput" name="schedule_type" onchange="
				var ow=gebi('custom_weekday')
				var fh=gebi('full_hours')
				var od=gebi('custom_days_of_the_month')
				var icon=gebi('schedule_icon')
				if (this.value === 'Custom Weekday')
				{
					ow.style.display = 'block'
					fh.style.display = 'block'
					od.style.display = 'none'
					//icon.style.display = 'none'
				}
				else if (this.value === 'Custom Days of the Month')
				{
					ow.style.display = 'none'
					fh.style.display = 'block'
					od.style.display = 'block'
					//icon.style.display = 'none'
				}
				else
				{
					fh.style.display = 'none'
					ow.style.display = 'none'
					od.style.display = 'none'
					icon.style.display = 'block'
				}
				">
			<?
				foreach(array(
					'Everyday (Recommended)',
					'Every Weekday (Recommended)',
					'Every Saturday',
					'Custom Weekday (Advanced)',
					'Custom Days of the Month (Advanced)',
					
					'Incremental Weekdays, Full Saturday',
					'Incremental Weekdays, Full Sunday'
					) as $type)
				{
					$value = str_replace(' (Advanced)', '', str_replace(' (Recommended)', '', $type));
					echo "\t\t\t\t\t<option value='$value' ", (($pm->binding['schedule']['schedule_type'] === $value) ? ' selected="selected"' : ''), ">$type</option>\n";
				}
			?>
			</select>
		</div>



		<div id="full_hours" class="p" style="display:none">
			<label><b>Full Backup Time:<span class="required">*</span></b></label>
			<input name="full_hours_same" value="0" type="hidden" />
			<input id="full_hours_same" name="full_hours_same" value="1" type="checkbox" <? if (!empty($pm->binding['schedule']['full_hours_same'])) echo ' checked="checked"'; ?> onchange="var o=gebi('full_hours_detail'); if (this.checked) o.style.display = 'none'; else o.style.display = 'block'" /> <label style="float:none;" for="full_hours_same">Same as hours for Auto/Incremental (above)</label>?
			<div id="full_hours_detail" <? if (!empty($pm->binding['schedule']['full_hours_same'])) echo 'style="display:none"'; ?>>
				<? showHours($pm, 'full_'); ?>
			</div>
		</div>

		<div id="custom_weekday" style="display:none">
		<div class="p">
			<label><b>Custom Weekday<br />(Advanced):<span class="required">*</span></b></label>
				<div class="zmcAfter">
					<div class="dataTable zmcBorder">
						<table width="100%" style="text-align:center">
							<tr>
								<th style="text-align:right;">Type</th>
								<th class="zmcCenterNoLeftPad">Mon</th>
								<th class="zmcCenterNoLeftPad">Tue</th>
								<th class="zmcCenterNoLeftPad">Wed</th>
								<th class="zmcCenterNoLeftPad">Thur</th>
								<th class="zmcCenterNoLeftPad">Fri</th>
								<th class="zmcCenterNoLeftPad">Sat</th>
								<th class="zmcCenterNoLeftPad">Sun</th>
							</tr>
							<tr>
								<th style="text-align:right;">No Backup</th>
								<? showDays($pm, ''); ?>
							</tr>
							<tr>
								<th style="text-align:right;">Auto</th>
								<? showDays($pm, 'auto'); ?>
							</tr>
							<tr>
								<th style="text-align:right;">Full</th>
								<? showDays($pm, 'full'); ?>
							</tr>
							<tr>
								<th style="text-align:right;">Incremental</th>
								<? showDays($pm, 'incremental'); ?>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>



		<div id="custom_days_of_the_month" style="display:none">
		<div class="p zmcShortestInput">
			<label><b>Custom Days of the Month (Advanced) Backup:<span class="required">*</span></b></label>
			<table class="dataTable zmcBorder" style="text-align:center; border-collapse:separate;">
				<tr>
					<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
					<th style="text-align:center;">Type</th>
					<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
					<th style="text-align:center;">Type</th>
					<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
					<th style="text-align:center;">Type</th>
				</tr>
				<?
				for($i=1; $i<12; $i++)
				{
					echo "\t\t\t\t<tr>\n";
					for($j=0; $j<3; $j++)
					{
						if ($i === 11 && ($j < 2))
						{
							echo "\t\t\t\t\t\t<td></td><td></td>\n";
							continue;
						}
						$dom = $i + $j*10;
						echo "\t\t\t\t\t\t<td>$dom</td><td><select name='custom_dom[$dom]'><option value=''>-</option>";
						foreach(array('auto', 'full', 'incremental') as $type)
						{
							$selected = '';
							if (isset($pm->binding['schedule']['custom_dom'][$dom])
								&& ($pm->binding['schedule']['custom_dom'][$dom] === $type))
									$selected = ' selected="selected"';
							echo "<option$selected>$type</option>";
						}
						echo "</select></td>\n";
					}
					echo "\t\t\t\t<tr>\n";
				}
				?>
			</table>
		</div>
		</div>
		<br />
		<div style="clear:left;"></div>
	</div><!-- zmcFormWrapperLeft -->

<?	if (!empty($pm->form_advanced_html)) ZMC_Loader::renderTemplate('formAdvanced', $pm); ?>

	<div class="zmcButtonBar">
		<input type="submit" name="action" value="Update" title="Update" />
		<input type="submit" name="action" value="Cancel" title="Cancel" />
	</div>
</div><!-- zmcLeftWindow -->



<? if ($pm->binding['dev_meta']['media_type'] !== 'vtape') { ?>
<div class="zmcRightWindow">
	<? ZMC::titleHelpBar($pm, 'Retention & Statistics'); ?>
	<div class="zmcFormWrapper zmcLongestLabel zmcUltraShortInput">
		<fieldset><legend>Retention Statistics</legend>

			<div class="p">
				<label><b>Requested Retention Period:<span class="required">*</span></b></label>
				<input type="text" name="requested_retention_period" value="<?= empty($pm->binding['schedule']['desired_retention_period']) ? $pm->binding['schedule']['dumpcycle'] : $pm->binding['schedule']['desired_retention_period'] ?>"> days
			</div>

			<div class="p">
				<label>Historical Retention Period:</label>
				<input type="text" disabled="disabled" name="" value="<? $p = $pm->binding['schedule']['historical_retention_period']; echo ($p === 'NA' ? $p : "~$p"); ?>"> days
			</div>

			<div class="p">
				<label>Estimated Retention Period:</label>
				<input type="text" disabled="disabled" name="" value="~<?= $pm->binding['schedule']['estimated_retention_period'] ?>"> days
			</div>
	
			<div class="p">
				<label>DLE w/shortest Retention Period:</label>
				<input type="text" disabled="disabled" name="" value="~<?= $pm->binding['schedule']['retention_timestamp'] === 'NA' ? 'NA' : round((time() - $pm->binding['schedule']['retention_timestamp']) / 86400, 1) ?>"> days
			</div>

		</fieldset>

		<br />

		<fieldset style='clear:both;'><legend>Tape Statistics</legend>

			<div class="p">
				<label>Estimated <?= $tapes ?> Needed:<!--br />(for desired retention period)--></label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['estimated_tapes_per_retention_period'] ?>">
			</div>
	
			<div class="p">
				<label><?= $tapes ?> Archived:</label>
				<input type="text" disabled="disabled" name=""  value="<?= $pm->binding['schedule']['archived_media'] ?>">
			</div>
	
			<div class="p">
				<label>Total <?= $tapes ?> Needed:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['total_tapes_needed'] ?>">
			</div>
	<?
			if ($pm->binding['schedule']['estimated_tapes_per_retention_period'] > $pm->binding['schedule']['tapecycle'])
				echo "<div class='p'><span style='color:#cc0000'>Warning:</span> Insufficient $tapes in rotation list<br />for desired retention period.</div>";
	
			$style = $class = '';
			if (!empty($pm->binding['schedule']['shortfall']))
			{
				$class = ' zmcUserErrorsText';
				$style = 'color:#CC0000;';
			}
	?>
			<div class="p">
				<label><?= $tapes ?> Used This Cycle:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['dumpcycle_tapes_used'] ?>">
			</div>
	
			<div class="p">
				<label>Total <?= $tapes ?> Used:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['total_tapes_seen'] ?>">
			</div>
	
			<div class="p">
				<label><?= $tapes ?> Per Cycle:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['tapes_per_dumpcycle'] ?>">
			</div>
	
			<div class="p">
				<label>Backup Runs / Week:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['runs_per_week'] ?>">
			</div>
	
			<div class="p">
				<label>Backup Runs / Backup Cycle:</label>
				<input type="text" disabled="disabled" name="" value="<?= $pm->binding['schedule']['runspercycle'] ?>">
			</div>
	
		</fieldset>

		<br style="clear:left;" />

		<p>&nbsp;<span class="zmcUserErrorsText">Note:</span> Click &quot;Update&quot; button to refresh statistics.</p>

	</div><!-- zmcFormWrapperLeft -->
</div><!-- zmcLeftWindow -->
<?
} 
}

function showDays($pm, $value)
{
	foreach(array(1,2,3,4,5,6,0) as $day)
	{
		echo "\t\t\t\t\t<td><input type='radio' name='custom_days[$day]' value='$value' ";
		if (isset($pm->binding['schedule']['custom_days'][$day]) && ($pm->binding['schedule']['custom_days'][$day] === $value))
			echo " checked='checked' />";
		echo "</td>\n";
	}
}

function showHours($pm, $prefix = '')
{
	$field = $prefix . 'hours';
	echo "<label style='clear:left; text-align:right'><b>AM&nbsp;&nbsp;</b></label>\n";
	if (!isset($pm->binding['schedule'][$field]))
		$pm->binding['schedule'][$field] = array();

	for($i=0; $i<24; $i++)
	{
		$h = $i;
		if ($i === 0)
			$h = 12;
		elseif ($i > 12)
			$h = $i - 12;

		$checked = '';
		if (!empty($pm->binding['schedule'][$field][$i]))
			$checked = 'checked="checked"';

		echo "\t\t\t\t<input type='hidden' name='{$field}[$i]' value='' />\n"; 
		echo "\t\t\t\t<div class='zmcAfter' style='padding-left:0;'>$h</div><input $checked style='width:10px;' type='checkbox' name='{$field}[$i]' value='1' />\n";

		if ($i === 11)
			echo "<label style='clear:left; text-align:right'><b>PM&nbsp;&nbsp;</b></label>\n";
	}
	?>
		<div style="clear:left;">
			<br />
			<label style="text-align:right;"><b>Minutes&nbsp;</b></label>
			<input type="text" name="<?= $prefix ?>minute" maxlength="2" value="<?= $pm->binding['schedule'][$prefix . 'minute'] ?>" /><div class="zmcAfter">after the hours selected above</div>
		</div>
<?
}
$pm->tableTitle = 'View and edit scheduled backup';
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
