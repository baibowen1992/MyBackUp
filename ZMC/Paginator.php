<?













class ZMC_Paginator 
{
	






	protected $paginator;
	protected $offset;
	protected $sortKey;
	protected $sortOrder; 
	protected $found = 0;
	public $pages = 0; 

	
	protected $rowsPerPage;

	
	public $columns;

	
	protected $cols;

	
	protected $select;

	
	protected $where;

	const SORT_KEY = 'sort';

	
	function __construct(ZMC_Registry_MessageBox $pm, $where, array $cols, $sortKey = self::SORT_KEY, $rowsPerPage = 0)
	{
		$this->where = $where;
		$this->cols = $cols = array_filter($cols); 
		$this->sortKey = $sortKey;
		$this->rowsPerPage = intval($rowsPerPage);
		if (empty($this->rowsPerPage))
			$this->rowsPerPage = 10;

		$key = $pm->tombstone . ':' . $pm->subnav . ':rows:' . $sortKey;
		if (isset($_POST['rows_per_page_' . $sortKey]) && intval($_POST['rows_per_page_' . $sortKey] >= 5))
		{
			ZMC::$userRegistry[$key] = $this->rowsPerPage = intval($_POST['rows_per_page_' . $sortKey]);
			if ($_POST['rows_per_page_orig_' . $sortKey] != $_POST['rows_per_page_' . $sortKey])
				$_REQUEST['goto_page_' . $sortKey] = 1;
		}
		elseif (isset(ZMC::$userRegistry[$key]))
			$this->rowsPerPage = ZMC::$userRegistry[$key];

		$this->columns = array();
		$this->select = '';
		foreach($cols as $as => $sql)
			if (is_string($as))
			{
				if (!empty($sql))
					$this->select .= "$sql AS $as, ";
				$this->columns[] = $as;
			}
			else
			{
				$this->select .= "$sql, ";
				$this->columns[] = $sql;
			}

		$this->select{strlen($this->select) -2} = ' ';
		if (!isset(ZMC::$userRegistry[$sortKey]) || !is_array(ZMC::$userRegistry[$sortKey]))
		{
			ZMC::$userRegistry[$sortKey] = array();
			ZMC::$userRegistry['offset_' . $sortKey] = 0;
		}
		$this->paginator =& ZMC::$userRegistry[$sortKey];
		$this->offset =& ZMC::$userRegistry['offset_' . $sortKey];

		if (isset($_REQUEST['goto_page_' . $sortKey]))
			$this->offset = (intval($_REQUEST['goto_page_' . $sortKey]) - 1) * $this->rowsPerPage;
		elseif (isset($_REQUEST['offset_' . $sortKey]))
			$this->offset = intval($_REQUEST['offset_' . $sortKey]);
		
	}

	



	public function countPages()
	{
		return  $this->pages = (integer)ceil($this->found / $this->rowsPerPage);
	}

	public function found()
	{	return $this->found; }

	





	public function createColUrls(ZMC_Registry_MessageBox $pm)
	{
		if (isset($_GET[$this->sortKey]) && ZMC::isalnum_($_GET[$this->sortKey]))
		{	
			foreach(array_keys($this->paginator) as $idx)
				if ($this->paginator[$idx] === $_GET[$this->sortKey] . ':0' || $this->paginator[$idx] === $_GET[$this->sortKey] . ':1')
					unset($this->paginator[$idx]); 

			
			$sortSpec = $_GET[$this->sortKey] . ':' . intval($_GET['dir']);
			if (count($this->paginator) > 2)
				$this->paginator = array(array_shift($this->paginator), array_shift($this->paginator));
			array_unshift($this->paginator, $sortSpec);
		}

		if (empty($this->paginator))
			array_push($this->paginator, current($this->columns) . ':0');

		$this->sortOrder = array();
		foreach(array_keys($this->paginator) as $idx)
		{
			list($sortKey, $direction) = explode(':', $this->paginator[$idx]);
			$this->sortOrder[$sortKey] = $direction; 
			if ($idx === 0)
			{
				$pm->sortImageIdx = $sortKey;
				$pm->sortImage = "sort-" . ($direction ? 'down' : 'up') . "-arrow.png";
				$pm->sortImageUrl = '<img src="/images/global/' . $pm->sortImage . '">';
			}
		}

		$i=0;
		$pm->colUrls = array();
		$pm->columns = $this->columns;
		foreach($this->columns as $col)
			$pm->colUrls[$col] = '?' . $this->sortKey . "=$col&amp;dir=" . (empty($this->sortOrder[$col]) ? '1' : '0');

	}

	



	public function &get($offset = null)
	{
		if (!is_string($this->where))
			if (ZMC::$registry->debug)
				throw new ZMC_Exception(__CLASS__ . ' constructor requires a SQL expression for $where (are you trying to use ZMC_Paginator_Array?)');
			else
				ZMC::headerRedirect(ZMC::$registry->bomb_url, __FILE__, __LINE__);

		$orderBy = '';
		if (empty($offset))
		{
			if (empty($this->offset))
				$offset = 0;
			else
				$offset = $this->offset;
		}

		foreach($this->paginator as $sortKey)
		{
			list($col, $direction) = explode(':', $sortKey);
			if (ZMC::isalnum_($col))
				$orderBy .= $col . ($direction ? ' DESC,' : ' ASC,');
		}
		$orderBy = rtrim($orderBy, ',');
		if (empty($orderBy))
			$orderBy = current($this->columns);
		$this->found = ZMC_Mysql::getOneValue('SELECT COUNT(*) ' . $this->where, 'Unable to count table "' . $this->where . '"'); 
		$sql = "SELECT " . $this->select . ' ' . $this->where . " ORDER BY $orderBy LIMIT $offset, " . $this->rowsPerPage;
		try
		{ $result =& ZMC_Mysql::getAllRowsMap($sql); }
		catch(ZMC_Mysql_Exception $e)
		{
			$sql = "SELECT " . $this->select . ' ' . $this->where . " LIMIT $offset, " . $this->rowsPerPage;
			$result =& ZMC_Mysql::getAllRowsMap($sql);
		}
		$this->row_count = (is_array($result) ? count($result) : 0);
		return $result;
	}

	public function shortFooter($url)
	{
		return $this->footer($url, true);
	}

	



	public function footer($url, $short = false)
	{
		$pm = new ZMC_Registry(array(
			'url' => $url,
			'row_count' => $this->row_count,
			'page_count' => $this->countPages(),
			'found_count' => $this->found,
			'offset' => $this->offset,
			'rows_per_page' => $this->rowsPerPage,
			'short' => $short,
			'sortKey' => $this->sortKey,
		));
		$pm->currentPage = floor($pm->offset / $this->rowsPerPage) + 1;
		
		if ($pm->page_count > 1 || $this->found > 5)
		{
			ob_start();
			ZMC_Loader::renderTemplate('FooterPaginator', $pm);
			return ob_get_clean();
		}
	}
}
