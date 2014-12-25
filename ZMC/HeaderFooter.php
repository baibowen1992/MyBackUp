<?













class ZMC_HeaderFooter
{
	
	public static $instance;

	
	protected $pm = null;

	
	protected $enableFooter = true;

	
	protected $tombstone = null;

	
	protected $pageTitle = null;

	
	protected $subnav = null;

	
	protected $symfony = null;

	
	protected $injectYuiCode = '';

	
	private $javascript = array();

	
	private $yui = array();

	
	private $jsRegistry = null;

	public function __construct()
	{
		self::$instance = $this;
	}

	public function runFrontController($class, ZMC_Registry_MessageBox $pm)
	{
		ZMC::inputLog();
		if ($percent = ZMC_Timer::testMemoryUsage())
		{
			$pm->addWarning($msg = "Low Memory: $percent of PHP memory used");
			ZMC::debugLog("ZMC: $msg");
		}
		try
		{
			$template = call_user_func(array($class, 'run'), $pm);
			$errorsAndWarnings = $pm->cloneErrorsAndWarnings();
			$action = (empty($_REQUEST['action']) ? 'no button pushed' : $_REQUEST['action']);
			if (!empty($errorsAndWarnings) || !empty($_REQUEST['action'] ))
				ZMC_Events::add(str_replace('ZMC_', '',
					wordwrap(str_replace(array('?', '&', '=', '/'), array('? ', ' &', ' = ', ' / '), trim(urldecode($_SERVER['REQUEST_URI']), '/')), 48, "\n", true)) . '=>' . $action,
					($errorsAndWarnings && $errorsAndWarnings->isErrors()) ? ZMC_Error::ERROR : ZMC_Error::NOTICE,
					$errorsAndWarnings);

			if (!empty($pm->next_state))
				$pm->state = $pm->next_state;
		}
		catch(Exception $exception)
		{
			if (!ZMC::$registry->debug)
			{
				ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?date=' . $exception->date . '&error=' . bin2hex($exception->getLocation()), __FILE__, __LINE__);
				
				exit;
			}

			if ($exception instanceof ZMC_Exception)
			{
				$pm->addInternal("$exception");
				$pm->addDetail($exception->toString(true));
			}
			elseif (ZMC::$registry->debug)
				$pm->addInternal("$exception");
			else
				$pm->addInternal($exception->getMessage());

			ZMC_Loader::renderTemplate('MessageBox', $pm);
			$this->footer(true); 
		}

		if (empty($template)){
			ZMC::$registry->dev_only ? ZMC::quit() : $pm->addInternal('Missing template' . $template);
		}

		if ($template !== 'Login')
			if (empty($pm->confirm_template))
			{
				if (!empty($template) && ($template !== 'MessageBox') && !empty($this->yui))
					$pm->show_yui_loader_div = true;
				ZMC_Loader::renderTemplate('MessageBox', $pm);
			}
			else
			{
				ZMC_Loader::renderTemplate($pm->confirm_template, $pm);
				if ($pm->confirm_template === $template)
				{
					$this->footer(true); 
					exit;
				}
			}

		$template = empty($_GET['template']) ? $template : $_GET['template'];
		if (!empty($template) && ($template !== 'MessageBox'))
			ZMC_Loader::renderTemplate($template, $pm);

		$this->footer(true); 
		exit;
	}

	final public function enableFooter($bool)
	{
		$this->enableFooter = $bool;
	}

	





	final public function footer($quit = true)
	{
		if ($this->enableFooter)
			$this->footerCode();
		$this->close($quit);
	}

	protected function footerCode()
	{
		
		
	}

	



	final public function close($quit = true)
	{
		static $once = false;
		if ($once) 
			throw new ZMC_Exception(__CLASS__ . '::' . __FUNCTION__ . "() or footer() called twice!\nbacktrace=" . ZMC_Error::backtrace());

		$once = true;


			$this->yui();
		$this->javascript();
	
		echo "\n<div style='clear:both;'></div></body>\n</html>";
	
		if (!empty($_SESSION['user_id']))
			ZMC::shutdown($quit);

		if ($quit) 
			exit;
	}

	








	public function addJavaScript($filename, $priority = 1)
	{
		$this->javascript[$filename] = $priority;
	}
	
	









	public function addYui($module, $dependencies = array())
	{
		if (is_string($dependencies))
		{
			$this->yui[$module] = array($dependencies);
		}
		elseif (is_array($dependencies))
		{
			$this->yui[$module] = $dependencies;
		}
		else
		{
			throw new ZMC_Exception('dependencies must be an array (contents: ' . ZMC_Error::backtrace(-5, $dependencies) . ')');
		}
	}
	
	





	public function addRegistry($registry)
	{
		if (!is_array($registry) && !($registry instanceof ZMC_Registry))
		{
			throw new ZMC_Exception(__CLASS__ . '::' . __FUNCTION__ . '() argument must be either array or ZMC_Registry' .  ZMC_Error::backtrace(-10));
		}

		if (!empty($_SESSION['user_id']))
		{
			($this->jsRegistry === null) && $this->jsRegistry = new ZMC_Registry();
			$this->jsRegistry->merge($registry);
		}
	}

	


	public function injectYuiCode($code)
	{
		$this->injectYuiCode .= "\n$code\n";
	}

	


	protected function javascript()
	{
		if (!empty($this->javascript))
		{
			asort($this->javascript); 
			foreach(array_keys($this->javascript) as $filename)
			{
				echo '<script src="', ZMC::$registry->scripts, $filename, "\"></script>\n";
			}
		}
	}	

	






	private function yui()
	{
		





		$inits = array(
			'debug' => ZMC::$registry['debug'],
			'userId' => empty($_SESSION['user_id']) ? '0' : $_SESSION['user_id'],
			'timezone' => ZMC::$registry['tz'],
			'userName' => empty($_SESSION['user']) ? 'unknown' : $_SESSION['user'],
			'backupSet' => ZMC_BackupSet::getId(),
			'backupSetName' => ZMC_BackupSet::getName()
		);
		if (empty($this->jsRegistry))
			$this->jsRegistry = $inits;
		else
			$this->jsRegistry = array_merge($this->jsRegistry->getArrayCopy(), $inits);
		$zmcRegistryTmp = ZMC::escapedJson($this->jsRegistry);

		$scripts = (ZMC::$registry->scripts)? ZMC::$registry->scripts: "/scripts/";
		echo <<<EOD

<script src="{$scripts}yui/yuiloader-dom-event/yuiloader-dom-event.js"></script>
<script src="{$scripts}yui/json/json-min.js"></script>
<script>
var zmcRegistryTmp = '$zmcRegistryTmp';

function zmcYuiInit(repeatCount)
{
	if (!('YAHOO' in window) || !('lang' in YAHOO) || !('JSON' in YAHOO.lang))
	{
		if (--repeatCount)
			return setTimeout('zmcYuiInit(' + repeatCount + ')', 3333)
	
		if (confirm("ZMC has not yet finished loading required javascript files from the AEE server. Do you want to continue waiting for these files to load?"))
			return setTimeout('zmcYuiInit(90)', 333) // wait another 30 seconds

		alert('ZMC was not able to load the required JavaScript files from the AEE server to your Web browser.  Please check connectivity and logout of ZMC, before trying again.')
	}

	var o = gebi('div_yui_loading')
	if (o)
		o.style.display = 'none'
	zmcRegistry = YAHOO.lang.JSON.parse(zmcRegistryTmp)
	ZMC = (function(){})
	ZMC.base = {scripts: window.location.protocol + '//' + window.location.host + '{$scripts}'};
	zmcloader = new YAHOO.util.YUILoader(
	{
		base: ZMC.base['scripts'] + 'yui/',
		allowRollup: true,
		require: ['json'],
		//loadOptional: true,
		onSuccess: function()
		{
			yuiComplete = true;

			{$this->injectYuiCode}

			if (YAHOO.zmc && YAHOO.zmc.utils && typeof YAHOO.zmc.utils.enable_datatable_buttons === 'function')
				YAHOO.zmc.utils.enable_datatable_buttons()
		},
		onFailure: function(msg, xhrobj)
		{
			alert('JS: YUI Loader failed');
			var m = "LOAD FAILED: " + msg;
			if (xhrobj)
				m += ", " + YAHOO.lang.dump(xhrobj);

			YAHOO.log(m);
		},
		skin:
		{
			'defaultSkin': 'none', // 'sam', //@TODO: make YUI stop loading a skin
			'base': 'assets/skins/'
			//'after': ['reset', 'fonts']
		}
	});

EOD;
		foreach($this->yui as $module => $dependencies)
		{
			$requires = empty($dependencies) ? '' : ("'" . join("', '", $dependencies) . "'");
			echo <<<EOD

	zmcloader.addModule(
	{
		name: '$module',
		type: 'js',
		path: '../$module.js',
		requires: [$requires]
	}); 
	zmcloader.require('$module')
EOD;
		}

		echo <<<EOD

	zmcloader.insert();
}
if (typeof noZmcYuiInit == 'undefined')
	zmcYuiInit(25);
</script>
EOD;
	}
}
