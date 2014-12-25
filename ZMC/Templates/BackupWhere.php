<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if (isset($pm->form_type) && ($pm->form_type['license_group'] === 'changer' || ($pm->form_type['license_group'] === 'tape')))
	if (!isset($pm->binding) || strncmp($pm->binding['_key_name'], 'changer_ndmp', 11))
		require 'ZMC/Templates/lsscsiWindow.php';

if ($pm->state === 'Use1')
{
	echo "<div class='zmcLeftWindow'>\n";
	ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . ' device configuration');
	if (!count(ZMC_BackupSet::getMyNames()))
		$sets = '<p style="padding:5px;">Please <a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'backup sets') . '">create a backup set</a> first.</p>';
	elseif (empty($pm->sets))
		$sets = "<p style='padding:5px;'>All backup sets already have devices configured.<br />Create a new backup set to use a new device.</p>";
	else
	{
		$sets = '<select name="edit_id">';
				
		ksort($pm->sets);
		foreach($pm->sets as $name => $set)
		{
			$selected = ($name === $pm->selected_name) ? 'selected="selected"' : '';
			$sets .= "<option $selected value='" . ZMC::escape($name) . '\'>' . ZMC::escape($name) . "</option>\n";
		}
		$sets .= "</select>\n";
	}
	
	$i = 0;
	$devices = '';
	foreach($pm->device_profile_list as $name => $device)
	{
		if (($i++ % 3) === 0) $devices .= "\n</tr><tr>\n";
		$disabled = $onclick = '';
		$selected = (!empty($pm->selected_device) && ($name === $pm->selected_device)) ? 'checked="checked"' : '';
		$icon = ZMC_Type_Devices::getIcon($pm, $device, $disabled);
		$devices .= "
			<td>
				<div style='padding:5px'>
					<input type='radio' name='selected_device' value='$name' $selected $disabled id='radio$i' onclick=\"gebi('use_button').disabled = ''\">
					<label for='radio$i'>$icon$name</label>
				</div>
			</td>\n";
	}
?>
	<div wrapperUse1 class="zmcFormWrapper">
		<fieldset><legend>Backup Set</legend><?= $sets ?></fieldset>
		<fieldset><legend>Choose Storage Device</legend><?= $devices ?></fieldset>
	</div><!-- formWrapper -->
	<div class="zmcButtonBar">
		<? if (!empty($pm->sets)) echo '<input id="use_button" type="submit" name="action" value="Use" ',
			(empty($pm->selected_device) ? 'disabled="disabled"':''), ' />'; ?>
	</div>
</div><!-- zmcLeftWindow -->
<?
} 
elseif (!empty($pm->binding))
{
?>
<div id='deviceFormWrapper' class='zmcLeftWindow'><?
	ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . ' ' . $pm->selected_name . ' configuration for device: ' . $pm->binding['private']['zmc_device_name'], $pm->state);
	$icon = ZMC_Type_Devices::getIcon($pm, $pm->binding, $disabled);
?>
	<div wrapperCreate2 class="zmcFormWrapper <?= $pm->form_type['form_classes'] ?>">
		<img class="zmcWindowBackgroundimageRight" style="top:10px;" src="/images/3.1/<? echo ($pm->state === 'Edit' ? 'edit' : 'add'); ?>.png" />
		<div class="p" style='min-height:80px;'>
			<label>Device Type:</label>
			<div class='zmcAfter'><a style="display:block; border:solid blue 1px; padding:2px; margin:2px;" href="/ZMC_Admin_Devices?action=Edit&id=<? echo urlencode($pm->binding['private']['zmc_device_name']); ?>"><?= $icon ?></a></div>
			<label style='clear:left;'>&nbsp;</label>
			<div class='zmcAfter'><?= $pm->pretty_name ?></div>
		</div>
		<?= $pm->form_html ?>
		<div style='clear:left;'></div>
	</div><!-- zmcFormWrapper -->

<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
?>

	<div class="zmcButtonBar">
		<?php if($pm->binding['_key_name'] === "changer_library" && ($pm->state === 'Edit' || $pm->state === 'Update')){ ?>
			<input type="submit" name="action" value="Update & Verify Tape Drive" onclick='return window.confirm("Verifying configuration may take several minutes to complete and will load tapes in and out of each unskipped tape drive. \n\nWarning: No backup nor other process should be using the tape drives during this time and please ensure that only one user at a time is interacting with the forms on the Backup Where page. Please wait for the requested operation to complete before starting a new operation.  Continue?");' />
		<?php } ?>
		<input type="submit" name="action" value="<? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? 'Update' : 'Add'); ?>" />
		<input type="submit" value="Cancel" id="cancelButton" name="action"/>

	</div>
</div><!-- zmcLeftWindow -->
<?
}

$pm->tableTitle = 'View, add, and edit how backup sets use devices';
$pm->buttons = array('Refresh Table' => true, 'Edit' => false, 'Expert' => false);
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
?>
<script>
var o =gebi('private:bandwidth_toggle');
if(o.checked == true){	
	gebi('device_property_list:MAX_SEND_SPEED').setAttribute('disabled','disabled');
	gebi('device_property_list:MAX_RECV_SPEED').setAttribute('disabled','disabled');
	gebi('device_property_list:NB_THREADS_BACKUP').disabled = true;
	gebi('device_property_list:NB_THREADS_RECOVERY').disabled = true;;
}
</script>

<?php

?>
