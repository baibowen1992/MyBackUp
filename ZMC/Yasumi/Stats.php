<?
















class ZMC_Yasumi_Stats extends ZMC_Yasumi
{
	const ZMC_LICENSE_FILENAME = '/etc/zmanda/zmanda_license';

	private static $feature2group = array(
		'aws' => 's3'
		
	);

	private function disklistHistogram()
	{
		$this->reply['zmc_type_histograms'] = array();
		$this->reply['zmc_host_histograms'] = array();
		$this->reply['zmc_amcheck_histograms'] = array();
		$find = (!empty($this->data['filename_of_proposed_disklist_edit']) ? true : false);
		foreach(glob(ZMC::$registry->etc_amanda . '*/disklist.conf') as $fn)
		{
			$basename = basename(dirname($fn));
			if ($find)
			{
				if ($basename == $this->getAmandaConfName())
					if (!is_readable($fn = $this->data['filename_of_proposed_disklist_edit']))
						throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Unable to read: $fn"));
					else
						$find = false;
				$this->debugLog(__FUNCTION__ . " fn=$fn; basename=$basename; conf=" . $this->getAmandaConfName());
			}
					
			if (!strncmp($basename, 'zmc_test_', 21))
				continue;

			$lines = file($fn, FILE_SKIP_EMPTY_LINES );
			foreach($lines as &$line)
			{
				if (false !== ($pos = strpos($line, '#')))
					$line = substr($line, 0, $pos);
				if (false !== ($pos = strpos($line, '"zmc_amcheck"')))
					if (false === strpos($line, '""', $pos + 14))
						@$this->reply['zmc_amcheck_histograms'][$basename]++;
			}
			$all = implode('', $lines);
			$lines = preg_split('/property\s+"zmc_type"\s+/', $all);
			for($j=count($lines) -1; $j > 0; $j = $j - 1)
		    {
				$dle = explode("\n", $lines[$j-1]);
				for($i=count($dle)-1; $i >= 0; $i--)
				{
					if (empty($dle[$i]))
						continue;
					$c1 = $dle[$i][0];
					if ($c1 === ' ' || $c1 === '	')
						continue;
					if (preg_match('/^(\S+)\s+".+"\s+{\s*$/', $dle[$i], $matches))
					{
						$zmcType = strtolower(substr($lines[$j], 1, strpos($lines[$j], '"', 3) -1));

						if ($zmcType === 'postgres') 
							$licenseGroup = $zmcType;
						else
							$licenseGroup = ZMC_Type_What::getLicenseGroup($zmcType);

						if (empty($licenseGroup))
							if (ZMC::$registry->dev_only)
								ZMC::quit($zmcType);
							else
							{
								$this->debugLog("Unknown license group: '$zmcType'.");
								$licenseGroup = 'unknown';
							}

						$host = strtolower($matches[1]);
						if (ZMC::isLocalHost($host))
							$host = 'localhost';
						if(in_array($licenseGroup, array("vmware", "ndmp", "cifslic"))){
							$esxi_host = explode('" "', $matches[0] );
							$esxi_host = preg_split("/\\\\|\//",  $esxi_host[1]);
							$esxi_host = array_values(array_filter($esxi_host));
							$host = $esxi_host[0];
						}
						@$this->reply['zmc_type_histograms'][$licenseGroup][$host]++;
						@$this->reply['zmc_host_histograms'][$host][$licenseGroup]++;
						@$this->reply['zmc_typeconf_histograms'][$basename][$licenseGroup][$host]++;
					}
				}
			}
		}

		if ($find) 
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Did not find requested file {$this->data['filename_of_proposed_disklist_edit']}"));
	}

	protected function opUpdateStatsCache($devicesOnly = false)
	{
		$this->reply['licenses'] = array('zmc' => array()); 
		$oneWeek = 60 * 60 * 24 * 7;
		if (!file_exists($fn = self::ZMC_LICENSE_FILENAME))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("License not found. Please install the license"));
		if (!is_readable($fn = self::ZMC_LICENSE_FILENAME))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Can not read license file \"$fn\". " . ZMC::getFilePermHelp($fn)));
		if (false === ($lines = file($fn = self::ZMC_LICENSE_FILENAME, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Corrupt / Can not read license file: $fn;" . __LINE__));
	
		$products = array('zmc' => array('Licensed' => array(), 'Expired' => array(), 'Expiring' => array(), 'Remaining' => array(), 'Used' => array()));
		$products = array();
		$empty = true;
		foreach($lines as $line)
			if ($line[0] !== '#' && !isset($s[$line]))
			{
				$s[$line] = $empty = false;
				for($i=strlen($line) -1; $i >=0; $i--)
					if ($line[$i] !== "\n")
						$line[$i] = chr(ord($line[$i]) + 10);
				if (ZMC::$registry->dev_only)
					error_log("LICENSE: $line\n");
				$parts = explode('-', strtolower($line));
				if (count($parts) < 4)
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Corrupt / Can not read license file: $fn\nline=$line;" . __LINE__ . "\n" . print_r($parts, true)));
				$product = array_shift($parts);
				list($month, $day, $year) = $dateParts = explode('/', array_shift($parts));
				if (count($dateParts) !== 3)
					throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Corrupt / Can not read license file: $fn\nline=$line;" . __LINE__));
				$count = array_shift($parts);
				if (empty($count))
					continue;
				$expires = mktime(23, 59, 59, $month, $day, $year);
				$reply['license_expires_list'][] = "$year $month $day";
				
				
				
				if ($expires < time())
					$this->add($products, $product, 'Expired', $count, $parts);
				else
				{
					$this->add($products, $product, 'Licensed', $count, $parts);
					if ($expires < (time() + ($oneWeek * ZMC::$registry->license_expiration_warning_weeks)))
						$this->add($products, $product, 'Expiring', $count, $parts);
				}
				$this->add($products, $product, 'Expires', $count, $parts, $expires);
			}

		if ($empty)
			throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Empty license file: $fn"));

		$devicesLicensed = array();
		

		foreach(array_keys($products['zmc']) as $status)
			foreach(array_keys($products['zmc'][$status]) as $feature)
			{
				$licenseGroup = ZMC::ilookup($feature, self::$feature2group);
				$device = ZMC_Type_Devices::hasLicenseGroup($feature, self::$feature2group); 
				if ($devicesOnly && !$device)
					unset($products['zmc'][$status][$feature]);
				elseif ($feature !== $licenseGroup)
				{
					if (isset($products['zmc'][$status][$licenseGroup]))
						if ($products['zmc'][$status][$feature] == $products['zmc'][$status][$licenseGroup])
							unset($products['zmc'][$status][$feature]);
						else
							throw new ZMC_Exception_YasumiFatal($this->reply->addInternal("Corrupt license file. Equivalent license types with different counts: $feature != $licenseGroup"));
					else 
					{
						$products['zmc'][$status][$licenseGroup] = $products['zmc'][$status][$feature];
						unset($products['zmc'][$status][$feature]);
					}
				}
				if (($status === 'Licensed') && !empty($products['zmc']['Licensed'][$feature]))
					$devicesLicensed[$feature] = true; 
			}

		foreach($devicesLicensed as $deviceFeature) 
		{
			unset($products['zmc']['Expiring'][$feature]);
			unset($products['zmc']['Expired'][$feature]);
		}

		$reply['licenses'] = $products;
		if (!$devicesOnly)
			$this->disklistHistogram(); 

		$products =& $reply['licenses']['zmc'];
		$products['Used'] = $products['Remaining'] = array();
		
		if (isset($products['Licensed']))
			foreach($products['Licensed'] as $feature => $count)
			{
				$licenseGroup = ZMC::ilookup($feature, self::$feature2group);
				$products['Remaining'][$licenseGroup] = $products['Licensed'][$licenseGroup];
			}

		if (!empty($this->reply['zmc_type_histograms']))
			$this->countHistogram($products, $this->reply['zmc_type_histograms']);
		$reply['over_limit'] = $reply['group_over_limit'] = array();
		foreach($products['Remaining'] as $licenseGroup => $count)
			if ($count < 0)
			{
				if (ZMC_Type_Devices::getName($licenseGroup) && !empty($products['Licensed'][$licenseGroup]))
					continue; 

				$reply['over_limit'][$licenseGroup] = abs($count);
				if (!empty($this->reply['zmc_typeconf_histograms']))
				{
					foreach($this->reply['zmc_typeconf_histograms'] as $basename => $groups)
						if (isset($groups[$licenseGroup]))
						{
							if (!isset($reply['group_over_limit'][$basename]))
								$reply['group_over_limit'][$basename] = array();
							$reply['group_over_limit'][$basename][$licenseGroup] = min(abs($count), array_sum($groups[$licenseGroup]));
							@$reply['dles_over_limit'][$basename][$licenseGroup] = $groups[$licenseGroup];
						}
				}
			}

		if ($devicesOnly && $cache = $this->readCache())
			$this->reply->merge(array_merge($cache, $reply));
		else
			$this->reply->merge($reply);

		$reply['over_limit_errors'] = '';
		if (!empty($reply['over_limit']))
		{
			if ($this->debug && !is_array($reply['over_limit']))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal(__CLASS__ . __FUNCTION__ . '(): ' . print_r($reply, true)));

			foreach($reply['over_limit'] as $licenseGroup => $count)
				$reply['over_limit_errors'] .= "$licenseGroup exceeds license limit by $count.\n";
		}

		
		{
			$this->reply->zmc_device_histograms =& $this->getDeviceHistogram($products); 
			$this->reply->feature2group = self::$feature2group;
		}

		if (empty($reply['over_limit']))
		{
			$clone = clone $this->reply;
			$clone->unsetKeys(false); 
			if (false === file_put_contents($fn = ZMC::$registry->tmp_path . 'zstats', str_rot13(serialize($clone)), LOCK_EX))
				throw new ZMC_Exception_YasumiFatal($this->reply->addInternal(ZMC::getFilePermHelp($fn)));
		}
	}
	
	protected function add(&$products, $product, $status, $count, $features, $expiresTimestamp = null)
	{
		
		if (empty($products[$product]))
			$products[$product] = array();

		foreach($features as &$feature)
		{
			
			
			if (empty($products[$product][$status][$feature]))
				if ($expiresTimestamp === null)
					@$products[$product][$status][$feature] = $count;
				else
					@$products[$product][$status][$feature] = $expiresTimestamp;
			else
				if ($expiresTimestamp === null)
					$products[$product][$status][$feature] += $count;
				elseif ($products[$product][$status][$feature] != $expiresTimestamp)
					$products[$product][$status][$feature] = 'multiple dates';
		}
	}

	protected function opRead()
	{
		if (empty($this->data['nocache']))
			$cache = $this->readCache();

		if (empty($cache))
			$this->opUpdateStatsCache($cache);
		else
			$this->reply->merge($cache);
	}

	private function readCache()
	{
		$cacheFn = 'zstats';
		$dependencies = glob(ZMC::$registry->etc_amanda . '*/disklist.conf');
		$dependencies[] = self::ZMC_LICENSE_FILENAME;
		if (!ZMC::useCache($this->reply, $dependencies, $cacheFn, true, 3600))
			return false;

		$result = unserialize(str_rot13(file_get_contents($cacheFn)));
		$result['cached_stats'] = true;
		return $result;
	}

	private function &getDeviceHistogram(&$products)
	{
		$histogram = array();	
		foreach($this->reply['zmc_typeconf_histograms'] as $backupsetName => $usage){
			foreach(glob('/etc/amanda/' . $backupsetName . '/*.profile') as $fn){
				$lines = file($fn, FILE_SKIP_EMPTY_LINES);
				foreach($lines as &$line)
					if (false !== ($pos = strpos($line, '_key_name')))
					{
						$group = ZMC_Type_Devices::getLicenseGroup(trim(substr($line, $pos + strlen('_key_name')+1)));
						if (empty($group))
						{
							$this->debugLog("Unknown license type: '$line'.");
							$group = 'unknown';
						}
						$totalDLEs = 0;
						foreach($usage as $type => $hosts)
							$totalDLEs += array_sum($hosts);
						$histogram[$group][$backupsetName] = $totalDLEs;
						continue 2;
					}
			}
		}

		return $histogram;
	}

	private function countHistogram(&$products, $histogram)
	{
		foreach($histogram as $feature => $hostCounts)
		{
			$licenseGroup = ZMC::ilookup($feature, self::$feature2group);
			
			if (isset($products['Used'][$licenseGroup]))
				$products['Used'][$licenseGroup] += count($hostCounts); 
			else
				$products['Used'][$licenseGroup] = count($hostCounts); 

			if (isset($products['Licensed'][$licenseGroup]))
				$products['Remaining'][$licenseGroup] = $products['Licensed'][$licenseGroup] - $products['Used'][$licenseGroup];
			else
				$products['Remaining'][$licenseGroup] = 0 - $products['Used'][$licenseGroup];
		}
	}
}
