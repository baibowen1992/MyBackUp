<?






























global $pm;
?>
</form>
<div class="wocloudConfirmWindow" style="margin:10px auto; width:<?= empty($pm->confirm_width) ? 516 : $pm->confirm_width ?>px; z-index:1005;">
	<form <? if (!empty($pm->url)) echo 'name="confirmDialog" action="', $pm->url, '" method="post"'; ?>>
<?
	ZMC::titleHelpBar($pm, empty($pm->confirm_help) ? 'Confirm' : $pm->confirm_help);
	ZMC_Loader::renderTemplate('MessageBox', $pm);

	if (!empty($pm->confirm_action))
		echo '<input type="hidden" name="action" value="', $pm->confirm_action, '" />';
?>

    <div class="wocloudFormWrapperLeft <?= isset($pm->form_type) ? $pm->form_type['form_classes']:'' ?>">
		<img class="wocloudWindowBackgroundimage" src="/images/global/confirmation_<? echo ($icon = empty($pm->confirm_icon) ? 'warning' : $pm->confirm_icon), file_exists($fn = dirname(__DIR__) . '/images/global/confirmation_' . $icon . '.png') ? '.png' : '.gif' ?>" />
		<div style="min-height:55px;"><?=$pm->prompt;?></div>
		<div id='progress_status'></div>
		<div style="clear:both;"></div>
	</div>

	<div class="wocloudButtonBar">
		<input type="hidden" name="formName" value="confirmDialog" />
		<?	if (!empty($pm->yes))
				echo '<button type="submit" name="ConfirmationYes" value="', $pm->yes, '" />确认'.'</button>';
			if (!empty($pm->no))
				echo '<button type="submit" name="ConfirmationNo"  value="', $pm->no, '"  />取消'.'</button>';
			if (!empty($pm->raw_html)) 
				echo $pm->raw_html;
		?>
	</div>
	</form>
</div><!-- wocloudWindow -->

<div class="confirmationWindow" id="confirmationWindow"></div>
