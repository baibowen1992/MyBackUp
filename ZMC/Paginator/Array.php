<?

















class ZMC_Paginator_Array extends ZMC_Paginator
{
	




	public function countPages()
	{
		if ($this->found <= $this->rowsPerPage)
			return 1;

		return  (integer)ceil($this->found / $this->rowsPerPage);
	}

	




	public function &get($offset = null)
	{
		$data = array();
		if (empty($this->where)) 
			return $data;

		$orderBy = '';
		foreach($this->where as $id => &$row)
		{
			if (isset($data[$id]))
				throw new ZMC_Exception('Unexpected failure preparing table. ' . (ZMC::$registry->debug ? serialize($this->where) : ''));
			$data[$id] =& $row;
		}
		unset($row);
		$remap = $cols = array();
		foreach($data as $rowNum => &$row)
			foreach($this->cols as $colIndex => $sortKeyName)
			{
				if (!is_string($colIndex))
					$idx = $sortKeyName;
				else
				{
					$idx = $colIndex;
					if ($sortKeyName === null)
						continue;
					if ($sortKeyName !== true)
					{
						$row[$colIndex] =& $row[$sortKeyName];
						unset($row[$sortKeyName]);
					}
				}

				if (isset($row[$idx]))
					if (is_array($row[$idx]))
						$cols[$idx][$rowNum] = empty($this->sortOrder[$idx]) ? min(array_keys($row[$idx])) : max(array_keys($row[$idx]));
					else
						$cols[$idx][$rowNum] = $row[$idx];
				else
					$cols[$idx][$rowNum] = '';
			}

		$parms = array();
		$array_multisort = '';
		$i = 0;
		foreach($this->sortOrder as $col => $direction)
		{
			if (isset($cols[$col]))
			{
				$parms[] = $cols[$col];
				$parms[] = ($direction ? SORT_DESC : SORT_ASC); 
				$array_multisort .= '$parms[' . $i++ . '], $parms[' . $i++ . '], ';
			}
		}

		if (empty($parms))
		{
			$parms = array(current($cols), SORT_ASC); 
			$array_multisort = '$parms[0], $parms[1], ';
			$i = 2;
		}

		
		$parms[] =& $data;
		$array_multisort .= '$parms[' . $i . ']';
		if (false === eval("return array_multisort($array_multisort);"))
			throw new ZMC_Exception('Unexpected failure sorting table');
		if ($offset === null)
			$offset = $this->offset;
		$offset = floor($offset / $this->rowsPerPage) * $this->rowsPerPage; 
		$nrows = count($data);
		
		$lastOffset = (($fragment = $nrows % $this->rowsPerPage) ? $nrows - $fragment : $nrows - $this->rowsPerPage);
		$offset = min($offset, $lastOffset);
		$this->found = count($this->where);
		$result = array_slice($data, $offset, $this->rowsPerPage);
		$this->row_count = count($result);
		return $result;
	}
}
