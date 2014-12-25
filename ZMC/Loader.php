<?

















class ZMC_Loader
{
	const DEBUG = false;

	







	public static function renderTemplate($filename, $presentationModel)
	{
		global $pm; 
		if (!isset($pm)) $pm = null;
		$savePm = $pm; 
		if (is_array($presentationModel)) 
			$pm = new ArrayObject($presentationModel, ArrayObject::ARRAY_AS_PROPS);
		elseif ($presentationModel instanceof ArrayObject)
			$pm = $presentationModel; 
		else
			throw new ZMC_Exception_Loader("模板 '$filename': 展示模型既不时一个array也不是ArrayObject类型 ("
											. print_r($presentationModel, true) . ')', __LINE__, $filename);

		$return = self::loadFile("$filename.php", 'ZMC/Templates');
		$pm = $savePm; 
		return $return;
	}

	









	public static function loadClass($classname, $dirname = null)
	{
		if ($classname === 'AmazonS3')
		{
			require 's3/sdk.class.php';
			require 's3/services/s3.class.php';
			return;
		}

		








		if (class_exists($classname, false) || interface_exists($classname, false))
			return; 

		if ($classname == ($filePath = str_replace('_', DIRECTORY_SEPARATOR, $classname)))
		{
			$filename = "$classname.php";
		}
		else
		{
			$dirPath = dirname($filePath); 
			if (empty($dirname) || ($dirname == '.'))
				$dirname = $dirPath;
			else
				$dirname = rtrim($dirname, '\\/') . DIRECTORY_SEPARATOR . $dirPath;
			$filename = basename($filePath) . '.php';
		}

		$return = self::loadFile($filename, $dirname, true, $classname);
		if (!class_exists($classname, false) && !interface_exists($classname, false))
			throw new ZMC_Exception_Loader("'$dirname/$filename' loaded, but class/interface '$classname' does not exist (using include path: " . get_include_path() . ')', $return, $filename, $dirname);

		return $return;
	}

	










	public static function loadFile($filename, $dirname = null, $includeOnce = false, $classname = '')
	{
		static $root = null;
		if ($root === null)
			$root = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		if (!ctype_alnum(strtr($filename, '.-_', 'aaa')))
			throw new ZMC_Exception_Loader("filename '$filename' contains unsafe characters", 1, $filename, $dirname);

		if (!empty($dirname))
		{
			$filepath = rtrim($dirname, '\\/') . DIRECTORY_SEPARATOR . $filename;
			if (self::DEBUG)
				error_log("classname=$classname; dirname=$dirname; root=$root; filepath=$filepath; filename=$filename;");
			if (is_readable($root . $filepath))
				return self::_include($filepath, $includeOnce); 
			$filename = $dirname . DIRECTORY_SEPARATOR . $filename;
		}

		if (self::isReadable($filename)) 
			return self::_include($filename, $includeOnce);

		if (empty($dirname))
			throw new ZMC_Exception_Loader("filename '$filename' not found"
				. (empty($classname) ? '' : " (while searching for '$classname')"), 1, $filename);
		else
			throw new ZMC_Exception_Loader("filename '$filename' not found "
				. "in either PHP's include path or directory '$root$filepath'"
				. (empty($classname) ? '' : " (while searching for '$classname')")
				. ' PHP include path=' . get_include_path(), 1, $filename, $dirname);
	}

	





	protected static function _include($filepath, $includeOnce = false)
	{
		if (self::DEBUG)
			error_log("Including $filepath " . ($includeOnce ? 'once':''));
		if ($includeOnce)
			return include_once $filepath;
		else
			return include $filepath ;
	}

	





	public static function isReadable($filename)
	{
		if (@is_readable($filename))
			return true;

		foreach (explode(PATH_SEPARATOR, get_include_path()) as $dir)
		{
			if ('.' == $dir) 
				continue;

			if (@is_readable($dir . DIRECTORY_SEPARATOR . $filename))
				return true;
		}

		return false;
	}

	





	public static function autoload($class)
	{
		try {
			self::loadClass($class);
			return $class;
		} catch (Exception $e) {
			return false;
		}
	}

	


	public static function register()
	{
		spl_autoload_register(array('ZMC_Loader', 'autoload'));
	}

	


	public static function unregister()
	{
		spl_autoload_unregister(array('ZMC_Loader', 'autoload'));
	}
}


class ZMC_Exception_Loader extends ZMC_Exception
{
	
	protected $filename = null;

	
	protected $dirname = null;

	public function __construct($message, $code, $filename = '', $dirname = '')
	{
		$this->filename = $filename;
		$this->dirname = $dirname;
		parent::__construct($message, $code);
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function getDirname()
	{
		return $this->dirname;
	}

	public function __toString()
	{
		return parent::__toString() . (empty($dirname) ? " ($filename)" : " ($dirname/$filename)");
	}
}
