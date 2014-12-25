<?













global $pm;
?>
<script>
function zmc_admin_users_form_enable()
{
	if (btn=gebi('btn_add'))
		if (gebi('origUsername').value != gebi('userName').value)
			btn.disabled=false
		else
			btn.disabled='disabled'
}
</script>



<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, (($pm->edit && !empty($pm->edit['user_id'])) ? 'Edit ZMC User: ' . ZMC::escape($pm->edit['origUsername']) : 'Create ZMC User')); ?>
	<form autocomplete="off" method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapperRight zmcLongInput">
		<img class="zmcWindowBackgroundimageRight" src="/images/3.1/<?= ($pm->edit ? 'edit' : 'add') ?>.png" />
<?
$label = 'User Role<span class="required">*</span>:</label>';
foreach(array('Administrator', 'Operator', empty(ZMC::$registry->enable_monitor_role) ? null:'Monitor', empty(ZMC::$registry->enable_restore_role) ? null:'RestoreOnly') as $role)
{
	if (empty($role)) continue;
	$begin = $end = $checked = $disabled = '';
	$labelClass = 'secondaryLabel';
	if(isset($_POST) && $_POST['user_role'] == $role)
		if(!empty($_POST) && $_POST['user_role'] == '')
			$checked = '';
		else 
			$checked = 'checked="checked"';
	elseif ($pm->edit && isset($pm->edit['user_role']) && ($pm->edit['user_role'] === $role))
		if(!empty($_POST) && $_POST['user_role'] == '')
			$checked = '';
		else 
			$checked = 'checked="checked"';

	if (!ZMC_User::hasRole('Administrator') || (!empty($pm->edit['user_id']) && ($pm->edit['user_id'] == 1))) 
	{
		$disabled = 'disabled="disabled"';
		$begin = '<span class="textDisabled">';
		$end = '</span>';
	}
	echo <<<EOD
	<p><label>$label&nbsp;</label>
	<input
		tabindex="5"
		onChange="zmc_admin_users_form_enable()"
		id="id$role"
		type="radio"
		name="user_role"
		value="$role"
		$checked
		$disabled
	/>&nbsp;<label for="id$role" class="secondaryLabel">$begin$role$end</label></p>
EOD;
	$label = '';
}
?>
		<input type='hidden' id='origUsername' name='origUsername' value='<?= empty($pm->origUsername) ? '' : ZMC::escape($pm->origUsername) ?>' />
		<div class="p">
			<label>User Name<span class="required">*</span>:</label>
			<input
				tabindex="1"
				<? echo 'onKeyUp="zmc_admin_users_form_enable()" ' ?>
				type="text"
				name="user"
				id="userName"
				<?
				if (isset($_POST) && $_POST['user'] != null){
					echo " value='", ZMC::escape($_POST['user']), "' ";
				}
				elseif ($pm->edit)
				{
					echo " value='", ZMC::escape($pm->edit['user']), "' ";
					if ($pm->edit['user_id'] == 1 || !ZMC_User::hasRole('Administrator'))
						echo ' readonly disabled="disabled" title="Do not edit admin user name." ';
				}
				else
					echo ' value="" title="Allowed characters are dash, underscore, and alphanumeric characters"';
				?>
			/>
		</div><div class="p">
			<label>Email Address<span class="required">*</span>:</label>
			<input
				tabindex="2"
				id="userEmail"
				type="text"
				name="email"
				onKeyUp="zmc_admin_users_form_enable()"
				title="Email address of the user for notification"
				<?
				if (isset($_POST) && $_POST['user'] != null)
					echo 'value="', ZMC::escape($_POST['email']), '"';
				elseif ($pm->edit)
					echo 'value="', ZMC::escape($pm->edit['email']), '"';
				?>
			/>
		</div><div class="p">
		<label><?
				if($_POST['edit_id'] == 0)
					echo 'Password<span class="required">*</span>';
				elseif (!empty($pm->edit['user_id']) || $_POST['edit_id'] != 0)
					echo 'New Password';
				else
					echo 'Password<span class="required">*</span>'; ?>
				<input style="float:none;" title="Show Password" type="checkbox" onclick="this.form['password'].type = (this.form['password'].type === 'password' ? 'text' : 'password');" />:
			</label>
			<input tabindex="3" type="password" name="password" title="Zmanda management console password of the user" id="newPassword" <?
				if ($pm->edit && isset($pm->edit['password']))
					echo 'value="', ZMC::escape($pm->edit['password']), '"';
				?>
				onKeyUp="zmc_admin_users_form_enable()"
			/>
		</div><div class="p">
			<label>Confirm<?
				if (empty($pm->edit['user_id']) || ($_POST['edit_id'] == 0))
					echo '<span class="required">*</span>';
			?>
				<input style="float:none;" title="Show Password" type="checkbox" onclick="this.form['confirm'].type = (this.form['confirm'].type === 'password' ? 'text' : 'password');" />:
			</label>
			<input tabindex="4" type="password" name="confirm" title="Re-enter the password" id="confirmNewPassword" <?
				if ($pm->edit && isset($pm->edit['confirm']))
					echo 'value="', ZMC::escape($pm->edit['confirm']), '"';
				?>
				onKeyUp="zmc_admin_users_form_enable()"
			/>
		</div>
		<div style='clear:both;'></div>
	</div><!-- zmcFormWrapper -->


	<div class="zmcButtonBar"><?
		if (!empty($pm->edit['user_id']) && $_POST['edit_id'] !== 0)
			echo '<input type="hidden" name="edit_id" value="', $pm->edit['user_id'], '" />'
				. '<input tabindex="6" type="submit" name="action" value="Update" />';
	
		if (ZMC_User::hasRole('Administrator') && (empty($pm->edit['user_id']) || ($pm->edit['user_id'] != 1))) 
			echo "<input tabindex='6' id='btn_add' type='submit' name='action' value='Add' disabled='disabled' />";
	

			echo "<input tabindex='7' type='submit' value='Cancel' id='btnCancel' name='btnCancel' />";
		?>
	</div><!-- zmcButtonBar -->
	</form>
</div><!-- zmcLeftWindow -->




<? if (!empty($pm->edit['user_id'])) { ?>
<div class="zmcLeftWindow">
    <? ZMC::titleHelpBar($pm, 'Associate ZMC User with Zmanda Network') ?>
	<form autocomplete="off" method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapper zmcLongerLabel zmcLongInput">
		<div class="p">
			<label>Zmanda Network User Name:</label>
			<input type="text" name="zmandaNetworkID" title="Enter Zmanda Network user name" id="znID" <?
				if ($pm->edit && !empty($pm->edit['network_ID']))
					echo 'value="', ZMC::escape($pm->edit['network_ID']), '"';
				?>
			/>
		</div><div class="p">
			<input type="hidden" name="edit_id" value="<?= $pm->edit['user_id'] ?>" />
			<input type='hidden' id='origUsername' name='origUsername' value='<?= $pm->origUsername ?>' />
		</div><div class="p">
			<label>Zmanda Network Password:</label>
			<input type="password" name="zmandaNetworkPassword" title="Enter Zmanda Network user password" id="znPassword" value="" />
		</div>
		<div style='clear:both;'></div>
	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar"><input type='submit' name='UpdateZN' value='Update' /></div>
	</form>
</div><!-- zmcLeftWindow -->
<?	} 

echo '<br style="clear:both;" />';

if (!isset($pm->rows) || !ZMC_User::hasRole('Administrator')) 
	echo '<div style="height:166px"></div>';
else
{
    ZMC::titleHelpBar($pm, 'View, edit and delete users');
?>
	<form method="post" action="<?= $pm->url ?>">
	<div id="dataTable" class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='User Name'><a href='<?= $pm->colUrls['user'] ?>'>User Name <? if ($pm->sortImageIdx == 'user') echo $pm->sortImageUrl; ?></a></th>
				<th title='User Role'><a href='<?= $pm->colUrls['user_role'] ?>'>User Role <? if ($pm->sortImageIdx == 'user_role') echo $pm->sortImageUrl; ?></a></th>
				<th title='Email Address'><a href='<?= $pm->colUrls['email'] ?>'>Email Address <? if ($pm->sortImageIdx == 'email') echo $pm->sortImageUrl; ?></a></th>
				<th title='Creation Date'><a href='<?= $pm->colUrls['registration_date'] ?>'>Creation Date <? if ($pm->sortImageIdx == 'registration_date') echo $pm->sortImageUrl; ?></a></th>
			</tr>
<?
		$i = 0;
		$url = '';
		foreach ($pm->rows as $name => $row)
		{
			if($row['user'] == "zmc")
				continue;
			$color = (($i++ % 2) ? 'stripeGray':'');
			$encName = urlencode($row['user_id']);
			echo "<tr style='cursor:pointer' class='$color' onclick=\"noBubble(event); window.location.href = '$pm[url]?edit_id=$encName&amp;action=Edit'; return true;\">\n";
			
			
			if ($row['user_id'] == 1 || $row['user'] == "zmc")
				echo "<td>-</td>\n";
			else
				echo <<<EOD
					<td onclick="list=this.getElementsByTagName('input'); list[0].checked = !list[0].checked; noBubble(event)">
						<input onclick="noBubble(event);" style="vertical-align:bottom" type="checkbox" name="selected_ids[$encName]" />
					</td>
EOD;
			foreach ($pm->columns as $key)
				echo '<td>', ZMC::escape($row[$key]), "</td>\n";
			echo "</tr>\n";
		}

?>
		</table>
	</div><!-- dataTable -->

	<div class="zmcButtonBar zmcButtonsLeft">
		<input type="button" name="noop" value="Invert Selection" onclick="var o=gebi('dataTable').getElementsByTagName('input'); for(var i = 0; i < o.length; i++) { b = o.item(i); b.checked = !b.checked } return false;" />
		<input type="submit" name="action" value="Delete" />
		<? echo $pm->goto; ?>
	</div>
	</form>
<? } ?>
</div>
