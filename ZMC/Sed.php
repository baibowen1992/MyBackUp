<?



















class ZMC_Sed
{
	private $pm;
	private $fn;
	private $fp;
	private $wrote;

	public function __construct($pm, $fn)
	{
		$this->pm = $pm;
		if ($this->fp = fopen($this->fn = $fn, 'c'))
			ftruncate($this->fp, 0);
		return $this->fp;
	}

	public static function preg_modify($pm, $fn, $patterns, $replacements)
	{
		if (false === ($this->fp = fopen($this->fn = $fn, 'c')))
			return false;
		$content = stream_get_contents($this->fp);
		$newfn = $fn . time();
	    if (null === ($replaced = preg_replace($patterns, $replacements, $content)))
			$pm->addError("Substitution pattern failed ($patterns) on file $fn.");
		elseif (strlen($replaced) !== file_put_contents($newfn, $replaced))
			$pm->addError("Writing $fn modified by substitution pattern ($patterns) failed.");
		elseif (!rename($newfn, $fn))
			$pm->addError("Replacing $fn modified by substitution pattern ($patterns) failed.");
		fclose($this->fn);
	}

	public function close($content)
	{
		if (!is_resource($this->fp))
		{
			$this->pm->addError('Can not write to ' . $this->fn . ', because it was not opened successfully.');
			return false;
		}
		$this->wrote = fwrite($this->fp, $content);
		fclose($this->fp);
		if ($this->wrote !== strlen($content))
		{
			$this->pm->addInternal("Failed when updating file: " . $this->fn);
			unlink($this->fn);
			return false;
		}
		return $this->wrote;
	}
}
