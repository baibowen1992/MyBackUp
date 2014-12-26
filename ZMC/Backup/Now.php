<?













class ZMC_Backup_Now
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		return ZMC_Admin_BackupSets::run($pm, 'Backup', '云备份 - 激活', 'now');
	}
}
