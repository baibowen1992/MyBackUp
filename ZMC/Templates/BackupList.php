<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>
<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, ($pm->edit ? '编辑备份项' : '新建备份项'), $pm->state); ?>
	<img class="wocloudWindowBackgroundimage" src="/images/3.1/<?= ($pm->edit ? 'edit' : 'add') ?>.png" />
	<div class="wocloudFormWrapper wocloudLongerInput">
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
   				title="名字必须是唯一的，支持的字符有短线、下划线的字母数字字符."
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
   				title="描述"
   				cols="31"
   				rows="4"
				onKeyUp="o=gebi('btn'); if(o) o.disabled=false"
			><?  if ($pm->edit) echo $pm->edit['comments']; ?></textarea>
		</div>
	</div><!-- wocloudFormWrapper -->
	<div class="wocloudButtonBar">
		<button id="btn" disabled="disabled" type="submit" name="action" value="<?= ($pm->edit ? 'Update' : 'Add') ?>" /><?= ($pm->edit ? '更新' : '创建') ?></button>
  		<button type="submit" value="Cancel" id="cancelButton" name="action"/>取消</button>
	</div>
</div><!-- wocloudLeftWindow -->
<?


if (empty($pm->rows))
	return print("<div style='height:250px;'>&nbsp;</div>\n</form>\n");

ZMC::titleHelpBar($pm, $pm->goto . "查看、新增、编辑、删除备份项", '', 'wocloudTitleBarTable');
?>
	<div class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='列表名'><a href='<?= $pm->colUrls['id'] ?>'>列表名(点击打开)<? if ($pm->sortImageIdx === 'id') echo $pm->sortImageUrl; ?></a></th>
				<th title='创建日期'><a href='<?= $pm->colUrls['creation_date'] ?>'>创建日期<? if ($pm->sortImageIdx === 'creation_date') echo $pm->sortImageUrl; ?></a></th>
				<th title='对象'><a href='<?= $pm->colUrls['objects'] ?>'>对象<? if ($pm->sortImageIdx === 'objects') echo $pm->sortImageUrl; ?></a></th>
				<th hidden="hidden" title='Live'><a href='<?= $pm->colUrls['live'] ?>'>Live<? if ($pm->sortImageIdx === 'live') echo $pm->sortImageUrl; ?></a></th>
				<? if (ZMC_User::hasRole('Administrator')) { ?>
				<th title='所属者'><a href='<?= $pm->colUrls['user'] ?>'>所属者<? if ($pm->sortImageIdx === 'user') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
				<th title='备注'><a href='<?= $pm->colUrls['comments'] ?>'>备注<? if ($pm->sortImageIdx === 'comments') echo $pm->sortImageUrl; ?></a></th>
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
		'Edit' => true,
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
		'Delete' => true,
	)));
