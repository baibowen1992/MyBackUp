<?













class ZMC_Paginator_Reset 
{
	public static function reset($col, $reverse = true, $sortKey = 'sort')
	{
		$_GET[$sortKey] = $col; 
		$_GET['dir'] = ($reverse ? 1:0); 
		if (empty($_REQUEST['goto_page_' . $sortKey]))
			$_REQUEST['goto_page_' . $sortKey] = 1; 
	}

	public static function defaultSortOrder(array $cols, $sortKey = 'sort')
	{
		if (empty(ZMC::$userRegistry[$sortKey]))
			foreach($cols as $col)
				ZMC::$userRegistry[$sortKey][] = "$col:0";
	}
}
