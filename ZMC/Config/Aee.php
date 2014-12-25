<?
//zhoulin-config-Aee 201409201230

















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
			'long_name'		=> '云备份管理控制台',
			'media_tape'	=> 'Tape',
			'media_tapes'	=> 'Tapes',
			'name'			=> '云备份',
			'required_license_version' => "3.0",
			'restore_log'	=> false,
			'rss'			=> 'http://www.wocloud.cn',
			's3certs_path'	=> ZMC_ConfigHelper::$etc_zmanda . 's3certs', 
			'short_name'	=> 'AEE',
			'short_name_lc'	=> 'aee',
			'start_page'	=> '/ZMC_Admin_BackupSets', 
			'sync_always'	=> true,
			'test_internet_connectivity' => true,
			'tips'			=> array(
				'backup_what' => '请选择需要备份的数据类型(文件系统，数据库)在选择各个备份项组成备份集时主要考虑的是备份目标、备份周期、保存周期等等',
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
