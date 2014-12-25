<?
//zhoulin-restore-what 201409221624
//右边   查看已选择的恢复文件列表











global $pm;
?>
<input id='hack_for_ie_ff' type="hidden" name="action" value="" />

<div class="wocloudRightWindow" id="restoreWhatRightContainer">
	<? ZMC::titleHelpBar($pm, '需要从目录' . $pm->restore['bread_crumbs'] . '中恢复的文件和目录：'); ?>
	<div class="p" style="margin-top:4px; text-align:center; padding:0;">
		<button id='submit_go' type="submit" name="action" value="Go" />前往</button>
		<button class="wocloudButtonsLeft" name="action" type="submit" value="Up" />上一级</button>
		<input
			style="width:340px"
			type="text" 
			name="fpn" 
			title="你想查看文件夹的绝对路径" 
			maxlength="255"
			value="<?= ZMC::escape($pm->restore['fpn']) ?>"
			onkeyup="return onEnterSubmit('submit_go', event);"
		/>
	</div>

	<div class="wocloudBreadCrumbs" style='clear:left;'><? $pm->restore['bread_crumbs'] ?></div>
	<div class="wocloudSubHeadingSelect">
		选择：
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'all'); return false;">全选</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'none'); return false;">全不选</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('rbox_select_all_none_invert', 'invert'); return false;">反选</a>
	</div>
		
	<div class="wocloudFormWrapperText" style="margin-top:0px; height:375px; overflow:auto;" id="rbox_select_all_none_invert">
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
	</div><!-- wocloudFormWrapperText rbox_select_all_none_invert -->
	<div id="nextStep" class="wocloudButtonBar">
		<div class="footerButtons">
			<button type="submit" name="action" value="Next Step" />恢复到哪？</button>
			<button type="submit" name="action" value="<<" class="zmcButtonsLeft" />移除</button>
			<button type="submit" name="action" value="Reset" class="zmcButtonsLeft" />重选</button>
		</div>
	</div>
</div><!-- wocloudRightWindow restoreWhatRightContainer -->
