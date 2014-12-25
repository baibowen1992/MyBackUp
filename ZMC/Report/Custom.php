<?













class ZMC_Report_Custom
{
	const MY_CLIENT_REPORT = 'My_Client_Report';
	public static $presets = array(
		'Client Backup Status' => array('timestamp', 'hostname', 'directory', 'status', 'backup_level'),
		'Client Performance' => array('timestamp', 'hostname', 'directory', 'backup_level', 'dump_orig_kb', 'dump_out_kb', 'dump_rate'),
		'Media Performance' => array('timestamp', 'tape_label', 'time_duration', 'size', 'percent_use', 'tape_rate'),
		'All' => array('timestamp', 'status_summary', 'hostname', 'directory', 'backup_level', 'dump_orig_kb', 'dump_out_kb', 'compressTool', 'encryptTool', 'dump_duration', 'dump_rate', 'tape_duration', 'tape_rate', 'status', 'tape_label', 'time_duration', 'size', 'percent_use', 'nb', 'nc', 'failed', 'zmc_type', 'program'),
	);

	public static $cols2group = array(
		'status_summary' => array(null, 'Status', null, 'Misc.'),
		'hostname' => array(null, 'Client Hostname', null, 'Client'),
		'directory' => array(null, 'Directory', null, 'Client'),
		'backup_level' => array(null, 'Backup Level', null, 'Client'),
		'dump_orig_kb' => array(null, 'Backup Size', null, 'Client'),
		'dump_out_kb' => array(null, 'Compressed Size', null, 'Client'),
		'compressTool' => array(null, 'Compression', null, 'Client'),
		
		'encryptTool' => array(null, 'Encryption', null, 'Client'),
		'dump_duration' => array(null, 'Backup Duration', null, 'Client'),
		'dump_rate' => array(null, 'Backup Speed (KiB/s)', null, 'Client'),
		'tape_duration' => array(null, 'Tape Duration', null, 'Client'),
		'tape_rate' => array(null, 'Write Speed (KiB/s)', null, 'Client'),
		'status' => array(null, 'Client Status', null, 'Client'),
		'tape_label' => array(null, 'Label', null, 'Media'),
		'time_duration' => array(null, 'Write Time (hh:mm)', null, 'Media'),
		'size' => array(null, 'Data Written', null, 'Media'),
		'percent_use' => array(null, '%Media Utilization', null, 'Media'),
		'nb' => array(null, 'Total DLEs', null, 'Client'),
		'nc' => array(null, 'Total Parts', null, 'Client'),
		'failed' => array(null, 'Client Failure Message', null, 'Misc.'),
		'zmc_type' => array(null, 'Type', null, 'Client'),
		'program' => array(null, 'Client App', null, 'Client'),
	);

	public static $pageCols = array(
		'status_summary',
		
		
		'hostname' => 'dump_summary.hostname',
		'directory' => 'dump_summary.directory',
		'backup_level' => 'l', 
		'dump_orig_kb',
		'dump_out_kb',
		'compressTool',
		'encryptTool',
		
		'dump_duration',
		'dump_rate',
		'tape_duration',
		'tape_rate',
		'status',
		'tape_label',
		'time_duration',
		'size',
		'percent_use',
		'nb',
		'nc',
		'failed',
		'zmc_type',
		'program',
	);

	public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Report', $title = '云备份 - 查看并自定义报告', $subnav = 'custom')
	{
		$pm->goto = null;
		$pm->disabled = 'Disabled';
		ZMC_BackupSet::start($pm);
		ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
		$pm->addDefaultInstruction('View reports and create customized report templates.');
		ZMC_BackupSet::select($pm);

		if (!isset($_SESSION['custom']))
			$_SESSION['custom'] = array();

		if (!isset($_SESSION['custom']['is_preset']))
			$_SESSION['custom']['is_preset'] = true;

		if (isset($_GET['type']))
		{
			if ($_SESSION['custom']['is_preset'] != ($_GET['type'] === 'preset')) 
				unset($_SESSION['custom']['report']);
			$_SESSION['custom']['is_preset'] = ($_GET['type'] === 'preset');
		}

		self::initCustomReports();
		if (empty($_SESSION['custom']['report'])) 
			if (empty($_SESSION['custom']['is_preset']))
				$_SESSION['custom']['report'] = (isset($_SESSION['custom']['prev_custom']) ? $_SESSION['custom']['prev_custom'] : self::MY_CLIENT_REPORT);
			else
				$_SESSION['custom']['report'] = (isset($_SESSION['custom']['prev_preset']) ? $_SESSION['custom']['prev_preset'] : 'Client Backup Status');

		if (!empty($_POST['choose_report'])) 
		{
			$_SESSION['custom']['report'] = $_POST['choose_report'];
			$_SESSION['custom']['is_preset'] = isset(self::$presets[$_POST['choose_report']]);
		}

		if (!empty($_POST['action']))
			self::runState($pm);

		if (empty($pm->confirm_template))
		{
			if ($_SESSION['custom']['is_preset'])
			{
				self::getPaginator($pm);
				if (empty($pm->rows))
					$pm->addWarning('No backup records found for this backup set.');
				else
					$pm->columns = self::$presets[$_SESSION['custom']['report']];
			}
			elseif (!empty($_SESSION['custom']['report']) && isset(ZMC::$registry->CustomReports[$_SESSION['custom']['report']]))
			{
				self::getPaginator($pm);
				if (empty($pm->rows))
					$pm->addWarning('No backup records found for this backup set.');
				else
					$pm->columns = ZMC::$registry->CustomReports[$_SESSION['custom']['report']];
			}
		}

		if (!empty($_POST['action']))
			self::runStatePostPagination($pm);

		$pm->enable_switcher = true;
		return 'ReportCustom';
	}

	public static function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		$where = '';

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('timestamp');

		$paginator = new ZMC_Paginator(
			$pm,
			'FROM (SELECT * FROM backuprun_dump_summary WHERE backuprun_dump_summary.configuration_id =' . ZMC_BackupSet::getId() . ') dump_summary ' .
				'INNER JOIN backuprun_summary ON backuprun_summary.backuprun_id = dump_summary.backuprun_id
				INNER JOIN backuprun_tape_usage ON backuprun_tape_usage.backuprun_id = backuprun_summary.backuprun_id
				INNER JOIN backuprun_dle_state ON
						backuprun_dle_state.backuprun_id = backuprun_summary.backuprun_id
						AND backuprun_dle_state.backuprun_date_time = backuprun_summary.backuprun_date_time
						AND backuprun_dle_state.hostname = dump_summary.hostname
						AND backuprun_dle_state.directory = dump_summary.directory',
			$pm->cols = array_merge(array('id' => 'backuprun_dump_id', 'timestamp' => 'backuprun_summary.backuprun_date_time'), self::$pageCols)
		);
		if ($paginator->pages < 10)
			$pm->disable_button_bar = true;
		else
			if (ZMC::$registry->dev_only) 
			$pm->buttons = array('Purge Records Older Than 180 Days' => true, 'Purge Records Older Than 365 Days' => true);

		$paginator->createColUrls($pm);
		array_shift($pm->columns);
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}

	public static function runState($pm)
	{
		switch($_POST['action'])
		{
			case 'Save As':
				if (!ZMC::isalnum_($_POST['name']))
					return $pm->addError('Please choose a name containing only letters, numbers, and the underscore character.');
				$_SESSION['custom']['report'] = $_POST['name'];
			case 'Update':
				if (empty($_SESSION['custom']['is_preset']))
				{
					array_unshift($_POST['template_columns'], 'timestamp');
					ZMC::$registry->CustomReports[$_SESSION['custom']['report']] = $_POST['template_columns'];
				}
				else
					ZMC::$registry->CustomReports[$_SESSION['custom']['report']] = self::$presets[$_SESSION['custom']['report']];

				ZMC::$registry->setOverrides(array('CustomReports' => ZMC::$registry->CustomReports));
				break;

			case 'Delete Template':
				$pm->confirm_template = 'ConfirmationWindow';
				$pm->addWarning('There is no undo.');
				$pm->prompt = 'Are you sure you want to DELETE the report template <b>'
					. $_SESSION['custom']['report'] . '</b>?';
				$pm->confirm_action = 'DeleteConfirm';
				$pm->yes = 'Delete';
				$pm->no = 'Cancel';
				break;

			case 'DeleteConfirm':
				if (!isset($_POST['ConfirmationYes']))
					return $pm->addWarning("编辑/新增  取消.");

				unset(ZMC::$registry->CustomReports[$_SESSION['custom']['report']]);
				ZMC::$registry->setOverrides(array('CustomReports' => ZMC::$registry->CustomReports));
				ZMC::auditLog('Deleted report template ' . $_SESSION['custom']['report'], 0, null, ZMC_Error::NOTICE);
				unset($_SESSION['custom']['report']);
				self::initCustomReports();
				break;

			default:
				if (!strncmp($_POST['action'], 'Purge Records', 13))
				{
					$days = preg_replace('/[\D]/', '', $_POST['action']);
					ZMC_Mysql::queryAndFree('CREATE TEMPORARY TABLE backuptmp SELECT backuprun_id FROM backuprun_summary WHERE (backuprun_date_time < date_sub(NOW(), INTERVAL ' . $days . ' DAY));');
					ZMC_Mysql::queryAndFree('DELETE FROM backuprun_dle_state USING backuprun_dle_state INNER JOIN backuptmp ON backuprun_dle_state.backuprun_id = backuptmp.backuprun_id;');
					ZMC_Mysql::queryAndFree('DELETE FROM backuprun_dump_summary USING backuprun_dump_summary INNER JOIN backuptmp ON backuprun_dump_summary.backuprun_id = backuptmp.backuprun_id;');
					ZMC_Mysql::queryAndFree('DELETE FROM backuprun_summary USING backuprun_summary INNER JOIN backuptmp ON backuprun_summary.backuprun_id = backuptmp.backuprun_id;');
					ZMC_Mysql::queryAndFree('DELETE FROM backuprun_tape_usage USING backuprun_tape_usage INNER JOIN backuptmp ON backuprun_tape_usage.backuprun_id = backuptmp.backuprun_id;');
					$pm->addMessage(str_replace('Purge', 'Purged', $_POST['action']));
				}
		}
	}

	public static function runStatePostPagination($pm)
	{
		switch($_POST['action'])
		{
			case 'Download as CSV':
				$pm->csv = true;
				$pm->fp = fopen('php://temp/maxmemory:10485760', 'r+');
				ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
				rewind($pm->fp);
				$result = stream_get_contents($pm->fp);
				fclose($pm->fp);
				ZMC_Error::flushObs();
				header('Content-Type: application/csv; charset=utf-8');
				header("Content-Disposition: attachment; filename={$_SESSION['custom']['report']}-" . ZMC_BackupSet::getName() . ".csv");
				header("Content-Description: Report {$_SESSION['custom']['report']} for " . ZMC_BackupSet::getName());
				header('Content-Transfer-Encoding: binary');
				header('Cache-Control: max-age=60, private', true);
				header('Pragma: ', true); 
				header('Expires: ', true); 
				header('Content-Length: ' . strlen($result));
				echo $result;
				exit;
		}
	}

	private static function initCustomReports()
	{
		if (empty(ZMC::$registry->CustomReports))
		{
			ZMC::$registry->CustomReports = array(self::MY_CLIENT_REPORT => array('timestamp', 'hostname', 'directory', 'status', 'backup_level'));
			ZMC::$registry->setOverrides(array('CustomReports' => ZMC::$registry->CustomReports));
		}
	}
}
