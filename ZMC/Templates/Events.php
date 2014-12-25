<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
$when = 'Any Time';
if (!empty($_POST['when']) && ($_POST['when'][0] !== '-'))
	$when = "When: $_POST[when] $_POST[days] days<br />\n";

if ($pm->subnav !== 'alerts') { ?>
<div class="zmcRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, 'Source Histogram'); ?>
	<div class="zmcFormWrapperText">
	<?
		if (empty($_POST['severity']))
			echo "Any Event Type<br />\n";
		else
			echo "Event Type at least ", ZMC_Error::$severity2text[$_POST['severity']], "<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['subsystem'] as $subsystem => $row)
			echo "$row[mycount] : $subsystem<br />\n";
	?>
	</div>
</div>
<? } ?>



<div class="zmcRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, 'Severity Histogram'); ?>
	<div class="zmcFormWrapperText">
	<?
		if (empty($_POST['subsystem']) || $_POST['subsystem'][0] === '-')
			echo "Any Event Source<br />\n";
		else
			echo "Event Source: $_POST[subsystem]<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['severity'] as $severity => $row)
			echo "$row[mycount] : ", ZMC_Error::$severity2text[$severity], "<br />\n";
	?>
	</div>
</div>



<? if ($pm->subnav !== 'alerts') { ?>
<div class="zmcRightWindow" style="min-width:175px">
	<? ZMC::titleHelpBar($pm, 'Backup Set Histogram'); ?>
	<div class="zmcFormWrapperText">
	<?
		if (empty($_POST['subsystem']) || $_POST['subsystem'][0] === '-')
			echo "Any Event Source<br />\n";
		else
			echo "Event Source: $_POST[subsystem]<br />\n";
		echo "$when<hr/>";
		foreach($pm->histograms['configuration_id'] as $id => $row)
			echo "$row[mycount] : ", (empty($id) ? 'none' : ZMC_BackupSet::getName($id)), "<br />\n";
	?>
	</div>
</div>
<? } ?>



<div class="zmcLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Filter ' . ucFirst($pm->subnav)); ?>
	<div class="zmcFormWrapper">
	<? if ($pm->subnav !== 'alerts') { ?>
		<div class="p">
			<label>Event Type</label>
			<select name="severity">
			<?
				foreach(array_merge(array('--no filter--' => 0), ZMC_Error::$error2severity) as $option => $level)
				echo "<option value='$level' ", ((isset($_POST['severity']) && $_POST['severity'] == $level) ? 'selected="selected"' : ''), ">$option</option>";
			?>
			</select>
		</div>
		<? } ?>
		<div class="p">
			<label>Event Source</label>
			<select name="subsystem" <?= ($pm->subnav === 'alerts') ? 'disabled="disabled"' : '' ?>>
			<?
				foreach(array_merge(array('--no filter--' => 0), $pm->histograms['subsystem']) as $option => $row)
				echo "<option ", ((isset($_POST['subsystem']) && $_POST['subsystem'] === $option) ? 'selected="selected"' : ''), ">$option</option>";
			?>
			</select>
		</div>
		<div class="p">
			<label>When</label>
			<select name="when">
			<?
				foreach(array(
					'--no filter--',
					'older than',
					'newer than',
					) as $option)
				echo "<option ", ((isset($_POST['when']) && $_POST['when'] === $option) ? 'selected="selected"' : ''), ">$option</option>";
			?>
				</select><input class="zmcUltraShortInput" type="text" name="days" value="<?= empty($_POST['days']) ? 30 : intval($_POST['days']) ?>" maxlength="3" size="3" /> days
		</div>
		<? if ($pm->subnav !== 'alerts') { ?>
		<div class="p">
			<label>Backup Set</label>
			<select name="configuration_id">
			<?
				foreach(array_merge(array('--no filter--' => 0), ZMC_BackupSet::getMyNames()) as $name => $id)
				echo "<option value='$id' ", ((isset($_POST['configuration_id']) && $_POST['configuration_id'] == $id) ? 'selected="selected"' : ''), ">$name</option>";
			?>
			</select>
		</div>
		<? } ?>
		<div class="p">
			<label for="show_descriptions">Show Descriptions?</label>
			<input type="hidden" name="show_descriptions" value="no" />
			<input id="show_descriptions" type="checkbox" name="show_descriptions" value="yes" <?= (empty($_POST['show_descriptions']) || $_POST['show_descriptions'] !== 'no') ? 'checked="checked"' : '' ?> />
		</div>
		<div style='clear:left;'></div>
	</div>
	<div class="zmcButtonBar">
			<? 
			if (!empty($pm->rows) && isset($_POST['when']) && strpos($_POST['when'], 'ld') && ($_POST['days'] > -1))
				echo '<input type="submit" name="action" value="Delete" class="zmcButtonsLeft" />'; ?>
		<input type="submit" name="action" value="Apply" />
	</div>
</div>
	

<?
$pm->tableTitle = 'Event Viewer';
$pm->disable_onclick = true;
$pm->disable_checkboxes = true;
$pm->buttons = array('Refresh Table' => true);
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
