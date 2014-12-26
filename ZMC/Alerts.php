<?













class ZMC_Alerts
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$_POST['subsystem'] = 'wocloud';
		return ZMC_Events::run($pm, 'Monitor', '云备份 - 告警', 'alerts');
	}
}
