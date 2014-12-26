<?
//zhoulin-login 201409222016












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
	$pm->addEscapedError("需要浏览器启用cookie\n");
?>
<header class="sf-header" >
		<div class="sf-main" data-spm="a1z08" >
			<a href="" class="sf-logo"><img src="../images/logo.png">
            </a>
			   <div class="ser mt15 fr">
			    <div class="input-append">
				</div>
			   </div>	
		</div>
</header>
<form action="<?= $pm->url ?>?cookies_checked=1" method="post">
<input type="hidden" name="login" value="<?= ZMC::$registry->short_name ?>" />
<input type="hidden" name="last_page" value="<?= isset($pm->last_page) ? $pm->last_page : '' ?>" />
<div class="zmcWindow" style=" background:url(images/login/cube.jpg) center center no-repeat; height:522px;width:1440px ; margin: 0 auto; ">
	<!--<? ZMC::titleHelpBar($pm, ''); ?>
	<div class="zmcFormWrapper zmcShorterInput" style="float:center;width:350px; margin:0px auto; text-align:justify; background:#FFFFFF;"> -->
	<div class="zmcFormWrapper zmcShorterInput" style="background:#fff; width:260px; 
	border:1px solid #ccc;
	position:absolute;
	top:30px;
	right:80px;
	margin-right: 200px;
	margin-top: 85px; ">
	<?
	if (isset($pm->logos))
	{
		if (count($pm->logos) === 0)
			echo "<h2>未找到任何产品<br /><br />请联系管理员</h2>\n";
		else
			echo "<center><h3>请选择</h3></center>\n";
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


		<?
//		if (!empty($pm->lostPassword))
//			echo '<div style="margin:15px 0 15px 0; width:350px;"><p>重置密码请登陆云管理平台或者联系管理员。</p>
//				</p></div>';
//		else
//			echo '<br />';
		?>
        <?
        if ( $pm->singlelogin != 0){
            echo "当前认证体系：云平台单点登陆";}
        ?>
		<div class="p" <? echo (($pm->singlelogin === 0) ? '':'hidden' ); ?>>
			<input class="logininput"  type="text" placeholder="用户名" name="username"  style=" margin:0 0 10px 30px; padding-left:15px; line-height:26px; border:1px #cccccc solid; background-color:#fff; height:26px; width:180px;" value="<? if (!empty($_SESSION['user'])) echo ZMC::escape($_SESSION['user']); ?>" />
		</div>
		<? if (!empty($pm->lostPassword))
		{ ?>
<?
		}
		else
		{
			?>
			<div class="p" <? echo (($pm->singlelogin === 0) ? '':'hidden' ); ?>>
				<input class="logininput" type="password" placeholder="密码" name="password" style=" margin:0 0 20px 30px; padding-left:15px; line-height:26px; border:1px #cccccc solid; background-color:#fff; height:26px; width:180px;" />&nbsp;
			</div>
			<?
			if (!empty($_SESSION['logout']) || !empty($_SESSION['last_page']))
			{
			?>
				
			<?
			}
			?>
			<div style='clear:left;'></div>
            <div class="p" <? echo (($pm->singlelogin === 0) ? '':'hidden' ); ?>>
					<span>
                    <label for="resume" style=" margin-left:30px; color:#808080;">恢复会话&nbsp;</label>
                    <input id="resume" name="resume" type="checkbox" value="" <? echo (empty($pm->loggedOut) ?	' checked="checked" ' : ''); ?> />
                    </span>
                    
				</div>
			
			<div class="p" hidden="hidden">
                <label for="resume">同步备份集?&nbsp;</label>
                <input id="sync_backupset" name="sync_backupset" type="checkbox" value=""  />
			</div>
            <div class="p" <? echo (($pm->singlelogin === 0) ? '':'hidden' ); ?>>
                <input id="login_button" type="submit" name="submit"  value="登录" style="margin:10px 26px 15px 0; width:200px; height:40px; font-size: 20px;                           font-weight: bold; letter-spacing:10px;   color:#FFF" />

            </div>
	
			<input id="javascript_switch" type="hidden" name="JS_SWITCH" value="JS_OFF" />
			<script>gebi("javascript_switch").value = 'JS_ON'</script>
			<div style='clear:left;'></div>
			<div class="p" <? echo (($pm->singlelogin === 0) ? '':'hidden' ); ?>>
				<label style=" margin:0 0 0 27px; color:#ff7c31 " ><small>重置密码请登陆云管理平台或者联系管理员</small></label>
			</div>
			<?
		}
		?>
	<?}  ?>
		<div id="wocloudLoginMessageBox" style="clear:left;">
			<noscript>
			<div class="wocloudMessageBox">
			<div class="wocloudMsgWarnErr wocloudUserErrorsText wocloudIconError">&nbsp;&nbsp;
				请在访问该页面前启用浏览器<br />
			</div>
			</div>
			</noscript>
		<?
			if (isset($_GET['timeout']))
				$pm->addError('登陆超时，请从云平台登陆后跳转.');
			ZMC_Loader::renderTemplate('MessageBox', $pm);
		?>
		</div>
		<div style='clear:left;'></div>
	</div>
	
</div>


</form>
<div class="sf-footer-copyright">
			Copyright  &copy;&nbsp; <?= ZMC::dateNow(true); ?> <a href="http://www.wocloud.cn" target="_blank">ChinaUnicom, Inc.</a> 
			<br>
			All Rights Reserved.
			<br>
</div>

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
	
