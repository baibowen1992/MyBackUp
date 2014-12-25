<?













global $pm;
$tape = ($pm->binding['dev_meta']['media_type'] === 'tape');
echo "<form method='post' action='$pm->url'>";

if (!empty($pm->binding))
{
	$vtape = ($pm->binding['dev_meta']['media_type'] === 'vtape');
?>
	<div class="zmcLeftWindow" style="<?= ($tape ? 'max-width:510px;':'') ?>">
<?
		$div_attribs = 'style="border-top:1px solid #5c706e;"';
		if (empty($pm->tapeListPm) || empty($pm->tapeListPm->rows))
		{
			if ($tape)
				echo '<div class="zmcFormWrapperText">No Amanda labeled media has been used yet.</div>';
		}
		else
		{
			$pm->tapeListPm->tableTitle = 'Manage Vaulted Media for: ' . $pm->binding['config_name'];
			$pm->tapeListPm->tbody_height = '285';
			$pm->tapeListPm->tombstone = $pm->tombstone;
			$pm->tapeListPm->subnav = $pm->subnav;
			$pm->tapeListPm->url = $pm->url;
			$pm->tapeListPm->disable_onclick = true;
			$pm->tapeListPm->checkbox_qualifier = '_mm';
			$pm->tapeListPm->buttons = array(
				'Refresh Table' => true,
				
				'Recycle' => "onclick=\"return window.confirm('Recycling media will delete data on Disk and S3 devices.  Continue?')\"",
				'Drop' => (!$vtape || ZMC::$registry->allow_dropping_vtapes) ? "onclick=\"return window.confirm('Dropping media will delete data on Disk and S3 devices, but not tape devices.  Continue?')\"" : null,
				'Verify Integrity' => false,
				'Explore' => false,
				
			);
			$pm->tapeListPm->data_table_div_attribs = $div_attribs;
			$icon = ZMC_Type_Devices::getIcon($pm, $pm->binding, $disabled);
			$iconLink = '<a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '?' . 'action=Edit&amp;edit_id=' . urlencode($pm->binding['private']['zmc_device_name']) . "\">$icon</a>";
			$pm->tapeListPm->prepend_html = '';
			if (!$vtape)
				$pm->tapeListPm->prepend_html .= '
					<table>
					<tr>
							<td nowrap><b>Row Legend:&nbsp;&nbsp;</b></td>
							<td><ul style="font-weight:normal">
								<li>Part of <span style="background-color:#ccffcc;">current retention period</span>.</li>
								<li>Probably used <span class="stripeWhiteSkip">next</span>.</li>
								<li>Desired retention period <span class="stripeRed">violation</span> (unless new media provided).</li>
								<li><b>L0</b> media contains at least one DLE with a level 0 backup image.
							</ul></td>
						</tr>
						</table>';


			$pm->tapeListPm->no_form_close = true;
			ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm->tapeListPm);
		}
	?>
	</div><!-- zmcLeftWindow -->

	<?
	if (!empty($pm->labelListPm))
	{
		echo '<div class="zmcRightWindow">';
		$pm->labelListPm->disable_onclick = true;
		$pm->labelListPm->checkbox_qualifier = '_lm';
		$pm->labelListPm->data_table_div_attribs = $div_attribs;
		$pm->labelListPm->data_table_id = ($tableId = 'label_media_table');
		$pm->zmc_advanced_form_style = 'border:0;';
		if ($vtape)
		{
			$pm->labelListPm->tableTitle = 'Mounted Volumes for: ' . $pm->binding['config_name'];
			$pm->labelListPm->disable_button_bar = true;
		}
		elseif ($pm->binding['dev_meta']['media_type'] !== 'tape')
		{
			ZMC::titleHelpBar($pm, 'Unknown Media Type');
			echo "<div class='zmcFormWrapperText'><span class='zmcUserErrorsText'>Error: Unrecognized media type.</div>";
			echo "</div><!-- zmcRightWindow -->";
			return;
		}
		else
		{
			$pm->labelListPm->tableTitle = 'Label Media for: ' . $pm->binding['config_name'];
			
			$pm->labelListPm->buttons = array(
				'Save Labels' => ($pm->binding['autolabel'] === 'on' ? "onclick=\"return window.confirm('This device has auto-labelling enabled. Are you sure you want to manually label tapes?')\"" : 'false'),
			);
			$useBarcodes = '<input type="hidden" name="barcodes_enabled" value="1" />';
			if (empty($pm->binding) || !$pm->binding['has_barcode_reader'] || $pm->binding['changer']['ignore_barcodes'] === 'on')
				$useBarcodes = '';
			$pm->form_advanced_html = $useBarcodes . '
				<div style="float:right; min-height:50px; padding-bottom:5px;">
					<input type="submit" name="action" value="Scan All Slots" 
						onclick="return window.confirm(\'This may require a very long time to check every tape.  Continue?\')" />
					<br />';
			if (!empty($pm->labelling_in_progress))
				$pm->form_advanced_html .= '<input type="submit" name="action" value="Abort Labeling" style="margin:5px;" />';
			$pm->form_advanced_html .= '
				</div>
';
			
			if (!empty($pm->binding['changer']) && $pm->binding['changer']['ignore_barcodes'] !== 'on')
				$pm->form_advanced_html .= '
				<div class="p"><label>Include Barcode:</label><input type="checkbox" name="include_barcode" checked="checked" onclick="if (this.checked) return true; return window.confirm(\'Without barcodes in the Amanda media label, finding media using barcodes will be difficult. Are you sure?\')" /><div style="clear:left;"></div></div>
';
			$pm->form_advanced_html .= <<<EOD
				<div class="p">
					<label for="overwrite_media_box">Overwrite Media:</label>
					<input type="checkbox" id="overwrite_media_box" name="overwrite_media" onclick="
						var result = true;
						if (this.checked)
							result = window.confirm('Overwriting a media label prevents restoring the contents of the media. Are you sure?');

						if (!result)
							return false;

						var o = gebi('$tableId').getElementsByTagName('input');
						for(var i = 0; i < o.length; i++)
							if (o[i].hasAttribute('hasLabel') && this.checked === false)
							{
								o[i].disabled = 'disabled';
								o[i].style.visibility = 'hidden';
							}
							else
							{
								o[i].disabled = '';
								o[i].style.visibility = 'visible';
							}

						return true;
						" />
					<div style="clear:left;"></div>
				</div>
EOD;
			$info = '
            <p>If a barcode reader was set for the device (Admin|Devices) and enabled (Backup|Where) for this backup set, the barcode will be automatically appended to labels (recommended).</p>
            <br />
            <p>Overwriting media is dangerous and can not be undone. Use with caution.</p>
            <br />
            <p>If labeling has not completed in a long time, press the "Clear" button, check the physical drive and tape status, and try again.</p>
            <br />
            <p><a href="<? echo ZMC::$registry->wiki ?>Backup_Media#Editing_the_Tape_Label_Prefix_and_starting_number" target="_blank">Implications of these options are discussed in more detail here</a>.</p>';
			$pm->form_type = array('advanced_form_classes' => 'zmcShortLabel zmcShortInput');
			ob_start();
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
			$pm->labelListPm->prepend_html = ob_get_clean();
		}
		$pm->labelListPm->no_form_close = true;
		ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm->labelListPm);
		echo "</div><!-- zmcRightWindow -->";
	}
}
