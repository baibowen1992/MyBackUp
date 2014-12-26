<?













global $pm;
$previous_url = ($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']: "https://".$_SERVER['HTTP_HOST']."/ZMC_Admin_BackupSets";
$previous_url = (preg_match('/cookies_checked/', $previous_url)) ? "https://".$_SERVER['HTTP_HOST']."/ZMC_Admin_BackupSets" : $previous_url;

?>
<form action="<?= $_SESSION['last_page'] ?>" method="post">
<div class="wocloudContentContainer" style="margin:100px auto 200px auto; width:498px">
	<div class="wocloudWindow" style="width:498px;">
		<? ZMC::titleHelpBar($pm, ZMC::$registry->long_name . ' ' . ZMC::$registry->svn->zmc_build_version); ?>
		<div style="margin-top:-8px;"><img src="/images/section/about/logo-zmc-<?= ZMC::$registry->short_name_lc ?>-product.png" alt="<?= ZMC::$registry->long_name ?>" title="<?= ZMC::$registry->long_name ?>" />
			<div style="position:absolute; top:34px; left:368px; color:#fff; font-size:11px;">
				当前系统版本号：<?= ZMC::$registry->svn->zmc_build_version ?>
				<br />
				当前版本发布时间： <br/><?= ZMC::$registry->svn->zmc_svn_build_date ?>
				<? if (ZMC::$registry->qa_team) echo "<br />\n", ZMC::$registry->zmc_svn_info; ?>
			</div>

			<div style="font-size:11px; position:absolute; top:225px; left:-20px; color:#808080; width:430px;">
				<div style="float:right;">Copyright &copy;&nbsp;2013-2014 <a href="http://www.wocloud.cn" target="_blank">ChinaUnicom, Inc.</a> All Rights Reserved.</div>
				<!--<a href="http://www.wocloud.cn/privacy_policy.html" target="_blank">Privacy Policy</a>-->
			</div>
		</div>

		<div class="wocloudButtonBar">
		<input type="button" value="OK" name="btnBack" onclick="document.location.href='<?=$previous_url?>';"/>
		</div>
	</div>

	<div style='padding:50px'>
	<?php
		if(count(ZMC::$registry->svn['zmc_patches']) > 0){
			$plural = (count(ZMC::$registry->svn['zmc_patches']) > 1)? "es": "";
			echo "<h2>Patch Information</h2><pre>Installed Patch$plural: ".implode(", ", ZMC::$registry->svn['zmc_patches'])."</pre>";
		}
		else{
			echo "<h2>Patch Information</h2><pre>No patch installed on this system.</pre>";
		}
		foreach($pm->versions as $key => $version)
			echo "<h2>$key</h2>\n<pre>$version</pre>\n";
	
		if (ZMC::$registry->platform !== 'windows')
		{
			echo "<h2>User Id</h2>\n<pre>", posix_getuid(), "\n\n</pre>\n";
			echo "<h2>Effective User Id</h2>\n<pre>", posix_geteuid(), "\n\n</pre>\n";
		}
	?>
	</div>
</form>
