<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
if (!empty($pm->edit)) { ?>


<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '为备份集' . ($config = ZMC_BackupSet::getName()) .'设置性能参数'); ?>
	<div class="wocloudFormWrapperLeft wocloudLongLabel wocloudShortestInput">
		<img src="/images/3.1/settings.png" class="wocloudWindowBackgroundimage" />
		<div style="display: none;" class="p">
			<label>介质使用：</label>
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
		<div class="p" style="display: none;">
			<label>备份顺序:</label>
			<input type="text" name="dumporder" title="备份客户端备份进程执行顺序." value="<?= $pm->conf['dumporder'] ?>" />
		</div>
		<div class="p">
			<label>服务器端并行备份数:</label>
			<input type="text" name="inparallel" title="可并行执行备份数" value="<?= $pm->conf['inparallel'] ?>" />
		</div>
		<div class="p">
			<label>客户端并行执行备份数:</label>
			<input type="text" name="maxdumps" title="可并行执行来自于同一个客户端的最大备份数。" value="<?= $pm->conf['maxdumps'] ?>" />
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" align="top" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>这个默认值可以为每一个客户端在 备份|来源  进行定制.</p>
				</div>
			</div>
		</div>
		<div class="p">
			<label>媒体并行写：</label>
			<input type="text" name="taper_parallel_write" title="存储设备最大并行写数量" value="<?= ($pm->binding['taper_parallel_write'])?>" />
		</div>
		<div class="p">
			<label>并行备份端口:</label>
			<input type="text" name="reserved_tcp_port" title="提供给客户端并行备份的端口。" value="<?
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
					<img height="18" align="top" width="18" alt="更多信息" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>
分配给客户端并行备份的端口。
已经列在/etc/service中的端口将被忽略。如果只剩下4个端口可用，并且没有在/etc/services中，那么给所有客服端并行备份的最大端口数不能超过4。					</p>
					<p>查看<a target="_blank" href="<?= ZMC::$registry->lore ?>476">Ports for Parallel Backups</a> 获取更多关于并行备份端口的信息。</p>
				</div>
			</div>
		</div>
		<!--div class="p">
			<label>最大网络带宽：</label>
			<input type="text" name="netusage" title="分配给amanda的最大网络带宽" value="<?= empty($pm->conf['netusage']) ? ZMC::$registry['netusage'] : $pm->conf['netusage']; ?>" /><br />&nbsp;&nbsp;&nbsp;<span class="instructions">(这个默认值可以为每一个客户端在  备份|目标 页面修改)</span>
		</div -->
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapperLeft -->
	<?
/*	if (ZMC_User::hasRole('Administrator'))
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
				<div class="p">amanda.conf:<br /><input class="wocloudShorterInput" type="text" name="global_free_style_key"><label class="wocloudShortestLabel">=</label><input class="wocloudShorterInput" type="text" name="global_free_style_value"></div>
				<div class="p">zmc_backupset_dumptypes:<br /><input class="wocloudShorterInput" type="text" name="dumptype_free_style_key"><label class="wocloudShortestLabel">=</label><input class="wocloudShorterInput" type="text" name="dumptype_free_style_value"></div>
				<div class="p">
					<label>Record Backup?</label>
						<input name="record" type="radio" value="Yes" id="record_yes" $checked_yes>
						<label for="record_yes" class="wocloudShortestLabel"><b>Yes*</b></label>
						&nbsp;&nbsp;&nbsp;
						<input name="record" type="radio" value="No" id="record_no" $checked_no onclick="return window.confirm('Not recommended. Are you sure?')">
						<label for="record_no" class="wocloudShortestLabel">No</label>
				</div>
				<fieldset><legend>高级选项--直接编辑源文件(！仅限专家！)</legend>
					<a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/amanda.conf">amanda.conf</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/disklist.conf">disklist.conf</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/binding-$profile_name.yml">device binding</a>
					<br /><a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/amanda/$config/zmc_backupset_dumptypes">zmc_backupset_dumptype</a>
					| <a href="/ZMC_Admin_Advanced?form=adminTasks&action=Apply&command=/etc/zmanda/zmc/zmc_aee/zmc_user_dumptypes">zmc_user_dumptypes</a>
				</fieldset>
EOD;
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
		}   */
	?>
	<div class="wocloudButtonBar">
		<button type="submit" name="action" value="Update" title="Update"  />更新</button>
		<button type="submit" name="action" value="Cancel" title="Cancel"  />删除</button>
	</div>
</div><!-- wocloudLeftWindow -->

<?php $list = array("hours" => "小时", "minutes" => "分钟", "seconds" => "秒");?>

<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '为备份集' . ZMC_BackupSet::getName() .'设置时间有效期'); ?>
	<div class="wocloudFormWrapperLeft wocloudLongLabel wocloudShortestInput">
		<img src="/images/3.1/stopwatch.png" class="wocloudWindowBackgroundimage" />
<!--		<fieldset><legend>通知用户：</legend>
		<div class="p">
			<label>接收通知用户邮件地址：</label>
			<textarea class="wocloudLongInput" name="mailto" cols="25" rows="3" title="备份系统的通知会发送到这些邮箱，多个邮箱请用都好隔开。"><? echo strtr($pm->conf['mailto'], ', ', "\n\n"); ?></textarea>
		</div>
		</fieldset>-->
		<fieldset style='clear:left;'><legend>超时时间设置</legend>
		<div class="p">
			<label>备份预估：<br /><small>(每一个备份项)</small></label>
			<input type="text" name="etimeout" title="为备份项预估备份时间设置超时时间." size="6" maxlength="6"  value="<? echo $pm->conf['etimeout'] = ZMC::convertToDisplayTimeout($pm->conf['etimeout'], $pm->binding['backup_timeout_list']['etimeout_display']); ?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'etimeout_display', $list, $pm->binding['backup_timeout_list']['etimeout_display']? $pm->binding['backup_timeout_list']['etimeout_display']: 'minutes');?>
		</div>
		<div class="p">
			<label>校验：</label>
			<input type="text" name="ctimeout" title="客户端验证超时时间." size="6" maxlength="6" value="<? echo $pm->conf['ctimeout'] = ZMC::convertToDisplayTimeout($pm->conf['ctimeout'], $pm->binding['backup_timeout_list']['ctimeout_display']);?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'ctimeout_display', $list, $pm->binding['backup_timeout_list']['ctimeout_display']? $pm->binding['backup_timeout_list']['ctimeout_display']: 'minutes' );?>
		</div>
		<div class="p">
			<label>无数据发送:</label>
			<input type="text" name="dtimeout" title="备份超时时间." size="6" maxlength="6" value="<? echo $pm->conf['dtimeout'] =ZMC::convertToDisplayTimeout($pm->conf['dtimeout'], $pm->binding['backup_timeout_list']['dtimeout_display']); ?>" />
			<?php 	echo ZMC::dropdown('Specify the unit size', 'dtimeout_display', $list, $pm->binding['backup_timeout_list']['dtimeout_display']? $pm->binding['backup_timeout_list']['dtimeout_display'] : 'minutes' );?>
		</div>
		</fieldset>
		<div style='clear:left;'></div>
	</div><!-- wocloudFormWrapperLeft -->

	<div class="wocloudButtonBar">
        <button type="submit" name="action" value="Update" title="Update"  />更新</button>
        <button type="submit" name="action" value="Cancel" title="Cancel"  />删除</button>
	</div>
</div><!-- wocloudLeftWindow -->

<? } 

ZMC_Loader::renderTemplate('backupSets', $pm);
echo "\n</form>\n";
