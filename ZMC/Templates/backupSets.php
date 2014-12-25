<?













global $pm;
if (empty($pm->rows))
	return print("<div style='height:250px;'>&nbsp;</div>\n");

$showDlesFailedLicense = false;
foreach($pm->rows as $row);
	if ($row['dles_failed_license'] > 0)
		$showDlesFailedLicense = true;

$only1user = (ZMC_User::count() === 1);
ZMC::titleHelpBar($pm, (empty($pm->tableTitle) ? $pm->tableTitle = 'Select a backup set' : $pm->tableTitle)
	. $pm->goto, $pm->tableTitle, 'zmcTitleBarTable');

?>
	<div class="dataTable">
		<table class="maxCol200" width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='Backup Set Activate?' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['active'] ?>'>Active<? if ($pm->sortImageIdx == 'active') echo $pm->sortImageUrl; ?></a></th>
				<th title='Backup Set Name' style="min-width:150px"><a href='<?= $pm->colUrls['configuration_name'] ?>'>Backup Set Name<? if ($pm->sortImageIdx == 'configuration_name') echo $pm->sortImageUrl; ?></a></th>
				<th title='Backup Status' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['last_amdump_result'] ?>'>Backup<? if ($pm->sortImageIdx == 'last_amdump_result') echo $pm->sortImageUrl; ?></a></th>
				<th title='Last Backup'><a href='<?= $pm->colUrls['last_amdump_date'] ?>'>Last Backup<? if ($pm->sortImageIdx == 'last_amdump_date') echo $pm->sortImageUrl; ?></a></th>
				<th title='Activated'><a href='<?= $pm->colUrls['schedule_type'] ?>'>Schedule<? if ($pm->sortImageIdx == 'schedule_type') echo $pm->sortImageUrl; ?></a></th>
				<th title='Error Code' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['code'] ?>'>Code<? if ($pm->sortImageIdx == 'code') echo $pm->sortImageUrl; ?></a></th>
				<th title='Total DLEs' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['dles_total'] ?>'><img src='/images/global/calendar/icon_calendar_success.gif' /><? if ($pm->sortImageIdx == 'dles_total') echo $pm->sortImageUrl; ?></a></th>
				<th title='Total DLEs failing Backup|What => Check Hosts' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['dles_failed_amcheck'] ?>'><img src='/images/global/calendar/icon_calendar_failure.gif' /><? if ($pm->sortImageIdx == 'dles_failed_amcheck') echo $pm->sortImageUrl; ?></a></th>
				<? if ($showDlesFailedLicense) { ?>
				<th title='Total DLEs failing license check' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['dles_failed_license'] ?>'><img src='/images/icons/icon_key_red.png' /><? if ($pm->sortImageIdx == 'dles_failed_license') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
				<th title='AEE Version' class='zmcCenterNoLeftPad'><a href='<?= $pm->colUrls['version'] ?>'>Version<? if ($pm->sortImageIdx == 'version') echo $pm->sortImageUrl; ?></a></th>
				<th title='Health Status'><a href='<?= $pm->colUrls['status'] ?>'>Health<? if ($pm->sortImageIdx == 'status') echo $pm->sortImageUrl; ?></a></th>
				<th title='Device Name'><a href='<?= $pm->colUrls['profile_name'] ?>'>Device<? if ($pm->sortImageIdx == 'device') echo $pm->sortImageUrl; ?></a></th>
				<th title='Creation Date' class='zmcCenterNoLeftPad' style="min-width:75px"><a href='<?= $pm->colUrls['creation_date'] ?>'>Creation&nbsp;Date<? if ($pm->sortImageIdx == 'creation_date') echo $pm->sortImageUrl; ?></a></th>
				<? if (ZMC_User::hasRole('Administrator') && !$only1user) { ?>
				<th title='Owner'><a href='<?= $pm->colUrls['user'] ?>'>Owner<? if ($pm->sortImageIdx == 'user') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
				<th title='Comments and Notes'>
					<a href='<?= $pm->colUrls['configuration_notes'] ?>'>Comments<? if ($pm->sortImageIdx == 'configuration_notes') echo $pm->sortImageUrl; ?></a></th>
			</tr>
<?
$i = 0;
foreach ($pm->rows as $row)
{
	$encName = urlencode($row['configuration_name']);
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo <<<EOD
		<tr style='cursor:pointer' class='$color' onclick="noBubble(event); window.location.href = '$pm[url]?edit_id=$encName&amp;action=Edit'; return true;">

EOD;
	echo ZMC_Form::tableRowCheckBox($row['configuration_name']);
	foreach ($pm->columns as $ignored => $key)
	{
		if ($key === 'configuration_name')
		{
			$name = ZMC_BackupSet::displayName($row[$key]);
			echo '<td>', $name, "</td>\n";
			continue;
		}

		switch($key)
		{
			case 'last_amdump_date':
				if (empty($row[$key]))
					echo '<td>never</td>';
				else
					echo '<td>', $row[$key], '</td>';
				break;

			case 'last_amdump_result':
				if (empty($row[$key]))
					echo '<td class="zmcIconWarning" style="background-position:center center"></td>';
				elseif ($row[$key] === 'OK')
					echo '<td class="zmcIconSuccess" style="background-position:center center"></td>';
				else
					echo '<td><p class="zmcUserErrorsText zmcIconError">', ZMC::escape($row[$key]), '</p></td>';

				break;

			case 'code':
				echo '<td class="zmcCenterNoLeftPad">';
				if ($row[$key])
					echo '<a onclick="noBubble(event)" href="', ZMC::$registry->wiki, 'Backup_Set_Error_Messages#code', $row[$key], '" target="_blank">', $row[$key];
				else
					echo '-';
				echo '</td>', "\n";
				break;

			case 'device':
				echo '<td class="zmcCenterNoLeftPad">';
				if(empty($row[$key])){
					echo '<a onclick="noBubble(event);" href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'where') . '?action=Edit&amp;edit_id=' . $row['configuration_name'] . '">NONE</a>';
				} else {
					$devices = explode(', ', $row[$key]);
					$set = ZMC_BackupSet::getByName($row['configuration_name']);
					foreach($devices as $device){
						if($device === $set['profile_name'] || $device === 'NONE'){
							echo '<a onclick="noBubble(event);" href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'where') . '?action=Edit&amp;edit_id=' . $row['configuration_name'] . '">' . $device . '</a>';
						} else {
							echo ', ' . $device;
						}
					}
				}
				echo '</td>', "\n";
				break;

			case 'status':
				echo "<td style='font-weight:normal'><span class='zmcUser", ($row['code'] ? 'Errors' : 'Messages'), "Text'>", (isset($row[$key]) ? ZMC::moreExpand($row[$key]) : ''), '</span>';
				if (!empty($row['migration_details']))
				{
					if ($row['migration_details'][1] != ':')
						$array = array('errors' => $row['migration_details']);
					else
						$array = unserialize($row['migration_details']); 
					$migrationPm = new ZMC_Registry_MessageBox($array);
					$migrationPm->caption = 'Migration Results:';
					ob_start();
					ZMC_Loader::renderTemplate('MessageBox', $migrationPm);
					echo ZMC::moreExpandLinkOnly('', str_replace('float:', 'width:260px; float:', ob_get_clean()), 'Migration Details ..');
				}
				echo "</td>\n";
				break;

			case 'configuration_notes':
				echo '<td>', ZMC::moreExpand($row[$key]), "</td>\n";

			case 'migration_details':
				break;

			case 'creation_date':
				echo "<td class='zmcCenterNoLeftPad'>", substr($row[$key], 0, -9), "</td>\n";
				break;

			case 'restore_running':
			case 'backup_running':
				break;

			case 'active':
				$row[$key] = ZMC_BackupSet::getStatusIconHtml($row['configuration_name'], $encName);
			case 'dles_total':
			case 'dles_failed_amcheck':
			case 'version':
				echo "<td class='zmcCenterNoLeftPad'>$row[$key]</td>\n";
				break;

			case 'dles_failed_license':
				if ($showDlesFailedLicense)
					echo "<td class='zmcCenterNoLeftPad'>$row[$key]</td>\n";
				break;

			case 'user':
				if ($only1user) 
					break;


			default:
				echo "<td>$row[$key]</td>\n";
		}
	}
	echo "</tr>\n";
}
echo "		</table>
	</div><!-- dataTable -->\n\n";

$pm->buttons['Disklist'] = false;
ZMC_Loader::renderTemplate('tableButtonBar', $pm);
