<?













global $pm;
if (!isset($pm->select))
	$pm->select = ''; 
?>
<div class="p">
	<label <? if (!empty($pm->label)) echo 'class="', $pm->label, '"'; ?>><?= $pm->owner ?>:<span class="required">*</span></label>
	<select name="ownerSelect"
			<? if (!empty($pm->class)) echo 'class="', $pm->class, '"'; ?>"
			onChange="o=gebi('btn'); if(o) o.disabled=false;">
	  	<option disabled='disabled'>Please select...</option>
		<?
			if (empty($pm->users))
				echo "<option>admin</option>\n";
			else
				foreach ($pm->users as $id => $user){
					if($user['user'] == "zmc")
						continue;
					echo "<option value='$id'", ($pm->select == $id ? ' selected="selected" ' : ''), ">$user[user]</option>\n";
				}
		?>
	</select>
</div>
