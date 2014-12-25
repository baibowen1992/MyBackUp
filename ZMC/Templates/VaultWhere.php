<?













global $pm;
echo "<form method='post' action='$pm->url'>";

if($pm->state === 'Config_Tape_Changer'){
?>
<div id="deviceFormWrapper" class="zmcLeftWindow">
<?	ZMC::titleHelpBar($pm, $pm->goto . 'Edit configuration for vault device', '', ''); ?>
	<div wrappercreate2="" class="zmcFormWrapper zmcLongInput zmcLongLabel">
		<div class="p" style="min-height:80px;">
			<label>Device Type:</label>
			<div class='zmcAfter'>
				<a style="display:block; border:solid blue 1px; padding:2px; margin:2px;" href="/ZMC_Admin_Devices?action=Edit&id=<? echo urlencode($pm->vault_job['vault_device']); ?>">
					<img title="Changer Library" style="" src="/images/icons/icon_changer_library.png">
				</a>
			</div>
			<label style="clear:left;">&nbsp;</label>
			<div class="zmcAfter">Changer Library</div>
		</div>

		<div class="p" id="private:zmc_device_name_disabled_div">
			<label for="private:zmc_device_name_disabled">Device Name<span class="required">*</span>:</label>
			<input id="private:zmc_device_name_disabled" type="text" name="private:zmc_device_name_disabled" disabled="disabled" title="User-friendly name for this ZMC device." value="<? echo $pm->vault_job['vault_device']; ?>">
		</div><!-- private:zmc_device_name_disabled_div -->
		
		<div class="p" id="autolabel_div">
			<label for="autolabel">Auto Label Tapes:</label>
			<input id="autolabel" name="autolabel" type="checkbox" value="on" title="">
			<select id="autolabel_how" class="zmcLongerInput" name="autolabel_how" title="Autolabel How?">
				<option selected="selected" value="empty">Only if empty (read returned 0 bytes)</option>
				<option value="empty non_amanda">Only if Amanda label not found</option>
				<option value="other_config">Only if already labelled for a different backup set</option>
				<option value="other_config non_amanda empty">Always</option>
			</select>
		</div>
		
		<div class="p" id="slotrange_div">
			<label for="slot_range" style="font-weight:bold;">Slot Range<span class="required">*</span>:</label>
			<input id="slot_range" type="text" name="slot_range" title="The slot range format 1-5,11-15,17,18,24-26, etc..." value="1-<? echo $pm->vault_job['max_slots']; ?>">
		</div><!-- changer:slotrange_div -->

		<div class="p" id="changer:changerdev_disabled_div">
			<label for="changer:changerdev_disabled">Changer Device<span class="required">*</span>:</label>
			<input id="changer:changerdev_disabled" type="text" name="changer:changerdev_disabled" disabled="disabled" title="" value="<? echo $pm->vault_job['changerdev']; ?>">
		</div><!-- changer:changerdev_disabled_div -->

		<div class="p" id="changer:tapedev_div">
			<label for="changer:tapedev" style="font-weight:bold;">Tape Drive Device<span class="required">*</span>:</label>
			<fieldset>
				<legend>Identify Drive Elements to Use</legend>
				<table width="300">
					<tbody>
						<tr>
							<th style="text-align:left;"><b>Skip?</b></th>
							<th style="text-align:left;"><b>Drive Slot</b></th>
							<th style="text-align:left;"><b>OS Drive Path</b></th>
						</tr>
<?
	foreach($pm->vault_job['tapedev_list'] as $slot => $drive){
?>
	<tr>
		<td>
			<input type="checkbox" onclick="var o = gebi('tape_drives[<? echo $drive; ?>]');if (this.checked) { o.value ='skip'; } else { o.value = <? echo $slot;?>}" style="float:none;">
		</td>
		<td>
			<input type="text" class="zmcUltraShortInput" id="tape_drives[<? echo $drive; ?>]" name="tape_drives[<? echo $drive; ?>]" value="<? echo $slot; ?>" style="float:none;">
		</td>
		<td>
			<? echo $drive; ?>
		</td>
</tr>
<?
	}
?>
					</tbody>
				</table>
			</fieldset>
		</div><!-- changer:tapedev_div -->
	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar">
		<input type="submit" name="action" value="Next">
		<input type="submit" value="Cancel" name="action">
	</div>
</div>
<?
} else {
	$backup_device = $pm->edit['profile_name'];
	foreach ($pm->rows as $name => $row) {
		if($backup_device === $row['id']) 
			unset($pm->rows[$name]);
		if($row['_key_name'] !== 'attached_storage' && $row['_key_name'] !== 's3_cloud' && $row['_key_name'] !== 'changer_library')
			unset($pm->rows[$name]);
	}

	if(empty($pm->rows)){
		$pm->addWarning("No devices available for vaulting.");
		ZMC_Loader::renderTemplate('MessageBox', $pm);
	} else {
	ZMC::titleHelpBar($pm, $pm->goto . 'Select where to vault', '', 'zmcTitleBarTable');
?>
	<div class="dataTable" id="dataTable">
		<table width="100%">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='Type'>
					<a href='<?= $pm->colUrls['_key_name'] ?>'>Type<? if ($pm->sortImageIdx == '_key_name') echo $pm->sortImageUrl; ?></a></th>
				<th title='Device Name' style='min-width:200px'>
					<a href='<?= $pm->colUrls['id'] ?>'>Device Name<? if ($pm->sortImageIdx == 'id') echo $pm->sortImageUrl; ?></a></th>
				<th title='Device Status' style='max-width:275px'>
					<a href='<?= $pm->colUrls['stderr'] ?>'>Status<? if ($pm->sortImageIdx == 'stderr') echo $pm->sortImageUrl; ?></a></th>
				<th title='Path'>
					<a href='<?= $pm->colUrls['changer:changerdev'] ?>'>Path<? if ($pm->sortImageIdx == 'changer:changerdev') echo $pm->sortImageUrl; ?></a></th>
				<th title='Comments'>
					<a href='<?= $pm->colUrls['changer:comment'] ?>'>Comments<? if ($pm->sortImageIdx == 'changer:comment') echo $pm->sortImageUrl; ?></a></th>
				<th title='Last modified time'>
					<a href='<?= $pm->colUrls['private:last_modified_time'] ?>'>Last Modified<? if ($pm->sortImageIdx == 'private:last_modified_time') echo $pm->sortImageUrl; ?></a></th>
				<? if (!$only1user) { ?>
				<th title='Last modified by'>
					<a href='<?= $pm->colUrls['private:last_modified_by'] ?>'>By<? if ($pm->sortImageIdx == 'private:last_modified_by') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
			</tr>
<?
	$i = 0;
	foreach ($pm->rows as $name => $row)
	{
	
		$color = (($i++ % 2) ? 'stripeGray':'');
		echo <<<EOD
			<tr style='cursor:pointer' class='$color'>

EOD;
		if(isset($pm->vault_job['vault_device'])){
			ZMC::$userRegistry['selected_ids'] = array($pm->vault_job['vault_device'] => '1');
		} else {
			ZMC::$userRegistry['selected_ids'] = array();
		}
		
		echo ZMC_Form::tableRowCheckBox($row['id']);
		foreach ($pm->cols as $index => $key)
		{
			$escaped = '';
			if (!is_string($index))
				$escaped = (isset($row[$key]) ? ZMC::escape($row[$key]) : '');
			elseif (isset($row[$key]) && isset($row[$key][$index]))
				$escaped = ZMC::escape($row[$key][$index]);
			$escapedTd = "<td>$escaped</td>\n";
	
			switch($key)
			{
				case 'uid':
					break;
	
				case 'stderr':
					if (empty($escaped))
						$escaped = 'OK';
					echo '<td style="max-width:275px;"><img style="vertical-align:text-top; padding:0; margin:0" src="/images/global/calendar/icon_calendar_', (empty($row[$key]) ? 'success' : 'failure'), ".gif\" /> $escaped</td>\n";
					break;
	
				case '_key_name':
					echo "<td>", ZMC_Type_Devices::getIcon($pm, $row[$key], $disabled, 'width=\'auto\' height=\'21\''), "</td>\n";
					break;
	
				case 'private:last_modified_by':
					if ($only1user) 
						break;
	
				default:
					echo $escapedTd;
			}
		}
		echo "</tr>\n";
	}
	echo "      </table>
		    </div><!-- dataTable -->\n\n";
	
	$pm->buttons = array(
			'Refresh Table' => true,
			'Cancel' => true,
			'Next' => false,
	);

	ZMC_Loader::renderTemplate('tableButtonBar', $pm);
	}
}
