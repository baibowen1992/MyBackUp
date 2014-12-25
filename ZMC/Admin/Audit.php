<?
//zhoulin-admin-audit 201409172103












class ZMC_Admin_Audit
{
public static function run(ZMC_Registry_MessageBox $pm)
{
	$pm->skip_backupset_start = true;
	ZMC_HeaderFooter::$instance->header($pm, 'Admin', '云备份 - 审计', 'audit'); 
	$pm->addDefaultInstruction('按时间线查看用户操作记录，包含详细的编辑和更改。在开启详细记录之后，鼠标悬停在紫色圆圈内的“i”图标可以查看相关的详细记录。.');
	if (!ZMC_User::hasRole('Administrator'))
	{
		$pm->addError('仅允许管理员进行该操作.');
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
