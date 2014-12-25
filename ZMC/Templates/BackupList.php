<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>
<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, ($pm->edit ? 'Edit Object (DLE) List' : 'Create Object (DLE) List'), $pm->state); ?>
	<img class="zmcWindowBackgroundimage" src="/images/3.1/<?= ($pm->edit ? 'edit' : 'add') ?>.png" />
	<div class="zmcFormWrapper zmcLongerInput">
<?
		if (ZMC_User::hasRole('Administrator'))
			ZMC_Loader::renderTemplate('OwnerSelect', array(
				'owner' => 'List Owner',
				'users' => $pm->users,
				'label' => 'label',
				'select' => ($pm->edit ? $pm->edit['owner_id'] : ''),
			));
?>
		<div class="p">
			<label>List Name:<span class="required">*</span></label>
			<?  if ($pm->edit)
					echo '<input type="hidden" name="name" value="', ZMC::escape($pm->name), '" />';
			?>
			<input
				type="text"
				name="name<? if ($pm->edit) echo 'Disabled'; ?>"
   				title="List names must be unique.  Allowable characters are dash, underscore and alphanumeric characters."
				onKeyUp="o=gebi('btn'); if(o) o.disabled=false"
				<?
					if ($pm->edit)
						echo "value='", $pm->edit['id'], "' disabled='disabled'";
				?>
   			/>
		</div><div class="p">
			<label>Comments:</label>
			<textarea
   				name="comments"
   				title="List description"
   				cols="31"
   				rows="4"
				onKeyUp="o=gebi('btn'); if(o) o.disabled=false"
			><?  if ($pm->edit) echo $pm->edit['comments']; ?></textarea>
		</div>
	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar">
		<input id="btn" disabled="disabled" type="submit" name="action" value="<?= ($pm->edit ? 'Update' : 'Add') ?>" />
  		<input type="submit" value="Cancel" id="cancelButton" name="action"/>
	</div>
</div><!-- zmcLeftWindow -->
<?


if (empty($pm->rows))
	return print("<div style='height:250px;'>&nbsp;</div>\n</form>\n");

ZMC::titleHelpBar($pm, $pm->goto . "View, add, edit, and delete lists of objects (DLEs) to backup", '', 'zmcTitleBarTable');
?>
	<div class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='List Name'><a href='<?= $pm->colUrls['id'] ?>'>List Name (click to open)<? if ($pm->sortImageIdx === 'id') echo $pm->sortImageUrl; ?></a></th>
				<th title='Creation Date'><a href='<?= $pm->colUrls['creation_date'] ?>'>Creation Date<? if ($pm->sortImageIdx === 'creation_date') echo $pm->sortImageUrl; ?></a></th>
				<th title='Objects'><a href='<?= $pm->colUrls['objects'] ?>'>Objects<? if ($pm->sortImageIdx === 'objects') echo $pm->sortImageUrl; ?></a></th>
				<th title='Live'><a href='<?= $pm->colUrls['live'] ?>'>Live<? if ($pm->sortImageIdx === 'live') echo $pm->sortImageUrl; ?></a></th>
				<? if (ZMC_User::hasRole('Administrator')) { ?>
				<th title='Owner'><a href='<?= $pm->colUrls['user'] ?>'>Owner<? if ($pm->sortImageIdx === 'user') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
				<th title='Comments'><a href='<?= $pm->colUrls['comments'] ?>'>Comments<? if ($pm->sortImageIdx === 'comments') echo $pm->sortImageUrl; ?></a></th>
			</tr>
<?
$i = 0;
$whatUrl = ZMC_HeaderFooter::$instance->getUrl('Backup', 'what');
foreach ($pm->rows as $row)
{
	$encName = urlencode($row['id']);
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo <<<EOD
		<tr style='cursor:pointer' class='$color' onclick="noBubble(event); window.location.href = '$pm[url]?name=$encName&amp;action=Edit'; return true;">

EOD;
	echo ZMC_Form::tableRowCheckBox($row['id']);

	foreach ($pm->columns as $key)
	{
		$escaped = ZMC::escape($row[$key]);
		$escapedTd = "<td>$escaped</td>\n";

		switch($key)
		{
			case 'id':
				$displayName = ZMC_BackupSet::displayName($row[$key]);
				
				echo "<td><a onclick='noBubble(event)' href='", $whatUrl, '?disklist=', urlencode($row['id']), "'>$displayName</a></td>\n";
				break;

			case 'creation_date':
				echo "<td>", ZMC::escape(substr($row[$key], 0, -9)), "</td>\n";
				break;

			case 'live':
				echo "<td>", $row[$key] ? 'Yes' : 'No', "</td>\n";
				break;

			default:
				echo $escapedTd;
		}
	}
	echo "</tr>\n";
}
echo "      </table>
	    </div><!-- dataTable -->\n\n";

ZMC_Loader::renderTemplate('tableButtonBar', array('goto' => $pm->goto, 'buttons' => array(
		'Refresh Table' => true,
		'Edit' => false,
		'Open' => "onClick=\"
			noBubble(event)
			var b; var o=gebi('dataTable').getElementsByTagName('input');
			for(var i = 0; i < o.length; i++)
			{
				b=o.item(i)
				if (b.checked)
				{
					b=b.name.substr(13, b.name.length -14)
					break
				}
			}
			window.location.href='$whatUrl?disklist='+b
			return false\"",
		'Duplicate' => false,
		'Merge' => false,
		'Delete' => false,
	)));
