<?













class ZMC_Admin_Advanced
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', 'ZMC - Advanced Administration / Command Line Interface', 'advanced');

		if (isset($_GET['mysql_stat']))
			$pm->commandResult = "==>ZMC DB Status<==\n" . mysql_stat() . "\n\n==>Server Uptime<==\n" . exec('uptime');

		if (!empty($_REQUEST['form']))
		{
			$action = 'op' . ucFirst($_REQUEST['form']);
		   	if (method_exists('ZMC_Admin_Advanced', $action))
				call_user_func(array('ZMC_Admin_Advanced', $action), $pm);
		}
		$pm->addWarning('EXPERTS ONLY! Directly edit configuration files and run command-line AE tools.');
		return 'AdminAdvanced';
	}
	
	public static function opAdminTasks($pm)
	{
		if (!ZMC_User::hasRole('Administrator'))
			return $pm->addError('Only ZMC administrators may perform this action.');

		if (isset($_POST['updateAmReports']))
		{
			ZMC_BackupSet::updateAmReports($pm);
			$pm->addMessage('Updating reports. If the unprocessed logs are large, this may take a while, but you may continue to use ZMC.');
		}

		if (empty(ZMC::$registry->admin_task_commands)) 
			$_REQUEST['command'] = null;

		if (empty($_REQUEST['command']))
			return;

		$command = strtok($_REQUEST['command'] = trim($_REQUEST['command']), " \t\n");
		if ($command[0] === '/')
		{
			if (!file_exists($command))
				return $pm->commandResult = 'File does not exist.';
			elseif(!is_readable($command))
				return $pm->commandResult = 'Could not read the file.';
			elseif (!is_executable($command))
			{
				if (!empty($_POST['action']) && $_POST['action'] === 'Save Changes')
				{
					if (false !== file_put_contents($_REQUEST['command'], str_replace("\r\n", "\n", $_POST['commandResult']), LOCK_EX))
					{
						$pm->addMessage("Wrote file '$_REQUEST[command]'.");
						ZMC::auditLog(__CLASS__ . " - ZMC user manually edited file '$_REQUEST[command]'.");
					}
					else
					{
						$pm->addError("Failed writing to file '$_REQUEST[command]'.");
						ZMC::auditLog(__CLASS__ . " - ZMC user manually tried to edit file '$_REQUEST[command]', but ZMC was not able to write to the file (file permission?).");
					}
				}
				$pm->commandResult = file_get_contents($_REQUEST['command']);
				if (false === $pm->commandResult)
				{
					ZMC::auditLog("ZMC user tried to view the file '$_REQUEST[command]', but ZMC was not able to read the file (file permissions?)");
					$pm->commandResult = 'Could not read the file.';
				}
				else
					ZMC::auditLog("ZMC user viewed the file '$_REQUEST[command]'");
				return;
			}
		}
		$cmd = explode(' ', $_REQUEST['command']);
		$cmd = basename($cmd[0]);
		if (ZMC::$registry->platform === 'windows')
			$pm->commandResult = 'On Windows, only reading and editing files is supported.  Please enter only a full path filename, such as "C:/file.txt";';
		elseif ($_REQUEST['command']{0} === '/' || in_array($cmd, ZMC::$registry->admin_task_commands))
		{
			






			try
			{
				putenv('PATH=/usr/sbin' . PATH_SEPARATOR . getenv('PATH'));
				$command = ZMC_ProcOpen::procOpen($cmd, 'TERM=vt100 HOME=/var/lib/amanda COLUMNS=180 ' . ZMC::$registry->svn['zmc_bash'] . ' -c ' . escapeshellarg($_REQUEST['command']), array(),
					$stdout, $stderr, "$cmd command failed unexpectedly");
				$pm->commandResult = '';
				foreach(explode("\n", "$stderr\n$stdout") as $line)
					$pm->commandResult .= rtrim($line) . "\n";
			}
			catch (ZMC_Exception_ProcOpen $e)
			{
				$pm->commandResult = "$e";
			}
		}
		else
			$pm->commandResult = wordwrap('Only the following *non-interactive* commands are permitted: ' . implode(', ', ZMC::$registry->admin_task_commands));
	}
}
