<?













class ZMC_HeaderFooter_Aee extends ZMC_HeaderFooter
{
	static protected $links = array(
		'About' => 'ZMC_Admin_About',
		'Admin' => array(
			'users' => 'ZMC_Admin_Users',
			'backup sets' => 'ZMC_Admin_BackupSets',
			'devices' => 'ZMC_Admin_Devices',
			'preferences' => 'ZMC_Admin_Preferences',
			'advanced' => 'ZMC_Admin_Advanced',
			'audit'	=> 'ZMC_Admin_Audit',
			'licenses' => 'ZMC_License',
		),
		'Backup' => array(
			'list' => 'ZMC_Backup_List',
			'what' => 'ZMC_Backup_What',
			'where' => 'ZMC_Backup_Where',
			'staging' => 'ZMC_Backup_Staging',
			'how' => 'ZMC_Backup_How',
			
			'when' => 'ZMC_Backup_When',
			'now' => 'ZMC_Backup_Now',
			'media' => 'ZMC_Backup_Media',
		),
		'Vault' => array(
				'what' => 'ZMC_Vault_What',
				'where' => 'ZMC_Vault_Where',
				'when' => 'ZMC_Vault_When',
				'jobs' => 'ZMC_Vault_Jobs',
				'media' => 'ZMC_Vault_Media',
				'reports' => 'ZMC_Vault_Reports',
		),
		'Login' => 'ZMC_Admin_Login',
		'Monitor' => array(
			'backups' => 'ZMC_Monitor',
			'alerts' => 'ZMC_Alerts',
			'events' => 'ZMC_Events',
		),
		'Report' => array(
			'backups' => 'ZMC_Report_Backups',
			'restores' => 'ZMC_Report_Restores',
			'timeline' => 'ZMC_Report_Timeline',
			'media' => 'ZMC_Report_Media',
			'data' => 'ZMC_Report_Data',
			'custom' => 'ZMC_Report_Custom',
			'data integrity' => 'ZMC_Report_Integrity',
		),
		'Restore' => array(
			'what' => 'ZMC_Restore_What',
			'where' => 'ZMC_Restore_Where',
			'how' => 'ZMC_Restore_How',
			'now' => 'ZMC_Restore_Now',
			'log' => 'ZMC_Restore_Log',
			'search' => 'ZMC_Restore_Search',

		),
		'Starter' => 'ZMC_Starter',
		'Installcheck' => 'ZMC_Installcheck',
		'OperatorStarter' => 'ZMC_Starter',
		'MonitorStarter' => 'ZMC_Monitor',
		'RestoreOnlyStarter' => 'ZMC_Report_Backups',
		'X' => array( 
			'cloud' => 'ZMC_X_Cloud', 
		)
	);

	static protected $defaults = array(
		'Admin' => 'backup sets',
		'Backup' => 'what',
		'Monitor' => 'backups',
		'Report' => 'backups',
		'Restore' => 'what',
		'Vault' => 'what'
	);

	static protected $roles = array(
		'Operator' => array(
			'About' => true,
			'Admin' => array('users', 'backup sets', 'preferences', 'advanced', 'licenses'), 
			'Backup' => array('list', 'what', 'where', 'staging', 'media', 'when', 'how', 'verify', 'now'),
			'Login' => true,
			'Monitor' => array('backups', 'alerts', 'events'),
			'Report' => array('backups', 'restores','timeline', 'media', 'data', 'custom', 'data integrity'),
			'Restore' => array('what', 'where', 'how', 'now'),
			'Vault' => array('what', 'where', 'how', 'jobs', 'media'),
			'Starter' => true,
			'Installcheck' => true,
		),
		'Monitor' => array(
			'About' => true,
			'Admin' => array('users', 'backup sets', 'preferences'),
			'Login' => true,
			'Monitor' => array('backups', 'alerts', 'events'),
			'Report' => array('backups', 'restores','timeline', 'media', 'data', 'custom', 'data integrity'),
			'Restore' => array(),
			'Starter' => true,
			'Installcheck' => true,
		),
		'RestoreOnly' => array(
			'About' => true,
			'Admin' => array('users', 'backup sets', 'preferences'),
			'Login' => true,
			'Monitor' => array('backups', 'alerts', 'events'),
			'Restore' => array('what', 'where', 'how', 'now', 'log', 'search'),
			'Report' => array('backups', 'restores','timeline', 'media', 'data', 'custom', 'data integrity'),
			'Starter' => true,
			'Installcheck' => true,
		),
	);

	public function __construct()
	{
		parent::__construct();
		if (!ZMC::$registry->advanced_disklists)
			unset(self::$links['Backup']['list']);
		if (!ZMC::$registry->restore_log)
		{
			unset(self::$links['Restore']['log']);
			unset(self::$links['Restore']['search']);
		}
		



		foreach(self::$roles as &$role)
			foreach($role as &$tombstone)
				if (is_array($tombstone))
					$tombstone = array_flip($tombstone);
	}

	public function getUrl($tombstone, $subnav = '')
	{
		$tombstone = ucFirst(strtolower($tombstone));
		if ($tombstone === 'Login')
			return '/' . self::$links[$tombstone]; 

		$subnav = strtolower($subnav);
		$role = ZMC_User::get('user_role');
		if (isset(self::$roles[$role])) 
		{
			if (!isset(self::$roles[$role][$tombstone]))
				return '';
			if ($subnav !== '')
				if (is_array(self::$roles[$role][$tombstone]))
					if (!isset(self::$roles[$role][$tombstone][$subnav]))
						return '';
		}

		if ($role === 'Administrator')
		{
			if (!is_array(self::$links[$tombstone]) || $subnav === '')
			{
				if (isset(self::$links[$tombstone]))
					return '/' . self::$links[$tombstone];
			}
		}
		else
		{
			if (!is_array(self::$roles[$role][$tombstone]) || $subnav === '')
				if (isset(self::$links[$role . $tombstone]))
					return '/' . self::$links[$role . $tombstone];
				else
					return '/' . self::$links[$tombstone];
		}

		if (!empty(self::$links[$tombstone][$subnav]))
			return '/' . self::$links[$tombstone][$subnav];

		ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex("No link found for page: $tombstone|$subnav."), __FILE__, __LINE__);
		
	}

	






	public function header(ZMC_Registry_MessageBox $pm, $tombstone="Backup", $pageTitle="Zmanda Management Console", $subnav="", $xhtml_head = null)
	{
		$this->pm = $pm; 
		if (	!empty($_SESSION['disk_space_check_errors']) 
			||	(empty($_SESSION['disk_space_check']) || $_SESSION['disk_space_check'] < time() - ZMC::$registry->disk_space_check_frequency)) 
		{
			$_SESSION['disk_space_check'] = time();
			ZMC::checkDiskSpace($pm); 
		}

		if (isset($_POST['Begin']))
			ZMC_User::set($_SESSION['user_id'], 'show_starter_page', isset($_POST['Dismiss'])? 1:0);

		if (!isset($_SESSION['tab'])) 
			foreach(self::$links as $tstone => $tlist)
				if (is_array($tlist) && isset(self::$defaults[$tstone]))
					$_SESSION['tab'][$tstone] = self::$links[$tstone][self::$defaults[$tstone]];
				else
					$_SESSION['tab'][$tstone] = self::$links[$tstone];

		$pm->tombstone = $this->tombstone = $tombstone = ucFirst(strtolower($tombstone));
		$this->pageTitle = $pageTitle;
		if (is_array(self::$links[$tombstone]) && empty($subnav))
			throw new ZMC_Exception('Why is $subnav empty?');
		$pm->subnav = $this->subnav = $subnav = strtolower($subnav);
		
		checkForGenuineSession();
		$pm->url = $_SESSION['tab'][$tombstone] = $this->getUrl($tombstone, $subnav);
		$pm->help_link = ZMC::$registry->wiki . $pm->tombstone . $pm->subnav . '#';
		if ($pm->url === '')
			throw new ZMC_Exception("This ZMC user account ($_SESSION[user]) does not have the correct role to access the requested page in ZMC ($tombstone|$subnav).");
		
		if (!empty($subnav))
			ZMC::perControllerStartup($tombstone, $subnav); 
	
		if (isset($_POST['selected_ids'])) 
		{
			ZMC::$userRegistry['selected_ids'] = array(); 
			unset($_POST['selected_ids']['0']); 
			foreach($_POST['selected_ids'] as $key => $on)
				ZMC::$userRegistry['selected_ids'][urldecode($key)] = true;
		}

		$aboutUrl = $this->getUrl('About');
		$pm->product_datestamp = ZMC::dateNow(true);
		if (file_exists($fn = ZMC::$registry['tmp_path'] . 'zmc.svn'))
			$pm->product_datestamp .= ' ' . file_get_contents($fn);
		ZMC_Loader::renderTemplate('Header', array(
			'tombstone' => $tombstone,
			'subnav' => $subnav,
			'product_datestamp' => $pm->product_datestamp,
			'title' => $pageTitle,
			'short_name' => ZMC::$registry['short_name'],
			'xhtml_head' => ($xhtml_head === null ? '<base href="' . ZMC::getUrl() . $pm->url . '">' : $xhtml_head),
		));
	
		ZMC_Loader::renderTemplate('BodyBegin',	array(
			'about_url'		=> (($tombstone === 'About') ? '' : $aboutUrl),
			'admin_users_url' => $this->getUrl('Admin', 'users'),
			'login_url'		=> $this->getUrl('Login'),
			'logo'			=> ZMC::$registry['logo'],
			'page_info'		=> ($tombstone == 'About') ? 'Starter' : $tombstone,
			'short_name'	=> ZMC::$registry['short_name'],
			'title'			=> $pageTitle,
			'wiki'			=> ZMC::$registry['wiki'],
		));

		$this->subnav($tombstone, $subnav);
		if (!empty($_POST['formName']) && $_POST['formName'] == 'zmandaNetworkLogin')

			ZMC_ZmandaNetwork::form($pm); 
		elseif (empty($_GET) && empty($_POST)) 
		{
			$checkedRecently = isset($_SESSION['zmanda_network_last']) && ($_SESSION['zmanda_network_last'] > (time() - ZMC::$registry->zn_frequency));
			if (isset($_GET['zn']) || !$checkedRecently)
			{
				ZMC_ZmandaNetwork::form($pm);

				if ($pm->offsetExists('zmandaNetworkLogin')) 
				{
					echo $pm->zmandaNetworkLogin;
					$this->close(__CLASS__);
					exit; 
				}
			}
		}

	}

	
	protected function subnav($Tab, $sub)
	{
		echo <<<EOD
<div id="subNav">
	<ol>
EOD;
		if (!isset(self::$links[$Tab]))
			error_log("***Zmanda Dev Team: Please add $Tab to \$links ***");
		elseif(is_array(self::$links[$Tab]))
			foreach(array_keys(self::$links[$Tab]) as $description)
			{
				$link = self::getUrl($Tab, $description);
				if (empty($link))
					echo "		<li class='disabled'>$description</li>\n";					
				else
					echo "		<li><a href='$link' ", ($sub === $description) ? 'style="font-weight:bold;" ':'', ">$description</a></li>\n";
			}
		echo <<<EOD
	</ol>
</div><!-- subNav -->
</div><!-- header -->




EOD;
		if(preg_match('/\/ZMC_Installcheck/', $this->pm->url)){
			echo "<br /><br />";
			echo '<div class="zmcWindow"><div class="zmcTitleBar" style="position:relative; ">Server Installation Information</div>';
			echo '<a class="zmcHelpLink" id="zmcHelpLinkId" href="'.ZMC::$registry->wiki . $this->pm->tombstone . ucFirst($this->pm->subnav) . '"  target="_blank"></a>';
			echo '<div class="" style="text-align: left; padding: 10px; border-left-width: 10px; position: relative;"> ';
			
		}

	}

	



	protected function footerCode($quit = true)
	{
		if (empty($this->pm->skip_backupset_start) &&
			(!empty(ZMC::$registry->always_show_switcher) || !empty($this->pm->enable_switcher)))
		{
			
	?>
<div class="alertsHeadingExpanded">
	<a class="zmcHelpLink" style="top:10px;" target="_blank" href="<?=ZMC::$registry->wiki?>FilterByBackupSet"></a>
	<div class="alertsHeadingTitle">
		Backup Set:&nbsp; <form name='form1' id='form1' action='<?= $this->pm->url ?>' method='post'>
			<?
			if (ZMC_BackupSet::count())
			{
				?>
				<select 
					name='ConfigurationSwitcher' 
					style='float:none; padding:0; max-width:195px;'
					title='Change Backup Set'
					onchange="var o = this.form['action']; if (o) o.value = ''; this.form.submit();"
				>
				<?
				if (false === ($myName = ZMC_BackupSet::getName()))
					echo "<option value='0'>Please select ...</option>\n";
				foreach(ZMC_BackupSet::getMyNames() as $name => $id)
					echo '<option value="' . urlencode($name) . '"', (($myName == $name) ? ' selected="selected" ' : ''),
						">", ZMC::escape($name), "</option>\n";
				echo "\t\t\t</select>\n";
			}
			else
				echo '<a href="', $this->getUrl('Admin', 'backup sets'), '">create one now</a>';
			?>
			<input type="hidden" name="form1Submitted" value="TRUE" />
		</form>
		&nbsp;
	</div>
</div><!-- alertsHeadingExpanded -->
<?
		}
		parent::footerCode();
	}
}
