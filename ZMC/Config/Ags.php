<?


















class ZMC_Config_Ags extends ZMC_Config
{
	public function __construct($array = null)
	{
		$etc_zmc_ags = ZMC_ConfigHelper::$etc_zmanda . 'zmc_ags/';
		$etc_zmc_aee = ZMC_ConfigHelper::$etc_zmanda . 'zmc_aee/';

		
		$config = array(
			'changerdev_globs' => array('/dev/changer*' => true, '/dev/sg?' => true, '/dev/sg??' => true, '/dev/scsi/changer/*' => true), 
			'crontab'	=> $etc_zmc_ags . 'crontab',
			'crondir'	=> $etc_zmc_ags . 'cron',
			'default_units'	=> 'b', 
			'debug_uri' => false, 
			'dump'			=> false, 
			'etc_amanda'	=> '/etc/amanda/',
			'etc_zmc_ags'	=> $etc_zmc_ags,
			'etc_zmc_aee'	=> $etc_zmc_aee,
			'free_space'	=> array(
				'amanda_cfg_path',
				'amanda_debug_path',
				'zmc_log_dir',
				'zmc_pkg_base'
			),
			'long_name'		=> 'AEE REST Configuration Service',
			'max_clock_skew' => 30, 
			'messages'		=> array(
					'dle_collision' => 'Duplicate (or ambiguous) host/directory path combination detected.  If you really do want two objects/DLEs with the same host name and the same directory/path, please use the advanced form field "Alias" to differentiate the objects, and use the exclude list to prevent duplication of backup data. Amanda does not discriminate between the characters \\ / : and _ in host name and path names (which may be the cause of the ambiguity).  Please see <a href="http://network.wocloud.cn/lore/article.php?id=387">this KiB article</a> for a workaround.',
				),
			'name'			=> 'AEE GUI Server',
			'short_name_lc'	=> 'ags',
			'staging_deny'	=> $etc_zmc_aee . 'staging.deny',
			'tapedev_globs'	=> array('/dev/nst*' => '/^nst\d+$/'), 
			'test_conf_prefix' => 'zmc_test', 
			'var_log_zmc'	=> '/var/log/amanda/zmc', 
			'vcli_ok'		=> array('4.0' => 0, '4.1' => 0, '5.1.0' => 1, '5.1.1' =>1, '5.5.0' =>1 ), 
			'vtapes_deny'	=> $etc_zmc_aee . 'vtapes.deny',
			'windows_concurrent_partitions' => false,
			'device_profiles'	=> $etc_zmc_ags . 'device_profiles' . DIRECTORY_SEPARATOR, 
			'zmc_ags_version'	=> 3200, 
			'zmc_version'	=> 3, 
		);

		if (($array !== null) && count($array))
			ZMC::merge($config, $array);

		parent::__construct($config);
		$this->debug_log = dirname($this->debug_log) . DIRECTORY_SEPARATOR . 'yasumi.log';
		$this->debug_child_log = str_replace('yasumi', 'yasumi_child', $this->debug_log);
		$this->production_dump_log = $this->debug_log; 
		$this->error_log = $this->debug_log;
	}
}
