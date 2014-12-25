<?













class ZMC_Restore_How extends ZMC_Restore
{
	public static function runWrapped(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Restore', 'ZMC - How would you like to perform the restore?', 'how');
		$pm->addDefaultInstruction('Configure how to perform restore (e.g. overwrite vs. rename existing).');
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
			$pm->addMessage("This restore job does not require configuring &quot;how&quot;.");
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
		$pm->addMessage('Restore|how changes applied.');
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
