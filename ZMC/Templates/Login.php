<?













global $pm;




?>
<div style="height:10px;"></div>
<? if (!empty($pm['noCookies'])) { ?>
<script type="text/javascript">
	var noZmcYuiInit = true;
	document.cookie = "zmc_cookies_enabled=true; path=<?= $pm->url ?>";
	if (window.location.search.indexOf('cookies_checked') === -1)
	{
		var o = window.location
			o += '&amp;cookies_checked=1'
		window.location.replace(o)
	}
</script>
<? } 
if (!isset($pm->logos))
	ZMC_HeaderFooter::$instance->enableFooter(false);
if (!empty($pm['noCookies']))
	$pm->addEscapedError("Cookies required.\n\nPlease enable cookies in your browser before using this page.\n\nBrowser requirements are described in more detail <a href='" . ZMC::$registry->wiki . "Pre-Installation#Zmanda_Management_Console_Browser_Requirements' target='_blank'>here</a>.");
?>
<form action="<?= $pm->url ?>?cookies_checked=1" method="post">
<input type="hidden" name="login" value="<?= ZMC::$registry->short_name ?>" />
<input type="hidden" name="last_page" value="<?= isset($pm->last_page) ? $pm->last_page : '' ?>" />
<div class="zmcWindow">
	<? ZMC::titleHelpBar($pm, 'Zmanda Management Console Login'); ?>
	<div class="zmcFormWrapper zmcShorterInput" style="float:left; width:350px; margin:0px auto; text-align:justify;">
	<?
	if (isset($pm->logos))
	{
		if (count($pm->logos) === 0)
			echo "<h2>No products found.<br /><br />Please contact customer support, if you need help.</h2>\n";
		else
			echo "<center><h3>Please select</h3></center>\n";
		foreach($pm->logos as $key => $logo)
		{
			$svnInfo = '';
			if (!empty($pm->svn[$key]) && ZMC::$registry->debug)
			{
				$svn = $pm->svn[$key];
				$svnInfo = $svn['branch'] . ' r' . $svn['revision'] . "<br />\n";
			}
			echo '<div style="margin:5px;">', $svnInfo, '<a href="', $key, '"><img src="', $logo, '" /></a></div>', "\n";
		}
	}
	else
	{
	?>
		<img src="/images/login/logo_management_console.gif" />
		<br />
		<?
		if (!empty($pm->lostPassword))
			echo '<div style="margin:15px 0 15px 0; width:350px;"><p>Enter your Username and a new password will be assigned to your account. Your new password will be emailed to you, if email service has been configured on this server for remote delivery (or local delivery, if your ZMC account email address is local to this server).</p>
				<p>Alternatively, to reset the ZMC admin user password to "<b>admin</b>", run the command:
				<br /><small>' . ZMC::$registry->cnf->zmc_bin_path . 'reset_admin_password.sh</small><br />
				</p></div>';
		else
			echo '<br /><br />';
		?>

		<div class="p">
			<label for="username">Username: <span class="required">*</span></label>
			<input type="text" name="username" value="<? if (!empty($_SESSION['user'])) echo ZMC::escape($_SESSION['user']); ?>" />
		</div>

		<? if (!empty($pm->lostPassword))
		{ ?>
			<div class="p zmcButtonsLeft">
				<input value="Cancel" type="button" onclick="history.back()" />
				<input name="RetrievePassword" value="Create and Email New Password" type="submit">
			</div><?
		}
		else
		{
			?>
			<div class="p">
				<label for="password">Password: <span class="required">*</span></label>
				<input type="password" name="password" />&nbsp;
				<input id="login_button" type="submit" name="submit" value="Login" style="margin:0" />
			</div>
			<?
			if (!empty($_SESSION['logout']) || !empty($_SESSION['last_page']))
			{
			?>
				<div class="p">
					<label for="resume">Resume Session?&nbsp;</label>
					<input id="resume" name="resume" type="checkbox" value="" <? echo (empty($pm->loggedOut) ?	' checked="checked" ' : ''); ?> />
				</div>
			<?
			}
			?>
			<div style='clear:left;'></div>
			<div class="p">
				<label for="resume">Check Server <br />Installation?&nbsp;</label>
				<?php $default_check_server_installation = (ZMC::$registry->default_check_server_installation == true)? "checked='checked'" : "";?>
			<input id="check_server" name="check_server" type="checkbox" value="" <?=$default_check_server_installation?> />
			</div>
			<div style='clear:left;'></div>
			<div class="p">
				<label for="resume">Sync Backup Sets?&nbsp;</label>
				<?php $default_sync_backupset = (ZMC::$registry->default_sync_backupset == true)? "checked='checked'" : "";?>
				<input id="sync_backupset" name="sync_backupset" type="checkbox" value="" <?=$default_sync_backupset?>/>
			</div>
	
			<input id="javascript_switch" type="hidden" name="JS_SWITCH" value="JS_OFF" />
			<script>gebi("javascript_switch").value = 'JS_ON'</script>
			<div style='clear:left;'></div>
			<div class="p">
				<label>&nbsp;</label>
				<label><small><a href="<?= $pm->url ?>?action=lostPassword&login=<?= ZMC::$registry->short_name ?>">Can't access your account?</a></small></label>
			</div>
			<?
		}
		?>
	
	<?}  ?>
		<div id="zmcLoginMessageBox" style="clear:left;">
			<noscript>
			<div class="zmcMessageBox">
			<div class="zmcMsgWarnErr zmcUserErrorsText zmcIconError">&nbsp;&nbsp;
				JavaScript required. <br /><br />Please enable scripting in your browser before using this page.<br /><br />Browser requirements are described in more detail
				<a href="<?= ZMC::$registry->wiki ?>Pre-Installation#Zmanda_Management_Console_Browser_Requirements" target="_blank">here</a>.
			</div>
			</div>
			</noscript>
		<?
			if (isset($_GET['timeout']))
				$pm->addError('To protect the application data, the session has timed out.');
			ZMC_Loader::renderTemplate('MessageBox', $pm);
		?>
		</div>
		<div style='clear:left;'></div>
	</div>
	<img src="/images/login/vertical_rule.gif" width="2" height="361" align="left" />
	<div style="text-align:center; border-bottom:1px solid #5C706E; background-color:white; overflow:hidden;">
		<a href="http://www.zmanda.com/" target="_blank">
			<img src="/images/login/plogo_<?= (ZMC::$registry->short_name_lc)? ZMC::$registry->short_name_lc: "aee" ?>.gif" alt="visit the Zmanda Website" style="width:240px; padding:20px;" />
			<?php if(count($pm->logos) > 1){?>
				<img src="/images/login/plogo_zrm.gif" alt="visit the Zmanda Website" style="width:240px; padding:20px;" />
			<?php } ?>
		</a>
	</div>
	<div style="text-align:center; overflow:hidden;">
		<div class="zmcTitleBar">Zmanda Portal</div>
		<div style='height:148px; padding:0px; margin-top:20px;'>
	<div style="width:32%;float:left;">
		<a
			href="http://<?= ZMC::$registry->zn_host ?>.zmanda.com/index.php?action=Login&amp;module=Users&amp;xcartAnonSessionID="
			target="_blank"
			onmouseover="MM_swapImage('Network','','/images/login/icon_network_lrg_over.jpg',1)"
			onmouseout="MM_swapImgRestore()"
		>
			<img
				style="margin:auto; display:block;"
				src="/images/login/icon_network_lrg.jpg"
				title="Go to the Zmanda Network"
				alt="Go to the Zmanda Network"
				width="63"
				height="95"
				border="0"
				id="Network"
			/>
		</a>
	</div>
	<div style="width:2px; float:left;">
		<img src="/images/login/vertical_rule.gif" width="2" height="110" />
	</div>
	<div style="width:32%;float:left;">
		<a
			href="http://forums.zmanda.com/"
			target="_blank"
			onmouseover="MM_swapImage('Forums','','/images/login/icon_forum_lrg_over.jpg',1)"
			onmouseout="MM_swapImgRestore()"
		>
			<img
				style="margin:auto; display:block;"
				src="/images/login/icon_forum_lrg.jpg"
				title="Go to the Zmanda Forums"
				alt="Go to the Zmanda Forums"
				width="63"
				height="95"
				border="0"
				id="Forums"
			/>
		</a>
	</div>
	<div style="width:2px; float:left;">
		<img src="/images/login/vertical_rule.gif" width="2" height="110" />
	</div>
	<div style="width:32%;float:left;">
		<a
			href="http://wiki.zmanda.com/index.php/Main_Page"
			target="_blank"
			onmouseover="MM_swapImage('Wiki','','/images/login/icon_wiki_lrg_over.jpg',1)"
			onmouseout="MM_swapImgRestore()"
		>
			<img
				style="margin:auto; display:block;"
				src="/images/login/icon_wiki_lrg.jpg"
				title="Go to the Zmanda Wiki"
				alt="Go to the Zmanda Wiki"
				width="74"
				height="95"
				border="0"
				id="Wiki"
			/>
		</a>
	</div>
	</div>
	</div>
	<div class="zmcButtonBar">
		<div style='position:absolute; font-size:8pt; color:#CCC; z-index:999; top:7px; left:10px;'><?= ZMC::dateNow(true); ?></div>

		<div style="position:absolute; right:10px; top:7px; color:#999!important;">
			<small>Copyright &copy;&nbsp; <a href="http://www.zmanda.com" target="_blank">Zmanda, Inc.</a> All Rights Reserved.</small>
		</div>
	</div>
</div>
</form>

<?

chdir($_SERVER['DOCUMENT_ROOT'] . ZMC::$registry->scripts);
foreach(glob('*.js') as $file)
	echo '<script type="text/plain" src="', ZMC::$registry->scripts, $file, "\" ></script>\n";
	
foreach(array(
	'yui/json/json-min.js',
	'yui/yuiloader-dom-event/yuiloader-dom-event.js',





	'yui/connection/connection-min.js',

	'yui/dom/dom-min.js'
) as $file)
	echo '<script type="text/plain" src="', ZMC::$registry->scripts, $file, "\" ></script>\n";
	
