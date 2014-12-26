<?
//zhoulin-admin-advance 201409191139












global $pm;
if (true && ZMC_User::hasRole('Administrator'))
{
?>
<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '编辑文件或者运行命令') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="wocloudFormWrapper">
		<? if (ZMC::$registry->platform !== 'solaris') { ?>
		<div class="p" style="float:right;">
			<label><a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=<?= urlencode("ps aflxww | awk '\$NF !~ /^\\[/'") ?>">进程列表</a></label>
			<label><a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=<?= urlencode("top -bn1HM | awk '\$14 !~ /^\\[/'") ?>">Top列表</a></label>
		</div>
		<? } ?>
		<div class="p">
			<label>更新报告：</label>
			<input type="checkbox" name="updateAmReports" title="请仅在手动执行‘amdump’之后使用" /> 请仅在手动执行‘amdump’之后使用
		</div>
		<? if (!empty(ZMC::$registry->admin_task_commands)) { ?>
		<!--p>
			<label class="shortLabel">Command Timeout:</label>
			<input type="text" size="2" maxlength="2" value="5" name="commandTimeout" title="Commands that do not complete within the timeout are terminated." /> (3 to 30 seconds):
		</p-->
		<div class="p">
			<label>命令或者<br />&nbsp;&nbsp;文件名；</label>
			<textarea style="width:794px;" rows="3" title="运行这个命令行命令" name="command"><?
				if (!empty($_REQUEST['command'])) echo ZMC::escape($_REQUEST['command']);
			?></textarea>
			<div style="float:left;" class="contextualInfoImage"><a target="_blank" href="<? $pm->help_link ?>#command"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
				<div style="position:absolute; top:-75px; width:288px; left:20px;" class="contextualInfo">
					<p>(1) 请输入要编辑的文件的完整路径并点击应用。</p>
					<p>(2) 随后请编辑文件，完成后再次点击应用按钮。</p>
					<p>-或者-</p>
					<p>输入一条想在服务器上执行的简单命令，如：<b>pstree</b></p>
						<!--br><br>
						<a target="_blank" href="<? $pm->help_link ?>#command">See more examples.</a-->
					</p>
				</div>
			</div>
			<div style='clear:left;'></div>
		</div>
		<?
		} 
	echo '<div class="p"><label>Web Server</label><a href="/server-status">Status</a></div>';
	if (ZMC::$registry->debug)
		echo '<div class="p"><label>Test</label><a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=/opt/zmanda/amanda/bin/amadmin_find_times.sh">Amadmin Find</a></div>';
	if (ZMC::$registry->dev_only)
		echo '<div class="p"><label>Web Server</label><a href="/server-info">Info</a> (dev only mode)</div>';
	echo '<div class="p"><label>DB Server</label><a href="?mysql_stat">Status</a></div>';
	?>
	</div>
	<div class="wocloudButtonBar">
		<input type="submit" value="Apply" name="action"/>
		<input type="reset" value="Cancel" name="action" />
		<input type="hidden" name="form" value="adminTasks" />
	</div>
	</form>
</div><!-- wocloudLeftWindow -->
<?
	if (!empty($pm->commandResult))
	{
?>
		<div class="wocloudLeftWindow">
			<form method="post" action="<?= $pm->url ?>">
			<input type="hidden" name="command" value="<?= ZMC::escape($_REQUEST['command']) ?>" />
<?
		ZMC::titleHelpBar($pm, 'Command Results for: ' . substr($_REQUEST['command'], 0, 128));
		$result = ZMC::escape(preg_replace(array('/_\\x08/', '/\\x08./', '/[^[:print:]\s]/'), array('', '', ''), $pm->commandResult));
		$lines = explode("\n", $result);
		$cols = min(135, max(array_map('strlen', $lines)));
		$lines = min(45, max(count($lines), 3));
		if (!file_exists($_REQUEST['command']))
			echo "<div class='wocloudFormWrapperText' style='padding:5px;'><pre>$result</pre></div>\n\n";
		else
		{
?>
			<textarea style="margin:10px; max-width:960px; width:inherit;" cols="<?= $cols ?>" rows="<?= $lines ?>"
					title="Edit this file" name="commandResult"
					onkeyup="gebi('save_changes_btn').disabled=false"
					><?= $result ?>
			</textarea>
			<div class="wocloudButtonBar">
				<input id="reload_btn" type="submit" value="Reload/Rerun" name="action"/>
				<input id="save_changes_btn" type="submit" value="Save Changes" disabled="disabled" name="action"/>
				<input type="reset" value="Cancel" name="action" />
				<input type="hidden" name="form" value="adminTasks" />
			</div>
<?		}
		echo "\n</form>\n</div>\n";
	}
}
