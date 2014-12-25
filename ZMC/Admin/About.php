<?













class ZMC_Admin_About
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'About', 'About the Zmanda Management Console');

		if (ZMC::$registry->debug_level >= ZMC_Error::NOTICE)
		{
			$pm->versions = array();
			foreach (glob(ZMC::$registry->etc_zmanda_product . '*/*svn*.cnf', GLOB_NOSORT) as $filename)
				$pm->versions[basename($filename)] = file_get_contents($filename);

			ob_start();
			passthru('/usr/sbin/amadmin randomtext version | head -n 4');
			$pm->versions['Amanda'] = ob_get_clean();
		}

		return 'About';
	}
}
