<?


















class ZMC_Config_Ags_Solaris extends ZMC_Config_Ags
{
	public function __construct($array = null)
	{
		
		parent::__construct(array(
			'mt_path'		=> '/usr/bin/mt',
			'tapedev_globs' => array('/dev/rmt/*' => '/^\d+n$/'), 
		));

		if (($array !== null) && count($array))
			$this->merge($array);
	}
}
