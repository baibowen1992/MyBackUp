<?













class ZMC_Monitor
{

public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Monitor', $title = '云备份 - 监控备份集', $subnav = 'backups')
{
	$pm->goto = null;
	ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
	$pm->addDefaultInstruction('Monitor recent backups.');
	ZMC_BackupSet::start($pm);
	$report = new self();
	$pm->sets = ZMC_BackupSet::getMySets();

	foreach(array(
		'monitor_which' => false,
		'monitor_details' => false,
		'monitor_filters' => array(),
		'monitor_recent' => false,
		'monitor_refresh' => 1,
		'monitor_when' => 'newer than',
		'monitor_days' => 2,
	) as $key => $default)
		if (isset($_POST[$key]))
			ZMC::$userRegistry[$key] = $_POST[$key];
		elseif (!isset(ZMC::$userRegistry[$key]))
			ZMC::$userRegistry[$key] = $default;

	if (empty(ZMC::$userRegistry['monitor_which']))
		ZMC::$userRegistry['monitor_which'] = '';
	elseif (!ZMC_BackupSet::getName(ZMC::$userRegistry['monitor_which'])) 
		ZMC::$userRegistry['monitor_which'] = '';

	$report->getPaginator($pm);
	if (empty(ZMC::$userRegistry['monitor_refresh']))
		$pm->addMessage("上次刷新时间： " . ZMC::humanDate(true));
	else
		$pm->addMessage("刷新于: " . ZMC::humanDate(true) . ' (' . ZMC::$userRegistry['monitor_refresh'] . " 秒刷新间隔)");
	return 'Monitor';
}

protected function getPaginator($pm)
{
	
	$sql = "FROM backuprun_dle_state ";
	$recentSql = " b1
			JOIN (
				SELECT configuration_id AS cid, MAX( backuprun_date_time ) AS bdt
				FROM backuprun_dle_state
				GROUP BY configuration_id
			) AS b2 ON b1.configuration_id = b2.cid
			AND b1.backuprun_date_time = b2.bdt\n";
	$allSql = empty(ZMC::$userRegistry['monitor_which']) ? '' : " AND configuration_id=" . ZMC::$userRegistry['monitor_which'];
	$whenSql = $this->getWhenWhereSql();
	$filters = array();
	if (!empty(ZMC::$userRegistry['monitor_filters']))
	{
		foreach(ZMC::$userRegistry['monitor_filters'] as $name => $on)
			if (!empty($on))
				switch($name)
				{
					case 'failed':
						$filters[] = 'Failed';
						break;

					case 'completed':
						$filters[] = 'Backups in Media';
						break;

					case 'staging':
						$filters[] = "Backups in Holding Disk";
						$filters[] = "Backups in Holding Disk Waiting";
						break;

					case 'progress':
						$filters[] = "Backup Started";
						$filters[] = "Estimate Completed";
						$fitlers[] = "Flush Completed";
						break;

					default:
						throw new ZMC_Exception($name);
				}
	}
	$filtersSql = empty($filters) ? '' : " AND state NOT IN ('" . implode("', '", $filters) . "')";
	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('backuprun_date_time');

	$paginatorQuery = $sql . (empty(ZMC::$userRegistry['monitor_recent']) ? '' : $recentSql) . " WHERE TRUE $allSql $whenSql $filtersSql";
	ZMC::$registry->debug && $pm->addDetail($paginatorQuery);
	$paginator = new ZMC_Paginator($pm, $paginatorQuery,
		$pm->cols = array(
			'dle_state_id',
			'backuprun_date_time',
			'configuration_id',
			'zmc_type',
			'backup_level',
			'hostname',
			'directory',
			'state',
			'active',
			'flush',
			'estimate',
			'holding_disk',
			'media',
			'failed',
			'backuprun_id',
			
			
			
			
			
			
			
			
		)
	);
	$paginator->createColUrls($pm);
	unset($pm->columns[0]);
	unset($pm->columns[8]);
	unset($pm->columns[14]);
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
	$phases = array("media", "holding_disk", "estimate", "flush");
	foreach($pm->rows as &$row)
	{
		if(empty($row['media'])){
			$row['media'] = $row['holding_disk'];
			$row['holding_disk'] = '';
		}
		$row['holding_disk'] = $row['estimate'];
		$row['estimate'] = '';

		$green = ($row['state'] === 'Backups in Media') ? true:false;
		foreach($phases as $phase)
			if ($green)
				$row[$phase.'_bar'] = 'Success';
			elseif (empty($row['failed']))
			{
				if (!empty($row[$phase]))
				{
					$row[$phase.'_bar'] = 'Progress';
					$green = true;
				}
			}
			elseif (!empty($row[$phase]))
			{
				$row[$phase.'_bar'] = 'Failure';
				$green = true;
			}
	}
	$this->getStats($pm, $sql, $recentSql, $allSql, $whenSql);
}

protected function getWhenWhereSql()
{
	if (empty(ZMC::$userRegistry['monitor_when']))
		return '';

	return ' AND (backuprun_date_time ' . ((ZMC::$userRegistry['monitor_when'] === 'newer than') ? '>' : '<')
		. ' date_sub(now(), INTERVAL ' . intval(ZMC::$userRegistry['monitor_days']) . ' DAY))';
}

protected function getStats($pm, $sql, $recentSql, $allSql, $whenSql)
{
	$pm->backup_stats = array( 'completed' => 0, 'failed' => 0, 'staging' => 0, 'progress' => 0, );
	$pm->backup_stats['total'] = $pm->backup_stats['older'] = 0;
	$rows = ZMC_Mysql::getAllRows("SELECT * $sql WHERE TRUE $allSql $whenSql");
	if (empty(ZMC::$userRegistry['monitor_recent'])) 
		$pm->backup_stats['older'] = count($rows) - ZMC_Mysql::getOneValue("SELECT count(*) $sql $recentSql WHERE TRUE $allSql $whenSql");
	else 
		$pm->backup_stats['older'] = count($rows) - count($pm->rows);

	$i = 0;
	foreach($rows as &$row)
	{

		switch($row['state'])
		{
			case 'Backups in Media':
				$pm->backup_stats['completed']++;
				if (!empty(ZMC::$userRegistry['monitor_filters']['completed']))
					continue 2;
				break;

			case 'Failed':
				$pm->backup_stats['failed']++;
				if (!empty(ZMC::$userRegistry['monitor_filters']['failed']))
					continue 2;
				break;

			case "Backups in Holding Disk":
			case "Backups in Holding Disk Waiting":
				$pm->backup_stats['staging']++;
				if (!empty(ZMC::$userRegistry['monitor_filters']['staging']))
					continue 2;
				break;

			
			
			
			default:
				$pm->backup_stats['progress']++;
				if (!empty(ZMC::$userRegistry['monitor_filters']['progress']))
					continue 2;
		}
	}

	$total = count($rows);
	$filtered = $total - ($shown = count($pm->rows));
	if ($filtered === 0)
	{
		if (count($pm->rows) === 0)
			$pm->addMessage('在选定的周期内，备份集' . ZMC_BackupSet::getName(). '中没有正在运行的备份');
		return;
	}

	if (count($pm->rows) === 0)
		return $pm->addWarning("所有备份结果已经被隐藏，请取消勾选右边的筛选框。");

	$pm->addMessage("注意：显示 $total 中的 $shown 个备份结果在下面的监控框中。");
}

}
