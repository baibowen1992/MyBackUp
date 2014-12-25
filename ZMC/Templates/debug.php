<?














global $pm;
if (!empty($_GET['key']))
	ZMC::quit($pm->$_GET['key']);
ZMC::quit($pm);
