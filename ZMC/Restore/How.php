<?













class ZMC_Restore_How extends ZMC_Restore
{
	public static function runWrapped(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Restore', '云备份 - 还原策略', 'how');
		$pm->addDefaultInstruction('本页面配置如何恢复备份 (比如：覆盖或者重命名已存在的文件).');
		if (!($configName = ZMC_BackupSet::assertSelected($pm)))
			return 'MessageBox'; 
		if (!ZMC_BackupSet::hasBackups($pm, $configName))
			return 'MessageBox'; 

		$pm->rows = $pm->state = '';
		if (!empty($_REQUEST['action']))
			$pm->state = $_REQUEST['action'];
		unset($_REQUEST['action']);

		$pm->set = ZMC_BackupSet::get();
		$job = new self($pm, $configName);
		if ($job->redirect_page)
			return $job->redirect_page;






		if (!$job->isConflictResolutionConfigurable())
		{
			$pm->addMessage("该恢复任务无需配置怎样恢复相关参数.");
			return ZMC::redirectPage('ZMC_Restore_Now', $pm);
		}

		if (!empty($_POST['action']))
			$template = $job->runState($pm);

		return (empty($template) ? 'RestoreHow' : $template);
	}

	protected function runState($pm)
	{
		if ($pm->isErrors())
			return;

		$this->applyChanges($pm);
		switch($_POST['action'])
		{
			case 'Apply Next':
				return ZMC::redirectPage('ZMC_Restore_Now', $pm);

			case 'Apply Previous':
				return ZMC::redirectPage('ZMC_Restore_Where', $pm);

			default:
				break;
		}
	}

	protected function applyChanges($pm)
	{
		$pm->addMessage('已应用修改的参数。');
		$this->restore_job['configured_how'] = true;
		if (empty($_POST['conflict_file_selected']))
			$_POST['conflict_file_selected'] = $_POST['conflict_dir_selected'];
		$this->restore_job['conflict_file_text'] = ZMC_Type_AmandaApps::conflict2text($_POST['conflict_file_selected'], $pm->restore['destination_location'], true);
		if (!empty($_POST['conflict_dir_selected']))
		{
			
			
			$this->restore_job['conflict_dir_text'] = ZMC_Type_AmandaApps::conflict2text($_POST['conflict_dir_selected'], $pm->restore['destination_location'], false);
		}
		$this->mergeToDisk();
	}
}
