<?













global $pm;
?>
	<div class="wocloudButtonBar wocloudButtonsLeft">
		<input type="hidden" name="selected_ids[0]" value="0" />
<?
		if (empty($pm->disable_checkboxes))
//			echo '<input type="button" name="noop" value="反选" onclick="YAHOO.zmc.utils.invert_datatable_checkboxes(this); return false;" />';
			echo '<button type="button" name="noop" value="Invert Selection" onclick="YAHOO.zmc.utils.invert_datatable_checkboxes(this); return false;" />反选</button>';

if (empty($pm->buttons))
	$pm->buttons = array('Refresh Table' => true,'Edit' => true);

foreach($pm->buttons as $name => $enabled)
{
	if ($enabled === null)
		continue;
    if ($name === 'Edit') {
//        echo '<label title="'.$name.'"';
//        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
//        echo ' >'.$enabled.'-'.$name.'</label>', "\n";
        if (($pm[url] === '/ZMC_Admin_BackupSets')or(!$pm[url])){
            echo '<button name="action" type="submit" ';
            echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
            echo " value='$name' ";
            if (!$enabled || is_string($enabled))
                echo ' disabled="disabled" ';
            if (is_string($enabled))
                echo $enabled;
            echo ' />编辑'.'</button>', "\n";
        }
        else
            continue;
    }
    elseif ($name === 'Refresh Table') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />刷新表格'.'</button>', "\n";
    }
    elseif ($name === 'Check Hosts') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />检查主机'.'</button>', "\n";
    }
    elseif ($name === 'Delete') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />删除'.'</button>', "\n";
    }
    elseif ($name === 'Cancel') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />取消'.'</button>', "\n";
    }
    elseif ($name === 'Abort') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />终止'.'</button>', "\n";
    }
    elseif ($name === 'Use') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />选择'.'</button>', "\n";
    }
    elseif ($name === 'Start Backup Now') {
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />开始备份'.'</button>', "\n";
    }
    else{
        echo '<button name="action" type="submit" ';
        echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
        echo " value='$name' ";
        if (!$enabled || is_string($enabled))
            echo ' disabled="disabled" ';
        if (is_string($enabled))
            echo $enabled;
        echo ' />'.$name.'</button>', "\n";
    }
//    //added by zhoulin
//    echo '<label title="'.$name.'"';
//    echo ' id="', substr(strtolower(str_replace(' ', '_', $name)), 0, 8), '_button" ';
//    echo ' >'.$pm[url].'-'.$name.'</label>', "\n";
}

if (isset($pm->html))
	echo $pm->html, "\n";

echo "\t</div>\n";
