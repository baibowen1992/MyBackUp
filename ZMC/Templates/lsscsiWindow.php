<?












?>

<div id="lsscsiWindow" class="zmcRightWindow" style='width:45%; background-color:white;'>
	<? ZMC::titleHelpBar($pm, 'Changer/Tape Unit Details'); ?>
	<div class="zmcContentContainer tabbedNavigation" style="height:22px;">
		<ul>
			<li id="li_lsscsi" class="current"><a href="#" onclick="YAHOO.zmc.utils.show_lsscsi(); return false;">lsscsi</a></li>
			<li id="li_mt" ><a href="#" onclick="YAHOO.zmc.utils.show_mt(<?=
				(empty($pm->binding['changer']['changerdev']) ?
					"gebi('changer:changerdev').value"
					:
					("'" . $pm->binding['changer']['changerdev'] . "'"))
				?>); return false;">mt/mtx</a>
			</li>
		</ul>
	</div>
	<textarea id="ta_lsscsi" class="textBoxDisabled" readonly name="commandResult" title="lsscsi /proc/scsi/scsi"   class="textarea" style="clear: left; margin: 7px; width:95%; font-size:smaller; height:250px;color:black!important;"><?= $pm->lsscsi ?></textarea>
    <textarea id="ta_mt" class="textBoxDisabled" readonly name="commandResult" title="mt/mtx -f /dev/* status"   class="textarea" style="clear: left; margin: 7px; width:95%; display:none; font-size:smaller;color:black!important;"></textarea>
	<div class="zmcButtonBar">
  		<input type="submit" value="Refresh" name="action" />
	</div>
</div><!-- lsscsiWindow zmcRightWindow -->
