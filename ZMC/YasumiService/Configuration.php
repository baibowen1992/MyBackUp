<?













class ZMC_YasumiService_Configuration extends ZMC_YasumiService
{
	public function read($what = "amanda.conf")
	{
		$message = $this->messageRead("conf/read/{$this->amandaConf}?what=$what");
		return new ZMC_Registry($message->conf);
	}

	public function amgetindex($op)
	{
		throw new ZMC_Exception(__FILE__ . __FUNCTION__ . ' is this used anywhere?');
		$message = $this->messageRead("/job_amgetindex/$op/" . $this->amandaConf);
		return $message->status;
	}

	public function index($hostname, $dle, $date, $time, $msg)
	{
		$result = $this->doRequest('/job_amgetindex/get/' . $this->amandaConf, array( 
			'date' => $date,
			'time' => $time,
			'dle' => $dle,
			'zmc_message' => $msg,
			'host_name' => $hostname,
			'callback_location' => $this->getCallbackUrl("amGetIndexResults"),
			'mode' => 'asynchronous',
			'callback_username' => $this->username,
			'callback_password' => $this->password));
ZMC::debugLog(__CLASS__ . $result);
		return $result;
	}

	public function write($what = "amanda.conf", $where = "amanda.conf")
	{
		$message = new ZMC_Registry_Message(null, null, 'ZMC', $this->forUserName, $this->forUserId);
		$message->conf = $this->amandaConf;
		$message->commit_comment = "Write for ZMC";
		return $this->doPost("/migrate/read_write/" . $this->amandaConf, array('what' => $what, 'where' => $where), $message->toJSON());
	}
}
