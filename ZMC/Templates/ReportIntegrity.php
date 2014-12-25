<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>

<div class='zmcRightWindow' style='width:650px;'>
	<?
		ZMC::titleHelpBar($pm, 'Verification Results', '', '', '', (empty($_SESSION['DataIntegrityTapeLabel']) ? '' : "&nbsp; for Tape Label: $_SESSION[DataIntegrityTapeLabel]"));
	?>
	<div class="zmcFormWrapperText" style='min-height:324px;'>
		<span id='monitor_countdown'></span>
		<?= $pm->taskInDiv ?>
	</div>
</div>



<div class="zmcLeftWindow" style="width:<?= $pm->verifyByDate ? '216' : '284' ?>px; clear:left;">
	<?
	ZMC::titleHelpBar($pm, 'Verify Data Integrity');
	if (false && $pm->verifyByDate)
		echo '<div class="zmcSubHeadingWide">',
			ZMC_Report::renderDayWeekNavigation("DataIntegrity"),
			'</div>';
	?>
	<div class="zmcFormWrapper zmcShortestLabel">
		<div class="p">
			<select onchange='this.form.submit();'  name='mode' value=1>
			<?
			if  (!$pm->verifyByDate)
			{
				echo "<option value=ByTape selected='selected'>By Media Label</option>";
				echo "<option value=ByDate >By Date</option>";
			}
			else
			{
				echo "<option value=ByTape >By Media Label</option>";
				echo "<option value=ByDate selected='selected'>By Date</option>";
			}
			?>
			</select>
		</div>
		<div class='p'>
		<?
		if ($pm->verifyByDate)
		{
			if (ctype_digit($pm->verifyButton[0]))
				echo '<label>', $pm->verifyButton, '</label>',
					'<input type="submit" value="Verify Integrity" name="action" />';
			else
				echo $pm->verifyButton;
		}
		else
		{
			echo "<p>Please select an Amanda tape for verification:</p>\n";
			if (empty($pm->tapelist_stats))
				echo "No tape labels found";
			else
			{
				?>
					<select name='dataIntegrityTapeLabel' value=1>
					<?
					foreach($pm->tapelist_stats['tapelist'] as $tapename => $record)
						if ($record['timestring']) 
							echo '<option value="', ZMC::escape($tapename), '"',
								((isset($_SESSION["DataIntegrityTapeLabel"]) && $_SESSION["DataIntegrityTapeLabel"] === $tapename) ? ' selected="selected" ' : ''),
							   	'>', ZMC::escape($tapename), '</option>';
					?>
					</select>
					&nbsp;&nbsp;<input type='submit' name='action' value='Verify' />
				<?
			}
		}
		?>
		</div>
	<div style='clear:left;'></div>
	</div>
</div>



<?
if ($pm->verifyByDate)
	ZMC_BackupCalendar::renderCalendar($pm, "DataIntegrity");

echo "</form>\n";
