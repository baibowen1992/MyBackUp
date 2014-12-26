<?
//zhoulin-admin-backupset  201409191142












global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if ($pm->subnav === 'now') 
{
	if (ZMC_BackupSet::getName()) 
		require 'activate.php';
	$pm->tableTitle = '请选择一个备份集';
	$pm->buttons = array(
		'Refresh Table' => true,
		'激活' => true,
		'反激活' => true,
		'Edit' => true,
		'Abort' => true,
	);
}
else
{
	require 'createEditBackupSets.php';
	$pm->tableTitle = '查看、添加、编辑和删除备份集';
	$pm->buttons = array(
		'Refresh Table' => true,
		'Edit' => true,
//		'Duplicate' => false,
		'Delete' => true,
		'Abort' => true,
	);
	if (file_exists(dirname(__FILE__) . '/../BackupSet/Migration.php')) 
		foreach($pm->rows as $set)
			if ($set['version'] === '3.0' || is_dir(ZMC::$registry->etc_amanda . $set['configuration_name'] . '/3.0'))
			{
				$pm->buttons['Migrate'] = false;
				break;
			}
}
ZMC_Loader::renderTemplate('backupSets', $pm);
