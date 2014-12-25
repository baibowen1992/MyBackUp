<?













global $pm;
?>
<div class="zmcRightWindow" id="restoreWhatRightContainerHost" style="display:none; min-width:400px;">
	<? ZMC::titleHelpBar($pm, 'Select Host'); ?>
	<div class="zmcFormWrapper">
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
	</div><!-- zmcFormWrapper -->

	<div class="zmcButtonBar" id="selectHostButtonBarPop">
		<input type="submit" name="action" value="Cancel" onclick="YAHOO.zmc.restore.what.swapPopOff(300, 600); return false;" />
	</div>
</div><!-- zmcRightWindow restoreWhatRightContainerHost -->

<div class="zmcRightWindow" id="restoreWhatRightContainerPath" style="display:none; min-width:400px;">
	<? ZMC::titleHelpBar($pm, 'Select Path'); ?>
	<div class="zmcFormWrapper" id="restoreWhatRightInteriorContainerPath">Select an initial Host first.</div>
	<div class="zmcButtonBar">
		<input type="submit" name="cancel" value="Cancel" onclick="YAHOO.zmc.restore.what.swapPopOff(300, 600); return false;" />
	</div>
</div><!-- zmcRightWindow restoreWhatRightContainerPath -->
