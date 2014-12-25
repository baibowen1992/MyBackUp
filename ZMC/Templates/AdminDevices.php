<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
if (!isset($pm->confirm_template))
{
	if (	($pm->state === 'Edit' || $pm->state === 'Create2' || $pm->state === 'Add' || $pm->state === 'Update')
		&&	isset($pm->form_type) && ($pm->form_type['license_group'] === 'changer' || ($pm->form_type['license_group'] === 'tape')))
			if (!isset($pm->binding) || $pm->binding['_key_name'] !== 'changer_ndmp')
				require 'ZMC/Templates/lsscsiWindow.php';
?>


<div id="deviceFormWrapper" class="zmcLeftWindow">
		<? 
		$deviceType = '';
		if ($pm->state !== 'Create1' && $pm->offsetExists('form_type'))
			$deviceType = ' : ' . ZMC::escape($pm->form_type['name']) . ' Device';
		else
			$deviceType = ' Storage Device';

		ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . $deviceType);
}

if ($pm->state === 'Create1')
{
?>
		<div class="zmcFormWrapper" style="min-height:90px;">
			<input type="hidden" name="action" value="Create2" />
<?
		foreach(ZMC_Type_Devices::get() as $deviceType => $keys)
			@$deviceGroups[$keys['human_group']][$deviceType] = $keys;

		foreach($deviceGroups as $human_group => $devices)
		{
			$display = array();
			foreach($devices as $deviceType => $keys)
			{
				$onclick = 'onclick="noBubble(event); ';
				$style = 'vertical-align:middle;';
				$pointer = '';
				$onclick .= ($deviceType === 'tape' ? 'if (window.confirm(\'Is a tape inserted into the tape drive?\')) ' : '')
					. "window.location.href='$pm[url]?_key_name=" . urlencode($deviceType) . "&amp;action=Create2'; return true;";
				$icon = ZMC_Type_Devices::getIcon($pm, $deviceType, $disabled, '', $style);
				if (empty($disabled))
				{
					$onclick .= ($deviceType === 'tape' ? 'if (window.confirm(\'Is a tape inserted into the tape drive?\')) ' : '')
						. "window.location.href='$pm[url]?_key_name=" . urlencode($deviceType) . "&amp;action=Create2'; return true;";
					$pointer = 'cursor:pointer;';
					$display[] = "<td><a style=\"$pointer; padding:5px;\" $onclick\">$icon$keys[name]\n</a>\n</td>";
				}
				elseif ($keys['device_type'] !== 'type_cloud')
					$display[] = "<td><a style=\"$pointer; padding:5px;\" href='/ZMC_License?license_group=$keys[license_group]'>$icon$keys[name]\n</a></td>\n";
			}
			if (empty($display))
				continue;
			echo "<fieldset><legend>$human_group</legend><table style='border-spacing:10px'>\n";
			$twoRows = (count($display) > 3);
			if($human_group == "Tape Storage")
				$display = array_reverse($display);
			while(!empty($display))
			{
				echo '<tr>', array_pop($display);
				if ($twoRows && !empty($display))
					echo array_pop($display);
				echo '</tr>';
			}

			echo "</table></fieldset>\n\n\n";
		}
?>
			<div style='clear:both;'></div>
		</div><!-- zmcFormWrapper -->
<?
} 
elseif (!empty($pm->form_html)) 
{
?>
		<div id="deviceFormWrapper" class="zmcFormWrapperRight <?= $pm->form_type['form_classes'] ?>">
			<img class="zmcWindowBackgroundimageRight" src="/images/3.1/<? echo ($pm->state === 'Edit' ? 'edit' : 'add'); ?>.png" />
			<? echo ZMC_Type_Devices::getIcon($pm, $pm->form_type, $disabled, '', 'position:absolute; right:10px;'), $pm->form_html; ?>
			<div style='clear:both;'></div>
		</div><!-- zmcFormWrapper -->
<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
?>

	<div class="zmcButtonBar">
		<input type="submit" name="action" value="<? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? 'Update' : 'Add'); ?>" />
		<? if ($pm->state === 'Edit')
			echo '<input id="addButton" type="submit" name="action" value="Add" disabled="disabled" />';
		?>
  		<input type="submit" value="Cancel" id="cancelButton" name="action"/>
	</div>
<?
}
?>
</div><!-- zmcLeftWindow -->
<div id="endZmcLeftWindow"></div>
<?

if (empty($pm->rows))
	return;
$only1user = (ZMC_User::count() === 1);
ZMC::titleHelpBar($pm, $pm->goto . 'View and edit backup set devices', '', 'zmcTitleBarTable');
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
				<th title='Used With'>
					<a href='<?= $pm->colUrls['private:used_with'] ?>'>Used With<? if ($pm->sortImageIdx == 'private:used_with') echo $pm->sortImageUrl; ?></a></th>
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
	$encName = urlencode($row['id']);
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo <<<EOD
		<tr style='cursor:pointer' class='$color' onclick="noBubble(event); window.location.href='$pm[url]?edit_id=$encName&amp;action=Edit'; return true;">

EOD;
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

			case 'private:used_with':
				echo "<td>$row[$key]</td>\n";
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

$buttons = array(
    'Refresh Table' => true,
    'Edit' => false,
    'Delete' => false,
    'List' => false,
    'Expert' => false,
);
if (ZMC_User::hasRole('Administrator'))
	$buttons['List'] = false;

if(!ZMC::$registry->debug)
	unset($buttons['List']);


$whereUrl = ZMC_HeaderFooter::$instance->getUrl('Backup', 'where');


if (count(ZMC_BackupSet::getMyNames()))
{
	$buttons['Use'] = "onClick=\"return YAHOO.zmc.utils.data_table_button_redirect('$whereUrl?action=Use&ConfigurationName=&selected_device=')\"";
	if (!empty($pm->use_with))
		$buttons['Use with: ' . $pm->use_with] = "onClick=\"return YAHOO.zmc.utils.data_table_button_redirect('$whereUrl?action=Use&ConfigurationName=$pm->use_with&selected_device=')\"";
}
ZMC_Loader::renderTemplate('tableButtonBar', array('goto' => $pm->goto, 'buttons' => $buttons));
