<?













global $pm;
echo "<form id=\"js_auto_refresh_form\" method='post' action='$pm->url'>";
ZMC::titleHelpBar($pm, $pm->goto . 'Manage vault jobs', '', 'wocloudTitleBarTable');
?>

	<div class="dataTable" id="dataTable">
		<table width="100%">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='What'>
					<a href='<?= $pm->colUrls['vault_what'] ?>'>What<? if ($pm->sortImageIdx == 'vault_what') echo $pm->sortImageUrl; ?></a></th>
				<th title='Where'>
					<a href='<?= $pm->colUrls['vault_where'] ?>'>Where<? if ($pm->sortImageIdx == 'vault_where') echo $pm->sortImageUrl; ?></a></th>
				<th title='When'>
					<a href='<?= $pm->colUrls['vault_when'] ?>'>When<? if ($pm->sortImageIdx == 'vault_when') echo $pm->sortImageUrl; ?></a></th>
				<th title='Created Time'>
					<a href='<?= $pm->colUrls['timestamp'] ?>'>Created Time<? if ($pm->sortImageIdx == 'timestamp') echo $pm->sortImageUrl; ?></a></th>
				<th title='Activated?'>
					<a href='<?= $pm->colUrls['vault_activated'] ?>'>Activated?<? if ($pm->sortImageIdx == 'vault_activated') echo $pm->sortImageUrl; ?></a></th>
				<th title='In Progress?'>
					<a href='<?= $pm->colUrls['in_progress'] ?>'>In Progress?<? if ($pm->sortImageIdx == 'in_progress') echo $pm->sortImageUrl; ?></a></th>
			</tr>

<?
$i = 0;
$hasJobInProgress = false;
foreach ($pm->rows as $name => $row)
{
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo <<<EOD
		<tr style='cursor:pointer' class='$color'>
EOD;
	echo ZMC_Form::tableRowCheckBox($row['timestamp'], '_vault_job');
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
			case 'timestamp':
				$date = new DateTime();
				$date->setTimestamp($row['timestamp']);
				$date->format('d-m-Y H:i:s');
				echo "<td>" . $date->format('Y-m-d H:i:s') . "</td>";
				break;

			case 'vault_activated':
				echo"<td><font color=" . ($row[$key] === 'Yes' ? 'green' : 'red') . "><b>" . $row[$key] . "</b></font></td>";
				break;
				
			case 'in_progress':
				if($row[$key]){
					echo "<td><img src=\"/images/global/calendar/icon_calendar_progress.gif\">&nbsp;" . $row[$key] . "</td>";
					$hasJobInProgress = true;
				} else {
					echo "<td><font color='green'><b>No</b></font></td>";
				}
				break;

			default:
				echo $escapedTd;
		}
	}
	echo "</tr>";
}
echo "      </table>
	    </div><!-- dataTable -->";

if($hasJobInProgress)
	$pm->buttons = array(
			'Refresh Table' => true,
			'激活' => false,
			'反激活' => false,
			'Delete' => false,
			'Abort' => false,
	);
else
	$pm->buttons = array(
			'Refresh Table' => true,
			'激活' => false,
			'反激活' => false,
			'Delete' => false,
			'Vault Now' => false,
	);

ZMC_Loader::renderTemplate('tableButtonBar', $pm);
?>
<script>
	var countdown = 15;
	setTimeout(function () { gebi('js_auto_refresh_form').submit(); }, countdown * 1000)
	function countdown()
	{
		var o = gebi('countdown')
		if (o)
			o.innerHTML = '&bull;'.repeat(countdown--)
		if (countdown < 1)
			countdown = 15
		setTimeout( 'countdown()', 1000 )
	}
	countdown()
</script>
<span id='countdown'></span>