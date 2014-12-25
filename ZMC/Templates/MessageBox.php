<?













global $pm;

$pm->injectStickyMessages();
if (false === ob_start())
	ZMC::headerRedirect(ZMC::$registry->bomb_url, __FILE__, __LINE__);


	ZMC::debugLog($pm->getAllMerged()); 

$special = 'left';
$default = 'right';
 $noInternals = ZMC_Templates_MessageBoxHelper::display('icon_calendar_failure', 'InternalErrors', $pm->internals, $pm->escapedInternals, 'left');
{
	$special = 'right';
	$default = 'left';
}

$boxes = array();
$biggest = '';
for ($i = 3; $i; $i--)
{
	ob_start();
	if ($i === 3)
		$noErrors = ZMC_Templates_MessageBoxHelper::display('icon_calendar_failure', 'Errors', $pm->errors, $pm->escapedErrors, $default);
	if ($i === 2)
		$noWarnings = ZMC_Templates_MessageBoxHelper::display('icon_calendar_warning', 'Warnings', $pm->warnings, $pm->escapedWarnings, $default);
	if ($i === 1) 
		$noDefaults = ZMC_Templates_MessageBoxHelper::display('icon_calendar_success', 'Messages', $pm->messages, $pm->escapedMessages, $default);

	if (ob_get_length() === 0)
	{
		ob_get_clean();
		continue;
	}

	if (ob_get_length() <= strlen($biggest))
		$boxes[] = ob_get_clean();
	else
	{
		if ($biggest !== '')
			$boxes[] = $biggest;
		$biggest = ob_get_clean();
	}
}
echo empty($boxes) ? $biggest : str_replace("float:$default", "float:$special", $biggest);
foreach($boxes as $box) 
	echo $box;

ZMC_Templates_MessageBoxHelper::display('', 'Details', $pm->details, $pm->escapedDetails, $default);

if (!empty($pm->exception))
	ZMC::renderException($pm->exception);

$hideBox = '';
$hidePadding = 'display:none;';
if (!ob_get_length() || ($noErrors && $noWarnings && $noInternals && $noDefaults))
	if (ZMC_Templates_MessageBoxHelper::display('instructions', 'Instructions', $pm->instructions, $pm->escapedInstructions, $default))
	{
		$hideBox = 'display:none;'; 
		$hidePadding = '';
	}
$box = str_ireplace('Yasumi', 'AGS', ob_get_clean()); 
$id = (empty($pm->id) ? ZMC_Templates_MessageBoxHelper::$boxNumber++ : $pm->id);
echo "\t\t<div class='wocloudMessageBox' id='wocloudMessageBox$id' style='$hideBox'>\n";
if (!empty($pm->show_yui_loader_div))
	echo '<div id="div_yui_loading" style="position:absolute; right:20px; z-index:999; font-size:11px;">载入中 <img style="vertical-align:middle;" title="..." src="/images/icons/icon_calendar_progress.gif" height="18" width="20" /></div>';
echo "		$box\n<div style='clear:both'></div>\n";
echo "\t\t</div><!-- wocloudMessageBox -->\n";
echo "<div id='wocloudMessageBoxPad$id' style='height:10px; $hidePadding'></div>";
