<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if ($pm->state === 'Edit' && !empty($pm->binding))
{
?>
<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . ' Staging Configuration for: ' . $pm->binding['config_name'], $pm->state);

	$type = $pm->form_type['license_group'];
?>
	<div wrapperCreate2 class="zmcFormWrapperRight <?= $pm->form_type['form_classes'] ?>">
		<img class="zmcWindowBackgroundimageRight" src="/images/3.1/edit.png" />
		<?= $pm->form_html ?>
		<div style='clear:left;'></div>
	</div><!-- zmcFormWrapper -->

<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
?>

	<div class="zmcButtonBar">
		<input type="submit" name="action" value="Update" />
  		<input type="submit" value="Cancel" id="cancelButton" name="action"/>
	</div>
</div><!-- zmcLeftWindow -->

<div class="zmcLeftWindow" style="min-width:275px;">
<?
	ZMC::titleHelpBar($pm, 'Live Staging Contents');
	$used = $pm->binding['holdingdisk_list']['zmc_default_holding']['used_space'];
	$enableFlush = $pm->enableFlush ? '' : 'disabled="disabled"';
	$flush = '';
	if ($used)
	{
		$flush = '<div class="zmcButtonBar zmcButtonsLeft"><input type="submit" name="action" value="Flush" ' . $enableFlush . ' />';
		if (ZMC::$registry->dev_only)
			$flush .= '<input type="submit" name="action" value="Prune &amp; Flush" class="zmcButtonsLeft" '. $enableFlush . ' />';
		$flush .= '</div>';
		if ($used >= 1024)
			$used = round(bcdiv($used, 1024, 2), 1) . ' GiB';
		else 
			$used .= ' MiB';
	}
	echo <<<EOD
	<div class="zmcFormWrapper zmcShortestInput" style="min-height:30px;">
		<div class="p">
			<label>Space currently used:</label>
			<input type="text" disabled="disabled" value="$used" />
		</div>
	</div>
	<div class='zmcFormWrapperText' style='padding:5px;'>
		<pre>$pm->holding_list</pre>
	</div>
	$flush
</div>

EOD;
}

$pm->tableTitle = 'View and edit how backup sets use staging areas';
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
