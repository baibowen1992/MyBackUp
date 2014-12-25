<?













class ZMC_Alerts
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$_POST['subsystem'] = 'Zmanda Network';
		return ZMC_Events::run($pm, 'Monitor', 'ZMC - Monitor Alerts', 'alerts');
	}
}
