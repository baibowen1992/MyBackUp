<?














class ZMC_Report_Restores 
{
	public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Report', $title = 'ZMC - Monitor Recent Restores', $subnav = 'restores')
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
		$pm->rows = $pm->state = '';
		if (!empty($_REQUEST['action']))
			$pm->state = $_REQUEST['action'];
		unset($_REQUEST['action']);

		if (!($configName = ZMC_BackupSet::assertSelected($pm)))
			return 'MessageBox'; 

		if (!self::hasRestore($pm, $configName))
			return 'MessageBox'; 
		$rhistory = new self($pm);

		$rhistory->runState($pm);
		$rhistory->getPaginator($pm);
		return "ReportRestores";
	}

	public function hasRestore(ZMC_Registry_MessageBox $pm, $name){
		$files = glob($dirName = ZMC::$registry->etc_amanda . $name . '/jobs/history/*Restore.state');
		if (count($files) > 0)
			return true;
		$pm->addMessage("This backup set \"$name\" has no restores.");
		return false;
	}

	protected function runState($pm){
		if(!empty($state))
			$pm->state = $state;

		switch($pm->state){
		case "Refresh Table":
			
			
			
			
			exec(ZMC::$registry->cnf->zmc_bin_path . "restore_history --config=$pm->selected_name");
		case "List":
			
		default:
				$sql = "select rh.*, c.configuration_name from restore_history rh, configurations c where rh.configuration_id = c.configuration_id and c.configuration_name = \"". $pm->selected_name."\"  order by rh.starttime_human desc ";
				$rows = ZMC_Mysql::getAllRows("$sql");
				$pm->rows = $rows;
				return;
		}
	}

	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (empty($pm->rows))
			return;

		$flattened =& ZMC::flattenArrays($pm->rows);

		if(!(isset($_REQUEST['sort']) 
			|| isset($_REQUEST['dir']) 
			|| isset($_REQUEST['offset_sort']) 
			|| isset($_REQUEST['np'])
			|| isset($_REQUEST['goto_page_sort'])
			|| isset($_REQUEST['rows_per_page_sort'])
			|| isset($_REQUEST['rows_per_page_orig_sort'])
			|| isset($_REQUEST['edit_id'])
		)){
			ZMC_Paginator_Reset::reset('starttime_human');
		}

		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'backup_date',
			'host',
			'disk_name',
			'target_host',
			'target_dir',
			'state',
			'starttime_human',
			'endtime_human',
			
			'restore_type',
			'user_name'
			
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}

}
