<?













class ZMC_User
{
	public static $users;

	public static function init()
	{
		self::$users = ZMC_Mysql::getAllRowsMap('SELECT * FROM users ORDER BY user_id', '无法读取数据表 "users" ', false, null, 'user_id');
	}

	public static function count()
	{
		return count(self::$users);
	}

	public static function getByName($name)
	{
		foreach(self::$users as &$user)
			if ($user['user'] === $name)
				return $user;
		return false;
	}


	public static function get($field = null, $id = null)
	{
		$id = intval($id);
		if (empty($id))
			if (isset($_SESSION['user_id']))
				$id = $_SESSION['user_id'];
		if (empty($id))
			return false;
		if ($field)
			return self::$users[$id][$field];
		return self::$users[$id];
	}

	public static function set($id, $field, $value)
	{
		ZMC_Mysql::update('users', array($field => $value), "user_id='$id'", "Unable to set '$field' to '$value'");
	}

	public static function getBy($field, $value)
	{
		foreach(self::$users as $id => $row)
			if ($row[$field] == $value)
				return $id;
		return false;
	}

	





	public static function hasRole($role, $id = null)
	{
		$id = intval($id);
		if (!($id > 0))
		{
			if (isset($_SESSION['user_id']))
				$id = $_SESSION['user_id'];
			else
				return false;
		}

		if ($id == 1)
			return $role;
	
		if (isset(self::$users[$id]) && ($role === self::$users[$id]['user_role']))
			return $role;

		return false;
	}
	
	public static function filterHostUsername(ZMC_Registry_MessageBox $pm, &$name)
	{
		$name = trim($name); 
		if (empty($name))
		{
			$pm->addError('Missing host username.');
			return false;
		}
		if (!ctype_print($name))
		{
			$pm->addError("Invalid characters in user name '$name'. Avoid spaces, if possible. Avoid unprintable characters.");
			return false;
		}
		return true;
	}

	public static function filterMysqlUsername(ZMC_Registry_MessageBox $pm, &$name)
	{
		$name = trim($name); 
		if (empty($name))
		{
			$pm->addError('Missing Mysql username.');
			return false;
		}
		if (!preg_match('/^[a-z0-9_]+([-.][a-z0-9]+)*$/', $name)) 
		{
			$pm->addError("Invalid characters in user name '$name'.  Do not use spaces.  Use only alphanumeric characters, periods, or hyphens.");
			return false;
		}
		return true;
	}

	public static function filterZmcUsername(ZMC_Registry_MessageBox $pm, &$name)
	{
		$name = trim($name); 
		if (empty($name))
		{
			$pm->addError('Missing ZMC username.');
			return false;
		}
		if (!preg_match('/^[a-z0-9]+([-.][a-z0-9]+)*$/', $name))
		{
			$pm->addError("用户名 '$name'.中含有非法字符，请不要使用空格。");
			return false;
		}
	}

	public static function isValidEmail($email)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return $email;

		
		$parts = explode('@', $email);
		if (empty($parts[0]))
			return false;
		if (ZMC::isValidHostName($parts[1]) && count($parts) === 2)
			return $email;
		return false;
	}

	





	public static function isValidAmandaEmail($email)
	{
		if (strpbrk($email, '`!$'))
			return false;
		if (self::isValidEmail($email))
			return $email;
	}

	


	public static function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (!ZMC_User::hasRole('Administrator'))
			return;

		if (!isset(ZMC::$userRegistry['sort']))
			ZMC::$userRegistry['sort'] = '';
	
		$paginator = new ZMC_Paginator($pm, 'FROM users', array('user', 'user_role', 'email', 'registration_date', 'user_id'));

		$paginator->createColUrls($pm);
		array_pop($pm->columns);
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}
	
	public static function authenticateUser($inUserName, $inPassword)
	{
		$username = ZMC_Mysql::escape($inUserName); 
		$password = ZMC_Mysql::escape($inPassword); 
		$sql = "SELECT user_id FROM users WHERE user='$username' AND password=SHA('$password')";
		$result = ZMC_Mysql::getOneValue($sql, "Query failure while retrieving user_id for user '$username'.", false, substr($sql, 0, strpos($sql, 'SHA')+3));
		ZMC::auditLog(__FUNCTION__ . "($inUserName)", $result);
		return $result;
	}

    /**
     * 取用户的id，登录的时候
     * @param $inUserName
     * @return bool
     * @throws ZMC_Mysql_Exception
     */
    public static function getUserIdByNetwork($inUserName)
    {
        $username = ZMC_Mysql::escape($inUserName);
        $sql = "SELECT user_id FROM users WHERE user='$username'";
        $result = ZMC_Mysql::getOneValue($sql, "Query failure while retrieving user_id for user '$username'.", false, substr($sql, 0, strpos($sql, 'SHA')+3));
        ZMC::auditLog(__FUNCTION__ . "($inUserName)", $result);
        return $result;
    }

    /**
     * 取用户的id 和 资源池，登录的时候
     *
     * add by Johnny @20141217
     *
     * @param $inUserName
     * @return bool
     * @throws ZMC_Mysql_Exception
     */
    public static function getUserIdAddResPoolByNetwork($inUserName)
    {
        $username = ZMC_Mysql::escape($inUserName);
        $sql = "SELECT user_id,resource_pool FROM users WHERE user='$username'";
        return ZMC_Mysql::getAllRowsMap($sql, '无法读取用户表 "users" ', false, null, 'user_id');;
    }

    /**
     * @param $userId
     * @return bool
     */
    public static function getResPoolByUserId($userId){
        try {
            $sql = "SELECT resource_pool FROM users WHERE user_id=$userId";
            return ZMC_Mysql::getOneValue($sql,"select resource_pool by userId:$userId");
        }
        catch(Exception $e){}
    }

    /**
     * 更新资源池，
     *
     * add by Johnny @20141219
     */
    public static function updateUserResPool($resourcePool,$username){
        $sql = "update users set resource_pool='$resourcePool' where user='$username'";
        ZMC_Mysql::queryAndFree($sql, "user '$username' update resource_pool ,rerPool value:$resourcePool .", false, null);
    }

    function http_post_data($url, $data_string) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        //echo $return_content.'<br>';
        ob_end_clean();

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //echo $return_code.'<br>';
        return array($return_code, $return_content);
    }


    public static function authenticateUserByNetwork($inUserName, $inToken)
    {

        // $url  = "http://127.0.0.1:8082/portal/user/checkIpAndIo";
        $url  = "http://172.66.6.113:8082/portal/user/checkIpAndIo";
//        $data = json_encode(array('username'=>$inUserName, 'password'=>$inPassword));

        $data = json_encode(array('username'=>$inUserName, 'token'=>$inToken));
//        echo "$inUserName=======$inPassword";
//        echo "+++++++++++singlelogin:".$inSinglelogin;
        //echo $data.'<br>'.'<br>';
        //list($return_code, $return_content) = http_post_data($url, $data);
        $result = ZMC_User::http_post_data($url, $data);

        //echo 'johnnytest';
        /*
        header("application/json;charset=UTF-8");
        $urlNetWork = 'http://10.122.129.99:8082/portal/user/login';
        $request = new HttpRequest($urlNetWork , HttpRequest::METH_POST);
        //$request->setHeaders(array('Accept' => 'application/json;charset=UTF-8', 'content-type' => 'application/json;charset=UTF-8'));
        $json_str = "{'username':'johnny','password':'11111111'}";
        //$request->setOptions(array('username' => 'johnny', 'password' => '11111111'));
        $request->setOptions($json_str);
        $result = $request->send();
        */
        return $result;
    }
	
	public static function saveUser(ZMC_Registry_MessageBox $pm)
	{
		if (ZMC_User::hasRole('Administrator')) 
		{
			if (isset($_POST['edit_id']) && $_POST['submit'] != 'Add')
				$_POST['edit_id'] = intval($_POST['edit_id']);
			else
				$_POST['edit_id'] = 0;
		}
		else
		{
			$_POST['edit_id'] = $_SESSION['user_id'];
			$_POST['user'] = $_SESSION['user'];
		}

		if (!empty($_POST['user']))
			$_POST['user'] = trim($_POST['user']);

		if (!empty($_POST['origUsername']))
		{
			if (empty($_POST['user']))
				$_POST['user'] = $_POST['origUsername'];
			if ($_POST['origUsername'] !== $_POST['user'])
				$nameChange = "账户名 '$_POST[origUsername]' 修改为 '$_POST[user]'";
		}
		ZMC_User::filterZmcUsername($pm, $_POST['user']);
		$_POST['origUsername'] = $_POST['user'];
		$_POST['user_role'] = isset($_POST['user_role']) ? trim($_POST['user_role']) : '';
		if (empty($_POST['zmandaNetworkID']))
			$_POST['zmandaNetworkID'] = $_POST['zmandaNetworkPassword'] = '';
		else
		{
			$_POST['zmandaNetworkID'] = trim($_POST['zmandaNetworkID']);
			$_POST['zmandaNetworkPassword'] = trim($_POST['zmandaNetworkPassword']);
		}
		$password = empty($_POST['password']) ? '' : trim($_POST['password']);
		$email = $_POST['email'] = trim($_POST['email']);
		if (empty($email))
			$pm->addError('请输入邮箱');
		else
		{
			if (strlen($email) === strrpos($email, '@localhost') + 10) 
				$_POST['email'] = "$email.localdomain";
			if (!self::isValidAmandaEmail($email))
				$pm->addWarnError('请提供合法邮箱，不能包含 `, !, or $');
		}
	
		
		if (empty($_POST['edit_id']) || !empty($_POST['password']) || !empty($_POST['confirm']))
		{
			if (empty($_POST['password']))
				$pm->addError('请输入密码');
			elseif ($_POST['password'] != $_POST['confirm'])
				$pm->addError('两次密码不匹配');
		}
	
		if (ZMC_User::hasRole('Administrator'))
		{
			if ($_POST['edit_id'] == 1) 
				$_POST['user_role'] = 'Administrator';
			elseif (empty($_POST['user_role']))
				$pm->addError('请选择角色.');
		}
		else
			$_POST['user_role'] = 'Operator';

		$existingId = ZMC_User::getBy('user', $_POST['user']);
		if ($existingId && $existingId != $_POST['edit_id'])
			$pm->addError("用户名 '$_POST[user]' 已经存在.");
		$existingId = ZMC_User::getBy('email', $email);
		if ($existingId && $existingId !=$_POST['edit_id'])
			$pm->addError('邮箱已经存在');
		if (!$pm->isErrors()) 
		{
			if (empty($_POST['edit_id']))
			{
				$_GET['sort'] = 3; 
				$_GET['dir'] = 1; 
				$_REQUEST['gotoPage'] = 1; 
			}
			$result = ZMC_User::updateUser($pm, $_POST['edit_id'], $_POST['user'], $_POST['user_role'], $email, $password, $_POST['zmandaNetworkID'], $_POST['zmandaNetworkPassword']);
			if ($result && !empty($_POST['edit_id']) && !empty($nameChange))
				$pm->addMessage($nameChange);
			return $result;
		}
		return null;
	}


    /**
     * 插入不存在于备份系统的用户
     *
     * add by Johnny
     * mod by Johnny @20141217 加入$resourcePool
     */
    public static function insertUser($username, $role, $email, $password, $zmandaNetworkId,$resourcePool){
        $sha1 = sha1($password);
        $sql = "INSERT INTO users (user, user_role, email, registration_date, password, network_ID, resource_pool)
						VALUES ('$username', '$role', '$email', NOW(), '$sha1', '$zmandaNetworkId','$resourcePool')";
        $sanitizedSql = str_replace("'$sha1'", "'***'", $sql);
        ZMC_Mysql::queryAndFree($sql, "Query failure while adding/updating information for user '$username'.", false, $sanitizedSql);
        $id = mysql_insert_id();
        self::init();
        return $id;
    }


    /**
     * 插入设备拥有者表， gci_drives_owner
     *
     * add by Johnny @20140926
     */
    public static function insertDrivesOwner($username, $owner){
        //$sql = "INSERT INTO users (user, user_role, email, registration_date, password, network_ID)
					//	VALUES ('$username', '$role', '$email', NOW(), '$sha1', '$zmandaNetworkId')";
        $sql = "INSERT INTO `gci_drives_owner` (`owner`,`drives`,`create_date`)VALUES('$username','$owner',NOW())";
        ZMC_Mysql::queryAndFree($sql, "Query failure while adding/updating information for user '$username'.", false, null);
        $id = mysql_insert_id();
        return $id;
    }

    /**
     * 查找出所有的设备拥有者
     * @return array
     */
    public static function findAllDrivesOwner($username){
        $sql = "SELECT id,owner,drives,create_date FROM gci_drives_owner ORDER BY id";
        if($username){
            $sql = "SELECT id,owner,drives,create_date FROM gci_drives_owner where owner='$username' ORDER BY id";
        }
        return ZMC_Mysql::getAllRowsMap($sql, '无法读取数据表 "gci_drives_owner" ', false, null, 'id');
    }


    /**
     * 删除设备， gci_drives_owner
     *
     * add by Johnny @20141030
     */
    public static function deleteDrivesOwner($delete_devices_name,$username){
        $sql = "DELETE FROM `gci_drives_owner` WHERE `drives` IN($delete_devices_name)";
        ZMC_Mysql::queryAndFree($sql, "user '$username' delete drives[gci_drives_owner] ,all drives name:$delete_devices_name .", false, null);
    }





	public static function updateUser($pm, $id, $username, $role, $email, $password, $zmandaNetworkId, $zmandaNetworkPassword)
	{
		if (empty($id))
		{
			$sha1 = sha1($password);
			$sql = "INSERT INTO users (user, user_role, email, registration_date, password, network_ID)
						VALUES ('$username', '$role', '$email', NOW(), '$sha1', '$zmandaNetworkId')";
			$sanitizedSql = str_replace("'$sha1'", "'***'", $sql);
			$pm->addMessage("创建用户: $username");
		}
		else
		{
			if ($id == 1) 
			{
				$role='Administrator';
			}
			$sql = "UPDATE users
						SET user='$username',
							user_role='$role',
							email='$email'"
							. (empty($zmandaNetworkId)	? '' : ", network_ID='$zmandaNetworkId'")
							. (empty($password)			? '' : ", password='" . sha1($password) . "'")
					. " WHERE user_id = $id";
			$sanitizedSql = preg_replace("/password='[^']+'/", "password='***", $sql);
			$pm->addMessage("账户更新: $username");
		}
	
		ZMC::debugLog(__CLASS__ . ':' . __FUNCTION__ . "($id, $username, $role, $email, " 
			. (ZMC::$registry->dev_only ? $password : '***')
			. ", $zmandaNetworkId, "
			. (ZMC::$registry->dev_only ? $zmandaNetworkPassword: '***')
			. " - " . substr($sql, 0, 6));
		ZMC_Mysql::queryAndFree($sql, "Query failure while adding/updating information for user '$username'.", false, $sanitizedSql);
		if (empty($id))
			$id = mysql_insert_id();
/*		if (!empty($zmandaNetworkPassword))
			ZMC_ZmandaNetwork::verifyAndSave($pm, $zmandaNetworkId, $zmandaNetworkPassword, $id);
*/
		self::init();
		return $id;
	}
	
	







	public static function deleteUser(ZMC_Registry_MessageBox $pm, $id)
	{
		$id = intval($id);
		if ($id <= 1)
		{
			$pm->addError("无法删除系统默认管理员用户 'admin'");
			return false;
		}
	
		if (!isset(self::$users[$id]))
			return $pm->addError("无法删除用户ID为 $id 的用户，已经删除了？");
		$username = self::$users[$id]['user'];
	
		
		if (($resource = ZMC_Mysql::query("DELETE FROM users WHERE user_id=$id", "Unable to delete user id #$id") === true) && (mysql_affected_rows() == 1))
		{
			$configs = ZMC_Mysql::getAllOneValue("SELECT configuration_name FROM configurations WHERE owner_id = $id", 'Unable to obtain backup set names for user id $id.');
			$configIds = ZMC_Mysql::getAllOneValue("SELECT configuration_id FROM configurations WHERE owner_id = $id", 'Counting users backup sets failed.');
			if (!empty($configIds))
			{
				$resource = ZMC_Mysql::query("UPDATE configurations SET owner_id=$_SESSION[user_id] WHERE configuration_id IN (" . implode(', ', $configIds) . ")",
					'Update of backup set owner query failed.');
				if ($count = mysql_affected_rows())
				{
					mysql_free_result($resource);
					$configs = array_map(array('ZMC', 'escape'), $configs);
					$pm->addMessage("$username's $count backup set(s) are now owned by the 'admin' account: " . implode(', ', $configs));
				}
			}
			
			return true;
		}
		else 
			$pm->addError('Deletion of "' . self::$users[$id]['user'] . '"failed.'); 

		return false;
	}
}
ZMC_User::init();
