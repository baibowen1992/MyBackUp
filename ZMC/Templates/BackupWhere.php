<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if (isset($pm->form_type) && ($pm->form_type['license_group'] === 'changer' || ($pm->form_type['license_group'] === 'tape')))
	if (!isset($pm->binding) || strncmp($pm->binding['_key_name'], 'changer_ndmp', 11))
		require 'ZMC/Templates/lsscsiWindow.php';

if ($pm->state === 'Use1')
{
	echo "<div class='wocloudLeftWindow'>\n";
	ZMC::titleHelpBar($pm, ' 设备配置');
	if (!count(ZMC_BackupSet::getMyNames()))
		$sets = '<p style="padding:5px;">请先创建备份集</p>';
	elseif (empty($pm->sets))
		$sets = "<p style='padding:5px;'>所有备份集都已经绑定存储设备。<br />请新建一个备份集来绑定新设备</p>";
	else
	{
		$sets = '<select name="edit_id">';
				
		ksort($pm->sets);
		foreach($pm->sets as $name => $set)
		{
			$selected = ($name === $pm->selected_name) ? 'selected="selected"' : '';
			$sets .= "<option $selected value='" . ZMC::escape($name) . '\'>' . ZMC::escape($name) . "</option>\n";
		}
		$sets .= "</select>\n";
	}

    $devices_owner_array = ZMC_User::findAllDrivesOwner($_SESSION['user']);
	$i = 0;
	$devices = '';
	foreach($pm->device_profile_list as $name => $device)
	{
        foreach($devices_owner_array as $k1=>$v1)
        {
            if($v1['drives']==$name)
            {
                if (($i++ % 3) === 0) $devices .= "\n</tr><tr>\n";
                $disabled = $onclick = '';
                $selected = (!empty($pm->selected_device) && ($name === $pm->selected_device)) ? 'checked="checked"' : '';
                $icon = ZMC_Type_Devices::getIcon($pm, $device, $disabled);

                //added to check if the device is used
                $deviced_used = ZMC_Type_Devices::getdeviceused($name);
                if (!empty($deviced_used))
                    continue;

                $devices .= "
                <td>
                    <div style='padding:5px'>
                        <input type='radio' name='selected_device' value='$name' $selected $disabled id='radio$i' onclick=\"gebi('use_button').disabled = ''\">
                        <label for='radio$i'>$icon$name</label>
                    </div>
                </td>\n";
            }
        }
	}
    //when no device is created echo some tips ;following is added by zhoulin 20141016
    if ($i === 0)
    {
        $devices = "
                <td>
                    <div style='padding:5px'>
                        <label>该用户还没有未被使用的存储设备，<br>请先去页面<a href=" .ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . "\>管理|存储设备</a>新建</label>
                    </div>
                </td>\n";
    }
?>
	<div wrapperUse1 class="wocloudFormWrapper">
		<fieldset><legend>备份集</legend><?= $sets ?></fieldset>
		<fieldset><legend>选择存储设备</legend><?= $devices ?></fieldset>
	</div><!-- formWrapper -->
	<div class="wocloudButtonBar">
		<? if (!empty($pm->sets)) echo '<button id="use_button" type="submit" name="action" value="Use" ',
			(empty($pm->selected_device) ? 'disabled="disabled"':''), ' />'.'选择<button>'; ?>
	</div>
</div><!-- wocloudLeftWindow -->
<?
} 
elseif (!empty($pm->binding))
{
?>
<div id='deviceFormWrapper' class='wocloudLeftWindow'><?
//	ZMC::titleHelpBar($pm, rtrim($pm->state, '012') . ' ' . $pm->selected_name . ' configuration for device: ' . $pm->binding['private']['zmc_device_name'], $pm->state);
	ZMC::titleHelpBar($pm,'为备份集 ' . $pm->selected_name . ' 配置存储设备 '. $pm->binding['private']['zmc_device_name'], $pm->state);
	$icon = ZMC_Type_Devices::getIcon($pm, $pm->binding, $disabled);
?>
	<div wrapperCreate2 class="wocloudFormWrapper <?= $pm->form_type['form_classes'] ?>">
		<img class="wocloudWindowBackgroundimageRight" style="top:10px;" src="/images/3.1/<? echo ($pm->state === 'Edit' ? 'edit' : 'add'); ?>.png" />
		<div class="p" style='min-height:80px;'>
			<label>设备类型:</label>
			<div class='wocloudAfter'><a style="display:block; border:solid blue 1px; padding:2px; margin:2px;" href="/ZMC_Admin_Devices?action=Edit&edit_id=<? echo urlencode($pm->binding['private']['zmc_device_name']); ?>"><?= $icon ?></a></div>
			<label style='clear:left;'>&nbsp;</label>
			<div class='wocloudAfter'><?= $pm->pretty_name ?></div>
		</div>
		<?= $pm->form_html ?>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapper -->

<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
?>

	<div class="wocloudButtonBar">
		<?php if($pm->binding['_key_name'] === "changer_library" && ($pm->state === 'Edit' || $pm->state === 'Update')){ ?>
			<input type="submit" name="action" value="Update & Verify Tape Drive" onclick='return window.confirm("验证配置文件大概需要几分钟。\n\n警告: 在此期间请确保没有备份或者其他进程在使用该设备并确保在同一个时间仅有一个用户在该表格进行交互操作。请耐心等待该进程结束。");' />
		<?php } ?>
        <button type="submit" name="action" value="<? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? 'Update' : 'Add'); ?>" /><? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? '更新' : '创建'); ?></button>
        <button type="submit" value="Cancel" id="cancelButton" name="action"/>取消</button>

	</div>
</div><!-- wocloudLeftWindow -->
<?
}

$pm->tableTitle = '查看、添加编辑备份存储设备';
$pm->buttons = array('Refresh Table' => true, 'Edit' => true);
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
?>
<script>
var o =gebi('private:bandwidth_toggle');
if(o.checked == true){	
	gebi('device_property_list:MAX_SEND_SPEED').setAttribute('disabled','disabled');
	gebi('device_property_list:MAX_RECV_SPEED').setAttribute('disabled','disabled');
	gebi('device_property_list:NB_THREADS_BACKUP').disabled = true;
	gebi('device_property_list:NB_THREADS_RECOVERY').disabled = true;;
}
</script>

<?php

?>
