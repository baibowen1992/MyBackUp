<?













class ZMC_Admin_Audit
{
public static function run(ZMC_Registry_MessageBox $pm)
{
	$pm->skip_backupset_start = true;
	ZMC_HeaderFooter::$instance->header($pm, 'Admin', 'ZMC - Audit', 'audit'); 
	$pm->addDefaultInstruction('View timeline of ZMC users\' actions, including details of edits and changes. Hover over the "i" icons to see the details of the corresponding action listed in the "action" column.');
	if (!ZMC_User::hasRole('Administrator'))
	{
		$pm->addError('Only ZMC administrators may perform this action.');
		return 'AdminAudit';
	}

	$pm->rows = array();
	foreach(glob(dirname(ZMC::$registry->input_log) . '/*') as $fn)
	{
		$fp = ((substr($fn, -3) === '.gz') ? gzopen($fn, 'r') : fopen($fn, 'r'));
		while(false !== ($line = fgets($fp, 8192)))
		{
			if ($row = json_decode($line, true))
			{
				$row['action'] = '';
				if (!empty($row['get']['action']))
					$row['action'] = $row['get']['action'];
				if (!empty($row['post']['action']))
					$row['action'] = $row['post']['action'];
				$pm->rows[] =& $row;
				unset($row);
			}
		}
		fclose($fp);
	}
	$pm->rows = array_reverse($pm->rows);
	
	return 'AdminAudit';
}
}
