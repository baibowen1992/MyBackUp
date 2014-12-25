<?














global $pm;
?>
<div style="border-bottom:1px solid; background-color:white;">
	<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="137">
				<a href="http://www.zmanda.com"><img 
					src="/images/global/<?= $pm->logo ?>"
					alt="Zmanda - Open Source Backup" 
					title="Zmanda - Open Source Backup"
					width="194" 
					height="56" 
					border="0" 
				/></a>
			</td>
				<?
foreach(array('Backup' => 78, 'Vault' => 58, 'Monitor' => 79, 'Report' => 72, 'Restore' => 79, 'Admin' => 70) as $Tab => $width)
{
	$tab = strtolower($Tab);
	echo "\n<td width='$width' valign='bottom'>";
	if ($pm->page_info !== $Tab)
	{
		$img = "<img src='/images/navigation/{$tab}_up.gif' alt='$Tab' width='$width' height='25' border='0' id='$Tab' /></a></td>";
		if (ZMC_HeaderFooter::$instance->getUrl($Tab) !== '')
			echo "<a href='{$_SESSION['tab'][$Tab]}' onmouseout='MM_swapImgRestore()' onmouseover=\"MM_swapImage('$Tab','','/images/navigation/{$tab}_over.gif',1)\">$img</a>";
		else
			echo $img;
	}
	else
		echo "<img src='/images/navigation/{$tab}_down.gif' alt='$Tab' width='$width' height='25' /></td>\n";
}
?>
		</tr>
	</table>

<div style='height:22px; background:url("/images/navigation/subnav.png") repeat-x bottom;'></div>

<div class="headerLinks" style="position:absolute; top:61px; left:15px; z-index:999"><?
	if (!empty($pm->about_url))
		echo '<a href="' . $pm->about_url, '">About</a>&nbsp;|&nbsp;';
	echo '<a href="' . rtrim($pm->wiki, '/') . '" target="_blank">User Guide</a>';
	if (ZMC::$registry->short_name !== 'ZRM')
		echo '&nbsp;|&nbsp;<a href="', ZMC::$registry->links['feedback'], '">Feedback</a>';
?>
</div><!-- headerLinks -->

<div style='position:absolute; top:8px; right:8px;'>

<a href="<?= ZMC::$registry->links['shopping'] ?>"  onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Purchase','','/images/icons/icon_cart_over.gif',0)" target="_blank" >
		<img 	src="/images/icons/icon_cart_up.gif" width="33" height="28" border="0" 
				alt="Purchase subscription(s)" title="Purchase subscription(s)" name="Purchase"/>
	</a>
	<a href="http://wiki.zmanda.com/index.php/Main_Page" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Wiki','','/images/icons/icon_wiki_over.gif',1)" target="_blank" >
		<img 	src="/images/icons/icon_wiki_up.gif" width="33" height="28" border="0" 
				alt="Zmanda Wiki" title="Zmanda Wiki" name="Wiki" />
	</a>
	<a href="http://forums.zmanda.com/" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Forums','','/images/icons/icon_forum_over.gif',1)" target="_blank" >
		<img 	src="/images/icons/icon_forum_up.gif" width="33" height="28" border="0" 
				alt="Zmanda Forums" title="Zmanda Forums" name="Forums"/>
	</a>
<a href="<?= ZMC::$registry->links['home'] ?>" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Network','','/images/icons/icon_network_over.gif',1)" target="_blank">
		<img	src="/images/icons/icon_network_up.gif" width="33" height="28" border="0" 
				alt="Zmanda Network" title="Zmanda Network" name="Network"/>
	</a>
	<a href="<?= ZMC::$registry->links['download'] ?>" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Website','','/images/icons/icon_download_over.gif',1)" target="_blank">
		<img 	src="/images/icons/icon_download_up.gif" width="35" height="28" border="0" 
				alt="Download Products" title="Download Products" name="Website"/>
	</a>
	<div class="links">
	<? echo '<a href="', $pm->admin_users_url, '?edit_id=', $_SESSION['user_id'], '&amp;action=edit">', $_SESSION['user'], '</a>',
		'&nbsp;&nbsp;|&nbsp;&nbsp;<a href="', $pm->login_url ?>?logout=1&login=<?= $pm->short_name ?>">Log Out</a>
	</div>
</div>
