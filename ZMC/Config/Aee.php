<?


















class ZMC_Config_Aee extends ZMC_Config
{
	public function __construct($array = null)
	{
		$etc_zmc_ags = ZMC_ConfigHelper::$etc_zmanda . 'zmc_ags/';
		$etc_zmc_aee = ZMC_ConfigHelper::$etc_zmanda . 'zmc_aee/';

		
		$config = array(
			'always_show_switcher' => true,
			'allow_dropping_vtapes' => false,
			'auto_exclude_unix_dirs' => true,
			'auto_exclude_windows_dirs' => false,
			'dle_windows_deny' => $etc_zmc_aee . 'dle_windows.deny',
			'dle_unix_deny' => $etc_zmc_aee . 'dle_unix.deny',
			'advanced_disklists' => false,
			'etc_amanda'	=> '/etc/amanda/',
			'free_space'	=> array(
				'amanda_cfg_path',
				'amanda_debug_path',
				'mysql_path',
				'zmc_log_dir',
				'zmc_pkg_base'
			),
			'logo'			=> 'logo-zmc-aee.png',
			'long_name'		=> 'Zmanda Management Console',
			'media_tape'	=> 'Tape',
			'media_tapes'	=> 'Tapes',
			'name'			=> 'Amanda Enterprise Edition',
			'required_license_version' => "3.0",
			'restore_log'	=> false,
			'rss'			=> 'http://network.zmanda.com/WebServices/RSS/feeds/feed.xml?zmc=1',
			's3certs_path'	=> ZMC_ConfigHelper::$etc_zmanda . 's3certs', 
			'short_name'	=> 'AEE',
			'short_name_lc'	=> 'aee',
			'start_page'	=> '/ZMC_Admin_BackupSets', 
			'sync_always'	=> true,
			'test_internet_connectivity' => true,
			'tips'			=> array(
				'backup_what' => 'Specify the type of data (filesystem, database, or application) and client information. Group different items into different backup sets depending on backup target, desired frequency of backups, desired retention period, etc.',
			),
			'var_log_zmc'	=> '/var/log/amanda/zmc', 
			'zmc_db_version'=> 101, 
			'device_profiles'	=> $etc_zmc_ags . 'device_profiles' . DIRECTORY_SEPARATOR, 
			'zmc_version'	=> 3, 
		);

		if (($array !== null) && count($array))
			ZMC::merge($config, $array);

		parent::__construct($config);
	}
}
