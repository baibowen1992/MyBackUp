<?













if (empty($pm->edit))
	return;
?>


<div class="wocloudRightWindow" xmlns="http://www.w3.org/1999/html">
	<? ZMC::titleHelpBar($pm, '立即为备份集  ' . ZMC_BackupSet::getName(). '执行备份', 'Immediate'); ?>
	<div class="wocloudFormWrapperLeft">
		<?
			$disabledReason = $disabled = '';
			if ($pm->edit['profile_name'] === 'NONE')
			{
				$disabled = 'disabled="disabled"';
				$disabledReason = '<br /><br /><div class="wocloudIconWarning">为备份集选择存储设备，请点击 备份|目的地 页面 </div>';
			}
			elseif (intval($pm->edit['dles_total']) === 0)
			{
				$disabled = 'disabled="disabled"';
				$disabledReason = '<br /><br /><div class="wocloudIconWarning">没有备份集  请点击 备份|来源  页面 </div>';
			}
			$buttonValue = "Start Backup Now";
			$hideSpinner = 'visibility:hidden';
			$onclick = "disableButton(this, 'Starting ...'); gebi('select_dles').disabled='disabled'; gebi('progress_spinner').style.visibility='visible'; gebi('big_start_icon').src='/images/3.1/stopwatch.png'";
			if (!empty($pm->edit['backup_running']) || !empty($pm->edit['restore_running']))
			{
				$icon = ZMC_BackupSet::getStatusIconHtml(null);
				$buttonValue = "立即监控备份";
				$hideSpinner = '';
				$onclick .= ' noBubble(event); window.location.href=\'' . ZMC_HeaderFooter::$instance->getUrl('Monitor', 'backups') . '\'; return false;';
				echo '<br /><img style="z-index:9; float:left;" src="/images/3.1/stopwatch.png" class="wocloudWindowBackgroundimage" width="75" height="99" />
					立即运行备份进程。
					<br />使用 &quot;<a href="', ZMC_HeaderFooter::$instance->getUrl('Monitor', 'backups'), '">监控</a>&quot; 导航监控备份运行过程。';
			}
			else if(!empty($pm->edit['vault_running']))
			{
				$icon = ZMC_BackupSet::getStatusIconHtml(null);
				$buttonValue = "Monitor Vault Now";
				$hideSpinner = '';
				$onclick .= ' noBubble(event); window.location.href=\'' . ZMC_HeaderFooter::$instance->getUrl('Vault', 'jobs') . '\'; return false;';
				echo '<br /><img style="z-index:9; float:left;" src="/images/3.1/stopwatch.png" class="wocloudWindowBackgroundimage" width="75" height="99" />
					立即运行备份备份进程。
					<br />Use the &quot;<a href="', ZMC_HeaderFooter::$instance->getUrl('Vault', 'jobs'), '">Vault</a>&quot; tab to monitor the vault run.';
			}
			else
			{
				$color = (empty($disabled) ? 'dark':'disabled');
				echo '<img id="big_start_icon" style="z-index:9" src="/images/3.1/start_', $color, '.png" class="wocloudWindowBackgroundimage" width="75" height="75" ';
				if (empty($disabled))
					echo ' onmouseover="this.src=\'/images/3.1/start.png\';" onmouseout="this.src=\'/images/3.1/start_dark.png\';" onclick="this.src=\'/images/3.1/start_disabled.png\'; return gebi(\'start_backup_now_button\').click()"';
			   echo ' />
					<input id="backup_smart" type="radio" value="smart" title="允许系统选择" name="backup_how" checked="checked" />
					<label for="backup_smart">智能选择备份级别 (推荐)</label>
					<br clear="all" />
					<input id="backup_full" type="radio" value="full" title="强制执行完整备份" name="backup_how" />
					<label for="backup_full">强制执行完整备份</label>
					<br clear="all" />
					<input id="backup_incremental" type="radio" value="incremental" title="强制执行增量备份" name="backup_how" />
					<label for="backup_incremental">强制执行增量备份</label>
					<br clear="all" />
					',
					$disabledReason;
			}
		?>
		<div style='clear:left;'></div>
	</div>
	<div class="wocloudButtonBar">
		<img id='progress_spinner' style='float:left; margin:3px; <?=$hideSpinner?>;' title='备份中.......' src='/images/global/calendar/icon_calendar_progress.gif'>
		<input id="start_backup_now_button" <?= $disabled ?> hidden="hidden" type="submit" name="action" value="<?= $buttonValue ?>" onclick="<?= $onclick ?>" />
		    <button id="start_backup_now_button" <?= $disabled ?>  type="submit" name="action" value="<?= $buttonValue ?>" onclick="<?= $onclick ?>" />开始备份</button>
		<?if($buttonValue === "Start Backup Now"){?>
		<input id="select_dles" <?= $disabled ?> hidden="hidden" type="submit" name="action" value="选择备份集" />
            <button id="select_dles" <?= $disabled ?> type="submit" name="action" value="选择备份集" />选择备份集</button>
		<?}?>
	</div>
</div><!-- wocloudLeftWindow -->



<input type="hidden" name="edit_id" value="<?= $pm->edit['configuration_name'] ?>" />
<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '激活/取消激活 备份集: ' . $pm->edit['configuration_name'], 'Activation');
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
	<div class="wocloudFormWrapperLeft">
	<br /><br /><img class="wocloudWindowBackgroundimage" onclick="return gebi('vation_button').click();" src="/images/3.1/power_<?= $btn ?>.png" onmouseover="this.src='/images/3.1/power_<?= $btnHover ?>.png'" onmouseout="this.src='/images/3.1/power_<?= $btn ?>.png'" />
<?
if (!empty($pm->edit['profile_name']) && $pm->edit['profile_name'] !== 'NONE')
{
	if (ZMC_BackupSet::isActivated())
		echo '备份集已经激活。
		</div>
		<div class="wocloudButtonBar">
			<button id="vation_button" type="submit" name="action" value="Deactivate Now" onclick="disableButton(this, \'Starting ...\')" />取消激活</button>
		</div>';
	else
		echo '备份集没有激活
		</div>
		<div class="wocloudButtonBar">
			<button id="vation_button" type="submit" name="action" value="Activate Now" onclick="disableButton(this, \'Activating ...\')" />激活</button>
		</div>';
}
else
{
	$whenPage = '<a href="' . (ZMC_HeaderFooter::$instance->getUrl('Backup', 'when')) . '">备份|计划 页面</a>';
	$wherePage = '<a href="' . (ZMC_HeaderFooter::$instance->getUrl('Backup', 'where')) . '">备份|目的地 页面</a>';
	$wherePage = '<a href="' . (ZMC_HeaderFooter::$instance->getUrl('Backup', 'where')) . '">备份|目的地 页面</a>';
	echo "再激活这个备份集之前，请确保已经在页面  <b>备份|存储设备</b>  中完成存储设备的配置。</br>一旦激活，备份过程将会按照页面 <b>备份|计划</b>  配置的时间表执行。
		</div>\n<div class='wocloudButtonBar'></div>\n";
} 
?>
</div><!-- wocloudLeftWindow -->

