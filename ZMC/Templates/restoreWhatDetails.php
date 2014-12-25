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
<div class="wocloudLeftWindow" style="width:460px">
    <? ZMC::titleHelpBar($pm, '选择想从备份集 ' . $pm->restore['config'] . '中还原的内容'); ?>
	<div class="wocloudFormWrapper wocloudLongInput">
		<div class="p">
			<label>备份日期:<br />(在此或之前)</label>
			<input
				id="nowBox"
				type="checkbox" 
				name="now" 
				title="从最近的可用备份恢复."
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
						odt.value='输入 日期/时间'
					}
					//od.disabled=disabled
					//ot.disabled=disabled
					odt.disabled=disabled
				"
			/>
			<div style='float:left;' class='wocloudShortestLabel'>
				<label style="float:left;" for="nowBox">今天， 或者</label>
				<input 
					style='width:160px;'
					id="date_time_human" 
					type="text" 
					name="date_time_human" 
					title="<?= empty($pm->restore['date_time_human']) ? '按照支持的格式输入日期/时间。' : $pm->restore['date_time_parsed'] ?>"
					size="35"
					maxlength="35"
					onfocus="if (this.value == '输入日期或者时间') this.value=''"
					onchange="gebi('nowBox').checked=false;"
					value="<?= $pm->restore['date_time_human'] ?>"
					<? if (empty($pm->restore['date_time_human'])) echo 'disabled="disabled"'; ?>
				/> <? $pm->restore['date_time_human']; ?>
			<div class="contextualInfoImage">
				<a target="_blank" href="<?= ZMC::$registry->wiki ?>Restore_Where">
					<img height="18" width="18" alt="More Information" src="/images/icons/icon_info.png"/>
				</a>
				<div class="contextualInfo">
					<p>搜素在某个日期/时间当天或者之前的所有备份。格式举例如下：</p>
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
				class="wocloudShortestInput" />
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
				class="wocloudUltraShortInput" />
			-->
				</div>
		</div>
		<div class="p">
			<label>恢复源数据存储设备:<span class="required">*</span></label>
			<select type="text" name="restore_device">
<?
			foreach($pm->restore['device_list'] as $device)
				echo "<option value=\"" . $device . "\"" . ($pm->restore['restore_device'] === $device ? 'selected="selected"' : '') . ">" . $device . "</option>";
?>
			</select>
		</div>
		<? if (!empty($pm->restore['media_explored'])) { ?>
		<div class="p">
			<label>存储搜索:</label>
<?
			$prefix = '';
			foreach($pm->restore['media_explored'] as $media)
			{
				echo "$prefix<div class='wocloudAfter'>",
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
			<label>备份主机名:<span class="required">*</span></label>
			<input
				type="text"
				name="client"
				id="client"
				value="<?= ZMC::escape($pm->restore['client']); ?>"
				title="备份文件的来源主机?"
<?
			if (count($pm->suggestedHosts)) echo <<<EOD
					onFocus="YAHOO.zmc.restore.what.swapPopOn('Host', 150, 350, false)" />
					<input type="button" name="editHost" value="选择" onclick="YAHOO.zmc.restore.what.swapPopOn('Host', 150, 350, true)"
EOD;
?>
			/>
		</div>
		<div class="p">
			<label>备份目录:<span class="required">*</span></label>
			<input
				type="text"
				name="disk_name"
				id="disk_name"
				value="<?= ZMC::escape($pm->restore['disk_name']); ?>"
				title="想还原的备份项的根目录。"
<?
			if (count($pm->suggestedHosts)) echo <<<EOD
					onFocus="YAHOO.zmc.restore.what.swapPopOn('Path', 150, 350, false)" />
					<input type="button" name="editDirectory" value="选择" onclick="YAHOO.zmc.restore.what.swapPopOn('Path', 150, 350, true)"
EOD;
?>
			/>
		</div>

		<? if (!empty($pm->backup_history_missing)) {?>
		<div class="p">
			<label>备份类型:<span class="required">*</span></label>
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


		<div class="wocloudFormWrapper wocloudLongInput">
		<div class="p">
			<label>还原类别: &nbsp;</label>	
			<input type="radio" <?=$sel_express?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt){ rpt.disabled= 'true'; rpt.value='default_match';}var frs = gebi('restore_search'); if(frs){ frs.disabled= 'true'; frs.value='';}" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::EXPRESS])?>">还原所有</label>
		</div>
		<div class="p">
			<label>&nbsp;</label>	
			<input type="radio" <?=$sel_select?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt){ rpt.disabled= 'true'; rpt.value='default_match';}var frs = gebi('restore_search'); if(frs){ frs.disabled= 'true'; frs.value='';}" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SELECT])?>">检索并选择</label>
		</div>
		<div class="p">
			<label>&nbsp;</label>	
			<input type="radio" <?=$sel_search?> onClick="var rpt = gebi('restore_pattern_type'); if(rpt) rpt.disabled= '';var frs = gebi('restore_search'); if(frs) frs.disabled= '';" name="restore_pref" value="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>" id="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>"><label for="<?=ZMC::escape(ZMC_Restore::$buttons[ZMC_Restore::SEARCH])?>">搜索特定文件</label>
		</div>
		<div class="p" >
			<label>还原文件名<br>或匹配模式<span class="required">*</span></label>
			<input id='restore_search' type="text" name="restore_search" value="<?=ZMC::escape($pm->restore['restore_search'])?>" class="wocloudButtonsLeft" <?=$dis_search_pref?> />
		</div>
		<?php $restore_pattern_type = array('default_match' => '任意部分','starts_with' => '开头', 'ends_with' => '结尾', 'exact_match' => '严格匹配');?>
		<div class="p">
			<label>搜索:&nbsp;</label>
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
	<div class="wocloudButtonBar">
		<input id="explore_button" type="submit" name="action" value="下一步" class="wocloudButtonsRight" <?=$disabled?> />
<?
			if (!empty($pm->restore['restore_type']) || $pm->amgetindex_state['state'][0] != "Not Started" )
				echo '<input type="submit" name="action" value="重置" title="清除所有输入数据和缓存" class="wocloudButtonsLeft" '.$disabled.'/>';
?>
	</div>
</div><!-- wocloudLeftWindow -->
