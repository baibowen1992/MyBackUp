<?













class ZMC_Events
{
public static function run(ZMC_Registry_MessageBox $pm, $tombstone = 'Monitor', $title = '云备份 - 查看事件', $subnav = 'events')
{
	$pm->goto = null;
	$pm->disabled = 'Disabled';
	$pm->skip_backupset_start = true;
	ZMC_BackupSet::start($pm);
	ZMC_HeaderFooter::$instance->header($pm, $tombstone, $title, $subnav);
	if (!empty($_POST['action']))
		$template = self::runState($pm);
	self::rss($pm);
	self::getPaginator($pm);
	if (empty($pm->rows))
		$pm->addWarning('没有日志记录');
	else
		$pm->addMessage('一共找到 ' . ($pm->found) . ' 项日志记录');

	return 'Events';
}

protected static function runState($pm)
{
	switch($_POST['action'])
	{
		case 'Refresh Table':
		case 'Apply':
			break; 

		case 'Delete':
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->confirm_help = 'Confirm Deletion of Events';
			$pm->addMessage('删除日志记录仅是删除本页面显示的记录而不是系统日志文件内容。');
			$pm->addWarning('操作不可恢复');
			$pm->prompt ='确认删除这些日志记录？';
			$pm->confirm_action = 'DeleteConfirm';
			$pm->yes = 'Delete';
			$pm->no = 'Cancel';
			$pm->raw_html = '<input type="hidden" name="when" value="' . $_POST['when'] . '" />'
				. '<input type="hidden" name="days" value="' . $_POST['days'] . '" />';
			break;

		case 'DeleteConfirm':
			if (!isset($_POST['ConfirmationYes']))
				$pm->addWarning('Delete cancelled.');
			else
				$pm->addMessage('Deleted ' . ZMC_Mysql::delete('events', self::getWhenWhere()) . ' events.');
			break;
	}
}

public static function getPaginator(ZMC_Registry_MessageBox $pm)
{
	$where = array();
	foreach(array('subsystem', 'when', 'severity', 'configuration_id') as $field)
		if (!empty($_POST[$field]) && ($_POST[$field][0] !== '-'))
			if ($field === 'when')
				$where[$field] = self::getWhenWhere();
			elseif ($field === 'severity')
				$where[$field] = "(severity <= $_POST[severity])";
			else
				$where[$field] = "($field = '$_POST[$field]')";

	$pm->histograms = array();
	foreach(array('configuration_id', 'severity', 'subsystem') as $field)
	{
		$hwhere = $where;
		unset($hwhere[$field]);
		$hwhere = (empty($hwhere) ? '1' : implode(' AND ', $hwhere));
		$pm->histograms[$field] = ZMC_Mysql::getAllRowsMap("SELECT $field, COUNT( $field ) AS mycount FROM events WHERE $hwhere AND (configuration_id=0 OR configuration_id IN (" . implode(', ', ZMC_BackupSet::getMyNames()) . ")) GROUP BY $field");
	}

	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('timestamp');

	$paginator = new ZMC_Paginator(
		$pm,
		"FROM events WHERE " . (empty($where) ? '1' : implode(' AND ', $where)),
		$pm->cols = array(
			'id',
			($pm->subnav === 'alerts') ? null : 'user_id',
			'timestamp',
			'severity',
			($pm->subnav === 'alerts') ? null : 'subsystem',
			($pm->subnav === 'alerts') ? null : 'configuration_id',
			'summary',
			((empty($_POST['show_descriptions']) || ($_POST['show_descriptions'] === 'yes')) ? 'message' : null),
			($pm->subnav === 'alerts') ? null : 'event_id',
		)
	);
	$paginator->createColUrls($pm);
	array_pop($pm->columns); 
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
	$pm->found = $paginator->found();
}

public static function getWhenWhere()
{
	return '(timestamp ' . (($_POST['when'] === 'newer than') ? '>' : '<')
		. ' date_sub(now(), INTERVAL ' . intval($_POST['days']) . ' DAY))';
}

private static function rss($pm)
{
	if (empty(ZMC::$registry->internet_connectivity))
		return;

	if (ZMC::useCache($pm, $cacheFn = 'rss', $cacheFn, false, 86400))
		return;
	$row = ZMC_Mysql::getOneRow('SELECT * FROM users WHERE network_ID NOT NULL ORDER BY user_id', null, true);
	$latest_rss_guid = ZMC_Mysql::getOneValue("SELECT event_id FROM events WHERE subsystem = 'wocloud' ORDER BY id LIMIT 1", null, true);
	ini_set('user_agent', "ZMC-" . ZMC::$registry->svn->zmc_svn_revision);
	$stream = fopen(ZMC::$registry->rss . ($row ? "&id=$row[network_ID]" : ''), 'r');
	if (false === ($contents = stream_get_contents($stream)))
		return ZMC::errorLog('Unable to fetch rss feed from: ' . ZMC::$registry->rss);
	fclose($stream);
	for($i=strpos($contents, '<item '); $i < strlen($contents); $i = $pos)
	{
		$row = array('subsystem' => 'wocloud');
		foreach(array('title' => 'summary', 'description' => 'message', 'severity' => 'severity', 'pubDate' => 'timestamp', 'guid' => 'event_id') as $tag => $key)
			if (false === ($i = strpos($contents, "<$tag>", $i)))
				return;
			else
			{
				$i += strlen($tag) + 2;
				$pos = strpos($contents, "</$tag>", $i);
				$row[$key] = substr($contents, $i, $pos - $i);
			}

		if ($row['severity'] < ZMC_Error::EMERGENCY || $row['severity'] > ZMC_Error::DEBUG)
			$row['severity'] = ZMC_Error::INFO;
		$row['timestamp'] = ZMC::humanDate(strtotime($row['timestamp']));
		if (($row['event_id'] <= $latest_rss_guid) || (false === ZMC_Mysql::insert('events', $row, null, true)))
			return;
	}
}

public static function add($summary, $severity = null, $msg = null, $config = null, $eid = null, $subsystem = null, $uid = 0)
{
	$cid = ZMC_BackupSet::getId($config);
	$eid = empty($eid) ? time() : $eid;
	if ($uid === 0 && isset($_SESSION['user']))
		$uid = $_SESSION['user_id'];
	$uid = empty($uid) ? 0 : $uid;
	$subsystem = empty($subsystem) ? 'ZMC' : $subsystem;
	list ($function2, $file2, $line2) = ZMC_Error::getFileLine($function, $file, $line);
	$where = " [$function, $file, $line; $function2, $file2, $line2]";
	if (is_string($msg))
		$msg .= $where;
	elseif(is_array($msg))
		$msg = implode("\n", $msg);
	elseif(is_object($msg))
	{
		$msg->addDetail($where);
		$msg = gzcompress(serialize($msg));

	}

	$severity = ($severity < ZMC_Error::EMERGENCY || $severity > ZMC_Error::DEBUG) ? ZMC_Error::INFO : $severity;
	ZMC_Mysql::insert('events', array('configuration_id' => $cid, 'event_id' => $eid, 'user_id' => $uid, 'subsystem' => $subsystem, 'summary' => $summary, 'severity' => $severity, 'message' => $msg));
}
}
