<?












?>

<div class="wocloudWindow">
	<? ZMC::titleHelpBar($pm, ($pm->state === 'Edit' ? '编辑备份集: ' . $pm->edit['configuration_name'] : '新建备份集'), $pm->state); ?>
	<div class="wocloudFormWrapperLeft wocloudLongInput">
		<img class="wocloudWindowBackgroundimage" src="/images/3.1/<?= ($pm->edit ? 'edit' : 'add') ?>.png" />
		<div class="wocloudShorterInput" style="float:right;">
			<div class="p">
				<label>简要说明:</label>
				<input
					type="text"
					name="org"
					title="两三个字来描述备份集，以便在报告邮件中显示。"
					id="backupSetName"
					maxlength="24"
					onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
					onKeyUp="o=gebi('btn'); if(o) {o.disabled=false}"
					value='<?= (empty($pm->edit) || empty($pm->edit['org'])) ? '' : ZMC::escape($pm->edit['org']);?>'
				>
			</div><div class="p">
				<label>显示文件大小单位:</label>
				<select
					name="display_unit"
					title="选择备份报告中显示的文件大小单位"
					onChange="o=gebi('btn'); if(o) {o.disabled=false}"
					/>
				<?
					$displayUnit = (empty($pm->edit['display_unit']) ? 'm' : $pm->edit['display_unit']);
					foreach (array( 'Kilobytes' => 'k', 'Megabytes' => 'm', 'Gigabytes' => 'g', 'Terabytes' => 't',) as $unit => $abr)
						echo "<option value='$abr'", ($displayUnit == $abr ? ' selected="selected" ' : ''), ">$unit</option>\n";
				?>
				</select>
			</div>
			<?
			if ($pm->edit)
			{
				$status = array();
				if (empty($pm->edit['schedule_type']))
					$status[] = '<p class="wocloudIconWarning">没有找到<a href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'when') . '">备份计划</a></p>';
				else
				{
					?>
					<div class="p" style="min-height:20px;">
						<label>激活：</label>
						<input
							type="checkbox"
							name="active"
							title="为备份集启用计划任务?"
							onChange="o=gebi('btn'); if(o) {o.disabled=false}"
							<?  if ($pm->edit && !empty($pm->edit['active'])) echo "checked='checked'"; ?>
							/>
					</div>
					<?
					if (empty($pm->edit['active']))
						$status[] = '<p class="wocloudIconWarning">备份集还没有激活(没有自动备份计划).<br />在  备份|执行 将备份计划<br />添加到系统的计划任务中</p>';
				}
				if (empty($pm->edit['dles_total']))
					$status[] = '<p class="wocloudIconWarning">没有找到<a href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'what') . '">备份项</a> </p>';
				if (empty($pm->edit['profile_name']) || $pm->edit['profile_name'] === 'NONE')
					$status[] = '<p class="wocloudIconWarning">没有绑定<a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '">存储设备</a></p>';
				if (!empty($status))
					echo '<div class="p"><fieldset><legend>', $pm->edit['configuration_name'], ' 状态</legend>', implode('', $status), "</fieldset></div>\n";
			}
			?>
	</div><!-- float:right -->
<?
		if (ZMC_User::hasRole('Administrator'))
			ZMC_Loader::renderTemplate('OwnerSelect', array(
				'owner' => '备份集拥有者',
				'users' => $pm->users,
				'select' => $pm->edit['owner_id'] ? $pm->edit['owner_id'] : $_SESSION['user_id'],
			));

        $resourcePool = ZMC_User::getResPoolByUserId($_SESSION['user_id']);
        $resPoolArray = explode(",",$resourcePool);
        $resourcejsonfile = file_get_contents("../json/resourcepool.json");
        $allResPool=json_decode($resourcejsonfile);
        //print_r($resPoolArray);
        //echo $resourcePool."----";echo count($resPoolArray);
        //johnny test json
        /*for($i=0;$i<count($allResPool);$i++){
            var_dump($allResPool[$i]);
            $obj = $allResPool[$i];
            echo $allResPool[$i]->name;
        }*/
        if($pm->state === 'Edit'){
            $hostip = $pm['binding_conf']['device_property_list']['S3_HOST'];
            for ($h = 0; $h < count($allResPool); $h++) {
                $objhostip = $allResPool[$h]->hostip;
                if ($hostip == $objhostip) {
                    $objname = $allResPool[$h]->name;
                    $objvalue = $allResPool[$h]->value;
                    continue;
                }
            }
        }
        /*
        echo '<pre>';
        print_r($pm['binding_conf']['device_property_list']['S3_HOST']);
        echo '</pre>';
        */
?>
        <div class="p">
            <label>资源池选择:<span class="required">*</span></label>
            <select name="respool" title="请选择资源池">
                <?
                if($pm->state === 'Edit') {
                    echo "<option value='$objvalue'>$objname</option>";
                }else{
                    $temp_count_j = 0;
                    for ($i = 0; $i < count($resPoolArray); $i++) {
                        for ($j = 0; $j < count($allResPool); $j++) {
                            $objname = $allResPool[$j]->name;
                            $objvalue = $allResPool[$j]->value;
                            if ($resPoolArray[$i] == $objvalue) {
                                $temp_count_j += 1;
                                echo "<option value='$resPoolArray[$i]'>$objname</option>";
                                continue;
                            }
                        }
                    }
                    if ($temp_count_j == 0) {
                        echo "<option value=''>无资源池</option>";
                    }
                }
                ?>
            </select>
        </div>
		<div class="p">
			<label>备份集名称:<span class="required">*</span></label>
			<input
				type="text"
				name="edit_id<? if ($pm->state === 'Edit') echo 'Disabled'; ?>"
 				title="备份集名称必须是唯一的。允许的字符有破折号、下划线和字母数字字符"
 				id="backupSetName"
				onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
				onKeyUp="
					if (/[^0-9a-zA-Z._-]/.exec(this.value) !== null)
					{
						this.value = this.value.replace(/[^0-9a-zA-Z._-]/, '');
						alert('请仅使用破折号、下划线和字母数字字符'); return false;
					}
					o=gebi('btn'); if(o) {o.disabled=false}"
				<?
					echo "value='", ZMC::escape($pm->edit['configuration_name']), "'";
					if ($pm->state === 'Edit')
						echo "Disabled' disabled='disabled";
					echo "'";
				?>
 				/>
		</div>
		<div class="p">
			<label>备注：</label>
			<textarea
 				name="configuration_notes"
 				title="简要描述备份集"
 				cols="31"
 				rows="4"
 				id="adminComments"
				onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
				onKeyUp="o=gebi('btn'); if(o) {o.disabled=false}"
			><?  if ($pm->edit) echo $pm->edit['configuration_notes']; ?></textarea>
			(<a href="" onclick="gebi('adminComments').value = ''; return false;" >清除</a>)
		</div>
		<div style='clear:both;'></div>
	</div><!-- wocloudFormWrapper -->
	<div class="wocloudButtonBar">
		<?	if ($pm->state === 'Edit')
				echo '<button id="btn" disabled="disabled" type="submit" name="action" value="Update" />更新'.'</button>
					  <button type="hidden" name="edit_id" value="', ZMC::escape($pm->edit['configuration_name']), '" />';
			else
				echo '<button id="btn" disabled="disabled" type="submit" name="action" value="Add" />创建'.'</button>';
		?>
		<button type="submit" value="Cancel" id="btnCancel1" name="action"/>取消</button>
		<? if ($pm->state === 'Edit') echo '<button type="submit" value="New" name="action" />创建'.'</button>'; ?>
	</div