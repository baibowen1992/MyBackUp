<?














class ZMC_Registry extends ArrayObject
{
	
	protected static $_props = array(
		'promote' => false, 
		'remove' => null 
	);

	


	public function __construct($array = null, $ignored = null)
	{
		parent::__construct($array === null ? array() : $array, ArrayObject::ARRAY_AS_PROPS);
	}

	public function arrayKeys()
	{
		$keys = array();
		foreach($this as $key => &$ignored)
			$keys[] = $key;
		return $keys;
	}

	public function displayKeys()
	{
		echo '<pre><b>';
		foreach($this as $key => $value)
			if (is_object($value))
				echo "$key = ", get_class($value), "\n";
			elseif (is_array($value))
				if (empty($value))
					echo "$key = Empty Array\n";
				elseif (count($value) < 8)
					echo "$key = Array(", implode(', ', array_keys($value)), ")\n";
				else
					echo "$key = Array(", count($value), ")\n";
			else
				echo "$key = ", var_dump($value);
		
		echo '</b></pre>';
	}

	




















	public function merge($array, $props = null)
	{
		if ($props === null)
			$props = self::$_props;

		if (!is_array($array) && !($array instanceof ArrayObject))
			throw new ZMC_Exception("'$array' is not an array");

		if (is_object($array) && (spl_object_hash($this) === spl_object_hash($array)))
			throw new ZMC_Exception("cannot merge an object with itself");

		foreach ($array as $key => $value)
		{
			if (array_key_exists('remove', $props) && $value === $props['remove'])
			{

				$this->offsetExists($key) && $this->offsetUnset($key);
				continue;
			}

			if (!is_string($key))
			{
				$this->append($value);
				continue;
			}

			if (!$this->offsetExists($key))
			{
				$this->offsetSet($key, $value);
				continue;
			}

			if (!$props['promote'])
			{
				if (is_array($this[$key]))
				{
					if (is_array($value))
						ZMC::merge($this[$key], $value);
					elseif ($value instanceof ArrayObject)
						ZMC::merge($this[$key], (array)$value);
					else
						$this[$key] = $value;
				}
				elseif (($this[$key]) instanceof ZMC_Registry)
					$this[$key]->merge($value, $props);
				elseif (($this[$key]) instanceof ArrayObject)
					throw new ZMC_Exception('Auto-promotion not enabled, so cannot apply merge to ArrayObject');
				else
					$this[$key] = $value;

				continue;
			}

			if ($this[$key] instanceof ZMC_Registry)
			{
				$this[$key]->merge($value, $props);
				continue;
			}
			if (is_array($this[$key]) || ($this[$key] instanceof ArrayObject))
			{
				$this[$key] = new self($this[$key]);
				$this[$key]->merge($value, $props);
				continue;
			}

			$this[$key] = $value;
		}

		return $this;
	}

	






	public function get($key, $default = null, $namespace = null)
	{
		if (empty($namespace))
			if ($this->offsetExists($key))
				return $this->offsetGet($key);
			else
				return $default;
		else
			if ($this->offsetExists($namespace))
				if (is_array($this[$namespace]))
					if (array_key_exists($key, $this[$namespace]))
						return $this[$namespace][$key];
					else
						return $default;
				elseif ($this[$namespace] instanceof ArrayObject)
					if ($this[$namespace]->offsetExists($key))
						return $this[$namespace][$key];
					else
						return $default;
				else
					throw new ZMC_Exception("Unable to access namespace '$namespace'");
			else
				return $default;
	}

	public function setIf($index, $newval, $oldval = false)
	{
		if ($this->offsetExists($index) && ($this->offsetGet($index) === $oldval))
		{
			$this->offsetSet($index, $newval);
			return true;
		}
		return false;
	}

	



	public function keys()
	{
		$keys = null;
		foreach($this as $key => $ignored)
			$keys[] = $key;
		return $keys;
	}

	public function __clone()
	{
		foreach($this as $key => $value)
			if (is_object($value))
				$this[$key] = clone $value;
	}

	
	public function diff($subtract)
	{
		foreach ($subtract as $key => $value)
		{
			if ($this->offsetExists($key))
			{
				if (is_array($value))
				$this->offsetUnset($key);
			}
		}
	}

	public static function __set_state($array)
	{
		return new static($array);
	}
}
