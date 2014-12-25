<?













global $pm;
if (true && ZMC_User::hasRole('Administrator'))
{
?>
<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Edit Files or Run Commands') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapper">
		<? if (ZMC::$registry->platform !== 'solaris') { ?>
		<div class="p" style="float:right;">
			<label><a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=<?= urlencode("ps aflxww | awk '\$NF !~ /^\\[/'") ?>">Process List</a></label>
			<label><a href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=<?= urlencode("top -bn1HM | awk '\$14 !~ /^\\[/'") ?>">Top List</a></label>
		</div>
		<? } ?>
		<div class="p">
			<label>Update Reports:</label>
			<input type="checkbox" name="updateAmReports" title="Use only after manually running 'amdump'" /> Use only after manually running 'amdump'
		</div>
		<? if (!empty(ZMC::$registry->admin_task_commands)) { ?>
		<!--p>
			<label class="shortLabel">Command Timeout:</label>
			<input type="text" size="2" maxlength="2" value="5" name="commandTimeout" title="Commands that do not complete within the timeout are terminated." /> (3 to 30 seconds):
		</p-->
		<div class="p">
			<label>Command or<br />&nbsp;&nbsp;File name:</label>
			<textarea style="width:794px;" rows="3" title="Run this command-line command" name="command"><?
				if (!empty($_REQUEST['command'])) echo ZMC::escape($_REQUEST['command']);
			?></textarea>
			<div style="float:left;" class="contextualInfoImage"><a target="_blank" href="<? $pm->help_link ?>#command"><img width="18" height="18" align="top" alt="More Information" src="/images/icons/icon_info.png"/></a>
				<div style="position:absolute; top:-75px; width:288px; left:20px;" class="contextualInfo">
					<p>(1) Enter the full path name for a file to edit, and click apply.</p>
					<p>(2) Then edit the file contents, and click apply again.</p>
					<p>-OR-</p>
					<p>Enter a simple command to run on this server, like: <b>pstree</b></p>
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
	<div class="zmcButtonBar">
		<input type="submit" value="Apply" name="action"/>
		<input type="reset" value="Cancel" name="action" />
		<input type="hidden" name="form" value="adminTasks" />
	</div>
	</form>
</div><!-- zmcLeftWindow -->
<?
	if (!empty($pm->commandResult))
	{
?>
		<div class="zmcLeftWindow">
			<form method="post" action="<?= $pm->url ?>">
			<input type="hidden" name="command" value="<?= ZMC::escape($_REQUEST['command']) ?>" />
<?
		ZMC::titleHelpBar($pm, 'Command Results for: ' . substr($_REQUEST['command'], 0, 128));
		$result = ZMC::escape(preg_replace(array('/_\\x08/', '/\\x08./', '/[^[:print:]\s]/'), array('', '', ''), $pm->commandResult));
		$lines = explode("\n", $result);
		$cols = min(135, max(array_map('strlen', $lines)));
		$lines = min(45, max(count($lines), 3));
		if (!file_exists($_REQUEST['command']))
			echo "<div class='zmcFormWrapperText' style='padding:5px;'><pre>$result</pre></div>\n\n";
		else
		{
?>
			<textarea style="margin:10px; max-width:960px; width:inherit;" cols="<?= $cols ?>" rows="<?= $lines ?>"
					title="Edit this file" name="commandResult"
					onkeyup="gebi('save_changes_btn').disabled=false"
					><?= $result ?>
			</textarea>
			<div class="zmcButtonBar">
				<input id="reload_btn" type="submit" value="Reload/Rerun" name="action"/>
				<input id="save_changes_btn" type="submit" value="Save Changes" disabled="disabled" name="action"/>
				<input type="reset" value="Cancel" name="action" />
				<input type="hidden" name="form" value="adminTasks" />
			</div>
<?		}
		echo "\n</form>\n</div>\n";
	}
}
