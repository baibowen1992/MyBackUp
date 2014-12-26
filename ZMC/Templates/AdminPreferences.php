<?













global $pm;
function get_prefs_icon($key, $class = '')
{
	global $pm;
	if (!empty($pm->flagErrors[$key]))
		return "class='wocloudLabelError $class'";
	if (!empty($pm->flagWarnings[$key]))
		return "class='wocloudLabelWarning $class'";
	if (empty($class))
		return '';
	return "class=\"$class\"";
}
	
function show_boolean_toggle($pm, $bconfig, $key)
{
	$on = $pm->offsetGet($key . '_on');
	$off = $pm->offsetGet($key . '_off');
	if ($on === null && $off === null)
		$off = 'checked';
	$icon = get_prefs_icon($key, '');
	$prompt = $bconfig[Zmc_Admin_Preferences::PROMPT];
	$recommendYes = $bconfig[Zmc_Admin_Preferences::RECOMMENDED] ? '<b>是*</b>' : '是';
	$recommendNo  = $bconfig[Zmc_Admin_Preferences::RECOMMENDED] ? '否' : '<b>否*</b>';
	$class = 'class="wrap"';
	echo <<<EOD
		<div class="p">
			<label $class $icon>$prompt</label>
			<input name="$key" type="radio" value="Yes" id="{$key}_yes" $on />
			<label for="{$key}_yes" class="wocloudShortestLabel">$recommendYes</label>
			&nbsp;&nbsp;&nbsp;
			<input name="$key" type="radio" value="No"  id="{$key}_no"  $off  />
			<label for="{$key}_no" class="wocloudShortestLabel">$recommendNo</label>
		</div>
EOD;
}

if(isset($_POST['default_vtape_device_path']))
	$default_vtape_device_path = $_POST['default_vtape_device_path'];
elseif(!empty(ZMC::$registry['default_vtape_device_path']))
	$default_vtape_device_path =ZMC::$registry['default_vtape_device_path'];
else
	$default_vtape_device_path  = "/var/lib/amanda/vtapes/";
	
if(isset($_POST['default_holding_disk_path']))
	$default_holding_disk_path = $_POST['default_holding_disk_path'];
elseif(!empty(ZMC::$registry['default_holding_disk_path']))
	$default_holding_disk_path =ZMC::$registry['default_holding_disk_path'];
else
	$default_holding_disk_path  = "/var/lib/amanda/vtapes/";
	
if(isset($_POST['default_vmware_restore_temp_path']))
	$default_vmware_restore_temp_path = $_POST['default_vmware_restore_temp_path'];
elseif(!empty(ZMC::$registry['default_vmware_restore_temp_path']))
	$default_vmware_restore_temp_path =ZMC::$registry['default_vmware_restore_temp_path'];
else
	$default_vmware_restore_temp_path  = "/tmp/amanda/";
	
?>

<? if (ZMC_User::hasRole('Administrator')) { ?>
<div class="wocloudLeftWindow" style="width:500px">
	<? ZMC::titleHelpBar($pm, '设置全局系统默认参数') ?>
    <form method="post" action="<?= $pm->url ?>">
	<div class="wocloudFormWrapper wocloudShortestInput wocloudLongestLabel">
		<img class="wocloudWindowBackgroundimageRight" src="/images/3.1/settings.png" />
		<div class="p">
			<label>登陆会话保留时间：</label>
			<input 
				type="text" 
				name="SessionTimeout" 
				title="输入默认最大会话保留时间(不超过10,000)" 
				size="5"
				maxlength="5"
				value = "<?= $pm->sessionTimeout ?>"
				<?  if (!ZMC_User::hasRole('Administrator')) echo ' disabled="disabled" ';
						echo get_prefs_icon('SessionTimeout');
				?>
			/> 分钟 (5-9999)
		</div><div class="p" hidden="hidden">
			<label>许可证到期警告:</label>
			<input type="text" name="license_expiration_warning_weeks" value="<?= ZMC::$registry['license_expiration_warning_weeks'] ?>" <?= get_prefs_icon('license_expiration_warning_weeks'); ?> /> (默认：3个星期前)
		</div>
<?
			if (($sha = sha1_file($fn = ZMC::$registry->cnf->apache_conf_path . '/certs/ZMC-server.crt')) === '6aa47d9203dc06a3fca0806cf151b999d18dc05f')
				echo '<div class="p wocloudUserWarningsText" style="padding:20px;"><img src="/images/global/calendar/icon_calendar_warning.gif" /> HTTPS:因为所有使用云备份的用户都使用一样的初始证书，因而证书不安全。请生成自签名的用户独立的证书。 </div>';
?>
		<div class="p">
			<label>HTTPS端口:</label>
			<input type="text" name="httpsPort" disabled="true" value="<?= ZMC::$registry->apache_https_port ?>" <?= get_prefs_icon("httpsPort"); ?> />
		</div>
		<div class="p">
			<label>HTTPS IF:</label>
			<input type="text" name="httpsIf" disabled="true" value="<?= ZMC::$registry->apache_https_if ?>" <?= get_prefs_icon("httpsIf"); ?> />
		</div><div class="p">
			<label>HTTP端口:</label>
			<input type="text" name="httpPort" disabled="true" value="<?= ZMC::$registry->apache_http_port ?>" <?= get_prefs_icon("httpPort"); ?> />
		</div><div class="p">
			<label>HTTP IF:</label>
			<input type="text" name="httpIf" disabled="true" value="<?= ZMC::$registry->apache_http_if ?>" <?= get_prefs_icon("httpIf"); ?> />
		</div><div class="p">
			<label>MySQL端口:</label>
			<input type="text" name="httpPort" disabled="true" value="none" /> ( 使用本地 socket)
		</div>
		<div>
			<label>&nbsp;</label>
			<label><b>* = 推荐</b></label>
		</div>
		<?
			foreach($pm->boolConfigs as $key => $bconfig)
				if (($key !== 'qa_mode' && $key !== 'dev_only') || (ZMC::$registry->qa_team === true))
					show_boolean_toggle($pm, $bconfig, $key);
		?>
		<div style="clear:both"></div>
	</div><!-- wocloudFormWrapper -->
		<? ob_start(); ?>
		<div class="p">
			<label>最多显示的文件数：</label>
			<input type="text" name="display_max_files" value="<?= ZMC::$registry['display_max_files'] ?>" <?= get_prefs_icon("display_max_files"); ?> /> (默认值: 10,000,000)
		</div><div class="p">
			<label>临界空间阀值：</label>
			<input type="text" name="critical_disk_space_threshold" value="<?= ZMC::$registry['critical_disk_space_threshold'] ?>" <?= get_prefs_icon("critical_disk_space_threshold"); ?> /> (默认值: 10% 剩余空间)
		</div><div class="p">
			<label>空间警告阀值：</label>
			<input type="text" name="warning_disk_space_threshold"  value="<?= ZMC::$registry['warning_disk_space_threshold'] ?>" <?= get_prefs_icon("warning_disk_space_threshold"); ?> /> (默认值: 15% 剩余空间)
		</div><div class="p">
			<label>空间检测频率：</label>
			<input type="text" name="disk_space_check_frequency"  value="<?= ZMC::$registry['disk_space_check_frequency'] ?>" <?= get_prefs_icon("disk_space_check_frequency"); ?> /> (推荐: 60 秒)
		</div><div class="p">
			<label>数据查询超时时间</label>
			<input type="text" name="sql_time_limit"  value="<?= ZMC::$registry['sql_time_limit'] ?>" <?= get_prefs_icon("sql_time_limit"); ?> /> (推荐: 300-600 秒)
		</div><div class="p" style="display:none">
			<label>超短界面任务超时时间：</label>
			<input type="text" name="proc_open_ultrashort_timeout"  value="<?= ZMC::$registry['proc_open_ultrashort_timeout'] ?>" <?= get_prefs_icon("proc_open_ultrashort_timeout"); ?> /> (推荐: 15 秒)
		</div><div class="p" style="display:none">
			<label>短界面任务超时时间：</label>
			<input type="text" name="proc_open_short_timeout"  value="<?= ZMC::$registry['proc_open_short_timeout'] ?>" <?= get_prefs_icon("proc_open_short_timeout"); ?> /> (推荐: 60 秒)
		</div><div class="p" style="display:none">
			<label>长界面任务超时时间：</label>
			<input type="text" name="proc_open_long_timeout"  value="<?= ZMC::$registry['proc_open_long_timeout'] ?>" <?= get_prefs_icon("proc_open_long_timeout"); ?> /> (推荐: 60 秒)
		</div><div class="p" style="display:none">
			<label>云端bucket缓存列表：</label>
			<input type="text" name="cache_cloud_list_of_buckets"  value="<?= ZMC::$registry['cache_cloud_list_of_buckets'] ?>" <?= get_prefs_icon("cache_cloud_list_of_buckets"); ?> />  (推荐: 86400 秒)
		</div><div class="p">
			<label>备份分割最大缓存</label>
			<input type="text" name="part_cache_max_size"  value="<?= ZMC::$registry['part_cache_max_size'] ?>" <?= get_prefs_icon("part_cache_max_size"); ?> /> (推荐: > 256 MiB)
		</div><div class="p" style="display:none">
			<label>注册码:</label>
			<input type="text" name="registry_key" />
			<div class="wocloudAfter">值:</div>
			<input type="text" name="registry_value" />
		</div><!-- div class="p">
			<label>Max Network Bandwidth:</label>
			<input type="text" name="netusage"  value="<?= ZMC::$registry['netusage'] ?>" /> (recommended: 8000 Kbps)
		</div -->
		<!-- fieldset><legend>PHP Settings</legend -->
			<div class="p">
				<label>文件或目录 <br />有国际字符?</label>
				<?
					$locales = file(ZMC::$registry->etc_zmanda_product.'locales.available', FILE_IGNORE_NEW_LINES);
					$checked = '';
					if(in_array('UTF-8', $locales)|| in_array('UTF8', $locales) || in_array('en_US.utf8', $locales)){
						$checked = (ZMC::$registry->locale_sort == "UTF8" ? "checked": '');
						$dis = '';
					}else{
						$checked = '';
						$dis = "disabled";
					 }
					echo '<input type="checkbox" name="locale_sort" value="UTF8" '.$checked.' '.$dis .' id="UTF-8">';
				?>
			</div>
			<div class="p">
				<label>时区 (地区/城市)</label>
				<input type="text" name="date_timezone" value="<?= ZMC::$registry['date_timezone'] ?>" <?= get_prefs_icon("date_timezone"); ?> /> 如：中国/北京 或者中国/上海
			</div>
			<div class="p">
				<label>PHP 内存警告：</label>
				<input type="text" name="low_memory" value="<?= ZMC::$registry['low_memory'] ?>" <?= get_prefs_icon('low_memory'); ?> /> (默认值: 0.75即当大于75%时报警)
			</div>
			<div class="p">
				<label>每个进程使用内存限制</label>
				<input type="text" name="php_memory_limit" value="<?= ZMC::$registry['php_memory_limit'] ?>" <?= get_prefs_icon("php_memory_limit"); ?> />MiB  (推荐值: 128 MiB)
			</div>
			<div class="p">
				<label>执行时间限制</label>
				<input type="text" name="max_execution_time" value="<?= ZMC::$registry['max_execution_time'] ?>" <?= get_prefs_icon("max_execution_time"); ?> />秒 (推荐值: 300)
			</div>
		<!-- /fieldset -->
		<?
			$pm->form_advanced_html = ob_get_clean();
			$pm->form_type['advanced_form_classes'] = 'wocloudLongerLabel wocloudShortestInput';
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
		?>
	<div class="wocloudButtonBar">
		<button type="submit" value="Apply" name="action" />应用</button>
		<button type="reset" value="Cancel" name="action" />取消</button>
		<button type="submit" value="Reset to Defaults" name="action" class="wocloudButtonsLeft" />还原默认配置</button>
		<button hidden="hidden" name="form" value="globalDefaults" />
	</div>
	</form>
</div><!-- wocloudLeftWindow -->
<? }  ?>


<div class="wocloudLeftWindow" style="width:456px">
	<? ZMC::titleHelpBar($pm, '用户参数设置') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="wocloudFormWrapper wocloudUltraShortInput"><? if($pm->app !== 'ZRM') { ?>
		<div class="p">
			<label>重置帮助页面?</label>
			<input name="show_help_pages" type="radio" value="Yes" id="resetYes" /><label class="wocloudShortestLabel" for="resetYes">是</label>
			<input name="show_help_pages" type="radio" value="No" id="resetNo" checked /><label class="wocloudShortestLabel" for="resetNo">否</label>
		</div>
		<? }  ?>
		<div class="p">
			<label>用户登录超时时间：</label>
			<input 
				type="text" 
				name="UserSessionTimeout" 
				title=请输入会话保持时间(系统默认值为0, <?= $pm->sessionTimeout ?> 分钟是上线)"
				size="5"
				maxlength="5"
				value = "<? if (!empty($pm->userSessionTimeout)) echo $pm->userSessionTimeout > $pm->sessionTimeout ? $pm->sessionTimeout : $pm->userSessionTimeout ?>"
				<?= get_prefs_icon("UserSessionTimeout"); ?>
			/>分钟 (使用系统全局默认值请置空)
		</div>
	</div><!-- wocloudFormWrapper -->
	<div class="wocloudButtonBar">
        <button type="submit" value="Apply" name="action" />应用</button>
        <button type="reset" value="Cancel" name="action" />取消</button>
		<input hidden="hidden" name="form" value="userPreferences" />
	</div>
	</form>
</div><!-- wocloudLeftWindow -->
<div class="wocloudLeftWindow" style="width:456px">
<!--<div class="wocloudLeftWindow wocloudFormWrapper " style="width:456px; border-right-width: 1px; margin-right: 10px; margin-left: 10px; padding: 0px; margin-bottom: 0px; border-left-width: 1px; left: 1px;">-->
	<? ZMC::titleHelpBar($pm, '默认全局路径') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="wocloudFormWrapper wocloudLongestLabel">
		<div class="p" hidden="hidden">
			<label>虚拟磁带设备位置Vtape: </label>
			<input 
				type="text" 
				name="default_vtape_device_path" 
				title="请输入默认设备路径"
				value = "<?=$default_vtape_device_path ?>"/>
		</div>
		<div class="p">
			<label>临时缓存目录: </label>
			<input 
				type="text" 
				name="default_holding_disk_path" 
				title="Enter default staging area location"
				value = "<?=$default_holding_disk_path ?>"/>
		</div>
		<div class="p" hidden="hidden">
			<label>VMWare临时恢复路径: </label>
			<input 
				type="text" 
				name="default_vmware_restore_temp_path" 
				title="VMWare临时恢复路径"
				value = "<?=$default_vmware_restore_temp_path ?>"/>
		</div>
</div>
	<div class="wocloudButtonBar">
        <button type="submit" value="Apply" name="action" />应用</button>
        <button type="reset" value="Cancel" name="action" />取消</button>
		<input hidden="hidden"  name="form" value="globalInputDefaults" />
	</div>
	</form>
</div><!-- wocloudLeftWindow -->

