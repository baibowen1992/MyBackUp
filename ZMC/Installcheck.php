<?













class ZMC_Installcheck
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Installcheck', 'ZMC - The ZMC Backup Process');
		$pm->admin_url = ZMC_HeaderFooter::$instance->getUrl('Admin', (empty($pm->device_profile_list) ? 'devices' : 'backup sets'));
		return 'Installcheck';
	}
}
