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
		self::KEEP_EXISTING => 'zmcIconSuccess',
		self::RENAME_RESTORED => 'zmcIconSuccess',
		self::RENAME_RESTORED_N => 'zmcIconSuccess',
		self::RENAME_EXISTING => 'zmcIconWarning',
		self::REMOVE_EXISTING => 'zmcIconWarning', 
		self::OVERWRITE_EXISTING => 'zmcIconWarning', 
	);

	public static function conflict2text($policy, $destination, $isFile = true)
	{
		if ($policy == self::KEEP_EXISTING)
			return "Do not restore the file.";
		if ($policy == self::RENAME_RESTORED)
			return "Rename the restored file by appending the date.";
		if ($policy == self::RENAME_RESTORED_N)
			return "Rename the restored file by appending a digit.";
		if ($policy == self::RENAME_EXISTING)
			return "Rename the existing file by appending the date.";
		if ($policy == self::OVERWRITE_EXISTING)
			return "Overwrite existing files/directories.";
		if ($policy == self::REMOVE_EXISTING) 
			return "Delete the existing file and contents.";
		if ($policy == self::NA)
			return "Not Applicable for file at $destination.";
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
			'field' => 'Original Location',
			'description' => 'Restores/replaces original data'),
		self::DIR_UNIX => array(
			'code' => 'UNIX',
			'field' => 'Destination Directory',
			'description' => 'Full *nix directory path on destination client',
			'unix_only' => true),
		self::DIR_WINDOWS => array(
			'code' => 'WINDOWS',
			'field' => 'Windows Folder',
			'description' => '<drive letter>:\\folder[\\folder]*',
			'zwc_only' => true),
		self::DIR_WINDOWS_SHARE => array(
			'code' => 'SHARE',
			'field' => 'Windows Network Share',
			'description' => '\\\\server\\share[\\folder]* (recommend: \\\\127.0.0.1\share\folder)',
			'zwc_only' => true),
		self::DIR_CIFS => array(
			'code' => 'CIFS',
			'field' => 'Network/CIFS Share',
			'description' => 'share[\\path]* (Exported directory path of network share on destination client.)',
			'dhn' => 'Amanda Client Host Name',
			'dhnHelp' => '&quot;localhost&quot; recommended. Fully qualified host name of machine (running Amanda CIFS client) with access to the destination network share',
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
			'field' => 'Unknown Location Type',
			'description' => 'Unknown Location Type',
			'unix_only' => true),
		self::DIR_RAW_IMAGE => array(
			'code' => 'RAW_IMAGE',
			'field' => 'Raw Image Path',
			'description' => 'Restores backup image only',
			'unix_only' => true),
		self::DIR_MS_SQLSERVER_ALTERNATE_PATH => array(
				'code' => 'SQL_ALT_SERVER',
				'field' => 'Alternate Path',
				'description' => 'Restore to new location and overwrite original database(s)',
				'zwc_only' => true),
		self::DIR_MS_SQLSERVER_ALTERNATE_NAME => array(
				'code' => 'SQL_ALT_LOCATION',
				'field' => 'Alternate Name and Path',
				'description' => 'Restore a copy of database(s) to original or new location',
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
			$helpMsg .= ' missing';
		else
			$helpMsg .= ' "' . $dir . '"';

		if (isset($okDirTypes[self::DIR_NDMP]))
		{
			$field = self::$dirTypes[self::DIR_NDMP]['field'];
			if (preg_match($ndmpRegExp, $dir))
				return $dir;
			if (count($okDirTypes) === 1) 
			{
				$pm->addWarnError("Invalid $field: $helpMsg\n$field must match: " . self::$dirTypes[self::DIR_NDMP]['description']);
				return $dir;
			}
			unset($okDirTypes[self::DIR_NDMP]);
		}

		if (isset($okDirTypes[self::DIR_MS_EXCHANGE]))
		{
			$field = self::$dirTypes[self::DIR_MS_EXCHANGE]['field'];
			if(preg_match('/[\\/:\*\?"<>|]+/', substr($dir, strlen($prefix))))
				$pm->addWarnError("Invalid $field: $helpMsg\nExchange DB name must not contain \\, /, :, *, ?, \", <, >, and |");
			return $dir;
		}

		if (isset($okDirTypes[self::DIR_UNIX]))
		{
			$field = self::$dirTypes[self::DIR_UNIX]['field'];
			if (!strncmp($dir, '/dev/', 5) || $dir === '/dev')
			{
				$pm->addWarnError("Invalid $field: *NIX directory/path must not be a device. Use the mount point instead.");
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
				$pm->addWarnError("Invalid $field: $helpMsg\nDirectories must begin with a forward slash \"/\".");
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
				$pm->addWarnError("Invalid $field: $helpMsg\n$field must match: " . self::$dirTypes[self::DIR_WINDOWS_SHARE]['description']);
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
				$pm->addWarnError("Invalid $field: $helpMsg\n$field must match: " . self::$dirTypes[self::DIR_VMWARE]['description']);
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
				$pm->addWarnError("Invalid $field: $helpMsg\n$field must match: " . self::$dirTypes[self::DIR_CIFS]['description']);
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
				$pm->addWarnError("Invalid $field: $helpMsg\n$field must match: " . self::$dirTypes[self::DIR_WINDOWS]['description'] . ' or ' . self::$dirTypes[self::DIR_WINDOWS_SHARE]['description']);
				$pm->addWarning('Windows directory paths must begin with [letter]:\\ or [letter]:/');
			}

			if (false !== strpbrk(substr($dir, 3), $badChars = ':*?"<>|')) 
			{
				$pm->addWarnError("Invalid $field: $helpMsg");
				$pm->addEscapedWarning('Windows folder and file names can not contain any of the following characters:  '
					. ZMC::escape($badChars) 
					. ' See <a target="_blank" href="http://en.wikipedia.org/wiki/NTFS">http://en.wikipedia.org/wiki/NTFS</a>.');
				$dir = $dir[0] . $dir[1] . $dir[2] . str_replace(array(':', '*', '?', '"', '<', '>', '|'), array(), substr($dir, 3));
			}
		}

		return $dir;
	}
}
