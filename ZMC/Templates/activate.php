<?













if (empty($pm->edit))
	return;
?>


<div class="zmcRightWindow">
	<? ZMC::titleHelpBar($pm, 'Immediate Backup for: ' . ZMC_BackupSet::getName(), 'Immediate'); ?>
	<div class="zmcFormWrapperLeft">
		<?
			$disabledReason = $disabled = '';
			if ($pm->edit['profile_name'] === 'NONE')
			{
				$disabled = 'disabled="disabled"';
				$disabledReason = '<br /><br /><div class="zmcIconWarning">Choose a storage device for backups.   Please visit ' . ZMC::getPageUrl($pm, 'Backup', 'Where') . ' first.</div>';
			}
			elseif (intval($pm->edit['dles_total']) === 0)
			{
				$disabled = 'disabled="disabled"';
				$disabledReason = '<br /><br /><div class="zmcIconWarning">Nothing to backup!  Please visit ' . ZMC::getPageUrl($pm, 'Backup', 'What') . ' first.</div>';
			}
			$buttonValue = "Start Backup Now";
			$hideSpinner = 'visibility:hidden';
			$onclick = "disableButton(this, 'Starting ...'); gebi('select_dles').disabled='disabled'; gebi('progress_spinner').style.visibility='visible'; gebi('big_start_icon').src='/images/3.1/stopwatch.png'";
			if (!empty($pm->edit['backup_running']) || !empty($pm->edit['restore_running']))
			{
				$icon = ZMC_BackupSet::getStatusIconHtml(null);
				$buttonValue = "Monitor Backup Now";
				$hideSpinner = '';
				$onclick .= ' noBubble(event); window.location.href=\'' . ZMC_HeaderFooter::$instance->getUrl('Monitor', 'backups') . '\'; return false;';
				echo '<br /><img style="z-index:9; float:left;" src="/images/3.1/stopwatch.png" class="zmcWindowBackgroundimage" width="75" height="99" />
					Performing immediate backup run now.
					<br />Use the &quot;<a href="', ZMC_HeaderFooter::$instance->getUrl('Monitor', 'backups'), '">Monitor</a>&quot; tab to monitor the backup run.';
			}
			else if(!empty($pm->edit['vault_running']))
			{
				$icon = ZMC_BackupSet::getStatusIconHtml(null);
				$buttonValue = "Monitor Vault Now";
				$hideSpinner = '';
				$onclick .= ' noBubble(event); window.location.href=\'' . ZMC_HeaderFooter::$instance->getUrl('Vault', 'jobs') . '\'; return false;';
				echo '<br /><img style="z-index:9; float:left;" src="/images/3.1/stopwatch.png" class="zmcWindowBackgroundimage" width="75" height="99" />
					Performing immediate vault run now.
					<br />Use the &quot;<a href="', ZMC_HeaderFooter::$instance->getUrl('Vault', 'jobs'), '">Vault</a>&quot; tab to monitor the vault run.';
			}
			else
			{
				$color = (empty($disabled) ? 'dark':'disabled');
				echo '<img id="big_start_icon" style="z-index:9" src="/images/3.1/start_', $color, '.png" class="zmcWindowBackgroundimage" width="75" height="75" ';
				if (empty($disabled))
					echo ' onmouseover="this.src=\'/images/3.1/start.png\';" onmouseout="this.src=\'/images/3.1/start_dark.png\';" onclick="this.src=\'/images/3.1/start_disabled.png\'; return gebi(\'start_backup_now_button\').click()"';
			   echo ' />
					<input id="backup_smart" type="radio" value="smart" title="Allow Amanda to choose" name="backup_how" checked="checked" />
					<label for="backup_smart">Auto Backup Level (recommended)</label>
					<br clear="all" />
					<input id="backup_full" type="radio" value="full" title="Force a Full Backup Now" name="backup_how" />
					<label for="backup_full">Force Full Backup</label>
					<br clear="all" />
					<input id="backup_incremental" type="radio" value="incremental" title="Force an Incremental Backup Now" name="backup_how" />
					<label for="backup_incremental">Force Incremental Backup</label>
					<br clear="all" />
					',
					$disabledReason;
			}
		?>
		<div style='clear:left;'></div>
	</div>
	<div class="zmcButtonBar">
		<img id='progress_spinner' style='float:left; margin:3px; <?=$hideSpinner?>;' title='Backup In Progress' src='/images/global/calendar/icon_calendar_progress.gif'>
		<input id="start_backup_now_button" <?= $disabled ?> type="submit" name="action" value="<?= $buttonValue ?>" onclick="<?= $onclick ?>" />
		<?if($buttonValue === "Start Backup Now"){?>
		<input id="select_dles" <?= $disabled ?> type="submit" name="action" value="Select DLEs" />
		<?}?>
	</div>
</div><!-- zmcLeftWindow -->



<input type="hidden" name="edit_id" value="<?= $pm->edit['configuration_name'] ?>" />
<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Backup Set Activation / Deactivation for: ' . $pm->edit['configuration_name'], 'Activation');
		$btn = 'off_dark';
		$btnHover = 'off';
		if (empty($pm->edit['schedule_type']))
			$btnHover = $btn = 'disabled';
		elseif (ZMC_BackupSet::isActivated())
		{
			$btn = 'on_dark';
			$btnHover = 'on';
		}
	?>
	<div class="zmcFormWrapperLeft">
	<br /><br /><img class="zmcWindowBackgroundimage" onclick="return gebi('vation_button').click();" src="/images/3.1/power_<?= $btn ?>.png" onmouseover="this.src='/images/3.1/power_<?= $btnHover ?>.png'" onmouseout="this.src='/images/3.1/power_<?= $btn ?>.png'" />
<?
if (!empty($pm->edit['profile_name']) && $pm->edit['profile_name'] !== 'NONE')
{
	if (ZMC_BackupSet::isActivated())
		echo 'This backup set is active.
		</div>
		<div class="zmcButtonBar">
			<input id="vation_button" type="submit" name="action" value="Deactivate Now" onclick="disableButton(this, \'Starting ...\')" />
		</div>';
	else
		echo 'This backup set is not active.
		</div>
		<div class="zmcButtonBar">
			<input id="vation_button" type="submit" name="action" value="Activate Now" onclick="disableButton(this, \'Activating ...\')" />
		</div>';
}
else
{
	$whenPage = '<a href="' . (ZMC_HeaderFooter::$instance->getUrl('Backup', 'when')) . '">Backup|when page</a>';
	$wherePage = '<a href="' . (ZMC_HeaderFooter::$instance->getUrl('Backup', 'where')) . '">Backup|where page</a>';
	echo "Before activating this backup set, please complete configuration by choosing a storage device
		on the $wherePage.  Once activated, the backup run will occur at the time scheduled on the $whenPage.
		</div>\n<div class='zmcButtonBar'></div>\n";
} 
?>
</div><!-- zmcLeftWindow -->

