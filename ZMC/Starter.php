<?













class ZMC_Starter
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Starter', 'ZMC - The ZMC Backup Process');
		
		$pm->admin_url = ZMC_HeaderFooter::$instance->getUrl('Admin', (empty($pm->device_profile_list) ? 'devices' : 'backup sets'));
		$pm->show = ZMC_User::get('show_starter_page', $_SESSION['user_id']);
		return 'BackupStarter';
	}
}
