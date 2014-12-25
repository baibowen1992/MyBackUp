<?













global $pm;
?>
<input id='hack_for_ie_ff' type="hidden" name="action" value="" />

<div class="zmcRightWindow" id="restoreWhatRightContainer">
	<? ZMC::titleHelpBar($pm, 'Directories / Files to be Restored from: ' . $pm->restore['bread_crumbs']); ?>
	<div class="p" style="margin-top:4px; text-align:center; padding:0;">
		<input id='submit_go' type="submit" name="action" value="Go" />					
		<input class="zmcButtonsLeft" name="action" type="submit" value="Up" />
		<input
			style="width:340px"
			type="text" 
			name="fpn" 
			title="Full pathname of the directory to browse" 
			maxlength="255"
			value="<?= ZMC::escape($pm->restore['fpn']) ?>"
			onkeyup="return onEnterSubmit('submit_go', event);"
		/>
	</div>

	<div class="zmcBreadCrumbs" style='clear:left;'><? $pm->restore['bread_crumbs'] ?></div>
	<div class="zmcSubHeadingSelect">
		Select:
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'all'); return false;">All</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'none'); return false;">None</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'invert'); return false;">Invert</a>
	</div>
		
	<div class="zmcFormWrapperText" style="margin-top:0px; height:375px; overflow:auto;" id="rbox_select_all_none_invert">
		<input type='hidden' name='rbox[]' value='' type='checkbox' checked='checked' dummy='do not delete this line' />
<?
		if (!empty($pm->restore['rbox']) && !is_string($pm->rows))
		{
			ksort($pm->restore['rbox']);
			$i = 0;
			foreach($pm->restore['rbox'] as $filename => $checked){
				if ($filename !== '') 
				{
					
					
					$valueEscaped = ZMC::escape($filename);
					unset($row);
					$row =& $pm->rows[rtrim($filename, '/')];
					$children = ($row['sibling_id'] ? ('(' . ($row['sibling_id'] - $row['id'] -1) . ')') : '');
					if ($i++)
						echo "<br style='clear:left;' />\n";
					echo "<input id='rbf$i' name='rbox[]' value='$valueEscaped' type='checkbox' ", 
						($checked ? ' checked="checked" ' : ''), " />$children<label for='rbf$i'>&nbsp;$valueEscaped</label>\n";
				}
			}
		}
?>
		<div style='clear:left;'></div>
	</div><!-- zmcFormWrapperText rbox_select_all_none_invert -->
	<div id="nextStep" class="zmcButtonBar">
		<div class="footerButtons">
			<button type="submit" name="action" value="Next Step" />Restore Where?</button>
			<button type="submit" name="action" value="<<" class="zmcButtonsLeft" />Remove</button>
			<button type="submit" name="action" value="Reset" class="zmcButtonsLeft" />Reset</button>
		</div>
	</div>
</div><!-- zmcRightWindow restoreWhatRightContainer -->
