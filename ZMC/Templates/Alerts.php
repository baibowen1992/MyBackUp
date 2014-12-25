<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
ZMC::titleHelpBar($pm, '日志', '', 'wocloudTitleBarTable');
?>

	<div class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th width="101" class="subHeadingTitle" title="Name" scope="col">备份集</th>
				<th width="154" class="subHeadingTitle" title="Last Backup" scope="col">上一次备份</th>
				<th width="325" class="subHeadingTitle" title="Last Alert" scope="col">上次警告</th>
			</tr>
<?
$i = 0;

foreach ($pm->rows as $name => &$row)
{
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo <<<EOD
		<tr style='cursor:pointer' class='$color' onclick="noBubble(event); window.location.href='$pm[url]?id=$name&amp;action=Edit'; return true;">

EOD;
	echo ZMC_Form::tableRowCheckBox($name);

	















	echo '<td width="94" align="left">' . $row['configuration_name'];						
	echo '</td>';

	echo '<td width="153" align="left">'; 
	switch (strtolower($pm->backupStatus[$row['configuration_name']]))
	{
		case "ok":
			echo '<img src="/images/global/calendar/icon_calendar_success.gif" alt="Successful" width="14" height="14" title="Successful" />';
			break;
		
		case "warning":
			echo '<img src="/images/global/calendar/icon_calendar_warning.gif" alt="Warning" width="14" height="14" title="Warning" />';			
			break;
		
		case "error":
		case "failure":
		case "fatal":
			echo '<img src="/images/global/calendar/icon_calendar_failure.gif" alt="Error" width="14" height="14" title="Error" />';
	}
	echo "&nbsp; ", substr($pm->timeStamp[$row['configuration_name']], 0, -3); 
	echo "</td><td width='325' align='left'>$row[message]</td></tr>\n";
}
echo "      </table>
    </div><!-- dataTable -->\n\n";
