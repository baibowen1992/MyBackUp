<?













class ZMC_X_Cloud_Bucket extends ZMC_X_Cloud
{
public function getPaginator(ZMC_Registry_MessageBox $pm)
{
	$pm->disable_button_bar = false;
	if (empty($pm->s3Objects))
	{
		if (empty($_POST['selected_ids']))
			return;

		reset($_POST['selected_ids']);
		$pm->selected = key($_POST['selected_ids']);
		$pm->setPrefix('ZMC Cloud Device ' . $pm->selected);
		$cloud = ZMC_A3::createSingleton($pm, $this->device);
		$pm->s3Objects = $cloud->listBucket($pm->selected);
	}
	if (empty($pm->s3Objects))
		return;

	if (empty(ZMC::$userRegistry['sort']))
		ZMC_Paginator_Reset::reset('CreationDate', true);

	$flattened =& ZMC::flattenArrays($pm->s3Objects);
	$paginator = new ZMC_Paginator_Array($pm,
		$flattened,
		$pm->cols = array_keys(current($pm->s3Objects)),
		'clouds',
		100
	);
	ZMC_Paginator_Reset::reset('CreationDate', true);
	$paginator->createColUrls($pm);
	
	$pm->rows = $paginator->get();
	$pm->goto = $paginator->footer($pm->url);
}

protected function runState(ZMC_Registry_MessageBox $pm, $state)
{
	$update = false;
	if ($state !== null)
		$pm->state = $state;

	switch($pm->state)
	{
		case 'Get':
			try
			{
				if (ZMC::$registry->dev_only)
				{
					if (empty($this->device)) ZMC::quit(array($_REQUEST,$this,$pm));
					if (empty($this->buckets)) ZMC::quit(array($_REQUEST,$this,$pm));
				}
				if (empty($this->device)) throw new ZMC_Exception("Missing device.");
				if (empty($this->buckets)) throw new ZMC_Exception("Missing buckets.");
				$this->createS3($pm, __FUNCTION__ . ' - Get object list', $this->buckets[$_POST['bucket']]['endpoint']);
				reset($_POST['selected_ids']);
				$objectName = key($_POST['selected_ids']);
				$tmpFile = ZMC::$registry->cnf->zmc_tmp_path . $objectName;
				if (empty($_REQUEST['display']))
				{
					$result = $this->s3->get_object_url($_POST['bucket'], $objectName, time() + 3600, $this->opt);
					if (is_string($result) && strlen($result))
						$pm->addEscapedMessage("<a href='" . urldecode($result) . "' target='_blank'>Download $objectName<a/>");
					else
						$pm->addWarnError("Unexpected result received from cloud: " . print_r($result, true));
				}
				else
				{
					$result = $this->s3->get_object($_POST['bucket'], $objectName, array_merge(array('fileDownload' => $tmpFile), $this->opt));
					if (is_string($result) && strlen($result))
						$pm->object = file_get_contents($tmpFile);
					else
						$pm->addWarnError("Unexpected result received from cloud: " . print_r($result, true));
				}
			}
			catch(Exception $e)
			{
				$pm->addInternal("Unable to read contents of cloud device (#".__LINE__."): $e");
			}
			return true;

		case 'Delete':
			$used = array();
			$pm->confirm_template = 'ConfirmationWindow';
			$pm->addWarning('There is no undo.');
			$pm->prompt ='Are you sure you want to DELETE the Cloud Buckets(s) and all their contents?<br /><ul>'
				. '<li>'
				. implode("\n<li>", array_keys(ZMC::$userRegistry['selected_ids']))
				. "\n</ul>\n";
			$pm->confirm_action = 'DeleteConfirm';
			$pm->url = "?device=" . $this->device['id'];
			$pm->yes = 'Delete';
			$pm->no = 'Cancel';
			break;

		case 'DeleteConfirm':
			if (!isset($_POST['ConfirmationYes']))
				return $pm->addWarning("Deletion cancelled.");
			if (empty(ZMC::$userRegistry['selected_ids']))
				ZMC::quit(array($_REQUEST, ZMC::$userRegistry));
			reset(ZMC::$userRegistry['selected_ids']);
			$pm->selected = key(ZMC::$userRegistry['selected_ids']);
			$cloud = ZMC_A3::createSingleton($pm, $this->device);
			return $cloud->deleteBuckets(ZMC::$userRegistry['selected_ids'], false);

		case 'Cancel':
			if ($pm->state === 'Cancel') 
				$pm->addWarning("编辑/新增  取消.");
		case 'Refresh':
		case 'Refresh Table':
		case '': 
		default:
	}
}
}
