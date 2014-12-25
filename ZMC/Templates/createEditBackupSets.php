<?












?>

<div class="zmcWindow">
	<? ZMC::titleHelpBar($pm, ($pm->state === 'Edit' ? 'Edit Backup Set: ' . $pm->edit['configuration_name'] : 'Create Backup Set'), $pm->state); ?>
	<div class="zmcFormWrapperLeft zmcLongInput">
		<img class="zmcWindowBackgroundimage" src="/images/3.1/<?= ($pm->edit ? 'edit' : 'add') ?>.png" />
		<div class="zmcShorterInput" style="float:right;">
			<div class="p">
				<label>Brief Description:</label>
				<input
					type="text"
					name="org"
					title="Two or three words used to describe this backup set in emailed reports."
					id="backupSetName"
					maxlength="24"
					onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
					onKeyUp="o=gebi('btn'); if(o) {o.disabled=false}"
					value='<?= (empty($pm->edit) || empty($pm->edit['org'])) ? '' : ZMC::escape($pm->edit['org']);?>'
				>
			</div><div class="p">
				<label>Report Display Unit:</label>
				<select
					name="display_unit"
					title="Choose the display size unit to use in ZMC backup reports"
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
					$status[] = '<p class="zmcIconWarning">No <a href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'when') . '">backup schedule</a> found.</p>';
				else
				{
					?>
					<div class="p" style="min-height:20px;">
						<label>Active:</label>
						<input
							type="checkbox"
							name="active"
							title="Schedule backup set?"
							onChange="o=gebi('btn'); if(o) {o.disabled=false}"
							<?  if ($pm->edit && !empty($pm->edit['active'])) echo "checked='checked'"; ?>
							/>
					</div>
					<?
					if (empty($pm->edit['active']))
						$status[] = '<p class="zmcIconWarning">Backup set is not active (no automatic backups scheduled).<br />Use the ' . ZMC::getPageUrl($pm, 'Backup', 'now') . ' to install a schedule using<br />your host server\'s cron daemon.</p>';
				}
				if (empty($pm->edit['dles_total']))
					$status[] = '<p class="zmcIconWarning">No <a href="' . ZMC_HeaderFooter::$instance->getUrl('Backup', 'what') . '">backup objects/DLEs</a> found.</p>';
				if (empty($pm->edit['profile_name']) || $pm->edit['profile_name'] === 'NONE')
					$status[] = '<p class="zmcIconWarning">No <a href="' . ZMC_HeaderFooter::$instance->getUrl('Admin', 'devices') . '">storage device</a> configured.</p>';
				if (!empty($status))
					echo '<div class="p"><fieldset><legend>', $pm->edit['configuration_name'], ' Status</legend>', implode('', $status), "</fieldset></div>\n";
			}
			?>
	</div><!-- float:right -->
<?
		if (ZMC_User::hasRole('Administrator'))
			ZMC_Loader::renderTemplate('OwnerSelect', array(
				'owner' => 'Backup Set Owner',
				'users' => $pm->users,
				'select' => $pm->edit['owner_id'] ? $pm->edit['owner_id'] : $_SESSION['user_id'],
			));
?>
		<div class="p">
			<label>Backup Set Name:<span class="required">*</span></label>
			<input
				type="text"
				name="edit_id<? if ($pm->state === 'Edit') echo 'Disabled'; ?>"
 				title="Backup set names must be unique.  Allowable characters are dash, underscore and alphanumeric characters."
 				id="backupSetName"
				onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
				onKeyUp="
					if (/[^0-9a-zA-Z._-]/.exec(this.value) !== null)
					{
						this.value = this.value.replace(/[^0-9a-zA-Z._-]/, '');
						alert('Use only alphanumeric characters, period, underscore, and hyphen characters'); return false;
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
			<label>Comments:</label>
			<textarea
 				name="configuration_notes"
 				title="Backup set description"
 				cols="31"
 				rows="4"
 				id="adminComments"
				onBlur="o=gebi('btn'); if(o) {o.disabled=false}"
				onKeyUp="o=gebi('btn'); if(o) {o.disabled=false}"
			><?  if ($pm->edit) echo $pm->edit['configuration_notes']; ?></textarea>
			(<a href="" onclick="gebi('adminComments').value = ''; return false;" >clear</a>)
		</div>
		<div style='clear:both;'></div>
	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar">
		<?	if ($pm->state === 'Edit')
				echo '<input id="btn" disabled="disabled" type="submit" name="action" value="Update" />
					  <input type="hidden" name="edit_id" value="', ZMC::escape($pm->edit['configuration_name']), '" />';
			else
				echo '<input id="btn" disabled="disabled" type="submit" name="action" value="Add" />';
		?>
		<input type="submit" value="Cancel" id="btnCancel1" name="action"/>
		<? if ($pm->state === 'Edit') echo '<input type="submit" value="New" name="action"/>'; ?>
	</div>
</div><!-- zmcLeftWindow -->
