<?













class ZMC_Disklists
{
	const TABLE_NAME = 'disklists';

	
	protected static $sets = array();

	
	protected static $mine = array();

	




	public static function start()
	{
		$pm->skip_backupset_start = true;
		foreach(ZMC_BackupSet::getMySets() as $name => $row)
		{
			$result[] = $row;
			ZMC_Mysql::replace('disklists', array(
					'id' => ZMC_Mysql::escape($name),
					'comments' => $row['configuration_notes'],
					'creation_date' => $row['creation_date'],
					'owner_id' => $row['owner_id'],
				),
				"Query failure while synchronizing backup set object lists with ZMC DB"
			);
		}

		$result = ZMC_Mysql::getAllRows('SELECT * FROM disklists ORDER BY id');
		if (!is_array($result) || !isset($_SESSION['user_id']))
			return;
		foreach($result as $row)
		{
			self::$sets[$row['id']] = $row;
			if (	ZMC_User::hasRole('Administrator')
				||	($row['owner_id'] == $_SESSION['user_id'])
				|| ZMC_User::hasRole('DisklistEditor'))
				self::$mine[$row['id']] = $row;
		}
	}

	public static function get($id)
	{
		if (!empty(self::$sets[$id]))
			return self::$sets[$id];

		return false;
	}

	public static function getColumn($id, $col)
	{
		if (!empty(self::$sets[$id]))
			return self::$sets[$id][$col];

		return false;
	}

	



	public static function getMine($id = null)
	{
		if ($id !== null)
		{
			if (!empty(self::$mine[$id]))
				return self::$sets[$id];
			return false;
		}
		return self::$mine;
	}

	





	public static function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('id', false);

		foreach(ZMC_BackupSet::getMySets() as $set)
			ZMC_Mysql::replace('disklists', array( 
				'id' => $set['configuration_name'],
				'creation_date' => $set['creation_date'],
				'owner_id' => $set['owner_id'],
				'live' => 1,
			));

		$paginator = new ZMC_Paginator(
			$pm,
			'	FROM disklists
				INNER JOIN users ON disklists.owner_id = users.user_id
				LEFT JOIN configurations ON configurations.configuration_name = disklists.id
				LEFT JOIN (SELECT disklists.id, count(*) as objects FROM disklists INNER JOIN dles ON disklists.id = dles.disklist GROUP BY disklists.id) as counts ON disklists.id = counts.id
			',
			$pm->cols = array(
				'id' => 'disklists.id',
				'creation_date' => 'disklists.creation_date',
				'objects',
				'live' => 'configuration_id',
				'user', 'comments'
			)
		);
		$paginator->createColUrls($pm);
		
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
	}

	



	public static function count()
	{
		return count(self::$mine);
	}

	





	public static function create(ZMC_Registry_MessageBox $pm, $name, $comments = null, $ownerId = null)
	{
		if (!empty(self::$sets[$name]))
			return $pm->addError('This backup set name already exists.');

		if (!ZMC_BackupSet::isValidName($pm, $name))
			throw new ZMC_Exception("Can not add object list.  Invalid name: '$name'");

		if ($ownerId === null)
			$ownerId = $_SESSION['user_id'];

		$ownerId = intval($ownerId);
		if (empty($ownerId))
			$ownerId = $_SESSION['user_id'];

		$result = ZMC_Mysql::insert('disklists', array(
				'id' => ZMC_Mysql::escape($name),
				'comments' => $comments,
				'creation_date' => ZMC::humanDate(true),
				'owner_id' => $ownerId,
			),
			"Query failure while adding an object list."
		);

		self::$mine[$name] = self::$sets[$name] = ZMC_Mysql::getOneRow("SELECT * FROM disklists WHERE id='$name'", 'DB failure');
		ZMC::auditLog($msg = "Created disklist '$name'");
		ZMC_Paginator_Reset::reset('creation_date'); 
		$pm->addMessage($msg);
	}

	






	public static function duplicate(ZMC_Registry_MessageBox $pm, $id, $newName)
	{
		$old = self::get($id);
		$oldName = $old['configuration_name'];
		if (self::getId($newName))
			return $pm->addError("The backup set '$newName' already exists.");

		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => "/Device-Binding/duplicate/$oldName",
				'data' => array(
					'commit_comment' => 'duplicate backup set config',
					'name' => $newName,
					'type' => true,
					'level' => true,
				),
			));
			$status = array();
			$pm->merge($result);
			self::create($pm, $newName, $old['comments']);
		}
		catch (Exception $e)
		{
			$pm->addError("An unexpected problem occurred while trying to duplicate the list '$oldName'. $e");
			ZMC::debugLog(__FILE__ . __LINE__ . " backup set duplicate exception: $e");
		}
	}

	





	public static function rm(ZMC_Registry_MessageBox $pm, $id)
	{
		if (false === self::getMine($id))
			return $pm->addError('Only backup set owner or user with admin role can delete.');
		ZMC::quit($id);
		if (ZMC_Mysql::delete('disklists', array('id' => $id)))
		{
			unset(self::$mine[$id]);
			unset(self::$sets[$id]);
			ZMC::auditLog("Deleted object list '$id'");
			return true;
		}
		return false;
	}

	







	public static function update(ZMC_Registry_MessageBox $pm, $name, $comments, $ownerId = null)
	{
		if (!ZMC_BackupSet::isValidName($pm, $name))
			throw new ZMC_Exception("Can not add object list.  Invalid name: '$name'");
		$ownerId = intval($ownerId);
		if (empty($ownerId))
			$ownerId = 1; 

		if (false !== ZMC_Mysql::update('disklists',
			array('id' => $name, 'comments' => $comments, 'owner_id' => $ownerId, 'live' => (ZMC_BackupSet::getByName($name) ? 1 : 0)),
			"id='" . ZMC_Mysql::escape($name) . "'"))
		{
			$pm->addMessage($msg = 'Object list updated.');
			ZMC::auditLog($msg, 0, null, ZMC_Error::NOTICE);
			return true;
		}

		$pm->addError($msg = "Can not find and update backup set '$name'");
		ZMC::auditLog($msg);
		return false;
	}

	public static function getDiskList()
	{
		
		return ZMC_Yasumi::operation($pm, array('pathInfo' => '/amadmin/disklist/' . self::getName()));
	}
}
