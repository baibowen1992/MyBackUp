<?
//zhoulin-restore-what 201409221624












class ZMC_Yasumi_Job_Amgetindex extends ZMC_Yasumi_Job
{

	public function __construct(ZMC_Yasumi $yasumi = null, $args = array(), ZMC_Registry_MessageBox $reply = null)
	{
		parent::__construct($yasumi, $args, $reply);
		$this->jobType = '获取索引';
	}

	protected function init()
	{
		parent::init();
		$this->sqlFile = '/opt/zmanda/amanda/mysql/tmp/' . $this->getAmandaConfName() . '.idx.sql';
		$this->mergedFile = '/opt/zmanda/amanda/mysql/tmp/' . $this->getAmandaConfName() . '.merged.files';
		$this->sortedFile = '/opt/zmanda/amanda/mysql/tmp/' . $this->getAmandaConfName() . '.sorted.files';
		$this->sqlFilesOnlyFn = $this->sqlFile . '.files';
	}

	
	protected function opCreateRestoreTree()
	{
		try
		{
			if (!$this->prepare(ZMC_Yasumi_Job::$notRunning, $waitForState = ZMC_Yasumi_Job::FINISHED))
				return;
			$this->command(array('pathInfo' => '/amadmin/find_tapes/'), $this->reply); 
			if (empty($this->reply->needed))
				throw new ZMC_Exception_YasumiFatal("No media found.");

			if ($this->start('Exploring: ' . $this->data['zmc_message']))
				return; 

			$e = null;
			if (false === ($this->sqlFilesOnlyFp = fopen($this->sqlFilesOnlyFn, 'w+'))) 
				throw new ZMC_Exception_JobAbort(ZMC::getFilePermHelp($this->sqlFilesOnlyFn));
			if (false === ($this->sqlFp = fopen($this->sqlFile, 'w')))
				throw new ZMC_Exception_JobAbort(ZMC::getFilePermHelp($this->sqlFile));
			if (false === ($this->sqlMp = fopen($this->mergedFile, 'w')))
				throw new ZMC_Exception_JobAbort(ZMC::getFilePermHelp($this->mergedFile));
			if (false === ($this->sqlSp = fopen($this->sortedFile, 'w')))
				throw new ZMC_Exception_JobAbort(ZMC::getFilePermHelp($this->sortedFile));
			fclose($this->sqlSp);

			ini_set('memory_limit','-1');
			if (ZMC::$registry->debug)
			{
				$command = ZMC_ProcOpen::procOpen($cmd = ZMC::$registry->svn->zmc_sort, 'LC_ALL=' . ZMC::$registry->locale_sort . ' ' . ZMC::$registry->svn->zmc_sort, array('--version'), $stdout, $stderr, "$cmd command failed unexpectedly", null, '/var/tmp', ZMC::$registry->proc_open_short_timeout);
				$this->errorLog(__CLASS__ . __FUNCTION__ . __LINE__ . " $cmd - $stdout $stderr");
			}

			
			$this->addIndex();
			$start_db_time = $this->microtime_float();
			$this->setState("Creating DB Index Table ..");
			$this->createNestedSetTable();
			$end_db_time = $this->microtime_float();
			$total_db_time = $this->time_diff($start_db_time , $end_db_time);
			$this->setState("Finished DB Index Table $total_db_time.");

			if (false && ZMC::$registry->dev_only)
			{
				$rand = rand(0,9);
				error_log("Developer Mode: Sleeping $rand seconds to delay recording amgetindex results by random number of seconds. Helps testing of multi-user test cases involving spinner/state transitions.");
				sleep($rand);
			}
		}
		catch(Exception $e)
		{
			$this->exception = $e;
			switch(get_class($e))
			{
			case 'ZMC_Exception':
				$this->endState = self::FAILED;
				break;
			case 'ZMC_Exception_JobAbort':
				$this->endState = self::ABORTED;
				break;
			case 'ZMC_ProcOpen_Terminate':
			case 'ZMC_Exception_ProcOpen':
			case 'ZMC_Exception_YasumiFatal':
			case 'Exception':
			default:
				$this->endState = self::CRASHED;
				break;
			}
		}
	}

	public function addIndex()
	{
		$errors = '';
		$this->setState("Reading Index Files ..");
		$id = 1;
		$prevId = $id;
		$dirNumber = 1;
		try
		{
			$addedDirs = array();

			
			foreach($this->reply->needed as $record)
			{
				$diskName = mb_ereg_replace(':', '_', mb_ereg_replace('\\\\', '_', mb_ereg_replace('/', '_', $record['disk_name'])));
				$fn = $this->getAmandaConfPath(true) . 'index/' . $record['host'] . "/$diskName/" . $record['datetime'] . '_' . $record['level'] . '.gz';
				$this->setState(null, "读取 $record[host]:$diskName 在时间节点 $record[datetime] 备份级别为 L$record[level]的数据");
				$this->debugLog(__FUNCTION__ . "() - sending index to sort: $fn");
				if (false === ($fp = gzopen($fn, 'r')))
					throw new ZMC_Exception("中止。索引文件丢失： $fn");

				if($this->data['_key_name'] === 'windowssqlserver' || $this->data['_key_name'] === 'windowsexchange' || $this->data['_key_name'] === 'windowshyperv'){
					$line = "/";
					
					if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape($line) . "\n"))
						throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
					$count_files_dir = 0;

					while(!gzeof($fp))
					{
						if($count_files_dir >= ZMC::$registry->display_max_files){
							$this->reply->state['warning_message'] = "当前备份备份数据文件/文件夹数大于 '".ZMC::$registry->display_max_files."' 。请联系管理员在页面". ZMC::getPageUrl($this->reply, 'Admin', 'preferences') . "增加选项 'Maximum Files to Display' 的值。 或者使用 '还原所有' 选项进行还原。";
							break;
						}
						$line = gzgets($fp);
						if(!empty($this->data['restore_search'])){
							$pattern = $this->data['restore_search'];
							if(!ZMC::match($line, $pattern, $this->data['restore_pattern_type'])){
								
								continue;
							}
						}

						$database = explode("/", $line);

						if(!isset($addedDirs[$record['level']])){
							$addedDirs[$record['level']] = array();
						}

						if(!isset($addedDirs[$record['level']][$database[1]])){
							$addedDirs[$record['level']][$database[1]] = array();
							if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape("/$database[1]/") . "\n"))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
						}
						if(!isset($addedDirs[$record['level']][$database[1]][$database[2]])){
							$addedDirs[$record['level']][$database[1]][$database[2]] = array();
							if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape("/$database[1]/$database[2]/") . "\n"))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
						}
						if(!isset($addedDirs[$record['level']][$database[1]][$database[2]][$database[3]])){
							$addedDirs[$record['level']][$database[1]][$database[2]][$database[3]] = 1;
							if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape("/$database[1]/$database[2]/$database[3]/") . "\n"))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
						}
						if (strlen($line))
							if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape($line)))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
						$count_files_dir++;
					} 
				} else {
					$isFirstLine = true;
					$dirList = array("/");
					$count_files_dir = 0;
					while(!gzeof($fp))
					{
						if($count_files_dir >= ZMC::$registry->display_max_files){
							$this->reply->state['warning_message'] = "Backup has more than '".ZMC::$registry->display_max_files."' files/directories. Please increase 'Maximum Files to Display' from ". ZMC::getPageUrl($this->reply, 'Admin', 'preferences') . " or use 'Restore All' option for restore.";
                            $this->reply->state['warning_message'] = "当前备份备份数据文件/文件夹数大于 '".ZMC::$registry->display_max_files."' 。请联系管理员在页面". ZMC::getPageUrl($this->reply, 'Admin', 'preferences') . "增加选项 'Maximum Files to Display' 的值。 或者使用 '还原所有' 选项进行还原。";
                            break;
						}
						$line = gzgets($fp);


						if($isFirstLine && $line !== "/"){
							if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape("/") . "\n"))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
							$isFirstLine = false;
						}
						if(!empty($this->data['restore_search'])){
							$pattern = $this->data['restore_search'];
							if(!ZMC::match($line, $pattern, $this->data['restore_pattern_type'])){
								continue;
							}

							$isDir = (($line[$last = strlen($line)-1] === '/') ? '/' : false);
							if($isDir) $dirList[] = substr($line, 0, -1); 

							$curLine = $line;
							while(true){
								$parentName = dirname($curLine);
								if(!in_array($parentName, $dirList)){
									if (!fwrite($this->sqlMp, $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape($parentName . "/") . "\n"))
										throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed.");
									$curLine = $parentName;
									$dirList[] = $parentName;
								} else {
									break;
								}
							}
						}

						if (strlen($line))
							if (false === ($wrote = fwrite($this->sqlMp, $line = $record['level'] . '/' . ZMC_RestoreJob_Amgtar::unescape($line))))
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted. Write to 'sort' failed."); 
							elseif ($wrote !== strlen($line)) 
							{
								
								$stderr = '';
								throw new ZMC_Exception_YasumiFatal("Processing indexes aborted (wrote $wrote, but expected to write " . strlen($line) . ') ' . $stderr);
							}
						$count_files_dir++;
					}
				}
				gzclose($fp);
			}
			if (false === fclose($this->sqlMp)) 
				throw new ZMC_Exception_YasumiFatal('Failed to close child STDIN (child will hang).');
			$this->setState("排序索引文件 ..");
			$sort_start_time  = $this->microtime_float();
			$this->debugLog(__FUNCTION__ . "() - finished sending indexes to sort");
			try{
				$command = ZMC_ProcOpen::procOpen($cmd = ZMC::$registry->svn->zmc_sort, 'LC_ALL=' . ZMC::$registry->locale_sort . ' ' . ZMC::$registry->svn->zmc_sort, array($this->mergedFile , '--batch-size=512', '--buffer-size=512m', '--temporary-directory=/var/tmp/', '--field-separator=/', '--key=2', '-o', $this->sortedFile), $stdout, $stderr, "$cmd command failed unexpectedly");
				if($stderr){
					throw new ZMC_Exception_YasumiFatal('Processing of sort failed.' .$stderr);
				}
				if (!$this->debug)
					unlink($this->mergedFile);
				
			}catch(Exception $e){
				throw new ZMC_Exception_YasumiFatal('Processing of sort failed.' .$e);
			}
			$sort_end_time  = $this->microtime_float();
			$total_time_to_sort = $this->time_diff($sort_start_time, $sort_end_time);
			$this->setState("Finished Sorting on Index Files $total_time_to_sort.");
			
			
			{
				$start_parse_time = $this->microtime_float();
				$this->setState("Parsing Sorted Index Files ..");
			}


			
			if (false === ($this->sqlSp = fopen($this->sortedFile, 'r')))
				throw new ZMC_Exception_YasumiFatal(ZMC::getFilePermHelp($this->sortedFile));
			$this->dirs2ids['/'] = array('did' => 1, 'pid' => 0, 'level' => 0, 'children' => 0);
			$prevRecord = '/';
			$prevParentName = false;
			$prevName = '';
			$prevIsDir = true;
			$prevLevel = 0;
			$flushed = false;
			$skip = 0;
			if ($this->data['_key_name'] === 'cifs') 
			{
				ZMC::parseShare($record['disk_name'], $host, $name, $path);
				$skip = strlen($path); 
				$this->reply->state['output'][] = "skip=$skip";
			}
			$sqlFilesContent = array();
			while (($output = fgets($this->sqlSp)) !== false){
				foreach(explode("\n", $output) as $line)
				{
					if (empty($line)) 
						continue;
					if($prevRecord !== $line)
						$id = $prevId + 1;
					$separator = strpos($line, '/');
					$level = hexdec(substr($line, 0, $separator));
					
					$level = (1 << $level);
					$isDir = (($line[$last = strlen($line)-1] === '/') ? '/' : false);
					$line = $isDir ? substr($line, $separator+1, -1) : substr($line, $separator+1) ;
					if ($skip)
					{
						$line = substr($line, $skip +1);
						
					}
					if (empty($line)) 
						continue;
					
					$name = basename($line);
					$parentName = dirname($line);
					unset($prevParent);
					if ($prevParentName === false)
						$prevParent = array('did' => 0, 'children' => 0); 
					else
						$prevParent =& $this->dirs2ids[$prevParentName];

					if ($prevIsDir && $prevRecord !== $line)
					{
						$this->dirs2ids[$prevRecord] = array('filename' => $prevName, 'did' => $prevId, 'pid' => &$prevParent, 'level' => $prevLevel, 'children' => 0);
					}
					
					if ($prevRecord === $line)
					{
						$prevLevel |= $level;
						if(!$isDir){
							$sqlFilesContent[$prevId] = array('did' => $this->dirs2ids[$parentName][did], 'level' => $prevLevel, 'type' => 2, 'line' => $line, 'name' => $name);
						}
						continue;
					} else {
						if(!$isDir)
							$sqlFilesContent[$id] = array('did' => $this->dirs2ids[$parentName][did], 'level' => $prevLevel, 'type' => 2, 'line' => $line, 'name' => $name);
					}

					
					
					if (!isset($this->dirs2ids[$parentName])){ 
						$curLine = $line;
						$prevParentName = false;
						$dirList = array();
						while(true){
							$parentName = dirname($curLine);
							if($parentName === "/")
								break;
							if(!in_array($parentName, $dirList)){
								$curLine = $parentName;
								$dirList[] = $parentName;
							}
							else{
								break;
							}
						}
						foreach(array_reverse($dirList) as $parentName){
							if(!isset($this->dirs2ids[$parentName])){
								if ($prevParentName === false)
									$prevParent = array('did' => 1, 'children' => 0); 
								else
									$prevParent =& $this->dirs2ids[$prevParentName];
								$this->dirs2ids[$parentName] = array('filename' => $parentName,'did' => $id++, 'pid' => &$prevParent, 'level' => $prevLevel, 'children' => 0);
								$curLine = $parentName;
								$prevParentName = $parentName;
							}
						}
					}
					if (!isset($this->dirs2ids[$parentName])){
						throw new ZMC_Exception_YasumiFatal("Not set (output=$output; line=$line): dirs2ids[$parentName]\n" . print_r($this->dirs2ids,true)); 
					}

					$curParent =& $this->dirs2ids[$parentName];
					do
					{
						$curParent['children']++;
						$curParent =& $curParent['pid'];
					} while (!empty($curParent));
					
					$prevRecord = $line;
					$prevIsDir = $isDir;
					$prevLevel = $level;
					$prevParentName = $parentName;
					$prevName = $name;
					$prevId = $id;
				}
			
			
				if ((0 === (count($this->dirs2ids) % 5000)) && ($percent = ZMC_Timer::testMemoryUsage()))
					if ($percent > 85)
						throw new ZMC_Exception_YasumiFatal("Insufficient memory available ($percent% used).");
					else
						$this->reply->addWarning($msg = "内存不足: $percent% 的内存PHP已使用");
			}
			
			foreach($sqlFilesContent as $id => $record)
				fputs($this->sqlFilesOnlyFp, $id . ";{$record['did']};0;{$record['level']};2;\"" . str_replace('"', '\\"', $record['line']) . "\";{$record['name']};\n");

			{
				$end_parse_time = $this->microtime_float();
				$total_parse_time = $this->time_diff($start_parse_time , $end_parse_time);
				$this->setState("Finished Parsing of Sorted Index Files $total_parse_time.");
			}   
			$this->debugLog(__FUNCTION__ . "() returning (sort complete)");
		}
		catch(Exception $e)
		{
			$errors = $e;
			$this->reply->state['output'][] = $errors; 
			if (!$this->debug){
				unlink($this->mergedFile);
				unlink($this->sortedFile);
				unlink($this->sqlFilesOnlyFn);
				unlink($this->sqlFile);
			}

			throw $e;
		}
	}

	private function createNestedSetTable()
	{
		rewind($this->sqlFilesOnlyFp);
		$fid = 1;
		$file = '';
		foreach($this->dirs2ids as $dirname => &$dir) 
		{
			while ($fid && $fid < $dir['did'])
			{
				fputs($this->sqlFp, $file);
				if (false === ($file = fgets($this->sqlFilesOnlyFp, 4096)))
					$fid = false;
				else
					$fid = substr($file, 0, strpos($file, ';'));
			}
			fputs($this->sqlFp, $dir['did'] . ';'
				. $dir['pid']['did'] . ';'
				. ($dir['did'] + $dir['children'] +1) . ';'
				. $dir['level']
				. ';1;"'
				. str_replace('"', '\\"', $dirname)
				. ($dirname === '/' ? '':'/'). '";'
				. $dir['filename'] . ';'
				. "\n");
		}

		while($file !== false)
		{
			fputs($this->sqlFp, $file);
			$file = fgets($this->sqlFilesOnlyFp, 4096);
		}

		unset($this->dirs2ids); 
		$feof = feof($this->sqlFilesOnlyFp);
		fclose($this->sqlFilesOnlyFp);
		fclose($this->sqlFp);
		if (!$feof)
			throw new ZMC_Exception('Unable to finish merging file list and directory list (' . $fidv.'@' . $this->sqlFilesOnlyFn . '=>' . $lastFile . ').');
		if (!$this->debug){
			unlink($this->sortedFile);
			unlink($this->sqlFilesOnlyFn);
		}
		$loading_start_time = $this->microtime_float();
		$this->debugLog("Start Loading '$this->sqlFile' into table " . $this->data['table']);
		if($this->debug)$this->setState("Start Loading '$this->sqlFile' into table " . $this->data['table']);
		chmod($this->sqlFile, 0755);
		ZMC_Mysql::connect(true);
		
		ZMC_Mysql::query("LOAD DATA INFILE '$this->sqlFile' INTO TABLE " . $this->data['table'] . "
			FIELDS TERMINATED BY ';'
			ENCLOSED BY '\"'
			ESCAPED BY '\\\\' 
			LINES TERMINATED BY '\\n'
			(id, parent_id, sibling_id, level, type, filename, name)");
		if (!$this->debug)
			unlink($this->sqlFile);
		
		$loading_end_time = $this->microtime_float();
		$total_load_time = $this->time_diff($loading_start_time , $loading_end_time);
		$this->debugLog("Finished Loading '$this->sqlFile' into table " . $this->data['table'] ." $total_load_time.");
		if($this->debug) $this->setState("Finished Loading '$this->sqlFile' into table " . $this->data['table'] . " $total_load_time.");
	}
}
