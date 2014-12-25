<?













global $pm;
$tape = ($pm->binding['dev_meta']['media_type'] === 'tape');
echo "<form method='post' action='$pm->url'>";

if (!empty($pm->binding))
{
	$vtape = ($pm->binding['dev_meta']['media_type'] === 'vtape');
?>
<!--	<div class="wocloudLeftWindow" style="<?= ($tape ? 'max-width:510px;':'') ?>">
<?
		$div_attribs = 'style="border-top:1px solid #5c706e;"';
		if (empty($pm->tapeListPm))
		{
			if ($tape)
				echo '<div class="wocloudFormWrapperText">还没有经过确认的存储设备被使用</div>';
		}
		else
		{
			$pm->tapeListPm->tableTitle = '管理存储设备: ' . $pm->binding['config_name'];
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
				'Archive' => false,
				'Verify Integrity' => false,
				'Explore' => false,
				'Prune' => $vtape ? true : null,
			);
			if(empty($pm->tapeListPm->rows)){
				unset($pm->tapeListPm->buttons['Drop']);
				unset($pm->tapeListPm->buttons['Recycle']);
				unset($pm->tapeListPm->buttons['Archive']);
				unset($pm->tapeListPm->buttons['Explore']);
				unset($pm->tapeListPm->buttons['Verify Integrity']);
				unset($pm->tapeListPm->buttons['Prune']);
			}
			$pm->tapeListPm->data_table_div_attribs = $div_attribs;
			$icon = ZMC_Type_Devices::getIcon($pm, $pm->binding, $disabled);
			$iconLink = '<a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '?' . 'action=Edit&amp;edit_id=' . urlencode($pm->binding['private']['zmc_device_name']) . "\">$icon</a>";
			$pm->tapeListPm->prepend_html = '
				<div class="wocloudFormWrapper wocloudUltraShortInput" style="min-height:25px;">
					<div style="float:right; padding:2px; margin:2px; border:solid blue 1px;">'.$iconLink.'</div>';
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
			else
			{
				$dc = intval($pm->conf['dumpcycle']);
				$ir = $pm->edit['initial_retention'];
				$pm->tapeListPm->prepend_html .= '<div class="wocloudFormWrapperText" style="float:right; margin:0 10px 0 0; min-height:30px;"><table class="dataTable infoTable">';
				$cells = array(
					array('Total', '', $pm->size_total ." ". ZMC::$registry->units['storage'][strtolower($pm->conf['displayunit'])]),
					array('Total Expired', 'background-color:#fcc;', $pm->size_expired ." ". ZMC::$registry->units['storage'][strtolower($pm->conf['displayunit'])]),
					array('Total Unexpired', '', $pm->size_unexpired ." ". ZMC::$registry->units['storage'][strtolower($pm->conf['displayunit'])]),
					array('Expired & not Prunable', 'background-color:#ff9;', $pm->size_expired_but_retained ." ". ZMC::$registry->units['storage'][strtolower($pm->conf['displayunit'])]),

					array('Total/Partition', '', empty($pm->binding['media']['partition_total_space']) ? 'NA' : (round($pm->size_total / $pm->binding['media']['partition_total_space'], 3) * 100) . ' %'),
					array('Expired/Total', '', empty($pm->size_total) ? 'NA' : round($pm->size_expired / $pm->size_total *100, 1) . ' %'),
					array('Expired/Partition', '', empty($pm->binding['media']['partition_total_space']) ? 'NA' : (round($pm->size_expired / $pm->binding['media']['partition_total_space'], 3) * 100) . ' %'),
					array('EP / Unexpired', '', empty($pm->size_unexpired) ? '0' : (round($pm->size_expired_but_retained / $pm->size_unexpired, 3) *100) . ' %'),
				);
				$half = count($cells)/2;
				for($row = 0; $row < $half; $row++)
				{
					$pm->tapeListPm->prepend_html .= "<tr>";
					for($col = 0; $col <= $half; $col += $half)
					{
						$labelContent = $cells[$row + $col];
						$pm->tapeListPm->prepend_html .= "<td style='border-right:0;$labelContent[1]'>$labelContent[0]:</td><td style='border-left:0; text-align:right;'>$labelContent[2]</td>";
					}
					$pm->tapeListPm->prepend_html .= "</tr>";
				}
				$dc_err = $irp_err = (($dc > $ir) ? 'visible':'hidden');
				$pm->tapeListPm->prepend_html .= <<<EOD
				</table>
			</div>
        <div class="p">
                          <label><b>备份周期<span class="required">*</span>:</b></label>
                          <input id="dumpcycle" type="text" name="dumpcycle" title="备份周期" value="$dc" onkeyup="
				var d=parseInt(gebi('dumpcycle').value);
				var irp=parseInt(gebi('initial_retention').value);
				if (!(irp >= d))
				{
					gebi('dc_err').style.visibility='visible';
				}
				else
				{
					gebi('irp_err').style.visibility='hidden';
					gebi('dc_err').style.visibility='hidden';
					gebi('dc').innerHTML=this.value;
				}
				"> <div class="wocloudAfter">天</div> <div class="wocloudAfter wocloudUserWarningsText wocloudIconError" id='dc_err' style='visibility:$dc_err;'>&nbsp;&nbsp;备份周期必须不大于备份保留时间</div>
            <label style='clear:left;'>&nbsp;</label>
			<div style="float:left;">每一个备份项每<b><span id='dc'>$dc</span></b> 天至少要有一次全备份</div>
		</div>
        <div class="p">
			<label><b>保留时间<span class="required">*</span>:</b></label>
			<input id="initial_retention" type="text" name="initial_retention" title="初始保留周期" value="$ir" onkeyup="
				var d=parseInt(gebi('dumpcycle').value);
				var irp=parseInt(gebi('initial_retention').value);
				if (!(irp >= d))
				{
					gebi('irp_err').style.visibility='visible';
				}
				else
				{
					gebi('irp_err').style.visibility='hidden';
					gebi('dc_err').style.visibility='hidden';
					gebi('irp').innerHTML=this.value;
				}
				" /> <div class="wocloudAfter">days</div> <div class="wocloudAfter wocloudUserWarningsText wocloudIconError" id='irp_err' style='visibility:$irp_err;'>&nbsp;&nbsp;保留周期必须不小于备份周期</div>
            <label style='clear:left;'>&nbsp;</label>
			<div style="float:left;"><input type="submit" name="action" value="Update" /></div>
            <label style='clear:left;'>&nbsp;</label>
            <label style='clear:left;'>&nbsp;</label>
			<div style="float:left;">你的备份数据将会保留 <b><span id='irp'>$ir</span></b> 天</div>
		</div>
EOD;
				if (ZMC::$registry->dev_only)
				{
					$rnf = empty($pm->binding['retain_n_fulls']) ? 0:$pm->binding['retain_n_fulls'];
					$father = empty($pm->binding['father_retention']) ? 0:$pm->binding['father_retention'];
					$grand = empty($pm->binding['grandfather_retention']) ? 0:$pm->binding['grandfather_retention'];
					$pm->tapeListPm->prepend_html .= <<<EOD
		<fieldset style="clear:both;"><legend>Optional Additional Retention Requirements</legend>
			<div class="p">
				<label>Keep at Least:</label>
				<input id="retain_n_fulls" type="text" name="retain_n_fulls" title="Retain N Full Backups" value="$rnf" /> <div class="wocloudAfter">Full Backup Images</div>
			</div>
			<div class="p">
				<label>Father Period:</label>
				<input id="father_retention" type="text" name="father_retention" title="Retain 1 Full Backup Every N Days" value="$father" /> <div class="wocloudAfter">days</div>
			</div>
			<div class="p">
				<label>Grandfather Period:</label>
				<input id="grandfather_retention" type="text" name="grandfather_retention" title="Retain 1 Full Backup Every N Days" value="$grand" /> <div class="wocloudAfter">days</div>
			</div>
		</fieldset>
EOD;
				}
			}
			$pm->tapeListPm->prepend_html .= '
					<div style="clear:both;"></div>
				</div>';
			$pm->tapeListPm->no_form_close = true;
			ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm->tapeListPm);
		}
	?>
	</div><!-- wocloudLeftWindow -->

	<?
	if (!empty($pm->labelListPm))
	{
		echo '<div class="wocloudRightWindow">';
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
			echo "<div class='wocloudFormWrapperText'><span class='wocloudUserErrorsText'>Error: Unrecognized media type.</div>";
			echo "</div><!-- wocloudRightWindow -->";
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
								var label = gebi((o[i].name.toString()).replace('selected_ids_lm', 'label'));
								if(label){
									o[i].disabled = '';
									o[i].style.visibility = 'visible';
								}
								else
								{
									o[i].disabled = 'disabled';
									o[i].style.visibility = 'hidden';
								}
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
			$pm->form_type = array('advanced_form_classes' => 'wocloudShortLabel wocloudShortInput');
			ob_start();
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
			$pm->labelListPm->prepend_html = ob_get_clean();
		}
		$pm->labelListPm->no_form_close = true;
		ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm->labelListPm);
		echo "</div><!-- wocloudRightWindow -->";
	}
}

$pm->tableTitle = '查看备份集中数据对存储设备的使用';
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
