<?














class ZMC_Type_AmandaApps
{
	const KEEP_EXISTING		= 'KEEP_EXISTING'; 
	const RENAME_RESTORED	= 'RENAME_RESTORED'; 
	const RENAME_RESTORED_N	= 'RENAME_RESTORED_N'; 
	const RENAME_EXISTING	= 'RENAME_EXISTING'; 
	const OVERWRITE_EXISTING= 'OVERWRITE_EXISTING'; 
	const REMOVE_EXISTING	= 'REMOVE_EXISTING'; 
	const NA				= 'NA';

	
	public static $default_options = array(
		self::KEEP_EXISTING => 'wocloudIconSuccess',
		self::RENAME_RESTORED => 'wocloudIconSuccess',
		self::RENAME_RESTORED_N => 'wocloudIconSuccess',
		self::RENAME_EXISTING => 'wocloudIconWarning',
		self::REMOVE_EXISTING => 'wocloudIconWarning', 
		self::OVERWRITE_EXISTING => 'wocloudIconWarning', 
	);

	public static function conflict2text($policy, $destination, $isFile = true)
	{
		if ($policy == self::KEEP_EXISTING)
			return "不恢复任何文件.";
		if ($policy == self::RENAME_RESTORED)
			return "重命名恢复文件并追加数据.";
		if ($policy == self::RENAME_RESTORED_N)
			return "重命名恢复文件并追加数字.";
		if ($policy == self::RENAME_EXISTING)
			return "重命名已存在文件并追加数据.";
		if ($policy == self::OVERWRITE_EXISTING)
			return "覆盖现有文件/目录.";
		if ($policy == self::REMOVE_EXISTING) 
			return "删除现有文件和内容";
		if ($policy == self::NA)
			return "不适用目录 $destination  下的文件.";
		throw new ZMC_Exception("Invalid policy: $policy");
	}

	
	private static $global_properties = array(
		'restore_to_original_requires_express' => false,
	);

	










	private static $zmc_app = array(
		ZMC_Restore::EXPRESS => array(
			'gtar'			=> array('excludable' => true,  'globable' => true,  'searchable' => false), 
			'bsdtar'		=> array('excludable' => true,  'globable' => true,  'searchable' => false), 
			'dump'			=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'oracle'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'rman'			=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'postgresql'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'star'			=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'suntar'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'zfssendrecv'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'zfssnapshot'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			
			'cifs'			=> array('excludable' => true,  'globable' => false, 'searchable' => false), 
			'ndmpnetapp'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'ndmpbluearc'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'ndmpsun'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'ndmpemc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'vmware'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'vmware_quiesce_on'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'vmware_quiesce_off'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'windowsdump'	=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'zwcinc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'zwcdiff'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
			'zwclog'		=> array('excludable' => false, 'globable' => false, 'searchable' => false), 
		),
		
		ZMC_Restore::SELECT => array(
			'gtar'			=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'bsdtar'		=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'dump'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'oracle'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'rman'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'postgresql'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'star'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'suntar'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'zfssendrecv'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'zfssnapshot'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'cifs'			=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpnetapp'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpbluearc'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpsun'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpemc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware_quiesce_on'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware_quiesce_off'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			
			
			
			'windowsdump'	=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => false),
			'zwcinc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			'zwcdiff'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			'zwclog'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			
		),
		ZMC_Restore::SEARCH => array(
			'gtar'			=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'bsdtar'		=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'dump'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'oracle'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'rman'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'postgresql'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'star'			=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'suntar'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'zfssendrecv'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'zfssnapshot'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'cifs'			=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpnetapp'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpbluearc'	=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpsun'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'ndmpemc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware_quiesce_on'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			'vmware_quiesce_off'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => true), 
			
			
			
			'windowsdump'	=> array('excludable' => true,  'globable' => false, 'searchable' => false, 'recursive' => false),
			'zwcinc'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			'zwcdiff'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			'zwclog'		=> array('excludable' => false, 'globable' => false, 'searchable' => false, 'recursive' => false), 
			
		),
		
		
		
		'common_properties' => array(
			'gtar'			=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => true,
				'extension' => '.tar',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'bsdtar'			=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => true,
				'extension' => '.tar',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'dump'			=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => false,
				'extension' => '.dump',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'oracle'		=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => array(self::OVERWRITE_EXISTING),
				),
				'extension' => '.oracle',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'rman'			=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => array(self::OVERWRITE_EXISTING),
				),
				'extension' => '.rman',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'postgresql'	=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => array(self::OVERWRITE_EXISTING),
				),
				'extension' => '.postgresql',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'star'			=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'conflict_resolvable' => true,
				'browseable' => true,
				'extension' => '.tar',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'suntar'		=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => true,
				'extension' => '.tar',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/opt', 
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'zfssendrecv'	=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => false,
				'conflict_resolvable' => false,
				'extension' => '.zfssendrecv',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/opt', 
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'zfssnapshot'	=> array(
				'backup_dir_type' => self::DIR_UNIX,
				'browseable' => true,
				'conflict_resolvable' => false,
				'extension' => '.zfssnapshot',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_UNIX,
				'temp_dir_default' => '/opt', 
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'cifs'			=> array( 
				'backup_dir_type' => self::DIR_CIFS,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_CIFS => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => true
				),
				'extension' => '.tar',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => array(self::DIR_CIFS, self::DIR_UNIX),
				'temp_dir_default' => '/tmp', 
				'temp_dir_types' => self::DIR_UNIX, 
				'user_name' => 'root',
				'zwc' => false,
			),
			'ndmpnetapp'	=> array(
				'backup_dir_type' => self::DIR_NDMP,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_NDMP => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => true,
				),
				'extension' => '.ndmp',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_NDMP,
				'temp_dir_default' => '/tmp', 
				'temp_dir_types' => self::DIR_UNIX, 
				'user_name' => 'root',
				'zwc' => false,
			),
			'ndmpbluearc'	=> array(
				'backup_dir_type' => self::DIR_NDMP,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_NDMP => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => true,
				),
				'extension' => '.ndmp',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_NDMP,
				'temp_dir_default' => '/tmp', 
				'temp_dir_types' => self::DIR_UNIX, 
				'user_name' => 'root',
				'zwc' => false,
			),
			'ndmpsun'		=> array(
				'backup_dir_type' => self::DIR_NDMP,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_NDMP => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => true,
				),
				'extension' => '.ndmp',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_NDMP,
				'temp_dir_default' => '/tmp', 
				'temp_dir_types' => self::DIR_UNIX, 
				'user_name' => 'root',
				'zwc' => false,
			),
			'ndmpemc'		=> array(
				'backup_dir_type' => self::DIR_NDMP,
				'browseable' => true,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL => array(self::OVERWRITE_EXISTING),
					self::DIR_NDMP => array(self::OVERWRITE_EXISTING),
					self::DIR_UNIX => true,
				),
				'extension' => '.ndmp',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => self::DIR_NDMP,
				'temp_dir_default' => '/tmp', 
				'temp_dir_types' => self::DIR_UNIX, 
				'user_name' => 'root',
				'zwc' => false,
			),
			'vmware'		=> array(
				'backup_dir_type' => self::DIR_VMWARE,
				'browseable' => false,
				'conflict_resolvable' => false,
				'extension' => '.vmware',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => array(self::DIR_VMWARE, ),
				'temp_dir_auto' => false,
				'temp_dir' => '/tmp/amanda',
				'temp_dir_default' => false,
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'vmware_quiesce_on'		=> array(
				'backup_dir_type' => self::DIR_VMWARE,
				'browseable' => false,
				'conflict_resolvable' => false,
				'extension' => '.vmware',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => array(self::DIR_VMWARE, ),
				'temp_dir_auto' => false,
				'temp_dir' => '/tmp/amanda',
				'temp_dir_default' => false,
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'vmware_quiesce_off'		=> array(
				'backup_dir_type' => self::DIR_VMWARE,
				'browseable' => false,
				'conflict_resolvable' => false,
				'extension' => '.vmware',
				'host_type' => self::HOST_TYPE_UNIX,
				'target_dir_types' => array(self::DIR_VMWARE, ),
				'temp_dir_auto' => false,
				'temp_dir' => '/tmp/amanda',
				'temp_dir_default' => false,
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'root',
				'zwc' => false,
			),
			'windowsdump'	=> array(
				'backup_dir_type' => array(self::DIR_WINDOWS, self::DIR_WINDOWS_SHARE),
				'browseable' => true,
				'conflict_dir_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_file_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS_SHARE	=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_UNIX => true,
				),
				'extension' => '.zip',
				'host_type' => self::HOST_TYPE_WINDOWS,
				'target_dir_types' => array(self::DIR_WINDOWS, self::DIR_WINDOWS_SHARE, self::DIR_UNIX),
				
				'temp_dir_default' => '/tmp',
				'temp_dir_types' => self::DIR_UNIX,
				'user_name' => 'amandabackup',
				'zwc' => true,
			),
			'zwcinc'		=> array(
				'backup_dir_type' => self::DIR_WINDOWS,
				'browseable' => true,
				'conflict_dir_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_file_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS_SHARE	=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
				),
				'extension' => '.zip',
				'host_type' => self::HOST_TYPE_WINDOWS,
				'target_dir_types' => self::DIR_WINDOWS,
				'temp_dir_default' => '',
				'temp_dir_types' => array(),
				'user_name' => 'amandabackup',
				'zwc' => true,
			),
			'zwcdiff'		=> array(
				'backup_dir_type' => self::DIR_WINDOWS,
				'browseable' => true,
				'conflict_dir_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_file_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
				'conflict_resolvable' => array(
					self::DIR_ORIGINAL		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					self::DIR_WINDOWS_SHARE	=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
				),
				'extension' => '.zip',
				'host_type' => self::HOST_TYPE_WINDOWS,
				'target_dir_types' => self::DIR_WINDOWS,
				'temp_dir_default' => '',
				'temp_dir_types' => array(),
				'user_name' => 'amandabackup',
				'zwc' => true,
			),
			'zwclog'		=> array(
					'backup_dir_type' => self::DIR_WINDOWS,
					'browseable' => true,
					'conflict_dir_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
					'conflict_file_selected' => ZMC_Type_AmandaApps::KEEP_EXISTING,
					'conflict_resolvable' => array(
							self::DIR_ORIGINAL		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
							self::DIR_WINDOWS		=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
							self::DIR_WINDOWS_SHARE	=> array( self::KEEP_EXISTING, self::RENAME_RESTORED, self::RENAME_EXISTING, self::OVERWRITE_EXISTING,),
					),
					'extension' => '.zip',
					'host_type' => self::HOST_TYPE_WINDOWS,
					'target_dir_types' => self::DIR_WINDOWS,
					'temp_dir_default' => '',
					'temp_dir_types' => array(),
					'user_name' => 'amandabackup',
					'zwc' => true,
			),
		)
	);

	const HOST_TYPE_UNIX = 'host_type_unix';
	const HOST_TYPE_WINDOWS = 'host_type_windows';

	
	const DIR_DO_NOT_USE = 0;	
	const DIR_UNKNOWN = 1;		
	const DIR_UNIX = 2;			
	const DIR_WINDOWS = 3;		
	const DIR_WINDOWS_SHARE = 4;
	const DIR_CIFS = 5;			
	const DIR_VMWARE = 6;		
	const DIR_MS_EXCHANGE = 7; 
	const DIR_NDMP = 8;		
	const DIR_ORIGINAL = 9;	
	const DIR_RAW_IMAGE = 10;	
	const DIR_NODEV = 11;		
	const DIR_MS_SQLSERVER_ALTERNATE_PATH = 12;
	const DIR_MS_SQLSERVER_ALTERNATE_NAME = 13;
	
	public static $dirTypes = array( 
		self::DIR_MS_EXCHANGE => array(
			'code' => 'EXCHANGE',
			'field' => 'Exchange Recovery DB',
			'description' => 'Name of MS Exchange recovery DB',
			'zwc_only' => true),
		self::DIR_ORIGINAL => array(
			'code' => 'ORIGINAL',
			'field' => '原路径',
			'description' => '还原并替换原始文件'),
		self::DIR_UNIX => array(
			'code' => 'UNIX',
			'field' => '目的目录',
			'description' => ' *nix 下客户端全路径',
			'unix_only' => true),
		self::DIR_WINDOWS => array(
			'code' => 'WINDOWS',
			'field' => 'Windows目录',
			'description' => '<drive letter>:\\folder[\\folder]*',
			'zwc_only' => true),
		self::DIR_WINDOWS_SHARE => array(
			'code' => 'SHARE',
			'field' => 'Windows共享目录',
			'description' => '\\\\server\\share[\\folder]* (推荐: \\\\127.0.0.1\share\folder)',
			'zwc_only' => true),
		self::DIR_CIFS => array(
			'code' => 'CIFS',
			'field' => 'Network/CIFS共享',
			'description' => 'share[\\path]* (目标节点的网络共享目录.)',
			'dhn' => 'Amanda Client Host Name',
			'dhnHelp' => '&quot;localhost&quot; 推荐. 运行云备份CIFS客户端主机的FQDN ，需要拥有目标网络共享的使用权限',
			'unix_only' => true),
		self::DIR_VMWARE => array(
			'code' => 'VMWARE',
			'field' => 'Alternate Location',
		    'description' => 'Restore to alternate location',
			'zwc_only' => false),
		self::DIR_NDMP => array(
			'code' => 'NDMP',
			'field' => 'NDMP Path',
			'description' => '//&lt;Filer host name or IP&gt;/&lt;volume name&gt;/&lt;directory&gt;',
			'unix_only' => true),
		self::DIR_UNKNOWN => array(
			'code' => 'UNKNOWN',
			'field' => '未知目的地类型',
			'description' => '未知目的地类型',
			'unix_only' => true),
		self::DIR_RAW_IMAGE => array(
			'code' => 'RAW_IMAGE',
			'field' => 'Raw 镜像地址',
			'description' => '仅仅还原备份镜像',
			'unix_only' => true),
		self::DIR_MS_SQLSERVER_ALTERNATE_PATH => array(
				'code' => 'SQL_ALT_SERVER',
				'field' => '备用路径',
				'description' => '还原到新位置，覆盖原始数据库',
				'zwc_only' => true),
		self::DIR_MS_SQLSERVER_ALTERNATE_NAME => array(
				'code' => 'SQL_ALT_LOCATION',
				'field' => '备用名称和路径',
				'description' => '还原数据库到原路径或者新路径',
				'zwc_only' => true),
	);

	public static function getTargetDirTypes($types)
	{ return array_intersect_key(self::$dirTypes, array_flip((is_array($types) ? $types : array($types)))); }

	public static function setTempDirDefault(ZMC_Registry_MessageBox $pm, array &$restore)
	{
		if ($restore['zwc'] && ($restore['target_dir_selected_type'] == ZMC_Type_AmandaApps::DIR_UNIX))
			$restore['temp_dir_auto'] = true; 
		if (!$restore['temp_dir_auto'])
		{
			if (empty($restore['temp_dir']))
				$restore['temp_dir'] = $restore['temp_dir_default'];
			return;
		}

		if (empty($restore['temp_dir_types'])) 
		{
			$restore['temp_dir_selected_type'] = null;
			$restore['temp_dir'] = '';
			return;
		}

		
		$restore['temp_dir_selected_type'] = current($restore['temp_dir_types']);
		if (!array_intersect(array(self::DIR_UNIX), $restore['temp_dir_types']))
			return; 

		if (empty($restore['target_dir']) || ($restore['target_dir_selected_type'] == self::DIR_ORIGINAL))
			$restore['temp_dir'] = $restore['disk_device']; 
		else
			$restore['temp_dir'] = $restore['target_dir']; 

		if ($restore['zmc_type'] === 'cifs')
		{
			if (	($restore['target_dir_selected_type'] == self::DIR_ORIGINAL)
				||	($restore['target_dir_selected_type'] == self::DIR_CIFS))
				$restore['temp_dir'] = $restore['temp_dir_default'];
		}
	}

	public static function setOptions(ZMC_Registry_MessageBox $pm, array &$restore, $restoreType)
	{
		









		$pm->zmc_type = ZMC_Type_What::get($restore['zmc_type']);
		if (empty($restore['zmc_amanda_app'])) 
			throw new ZMC_Exception(__CLASS__ . '::' . __FUNCTION__ . __LINE__ . "($restoreType, $app) - restore[zmc_amanda_app] is empty:" . print_r($restore, true));
		$app = $restore['zmc_amanda_app'];
		if(preg_match('/^vmware/', $app))
			$app = 'vmware';
		if (!isset(self::$zmc_app['common_properties'][$app])) 
		{
			$pm->addWarnError("This DLE was created with the custom application \"$app\" (a user-defined Amanda dumptype), and can not be restored using the GUI.  Please use the \"amrecover\" command-line tool.");
			return false;
		}
		if (!isset(self::$zmc_app[$restoreType][$app])) 
			return false;
		$pm->zmc_app = self::$global_properties; 
		ZMC::merge($pm->zmc_app, self::$zmc_app['common_properties'][$app]); 
		ZMC::merge($pm->zmc_app, self::$zmc_app[$restoreType][$app]); 
		
		foreach(array_keys($pm->zmc_app) as $key)
		{
			$restore[$key] = $pm->zmc_app[$key];
			if (isset($pm->zmc_type[$key])) 
				if (is_bool($pm->zmc_type[$key]))
					$restore[$key] = $pm->zmc_type[$key] && $pm->zmc_app[$key]; 
				else
					$restore[$key] = $pm->zmc_type[$key]; 
		}

		
		$restore['temp_dir_default'] = $pm->zmc_app['temp_dir_default'];
		$restore['zwc'] = $pm->zmc_app['zwc'];
		$restore['host_type'] = $pm->zmc_app['host_type'];
		$restore['user_name'] = $pm->zmc_app['user_name'];
		$restore['globable'] = $pm->zmc_app['globable'];
		foreach($pm->zmc_type as $key => $value)
			if ($key !== 'form')
				if (!isset($restore[$key]))
					$restore[$key] = $value;
				elseif(ZMC::$registry->dev_only)
					ZMC::errorLog("Warning: zmc_type '$key=$value' ignored. Already set in restore job to: " . $restore[$key]);

		if ($restoreType === ZMC_Restore::EXPRESS)
			$restore['browseable'] = false; 

		foreach(array('target_dir_types', 'temp_dir_types') as $key)
			if (($restore[$key] === null) || ($restore[$key] === ''))
				$restore[$key] = array();
			elseif (!is_array($restore[$key]))
				$restore[$key] = array($restore[$key]);

		if (!$restore['restore_to_original_requires_express'] || ($restoreType === ZMC_Restore::EXPRESS))
			array_unshift($restore['target_dir_types'], self::DIR_ORIGINAL);

		return true;
	}

	



	
	public static function assertValidDir(ZMC_Registry_MessageBox $pm, $dir, $okDirTypes = self::DIR_UNKNOWN, $helpMsg = '')
	{

		if (!is_array($okDirTypes))
			$okDirTypes = array($okDirTypes);

		$okDirTypes = array_flip($okDirTypes); 
		$ndmpRegExp		= '/^\\/(\\/[^\\/]+){3,}$/';
		$cifsRegExp		= '/^\\\\(\\\\[^\\\\\\/]+){2,}$/';
		$vmwareRegExp	= '/^\\\\(\\\\[^\\\\\\/]+){3}$/'; 
		$shareRegExp	= $cifsRegExp; 

		if (empty($helpMsg))
			$helpMsg = $dir;
		elseif (empty($dir))
			$helpMsg .= ' 为空';
		else
			$helpMsg .= ' "' . $dir . '"';

		if (isset($okDirTypes[self::DIR_NDMP]))
		{
			$field = self::$dirTypes[self::DIR_NDMP]['field'];
			if (preg_match($ndmpRegExp, $dir))
				return $dir;
			if (count($okDirTypes) === 1) 
			{
				$pm->addWarnError("无效的 $field: $helpMsg\n$field 必须匹配: " . self::$dirTypes[self::DIR_NDMP]['description']);
				return $dir;
			}
			unset($okDirTypes[self::DIR_NDMP]);
		}

		if (isset($okDirTypes[self::DIR_MS_EXCHANGE]))
		{
			$field = self::$dirTypes[self::DIR_MS_EXCHANGE]['field'];
			if(preg_match('/[\\/:\*\?"<>|]+/', substr($dir, strlen($prefix))))
				$pm->addWarnError("无效的 $field: $helpMsg\nExchange DB 名必须包含 \\, /, :, *, ?, \", <, >, and |");
			return $dir;
		}

		if (isset($okDirTypes[self::DIR_UNIX]))
		{
			$field = self::$dirTypes[self::DIR_UNIX]['field'];
			if (!strncmp($dir, '/dev/', 5) || $dir === '/dev')
			{
				$pm->addWarnError("无效的 $field: *NIX 路径不能是一个设备，请使用挂载点替代。");
				return $dir;
			}
			if (strlen($dir) && $dir[0] === '/') 
				if ($dir === '/')
					return str_replace('//', '/', $dir);
				else
					return str_replace('//', '/', rtrim($dir, '/'));

			if (count($okDirTypes) === 1) 
			{
				if(isset($pm->restore['program']))
					if(preg_match("/amzfs-sendrecv/", $pm->restore['program']))
						return $dir;
				if (preg_match("/zfssendrecv|zfssnapshot/", $_POST['property_list:zmc_custom_app']))
					return $dir;
				$pm->addWarnError("无效 $field: $helpMsg\n目录必须是以反斜杠 \"/\" 开头.");
				return $dir;
			}
			unset($okDirTypes[self::DIR_UNIX]);
		}

		if (isset($okDirTypes[self::DIR_WINDOWS_SHARE]))
		{
			$field = self::$dirTypes[self::DIR_WINDOWS_SHARE]['field'];
			if (preg_match($shareRegExp, $dir))
				return $dir;

			if (count($okDirTypes) === 1) 
			{
				$pm->addWarnError("无效 $field: $helpMsg\n$field 必须匹配： " . self::$dirTypes[self::DIR_WINDOWS_SHARE]['description']);
				return $dir;
			}
			unset($okDirTypes[self::DIR_WINDOWS_SHARE]);
		}

		if (isset($okDirTypes[self::DIR_VMWARE]))
		{
			$field = self::$dirTypes[self::DIR_VMWARE]['field'];
			if (preg_match($vmwareRegExp, $dir))
				return $dir;

			if (count($okDirTypes) === 1) 
			{
				$pm->addWarnError("无效的 $field: $helpMsg\n$field 必须匹配： " . self::$dirTypes[self::DIR_VMWARE]['description']);
				return $dir;
			}
			unset($okDirTypes[self::DIR_VMWARE]);
		}

		if (isset($okDirTypes[self::DIR_CIFS]))
		{
			$field = self::$dirTypes[self::DIR_CIFS]['field'];
			if (preg_match($cifsRegExp, $dir))
				return $dir;

			if (count($okDirTypes) === 1) 
			{
				$pm->addWarnError("无效的 $field: $helpMsg\n$field 必须匹配： " . self::$dirTypes[self::DIR_CIFS]['description']);
				return $dir;
			}
			unset($okDirTypes[self::DIR_CIFS]);
		}

		if (isset($okDirTypes[self::DIR_UNKNOWN]) || isset($okDirTypes[self::DIR_WINDOWS]))
		{
			$field = self::$dirTypes[self::DIR_WINDOWS]['field'];
			ZMC::normalizeWindowsDle($dir); 
			if (	strlen($dir) <= 1
				|| !ctype_alpha($dir[0])
				|| $dir[1] !== ':'
				|| ($dir[2] !== '/' && $dir[2] !== '\\'))
			{
				$pm->addWarnError("无效的 $field: $helpMsg\n$field 必须匹配： " . self::$dirTypes[self::DIR_WINDOWS]['description'] . ' or ' . self::$dirTypes[self::DIR_WINDOWS_SHARE]['description']);
				$pm->addWarning('Windows目录必须以[分区符]:\\ 或者 [分区符]:/ 开头');
			}

			if (false !== strpbrk(substr($dir, 3), $badChars = ':*?"<>|')) 
			{
				$pm->addWarnError("无效的 $field: $helpMsg");
				$pm->addEscapedWarning('Windows目录和文件名不能包含下列符号： '
					. ZMC::escape($badChars) 
					. ' See <a target="_blank" href="http://localhost</a>.');
				$dir = $dir[0] . $dir[1] . $dir[2] . str_replace(array(':', '*', '?', '"', '<', '>', '|'), array(), substr($dir, 3));
			}
		}

		return $dir;
	}
}
