<?
















class ZMC_Yasumi_Check extends ZMC_Yasumi
{
	public function opServerInstallation()
	{
		$this->noticeLog("Verifying ZMC dumptypes related files.");
		$box = new ZMC_Registry_MessageBox(array('overrides' => array()));

		if (!$this->dumpTypesInstalled($this->reply))
			$this->addMessageToInstallationPage($box, "Verification of ZMC dumptypes related files", 'error');
		else
			$this->addMessageToInstallationPage($box, "Verification of ZMC dumptypes related files", 'success');

		$this->updateStatsCache('', 'read');
		
		if (ZMC::$registry->qa_mode || ZMC::$registry->dev_only || empty(ZMC::$registry[__CLASS__]) || (time() - ZMC::$registry[__CLASS__]) > 90)
		{
		
			$this->doChecks($box);
			ZMC_Startup::initZmcOverrides($box); 
			ZMC::$registry->setOverrides(array(__CLASS__ . 'Result' => $box));
		}
		$this->reply->merge(ZMC::$registry[__CLASS__ . 'Result']);
	}

	private function doChecks(ZMC_Registry_MessageBox $box)
	{
		ZMC::$registry->setOverrides(array(__CLASS__ => time()));
		if(!$this->mkdirIfNotExists(ZMC::$registry->etc_amanda))
			$this->addMessageToInstallationPage($box, "Verification of Amanda directories", 'success');
		else
			$this->addMessageToInstallationPage($box, "Verification of Amanda directories", 'error');

		
		$amandaUser = ZMC::$registry->cnf['amanda_user'];
		$this->noticeLog("Checking disk space.");
		ZMC::checkDiskSpace($box); 
		if($_SESSION['disk_space_check_errors'])
			$this->addMessageToInstallationPage($box, "Disk space verification", 'error');
		else
			$this->addMessageToInstallationPage($box, "Disk space verification", 'success');
		$this->noticeLog("Performing check on VMWare VCLI.");
		$this->checkVmwareVcli($box);
		$this->noticeLog("Checking xinetd configuration.");
		if($this->checkXinetdConfiguration($box)){
                	if(ZMC::$registry->platform != 'solaris')
                        	$this->addMessageToInstallationPage($box, "Verification of xinetd configuration done successfully", 'success');
                }else{
                	if(ZMC::$registry->platform != 'solaris')
                		$this->addMessageToInstallationPage($box, "Verification of xinetd configuration failed", 'error');
                }

		$this->noticeLog("Performing check on shell.");
		if(ZMC::isShellOk($box))
			$this->addMessageToInstallationPage($box, "Verification of shell configuration done successfully", 'success');
		$this->noticeLog("Performing Ulimit check.");
		$this->ulimitCheck($box);
		$this->noticeLog("Performing check on temperory files.");
		if($this->checkTmp($box))
			$this->addMessageToInstallationPage($box, "Verification of /tmp directory done successfully", 'success');

		if (!empty(ZMC::$registry->internet_connectivity) && ZMC::$registry->verify_installed_files){
			$this->noticeLog("Performing check on PHP packages/moduels.");
			$this->checkPhp($box);
		}
		$this->noticeLog("Verifying Amanda libraries.");
		if(!ZMC::checkAmandaLibs($box))
			$this->addMessageToInstallationPage($box, "Verification of Amanda configuration done successfully", 'success');
		$this->noticeLog("Verifying AEE license file.");
		
		
		if (!file_exists($fn = '/etc/zmanda/zmanda_license'))
			$box->addError("Can not find the AEE license file: $fn");
		elseif (!is_readable($fn))
			$box->addError("Can not read the AEE license file: $fn " . ZMC::getFilePermHelp($fn));


		if (false === ($getpwnam = posix_getpwnam($amandaUser)))
			$box->addError("The Amanda user account '$amandaUser' does not exist on the system.");
		else
		{
			$this->noticeLog("Performing check on Amanda user account and home directory.");
			$this->addMessageToInstallationPage($box, "Performing check on Amanda user account and home directory", 'success');
		}

		if (!file_exists($getpwnam['dir'] . '/.gnupg/pubring.gpg'))
			ZMC::execv('bash', 'echo test | /usr/sbin/amcryptsimple > /dev/null 2>&1');
		$getgrgid = posix_getgrgid($getpwnam['gid']);
		$getgrnam = posix_getgrnam($amandaGroup = ZMC::$registry->cnf['amanda_group']);
		if ($getgrgid['name'] !== $amandaGroup)
			if (false === array_search($amandaUser, $getgrnam['members']))
				$box->addError("The Amanda user account '$amandaUser' does not belong to the group '$amandaGroup'.");
	
		if ($getpwnam['dir'] !== ($amandaHome = rtrim(ZMC::$registry->cnf['amanda_home_path'], '/')))
			$box->addError("$amandaUser's home directory is $getpwnam[dir], but should be set to $amandaHome.");
	
		if (!file_exists($amandaHome))
			$box->addError("$amandaUser's home directory $amandaHome does not exist: " . posix_strerror(posix_get_last_error()));
		elseif ($result = ZMC::is_readwrite($amandaHome))
			$box->addError("$amandaUser's home directory $amandaHome is not readable and/or writable: " . posix_strerror(posix_get_last_error()) . "\n$result");
		else
		{
			$stat = stat($amandaHome);
			if ($stat['uid'] != $getpwnam['uid'])
				$box->addError("$amandaUser's home directory $amandaHome is not owned by user $amandaUser.");
			
			
		}
	
		$amandaReleaseVersion = ZMC::$registry->amanda_release;
		$okAmandaVersions = array_flip(file(ZMC::$registry->etc_zmc_aee . 'amanda-release', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES));
		if ($okAmandaVersions === false || $amandaReleaseVersion === false){
			$this->noticeLog('Unable to verify installation of Amanda server component');
			$box->addError('Unable to verify installation of Amanda server component');
		}
		elseif (!isset($okAmandaVersions[$amandaReleaseVersion])){
			$msg_found_expected="Amanda server component version is not correct.
				Found: $amandaReleaseVersion
				Expected: " . join(', ', array_keys($okAmandaVersions)) . "\n";
				if(ZMC::$registry->debug)
					$msg_found_expected .= ZMC::$registry->support_email_html;
			$this->noticeLog($msg_found_expected);
			$box->addWarning($msg_found_expected);
		}else{
			$this->addMessageToInstallationPage($box, "Verified Amanda server component version", 'success');
		}

		if (ZMC::$registry->check_localhost_tar_version) 
			try
			{
				$this->noticeLog("Performing check on GNU tar.");
				ZMC_ProcOpen::procOpen('gtar', $tarPath = ZMC::$registry->getAmandaConstant('GNUTAR'), array('--version'), $stdout, $stderr);
				if (false === strpos($stdout, 'GNU tar'))
					$box->addError("Amanda Enterprise Edition requires GNU tar version 1.15.1 or later (1.20 or later strongly recommended).  See " . ZMC::$registry->wiki . "Pre-Installation#Linux . The version currently installed at $tarPath does not appear to be a GNU version of tar:\n$stdout");
				else
				{
					$version = explode('.', $stdout);
					$version[0] = trim(substr($version[0], -2));
					if (!$this->versionCheck($version, ZMC::$registry->gnutar_required))
						$box->addError("Amanda Enterprise Edition requires GNU tar version 1.15.1 or later (1.20 or later recommended).  See " . ZMC::$registry->wiki . "Pre-Installation#Linux . The version currently installed at $tarPath:\n$stdout");
					elseif (!$this->versionCheck($version, ZMC::$registry->gnutar_recommended))
						$box->addEscapedWarning("Amanda Enterprise Edition works best with GNU tar version 1.20 or later.  See <a target='_blank' href='" . ZMC::$registry->wiki . "Pre-Installation#Linux'>installation instructions</a>. The version currently installed at $tarPath: $stdout");
				}
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->noticeLog("$e");
				$box->addError("$e");
			}

		$contents = file($fn = ZMC::$registry->cnf['amanda_hosts_file'], FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );
		if (false === $contents){
			$this->noticeLog("Unbale to read '$fn'");
			$box->addError("Unable to read '$fn'");
		}
		else
		{
			foreach($contents as $line)
				if (!strncmp($line, 'localhost', 9))
				{
					$parts = array();
					$host = strtok($line, " \t");
					$user = strtok(" \t");
					while(false !== ($tok = strtok(" \t")))
						$found[$host][$tok] = $user;
				}
			foreach($found as $hostname => $host)
			{
				if (	(!isset($host['amindexd']) || $host['amindexd'] !== 'root')
					||	(!isset($host['amidxtaped']) || $host['amidxtaped'] !== 'root')){
					$this->noticeLog("In file $fn, $hostname is not configured correctly. Please add/modify a line to match:\n$hostname root amindexd amidxtaped");
					$box->addError("In file $fn, $hostname is not configured correctly. Please add/modify a line to match:\n$hostname root amindexd amidxtaped");
					}

				if (!isset($host['amdump']) || $host['amdump'] !== $amandaUser){
					$this->noticeLog("In file $fn, $hostname is not configured correctly. Please add/modify a line to match:\n$hostname $amandaUser amdump");
					$box->addError("In file $fn, $hostname is not configured correctly. Please add/modify a line to match:\n$hostname $amandaUser amdump");
				}
			}
		}

		$warning = false;
		$contents = file($fn = '/etc/services', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES );
		if (false === $contents){
			$this->noticeLog("Unable to read '$fn'");
			$box->addError("Unable to read '$fn'");
		}
		else
		{
			foreach($contents as $line)
			{
				$tokenize = $line;
				if (	('amanda' === strtok($tokenize, " \t"))
					&&	(false !== ($port = strtok(" \t"))))
				{
					$parts = explode('/', trim(strtok($port, '#')));
					if (	(count($parts) === 2)
						&&	(ctype_digit($parts[0])))
						if ($parts[1] === 'tcp')
							$contents = true;
						elseif ($parts[1] === 'udp')
							$warningUdp = $line;
						else
							$warningUnknownProtocol = $line;
				}
			}
		}
		$err = '/etc/services does not have an entry for amanda, such as "amanda 10080/tcp".';
		$this->noticeLog("Performing check on various servieces, such as /etc/service, xinetd, amservice, etc...");
		if ($contents !== true)
		{
			if (!empty($warningUdp)){
				$this->noticeLog("/etc/services has an entry for 'amanda', but the entry '$warningUdp' is not correct when using bsdtcp authentication, and does not match 'amanda #####/tcp'.");
				$box->addWarning("/etc/services has an entry for 'amanda', but the entry '$warningUdp' is not correct when using bsdtcp authentication, and does not match 'amanda #####/tcp'.");
			}
			elseif (!empty($warningUnknownProtocol)){
				$this->noticeLog("/etc/services has an entry for 'amanda', but the entry '$warningUnknownProtocol' is not correct when using bsdtcp authentication, and does not match 'amanda #####/tcp'.");
				$box->addError("/etc/services has an entry for 'amanda', but the entry '$warningUnknownProtocol' is not correct when using bsdtcp authentication, and does not match 'amanda #####/tcp'.");
			}
			else{
				$this->noticeLog("$err (#100)");
				$box->addError("$err (#100)");
			}
		}
		elseif (false === ($port = getservbyname("amanda", "tcp"))) 
		{
			$this->noticeLog("$err (#101)");
			$box->addError("$err (#101)");
		}
		elseif (empty($_SESSION['server_install_check']))
		{
			$_SESSION['server_install_check'] = true;
			$amserviceErr = 'xinetd/inetd (or amandad) does not appear to respond successfully to "amanda" service requests on this AEE server (localhost).';
			try
			{
				$cmd = ZMC_ProcOpen::procOpen('amservice', '/usr/sbin/amservice', array('127.0.0.1', 'bsdtcp', 'noop'), $stdout, $stderr);
				if (false === strpos($stdout, 'OPTIONS')){
					$this->noticeLog($amserviceErr);
					$box->addWarning($amserviceErr);
				}

				$cmd = ZMC_ProcOpen::procOpen('amservice', '/usr/sbin/amservice', array('localhost', 'bsdtcp', 'noop'), $stdout, $stderr);
				if (false === strpos($stdout, 'OPTIONS')){
					$this->noticeLog("amservice 127.0.0.1 check OK, but the same check for \"localhost\" failed. Perhaps \"localhost\" must be the first hostname after \"127.0.0.1\" in /etc/hosts?\r\n$amserviceErr");
					$box->addWarning("amservice 127.0.0.1 check OK, but the same check for \"localhost\" failed. Perhaps \"localhost\" must be the first hostname after \"127.0.0.1\" in /etc/hosts?\r\n$amserviceErr");
				}
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$this->noticeLog("$amserviceErr\n$e");
				$box->addWarning("$amserviceErr\n$e");
			}
		}
		elseif ($port)
		{
			if ($fp = fsockopen('127.0.0.1', $port, $errno, $errstr, 5))
				fclose($fp);
			else{
				$this->noticeLog("self-test failed: could not open \"amanda\" port $port on 127.0.0.1 (check xinetd/inetd installation)");
				$box->addWarning("self-test failed: could not open \"amanda\" port $port on 127.0.0.1 (check xinetd/inetd installation)");
			}
		}
	}

	protected function versionCheck($haveVersion, $wantVersion)
	{
		foreach($wantVersion as $wantDigit)
		{
			$haveDigit = array_shift($haveVersion);
			if ($haveDigit > $wantDigit)
				return true;

			if ($haveDigit < $wantDigit)
				return false;
		}

		return true; 
	}

	
	
	protected function checkXinetdConfiguration(ZMC_Registry_MessageBox $box)
	{
		if (ZMC::$registry->platform === 'solaris')
		{
			
			$results = '';
			$results = shell_exec('svcs svc:/network/zmrecover/tcp:default');
                        if (!empty($results)){
                        	if(preg_match('/(offline)|(disabled)/', $results)){
                                	$box->addError("Verification of 'zmrecover' service failed.\n");
                                	return false;
                                }   
                                if(strpos($results, 'online')){
                                	$box->addMessage("Verification of 'zmrecover' service is done successfully..\n");
                                }   
                        }   
			
			$results = '';
                        $results = shell_exec('svcs svc:/network/amanda/tcp:default');
                        if (!empty($results)){
                                if(preg_match('/(offline)|(disabled)/', $results)){
                                        $box->addError("Verification of 'amandad' service failed.\n");
                                        return false;
                                }   
                                if(strpos($results, 'online')){
                                        $box->addMessage("Verification of 'amandad' service is done successfully..\n");
                                }   
                        }   

			return true;
		}
		$this->noticeLog("Performing xinetd check on server.");
	
		$fn = ZMC::$registry->cnf['xinetd_dir'] . ZMC::$registry->cnf['xinetd_server_file'];
		if (!file_exists($fn)){
			$box->addError("xinetd configuration file '$fn' does not exist.");
			return false;
		}
		elseif(!is_readable($fn) || (false === ($lines = file($fn, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES )))){
			$box->addError("xinetd configuration file '$fn' is not readable.", posix_strerror(posix_get_last_error()));
			return false;
		}
	
		$expect = <<<EOD
service amanda
{
        disable         = no
        flags           = IPv4
        socket_type     = stream
        protocol        = tcp
        wait            = no
        user            = amandabackup
        group           = disk
        groups          = yes
        server          = /usr/lib/amanda/amandad
        server_args     = -auth=bsdtcp amdump amindexd amidxtaped
}
EOD;
		$service = false;
		$keys = array();
		foreach($lines as $line)
		{
			$line = trim($line);
			if ($line[0] === '#')
				continue;
	
			if (!$service)
			{
				if (!strncmp($line, 'service amanda', 14))
					$service = true;
				continue;
			}
	
			if (!strpos($line, '='))
				continue;
	
			$parts = explode('=', $line);
			$keys[trim($parts[0])] = trim($parts[1]) . (empty($parts[2]) ? '' : '=' . trim($parts[2]));
		}
	
		$problems = array();
		if (empty($keys['disable']) || $keys['disable'] !== 'no')
			$problems[] = "'disable' is not set to 'no', which disables the amandad service";

		if (empty($keys['user']) || $keys['user'] !== ($user = ZMC::$registry->cnf['amanda_user']))
			$problems[] = "'user' is not set to $user, which causes amandad service to run with incompatible user privileges";

		if (empty($keys['server']) || $keys['server'] !== ($server = ZMC::$registry->cnf['amanda_lib_path'] . 'amandad'))
			$problems[] = "'server' must be set to: $server";

		if (empty($keys['flags']))
			$problems[] = "'flags' should not be empty (e.g. needs at least 'IPv4' or 'IPv6')";

		if (empty($keys['socket_type']) || $keys['socket_type'] !== 'stream')
			$problems[] = "'socket_type' should be set to 'stream'";

		if (empty($keys['protocol']) || $keys['protocol'] !== 'tcp')
			$problems[] = "'protocol' should be set to 'tcp'";

		if (empty($keys['wait']) || $keys['wait'] !== 'no')
			$problems[] = "'wait' should be set to 'no'";

		if (empty($keys['group']) || $keys['group'] !== ($group = ZMC::$registry->cnf['amanda_group']))
			$problems[] = "'group' should be set to '$group'";

		if (empty($keys['groups']) || $keys['groups'] !== 'yes')
			$problems[] = "'groups' should be set to 'yes'";

		if (empty($keys['server_args']))
			$problems[] = "'server_args' should be set to '-auth=bsdtcp amdump amindexd amidxtaped'";
		else
		{
			$sa = str_replace("\t", ' ', $keys['server_args']);
			$parts = explode(' ', $sa);
			$args = array();
			foreach($parts as &$part)
			{
				$part = trim($part);
				if (!empty($part))
					$args[$part] = true;
				if (!strncmp($part, '-auth=', 6))
					$authOk = true;
			}
			if (empty($args['amdump']))
				$problems[] = "'server_args' is missing 'amdump'";
			if (empty($args['amindexd']))
				$problems[] = "'server_args' is missing 'amindexd'";
			if (empty($args['amidxtaped']))
				$problems[] = "'server_args' is missing 'amidxtaped'";
			if (empty($authOk))
				$problems[] = "'server_args' is missing '-auth=' (e.g. '-auth=bsdtcp')";
		}

		if (!empty($problems)){
			$box->addWarning("$fn contents do not match expected values:\n" . implode("\n", $problems),
				"@<pre>@==>Live copy of $fn<==\n" . implode("\n", $lines) . "\n\n==>Expected $fn<==\n$expect@</pre>");
			return false;
		}
		return true;
	}

	public static function dumpTypesInstalled($pm) 
	{
		
		$ok_332 = array(
			'0bc2809e95f0b5f34a5dcd9a87cf27e2ae0f5016' => true,
			'45a2d25140334156e3a0e727430ff75600485220' => true,
			'ffbd8d80f5fe7b99477a6a8466bdb3596928370e' => true,
		);
		$ok_333 = array(
				'ffbd8d80f5fe7b99477a6a8466bdb3596928370e' => true,
				'a8e32334c8bd8cca760d1c3fde25e2996ad2e983' => true,
				'e017b2bcddc738813fa80710551f6dfcb7cc169b' => true,
		);
		$ok_334 = array(
				'19573ec23edd21e25e450ec7172a92f967967d82' => true,
				'd9448fbd27af5e3258c94f63ddc8390b6808221e' => true,
				'ffbd8d80f5fe7b99477a6a8466bdb3596928370e' => true,
		);
		$ok_335 = array(
				
				
				'2f72c0080c1e79590221a42fd99ede8277921cb4' => true, 
				
				'9ed12355848b42d7768cd46a4ea800928d77b87c' => true,
				'057f59bf1615a7b66da889b3b20e7a4cd32810cd' => true,
				'ffbd8d80f5fe7b99477a6a8466bdb3596928370e' => true,
		);
		$ok_336 = array(
				'a3dc587e02466bc2cd915722387f0a3d54b56af0' => true,
				'b9294a31d19fdf69f1702c6bd8aafe784586f470' => true,
		);
		$ok = array(
				
				
				'83c374d986865bce134812bb2322d3460d0e9dc6' => true, 
				
				'2c5a9acb2b42e67d7f9a114fb74af5d997a7ffe7' => true,
				'b9294a31d19fdf69f1702c6bd8aafe784586f470' => true,
				'ffbd8d80f5fe7b99477a6a8466bdb3596928370e' => true,
		);
		$codes = $file_not_exists = array();
		$msg = "To complete the AEE software upgrade, Please <a target='_blank' href='" . ZMC::$registry->lore  . "544'>click here</a> to resolve this issue. (code #";
		foreach(array('ZDD' => 'zmc_device_dumptypes', 'ZDT' => 'zmc_dumptypes', 'ZDU' => 'zmc_user_dumptypes') as $code => $fn)
		{
			$filepath = ZMC::$registry->etc_zmanda . "/zmc_aee/$fn";
			$newFile = "$filepath.new";
			
			if (!file_exists($newFile) && file_exists($filepath))
				continue;
		
			if (!file_exists($newFile) && !file_exists($filepath)){
				$defaultFile = ZMC::$registry->etc_zmanda . "/zmc_aee/defaults/$fn.new";
				if (!copy($defaultFile, $filepath)) {
					$file_not_exists[] = $fn;
					continue;
				}
			}
			
			if (file_exists($newFile) && file_exists($filepath))
			{
				$sha_dumptypes = sha1($result = preg_replace(array('/^\s*#.*/m', '/^\s+/m', '/comment.*/', '/\n+/'), array('', '', '', "\n"), file_get_contents($filepath)));
				$sha_dumptypes_new = sha1($result = preg_replace(array('/^\s*#.*/m', '/^\s+/m', '/comment.*/', '/\n+/'), array('', '', '', "\n"), file_get_contents($newFile)));
				

				if(!isset($ok[$sha_dumptypes])){
					if(!isset($ok_332[$sha_dumptypes]) && !isset($ok_333[$sha_dumptypes]) && !isset($ok_334[$sha_dumptypes]) && !isset($ok_335[$sha_dumptypes])){
						$cmd = "diff -ruN $filepath $newFile";
						$results = shell_exec($cmd);
						$codes[] = $code;
						if(!empty($results)){
							$details .= "<b>$cmd</b>\n" . $results."<br />";
						}
						continue;
					}
					else{
						rename($newFile, $filepath);
						continue;
					}
				}elseif($sha_dumptypes == $sha_dumptypes_new){
					rename($newFile, $filepath);
					continue;
				}

			}

			if (file_exists($filepath))
			{
				$sha = sha1($result = preg_replace(array('/^\s*#.*/m', '/^\s+/m', '/comment.*/', '/\n+/'), array('', '', '', "\n"), file_get_contents($filepath)));
				if (!isset($ok[$sha]))
				{
					if(!isset($ok_332[$sha]) && !isset($ok_333[$sha]) && !isset($ok_334[$sha]) && !isset($ok_335[$sha])){
						$codes[] = $code;
						$defaultFile = ZMC::$registry->etc_zmanda . "/zmc_aee/defaults/$fn.new";
						$cmd = "diff -ruN $defaultFile $filepath";
						$results = shell_exec($cmd);
						if(!empty($results)){
							$details .= "$cmd\n" . $results;
						}	
						continue;
					}
				}
			}
			if(file_exists($newFile) && !file_exists($filepath)){
				if (rename($newFile, $filepath))
					continue;
			}

		}
		if(!empty($file_not_exists)){
			$plural = (count($file_not_exists) > 1)? "s": '';
			$pm->addEscapedError("Following file".$plural." (".implode(', ', $file_not_exists).") does not exists under '".ZMC::$registry->etc_zmanda . "zmc_aee/' directory.");
			return false;
		}
		
		if (empty($codes))
			return true;
		$pm->addEscapedError($msg . implode(', ', $codes) . ')', $details); 
		return false;
	}

	protected function checkVmwareVcli(ZMC_Registry_MessageBox $box)
	{
		$box->overrides['vcli'] = false;
		if (is_readable($vcli = '/etc/vmware-vcli/config'))
		{
			$config = file_get_contents($vcli);
			if (false !== ($pos = strpos("\n$config", "\nlibdir")))
			{
				$libdir = substr($config, $pos+9, strpos($config, "\n", $pos+7));
				if (is_dir($vcli = trim($libdir, "\t\n\"")))
				{
					try
					{
						
						ZMC_ProcOpen::procOpen('esxcli', $cmd = "$vcli/bin/esxcli/esxcli", array('--version'), $stdout, $stderr);
						$ver = trim(substr($stdout, strrpos($stdout, ':') +2));
						$box->overrides['vcli_ver'] = $ver;
						if (!empty(ZMC::$registry['vcli_ok'][$ver])){
							$box->overrides['vcli'] = true;
							$this->addMessageToInstallationPage($box, "Verification of VMware VCLI configuration done successfully", 'success');
						}
						else
							$box->addWarning("VMWare VCLI version $ver found (not supported by this version).");


						
						
						
						ZMC_ProcOpen::procOpen('perldoc', $cmd = "/usr/bin/perldoc", array('-l', 'Net::HTTP'), $stdout, $stderr);

						
						
						ZMC_ProcOpen::procOpen('perldoc', $cmd = "/usr/bin/perldoc", array( '-l', 'LWP'), $stdout,$stderr);

					}
					catch (ZMC_Exception_ProcOpen $e)
					{
						$this->addMessageToInstallationPage($box, "VMware VCLI verification failed", 'error');
						$box->addError("$e");
					}
				}
			}
		}else{
			 $this->addMessageToInstallationPage($box, "Skipping verification of VMware VCLI configuration", 'success');
		}

	}

	protected function checkMd5(ZMC_Registry_MessageBox $box)
	{
		if (ZMC::$registry->platform === 'solaris')
			
			
			return false;

		$md5 = glob(ZMC::$registry->etc_zmanda . '/zmc_aee-' . ZMC::$registry->svn->zmc_build_version . '*.manifest.md5');
		if (empty($md5))
		{
			$box->addError("ZMC installation problem. No ZMC package manifest found!");
			return true;
		}
		if (count($md5) > 1)
		{
			if(ZMC::$registry->svn->zmc_patches){
				$found = $not_found = 0;
				foreach(ZMC::$registry->svn->zmc_patches as $applied_patch){
					if($matches = preg_grep("/zmc_aee-(.*)?($applied_patch)+(.)?manifest.md5/", $md5)){
						$found++;
					}
					else{
						$box->addError("Could not find ZMC patch ( ". rtrim($applied_patch, '.')." ) related manifest file in following list: \n" . implode(", \n", $md5));
						$not_found++;
					}
				}
				
				
				if( $not_found > 0 ){
					
					return true;
				}
			}
		}

		try
		{
			$md5_file = ZMC::$registry->etc_zmanda . '/zmc_aee-' . ZMC::$registry->svn->zmc_build_version .".".ZMC::$registry->svn->zmc_svn_revision. '.manifest.md5';
			if(in_array($md5_file, $md5)){
				$test = ZMC_ProcOpen::procOpen('md5sum', 'md5sum', array('-c', $md5_file), $stdout, $stderr);
            }
			if(ZMC::$registry->svn->zmc_patch){
				$md5_patch_file = ZMC::$registry->etc_zmanda . '/zmc_aee-' . ZMC::$registry->svn->zmc_build_version .'.'.ZMC::$registry->svn->zmc_patch .'manifest.md5';
				if(in_array($md5_patch_file, $md5)){
					$test1 = ZMC_ProcOpen::procOpen('md5sum', 'md5sum', array('-c', $md5_patch_file), $stdout, $stderr);
					return false;
				}
			}
			return false;

		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			if (empty($stderr))
				$box->addError("$e");
			else
			{
				$filenames = array();
				$files = explode("\n", $stdout);
				for($i=count($files), $max = 10; ($i >= 0) && $max; $i--)
					if (!empty($files[$i]) && (substr($files[$i], -2) !== 'OK') && $max--)
					{
						$stderr .= "\n* " . ($fn = substr($files[$i], 0, strpos($files[$i], ':')));
						$filenames[] = $fn;
					}
				$box->addWarning($stderr);
				if (!empty($filenames))
					if (ZMC::$registry->qa_mode || ZMC::$registry->dev_only)
						ZMC::execv('bash', "tar czf /tmp/zmc-md5sum-changed-files.tar.gz " . implode(' ', $filenames));
			}
			return true;
		}
	}

	protected function ulimitCheck(ZMC_Registry_MessageBox $box) 
	{
		try
		{
			$cmd = ZMC_ProcOpen::procOpen('ulimit', 'ulimit', array('-a'), $stdout, $stderr);
		}
		catch (ZMC_Exception_ProcOpen $e)
		{
			$box->addWarning("$cmd failed: $e");
			return;
		}
		if (ZMC::$registry->platform === 'solaris')
		{
			








			
			return;
		}
		

















		
	}

	protected function checkTmp(ZMC_Registry_MessageBox $box) 
	{
		$note = "Usually /tmp requires:\nchown root:root /tmp; chmod 1777 /tmp";
		if ($result = ZMC::is_readwrite($path = '/tmp')){
			$box->addError("ZMC does not have read and write access to /tmp.\n$usually");
			return false;
		}
		$stats = stat($path = '/tmp');
		$mode = base_convert($stats['mode'], 10, 8);
		$this->dump(array('Mode' => $mode, 'stats' => $stats));
		if ($mode !== '41777')
		{
			$this->noticeLog("Warning: '$path' has mode $mode");
			if (ZMC::$registry->security_warnings)
			{
				if ($this->debug)
					$this->reply->addDetail(print_r($stats, true));

				$box->addWarning("/tmp does not have 41777 permissions.  ls -l /tmp => $mode /tmp\n$usually");
				return false;
			}
		}
		return true;
	}

	protected function checkCurl(ZMC_Registry_MessageBox $box) 
	{
		if (empty(ZMC::$registry->internet_connectivity)) return;
		try
		{
			
			$url = 'https://network.wocloud.cn';
			ZMC_ProcOpen::procOpen('curl', '/opt/zmanda/amanda/common/bin/curl', array('--progress-bar', '--max-time', '30', '--tlsv1', $url), $stdout, $stderr);
			$this->noticeLog("Connectivity to $url verified.");
			
			
			
			$this->addMessageToInstallationPage($box, "Verified curl connectivity with $url", 'success');
			if (empty($this->lstats['licenses']['zmc']['Licensed']['s3'])) return;
			$profiles = $this->command(array('pathInfo' => "/Device-Profile/read_profiles"));
			foreach($profiles['device_profile_list'] as $profile)
				if ($profile['_key_name'] === 's3_cloud')
					$checkS3 = true;
			if (empty($checkS3)) return;
			$url = 'https://s3.amazonaws.com';
			ZMC_ProcOpen::procOpen('curl', '/opt/zmanda/amanda/common/bin/curl', array('--progress-bar', '--max-time', '30', '--tlsv1', $url), $stdout, $stderr);
			$this->noticeLog("Connectivity to $url verified.");
			$this->addMessageToInstallationPage($box, "Verified curl connectivity with $url", 'success');
		}
		catch (Exception $e)
		{
			$box->addError("ZMC was not able to verify network connectivity (ability to interoperate with) $url: $e");
		}
	}

	public function	addMessageToInstallationPage(ZMC_Registry_MessageBox $box, $message, $type){
		$type = strtolower($type);
		if(isset($this->data['check_server']) && $this->data['check_server'] == "show_check_installation_page"){
			if($type == "success")
				$box->addMessage($message);
			if($type == "warning")
				$box->addWarning($message);
			if($type == "error")
				$box->addError($message);
		}

	}

	protected function checkPhp(ZMC_Registry_MessageBox $box)
	{
		require 's3/sdk.class.php';
		$expect = explode(' ', 'simplexml/0.1 json/1.2.1 pcre/8.11 spl/0.2 curl/7.30.0 apc/3.1.4 pdo/1.0.4dev pdo_sqlite/1.0.1 sqlite/2.0-dev sqlite3/0.7-dev zlib/1.1 open_basedir/off safe_mode/off zend.enable_gc/on'); 
		$extensions = explode(' ', trim(__aws_sdk_ua_callback()));
		foreach(array_keys($extensions) as $key)
			if (!strncmp('memory_limit', $extensions[$key], 12) || !strncmp('date.timezone', $extensions[$key], 12))
				unset($extensions[$key]);

		$missing = array_diff($expect, $extensions);
		$extra = array_diff($extensions, $expect);
		sort($missing);
		sort($extra);

		if (count($missing) || count($extra))
		{
			$box->addWarnError("ZMC PHP extensions do not match expectations.");
			if (count($extra)) $box->addWarnError("Found: " . implode(', ', $extra));
			if (count($missing)) $box->addWarnError("Expected: " . implode(', ', $missing));
		}
		else{
			$this->addMessageToInstallationPage($box, "Verification of PHP configuration done successfully", 'success');
		}
	}

	






















}
