<?













global $pm;
$action = rtrim($pm->state, '012');
echo "\n<form method='post' action='$pm->url'>\n";

$only1user = (ZMC_User::count() === 1);
ZMC::titleHelpBar($pm, $pm->goto . "List of objects (DLEs) to backup with: " . $pm->selected_name, 'DLE+Table', 'zmcTitleBarTable');
?>
	<input id="backup_how" type="hidden" value="<?= $pm->backup_how ?>" name="backup_how">
	<div class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='Type'>
					<a href='<?= $pm->colUrls['property_list:zmc_type'] ?>'>Type<? if ($pm->sortImageIdx == 'property_list:zmc_type') echo $pm->sortImageUrl; ?></a></th>
				<? if (!empty($pm->aliases))
						echo "<th title='Alias (defaults to directory/path)'><a href='{$pm->colUrls['disk_name']}'>Alias",
							($pm->sortImageIdx == 'disk_name' ? $pm->sortImageUrl : ''), "</a></th>\n";
					if (!empty($pm->comments))
						echo "<th title='Comments'><a href='{$pm->colUrls['property_list:zmc_comments']}'>Comments",
							($pm->sortImageIdx == 'property_list:zmc_comments' ? $pm->sortImageUrl : ''), "</a></th>\n";
				?>
				<th title='Host Name / DLE Check Status' style='min-width:200px'>
					<a href='<?= $pm->colUrls['host_name'] ?>'>Host Name / DLE Check Status<? if ($pm->sortImageIdx == 'host_name') echo $pm->sortImageUrl; ?></a></th>
				<th title='Directory/Path'>
					<a href='<?= $pm->colUrls['disk_device'] ?>'>Directory/Path<? if ($pm->sortImageIdx == 'disk_device') echo $pm->sortImageUrl; ?></a></th>
				<? if (!empty($pm->templates))
						echo "<th title='Template Name'><a href='{$pm->colUrls['property_list:zmc_dle_template']}'>Template",
							($pm->sortImageIdx == 'property_list:zmc_dle_template' ? $pm->sortImageUrl : ''), "</a></th>\n";
				?>
				<th title='# L0 Backup Images'>
					<a href='<?= $pm->colUrls['L0'] ?>'># L0<? if ($pm->sortImageIdx == 'L0') echo $pm->sortImageUrl; ?></th>
				<th title='# L1+ Backup Images'>
					<a href='<?= $pm->colUrls['Ln'] ?>'># L1+<? if ($pm->sortImageIdx == 'Ln') echo $pm->sortImageUrl; ?></a></th>
				<th title='AE Client Version'>
					<a href='<?= $pm->colUrls['property_list:zmc_amcheck_version'] ?>'>AE Version<? if ($pm->sortImageIdx == 'property_list:zmc_amcheck_version') echo $pm->sortImageUrl; ?></a></th>
				<th title='Client OS'>
					<a href='<?= $pm->colUrls['property_list:zmc_amcheck_platform'] ?>'>OS<? if ($pm->sortImageIdx == 'property_list:zmc_amcheck_platform') echo $pm->sortImageUrl; ?></a></th>
				<th title='Encryption Mode'>
					<a href='<?= $pm->colUrls['encrypt'] ?>'>Encrypt<? if ($pm->sortImageIdx == 'encrypt') echo $pm->sortImageUrl; ?></a></th>
				<th title='Compression Mode'>
					<a href='<?= $pm->colUrls['compress'] ?>'>Compress<? if ($pm->sortImageIdx == 'compress') echo $pm->sortImageUrl; ?></a></th>
				<th title='Last modified time'>
					<a href='<?= $pm->colUrls['property_list:last_modified_time'] ?>'>Last Modified<? if ($pm->sortImageIdx == 'property_list:last_modified_time') echo $pm->sortImageUrl; ?></a></th>
				<? if (!$only1user) { ?>
				<th title='Last modified by'>
					<a href='<?= $pm->colUrls['property_list:last_modified_by'] ?>'>By<? if ($pm->sortImageIdx == 'property_list:last_modified_by') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
			</tr>
<?
$poll = $i = 0;
foreach ($pm->rows as $row)
{
	if (!empty($row['zmc_status']) && $row['zmc_status'] === 'deleted')
	{
		$deleted = true;
		$color = (($i++ % 2) ? 'stripeGrayDeleted':'stripeDeleted');
		echo "<tr class='$color' onclick=\"window.confirm('Deleted DLEs can not be edited, and are removed when associated media have been dropped on the Backup|media page.'); return false;\">\n";
	}
	else
	{
		if (!empty($row['strategy']) && ($row['strategy'] === 'skip'))
		{
			$skipped = true;
			$color = (($i++ % 2) ? 'stripeGraySkip':'stripeWhiteSkip');
		}
		else
			$color = (($i++ % 2) ? 'stripeGray':'');
		echo "<tr style='cursor:pointer' class='$color' onclick=\"noBubble(event); window.location.href = '$pm[url]?edit_id=" . urlencode($row['natural_key']) . "&amp;action=Edit'; return true;\">\n";
	}
	echo ZMC_Form::tableRowCheckBox($row['natural_key']);

	foreach ($pm->columns as $key)
	{
		$escaped = (isset($row[$key]) ? ZMC::escape($row[$key]) : '');
		$escapedTd = "<td>$escaped</td>\n";

		switch($key)
		{
			case 'natural_key':
			case 'property_list:zmc_amcheck':
			case 'property_list:zmc_amcheck_date':
			case 'property_list:zmc_status':
			case 'strategy':
			case 'uid':
				break;

			case 'property_list:zmc_amcheck_version':
				echo "<td>$escaped", (empty($row['property_list:zmc_amcheck_app']) ? '':'/'.$row['property_list:zmc_amcheck_app']), "</td>\n";
				break;

			case 'property_list:zmc_disklist':
				break;
				$displayName = ZMC_BackupSet::displayName($row[$key]);
		        if (ZMC::$registry->advanced_disklists)
					echo '<td><a onclick="noBubble(event)" href="', ZMC_HeaderFooter::$instance->getUrl('Backup', 'list'),
						'?id=', urlencode($row[$key]), "\">$displayName</a></td>\n";
				else
					echo "<td>$displayName</td>\n";
				break;

			case 'property_list:last_modified_time':
				if ($escaped === '')
					echo "<td>-</td>\n";
				else
					echo '<td>', ZMC::escape(substr($row[$key], 0, -3)), "</td>\n";
				break;

			case 'disk_name':
				if (empty($pm->aliases))
					break;
				if ($row[$key] === $row['disk_device'])
					echo "<td>-</td>\n"; 
				else
					echo $escapedTd;
				break;

			case 'property_list:zmc_type':
				echo '<td>';
				if (isset($pm->lstats['over_limit'][$row[$key]]))
					echo '<img style="vertical-align:text-top; padding:0; margin:0" src="/images/global/calendar/icon_calendar_failure.gif" title="License limit exceeded. DLE disabled." />';
				echo ZMC_Type_What::getName($row[$key]), "</td>\n";
				break;

			case 'creation_date':
				echo "<td>", ZMC::escape(substr($row[$key], 0, -9)), "</td>\n";
				break;

			case 'live':
				echo "<td>", $row[$key] ? 'Yes' : 'No', "</td>\n";
				break;

			case 'property_list:zmc_comments':
				if (!empty($pm->comments))
					echo "<td>", ZMC::moreExpand(ZMC::escape($row[$key]), 20, '&gt;&gt;'), "</td>\n";
				break;

			case 'host_name':
				$escaped = '<a onclick="noBubble(event)" href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=amadmin+' . ZMC::escape($pm->selected_name) . '+find+' . $row[$key] . "\">$escaped</a>";
				$escapedTd = "<td>$escaped</td>\n";
				$last_modified_time = -1;
				if (!empty($row['property_list:last_modified_time']))
					$last_modified_time = ZMC::mktime($row['property_list:last_modified_time']);

				$zmc_amcheck_date = 0;
				if (!empty($row['property_list:zmc_amcheck_date']))
					$zmc_amcheck_date = ZMC::mktime($row['property_list:zmc_amcheck_date']);

				if (!empty($row['property_list:zmc_amcheck']) && !strncmp($row['property_list:zmc_amcheck'], 'checking', 8))
				{
					$poll++;
					echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_progress.gif' /> $escaped</td>\n";
					break;
				}

				if ($last_modified_time > $zmc_amcheck_date)
				{
					echo $escapedTd;
					break;
				}

				if (!empty($row['property_list:zmc_amcheck']))
				{
					$err = null;
					$icon = 'warning';
					
					if (false !== strpos($row['property_list:zmc_amcheck'], 'selfcheck request failed: Connection refused'))
					{
						$err = 'client refused connection';
						$icon = 'failure';
					}
					elseif (false !== strpos($row['property_list:zmc_amcheck'], 'resolve_hostname'))
					{
						if (false !== strpos($row['property_list:zmc_amcheck'], 'Name or service not known'))
							$err = 'client hostname not found';
					}
					elseif (false !== strpos($row['property_list:zmc_amcheck'], 'can not stat'))
					{
						$err = ZMC::escape("location not found: $row[disk_device]");
						$row['property_list:zmc_amcheck'] .= ' 1 problem found'; 
						$icon = 'failure';
					}

					if (empty($err) && strpos($row['property_list:zmc_amcheck'], ' 0 problems found'))
						echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_success.gif' /> $escaped</td>\n";
					elseif (empty($err) || false === strpos($row['property_list:zmc_amcheck'], ' 1 problem')) 
						echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_failure.gif' /> $escaped</td>\n";
					else
					{
						echo "<td
						onMouseOut=\"if (!(this.saveHTML === undefined) && !(this.saveState === undefined)) { delete this.saveState; this.innerHTML = this.saveHTML }\"
						onMouseOver=\"if (this.saveHTML === undefined) this.saveHTML = this.innerHTML;
						   	if (this.saveState === undefined)
							{ this.saveState = 1; this.innerHTML='<img style=vertical-align:text-top;padding:0;margin:0 src=/images/global/calendar/icon_calendar_$icon.gif /><span class=stripeYellow>&nbsp;$err&nbsp;</span>'; } \"
						><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_$icon.gif' /> $escaped</td>\n";
					}
				}
				elseif ($zmc_amcheck_date >= $last_modified_time)
					echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_success.gif' /> $escaped</td>\n";
				else
					throw new ZMC_Exception('column Host Name malformed');
				break;

			case 'property_list:last_modified_by':
				if (!$only1user) 
					echo $escaped === '' ? "<td>-</td>\n" : $escapedTd;
				break;

			case 'disk_device':
				echo '<td>', str_replace('/', '<wbr/>/', ZMC::escape($row[$key])), '</td>';
				break;

			case 'property_list:zmc_dle_template':
				if (empty($pm->templates))
					break;
				if (empty($row[$key]))
				{
					echo "<td>-</td>\n";
					break;
				}
			default:
				echo $escapedTd;
		}
	}
	echo "</tr>\n";
}
echo "      </table>
	    </div><!-- dataTable -->\n\n";

if (!empty($poll) && $pm->state === 'Create1')
	echo '<script>setTimeout(function () { window.location.replace(window.location.pathname) }, 5000)</script>';

$html = '';
if (!empty($deleted) || !empty($skipped))
{
	$html = '<div style="padding:4px; float:right;"><b>&nbsp;&nbsp;&nbsp;Row Legend:</b> ';
	if (!empty($deleted))
		$html .= '<span class="stripeWhite" style="text-decoration: line-through">Deleted</span>';

	if (!empty($skipped))
		$html .= '<span class="stripeGraySkip">Skipped</span>';

	$html .= "</div>\n";
}

ZMC_Loader::renderTemplate('tableButtonBar', array('goto' => $pm->goto,
	'buttons' => array(
		'Cancel' => true,
		'Start Backup Now' => false,
	),
	'html' => $html
));

echo "\n</form>\n";
