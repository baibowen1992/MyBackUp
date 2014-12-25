<?













global $pm;
?>
	<div class="zmcButtonBar zmcButtonsLeft">
		<input type="hidden" name="selected_ids[0]" value="0" />
<?
		if (empty($pm->disable_checkboxes))
			echo '<input type="button" name="noop" value="Invert Selection" onclick="YAHOO.zmc.utils.invert_datatable_checkboxes(this); return false;" />';

if (empty($pm->buttons))
	$pm->buttons = array('Refresh Table' => true,'Edit' => false);

foreach($pm->buttons as $name => $enabled)
{
	if ($enabled === null)
		continue;
	echo '<input name="action" type="submit" ';
	echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
	echo " value='$name' ";
	if (!$enabled || is_string($enabled))
		echo ' disabled="disabled" ';
	if (is_string($enabled))
		echo $enabled;
	echo ' />', "\n";
}

if (isset($pm->html))
	echo $pm->html, "\n";

echo "\t</div>\n";
