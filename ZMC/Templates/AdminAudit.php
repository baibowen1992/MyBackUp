<?













global $pm;
if (empty($pm->rows))
	return print("No records found.");

$advanced_view = ( "true" === $_GET['advanced_view']) ? true : false;
$hide_row = array('Date', 'Time', 'Get', 'Post', 'Session');
$link = ($advanced_view)? "false": "true";


?>
<div class="zmcButtonBar">
<input style="width:150px" class="zmcRight" type="submit" name="advanced_view" value="Turn <?=($link === "false")? "Off": "On" ?> Advanced View?" onclick="document.location.href='<?=$pm->url?>?advanced_view=<?=$link?>'"/>
</div>
<?php

ZMC::titleHelpBar($pm, 'Audit Records');
?>

	<div class="dataTable">
		<table class="maxCol200" width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr><th>User</th>
				<?
foreach($pm->rows[0] as $key => $ignored){
	if(!$advanced_view && in_array(ucfirst($key), $hide_row))
		continue;
	if($key == 'request_uri')
		$key = 'Operation';
	if($key == 'pid')
		$key = 'Process ID';
	if($key == 'apache')
		$key = 'Server Date/Time';
	echo ($key === 'session' ? '<th class="zmcCenterNoLeftPad">S' : "<th>".ucfirst($key).""), "</th>\n";
}

echo '</tr>';

$i = 0;

foreach ($pm->rows as $key => $row)
{
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo "<tr class='$color'>";
	if (!is_array($row)) ZMC::quit(array($key, $row, $pm->rows));
	if (!empty($row['session']) && !empty($row['session']['user_id']))
		echo '<td>', ZMC::escape(ZMC_User::get('user', $row['session']['user_id'])), '</td>';
	else
		echo '<td>-</td>';
	foreach ($row as $key => $value)
	{
		if(!$advanced_view && in_array(ucfirst($key), $hide_row))
			continue;
		if (empty($value))
		{
			if(!in_array(ucfirst($key), $hide_row))
				echo "<td>View</td>\n";
			else
				echo "<td>-</td>\n";
			continue;
		}
		switch($key)
		{
			case 'request_uri':
				$value = str_replace('/ZMC_', '', $value);
			case 'apache':
			case 'date':
			case 'time':
			case 'pid':
				echo "<td>$value</td>\n";
				break;
			case 'session':
				unset($value['tab']);
			default:
				if (is_array($value))
					echo '<td><span class="contextualInfoImage"><a target="_blank" href="#command"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"></a><div style="right:150px; height:300px;" class="contextualInfo pre">', ZMC::escape(substr(print_r($value, true), 8, -3)), "</div></span></td>\n";
				elseif (strlen($value) > 64)
					echo '<td class="pre">', wordwrap(ZMC::escape(trim($value))), "</td>\n";
				else
					echo '<td>', ZMC::escape($value), "</td>\n";
		}
	}
	echo "</tr>\n";
}
echo '</table>';
