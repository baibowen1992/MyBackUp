<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 8]> <html xml:lang="en" class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8]>    <html xml:lang="en" class="ie ie8" lang="en"> <![endif]-->
<!--[if IE 9]> <html xml:lang="en" class="modern_browser ie9" lang="en"> <![endif]-->
<!--[if gt IE 9]><!--> <html xml:lang="en" class="modern_browser" lang="en"> <!--<![endif]-->
<?

















global $pm;
?>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title><?= $pm->title; ?></title>
	<script src="<?= (ZMC::$registry->scripts)? ZMC::$registry->scripts: "/scripts/" ?>zmc.js"></script>
	<link rel="shortcut icon" href="/images/icons/favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="<?= (ZMC::$registry->scripts)? ZMC::$registry->scripts: "/scripts/" ?>zmc.css" type="text/css" />
	<?
		
		if (!empty($pm->xhtml_head))
			echo $pm->xhtml_head, "\n";
		if (!empty($_REQUEST['auto_refresh_page']))
			echo '<meta http-equiv="refresh" content="', max(5, intval($_REQUEST['auto_refresh_page'])), '" />';
		if (($pm->tombstone === 'Report') || ($pm->tombstone === 'Verify') || $pm->tombstone === 'Vault')
			echo '<link rel="stylesheet" href="', ZMC::$registry->scripts, 'backup-calendar.css" type="text/css" />';
	?>
</head>
<body id="body" class="yui-skin-sam" bgcolor="#FFFFFF">
<?
if (ZMC::$registry && class_exists('ZMC_HeaderFooter', false) && strncmp($_SERVER['SCRIPT_NAME'], ZMC_HeaderFooter::$instance->getUrl('Login'), 16))
	echo '<script type="text/javascript">var yuiComplete = false</script>';

if ($pm->subnav === 'Login')
	return;
if (!ZMC::$registry->qa_mode)
{
	if(!preg_match('/index.php/', $_SERVER['PHP_SELF'])){
?>
	<div style='position:absolute; font-size:8pt; color:#E0E0E0; z-index:999; top:5px; left:345px;'>
		<form method='get' action='https://www.google.com/search'>
		<input type='text' class='zmcShorterInput' name='q' value='' />
			<input type='submit' value='Search Docs' onclick="
				this.form.q.value += ' site:docs.zmanda.com &quot;Project:Amanda_Enterprise_3.3&quot;'
				return true;
			" />
		</form>
	</div>
	<div style='position:absolute; font-size:8pt; color:#E0E0E0; z-index:999; top:0; left:250px;'>
<?
		echo $pm->product_datestamp, $s = ZMC::$registry->svn_overlay, "</div>\n";
	}
	return;
}

$set = ((empty($_SESSION) || empty($_SESSION['configurationName'])) ? '':"\r\nBackup Set: $_SESSION[configurationName]");
echo '
<div style="position:absolute; font-weight:bold; z-index:999; top:0; left:250px;">
	<span style="background-color:#F66">
	<a href="http://bugs.zmanda.com/enter_bug.cgi?product=Amanda%20enterprise%20edition&component=ZMC&comment=', 
		urlencode("Bug reported against:\r\n  ZMC " . ZMC::$registry->zmc_svn_info . "\r\n  Amanda "
		. ZMC::$registry->amanda_svn_info . "\r\n  Test Server: " . ZMC::getUrl() . ' ' . ZMC::getServerIp() . "$set\r\n  Browser: "), '"
			onClick="this.href += escape(navigator.userAgent)">&nbsp;QA: Report ZMC Bugs Here&nbsp;</a>
	</span>
	&nbsp;', $pm->product_datestamp, '
</div>
<div style="position:absolute; z-index:999; top:75px; right:0px; filter:alpha(opacity=50); opacity:0.5; background-color:yellow;">',
	ZMC::$registry->zmc_svn_info, ZMC::$registry->svn_overlay,
"</div>\n";
