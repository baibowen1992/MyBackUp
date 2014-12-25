<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";

if ($pm->subnav === 'now') 
{
	if (ZMC_BackupSet::getName()) 
		require 'activate.php';
	$pm->tableTitle = 'Select a backup set';
	$pm->buttons = array(
		'Refresh Table' => true,
		'Activate' => false,
		'Deactivate' => false,
		'Edit' => false,
		'Abort' => false,
	);
}
else
{
	require 'createEditBackupSets.php';
	$pm->tableTitle = 'View, add, edit, and delete backup sets';
	$pm->buttons = array(
		'Refresh Table' => true,
		'Edit' => false,
		'Duplicate' => false,
		'Delete' => false,
		'Abort' => false,
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
