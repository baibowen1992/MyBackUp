<?
//zhoulin-nav 201409191551
//一级导航条及其上面的部分，帮助，关于，退出等












global $pm;
?>
<div style=" background-color:white;">
	<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="137">
				<a href="http://www.wocloud.cn"><img 
					src="/images/global/<?= $pm->logo ?>"
					alt="云备份" 
					title="云备份"
					width="194" 
					height="56" 
					border="0" 
				/></a>
			</td>
				<?
##foreach(array('Backup' => 78, 'Vault' => 58, 'Monitor' => 79, 'Report' => 72, 'Restore' => 79, 'Admin' => 70) as $Tab => $width)
if(ZMC_User::hasRole('Administrator')){ 
    foreach(array('Admin' => 87, 'Backup' => 87, 'Monitor' => 87, 'Report' => 87,'Restore' => 87) as $Tab => $width)
    {
        $tab = strtolower($Tab);
        echo "\n<td width='$width' valign='bottom'>";
        if ($pm->page_info !== $Tab)
        {
            $img = "<img src='/images/navigation/{$tab}_up.gif' alt='$Tab' width='$width' height='32' border='0' id='$Tab' /></a></td>";
            if (ZMC_HeaderFooter::$instance->getUrl($Tab) !== '')
                echo "<a href='{$_SESSION['tab'][$Tab]}' onmouseout='MM_swapImgRestore()' onmouseover=\"MM_swapImage('$Tab','','/images/navigation/{$tab}_over.gif',1)\">$img</a>";
            else
                echo $img;
        }
        else
            echo "<img src='/images/navigation/{$tab}_down.gif' alt='$Tab' width='$width' height='32' /></td>\n";
    }
}
else {
    foreach (array('Admin' => 87, 'Backup' => 87, 'Monitor' => 87, 'Restore' => 87) as $Tab => $width) {
        $tab = strtolower($Tab);
        echo "\n<td width='$width' valign='bottom'>";
        if ($pm->page_info !== $Tab) {
            $img = "<img src='/images/navigation/{$tab}_up.gif' alt='$Tab' width='$width' height='32' border='0' id='$Tab' /></a></td>";
            if (ZMC_HeaderFooter::$instance->getUrl($Tab) !== '')
                echo "<a href='{$_SESSION['tab'][$Tab]}' onmouseout='MM_swapImgRestore()' onmouseover=\"MM_swapImage('$Tab','','/images/navigation/{$tab}_over.gif',1)\">$img</a>";
            else
                echo $img;
        } else
            echo "<img src='/images/navigation/{$tab}_down.gif' alt='$Tab' width='$width' height='32' /></td>\n";
    }
}
?>
		</tr>
	</table>

<div style='height:42px;  background-color: #ffffff;border-top:1px #d1d1d1 solid;border-bottom:1px #d1d1d1 solid;'></div>

<div style="display: none" class="headerLinks" style="position:absolute; top:62px; margin-top:9px; color:#b9b9b9; left:15px; z-index:999"><?
	if (!empty($pm->about_url))
//		echo '<a href="' . $pm->about_url, '">关于云备份</a>';
		echo '<a>关于云备份</a>';
?>
</div><!-- headerLinks -->

<div style='position:absolute; top:8px; right:8px;'>
<<<<<<< HEAD
=======

>>>>>>> b49f5f035663e1341c6b53994186bbcdc199bd8b
	<div class="links">
    <a id="startButton"  href="javascript:void(0);">操作提醒</a>&nbsp;&nbsp;|&nbsp;&nbsp;
	<? echo '<a href="', $pm->admin_users_url, '?edit_id=', $_SESSION['user_id'], '&amp;action=edit">', $_SESSION['user'], '</a>',
		'&nbsp;&nbsp;|&nbsp;&nbsp;<a href="', $pm->login_url ?>?logout=1&login=<?= $pm->short_name ?>">退出</a>
	</div>
    <script type="text/javascript" src="/scripts/introjs/intro.js"></script>
    <script type="text/javascript">
        document.getElementById('startButton').onclick = function() {
            //window.location.href = 'ZMC_Backup_What?multipage=true';
            window.location.href = 'ZMC_Admin_BackupSets?yanshi=true';
        };
    </script>
</div>
