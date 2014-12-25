<?
//zhoulin-monitor-event 201409191557












global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
$when = '任意时间';
if (!empty($_POST['when']) && ($_POST['when'][0] !== '-'))
	$when = "When: $_POST[when] $_POST[days] days<br />\n";

if ($pm->subnav !== 'alerts') { ?>
<div class="wocloudRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, '日志来源直方图'); ?>
	<div class="wocloudFormWrapperText">
	<?
		if (empty($_POST['severity']))
			echo "所有日志级别<br />\n";
		else
			echo "日志级别不低于 ", ZMC_Error::$severity2text[$_POST['severity']], "<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['subsystem'] as $subsystem => $row)
			echo "$row[mycount] : $subsystem<br />\n";
	?>
	</div>
</div>
<? } ?>



<div class="wocloudRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, '日志级别直方图'); ?>
	<div class="wocloudFormWrapperText">
	<?
		if (empty($_POST['subsystem']) || $_POST['subsystem'][0] === '-')
			echo "所有日志来源<br />\n";
		else
			echo "日志来源: $_POST[subsystem]<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['severity'] as $severity => $row)
			echo "$row[mycount] : ", ZMC_Error::$severity2text[$severity], "<br />\n";
	?>
	</div>
</div>



<? if ($pm->subnav !== 'alerts') { ?>
<div class="wocloudRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, '备份集日志直方图'); ?>
	<div class="wocloudFormWrapperText">
	<?
		if (empty($_POST['subsystem']) || $_POST['subsystem'][0] === '-')
			echo "所有日志来源<br />\n";
		else
			echo "日志来源: $_POST[subsystem]<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['configuration_id'] as $id => $row)
			echo "$row[mycount] : ", (empty($id) ? 'none' : ZMC_BackupSet::getName($id)), "<br />\n";
	?>
	</div>
</div>
<? } ?>



<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, '筛选 ' . ucFirst($pm->subnav)); ?>
	<div class="wocloudFormWrapper">
	<? if ($pm->subnav !== 'alerts') { ?>
		<div class="p">
			<label>日志类型</label>
			<select name="severity">
			<?
				foreach(array_merge(array('--no filter--' => 0), ZMC_Error::$error2severity) as $option => $level)
				echo "<option value='$level' ", ((isset($_POST['severity']) && $_POST['severity'] == $level) ? 'selected="selected"' : ''), ">$option</option>";
			?>
			</select>
		</div>
		<? } ?>
		<div class="p">
			<label>日志来源</label>
			<select name="subsystem" <?= ($pm->subnav === 'alerts') ? 'disabled="disabled"' : '' ?>>
			<?
				foreach(array_merge(array('--no filter--' => 0), $pm->histograms['subsystem']) as $option => $row)
				echo "<option ", ((isset($_POST['subsystem']) && $_POST['subsystem'] === $option) ? 'selected="selected"' : ''), ">$option</option>";
			?>
			</select>
		</div>
		<div class="p">
			<label>时间</label>
			<select name="when">
			<?
				foreach(array(
					'--no filter--',
					'older than',
					'newer than',
					) as $option)
				echo "<option ", ((isset($_POST['when']) && $_POST['when'] === $option) ? 'selected="selected"' : ''), ">$option</option>";
			?>
				</select><input class="wocloudUltraShortInput" type="text" name="days" value="<?= empty($_POST['days']) ? 30 : intval($_POST['days']) ?>" maxlength="3" size="3" /> 天
		</div>
		<? if ($pm->subnav !== 'alerts') { ?>
		<div class="p">
			<label>备份集</label>
			<select name="configuration_id">
			<?
				foreach(array_merge(array('--no filter--' => 0), ZMC_BackupSet::getMyNames()) as $name => $id)
				echo "<option value='$id' ", ((isset($_POST['configuration_id']) && $_POST['configuration_id'] == $id) ? 'selected="selected"' : ''), ">$name</option>";
			?>
			</select>
		</div>
		<? } ?>
		<div class="p">
			<label for="show_descriptions">显示摘要?</label>
			<input type="hidden" name="show_descriptions" value="no" />
			<input id="show_descriptions" type="checkbox" name="show_descriptions" value="yes" <?= (empty($_POST['show_descriptions']) || $_POST['show_descriptions'] !== 'no') ? 'checked="checked"' : '' ?> />
		</div>
		<div style='clear:left;'></div>
	</div>
	<div class="wocloudButtonBar">
			<? 
			if (!empty($pm->rows) && isset($_POST['when']) && strpos($_POST['when'], 'ld') && ($_POST['days'] > -1))
				echo '<input type="submit" name="action" value="Delete" class="wocloudButtonsLeft" />'; ?>
		<input type="submit" name="action" value="Apply" />
	</div>
</div>
	

<?
$pm->tableTitle = '显示日志';
$pm->disable_onclick = true;
$pm->disable_checkboxes = true;
$pm->buttons = array('Refresh Table' => true);
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
