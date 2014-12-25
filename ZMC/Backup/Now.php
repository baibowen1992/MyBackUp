<?













class ZMC_Backup_Now
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		return ZMC_Admin_BackupSets::run($pm, 'Backup', 'ZMC - Backup Now or Activate Backup Set', 'now');
	}
}
