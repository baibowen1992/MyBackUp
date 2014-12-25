<?













global $pm;
function get_prefs_icon($key, $class = '')
{
	global $pm;
	if (!empty($pm->flagErrors[$key]))
		return "class='zmcLabelError $class'";
	if (!empty($pm->flagWarnings[$key]))
		return "class='zmcLabelWarning $class'";
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
	$recommendYes = $bconfig[Zmc_Admin_Preferences::RECOMMENDED] ? '<b>Yes*</b>' : 'Yes';
	$recommendNo  = $bconfig[Zmc_Admin_Preferences::RECOMMENDED] ? 'No' : '<b>No*</b>';
	$class = 'class="wrap"';
	echo <<<EOD
		<div class="p">
			<label $class $icon>$prompt</label>
			<input name="$key" type="radio" value="Yes" id="{$key}_yes" $on />
			<label for="{$key}_yes" class="zmcShortestLabel">$recommendYes</label>
			&nbsp;&nbsp;&nbsp;
			<input name="$key" type="radio" value="No"  id="{$key}_no"  $off  />
			<label for="{$key}_no" class="zmcShortestLabel">$recommendNo</label>
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
<div class="zmcLeftWindow" style="width:500px">
	<? ZMC::titleHelpBar($pm, 'Set Global System Defaults') ?>
    <form method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapper zmcShortestInput zmcLongestLabel">
		<img class="zmcWindowBackgroundimageRight" src="/images/3.1/settings.png" />
		<div class="p">
			<label>Maximum Login Timeout Allowed:</label>
			<input 
				type="text" 
				name="SessionTimeout" 
				title="Enter default maximum session timeout in minutes (up to 10,000)" 
				size="5"
				maxlength="5"
				value = "<?= $pm->sessionTimeout ?>"
				<?  if (!ZMC_User::hasRole('Administrator')) echo ' disabled="disabled" ';
						echo get_prefs_icon('SessionTimeout');
				?>
			/> minutes (5-9999)
		</div><div class="p">
			<label>License Expiration Warning:</label>
			<input type="text" name="license_expiration_warning_weeks" value="<?= ZMC::$registry['license_expiration_warning_weeks'] ?>" <?= get_prefs_icon('license_expiration_warning_weeks'); ?> /> (default: 3 weeks before)
		</div>
<?
			if (($sha = sha1_file($fn = ZMC::$registry->cnf->apache_conf_path . '/certs/ZMC-server.crt')) === '6aa47d9203dc06a3fca0806cf151b999d18dc05f')
				echo '<div class="p zmcUserWarningsText" style="padding:20px;"><img src="/images/global/calendar/icon_calendar_warning.gif" /> HTTPS: The pre-packaged ZMC web server certificate is not secure, because all Zmanda customer installations initially have the same certificate. <a href="' . ZMC::$registry->wiki . 'Download#section_3">See ZMC manual for details.</a><br />See /opt/zmanda/amanda/bin/zmc_create_certificate.sh to create your own self-signed certificate.</div>';
?>
		<div class="p">
			<label>HTTPS Port:</label>
			<input type="text" name="httpsPort" disabled="true" value="<?= ZMC::$registry->apache_https_port ?>" <?= get_prefs_icon("httpsPort"); ?> />
		</div>
		<div class="p">
			<label>HTTPS IF:</label>
			<input type="text" name="httpsIf" disabled="true" value="<?= ZMC::$registry->apache_https_if ?>" <?= get_prefs_icon("httpsIf"); ?> />
		</div><div class="p">
			<label>HTTP Port:</label>
			<input type="text" name="httpPort" disabled="true" value="<?= ZMC::$registry->apache_http_port ?>" <?= get_prefs_icon("httpPort"); ?> />
		</div><div class="p">
			<label>HTTP IF:</label>
			<input type="text" name="httpIf" disabled="true" value="<?= ZMC::$registry->apache_http_if ?>" <?= get_prefs_icon("httpIf"); ?> />
		</div><div class="p">
			<label>MySQL Port:</label>
			<input type="text" name="httpPort" disabled="true" value="none" /> (using local socket)
		</div>
		<div>
			<label>&nbsp;</label>
			<label><b>* = Recommended</b></label>
		</div>
		<?
			foreach($pm->boolConfigs as $key => $bconfig)
				if (($key !== 'qa_mode' && $key !== 'dev_only') || (ZMC::$registry->qa_team === true))
					show_boolean_toggle($pm, $bconfig, $key);
		?>
		<div style="clear:both"></div>
	</div><!-- zmcFormWrapper -->
		<? ob_start(); ?>
		<div class="p">
			<label>Maximum Files to Display:</label>
			<input type="text" name="display_max_files" value="<?= ZMC::$registry['display_max_files'] ?>" <?= get_prefs_icon("display_max_files"); ?> /> (default: 10,000,000)
		</div><div class="p">
			<label>Critical Space Threshold:</label>
			<input type="text" name="critical_disk_space_threshold" value="<?= ZMC::$registry['critical_disk_space_threshold'] ?>" <?= get_prefs_icon("critical_disk_space_threshold"); ?> /> (default: 10% disk free space)
		</div><div class="p">
			<label>Warning Space Threshold:</label>
			<input type="text" name="warning_disk_space_threshold"  value="<?= ZMC::$registry['warning_disk_space_threshold'] ?>" <?= get_prefs_icon("warning_disk_space_threshold"); ?> /> (default: 15% disk free space)
		</div><div class="p">
			<label>Space Check Frequency:</label>
			<input type="text" name="disk_space_check_frequency"  value="<?= ZMC::$registry['disk_space_check_frequency'] ?>" <?= get_prefs_icon("disk_space_check_frequency"); ?> /> (recommended: 60 seconds)
		</div><div class="p">
			<label>DB Query Timeout:</label>
			<input type="text" name="sql_time_limit"  value="<?= ZMC::$registry['sql_time_limit'] ?>" <?= get_prefs_icon("sql_time_limit"); ?> /> (recommended: 300-600 seconds)
		</div><div class="p">
			<label>Ultra-Short UI Task Timeout:</label>
			<input type="text" name="proc_open_ultrashort_timeout"  value="<?= ZMC::$registry['proc_open_ultrashort_timeout'] ?>" <?= get_prefs_icon("proc_open_ultrashort_timeout"); ?> /> (recommended: 15 seconds)
		</div><div class="p">
			<label>Short UI Task Timeout:</label>
			<input type="text" name="proc_open_short_timeout"  value="<?= ZMC::$registry['proc_open_short_timeout'] ?>" <?= get_prefs_icon("proc_open_short_timeout"); ?> /> (recommended: 60 seconds)
		</div><div class="p">
			<label>Long UI Task Timeout:</label>
			<input type="text" name="proc_open_long_timeout"  value="<?= ZMC::$registry['proc_open_long_timeout'] ?>" <?= get_prefs_icon("proc_open_long_timeout"); ?> /> (recommended: 60 seconds)
		</div><div class="p">
			<label>Cloud: Cache List of Buckets:</label>
			<input type="text" name="cache_cloud_list_of_buckets"  value="<?= ZMC::$registry['cache_cloud_list_of_buckets'] ?>" <?= get_prefs_icon("cache_cloud_list_of_buckets"); ?> /> (recommended: 86400 seconds)
		</div><div class="p">
			<label>Max. Backup Split-Cache RAM:</label>
			<input type="text" name="part_cache_max_size"  value="<?= ZMC::$registry['part_cache_max_size'] ?>" <?= get_prefs_icon("part_cache_max_size"); ?> /> (recommended: > 256 MiB)
		</div><div class="p">
			<label>Registry Key:</label>
			<input type="text" name="registry_key" />
			<div class="zmcAfter">Value:</div>
			<input type="text" name="registry_value" />
		</div><!-- div class="p">
			<label>Max Network Bandwidth:</label>
			<input type="text" name="netusage"  value="<?= ZMC::$registry['netusage'] ?>" /> (recommended: 8000 Kbps)
		</div -->
		<!-- fieldset><legend>PHP Settings</legend -->
			<div class="p">
				<label>Files/Directories have <br /> international characters?</label>
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
				<label>Time Zone (Region/City)</label>
				<input type="text" name="date_timezone" value="<?= ZMC::$registry['date_timezone'] ?>" <?= get_prefs_icon("date_timezone"); ?> /> e.g. America/Los Angeles or Europe/London
			</div>
			<div class="p">
				<label>PHP Memory Warning:</label>
				<input type="text" name="low_memory" value="<?= ZMC::$registry['low_memory'] ?>" <?= get_prefs_icon('low_memory'); ?> /> (default: 0.75 = warn if > 75%)
			</div>
			<div class="p">
				<label>Per-Process Memory Limit</label>
				<input type="text" name="php_memory_limit" value="<?= ZMC::$registry['php_memory_limit'] ?>" <?= get_prefs_icon("php_memory_limit"); ?> />MiB  (recommended: 128 MiB)
			</div>
			<div class="p">
				<label>Execution Time Limit</label>
				<input type="text" name="max_execution_time" value="<?= ZMC::$registry['max_execution_time'] ?>" <?= get_prefs_icon("max_execution_time"); ?> />seconds (recommended: 300)
			</div>
		<!-- /fieldset -->
		<?
			$pm->form_advanced_html = ob_get_clean();
			$pm->form_type['advanced_form_classes'] = 'zmcLongerLabel zmcShortestInput';
			ZMC_Loader::renderTemplate('formAdvanced', $pm);
		?>
	<div class="zmcButtonBar">
		<input type="submit" value="Apply" name="action" />
		<input type="reset" value="Cancel" name="action" />
		<input type="submit" value="Reset to Defaults" name="action" class="zmcButtonsLeft" />
		<input type="hidden" name="form" value="globalDefaults" />
	</div>
	</form>
</div><!-- zmcLeftWindow -->
<? }  ?>


<div class="zmcLeftWindow" style="width:456px">
	<? ZMC::titleHelpBar($pm, 'User Preferences') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapper zmcUltraShortInput"><? if($pm->app !== 'ZRM') { ?>
		<div class="p">
			<label>Reset Help Pages?</label>
			<input name="show_help_pages" type="radio" value="Yes" id="resetYes" /><label class="zmcShortestLabel" for="resetYes">Yes</label>
			<input name="show_help_pages" type="radio" value="No" id="resetNo" checked /><label class="zmcShortestLabel" for="resetNo">No</label>
		</div>
		<? }  ?>
		<div class="p">
			<label>User Login Timeout:</label>
			<input 
				type="text" 
				name="UserSessionTimeout" 
				title="Enter session timeout in minutes (0 for system default, <?= $pm->sessionTimeout ?> minutes maximum)"
				size="5"
				maxlength="5"
				value = "<? if (!empty($pm->userSessionTimeout)) echo $pm->userSessionTimeout > $pm->sessionTimeout ? $pm->sessionTimeout : $pm->userSessionTimeout ?>"
				<?= get_prefs_icon("UserSessionTimeout"); ?>
			/>minutes (blank to use system global default)
		</div>
	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar">
		<input type="submit" value="Apply" name="action"/>
		<input type="reset" value="Cancel" name="action" />
		<input type="hidden" name="form" value="userPreferences" />
	</div>
	</form>
</div><!-- zmcLeftWindow -->
<div class="zmcLeftWindow" style="width:456px">
<!--<div class="zmcLeftWindow zmcFormWrapper " style="width:456px; border-right-width: 1px; margin-right: 10px; margin-left: 10px; padding: 0px; margin-bottom: 0px; border-left-width: 1px; left: 1px;">-->
	<? ZMC::titleHelpBar($pm, 'Global Input Defaults') ?>
	<form method="post" action="<?= $pm->url ?>">
	<div class="zmcFormWrapper zmcLongestLabel">
		<div class="p">
			<label>Vtape Device Path: </label>
			<input 
				type="text" 
				name="default_vtape_device_path" 
				title="Enter default device patch location"
				value = "<?=$default_vtape_device_path ?>"/>
		</div>
		<div class="p">
			<label>Holding Disk Path: </label>
			<input 
				type="text" 
				name="default_holding_disk_path" 
				title="Enter default staging area location"
				value = "<?=$default_holding_disk_path ?>"/>
		</div>
		<div class="p">
			<label>VMWare Temp Restore Path: </label>
			<input 
				type="text" 
				name="default_vmware_restore_temp_path" 
				title="Enter default vmware restore temperory path location"
				value = "<?=$default_vmware_restore_temp_path ?>"/>
		</div>
</div>
	<div class="zmcButtonBar">
		<input type="submit" value="Apply" name="action"/>
		<input type="reset" value="Cancel" name="action" />
		<input type="hidden" name="form" value="globalInputDefaults" />
	</div>
	</form>
</div><!-- zmcLeftWindow -->

