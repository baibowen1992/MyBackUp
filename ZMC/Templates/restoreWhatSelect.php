<?
//zhoulin-restore-what 201409221624
//调用选择恢复文件的javascript











global $pm;
?>
<div class="wocloudRightWindow" id="restoreWhatRightContainerHost" style="display:none; min-width:400px;">
	<? ZMC::titleHelpBar($pm, '选择主机'); ?>
	<div class="wocloudFormWrapper">
		<input id="hostother" name="h" type="radio" value="Other" onclick="YAHOO.zmc.restore.what.swapPopOff(300, 600); gebi('client').select();" /><label for="hostother">&nbsp;Other</label>
		<?
			$label = 0;
			foreach ($pm->suggestedHosts as $item)
			{
				$escaped = ZMC::escape($item);
				$label++;
				$selected = ((empty($pm->restore['client']) || ($item !== $pm->restore['client'])) ? '':'checked="checked"');
				echo "<br style='clear:left;' />\n<input id='host$label' name='h' type='radio' $selected value='$escaped' onclick='YAHOO.zmc.restore.what.selectHost(this)'><label for='host$label'>&nbsp;$escaped</label>\n";
			}
		?>
	</div><!-- wocloudFormWrapper -->

	<div class="wocloudButtonBar" id="selectHostButtonBarPop">
		<input type="submit" name="action" value="Cancel" onclick="YAHOO.zmc.restore.what.swapPopOff(300, 600); return false;" />
	</div>
</div><!-- wocloudRightWindow restoreWhatRightContainerHost -->

<div class="wocloudRightWindow" id="restoreWhatRightContainerPath" style="display:none; min-width:400px;">
	<? ZMC::titleHelpBar($pm, '选择要恢复的目录'); ?>
	<div class="wocloudFormWrapper" id="restoreWhatRightInteriorContainerPath">Select an initial Host first.</div>
	<div class="wocloudButtonBar">
		<input type="submit" name="cancel" value="Cancel" onclick="YAHOO.zmc.restore.what.swapPopOff(300, 600); return false;" />
	</div>
</div><!-- wocloudRightWindow restoreWhatRightContainerPath -->
