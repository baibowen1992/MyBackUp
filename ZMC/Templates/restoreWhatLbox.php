<?
//zhoulin-restore-what 201409221624
//左边 查看备份文件列表











global $pm;
?>
<div class="wocloudLeftWindow" style='clear:left; width:460px;'><?
	ZMC::titleHelpBar($pm, '选择需要从备份集' . $pm->restore['config'] .'中恢复的目录和文件');
	ob_start(); ?>
	<div class="zmcSubHeadingSelect">
		选择：
		<a href="" onclick="YAHOO.zmc.utils.select('restoreWhatLeftInteriorContainer', 'all'); return false;">全选</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('restoreWhatLeftInteriorContainer', 'none'); return false;">取消已选</a>&nbsp;|&nbsp;
		<a href="" onclick="YAHOO.zmc.utils.select('restoreWhatLeftInteriorContainer', 'invert'); return false;">反选</a>
	</div>

	<div class="zmcFormWrapperText" id="restoreLoaderID" style="margin-top:0px; max-height:205px;<? if (!$pm->exploring) echo ' display:none; '; ?>">
		<p class="instructions">
			<b>检索文件列表 ...</b><img src="/images/icons/icon_fetch.gif" border="0" title="Loading.." alt="Loading.." /> <br />&nbsp;&nbsp;&nbsp; <a href="" onClick="YAHOO.zmc.restore.what.abort(); return false;">取消</a>
		</p>
	</div>

	<input type='hidden' name='lbox[]' value='' type='checkbox' checked='checked' dummy='do not delete this line' />

	<div class="wocloudFormWrapperText" id="restoreWhatLeftInteriorContainer"
		style="margin-top:0px; max-height:255px; overflow:auto; <? if ($pm->exploring) echo ' display:none; '; ?>"><?


		$filename = '';
		$totalUnique = 0;
		if(!empty($pm->warnings) && empty($pm->rows)){
			$error_message = implode( " ", array_values($pm->warnings));
			if(preg_match("/Maximum Files to Display/", $error_message))
				echo "<small><div class='wocloudUserErrorsText'>". $error_message ."</div></small>"; 
		}
		if (empty($pm->rows))
			echo 'Empty Directory';
		elseif (is_string($pm->rows))
			echo "<div class='wocloudUserErrorsText'>" . ZMC::escape($pm->rows) . '</div>';
		elseif (!is_array($pm->rows))
				echo "<div class='wocloudUserErrorsText'>Found {$pm->restoring} object/files to restore.
					File list exceeds ZMC limits.</div><p>Please use &quot;", ZMC_Restore::$buttons[ZMC_Restore::EXPRESS], "&quot; above, or the command line utility <a href='",
					ZMC::$registry->wiki, "406' target='_blank'>amrecover</a> when restoring from very large sets of files.</p>";
		else
			foreach ($pm->rows as $row)
			{
				if ($row['filename'] === $filename)
					continue;

				$filename = $row['filename'];
				$displayEscaped = ZMC::escape($filename);
				$url = urlencode($filename);
				$begin = $end = '';
				
				
				if(empty($displayEscaped))
					break;
				
				if ($row['restore'])
				{
					$begin = '<span style="background-color:#' . ZMC_Restore_What::$colorMap[$row['restore']] . '">';
					$end = '</span>';
				}
				if ($totalUnique++)
					echo '<br style="clear:left;" />';
				echo "<input id='L$totalUnique' name='lbox[]' value=\"$displayEscaped\" type='checkbox' ";
				if (!empty($pm->restore['lbox']) && !empty($pm->restore['lbox'][$filename]))
					echo 'checked="checked"';
				if ($filename[strlen($filename) - 1] === '/')	
				{
					$children = $row['sibling_id'] - $row['id'] -1;
					$children = ($children ? "($children) " : '');
					echo ' />&nbsp;<a href="?action=Go&amp;fpn=', $url, '">', $begin, $children, $displayEscaped, $end, '</a>', "\n";
				}
				else
					echo ' />&nbsp;<label for="L', $totalUnique, '">', $begin, $displayEscaped, $end, "</label>\n";
			}
		?>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapperText -->
	<?
		$out = ob_get_clean();
		echo '<div class="wocloudBreadCrumbs">', $pm->restore['bread_crumbs'], "<div style='float:right'>($totalUnique)", "</div></div>\n";
		echo $out;
	if(!empty($pm->rows )){
	?>

	<div class="wocloudButtonBar">
		<div class="footerButtons">
			<button type="submit" name="action" value=">>" />添加</button>
		</div>
	</div>
	<?php } ?>
</div><!-- wocloudLeftWindow restoreWhatLeftContainer -->
