<?













class ZMC_Type
{
	protected static $defaultType = null;
	protected static $init;

	protected static $keysArray = array(
		'priority'		=> true, 
		'vermpaw'		=> true, 
		'default'		=> true, 
		'label'			=> true, 
		'tip'			=> true, 
		'form_type'		=> true, 
		'html_before'	=> true, 
		'html_after'	=> true, 
		'attributes'	=> true, 
	);

	


















	protected static $vermpaw = array(
		'visible'	=> 'v',	
		'enabled'	=> 'e',	
		'required'	=> 'r',	
		'mutable'	=> 'm',	
		'preset'	=> 'p',	
		'advanced'	=> 'a',	
		'worm'		=> 'w',	
	);

	protected static $zmcTypes = array();
	protected static function init()
	{
		static::$init = true;
		foreach(static::$zmcTypes as $name => &$type)
		{
			$type['_key_name'] = $name;
			if (!isset($type['license_group']))
				$type['license_group'] = $name;
		}
	}

	public static function &get($zmcType = null, $failureOk = false)
	{
		static::init();
		if ($zmcType === null)
			return static::$zmcTypes;

		if (!isset(static::$zmcTypes[$zmcType]))
			if ($failureOk)
				return false;
			else
				throw new ZMC_Exception("无效的备份项类型 '$zmcType'.");

		if (empty(static::$defaultType))
		{
			foreach(static::$defaultTypeArray['form'] as $field => &$meta)
				ZMC::mergeKeys($meta, array_keys(self::$keysArray));
			static::$defaultType = new ZMC_Registry(static::$defaultTypeArray);

			foreach(static::$zmcTypes as $type => &$keys)
				foreach($keys['form'] as $field => &$meta)
					if (is_array($meta))
					{
						if (is_integer(key($meta)))
							ZMC::mergeKeys($meta, array_keys(self::$keysArray));
					}
					elseif (!empty($meta))
					{
						if	(isset(static::$zmcTypes[$meta]) && array_key_exists($field, static::$zmcTypes[$meta]['form']))
							$meta = static::$zmcTypes[$meta]['form'][$field]; 
						else
							throw new ZMC_Exception("Invalid type specification for type '$type' field '$field' : " . print_r($meta, true));
					}
		}

		$result = clone static::$defaultType;
		$result->merge(static::$zmcTypes[$zmcType]);
		foreach(static::$labelFixes as $label => $names)
		{
			if (!isset($result->form[$label])) 
				continue;

			if (!is_array($result->form[$label]['default'])) 
			{
				if ($result->form[$label]['vermpaw'][1] === 'e') 
					continue; 
				else
					ZMC::quit(array('label' => $label, 'form' => $result->form));
			}

			foreach($result->form[$label]['default'] as $key => &$value)
				$value = array('label' => $names[$key], 'default' => $value);
		}

		foreach($result['form'] as $field => &$meta)
		{
			
			
			if (empty($meta['vermpaw'])){
				if(preg_match('/^vmware/', $zmcType))
					continue;
				ZMC::quit(array('Inheritance only works if inherited type appears first in Type/*.php', $field, $meta, $result));
			}
			if (isset($result['form'][$meta['vermpaw']]))
				$vermpaw = $result['form'][$meta['vermpaw']]['vermpaw'];
			else
				$vermpaw = $meta['vermpaw'];

			$found = 0;
			foreach(static::$vermpaw as $key => $letter)
				if (false !== ($pos = stripos($vermpaw, $letter)))
				{
					if (!isset($meta[$key])) 
						$meta[$key] = (ctype_upper($vermpaw[$pos]) ? true : false);
					$found++;
				}

			if ($found !== count(static::$vermpaw))
				throw new ZMC_Exception("Invalid VERMPAW '$vermpaw' for field '$field' (only found $found): " . print_r($meta, true));

			foreach($meta as $metaFieldname => $ignored)
				if (!isset(self::$keysArray[$metaFieldname]) && !isset(static::$vermpaw[$metaFieldname]))
					throw new ZMC_Exception(__FILE__ . " - Invalid field name '$metaFieldname' for field '$field' of type '$zmcType'");
		}

		return $result;
	}

	public static function getKey($zmcType, $key)
	{
		static::init();
		if (isset(static::$zmcTypes[$zmcType][$key]))
			return static::$zmcTypes[$zmcType][$key];
		ZMC::quit(array($zmcType, $key));
	}

	public static function hasLicenseGroup($feature, $feature2group)
	{
		static::init();
		$group = ZMC::ilookup($feature, $feature2group);
		foreach(static::$zmcTypes as $type)
			if ($type['license_group'] === $group)
				return true;

		return false;
	}

	public static function getName($zmcType)
	{
		static::init();
		if (!isset(static::$zmcTypes[$zmcType]))
		{
			ZMC::debugLog(__CLASS__ . '::' . __FUNCTION__ . "(): Invalid type '$zmcType'");
			return false;
		}

		return static::$zmcTypes[$zmcType]['name'];
	}

	public static function getNames(array $histogram)
	{
		return implode(', ', array_filter(array_map(array(__CLASS__, 'getName'), $histogram)));
	}

	public static function getPrettyNames($group = false)
	{
		static::init();
		$result = array();
		foreach(static::$zmcTypes as $name => $type)
			if (isset($type['pretty_name']))
				$result[$group ? $type['license_group'] : $name] = $type['pretty_name'];
			elseif (!isset($result[$type['license_group']]))
				$result[$group ? $type['license_group'] : $name] = $type['name'];
			else
				$result[$name] = $type['name'];

		return $result;
	}

	



	public static function getLicenseGroup($zmcType)
	{
		static::init();
		if (isset(static::$zmcTypes[$zmcType]))
			return static::$zmcTypes[$zmcType]['license_group'];

		ZMC::debugLog(__CLASS__ . '::' . __FUNCTION__ . "(): 非法的许可证类型 '$zmcType'");
		return false;
	}

	
	
	public static function addExpireWarnings(ZMC_Registry_MessageBox $pm)
	{
		static::init();
		if (empty($pm->lstats) || empty($pm->lstats['licenses']['zmc']))
			if (ZMC::$registry->debug)
				ZMC::quit("lstats empty");
			else
				return $pm->addInternal("无法增加许可证到期警告.");
			
		$products =& $pm->lstats['licenses']['zmc'];
		$expiring = array();
		$expired = array();
		foreach(static::$zmcTypes as $zmcType => &$type)
		{
			$group = $type['license_group'];
			$numExpiring = (isset($products['Expiring'][$group]) ? $products['Expiring'][$group] : 0);
			$numExpired = (isset($products['Expired'][$group]) ? $products['Expired'][$group] : 0);
			if ($numExpiring)
				if ($products['Licensed'][$group] === $numExpiring)
					$expiring[] = 'all ' . $type['name']; 
				elseif ($products['Licensed'][$group] > ($numExpiring + $numExpired))
					$expiring[] = 'some ' . $type['name'];
				else
					$expiring[] = 'all unexpired ' . $type['name']; 

			if ($numExpired)
				if ($products['Licensed'][$group] === $numExpired)
					$expired[] = 'all ' . $type['name']; 
				else
					$expired[] = 'some ' . $type['name'];
		}

		$url = ZMC::getPageUrl($pm, 'Admin', 'Licenses', 'ZMC licenses');
//		if (count($expiring))
//			$pm->addEscapedWarning("$url expire soon for: " . ZMC::escape(implode(', ', $expiring)));
//
//		if (count($expired))
//			$pm->addEscapedWarning("$url have expired for: " . ZMC::escape(implode(', ', $expired)));
	}

	public static function getIcon(ZMC_Registry_MessageBox $pm, $type, &$disabled, $attribs = '', $css = '')
	{
		static::init();
		if (is_string($type))
			$type = static::$zmcTypes[$type];
		if (isset($type['_key_name']))
			$type = static::$zmcTypes[$type['_key_name']];
		if (!isset($type['license_group']) || !isset($type['_key_name']))
			ZMC::quit($type);
		$licenseGroup = $type['license_group'];
		$icon = '/images/icons/icon_' . $type['_key_name'];
		$licensed = $disabled = '';
		if (	empty($pm->lstats['licenses']['zmc']['Licensed'][$licenseGroup])
			||	!($pm->lstats['licenses']['zmc']['Licensed'][$licenseGroup] > 0))
		{
			$icon .= '_disabled';
			$disabled = ' disabled="disabled" ';
			$attribs = '';
		}
	   	$icon .= $type['icon'];
		return "<img title='" . ZMC::escape($type['name']). "' style='$css' src='$icon' $attribs />\n";
	}

    //added by zhoulin.search if the device is in used or not
    public static function getdeviceused($name)
    {
        $sql = "SELECT device FROM configurations where device='$name'";
        return ZMC_Mysql::getAllRowsMap($sql, '无法读取数据表 "configurations" ', false, null, 'id');
    }
	
	public static function mergeCreationDefaults(&$type, $convertToDisplayUnits = false)
	{
		if (!is_array($type) || empty($type['_key_name']))
			throw new ZMC_Exception('Invalid object/DLE type: ' . print_r($type, true));
		static::init();
		if (empty(static::$zmcTypes[$type['_key_name']])) 
			throw new ZMC_Exception('Invalid object/DLE type: ' . print_r($type, true));
		if (!array_key_exists('creationDefaults', static::$defaultTypeArray)) 
			throw new ZMC_Exception('Invalid object/DLE type: ' . print_r(static::$defaultTypeArray, true));
		$merged = static::$defaultTypeArray['creationDefaults'];
		if (isset(static::$zmcTypes[$type['_key_name']]['creationDefaults']))
			ZMC::merge($merged, static::$zmcTypes[$type['_key_name']]['creationDefaults']);
		if ($convertToDisplayUnits)
			ZMC::convertToDisplayUnits($merged);
		ZMC::assertNoColons($type); 
		ZMC::merge($merged, $type);
		$type = $merged;
	}

	public static function opDiscoverTapes(ZMC_Registry_MessageBox $pm)
	{
		$tapes = self::discoverDeviceHelper($pm, '/Tape-Drive/discover_tapes', 'tapedev_list',
			'No tape drives found. Is a tape drive connected and powered on? Does it have a tape loaded? Please make sure the user "amandabackup" can access the changer using the "mt" program.'
		);
		array_shift($tapes);
		array_pop($tapes);
		
		$result = array();
		if(!empty($tapes)){
			foreach($tapes as $tape => $val)
				$result[$tape] = ZMC::filterDigits($tape, 0);
		}
		$next = max($result) +1;
		$reg = array();
		for($i = 0; $i < 3; $i++)
		{
			$result["Other$next"] = 'skip';
			$reg["driveslot_Other$next"] = '?';
			$next++;
		}
		ZMC_HeaderFooter::$instance->addRegistry($reg);
		return $result;
	}

	public static function opDiscoverChangers(ZMC_Registry_MessageBox $pm)
	{
		return self::discoverDeviceHelper($pm, '/Tape-Drive/discover_changers', 'changerdev_list',
			'No changers found. Is a changer connected and powered on?  Some changers require manual configuration of an appropriate /dev/sg* device driver on the server host. Please make sure the user "amandabackup" can access the changer using the "mtx" program.'
		);
	}

	
	private static function discoverDeviceHelper(ZMC_Registry_MessageBox $pm, $pathInfo, $key, $errMsg)
	{
	
		if($key == 'tapedev_list'){
			if(!empty($pm->binding['changer']['tapedev'])){
				foreach($pm->binding['changer']['tapedev'] as $k => $v)
					if($v == null)
						unset($pm->binding['changer']['tapedev'][$k]);
			}
			$result = ZMC_Yasumi::operation($pm, array('pathInfo' => $pathInfo, 'data' => array('tape_dev' =>$pm->binding['changer']['tapedev'])));
		}
		else
			$result = ZMC_Yasumi::operation($pm, array('pathInfo' => $pathInfo,));

		$pm->merge($result);

		$list = array('' => 'Please Select ..');
		if (empty($result[$key]))
			$pm->addError($errMsg);
		else
		{
			if (isset($result['warnings']))
				unset($result['warnings']);
			if (isset($result['errors']))
				unset($result['errors']);
			ZMC_HeaderFooter::$instance->addRegistry($result);
			foreach($result[$key] as $name => $value)
				if (!empty($value['show']))
					$list[$name] = $name;
		}
		$list['0'] = '--OTHER--';
		return $list;
	}

	public static function opGetMaxSlots(ZMC_Registry_MessageBox $pm)
	{
		return $pm->binding['max_slots'];
	}
}
