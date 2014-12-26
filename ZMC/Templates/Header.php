<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 8]> <html xml:lang="en" class="ie ie7" lang="UTF-8"> <![endif]-->
<!--[if IE 8]>    <html xml:lang="en" class="ie ie8" lang="UTF-8"> <![endif]-->
<!--[if IE 9]> <html xml:lang="en" class="modern_browser ie9" lang="UTF-8"> <![endif]-->
<!--[if gt IE 9]><!--> <html xml:lang="en" class="modern_browser" lang="UTF-8"> <!--<![endif]-->
<?
//zhoulin-nav 201409191604
//head  body所在，，首页开始的地方















global $pm;
?>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title><?= $pm->title; ?></title>
	<script src="<?= (ZMC::$registry->scripts)? ZMC::$registry->scripts: "/scripts/" ?>wocloud.js"></script>
	<link rel="shortcut icon" href="/images/icons/favicon.ico" type="image/x-icon" />
    <link href="/scripts/introjs/introjs.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= (ZMC::$registry->scripts)? ZMC::$registry->scripts: "/scripts/" ?>wocloud.css" type="text/css" />
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

	<div style='position:absolute; font-size:8pt; color:#E0E0E0; z-index:999; top:0; left:250px;'>
<?
		echo "</div>\n";
	}
	return;
}

$set = ((empty($_SESSION) || empty($_SESSION['configurationName'])) ? '':"\r\nBackup Set: $_SESSION[configurationName]");
//echo '

//<div style="position:absolute; z-index:999; top:75px; right:0px; filter:alpha(opacity=50); opacity:0.5; background-color:yellow;">',
//	ZMC::$registry->zmc_svn_info, ZMC::$registry->svn_overlay,
//"</div>\n";
