<?














class ZMC_X_Cloud extends ZMC_Form
{
protected $s3 = null;

public static function run(ZMC_Registry_MessageBox $pm)
{
	ZMC_HeaderFooter::$instance->header($pm, 'Admin', 'ZMC - Cloud Management', 'devices', '<base href="' . ZMC::getUrl() . '/' . get_called_class() . '">');
	$pm->url = '/' . get_called_class();
	if (!empty($pm->selected_ids))
	{
		reset($pm->selected_ids);
		$selected = key($pm->selected_ids);
	}
	ZMC_BackupSet::start($pm);
	$pm->zmc_device_name = (empty($_REQUEST) ? $selected : (empty($_REQUEST['device']) ? $_SESSION['selected_device'] : $_REQUEST['device']));
	if (empty($pm->zmc_device_name))
		throw new ZMC_Exception("No Cloud device selected. Can not continue.");
	$_SESSION['selected_device'] = $pm->zmc_device_name;
	ZMC_HeaderFooter::$instance->addYui('zmc-utils', array('dom', 'event', 'connection'));
	ZMC_HeaderFooter::$instance->addYui('zmc-messagebox', array('dom', 'event', 'connection'));
	try
	{
		$cloudsPage = new static($pm);
		$cloudsPage->device = ZMC_Admin_Devices::get($pm, $pm->zmc_device_name);
		if ('type_cloud' !== $cloudsPage->device['dev_meta']['device_type']) 
			return ZMC::redirectPage('ZMC_Backup_Media', $pm);
		$result = $cloudsPage->runState($pm, (empty($_REQUEST['action']) ? '' : $_REQUEST['action']));
		if ($result !== true)
			$cloudsPage->getPaginator($pm);
	}
	catch(Exception $e)
	{ $pm->addError($e->getMessage()); }
	return 'XClouds';
}

protected function runState(ZMC_Registry_MessageBox $pm, $state)
{
	if ($state !== null)
		$pm->state = $state;

	switch($pm->state)
	{
		case 'List':
		default:
			if (empty($this->device))
			{
				$pm->addError('Select a ZMC cloud device on Admin|devices page first.');
				return true;
			}
	}
}

public function getPaginator(ZMC_Registry_MessageBox $pm)
{
	$buckets =& $this->formatBucketList($pm);
	$pm->total_buckets = 0;
	if (empty($buckets))
		return;

	$pm->total_buckets = count($buckets);
	if (empty($_REQUEST['unhide']))
		foreach(array_keys($buckets) as $key)
			if ($buckets[$key]['ZMC'] === 'No')
				unset($buckets[$key]);

	if (empty($buckets))
		return;

	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('config_name', true);

	$flattened =& ZMC::flattenArrays($buckets);
	$cols = array_flip(array_keys(current($buckets)));
	unset($cols['config_name']);
	$pm->cols = array_flip($cols);
	array_unshift($pm->cols, 'config_name');
	$paginator = new ZMC_Paginator_Array($pm,
		$flattened,
		$pm->cols,
		'clouds',
		100
	);
	$paginator->createColUrls($pm);
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
}

protected function &formatBucketList($pm)
{
	$buckets = null;
	try
	{
		$cloud = ZMC_A3::createSingleton($pm, $this->device);
		if (!empty($pm->selected))
			$pm->setPrefix('ZMC Cloud Device ' . $pm->selected);

		if (empty($this->buckets))
			$this->buckets = $cloud->getBucketList();
		$pm->zmc_buckets = $pm->zmc_buckets_legacy = 0;
		$accessKey = strtolower(preg_replace('/[^-a-zA-Z0-9]+/', '-', $cloud->getUsername()));
		foreach($this->buckets as &$bucket)
		{
			$array = $bucket;
			$name = $bucket['Name'];
			if (!empty($bucket['CreationDate']))
			{
				$cd = ZMC::humanDate(strtotime($bucket['CreationDate']));
				$timestamp = strtotime($bucket['CreationDate']);
			}
			$zmc = 'No';
			if (!strncmp($name, "zmc-", 4)) 
			{
				$cn = substr($name, 1+strpos($name, '-', 6));
				$zmc = 'Yes';
				$pm->zmc_buckets++;
			}
			elseif (($pos = strrpos($name, '-')) && (substr($name, $pos+1) === $accessKey))
			{
				$cn = substr($name, 0, $pos);
				$zmc = 'Legacy';
				$pm->zmc_buckets_legacy++;
			}
			else
				$cn = '-';

			$array['ZMC'] = $zmc;
			$array['bucket_name'] = $name;
			unset($array['Name']);
			$array['config_name'] = $cn;
			if (!empty($bucket['Region']))
				$array['Region'] = $bucket['Region'];
			if (!empty($cd))
			{
				$array['CreationDate'] = $cd;
				$array['timestamp2locale'] = $timestamp;
			}
			ksort($array);
			$buckets[$bucket['Name']] = $array;
		}
		if (count($buckets) > 99)
			$pm->addWarning("This S3 account has used the maximum number of buckets. Each ZMC backup set requires a unique bucket.  No new ZMC backup sets can use this S3 account.");
	}
	catch(Exception $e)
	{	$pm->addInternal("Unable to read contents of cloud device (#".__LINE__."): $e"); }
	return $buckets;
}

}
