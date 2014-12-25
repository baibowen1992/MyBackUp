<?













class ZMC_Backup_When extends ZMC_Backup
{
public static function run(ZMC_Registry_MessageBox $pm)
{
	ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - 备份计划', 'when');
	ZMC_HeaderFooter::$instance->injectYuiCode("
		var o=gebi('zmc_schedule_type');
		if (o) o.onchange();
	");
	$pm->addDefaultInstruction('为备份集编辑备份计划');
	$whenPage = new self($pm);
	$whenPage->runState($pm);
	$whenPage->getPaginator($pm);

	return 'BackupWhen';
}

protected function buildFormWrapper(ZMC_Registry_MessageBox $pm, array $ignored = null)
{}

protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
{
	$update = false;
	if (!empty($state))
		$pm->state = $state;

	switch($pm->state)
	{
		case 'Update': 
			$this->updateAdd($pm, true);
			if (!empty($pm->binding) && !empty($pm->binding['schedule']))
				ZMC_Mysql::update('configurations',	array('schedule_type' => $pm->binding['schedule']['schedule_type']), "configuration_name='{$pm->binding['config_name']}'");

			if (!ZMC_BackupSet::isActivated())
				$pm->addEscapedWarning("该备份集还没有被激活，请在 备份|计划 中使用系统的任务计划为备份集添加备份计划。");
			break;

		case 'Delete':
			$pm->addEscapedError('所有备份集总是有一个备份计划。在' . ZMC::getPageUrl($pm, 'Backup', 'now')
				. ' 启用或者取消备份计划。备份计划不能被删除，但是备份集可以在页面 Admin|backup 删除。');
			break;

		case 'Cancel':
			ZMC_BackupSet::cancelEdit();
			$pm->addWarning("取消了 编辑/添加 操作.");
			break;

		case '':
		case 'Refresh Table': 
		case 'Refresh': 
		case 'Edit':
		default:
			$pm->state = $this->getSelectedBinding($pm) ? 'Edit' : '';
			break;
	}
}





public function getPaginator(ZMC_Registry_MessageBox $pm)
{
	$this->getBindingList($pm);
	if (empty($pm->binding_list))
		return;

	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('config_name', false);

	
	foreach($pm->binding_list as &$binding)
		if (!empty($binding['schedule']))
		{
			if (strpos($binding['schedule']['schedule_type'], 'Days of the Month'))
			{
				$binding['schedule']['when'] = '';
				foreach($binding['schedule']['dom'] as $day => &$type)
					$binding['schedule']['when'] .= $day . ':' . $type[0] . ', ';
			}
			else
			{
				
				
				$binding['schedule']['when'] = '-|-|-|-|-|-|-';
				
				if (is_array($binding['schedule']['days']))
					foreach($binding['schedule']['days'] as $day => &$type)
						$binding['schedule']['when'][($day * 2)] = (empty($type) ? '-' : ucFirst($type[0]));
				else 
				{
					if (!ZMC::$registry->debug)
						ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex('Internal Scheduling Error'), __FILE__, __LINE__);
					$pm->addInternal("Internal Scheduling Error");
					$pm->addDetail($binding);
				}
			}

			$binding['schedule']['hours'] = implode(', ', array_keys(array_filter($binding['schedule']['hours'])));
			$binding['schedule']['full_hours'] = implode(', ', array_keys(array_filter($binding['schedule']['full_hours'])));
		}

	$flattened =& ZMC::flattenArrays($pm->binding_list);
	$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
		'config_name',
		'_key_name',
		'schedule:schedule_type',
		'schedule:archived_media',
		'schedule:dumpcycle',
		'schedule:desired_retention_period',
		'schedule:hours',
		'schedule:minute',
		'schedule:when',
		
		'private:last_modified_time',
		'private:last_modified_by',
		'schedule:status'
	));
	$paginator->createColUrls($pm);
	
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
	if (!empty($pm->rows))
		foreach($pm->rows as &$row)
		{
			
			
			
			if (!empty($pm->sets))
				unset($pm->sets[$row['config_name']]);
		}
}

protected function inputFilter(ZMC_Registry_MessageBox $pm)
{
	unset($_POST['action']);
	unset($_POST['selected_ids']);
	$binding = array();
	$_POST['dumpcycle'] = max(1, $_POST['dumpcycle']);
	ZMC::array_move($_POST, $binding, array('config_name', '_key_name', 'private'));
	if (false === array_search('1', $_POST['hours']))
	{
		$pm->addError('请在 “备份开始时间” 中至少选择一个。默认时间是凌晨。');
		$_POST['hours'] = array('0' => '1');
	}
	$_POST['minute'] = filter_var(filter_input(INPUT_POST, 'minute', FILTER_SANITIZE_NUMBER_INT), FILTER_VALIDATE_INT, array('default' => 0, 'min_range' => 0, 'max_range' => 60));
	if ($_POST['minute'] < 0 || $_POST['minute']>  59)
	{
		$pm->addError('请为 “分钟” 输入一个有效值 (0-59)。');
	}
	if ($_POST['minute'] === false || $_POST['minute'] === null)
	{
		$_POST['minute'] = 0;
		$pm->addError('请问 “备份开始时间”下的“分钟”输入一个有效值(0-59)。开始小时已经默认选择');
	}
	if (false === array_search('1', $_POST['full_hours']))
		$_POST['full_hours'] = $_POST['hours'];
	if (empty($_POST['full_minute']))
		$_POST['full_minute'] = $_POST['minute'];

	$binding['schedule'] = $_POST;
	ZMC::rmFormMetaData($pm->binding['schedule']);
	$result = null;
	ZMC::flattenArray($result, $binding);
	return $result;
}
}
