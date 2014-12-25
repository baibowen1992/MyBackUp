<?













class ZMC_Registry_Message extends ZMC_Registry
{
	public function __construct($array = array())
	{
		if (!is_array($array) && !($array instanceof ArrayObject))
		{
			error_log(ZMC_Error::backtrace(-10));
			throw new ZMC_Exception("The Message class only accepts initial values packaged in an array or an array object. A ".gettype($array)."  was passed in instead.");
		}
		parent::__construct($array);
		$this->user_name = empty($array['user_name']) ? $_SESSION['user'] : $array['user_name'];
		$this->user_id = empty($array['user_id']) ? $_SESSION['user_id'] : $array['user_id'];
		$this->facility = empty($array['facility']) ? 'ZMC' : $array['facility'];
		$this->message_type = empty($array['message_type']) ? 'symfony' : $array['message_type'];
		$this->message_id = empty($array['message_id']) ? uniqid() : $array['message_id'];
	}
	
	public function getId()
	{
		return $this->message_id;
	}

	public function getType()
	{
		return $this->message_type;
	}

	public function toJSON()
	{
		$this->user_id = $_SESSION['user_id'];
		$this->username = $_SESSION['user'];
		return json_encode($this);
	}
}
