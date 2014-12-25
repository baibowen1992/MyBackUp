<?













class ZMC_YasumiService_Device extends ZMC_YasumiService
{
	public function delete($deviceName)
	{
		$message = new ZMC_Registry_Message(null, null, 'ZMC', $this->forUserName, $this->forUserId);
		$message->commit_comment = "Delete of device for ZMC";
		return $this->doPost("/Device-Profile/delete?name=$deviceName", $message->toJSON());
	}

	public function listAllBoundConfigurations()
	{
		$message = $this->messageRead('/conf/list_all');
		return $message->profiles2configs;
	}

	public function read($deviceName)
	{
		return $this->messageRead("/Device-Profile/read/$deviceName");
	}

	public function create($name, $device)
	{
		return $this->doWrite("create", $name, $device);
	}

	public function merge($name, $device)
	{
		return $this->doWrite("merge", $name, $device);
	}

	public function replace($name, $device)
	{
		return $this->doWrite("replace", $name, $device);
	}

	protected function doWrite($op, $name, $device)
	{
		$message = new ZMC_Registry_Message(null, null, 'ZMC', $this->forUserName, $this->forUserId);
		$message->commit_comment = "Write of device for ZMC";
		$message->name = $name;
		$message->type = $device['_key_name'];
		$message->device_profile_list = new ZMC_Registry(array($name => $device));
		return $this->doPost("/Device-Profile/$op/$name", $message->toJSON());
	}

	public function defaultProfile($type)
	{
		$message = $this->messageRead("/Device-Profile/defaults?type=$type");
throw new ZMC_Exception(__FILE__ . __LINE__ . print_r($message, true));
		return $message->defaults;
	}

	

	public function bind($deviceName, $configurationName, $level)
	{
		return null;
	}

	public function listAll()
	{
		$message = $this->messageRead('/Device-Profile/list');
		foreach($message->device_profile_list as $name => & $value) $value = array(
			'name' => $name,
			'_key_name' => $value,
			'enabled' => $message['licenses']['Licensed'][$value] > 0
		);
		return $message->device_profile_list;
	}

	public function readAll($type = null)
	{
		static $message = null; 

		if ($message === null)
		{
			$message = $this->messageRead('/Device-Profile/read');
			foreach($message->device_profile_list as $name => &$definition)
				$definition['name'] = $name;
		}
		return $message->device_profile_list;
	}
}
