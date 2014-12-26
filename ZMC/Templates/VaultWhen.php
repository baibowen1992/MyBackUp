<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
$action = rtrim($pm->state, '012');
?>
<input type='hidden' name='config_name' value='<?= ZMC::escape($pm->binding['config_name']) ?>' />
<input type='hidden' name='private[zmc_device_name]' value='<?= ZMC::escape($pm->binding['private']['zmc_device_name']) ?>' />
<input type='hidden' name='_key_name' value='<?= ZMC::escape($pm->binding['_key_name']) ?>' />
<input type='hidden' name='private[occ]' value='<?= ZMC::escape($pm->binding['private']['occ']) ?>' />
<input type='hidden' name='private[profile_occ]' value='<?= ZMC::escape($pm->binding['private']['profile_occ']) ?>' />
<div class="wocloudLeftWindow" style='max-width:575px;'>
	<? ZMC::titleHelpBar($pm, 'Schedule a vault job for: ' . $pm->binding['config_name'], $pm->state);
?>
	<div class="wocloudFormWrapper wocloudUltraShortInput">
		<img id="schedule_icon" style="position:absolute; left:10px; top:35px;" src="/images/section/admin/schedule-transparent-80.png">
		<div class="p">
			<label><b>Vault Start Time:<span class="required">*</span></b></label>
			<? showHours($pm); ?>
		</div>

		<div class="p">
			<label><b>Schedule Type:<span class="required">*</span></b></label>
			<select id="zmc_schedule_type" class="wocloudLongerInput" name="schedule_type" onchange="
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
					'Custom Days of the Month (Advanced)'
					) as $type)
				{
					$value = str_replace(' (Advanced)', '', str_replace(' (Recommended)', '', $type));
					echo "\t\t\t\t\t<option value='$value' ", (($pm->binding['schedule']['schedule_type'] === $value) ? ' selected="selected"' : ''), ">$type</option>\n";
				}
			?>
			</select>
		</div>

		<div id="full_hours" class="p" style="display:none">
			<input name="full_hours_same" value="0" type="hidden" />
		</div>

		<div id="custom_weekday" style="display:none">
			<div class="p">
				<label><b>Custom Weekday<br />(Advanced):<span class="required">*</span></b></label>
				<div class="wocloudAfter">
					<div class="dataTable wocloudBorder">
						<table width="100%" style="text-align:center">
							<tr>
								<th style="text-align:right;">Type</th>
								<th class="wocloudCenterNoLeftPad">Mon</th>
								<th class="wocloudCenterNoLeftPad">Tue</th>
								<th class="wocloudCenterNoLeftPad">Wed</th>
								<th class="wocloudCenterNoLeftPad">Thur</th>
								<th class="wocloudCenterNoLeftPad">Fri</th>
								<th class="wocloudCenterNoLeftPad">Sat</th>
								<th class="wocloudCenterNoLeftPad">Sun</th>
							</tr>
							<tr>
								<th style="text-align:right;">No</th>
								<? showDays($pm, 'no'); ?>
							</tr>
							<tr>
								<th style="text-align:right;">Yes</th>
								<? showDays($pm, 'yes'); ?>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div id="custom_days_of_the_month" style="display:none">
			<div class="p wocloudShortestInput">
				<label><b>Custom Days of the Month (Advanced):<span class="required">*</span></b></label>
				<table class="dataTable wocloudBorder" style="text-align:center; border-collapse:separate;">
					<tr>
						<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
						<th style="text-align:center;">Vault?</th>
						<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
						<th style="text-align:center;">Vault?</th>
						<th style="text-align:center;">&nbsp;&nbsp;Day&nbsp;&nbsp;</th>
						<th style="text-align:center;">Vault?</th>
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
							foreach(array('yes', 'no') as $type)
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
		<br>
		<div style="clear:left;"></div>
	</div><!-- wocloudFormWrapperLeft -->

<?	if (!empty($pm->form_advanced_html)) ZMC_Loader::renderTemplate('formAdvanced', $pm); ?>

	<div class="wocloudButtonBar">
		<input type="submit" name="action" value="Next" title="Next" />
		<input type="submit" name="action" value="Cancel" title="Cancel" />
	</div>
</div><!-- wocloudLeftWindow -->

<?

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
		echo "\t\t\t\t<div class='wocloudAfter' style='padding-left:0;'>$h</div><input $checked style='width:10px;' type='checkbox' name='{$field}[$i]' value='1' />\n";

		if ($i === 11)
			echo "<label style='clear:left; text-align:right'><b>PM&nbsp;&nbsp;</b></label>\n";
	}
	?>
		<div style="clear:left;">
			<br />
			<label style="text-align:right;"><b>Minutes&nbsp;</b></label>
			<input type="text" name="<?= $prefix ?>minute" maxlength="2" value="<?= $pm->binding['schedule'][$prefix . 'minute'] ?>" /><div class="wocloudAfter">after the hours selected above</div>
		</div>
<?
}
?>
