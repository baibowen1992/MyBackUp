<?













global $pm;

if($pm->restore['restore_pref'] == ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])){
	$sel_search = "checked";
	$sel_select = $sel_express = '';
}elseif($pm->restore['restore_pref'] == ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])){
	$sel_select = "checked";
	$sel_express = $sel_search = '';
}else{
	$sel_select = $sel_search = '';
	$sel_express = "checked";
}
if($sel_express == "checked" || $sel_select == "checked")
	$dis_search_pref = "disabled";
else
	$dis_search_pref = "";

?>
<div class="zmcLeftWindow" style="width:460px">
	<? ZMC::titleHelpBar($pm, 'What would you like to restore from: ' . $pm->restore['config']); ?>
	<div class="zmcFormWrapper zmcLongInput">
		<div class="p">
			<label>Restore To:<br />(on or before)</label>
			<input
				id="nowBox"
				type="checkbox" 
				name="now" 
				title="Restore from most recently available backups."
				<? if (empty($pm->restore['date_time_human'])) echo 'checked="checked"'; ?>
				value="1"
				onclick="
					var disabled=false
					//var od=gebi('date')
					//var ot=gebi('time')
					var odt=gebi('date_time_human')
					if (this.checked)
					{
						disabled='disabled'
						//od.value=''
						//ot.value=''
						odt.value=''
					}
					else
					{
						//od.value='YYYY-MM-DD'
						//ot.value='HH:MM'
						odt.value='Enter a date/time'
					}
					//od.disabled=disabled
					//ot.disabled=disabled
					odt.disabled=disabled
				"
			/>
			<div style='float:left;' class='zmcShortestLabel'>
				<label style="float:left;" for="nowBox">Today, &nbsp;OR</label>
				<input 
					style='width:160px;'
					id="date_time_human" 
					type="text" 
					name="date_time_human" 
					title="<?= empty($pm->restore['date_time_human']) ? 'Enter any date/time in your favorite format.' : $pm->restore['date_time_parsed'] ?>"
					size="35"
					maxlength="35"
					onfocus="if (this.value == 'Enter a date/time') this.value=''"
					onchange="gebi('nowBox').checked=false;"
					value="<?= $pm->restore['date_time_human'] ?>"
					<? if (empty($pm->restore['date_time_human'])) echo 'disabled="disabled"'; ?>
				/> <? $pm->restore['date_time_human']; ?>
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>Search for backups made on, or before, a date/time.  Format is flexible.  Examples:</p>
					<ul style="margin-left:20px;"><li>yesterday</li>
					<li>last thursday</li>
					<li>last year</li>
					<li>-1 week 2 days</li>
					<li>28 April 2010</li>
					<li>2010/05/28</li>
					<li>28/05/2010</li>
					<li>Sat Mar 10 17:16:18 MST 2001</li>
					<li>March 10, 2001, 5:16 pm</li>
					<li>4am</li>
					<li>04:59</li>
					<li>etc.</li>
					</ul>
				</div>
			</div>
			<!--br />
			<input 
				id="date" 
				type="text" 
				name="date" 
				size="10"
				maxlength="10"
				onfocus="if (this.value == 'YYYY-MM-DD') this.value=''"
				title="Date of backup run (YYYY-MM-DD Format)" 
				value="<?
$disableDate = false;
if (!empty($pm->restore['date']))
	echo $pm->restore['date'];
elseif (!empty($pm->restore['time']))
	echo 'YYYY-MM-DD'; 
else
	$disableDate = true;
?>"
				<? if (!empty($disableDate)) echo 'disabled="disabled"'; ?>
				class="zmcShortestInput" />
			&nbsp;
			<input 
				id="time" 
				type="text" 
				name="time" 
				size="6"
				maxlength="6"
				<? if (!empty($disableDate)) echo 'disabled="disabled"'; ?>
				onfocus="if (this.value == 'HH:MM') this.value=''"
				title="Time of backup run (HH:MM Format)" 
				value="<?	if (!$disableDate)
				{
					$t = substr($pm->restore['time'], 0, 5);
					echo empty($t) ? 'HH:MM' : $t;
				}
?>"
				class="zmcUltraShortInput" />
			-->
				</div>
		</div>
		<div class="p">
			<label>Restore Device:<span class="required">*</span></label>
			<select type="text" name="restore_device">
<?
			foreach($pm->restore['device_list'] as $device)
				echo "<option value=\"" . $device . "\"" . ($pm->restore['restore_device'] === $device ? 'selected="selected"' : '') . ">" . $device . "</option>";
?>
			</select>
		</div>
		<? if (!empty($pm->restore['media_explored'])) { ?>
		<div class="p">
			<label>Media Explored:</label>
<?
			$prefix = '';
			foreach($pm->restore['media_explored'] as $media)
			{
				echo "$prefix<div class='zmcAfter'>",
					' <b>', ZMC::amandaDate2humanDate($media['datetime']), "</b> ",
					ZMC_BackupSet::displayName($media['tape_label']), 
					"</div>\n";
				$prefix = "<label style='clear:left;'>&nbsp;</label>";
			}
?>
		</div>
		<? } ?>
<?php 
			$disabled = '';
			if($pm->restore_state['running'])
				$disabled = 	'disabled';
?>
		<div class="p">
			<label>Host Name:<span class="required">*</span></label>
			<input
				type="text"
				name="client"
				id="client"
				value="<?= ZMC::escape($pm->restore['client']); ?>"
				title="Restore backup of which host?"
<?
			if (count($pm->suggestedHosts)) echo <<<EOD
					onFocus="YAHOO.zmc.restore.what.swapPopOn('Host', 150, 350, false)" />
					<input type="button" name="editHost" value="Edit" onclick="YAHOO.zmc.restore.what.swapPopOn('Host', 150, 350, true)"
EOD;
?>
			/>
		</div>
		<div class="p">
			<label>Alias/Directory/Path:<span class="required">*</span></label>
			<input
				type="text"
				name="disk_name"
				id="disk_name"
				value="<?= ZMC::escape($pm->restore['disk_name']); ?>"
				title="The top directory/path for the host to be restored."
<?
			if (count($pm->suggestedHosts)) echo <<<EOD
					onFocus="YAHOO.zmc.restore.what.swapPopOn('Path', 150, 350, false)" />
					<input type="button" name="editDirectory" value="Edit" onclick="YAHOO.zmc.restore.what.swapPopOn('Path', 150, 350, true)"
EOD;
?>
			/>
		</div>

		<? if (!empty($pm->backup_history_missing)) {?>
		<div class="p">
			<label>ZMC Type:<span class="required">*</span></label>
			<input
				type="text"
				name="zmc_type"
				title="value of 'zmc_type' property in DLE in disklist.conf"
				value="<? if (!empty($pm->restore['zmc_type'])) echo ZMC::escape($pm->restore['zmc_type']); ?>"
			/>
		</div>
		<div class="p">
			<label>Amanda Application:<span class="required">*</span></label>
			<input
				type="text"
				name="zmc_amanda_app"
				title="value of 'zmc_amanda_app' property in DLE in disklist.conf"
				value="<? if (!empty($pm->restore['zmc_amanda_app'])) echo ZMC::escape($pm->restore['zmc_amanda_app']); ?>"
			/>
		</div>
		<?}?>
	</div>


		<div class="zmcFormWrapper zmcLongInput">
		<div class="p">
			<label>Restore Preference: &nbsp;</label>	
			<input type="radio" <?=$sel_express?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt){ rpt.disabled= 'true'; rpt.value='default_match';}var frs = gebi('restore_search'); if(frs){ frs.disabled= 'true'; frs.value='';}" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>">Restore All</label>
		</div>
		<div class="p">
			<label>&nbsp;</label>	
			<input type="radio" <?=$sel_select?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt){ rpt.disabled= 'true'; rpt.value='default_match';}var frs = gebi('restore_search'); if(frs){ frs.disabled= 'true'; frs.value='';}" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>">Explore & Select</label>
		</div>
		<div class="p">
			<label>&nbsp;</label>	
			<input type="radio" <?=$sel_search?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt) rpt.disabled= '';var frs = gebi('restore_search'); if(frs) frs.disabled= '';" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>">Search Specific files</label>
		</div>
		<div class="p">
			<label>File/Pattern to <br />Restore:<span class="required">*</span></label>
			<input id='restore_search' type="text" name="restore_search" value="<?=ZMC::escape($pm->restore['restore_search'])?>" class="zmcButtonsLeft" <?=$dis_search_pref?> />
		</div>
		<?php $restore_pattern_type = array('default_match' => 'Anything','starts_with' => 'Starts With', 'ends_with' => 'Ends With', 'exact_match' => 'Exact Match');?>
		<div class="p">
			<label>Search for:&nbsp;</label>
			<select id='restore_pattern_type' name='restore_pattern_type' <?=$dis_search_pref?> >
			<?php 
			$pm->restore['restore_pattern_type'] = ($pm->restore['restore_pattern_type'] != '')? $pm->restore['restore_pattern_type'] : 'default_match';
			foreach($restore_pattern_type as $name => $display){
				if($pm->restore['restore_pattern_type'] == $name){
					echo "<option value='$name' selected>$display</option>";
				}else{
					echo "<option value='$name'>$display</option>";
				}
			}
			?>
			</select>
			<br />
		</div>
	</div>
	<div class="zmcButtonBar">
		<input id="explore_button" type="submit" name="action" value="Next" class="zmcButtonsRight" <?=$disabled?> />
<?
			if (!empty($pm->restore['restore_type']) || $pm->amgetindex_state['state'][0] != "Not Started" )
				echo '<input type="submit" name="action" value="Reset" title="Clear all inputs and cache memory which allows Fresh Restore." class="zmcButtonsLeft" '.$disabled.'/>';
?>
	</div>
</div><!-- zmcLeftWindow -->
