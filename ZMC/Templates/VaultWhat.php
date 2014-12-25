<?













global $pm;
$action = rtrim($pm->state, '012');
echo "<form method='post' action='$pm->url'>";
?>
<div class="zmcWindow">
<div class="zmcTitleBar">
<?
	$objectType = isset($pm->form_type) ? ' ' . $pm->form_type['name'] : '';
	echo 'Create a new vault job';
?>
</div>
<a class="zmcHelpLink" id="zmcHelpLinkId" href="<? echo ZMC::$registry->wiki, $pm->tombstone, '+', ucFirst($pm->subnav), '#', $action, urlencode($objectType) ?>" target="_blank"></a>
<?
if($pm->state === 'Create' || $pm->state === 'Cancel'){
	$selected_vault_level = 'latest_full_backup';
	$selected_vault_type = 'latest';
} else {
	$selected_vault_level = $pm->vault_job['vault_level'];
	$selected_vault_type = $pm->vault_job['vault_type'];
}

if($selected_vault_type === 'time_frame'){
	$startDate = $pm->vault_job['vault_start_date'];
	$startTime = $pm->vault_job['vault_start_time'];
	$endDate = $pm->vault_job['vault_end_date'];
	$endTime = $pm->vault_job['vault_end_time'];
} else {
	$startDate = $endDate = date("Y") . '-' . date("m") . '-' . date("d");
	$startTime = "00:00";
	$endTime = "23:59";
}
?>
<div wrapperCreate1 style="padding:20px 20px 10px 150px; width:auto; border-top:0px;">
	<label style="font-weight:bold;">Backup Level:</label>
	<div style="padding-left:20px;">
		<input type="radio" name="vault_level" value="latest_full_backup" id="latest_full_backup_radio_button"<?= $selected_vault_level === 'latest_full_backup' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers(); zmcRegistry.adjust_vault_backup_run_range_div()" /> Latest Full Backup<br style="clear:left;">
		<input type="radio" name="vault_level" value="full_only" <?= $selected_vault_level === 'full_only' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers(); zmcRegistry.adjust_vault_backup_run_range_div()" /> Full Backups Only<br style="clear:left;">
		<input type="radio" name="vault_level" value="all_level" <?= $selected_vault_level === 'all_level' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers(); zmcRegistry.adjust_vault_backup_run_range_div()" /> All Backups Levels<br style="clear:left;">
	</div>
</div>
<div id="backup_run_range_div" wrapperCreate2 style="padding:10px 20px 20px 150px; width:auto; border-top:0px;">
	<label style="font-weight:bold;">Backup Run Range:</label>
	<div style="padding-left:20px;">
		<input type="radio" name="vault_type" value="latest" <?= $selected_vault_type === 'latest' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers()" /> Vault the latest backup run<br style="clear:left;">
		<input type="radio" name="vault_type" value="last_x_days" <?= $selected_vault_type === 'last_x_days' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers()" />Vault all backup runs started in the last <input id="num_of_days" class="zmcShortestInput" type="number" name="num_of_days" style="float:none; text-align:right; width:40px;" value="31"/> days<br style="clear:left;">
		<input type="radio" name="vault_type" id="vault_time_frame_radio_button" value="time_frame" <?= $selected_vault_type === 'time_frame' ? 'checked' : '' ?> onchange="zmcRegistry.adjust_vault_datetime_pickers()" /> Vault all backup runs started within a time frame<br style="clear:left;">
	</div>
	<div style="padding-left:50px;">
		<label for="vault_start_date">From:</label><br style="clear:left;">
		<input type="date" name="vault_start_date" id="start_date_picker" disabled="disabled" value=<?= "\"" . $startDate . "\""?>/>
		<input type="time" name="vault_start_time" id="start_time_picker" disabled="disabled" value=<?= "\"" . $startTime . "\""?>/><br style="clear:left;">
		<label for="vault_end_date">To:</label><br style="clear:left;">
		<input type="date" name="vault_end_date"  id="end_date_picker" disabled="disabled" value=<?= "\"" . $endDate . "\""?>/>
		<input type="time" name="vault_end_time" id="end_time_picker" disabled="disabled" value=<?= "\"" . $endTime . "\""?>/><br style="clear:left;">
	</div>
</div><!-- zmcFormWrapper -->
<div class="zmcButtonBar zmcButtonsLeft">
	<input type="submit" name="action" value="Cancel" />
	<input type="submit" name="action" value="Next" />
</div>
</div><!-- zmcWindow -->

<?echo "</form>";?>
