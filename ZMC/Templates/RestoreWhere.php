<?













global $pm;
if(ZMC::escape($pm->restore['_key_name']) == "vmware"){
	if(empty($pm->restore['temp_dir'])){
		$pm->restore['temp_dir'] = ZMC::$registry['default_vmware_restore_temp_path'];
	}else if($pm->restore['temp_dir'] == "/tmp/amanda" && !empty(ZMC::$registry['default_vmware_restore_temp_path'])){
		$pm->restore['temp_dir'] = ZMC::$registry['default_vmware_restore_temp_path']; 
	}
	if(count($_POST) > 0  && $_POST['temp_dir'] == null)
		$pm->restore['temp_dir'] = null;
}
if(ZMC::escape($pm->restore['_key_name']) == "solaris" && $pm->restore['program'] == "amzfs-sendrecv"){
	if(empty($pm->restore['temp_dir']))
		$pm->restore['temp_dir'] = "/tmp";
	else if($pm->restore['temp_dir'] !== "/tmp" && $pm->restore['temp_dir'][0] !== "/")
		$pm->restore['temp_dir'] = "/tmp";


}
echo "\n<form method='post' action='$pm->url' onsubmit=\"
var rlist = this.elements['target_dir_selected_type']
if (rlist.length == undefined) return true
var dest_type_found = false
for(var i = 0; i < rlist.length; i++)
{
	if (rlist[i].checked)
		dest_type_found = true

	if (this.elements['host_type'].value == 'host_type_windows')
	{
		if (rlist[i].checked && (rlist[i].value == " . ZMC_Type_AmandaApps::DIR_UNIX . "))
		{
			alert('请更换目的目录类型，或者选择*NIX 为目的主机类型');
			return false;
		}
	}
}
if (dest_type_found == false)
{
	gebi('destination_type').style.backgroundColor = 'lightyellow'
	YAHOO.zmc.messageBox.append(null, 'Please select a Destination Type below.')
	return false;
}
\">\n";
$dhnHelp = '这是恢复过程目的主机的主机名.';
$tdst = $pm->restore['target_dir_selected_type'];
$pm->target_dir_types = ZMC_Type_AmandaApps::getTargetDirTypes($pm->restore['target_dir_types']);
?>
<script language="JavaScript">
var linux_temp_dir = '/tmp'
var windows_temp_dir = 'C:\\tmp'
function displayRootUsername(select, field)
{
	var textinput=gebi(field);
	<? if ($pm->restore['zwc']) echo "\n\tvar tdo = gebi('dl" . ZMC_Type_AmandaApps::DIR_UNIX . "')\n"; ?>
	if (gebi('linuxHost').selected)
	{
		textinput.value='root'
		textinput.disabled=false
		gebi('temp_dir').value = linux_temp_dir
		gebi('temp_dir_div').style.visibility = 'visible'
	}
	else 
	{
		textinput.value='amandabackup'
		textinput.disabled=true
		gebi('temp_dir').value = windows_temp_dir
		gebi('temp_dir_div').style.visibility = 'hidden'
	}

	var o = gebi('limit_restore_window');
	if (o)
		o.style.display = (select.value == 'windows') ? 'none' : 'block';
}
</script>	



<div class="wocloudLeftWindow" style="overflow:visible;">
	<? ZMC::titleHelpBar($pm, '将备份集'. $pm->selected_name . '中需要恢复的将内容恢复到哪里？'); ?>
	<div class="wocloudFormWrapper wocloudLongLabel wocloudLongInput">
		<div class="p">
			<label>备份项类型：</label>
			<input 
				type="text" 
				value="<?= ZMC::escape(empty($pm->restore['pretty_name']) ? $pm->restore['name'] : $pm->restore['pretty_name']); ?>"
				disabled="disabled"
			/>
		</div>

		<div class="p">
			<label>备份源主机：</label>
			<input 
				type="text" 
				value="<?= $pm->restore['client'] ?>"
				disabled="disabled"
			/>
		</div>

		<div class="p">
			<label>备份源目录:</label>
			<input 
				type="text" 
				value="<?= $pm->restore['disk_device'] ?>"
				disabled="disabled"
			/>
		</div>

		<?
		$cifs = ($pm->restore['_key_name'] === 'cifs');
		$vmware = $pm->restore['_key_name'] === 'vmware';
		if ($cifs || $vmware)
		{ ?>
		<div id="login_credential_div">
			<div class="p">
				<label><?= $cifs ? 'Share ':'' ?><?= $vmware ? 'ESX ':''?>用户名</label>
				<input 
					name='zmc_share_username'
					type="text" 
					value="<?= ZMC::escape($pm->restore['zmc_share_username']); ?>"
				/>
			</div>
	
			<div class="p">
				<label><?= $cifs ? 'Share ':'' ?><?= $vmware ? 'ESX ':''?>密码:
					<input style="float:none;" title="Show Password" type="checkbox" onclick="this.form['zmc_share_password'].type = (this.form['zmc_share_password'].type === 'password' ? 'text' : 'password');" />
				</label>
				<input 
					name='zmc_share_password'
					type="password" 
					value="<?= ZMC::escape($pm->restore['zmc_share_password']); ?>"
				/>
			</div>
		</div>
		<?}?>

		<? if ($cifs) { ?>
		<div id="cifs_div">
			<div class="p">
				<label>Share Domain:</label>
				<input 
					name='zmc_share_domain'
					type="text" 
					value="<?= ZMC::escape($pm->restore['zmc_share_domain']); ?>"
				/>
			</div>
			
			<div class="p">
				<label>目的主机类型:</label>
				<b>Linux/UNIX/Mac/Solaris</b><input type="hidden" name="host_type" value="', ZMC_Type_AmandaApps::HOST_TYPE_UNIX, '" />
			</div>
			
			<input 
				type="hidden" 
				id="user_name" 
				name="user_name" 
				title="" 
				value="<?= ($zwc ? 'amandabackup' : 'root'); 
					 ?>"
			/>

			<div class="p">
				<label>目的主机名<span class="required">*</span>:</label>
				<input 
					type="text"
					id="target_host"
					name="target_host" 
					title="<?= $dhnHelp ?>" 
					value="<?echo ZMC::escape($pm->restore['target_host']);?>"
				/>
			</div>
		</div>
		<? } ?>

<?	if ($vmware || $pm->restore['_key_name'] == 'ndmp')
		echo '<input type="hidden" name="target_host" value="localhost" />';
	else if($pm->restore['_key_name'] !== 'cifs')
	{
?>
		<div class="p">
			<label>目的主机类型:</label>
			<? if ($zwc = count(array_intersect(array(ZMC_Type_AmandaApps::DIR_WINDOWS, ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE, ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH, ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME), $pm->restore['target_dir_types']))) { ?>
			<select id='host_type' name="host_type" class="wocloudLongInput" onChange="displayRootUsername(this, 'user_name')" >'
				<?
				$nix = '';
				if ($pm->restore['user_name'] === 'root')
					$nix = " selected='selected' ";
				$disabled = '';
				if( $pm->restore['zmc_type'] === 'windowstemplate' || $pm->restore['zmc_type'] === 'windowsexchange' || $pm->restore['zmc_type'] === 'windowshypev' || $pm->restore['zmc_type'] === 'windowssqlserver' )
					$disabled = "disabled";
				echo "<option id='linuxHost' $disabled  value='", ZMC_Type_AmandaApps::HOST_TYPE_UNIX, "' $nix>Linux/UNIX/Mac/Solaris</option>";
				$optselect = '';
				if ($pm->restore['host_type'] === 'host_type_windows' )
					$optselect = " selected='selected' ";
				echo "<option id='windowsHost' value='", ZMC_Type_AmandaApps::HOST_TYPE_WINDOWS, "' $optselect>Windows</option>";
				?>
			</select>
			<? } else echo '<b>Linux/UNIX/Mac/Solaris</b><input type="hidden" name="host_type" value="', ZMC_Type_AmandaApps::HOST_TYPE_UNIX, '" />'; ?>
		</div>

			<input 
				type="hidden" 
				id="user_name" 
				name="user_name" 
				title="" 
				value="<?= ($zwc ? 'amandabackup' : 'root'); 
					 ?>"
			/>

		<div id="target_host_div" class="p">
			<label>目的主机名<span class="required">*</span>:</label>
			<input 
				type="text"
				id="target_host"
				name="target_host" 
				title="<?= $dhnHelp ?>" 
				value="<?
				if (($pm->restore['_key_name'] === 'cifs') && !empty($pm->restore['target_dir_selected_type']) && $pm->restore['target_dir_selected_type'] != ZMC_Type_AmandaApps::DIR_UNIX)
					echo substr($pm->restore['target_dir'], 2, $locpos = strpos($pm->restore['target_dir'], '\\', 3) -2);
				
				
				else
					echo ZMC::escape($pm->restore['target_host']);
?>"
			/>
		</div>
<? } ?>

		<div class="p">
			<?
				if($pm->restore['zmc_type'] === 'ndmp'){
?>
					<label id="target_dir_label" style='display:none;'>目的目录:</label>
					<input id="target_dir" name='target_dir'
						style="display:none;"
						type='text' onFocus='this.select()'
						value='<?= empty($locpos) ? ZMC::escape($pm->restore['target_dir']) : substr($pm->restore['target_dir'], $locpos +3) ?>' />
					<div id='ndmp_div'>
						<div class="p">
							<label>Filer Host Name<span class="required">*</span>:</label>
							<input type="text" name="ndmp_filer_host_name" value='<?= $pm->restore['ndmp_filer_host_name']?>'/>
						</div>
						<div class="p">
							<label>Volume Name<span class="required">*</span>:</label>
							<input type="text" name="ndmp_volume_name" value='<?= empty($pm->restore['ndmp_volume_name']) ? "/vol" : $pm->restore['ndmp_volume_name']?>'/>
						</div>
						<div class="p">
							<label>Directory<span class="required">*</span>:</label>
							<input type="text" name="ndmp_directory" value='<?= empty($pm->restore['ndmp_directory']) ? "/" : $pm->restore['ndmp_directory']?>'/>
						</div>
						<div class="p">
							<label>NDMP Username:</label>
							<input id='ndmp_username' name='ndmp_username' type='text'/>
						</div>
						<div class="p">
							<label>NDMP Password:
								<input title="Show Password" style="float:none;" id="ndmp_password_box" type="checkbox" onclick="this.form['ndmp_password'].type = (this.form['ndmp_password'].type === 'password' ? 'text' : 'password');">
							</label>
							<input id='ndmp_password' name='ndmp_password' type='password'/>
						</div>
						<div class="p">
							<label>Auth type<span class="required">*</span>:</label>
							<select id="ndmp_filer_auth" class="wocloudUltraShortInput" name="ndmp_filer_auth" title="Specify the authentication method">
								<option selected="selected" value="md5">MD5</option>
								<option value="text">Text</option>
							</select>
						</div>
					</div> 
			<?} elseif($pm->restore['zmc_type'] === 'vmware') {?>
					<label id="target_dir_label" style='display:none;'>目的目录:</label>
					<input id="target_dir" name='target_dir'
						style="display:none;"
						type='text' onFocus='this.select()'
						value='<?= empty($locpos) ? ZMC::escape($pm->restore['target_dir']) : substr($pm->restore['target_dir'], $locpos +3) ?>' />
					<div id="vmware_div">
						<div class="p">
							<label>ESX Host Name<span class="required">*</span>:</label>
							<input type="text" name="esx_host_name" value='<?= $pm->restore['esx_host_name']?>'/>
						</div>
						<div class="p">
							<label>Datastore Name<span class="required">*</span>:</label>
							<input type="text" name="datastore_name" value='<?= $pm->restore['datastore_name']?>'/>
						</div>
						<div class="p">
							<label>Virtual Machine Name<span class="required">*</span>:</label>
							<input type="text" name="virtual_machine_name" value='<?= $pm->restore['virtual_machine_name']?>'/>
						</div>
						<fieldset><p class="wocloudIconWarning">A VM should not already exist at the specified original/alternate location.</p></fieldset>
					</div>
			<?} else {?>
				<label id="target_dir_label">目的目录<span class="required">*</span>:</label>
				<input id="target_dir" name='target_dir'
					style="visibility:<?= ((empty($tdst) && count($pm->target_dir_types) === 1 && key($pm->target_dir_types) == ZMC_Type_AmandaApps::DIR_ORIGINAL) || ($tdst == ZMC_Type_AmandaApps::DIR_ORIGINAL)) ? 'hidden' : 'visible' ?>"
					type='text' onFocus='this.select()'
					value='<?= empty($locpos) ? ZMC::escape($pm->restore['target_dir']) : substr($pm->restore['target_dir'], $locpos +3) ?>' />
			<?}?>
			<br style='clear:left;' />
			<fieldset id="destination_type"><legend>目的目录类型?</legend>
<?
			$targetTypeCount = 0;
			
			$restore_to_original = false;
			
			if($pm->restore['zmc_type'] === 'windowsexchange' || $pm->restore['zmc_type'] === 'windowshypev')
				$restore_to_original = true;
			if($pm->restore['zmc_type'] === 'windowssqlserver'){
				if($pm->restore['restore_type'] === ZMC_Restore::EXPRESS){
					$restore_to_original = true;
					$pm->restore['target_dir_types'] = array(ZMC_Type_AmandaApps::DIR_ORIGINAL);
					unset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME]);
					unset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH]);
					$pm->restore['target_dir_selected_type'] = ZMC_Type_AmandaApps::DIR_ORIGINAL;
				} else {
					$dbs =& ZMC_Mysql::getAllRows('SELECT * FROM ' . $pm->restore['tableName'] . 'WHERE (restore = ' . ZMC_Restore_What::SELECT . ' OR restore = ' . ZMC_Restore_What::IMPLIED_SELECT . ') AND type = 2 ORDER BY id');
					foreach($dbs as $db){
						if($db['name'] === 'master' || $db['name'] === 'model' || $db['name'] === 'msdb'){
							$restore_to_original = true;
							$pm->restore['target_dir_types'] = array(ZMC_Type_AmandaApps::DIR_ORIGINAL);
							unset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME]);
							unset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH]);
							$pm->restore['target_dir_selected_type'] = ZMC_Type_AmandaApps::DIR_ORIGINAL;
							break;
						}
					}
				}
			}
			if($pm->restore['zmc_type'] === 'windowsexchange' && $pm->restore['restore_type'] === ZMC_Restore::EXPRESS){
				unset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_MS_EXCHANGE]);
			}
			
			foreach($pm->target_dir_types as $okType => $record)
			{
				if(( $pm->restore['zmc_type'] === 'windowstemplate' || $pm->restore['zmc_type'] === 'windowsexchange' || $pm->restore['zmc_type'] === 'windowshypev' || $pm->restore['zmc_type'] === 'windowssqlserver' ) && $okType == ZMC_Type_AmandaApps::DIR_UNIX)
					continue;

				if ($okType == ZMC_Type_AmandaApps::DIR_ORIGINAL)
				{
					if ($pm->restore['zwc'] && !$zwc) continue; 
				}
				$targetTypeCount++;
				$checked = '';
				if (	(count($pm->target_dir_types) === 1)
					||	($tdst == $okType)
					||	((empty($tdst) || empty($pm->target_dir_types[$tdst])) && ($okType != ZMC_Type_AmandaApps::DIR_ORIGINAL)))
					$checked = 'checked="checked"';

				if ($restore_to_original){
					if($okType == ZMC_Type_AmandaApps::DIR_ORIGINAL)
						$checked = 'checked="checked"';
					else
						$checked = '';
				}

				$onclick = $okType == ZMC_Type_AmandaApps::DIR_MS_EXCHANGE ? "updateDestinationLocationLabel(true);" : "updateDestinationLocationLabel(false);";
				$onclick .=  " adjustRestoreToOriginalLocation('visible', '', false, 'none', 'none');";
				if ($okType == ZMC_Type_AmandaApps::DIR_ORIGINAL)
					$onclick = "adjustRestoreToOriginalLocation('hidden', '{$pm->restore['client']}', true, 'none', 'none'); var o=gebi('full_path_mode'); if(o) {o.click(); o.disabled='disabled';} if(gebi('safe_mode')) gebi('safe_mode').disabled='disabled';";
				elseif ($okType == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME)
					$onclick = "adjustRestoreToOriginalLocation('hidden', '". $pm->restore['target_host'] ."', false, 'none', 'inline'); var o=gebi('full_path_mode'); if(o) {o.click(); o.disabled='disabled';} if(gebi('safe_mode')) gebi('safe_mode').disabled='disabled';";
				elseif ($okType == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH)
				$onclick = "adjustRestoreToOriginalLocation('hidden', '". $pm->restore['target_host'] ."', false, 'inline', 'none'); var o=gebi('full_path_mode'); if(o) {o.click(); o.disabled='disabled';} if(gebi('safe_mode')) gebi('safe_mode').disabled='disabled';";
				else
					$onclick .= "if(gebi('full_path_mode')) gebi('full_path_mode').disabled = false; if(gebi('safe_mode')) gebi('safe_mode').disabled=false;";
				if ($pm->restore['zwc'] && $okType != ZMC_Type_AmandaApps::DIR_RAW_IMAGE && $okType != ZMC_Type_AmandaApps::DIR_UNIX)
					$onclick .= "var ht=gebi('host_type'); if(ht) {ht.value='" . ZMC_Type_AmandaApps::HOST_TYPE_WINDOWS . "'; ht.onchange();}";
				else
				{
					$onclick .= "var ht=gebi('host_type'); if(ht) {ht.value='" . ZMC_Type_AmandaApps::HOST_TYPE_UNIX . "'; ht.onchange();}";
				}
				echo "<div id='div_$okType'><label for='dl$targetTypeCount' style='clear:left; text-align:right;'>", $record['field'], ": &nbsp;</label>";
				echo "<input id='dl$targetTypeCount' onclick=\"$onclick; return true;\" type='radio' name='target_dir_selected_type' value='$okType' $checked /> ";
				echo "<span class='wocloudAfter'>", ZMC::escape($record['description']), "</span><br style='clear:left;' />\n</div>\n";
			}
?>
			</fieldset>
		</div>

<?  if (	isset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_WINDOWS])
		||	isset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_WINDOWS_SHARE])
		||	isset($pm->target_dir_types[ZMC_Type_AmandaApps::DIR_UNIX]))
	{
?>
<?
		if(strpos($pm->restore['_key_name'], 'windows') !== false) 
			echo '<div class="p" style="display:none">';
 		else
			echo '<div class="p">';
?>	
			<label>恢复目录<span class="required">*</span>:</label>
			<br style='clear:left;' />
			<label for="safe_mode" style='clear:left; text-align:right;'>新目录</label>
			<input id="safe_mode" type="radio" name="safe_mode" value="1" <?= $pm->restore['safe_mode'] ? 'checked="checked"' : '' ?> />
			<span class="wocloudAfter">&lt;目的目录&gt;/wocloud.YYYY-MM-DD_hh-mm-ss/&lt;selections&gt; </span>
			<br style='clear:left;' />
			<label for="full_path_mode" style='clear:left; text-align:right;'>全路径</label>
			<input id="full_path_mode" type="radio" name="safe_mode" value="0" <?= $pm->restore['safe_mode'] ? '' : 'checked="checked"' ?> />
			<span class="wocloudAfter">&lt;目的目录&gt;/original/backup/path/to/restored/&lt;selections&gt;</span>
		</div>
<?	}
	else
		echo '<input type="hidden" name="safe_mode" value="0" />';

?>

		<div id='temp_dir_div' class="p">
			<label>临时文件目录<span class="required">*</span>:</label>
			<span id='temp_dir' style="visibility:<?= $pm->restore['temp_dir_auto'] ? 'hidden' : 'visible' ?>;" >
			<input
				type="text"
				name="temp_dir"
				title="目的主机上的临时文件目录用于恢复过程中存放临时文件。"
				value="<?= ZMC::escape($pm->restore['temp_dir']); ?>"
				onFocus='this.select()'
				onKeyUp="
					var o = gebi('linuxHost');
					if (o)
					{
						if (o.selected)
							linux_temp_dir = this.value
						else
							windows_temp_dir = this.value
					}
				"
			/><b>/.wocloud_restore/</b></span>
			<? if ($pm->restore['temp_dir_default'] !== false) { ?>
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" align="top" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>不要使用基于内存的文件系统。比如如果目的主机是 Solaris，那么不用使用 "/tmp"目录。在基于内存的文件系统上恢复大数据量文件的时候会耗尽所有内存，并导致系统交换空间爆掉。</p>
				</div>
			</div>
			<br style='clear:left;' />
			<label for='temp_auto' style='clear:left; text-align:right;'>自动选择: &nbsp;</label><input onclick="gebi('temp_dir').style.visibility='hidden'" id='temp_auto' type='radio' name='temp_dir_auto' value='1' <?= $pm->restore['temp_dir_auto'] ? 'checked="checked"' : '' ?> /> (推荐)
			<br style='clear:left;' />
			<label for='temp_manual' style='clear:left; text-align:right;'>手动: &nbsp;</label><input onclick="gebi('temp_dir').style.visibility='visible'" id='temp_manual' type='radio' name='temp_dir_auto' value='0' <?= $pm->restore['temp_dir_auto'] ? '' : 'checked="checked"' ?> />
			<? } ?>
			<div style='clear:left;'></div>
		</div>
	</div>
	<div class="wocloudButtonBar">
		<button type="submit" name="action" value="Apply Previous" class="wocloudButtonsLeft" />上一步</button>
		<button type="submit" name="action" value="Apply Next" />下一步</button>
	</div>
</div>


<?
if ($pm->restore['globable'])
{ ?>
<div id='limit_restore_window' class="wocloudLeftWindow" style="width:250px">
	<? ZMC::titleHelpBar($pm, '选择性还原所有?'); ?>
	<div class="wocloudFormWrapper wocloudLongerLabel wocloudLongInput">
		<? if (false && ZMC::$registry->dev_only) { ?>
		<div class="p">
			<label>要恢复的文件列表:</label>
			<textarea name="rlist" style="height:95px;"><?= ZMC::escape($pm->restore['rlist']); ?></textarea>
			<br style='clear:left;' />文件名列表或者通配符匹配的需要还原的数据。
		</div>
		<? } elseif ($pm->restore['excludable']) { ?>
		<div class="p">
			<label>排除列表：</label>
			<textarea name="elist" style="height:95px;"><?= ZMC::escape($pm->restore['elist']); ?></textarea>
			<br style='clear:left;' />文件/目录列表以及满足通配符排除的数据。
		</div>
		<? } ?>
		<div class="p">
			<span class="wocloudUserMessagesText">注意：每行写一项，留空表示全部恢复。
<!-- Leave both boxes blank to restore everything. -->
			<br />
			<br />
			</span>
		</div>
	</div>
</div>
<? } ?>

<?
if($pm->restore['zmc_type'] === 'windowssqlserver' && $pm->restore['restore_type'] !== ZMC_Restore::EXPRESS){
	$rows =& ZMC_Mysql::getAllRows('SELECT * FROM ' . $pm->restore['tableName'] . 'WHERE (restore = ' . ZMC_Restore_What::SELECT . ' OR restore = ' . ZMC_Restore_What::IMPLIED_SELECT . ') AND type = 2 ORDER BY id');
?>
<div id='sql_alternate_path' class='wocloudRightWindow' style="width:650px">
	<? ZMC::titleHelpBar($pm, 'Database(s) to restore');?>
	<div class="dataTable centerHeadings">
		<table border="0" width="650" cellspacing="0" cellpadding="0">
			<tbody>
				<col width="325">
				<col width="325">
				<tr>
					<th scope="col">Database Name</th>
					<th scope="col">New Path</th>
				</tr>
				<?	foreach($rows as $row){
						$newpath = empty($pm->restore['sql_alternate_path']) ? "C:\\" : $pm->restore['sql_alternate_path'][$row['id']]['new_path'];	
				?>
				<tr class="stripeGray">
					<td class="wocloudCenterNoLeftPad" style="border-left:none"><?=$row['filename']?></td>
					<td class="wocloudCenterNoLeftPad">
						<input
							id="sql_alternate_path_new_path_<?=$row['id']?>"
							name="sql_alternate_path_new_path_<?=$row['id']?>"
							type="text"
							style="width:99%;text-align:center;"
							value="<?=$newpath?>">
					</td>
					
				</tr>
				<?}?>
			</tbody>
		</table>
	</div>
</div>
<div id='sql_alternate_name' class='wocloudRightWindow' style="width:650px">
	<? ZMC::titleHelpBar($pm, 'Database(s) to restore');?>
	<div class="dataTable centerHeadings">
		<table border="0" width="650" cellspacing="0" cellpadding="0">
			<tbody>
				<col width="300">
				<col width="125">
				<col width="225">
				<tr>
					<th scope="col">Database Name</th>
					<th scope="col">New Name</th>
					<th scope="col">New Path</th>
				</tr>
				<?	foreach($rows as $row){
						$newpath = empty($pm->restore['sql_alternate_name']) ? "C:\\" : $pm->restore['sql_alternate_name'][$row['id']]['new_path'];	
						$newname = empty($pm->restore['sql_alternate_name']) ? $row['name'] : $pm->restore['sql_alternate_name'][$row['id']]['new_name'];	
				?>
				<tr class="stripeGray">
					<td class="wocloudCenterNoLeftPad" style="border-left:none"><?=$row['filename']?></td>
					<td class="wocloudCenterNoLeftPad">
						<input
							id="sql_alternate_name_new_name_<?=$row['id']?>"
							name="sql_alternate_name_new_name_<?=$row['id']?>"
							type="text"
							style="width:99%;text-align:center;"
							value="<?=$newname?>">
					</td>
					<td class="wocloudCenterNoLeftPad">
						<input
							id="sql_alternate_name_new_path_<?=$row['id']?>"
							name="sql_alternate_name_new_path_<?=$row['id']?>"
							type="text"
							style="width:99%;text-align:center;"
							value="<?=$newpath?>"></td>
				</tr>
				<?}?>
			</tbody>
		</table>
	</div>
</div>
<?}?>

<? if(($pm->restore['zmc_type'] === 'windowssqlserver' || $pm->restore['zmc_type'] === 'windowsexchange' || $pm->restore['zmc_type'] === 'windowshyperv')
					&& count($pm->restore['media_explored']) > 1) {?>
	<script>
		if(gebi('target_dir')) gebi('target_dir').style.visibility = 'hidden'
		if(gebi('target_dir')) gebi('target_dir_label').style.visibility = 'hidden'
	</script>
<?}?>

<script>
	function adjustRestoreToOriginalLocation(isVisible, targetHost, disableTargetHost, sqlAlternatePath, sqlAlternateName) {
		if(gebi('ndmp_div')) gebi('ndmp_div').style.visibility = isVisible
		if(gebi('vmware_div')) gebi('vmware_div').style.visibility = isVisible
		if(gebi('cifs_div')) gebi('cifs_div').style.visibility = isVisible
		if(gebi('login_credential_div')) gebi('login_credential_div').style.visibility = isVisible
		if(gebi('target_dir_label')) gebi('target_dir_label').style.visibility = isVisible
		if(gebi('target_dir')) gebi('target_dir').style.visibility = isVisible
		if(gebi('sql_alternate_path')) gebi('sql_alternate_path').style.display = sqlAlternatePath
		if(gebi('sql_alternate_name')) gebi('sql_alternate_name').style.display = sqlAlternateName
		if(gebi('target_host')){
			gebi('target_host').value = targetHost
		}
	}
</script>

<? if($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_ORIGINAL){
	if($pm->restore['zmc_type'] === 'ndmp'|| $pm->restore['zmc_type'] === 'vmware' || $pm->restore['zmc_type'] === 'cifs' || $pm->restore['zmc_type'] === 'windowssqlserver'){?>
	<script>
		adjustRestoreToOriginalLocation('hidden', '<?= $pm->restore['client'] ?>', true, 'none', 'none')
	</script>
<?}}?>
<?if($pm->restore['zmc_type'] === 'windowssqlserver'){?>
<? if(empty($pm->restore['target_dir_selected_type']) || $pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_NAME){?>
	<script>
		adjustRestoreToOriginalLocation('hidden', '<?=$pm->restore['target_host'] ?>', false, 'none', 'inline')
	</script>
<?}?>
<? if($pm->restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_MS_SQLSERVER_ALTERNATE_PATH){?>
	<script>
		adjustRestoreToOriginalLocation('hidden', '<?=$pm->restore['target_host'] ?>', false, 'inline', 'none')
	</script>
<?}}?>

<script>
	if(gebi('host_type')) gebi('host_type').onchange();
	function updateDestinationLocationLabel(isRecoveryDB){
		if(isRecoveryDB)
			if(gebi('target_dir_label')) gebi('target_dir_label').innerHTML = "Exchange Recovery DB<span class=\"required\">*</span>:";
		else
			if(gebi('target_dir_label')) gebi('target_dir_label').innerHTML = "目的目录<span class=\"required\">*</span>:";
	}
</script>
</form>
