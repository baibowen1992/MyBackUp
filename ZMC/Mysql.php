<?



















class ZMC_Mysql_Exception extends ZMC_Exception
{
	private $_errno = null;
	private $_mysqlError = null;

	








	function __construct($message = null)
	{
		if ($this->_errno = ZMC_Mysql::errno())
			$this->_mysqlError = mysql_error();
		else
		{
			if (empty($message)) 
				$message = "Unknown MySQL error occurred ($message)";
			ZMC::debugLog(__CLASS__ . '::' . __FUNCTION__ . ' - exception without a reason (bug)');
		}

		parent::__construct("$message\nMySQL error #$this->_errno: $this->_mysqlError", $this->getCode(), null, null, 2);
	}

	public function getMysqlError()
	{
		return $this->_mysqlError;
	}
}

class ZMC_Mysql
{
	public static $queries = 0; 
	private static $_connection;

	public static function getConnection()
	{
		return self::$_connection;
	}	

	
	public static function voidConnection()
	{  self::$_connection = null; }

	public static function close()
	{
		if (!empty(self::$_connection))
			mysql_close(self::$_connection);
	}

	



	public static function connect($persisent = true)
	{
		if (empty(ZMC::$registry) || empty(ZMC::$registry->cnf->zmc_mysql_zmc_password))
			throw new ZMC_MySQL_Exception('ZMC registry not initialized?  No password available for MySQL.');

		$i = 1;
		$flags = 0;
		if ((ZMC::$registry->database->host !== '127.0.0.1') && (ZMC::$registry->database->host !== 'localhost'))
			ZMC::Quit('@TODO:	$flags = MYSQL_CLIENT_COMPRESS;');
		while(false === (self::$_connection = call_user_func(($persisent ? 'mysql_pconnect':'mysql_connect'), ZMC::$registry->database->host, ZMC::$registry->database->user, ZMC::$registry->cnf->zmc_mysql_zmc_password, $flags)))
		{
			error_log('Waiting for MySQL connection.  Sleeping 1 second.');
			sleep(1);
			if ($i++ > 15)
				throw new ZMC_MySQL_Exception("Could not connect to MySQL server, after $i attempts.");
		}

		
















		if ('utf8' !== ($set = mysql_client_encoding(self::$_connection)))
		{
			mysql_set_charset('utf8', self::$_connection); 
			ZMC::errorLog("ZMC DB default character set was: $set");
			if (false === @mysql_select_db(ZMC::$registry->database->name))
				throw new ZMC_MySQL_Exception('Could not select the database.');
		}
		return self::$_connection;
	}

	public static function error()
	{
		return (empty(self::$_connection) ? '' : mysql_error(self::$_connection));
	}

	public static function errno()
	{
		return (empty(self::$_connection) ? '' : mysql_errno(self::$_connection));
	}

	








	public static function query($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		try{
		try{
		if (is_array($sql))
			$sql = 'SELECT * FROM ' . array_shift($sql) . ' WHERE ' .  self::computeWhere($sql, true);
		self::$queries++;
		if (ZMC::$registry->dev_only || empty($sanitizedSql))
			$sanitizedSql = $sql;
		if (empty(self::$_connection))
			throw new ZMC_Mysql_Exception("Unable to connect to ZMC DB. Perhaps password has been changed?");

		if (ZMC::$registry->log_slow_queries)
		{
			$boring = (!strncasecmp($sql, 'select', 5) || !strncasecmp($sql, 'show', 4) || !strncasecmp($sql, 'insert into events', 18));
			$stime = ZMC_Timer::mtime();
		}
		$resource = @mysql_query($sql, self::$_connection);
		if (ZMC::$registry->log_slow_queries)
		{
			$elapsed = bcsub(ZMC_Timer::mtime(), $stime, 5);
			if (!$boring || (($elapsed >= 1) && ZMC::$registry->debug))
				self::audit($sanitizedSql, (boolean)$resource, $elapsed);
		}

		if ($resource === false && !$failureOk)
		{
			
			throw new ZMC_Mysql_Exception(empty($userErrorMessage) ? "DB query failed: $sanitizedSql" : "$userErrorMessage: $sanitizedSql");
		}

		return $resource;
		}catch (PDOException $e){}
		}catch (Exception $e){
			$error = substr($e->getMessage(), stripos($e->getMessage(), "MySQL error"));
			if(preg_match("/marked as crashed/", $error)){
			$a = '<div style="" id="zmcMessageBox0" class="zmcMessageBox"><div style="float:left" id="msgBoxErrors" class="zmcMessages zmcUserErrors"><div onclick="this.parentNode.style.display=\'none\'" class="zmcMsgBox">X</div><div class="zmcMsgWarnErr"><img alt="Warnings" src="/images/global/calendar/icon_calendar_failure.gif" style="cursor:pointer" onclick="this.parentNode.style.display=\'none\'">&nbsp;'.$error.'. Please <a target=\'_blank\' href="' . ZMC::$registry->lore  . '440">click here</a> to resolve this issue.</div><div></div>';
			ZMC::print_me($a, true);
			}
		}
	}

	








	public static function queryAndFree($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		$resource = self::query($sql, $userErrorMessage, $failureOk = false, $sanitizedSql = null);
		if (!is_bool($resource))
			mysql_free_result($resource);
	}
	
	










	public static function insert($table, $row, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null, $replace = false)
	{
		if ($userErrorMessage === null)
			$userErrorMessage = "DB failure while inserting row into '$table'";
		foreach($row as &$value)
			if ($value === null)
				$value = 'NULL';
			else
				$value = mysql_real_escape_string($value, self::$_connection);
		$sql = ($replace ? 'REPLACE' : 'INSERT') . " INTO $table (" . implode(',', array_keys($row)) . ') VALUES ('
			. "'" . join("','", $row) . "')";
		$resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql);
		if ($resource)
		{
			$i = mysql_affected_rows();
			return $i;
		}

		return false;
	}

	public static function replace($table, $row, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		return self::insert($table, $row, $userErrorMessage, $failureOk, $sanitizedSql, true);
	}

	










	public static function update($table, array $row, $where, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		if (empty($row))
			return false;
		if ($userErrorMessage === null)
			$userErrorMessage = "DB failure while updating '$table'";
		$keys = self::escapeTerms($row);
		$where = self::computeWhere($where);
		$sql = "UPDATE $table SET " . implode(',', $keys) . " WHERE $where";
		$resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql);
		if ($resource)
		{
			$i = mysql_affected_rows();
			return $i;
		}

		return false;
	}

	public static function delete($table, $where = null, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		if ($userErrorMessage === null)
			$userErrorMessage = "DB failure while deleting a row from '$table'";
		if (empty($where))
			return false;

		$sql = "DELETE FROM $table WHERE " . self::computeWhere($where, true);
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
			return mysql_affected_rows();

		return false;
	}

	







	public static function audit($query, $returnStatus, $elapsed = 0)
	{
		$query = "SQL: $elapsed " . strtr($query, "\n\t", '  ');
		ZMC_Timer::accumulate($elapsed);
		ZMC::auditLog($query, $returnStatus);
		if ($elapsed > 10)
			ZMC::errorLog('SLOW QUERY ' . $query, $returnStatus);
		if ($elapsed > 60)
			ZMC::$registry->setOverrides(array('sticky_once' => array_merge(ZMC::$registry->sticky_restart, array('warnings' => 'A DB query was unusually slow.'))));
	}


	







	public static function &getOneRow($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		if (is_bool($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql)))
			return $resource;
		$result = mysql_fetch_assoc($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql));
		if (!is_bool($resource))
			mysql_free_result($resource);
		return $result;
	}

	







	public static function getOneValue($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
		{
			$row = mysql_fetch_row($resource);
			if (!is_bool($resource))
				mysql_free_result($resource);
			if ($row)
				return $row[0];
		}
		return false;
	}

	







	public static function &getAllRows($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		$output = array();
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
		{
			while ($row = mysql_fetch_assoc($resource))
				$output[] = $row;
			if (!is_bool($resource))
				mysql_free_result($resource);
		}

		return $output;
	}

	









	public static function &getAllRowsMap($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null, $key = null)
	{
		$output = array();
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
		{
			while ($row = mysql_fetch_assoc($resource)){
				if($key === null)
					$output[] = $row;
				else
					$output[($row[$key])] = $row;
			}
			if (!is_bool($resource))
				mysql_free_result($resource);
		}

		return $output;
	}

	







	public static function &getAllOneValue($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null)
	{
		$output = array();
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
		{
			while ($row = mysql_fetch_row($resource))
				$output[] = $row[0];
			if (!is_bool($resource))
				mysql_free_result($resource);
		}

		return $output;
	}

	








	public static function &getAllOneValueMap($sql, $userErrorMessage = null, $failureOk = false, $sanitizedSql = null, $limit = 0)
	{
		$output = array();
		if ($resource = self::query($sql, $userErrorMessage, $failureOk, $sanitizedSql))
		{
			$i = -1;
			while (($row = mysql_fetch_row($resource)) && ($i < $limit))
			{
				$output[$row[0]] = $row[1];
				$limit && $i++;
			}
			if (!is_bool($resource))
				mysql_free_result($resource);
		}

		return $output;
	}

	



	public static function escape($data)
	{
		if (is_array($data)) 
		{
			if (ZMC::$registry->debug)
			{
				echo "\n<br><hr>\n", __FILE__,'#',__LINE__,'  ',__CLASS__,':',__FUNCTION__,' - data argument was an array:';
				ZMC_ZendDebug::dump($data);
				ZMC_ZendDebug::dump(debug_backtrace());
				ZMC::quit();
			}
			throw new ZMC_MySQL_Exception('Could not escape array data (' . implode(";\n", $data) . ')');
		}
		return mysql_real_escape_string(trim($data), self::$_connection);
	}

	



	public static function log($query)
	{
		ZMC::debugLog($query);
	}

	






	public static function countTable($table, $userErrorMessage = null, $failureOk = false)
	{
		return ZMC_Mysql::getOneValue("SELECT COUNT(*) from `$table`", $userErrorMessage, $failureOk = false);
	}

	


	public static function &logVars(ZMC_Registry_MessageBox $pm)
	{
		$log = '';
		$rows = ZMC_Mysql::getAllOneValueMap('SELECT * FROM information_schema.SESSION_VARIABLES UNION ALL SELECT * FROM information_schema.SESSION_STATUS');
		foreach($rows as $key => $value)
			$log .= "$key=$value\n";

		if ($rows['QCACHE_FREE_MEMORY'] < (0.8 * $rows['QUERY_CACHE_SIZE']))
			$pm->addWarning('ZMC DB query cache is ' . (round(1 - ($rows['QCACHE_FREE_MEMORY'] / $rows['QUERY_CACHE_SIZE']), 2) * 100) . '% full.');
		ZMC::debugLog("MySQL Client Connection: \n" . $log); 
		return $rows;
	}

	private static function computeWhere($where, $emptyOk = false)
	{
		if (!$emptyOk && empty($where))
			throw new ZMC_MySQL_Exception(__CLASS__ . '::' . __FUNCTION__ . ': $where must not be empty!');

		if (!is_array($where))
			return $where;

		$wkeys = array();
		foreach($where as $key => $value)
			if ($value === null)
				$wkeys[] = " $key = NULL";
			else
				$wkeys[] = " $key = '" . self::escape($value) . "'";
		return implode(' AND ', $wkeys);
	}

	private static function escapeTerms($row)
	{
		$keys = array();
		if (!is_array($row)) ZMC::quit($row);
		foreach($row as $key => $value)
			if ($value === null)
				$keys[] = " $key = NULL";
			elseif (is_array($value))
				if (isset($value['mysql']))
					$keys[] = " $key = " . $value['mysql'];
				else
					throw new ZMC_MySQL_Exception(__FUNCTION__ . ": unrecognized term array: " . print_r($value, true));
			else
				$keys[] = " $key = '" . self::escape($value) . "'";
			
		return $keys;
	}
}

ZMC_Mysql::connect(); 
