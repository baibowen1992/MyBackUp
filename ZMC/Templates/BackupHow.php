<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
if (!empty($pm->edit)) { ?>


<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Performance Parameters for: ' . ($config = ZMC_BackupSet::getName())); ?>
	<div class="zmcFormWrapperLeft zmcLongLabel zmcShortestInput">
		<img src="/images/3.1/settings.png" class="zmcWindowBackgroundimage" />
		<div class="p">
			<label>Media utilization:</label>
			<select name='taperalgo'>
			<?
				foreach(array('First', 'First Fit', 'Largest', 'Largest Fit', 'Smallest', 'Last') as $name)
				{
					$selected = '';
					$value = str_replace(' ', '', strtolower($name));
					if (str_replace(' ', '', strtolower($pm->conf['taperalgo'])) === $value)
						$selected =" selected='selected' ";
					echo "<option value='$value' $selected>$name</option>\n";
				}
				?>
			</select>
		</div>
		<div class="p">
			<label>Backup Order:</label>
			<input type="text" name="dumporder" title="The order in which backups of Amanda clients are performed." value="<?= $pm->conf['dumporder'] ?>" />
		</div>
		<div class="p">
			<label>Server Parallel Backups:</label>
			<input type="text" name="inparallel" title="The maximum number of backups to run at the same time." value="<?= $pm->conf['inparallel'] ?>" />
		</div>
		<div class="p">
			<label>Client Parallel Backups:</label>
			<input type="text" name="maxdumps" title="The maximum number of backups from a single client to run at the same time." value="<?= $pm->conf['maxdumps'] ?>" />
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" align="top" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>This default can be altered per client on <?= ZMC::getPageUrl($pm, 'Backup', 'what') ?>.</p>
				</div>
			</div>
		</div>
		<div class="p">
			<label>Media Parallel Writes:</label>
			<input type="text" name="taper_parallel_write" title="The maximum number of media writes at the same time." value="<?= ($pm->binding['taper_parallel_write'])?>" />
		</div>
		<div class="p">
			<label>Ports for Parallel Backups:</label>
			<input type="text" name="reserved_tcp_port" title="Port range to use for parallel backups of clients." value="<?
				if (isset($pm->conf['reserved_tcp_port']))
				{
					$matches = preg_split('/\D+/', $pm->conf['reserved_tcp_port']);
					echo implode('-', $matches);
				}
				else
					echo "700-710";
			?>" />
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" align="top" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>
Port range to use for parallel backups of clients.
Ports already listed in /etc/services will be ignored.
If only 4 ports are available, forwarded by firewall,
and not listed in /etc/services, then maximum total
client backups in parallel will not exceed 4.
					</p>
					<p>See <a target="_blank" href="<?= ZMC::$registry->lore ?>476">Ports for Parallel Backups</a> in the Zmanda Knowledgebase.</p>
				</div>
			</div>
		</div>
		<!--div class="p">
			<label>Max Network Bandwidth:</label>
			<input type="text" name="netusage" title="The maximum network bandwidth allocated to Amanda." value="<?= empty($pm->conf['netusage']) ? ZMC::$registry['netusage'] : $pm->conf['netusage']; ?>" /><br />&nbsp;&nbsp;&nbsp;<span class="instructions">(default can be altered per client on Backup|what page)</span>
		</div -->
		<div style='clear:left;'></div>
	</div><!-- zmcFormWrapperLeft -->
	<?  if (ZMC_User::hasRole('Administrator'))
		{
			$checked_yes = '';
			$checked_no = "checked='checked'";
			if ($pm->record)
			{
				$checked_yes = "checked='checked'";
				$checked_no = '';
			}
			$pm->form_type = array('advanced_form_classes' => '');
			$profile_name = $pm->edit['profile_name'];
			$pm->form_advanced_html = <<<EOD
				<div class="p">amanda.conf:<br /><input class="zmcShorterInput" type="text" name="global_free_style_key"><label class="zmcShortestLabel">=</label><input class="zmcShorterInput" type="text" name="global_free_style_value"></div>
				<div class="p">zmc_backupset_dumptypes:<br /><input class="zmcShorterInput" type="text" name="dumptype_free_style_key"><label class="zmcShortestLabel">=</label><input class="zmcShorterInput" type="text" name="dumptype_free_style_value"></div>
				<div class="p">
					<label>Record Backup?</label>
						<input name="record" type="radio" value="Yes" id="record_yes" $checked_yes>
						<label for="record_yes" class="zmcShortestLabel"><b>Yes*</b></label>
						&nbsp;&nbsp;&nbsp;
						<input name="record" type="radio" value="No" id="record_no" $checked_no onclick="return window.confirm('Not recommended. Are you sure?')">
						<label for="record_no" class="zmcShortestLabel">No</label>
				</div>
				<fieldset><legend>Advanced Raw Edit (experts only)</legend>
					<a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/amanda.conf">amanda.conf</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/disklist.conf">disklist.conf</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/binding-$profile_name.yml">device binding</a>
					<br /><a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/zmc_backupset_dumptypes">zmc_backupset_dumptype</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_aee/zmc_user_dumptypes">zmc_user_dumptypes</a>
				</fieldset>
EOD;
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
		}
	?>
	<div class="zmcButtonBar">
		<input type="submit" name="action" value="Update" title="Update"  />
		<input type="submit" name="action" value="Cancel" title="Cancel"  />
	</div>
</div><!-- zmcLeftWindow -->

<?php $list = array("hours" => "Hour(s)", "minutes" => "Minute(s)", "seconds" => "Second(s)");?>

<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Time Outs And Notifications for: ' . ZMC_BackupSet::getName()); ?>
	<div class="zmcFormWrapperLeft zmcLongLabel zmcShortestInput">
		<img src="/images/3.1/stopwatch.png" class="zmcWindowBackgroundimage" />
		<fieldset><legend>Notify Who?</legend>
		<div class="p">
			<label>Email address(es):</label>
			<textarea class="zmcLongInput" name="mailto" cols="25" rows="3" title="Amanda backup completion notifications are sent to email addresses in this comma separated list"><? echo strtr($pm->conf['mailto'], ', ', "\n\n"); ?></textarea>
		</div>
		</fieldset>
		<fieldset style='clear:left;'><legend>Time Outs</legend>
		<div class="p">
			<label>Backup Estimate:<br /><small>(per DLE)</small></label>
			<input type="text" name="etimeout" title="Amanda backup size estimation timeout for each DLE." size="6" maxlength="6"  value="<? echo $pm->conf['etimeout'] = ZMC::convertToDisplayTimeout($pm->conf['etimeout'], $pm->binding['backup_timeout_list']['etimeout_display']); ?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'etimeout_display', $list, $pm->binding['backup_timeout_list']['etimeout_display']? $pm->binding['backup_timeout_list']['etimeout_display']: 'minutes');?>
		</div>
		<div class="p">
			<label>Verification:</label>
			<input type="text" name="ctimeout" title="Amanda client verification time out." size="6" maxlength="6" value="<? echo $pm->conf['ctimeout'] = ZMC::convertToDisplayTimeout($pm->conf['ctimeout'], $pm->binding['backup_timeout_list']['ctimeout_display']);?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'ctimeout_display', $list, $pm->binding['backup_timeout_list']['ctimeout_display']? $pm->binding['backup_timeout_list']['ctimeout_display']: 'minutes' );?>
		</div>
		<div class="p">
			<label>No Data Sent:</label>
			<input type="text" name="dtimeout" title="Amanda client backup timeout." size="6" maxlength="6" value="<? echo $pm->conf['dtimeout'] =ZMC::convertToDisplayTimeout($pm->conf['dtimeout'], $pm->binding['backup_timeout_list']['dtimeout_display']); ?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'dtimeout_display', $list, $pm->binding['backup_timeout_list']['dtimeout_display']? $pm->binding['backup_timeout_list']['dtimeout_display'] : 'minutes' );?>
		</div>
		</fieldset>
		<div style='clear:left;'></div>
	</div><!-- zmcFormWrapperLeft -->

	<div class="zmcButtonBar">
		<input type="submit" name="action" value="Update" title="Update"  />
		<input type="submit" name="action" value="Cancel" title="Cancel"  />
	</div>
</div><!-- zmcLeftWindow -->

<? } 

ZMC_Loader::renderTemplate('backupSets', $pm);
echo "\n</form>\n";
