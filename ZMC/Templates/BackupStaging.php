<?
//zhoulin-backup-staging 201409191449












global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if ($pm->state === 'Edit' && !empty($pm->binding))
{
?>
<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '备份集 ' . $pm->binding['config_name'].'的临时缓存配置', $pm->state);

	$type = $pm->form_type['license_group'];
?>
	<div wrapperCreate2 class="wocloudFormWrapperRight <?= $pm->form_type['form_classes'] ?>">
		<img class="wocloudWindowBackgroundimageRight" src="/images/3.1/edit.png" />
		<?= $pm->form_html ?>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapper -->

<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
?>

	<div class="wocloudButtonBar">
        <button type="submit" name="action" value="Update" />更新</button>
        <button type="submit" value="Cancel" id="cancelButton" name="action"/>取消</button>
	</div>
</div><!-- wocloudLeftWindow -->

<div class="wocloudLeftWindow" style="min-width:275px;">
<?
	ZMC::titleHelpBar($pm, '动态显示临时缓存占用');
	$used = $pm->binding['holdingdisk_list']['zmc_default_holding']['used_space'];
	$enableFlush = $pm->enableFlush ? '' : 'disabled="disabled"';
	$flush = '';
	if ($used)
	{
		$flush = '<div class="wocloudButtonBar wocloudButtonsLeft"><input type="submit" name="action" value="Flush" ' . $enableFlush . ' />';
		if (ZMC::$registry->dev_only)
			$flush .= '<input type="submit" name="action" value="Prune &amp; Flush" class="wocloudButtonsLeft" '. $enableFlush . ' />';
		$flush .= '</div>';
		if ($used >= 1024)
			$used = round(bcdiv($used, 1024, 2), 1) . ' GiB';
		else 
			$used .= ' MiB';
	}
	echo <<<EOD
	<div class="wocloudFormWrapper wocloudShortestInput" style="min-height:30px;">
		<div class="p">
			<label>已使用空间:</label>
			<input type="text" disabled="disabled" value="$used" />
		</div>
	</div>
	<div class='wocloudFormWrapperText' style='padding:5px;'>
		<pre>$pm->holding_list</pre>
	</div>
	$flush
</div>

EOD;
}

$pm->tableTitle = '查看编辑备份集临时缓存';
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
