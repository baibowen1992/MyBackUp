<?













global $pm;
foreach($pm->rows as $key => $val){
	if (!isset($_REQUEST['edit_id']) || empty($_REQUEST['edit_id']))
		break;
	elseif (isset($_REQUEST['edit_id']) && !empty($_REQUEST['edit_id'])){
		if($_REQUEST['edit_id'] == $val['id']){
?>
<div class="wocloudLeftWindow" style="width:48%; clear:right;">
<? ZMC::titleHelpBar($pm, $pm['selected_name'] . ' Restore Status:  ' . $val['state'], '', '', '', '&nbsp;<span id="progress_dots"></span>'); 
	if (!empty($val['progress']))
		echo "<div class='wocloudSubHeadingSelect' style='margin:10px 10px -10px 10px;'>{$val['progress']}</div>\n";
?>
	<div class="wocloudFormWrapperText" id="restore_status_summary">
	<div style="overflow:auto; border-bottom:1px solid black; margin-bottom:7px;">
	<p style="padding-top:0;" id="job_output">
<?php

			echo 'Restore Started: ', $val['starttime_human'], "<br />\n";
			echo "Restore Duration: <span id='duration'>", $val['duration'], "</span><br />\n";
			if (!empty($val['output']))
			{
				echo str_replace("\n", "<br />\n", trim(ZMC::escape($val['output'])));
				echo '</p>';
			}
			echo '</div><div style="height: 300px; overflow: auto;"><span id="job_status"></span>';
			$replacement =  '<span class="wocloudUserErrorsText stripeRed">*&nbsp;Client&nbsp;Error:</span>';
			echo str_replace(array('* Client Status: FAILURE', '* Client Error:', ' Client Error:', ' Client Warning:'),
				array('<span class="wocloudUserErrorsText stripeRed">* Client Status: Restore Failed.</span>', $replacement, $replacement, '<span class="wocloudIconWarning">Client&nbsp;Warning:</span>'),
				str_replace("\n", "<br />\n", trim(ZMC::escape($val['status'])))), '</div>';
?>
	</div>
</div>
<?php
			$list_of_files = array();
			if(!empty($val['list_of_files'])){
				$list_of_files = explode("\n", $val['list_of_files']);
			}
			$includeCount = $excludeCount = count($list_of_files) - 1;
			if($includeCount < 0 || $excludeCount < 0){
				$includeCount = $excludeCount = 0;
			}
			restoreWindowHelper($pm, $val, 'Include', 'rlist', 'Restore files/directories', (empty($pm->selected) ? array():$pm->selected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_SELECT] , $val['restore_type'] == "express"? 0: $includeCount );

			restoreWindowHelper($pm, $val, 'Exclude', 'elist', 'Exclude files/directories/patterns', (empty($pm->deselected) ? array():$pm->deselected), ZMC_Restore_What::$colorMap[ZMC_Restore_What::IMPLIED_DESELECT], $val['restore_type'] == "express"? $excludeCount: 0);

		}
	}
}


function restoreWindowHelper($pm, $val, $listType, $list, $title, $map, $color, $count = 0)
{
	echo "<div class='wocloudRightWindow' style='clear:Right; width:48%; margin-left:0;'>\n";
	ZMC::titleHelpBar($pm, $title . ($count ? (': ' . $count) : ''));

	echo '      <div class="wocloudFormWrapperText" style="overflow:auto; max-height:300px; background-color:#', $color, ';">';
	if (empty($count))
	{
		if ($listType === 'Exclude' && $count === 0)
			echo "<div class='wocloudUserMessagesText wocloudIconSuccess'>Nothing explicitly excluded. Ok.</div>";
		elseif ($val['restore_type'] === ZMC_Restore::EXPRESS && $listType === "Include")
			echo "<div class='wocloudIconSuccess' style='font-size:24px; padding-top:4px; font-weight:bold;'>*</div>";
		elseif ($val['restore_type'] === ZMC_Restore::EXPRESS && $count != 0)
			echo "<div class='wocloudIconSuccess' style='font-size:24px; padding-top:4px; font-weight:bold;'>*</div>";
		else
			echo "<div class='wocloudUserWarningsText wocloudIconError'>Not available.</div>";
	}
	else
	{	$map = explode("\n", $val['list_of_files']);
		if (($count = count($map)) > 100)
			echo "<div class='wocloudUserWarningsText wocloudIconWarning'>Too many selections ($count) to display.<br />Showing only the first 100 selections:</div>\n";
		
		
		if (!empty($map))
		{
			$i = 1;
			foreach($map as &$row) 
			{
				if ($i++ > 100) break;
				echo ZMC::escape($row),"<br />";
			}
		}
	}
	echo '  </div><!-- "wocloudFormWrapperText" -->';
	echo '</div><!-- wocloudLeftWindow" -->';
}



echo "<form id=\"js_auto_refresh_form\" method='post' action='$pm->url'>";
ZMC::titleHelpBar($pm, $pm->goto . '查看还原历史', '', 'wocloudTitleBarTable');
foreach($pm->colUrls as $k => $v){
	if(isset($_REQUEST['edit_id']) && !empty($_REQUEST['edit_id']))
		$pm->colUrls[$k] = $v."&edit_id=".$_REQUEST['edit_id'];
}
?>

	<div class="dataTable" id="dataTable">
		<table width="100%">
			<tr>
				<? ?>
				<th title='Restore To (on or before)'D>
					<a href='<?= $pm->colUrls['backup_date'] ?>'>Restore To<br/>(on or before)<? if ($pm->sortImageIdx == 'backup_date') echo $pm->sortImageUrl; ?></a></th>
				<th title='源主机'>
					<a href='<?= $pm->colUrls['host'] ?>'>Original Host<? if ($pm->sortImageIdx == 'host') echo $pm->sortImageUrl; ?></a></th>
				<th title='Object Name'>
					<a href='<?= $pm->colUrls['disk_name'] ?>'>Object Name<? if ($pm->sortImageIdx == 'disk_name') echo $pm->sortImageUrl; ?></a></th>
				<th title='Target Host'>
					<a href='<?= $pm->colUrls['target_host'] ?>'>Target Host<? if ($pm->sortImageIdx == 'target_host') echo $pm->sortImageUrl; ?></a></th>
				<th title='Target Dir'>
					<a href='<?= $pm->colUrls['target_dir'] ?>'>Target Dir<? if ($pm->sortImageIdx == 'target_dir') echo $pm->sortImageUrl; ?></a></th>
				<th title='State'>
					<a href='<?= $pm->colUrls['state'] ?>'>State<? if ($pm->sortImageIdx == 'state') echo $pm->sortImageUrl; ?></a></th>
				<th title='Restore Start'>
					<a href='<?= $pm->colUrls['starttime_human'] ?>'>Restore Start<? if ($pm->sortImageIdx == 'starttime_human') echo $pm->sortImageUrl; ?></a></th>
				<th title='Restore End'>
					<a href='<?= $pm->colUrls['endtime_human'] ?>'>Restore End<? if ($pm->sortImageIdx == 'endtime_human') echo $pm->sortImageUrl; ?></a></th>
				<?

?>
				<th title='Restore Preference'D>
					<a href='<?= $pm->colUrls['restore_type'] ?>'>Restore<br />Preference<? if ($pm->sortImageIdx == 'restore_type') echo $pm->sortImageUrl; ?></a></th>
				<th title='User'D>
					<a href='<?= $pm->colUrls['user_name'] ?>'>User<? if ($pm->sortImageIdx == 'user_name') echo $pm->sortImageUrl; ?></a></th>
			</tr>

<?
$i = 0;
foreach ($pm->rows as $name => $row)
{
	$color = (($i++ % 2) ? 'stripeGray':'');
	echo "<tr style='cursor:pointer' class='$color' onclick=\"noBubble(event); window.location.href = '$pm[url]?edit_id=" . urlencode($row['id']) . "&amp;action=List'; return true;\">\n";
EOD;


$pm->disable_checkboxes = true;
foreach ($pm->cols as $index => $key)
{
	$escaped = '';
	if (!is_string($index))
		$escaped = (isset($row[$key]) ? ZMC::escape($row[$key]) : '');
	elseif (isset($row[$key]) && isset($row[$key][$index]))
		$escaped = ZMC::escape($row[$key][$index]);

	if($key == "restore_type"){
		if($escaped == "select" )
			$escaped = "Explore & Select";
		elseif($escaped == "search" )
			$escaped = "Search Specific Files";
		elseif($escaped == "express" )
			$escaped = "Restore All";
	}
	if($key == "state" && $escaped == "Finished")
		echo '<td><img src="/images/icons/icon_calendar_success.gif" title="'.$escaped.'" style="vertical-align:middle;"></td>';
	elseif($key == "state" && $escaped == "Failed")
		echo '<td><img src="/images/icons/icon_calendar_failure.gif" title="'.$escaped.'" style="vertical-align:middle;"></td>';
	else
		echo $escapedTd = "<td>$escaped</td>\n";

}echo "</tr>";
}
echo "      </table>
	</div><!-- dataTable -->";

$pm->buttons = array(
	'Refresh Table' => true,
);

ZMC_Loader::renderTemplate('tableButtonBar', $pm);
?>
