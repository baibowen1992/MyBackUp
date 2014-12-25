<?













global $pm;
$pm->display = (empty($pm->rows) ? 'none' : 'block');
$pm->display = 'block';
echo "\n<form id='explore_form' method='post' action='$pm->url'>\n";
ZMC_Loader::renderTemplate('restoreWhatSelect', $pm); 

if ((!empty($pm->rows) && $pm->restore['restore_type'] !== ZMC_Restore::EXPRESS))
	ZMC_Loader::renderTemplate('restoreWhatRbox', $pm);

ZMC_Loader::renderTemplate('restoreWhatDetails', $pm); 

if ($pm->restore['restore_type'] !== ZMC_Restore::EXPRESS)
	if ($pm->exploring || $pm->finished)
			ZMC_Loader::renderTemplate('restoreWhatLbox', $pm);

echo '</form>';
