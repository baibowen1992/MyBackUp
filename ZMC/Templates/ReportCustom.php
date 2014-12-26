<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

function display_name_select()
{
?>
			<label class="wocloudShortestLabel">名字:</label>
			<select name='choose_report' onChange='submit()'>
				<option value=''>--请选择一个 <?= $_SESSION['custom']['is_preset'] ? 'Preset' : 'Custom' ?> 报告模板--</option>
				<?
				if ($_SESSION['custom']['is_preset'])
					$list =& ZMC_Report_Custom::$presets;
				else
					$list =& ZMC::$registry->CustomReports;

				foreach($list as $name => $ignored)
				{
					$chk = '';
					if (!empty($_SESSION['custom']['report']) && $_SESSION['custom']['report'] === $name)
						$chk = 'selected="selected"';
		
					echo "<option value='", $name, "' $chk>", ZMC::escape($name), '</option>';
				}
				?>
			</select>
<?
}
?>

<div class="wocloudLeftWindow">
	<? ZMC::titleHelpBar($pm, 'Select or Customize a Report Template'); ?>
	<div class="tabbedNavigation">
		<ul>
		<?
			if ($_SESSION['custom']['is_preset'])
			{
				echo "<li class='current'><a href='#'>预置</a></li>\n";
				echo "<li><a href='?type=custom'>自定义</a></li>";
			}
			else
			{
				echo "<li><a href='?type=preset'>预置</a></li>";
				echo "<li class='current'><a href='#'>自定义</a></li>";
			}
		?>
		</ul>
		<br style='clear:left;' />
	</div>

	<div class="wocloudFormWrapper wocloudShortLabel wocloudLongInput">
		<div class="p">
			<?
			if (!$_SESSION['custom']['is_preset'] && !empty($_SESSION['custom']['report']))
				echo ' <input type="submit" name="action" value="Delete Template" class="wocloudButtonsLeft" />';
			$cols = (empty($pm->columns) ? array() : array_flip($pm->columns));
			$disabled = '';
			if ($_SESSION['custom']['is_preset'])
				$disabled = 'disabled="disabled"';

			$id = 1;
			foreach(array('Client', 'Media', 'Misc.') as $legend)
			{
				$i = 0;
				$m = ($legend === 'Client' ? 3 : 2);
				$style = ($legend === 'Client' ? 'float:right; margin:8px 8px 10px 10px;' : 'float:left; margin:8px;');
				echo "<fieldset style='$style'><legend>$legend</legend><table>";
				foreach(ZMC_Report_Custom::$cols2group as $col => $info)
				{
					if ($legend !== $info[3])
						continue;
	
					if ($i % $m === 0)
						echo "<tr>\n";
	
					$chk = '';
					if (isset($cols[$col]))
						$chk = " checked='checked'";
	
					echo "<td><input $disabled id='chk$id' name='template_columns[]' type='checkbox' value='",
						urlencode($col), "' $chk />&nbsp;<label for='chk$id'>", ZMC::escape($info[1]), "</label>&nbsp;</td>\n";
					$id++;
	
					if ($i++ % $m === ($m -1))
					echo "</tr>\n";
				}
				if ($i % $m !== 0)
					echo "</tr>\n";
				echo "</table></fieldset>\n";
				if ($legend === 'Client')
					display_name_select();
			}
			?>
			<br style="clear:left;" />
		</div>
	</div>

	<div class="wocloudButtonBar">
		<input type="submit" name="action" value="Save As" class='wocloudButtonsLeft' />
		<input type="text" name="name" value="My_Report" />
		<? if (!$_SESSION['custom']['is_preset'])
			echo '<input type="submit" name="action" value="Update" />';
		?>
		<input type="submit" name="action" value="Download as CSV" />
	</div>

</div>
	

<?
$pm->tableTitle = 'Backup Report';
$pm->disable_onclick = true;
$pm->disable_checkboxes = true;
$pm->data_table_div_attribs = 'style="overflow:auto;"';
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
