<?













global $pm;
$id = (empty($pm->form_advanced_id) ? ($pm->form_advanced_id = uniqid('twirl')) : $pm->form_advanced_id);
?>
		<div id="zmcAdvancedForm" class="zmcAdvancedForm" style="<?= empty($pm->stacked_twirl_down) ? '':$pm->stacked_twirl_down ?>"
			onclick="<?= empty($pm->form_advanced_onclick) ? '': $pm->form_advanced_onclick ?>; YAHOO.zmc.utils.twirl('img_<?= $id ?>', 'div_<?= $id ?>');">
			<div style="float:left;">
				<img src="/images/global/twirl-up-arrow.png" id="img_<?= $id ?>" />
				<?= empty($pm->advanced_options_title) ? 'Advanced Options' : $pm->advanced_options_title ?>
			</div>
			<div style="float:left; margin:-1px 0 0 10px;">
				<a class="zmcHelpLink zmcHelpLinkHug" href="<? echo ZMC::$registry->wiki, $pm->tombstone, $pm->subnav; ?>#Advanced_Options" target="_blank"></a>
			</div>
			<div style="clear:both;"></div>
		</div>
		<div id='div_<?= $id ?>' class="zmcFormWrapper <?= $pm->form_type['advanced_form_classes'] ?>" style='<?= empty($pm->form_advanced_display) ? 'display:none;' : '' ?>'>
			<?= $pm->form_advanced_html ?>
			<div style='clear:both'></div>
		</div>
