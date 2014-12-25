<?













global $pm;
$action = rtrim($pm->state, '012');
echo "\n<form method='post' action='$pm->url'>\n";
?>
<div class="zmcWindow">
    <div class="zmcTitleBar">
	<?
		$objectType = isset($pm->form_type) ? ' ' . $pm->form_type['name'] : '';
		$preposition = 'in';
		if ($action === 'Copy')
			$preposition = 'to';
		if (isset($pm->form_type))
			echo "$action Object ", ZMC::escape($objectType), " (", ZMC::escape($pm->form_type['category']), ") $preposition list: ", ZMC::escape($pm->selected_name);
		if ($pm->state === 'Create1')
			echo 'Add';
		elseif ($pm->offsetExists('form_type'))
			echo '<div style="float:right; margin-right:83px;"><small>Licensing: <a href="',
				ZMC_HeaderFooter::$instance->getUrl('Admin', 'licenses') ,
				'">', $pm->licensesRemaining, "</a></small></div>\n";
		?>
	</div>
	<a class="zmcHelpLink" id="zmcHelpLinkId" href="<? echo ZMC::$registry->wiki, $pm->tombstone, '+', ucFirst($pm->subnav), '#', $action, urlencode($objectType) ?>" target="_blank"></a>
<?
if ($pm->state === 'Create1')
{
	?>
	<div wrapperCreate1 class="zmcFormWrapper" style="padding:20px 0 20px 200px; width:auto; border-top:0px;">
		<img class="zmcWindowBackgroundimageRight"src="/images/3.1/add.png" />
		<input type="hidden" name="action" value="Create2" />
	<?
	$i=0;
	$prettyNames2types = ZMC_Type_What::getPrettyNames();
	$zmcTypeApps = ZMC_Type_What::get();
	foreach(array('File Systems', 'Databases', 'Applications') as $category)
	{
		$i++;
		echo <<<EOD

		<select name="selection$i" style="margin-right:20px" onchange="if (this.value != '') this.form.submit();">
			<option value=''>$category...</option>
EOD;
		$options = array();
		foreach($zmcTypeApps as $zmcType => $info)
			if ($info['category'] === $category)
				$options[$prettyNames2types[$zmcType]] = $zmcType;

		ksort($options);
		foreach($options as $name => $zmcType)
		{
			$disabled = '';
			$type = $zmcTypeApps[$zmcType]['license_group'];
			if (	($name === 'vmware' && empty(ZMC::$registry->vcli))
				||	empty($pm->lstats['licenses']['zmc']['Licensed'][$type])
				||	isset($pm->lstats['over_limit'][$type]))
				$disabled = ' disabled="disabled" ';
			echo "\t\t\t\t<option value='$zmcType' $disabled>$name</option>\n";
		}
		echo "\t\t\t</select>\n";
	}
	?>
	</div><!-- zmcFormWrapper -->
	<?
}
else 
{
	?>
	<input type="hidden" name="action" value="Create2" />
	<input type="hidden" name="selection1" value="<?= $pm->form_type['_key_name'] ?>" />
	<div wrapperCreate2 class="zmcFormWrapperRight <?= $pm->form_type['form_classes'] ?>" style="min-height:70px;">
		<img class="zmcWindowBackgroundimageRight"src="/images/3.1/edit.png" />
		<?= $pm->form_html ?>
	</div><!-- zmcFormWrapper -->
	<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
	?>
	<div class="zmcButtonBar" style="position:relative;">
		<input id="zmcSubmitButton" type="submit" name="action" value="<? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? 'Update' : 'Add'); ?>" />
		<? if ($pm->state === 'Edit')
			echo '<input id="addButton" type="submit" name="action" value="Add" disabled="disabled" />';
		?>
		<input type="submit" value="Discover" id="discoverButton" name="action"/>
  		<input type="submit" value="Cancel" id="cancelButton" name="action"/>
	</div>
<?
}
?>
</div><!-- zmcWindow -->
<?



if (empty($pm->rows))
	return print("<div style='height:250px;'>&nbsp;</div>\n</form>\n\n\n");

$only1user = (ZMC_User::count() === 1);
ZMC::titleHelpBar($pm, $pm->goto . "View, add, edit, and delete list of objects (DLEs) to backup with: " . $pm->selected_name, 'DLE+Table', 'zmcTitleBarTable');
?>
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
		'Refresh Table' => true,
		'Edit' => false,
		
		'Check Hosts' => "onclick=\"var sel=false; var o=gebi('dataTable').getElementsByTagName('input'); for(var i = 0; i < o.length; i++) { b = o.item(i); if (b.checked) sel=true; } if (sel) return true; return window.confirm('Check all?');\"",


		'Delete' => "onclick=\"return window.confirm('Deleting objects/DLEs does not delete backups.  If a backup still exists, the entry is marked as deleted, but remains visible on this page for reference, until the last backup is removed.  There is no undo, but backup copies are archived to /var/log/amanda/zmc.  Continue?')\"",
	),
	'html' => $html
));

echo "\n</form>\n";
