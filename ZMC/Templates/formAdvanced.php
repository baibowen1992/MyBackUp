<?













global $pm;
$id = (empty($pm->form_advanced_id) ? ($pm->form_advanced_id = uniqid('twirl')) : $pm->form_advanced_id);
?>
		<div
        <? //这里加上if语句是指仅仅在备份->存储设备，管理->存储设备，还原->策略 三个页面显示高级设置
        if (($pm[url] !== '/ZMC_Backup_Where')&&($pm[url] !== '/ZMC_Admin_Devices')&&($pm[url] !== '/ZMC_Restore_How')) {
            echo 'hidden="hidden"' ;
        }?> id="wocloudAdvancedForm" class="wocloudAdvancedForm" style="<?= empty($pm->stacked_twirl_down) ? '':$pm->stacked_twirl_down ?>"
            <? //下面的onclick是点击高级设置 前面的三角图标 来控制$pm->form_advanced_display 的值进而控制是否显示35行高级参数的div?>
			onclick="<?= empty($pm->form_advanced_onclick) ? '': $pm->form_advanced_onclick ?>; YAHOO.zmc.utils.twirl('img_<?= $id ?>', 'div_<?= $id ?>');">
			<div style="float:left;">
<!--				<img src="/images/global/twirl-up-arrow.png" id="img_--><?//= $id ?><!--" />-->
				<?= empty($pm->advanced_options_title) ? '高级设置' : $pm->advanced_options_title ?>
			</div>
			<div style="float:left; margin:-1px 0 0 10px;">
				<a class="wocloudHelpLink wocloudHelpLinkHug" href="http://www.wocloud.cn" target="_blank"></a>
			</div>
			<div style="clear:both;"></div>
		</div>
		<div
            <? if (($pm[url] !== '/ZMC_Backup_Where')&&($pm[url] !== '/ZMC_Admin_Devices')&&($pm[url] !== '/ZMC_Restore_How')) {
                echo 'hidden="hidden"' ;
            }?>
            id='div_<?= $id ?>' class="wocloudFormWrapper <?= $pm->form_type['advanced_form_classes'] ?>" style=''>
			<?= $pm->form_advanced_html ?>
			<div style='clear:both'></div>
		</div>
