<?






























global $pm;
?>
</form>
<div class="zmcConfirmWindow" style="margin:10px auto; width:<?= empty($pm->confirm_width) ? 516 : $pm->confirm_width ?>px; z-index:1005;">
	<form <? if (!empty($pm->url)) echo 'name="confirmDialog" action="', $pm->url, '" method="post"'; ?>>
<?
	ZMC::titleHelpBar($pm, empty($pm->confirm_help) ? 'Confirm' : $pm->confirm_help);
	ZMC_Loader::renderTemplate('MessageBox', $pm);

	if (!empty($pm->confirm_action))
		echo '<input type="hidden" name="action" value="', $pm->confirm_action, '" />';
?>

    <div class="zmcFormWrapperLeft <?= isset($pm->form_type) ? $pm->form_type['form_classes']:'' ?>">
		<img class="zmcWindowBackgroundimage" src="/images/global/confirmation_<? echo ($icon = empty($pm->confirm_icon) ? 'warning' : $pm->confirm_icon), file_exists($fn = dirname(__DIR__) . '/images/global/confirmation_' . $icon . '.png') ? '.png' : '.gif' ?>" />
		<div style="min-height:55px;"><?=$pm->prompt;?></div>
		<div id='progress_status'></div>
		<div style="clear:both;"></div>
	</div>

	<div class="zmcButtonBar">
		<input type="hidden" name="formName" value="confirmDialog" />
		<?	if (!empty($pm->yes))
				echo '<input type="submit" name="ConfirmationYes" value="', $pm->yes, '" />';
			if (!empty($pm->no))
				echo '<input type="submit" name="ConfirmationNo"  value="', $pm->no, '"  />';
			if (!empty($pm->raw_html)) 
				echo $pm->raw_html;
		?>
	</div>
	</form>
</div><!-- zmcWindow -->

<div class="confirmationWindow" id="confirmationWindow"></div>
