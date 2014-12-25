<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

function conflict_file_helper($pm)
{
	ZMC_HeaderFooter::$instance->addRegistry(array('file_conflict_option' => "file_" . $pm->restore['conflict_file_selected']));
	$html = '';
	foreach($pm->restore['conflict_file_options'] as $conflictFileOption)
	{
		if ($conflictFileOption == ZMC_Type_AmandaApps::REMOVE_EXISTING) 
			continue;

		$showBasic = "if (gebi('dir_OVERWRITE_EXISTING').checked || gebi('dir_KEEP_EXISTING').checked) gebi('disable_basic').style.visibility='hidden'; ";
		switch($conflictFileOption)
		{
			case ZMC_Type_AmandaApps::KEEP_EXISTING:
				$onclick = "gebi('basic_do_not_restore').click(); $showBasic";
				break;

			case ZMC_Type_AmandaApps::RENAME_RESTORED_N:
				$onclick = "gebi('basic_restore_rename').click(); $showBasic";
				break;

			case ZMC_Type_AmandaApps::OVERWRITE_EXISTING:
				$onclick = "gebi('basic_restore_replace').click(); $showBasic";
				break;

			case ZMC_Type_AmandaApps::RENAME_RESTORED:
			case ZMC_Type_AmandaApps::RENAME_EXISTING:
				$onclick = "gebi('disable_basic').style.visibility='visible'; gebi('basic_restore_replace').checked = gebi('basic_restore_rename').checked = gebi('basic_do_not_restore').checked = false;";
				break;
		}
		$class = ($conflictFileOption == ZMC_Type_AmandaApps::OVERWRITE_EXISTING) ? 'class="zmcIconWarning"':'';
		$ftext = ZMC_Type_AmandaApps::conflict2text($conflictFileOption, $pm->restore['destination_location'], true);
		$div = "
		<div class='p'>
			<input id='file_$conflictFileOption' type='radio' name='conflict_file_selected' value='$conflictFileOption'
				onclick=\"$onclick; zmcRegistry.file_conflict_option = this; return true;\" ";
				
				if (($pm->restore['conflict_file_selected'] == $conflictFileOption) || (count($pm->restore['conflict_file_options']) == 1))
				{
					$pm->clickFileOptOnLoad = "file_$conflictFileOption";
					$div .= 'checked="checked"';
				}
				$div .= " />\n
			<label for='file_$conflictFileOption' $class>$ftext</label>
			<div style='clear:left;'></div>
		</div>\n";
		$html .= $div;
	}
	return $html;
}

































?>


<div class="zmcLeftWindow" style='clear:left; min-width:500px;'>
	<? ZMC::titleHelpBar($pm, 'How to restore, if already exists?'); ?>
		<div class='zmcFormWrapper zmcAutoLabel zmcLongLabel zmcLongInput'>
		<div class="p">
			<label>DLE/Object Type:</label>
			<input 
				type="text" 
				value="<?= ZMC::escape(empty($pm->restore['pretty_name']) ? $pm->restore['name'] : $pm->restore['pretty_name']); ?>"
				disabled="disabled"
			/>
		</div>

		<div class="p">
			<label>Original Host:</label>
			<input 
				type="text" 
				value="<? foreach($pm->restore['media_explored'] as $media) { echo $media['host']; break; } ?>"
				disabled="disabled"
			/>
		</div>

		<div class="p">
			<label>Original Path:</label>
			<input 
				type="text" 
				value="<? foreach($pm->restore['media_explored'] as $media) { echo $media['disk_name']; break; } ?>"
				disabled="disabled"
			/>
		</div>
		<?if($pm->restore['zmc_type'] == 'windowsexchange'){
			$point_in_time_0_checked = '';
			$point_in_time_1_checked = '';
			if(isset($pm->restore['point_in_time']) && $pm->restore['point_in_time'] == '1')
				$point_in_time_1_checked = 'checked';
			else
				$point_in_time_0_checked = 'checked';
		?>
		<div class="p">
			<label>Keep existing logs:</label>
			<input type="radio" <?=$point_in_time_0_checked?> name="point_in_time" value="0" id="point_in_time_0"/>
			<label for="point_in_time_0">Yes</label>
		</div>
		<div class="p">
			<label>&nbsp;</label>
			<input type="radio" <?=$point_in_time_1_checked?> name="point_in_time" value="1" id="point_in_time_1"/>
			<label for="point_in_time_1">No</label>
		</div>
		<?}?>
		<div style='clear:both; height:10px;'></div>
	<?
		$zwc = ($pm->restore['host_type'] == ZMC_Type_AmandaApps::HOST_TYPE_WINDOWS);
		echo "If the Destination Location already has a file or directory with the same name:<br />";

		
		if($pm->restore['zmc_type'] === 'windows'
				&& $pm->restore['restore_type'] === 'express'
				&& $pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_WINDOWS
				&& count($pm->restore['media_explored']) > 1){
			$pm->restore['conflict_file_options'] = array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING);
			$pm->restore['conflict_dir_options'] = array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING);
			$pm->restore['conflict_file_selected'] = $pm->restore['conflict_file_options'][0];
			$pm->restore['conflict_dir_selected'] = $pm->restore['conflict_dir_options'][0];
		}
		
		
		if($pm->restore['zmc_type'] === 'windowsexchange' || $pm->restore['zmc_type'] === 'windowssqlserver' || $pm->restore['zmc_type'] === 'windowssharept'){
			if($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL){
				$pm->restore['conflict_file_options'] = array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING);
				$pm->restore['conflict_dir_options'] = array(ZMC_Type_AmandaApps::OVERWRITE_EXISTING);
				$pm->restore['conflict_file_selected'] = $pm->restore['conflict_file_options'][0];
				$pm->restore['conflict_dir_selected'] = $pm->restore['conflict_dir_options'][0];
			}
		}

		if ((count($pm->restore['conflict_file_options']) > 1) || (count($pm->restore['conflict_dir_options']) > 1))
		{
			$fileConflictOptions = '';
			$opts = array_flip($pm->restore['conflict_file_options']);
			if (isset($opts[ZMC_Type_AmandaApps::OVERWRITE_EXISTING]))
				$fileConflictOptions .= "<div class='p'><input style='z-index:10; position:relative;' type='radio' name='basic_merge' id='basic_restore_replace' value='basic_restore_replace' onclick=\"gebi('file_OVERWRITE_EXISTING').click(); return true;\" /><label for='basic_restore_replace'>Restore &amp; <img style='vertical-align:text-top;' src='/images/global/calendar/icon_calendar_warning.gif' /> Replace</label></div>\n";
			if (isset($opts[ZMC_Type_AmandaApps::KEEP_EXISTING]))
				$fileConflictOptions .= "<div class='p'><input style='z-index:10; position:relative;' type='radio' name='basic_merge' id='basic_do_not_restore' value='basic_do_not_Restore' onclick=\"gebi('file_KEEP_EXISTING').click(); return true;\" /><label for='basic_do_not_restore'>Do not Restore</label></div>\n";
			if (isset($opts[ZMC_Type_AmandaApps::RENAME_RESTORED_N]))
				$fileConflictOptions .= "<div class='p'><input style='z-index:10; position:relative;' type='radio' name='basic_merge' id='basic_restore_rename' value='basic_Restore_rename' onclick=\"gebi('file_RENAME_RESTORED_N').click(); return true;\" /><label for='basic_restore_rename'>Restore, but keep both files</label></div>\n";
			if (!$zwc)
			{
				$visibility = 'null';
				echo <<<EOD
<div class="zmcAfter" style="position:relative;">
<img id='disable_basic' style="visibility:hidden; position:absolute; width:100%; height:100%; background-color:#CCC; filter:alpha(opacity=60); opacity:0.6;">
<table id='basic_table' style='margin:10px 0 0 0;'><tr>
	<td style="vertical-align:middle;">
		<fieldset id='basic_dir_fs' style='width:auto; margin-top:0; padding-top:0; visibility:$visibility;'><legend>Directory Conflicts</legend>
			<div class='p'><label>&nbsp;</label> </div>
			<div class='p'>
				<input style='z-index:10; position:relative;' id='basic_merge_folders' type='checkbox' name='basic_merge_folders' value='$conflictDirOption' onclick="
					if (this.checked)
						gebi('dir_OVERWRITE_EXISTING').click();
					else
						gebi('dir_KEEP_EXISTING').click();
					gebi('basic_file_fs').style.visibility = (this.checked ? null:null);
					return true;" />
				<label for='basic_merge_folders' >Merge directory</label>
			</div>
			<div class='p'><label>&nbsp;</label> </div>
		</fieldset>
	</td>
	<td>
		<fieldset id='basic_file_fs' style='width:auto; margin-top:0; padding-top:0; visibility:$visibility;'><legend>File Conflicts</legend>
			$fileConflictOptions
			<div style='clear:left;'></div>
		</fieldset>
	</td>
</tr></table>
</div>
EOD;
				











			}
			
			echo "<div style='clear:left;'></div>\n";
		}
		else
			$disableAdvanced = true;

			echo "</div>";
			ob_start();
			if (!$zwc)
				echo "<fieldset style='width:auto; clear:left;'><legend>Directory Conflicts</legend>";

			foreach($pm->restore['conflict_dir_options'] as $conflictDirOption)
			{
				$onclick = '';
				$text = ZMC_Type_AmandaApps::conflict2text($conflictDirOption, $pm->restore['destination_location'], false);

				$class = '';
				switch($conflictDirOption)
				{
					case ZMC_Type_AmandaApps::RENAME_RESTORED:
					case ZMC_Type_AmandaApps::RENAME_RESTORED_N:
					case ZMC_Type_AmandaApps::RENAME_EXISTING:
					case ZMC_Type_AmandaApps::REMOVE_EXISTING:
						$onclick = "gebi('disable_basic').style.visibility='visible'; gebi('basic_merge_folders').checked = false;";
						break;

					case ZMC_Type_AmandaApps::KEEP_EXISTING:
						$onclick = "gebi('basic_merge_folders').checked = false; if (gebi('file_KEEP_EXISTING').checked || gebi('file_RENAME_RESTORED_N').checked || gebi('file_OVERWRITE_EXISTING').checked) gebi('disable_basic').style.visibility='hidden'; ";
						break;

					case ZMC_Type_AmandaApps::OVERWRITE_EXISTING:
						$onclick = "gebi('basic_merge_folders').checked = true; if (gebi('file_KEEP_EXISTING').checked || gebi('file_RENAME_RESTORED_N').checked || gebi('file_OVERWRITE_EXISTING').checked) gebi('disable_basic').style.visibility='hidden'; ";
						break;
				}
				if ($conflictDirOption == ZMC_Type_AmandaApps::REMOVE_EXISTING)
					$class = 'class="zmcIconWarning"';
				elseif ($conflictDirOption == ZMC_Type_AmandaApps::OVERWRITE_EXISTING)
				{
					$class = 'class="zmcIconWarning"';
					$hidden = 'visible';
					$style = ($pm->restore['conflict_dir_selected'] == $conflictDirOption ? '' : 'style="visibility:hidden;"');
					$text .= "<div id='merge_explanation' $style>Note: Extended attributes of restored directory are not preserved.<br />\n";
					$onclick .= "gebi('merge_explanation').style.visibility = 'visible'; ";
					$text .= '</div>';
				}
				if (!empty($pm->selectedFiles))
					$hidden = 'visible';

				$checked = (($pm->restore['conflict_dir_selected'] == $conflictDirOption) || (count($pm->restore['conflict_dir_options']) === 1));
				if ($checked)
					$pm->clickDirOptOnLoad = "dir_$conflictDirOption";
				$div = "<div class='p'><input id='dir_$conflictDirOption' type='radio' name='conflict_dir_selected' value='$conflictDirOption' onclick=\"$onclick; return true;\""
				. ($checked ? 'checked="checked"' : '')
				. " />\n<label for='dir_$conflictDirOption' $class>$text</label>"
				. "<div style='clear:left;'></div>\n</div>";
				echo $div;
			}
			if (!$zwc)
			{
				echo "<div style='clear:left;'></div>\n";
				echo "</fieldset>\n";
				echo "<fieldset style='width:auto;'><legend>File Conflicts</legend>\n";
				echo conflict_file_helper($pm) . "</fieldset>\n";
			}

			$pm->form_advanced_html = ob_get_clean();
			$pm->form_type['advanced_form_classes'] = 'zmcAutoLabel zmcUltraShortInput';
			
			if (empty($disableAdvanced))
				ZMC_Loader::renderTemplate('formAdvanced', $pm);
			else
				echo $pm->form_advanced_html;
			$code = "gebi('" . $pm->clickDirOptOnLoad . "').click();\n";
			if (isset($pm->clickFileOptOnLoad))
				$code .= "gebi('" . $pm->clickFileOptOnLoad . "').click();\n";
			ZMC_HeaderFooter::$instance->injectYuiCode($code);

?>
	<div class="zmcButtonBar">
		<button type="submit" name="action" value="Apply Previous" class="zmcButtonsLeft" />Back</button>
		<button type="submit" name="action" value="Apply Next" />Next</button>
	</div>
</div>


</form>
