<?














class ZMC_Exception_A3 extends ZMC_Exception
{
	protected $cloudCode = null;
    public function __construct($message = '', $code = 0, $file = null, $line = null, $depth = 0, $cloudCode = 0)
    {
		$this->cloudCode = $cloudCode;
		return parent::__construct($message, $code, $file, $line, $depth, $cloudCode);
	}

	public function getCloudCode()
	{	return $this->cloudCode; }

	public function toString($verbose = false)
	{	return $this->cloudCode  . ':' . parent::toString($verbose); }
}

class ZMC_A3 extends AmazonS3
{
public static $za3 = null;
private static $bucketCache = array();
public  static $nRequests = 0;
protected $bucketList = null;
protected $authType = 's3';
private $cacheFn;
private $cainfo;
private $opt = array();
private $pm;
private $usernameKey = 'S3_ACCESS_KEY';
public $use_ssl;
private $xHeaders = array();

public static function createSingleton($pm, &$device)
{
	if (self::$za3 === null)
		self::$za3 = new self($pm, $device);
	return self::$za3;
}

public function __construct(ZMC_Registry_MessageBox $pm, &$device)
{
	$this->pm = $pm;
	if (empty($device))
		throw new ZMC_Exception('Missing cloud device');
	$this->device = $device;
	$this->zmc_device_name = $device['id'];
	$props =& $device['device_property_list'];
	$this->use_ssl = ($props['S3_SSL'] === 'on');
	$this->path_style = false;
	if (!empty($props['S3_SERVICE_PATH']))
		$this->set_resource_prefix($props['S3_SERVICE_PATH']);
	
	$this->opt = array();
	$this->cainfo = ZMC::$registry->curlopt_cainfo;
	if (!empty($device['ssl_ca_cert']) && (file_exists($pemFilename = str_replace('.state', '.pem', $device['changerfile']))))
		$this->cainfo = $pemFilename;

	switch($this->cloud_type = $device['_key_name'])
	{
		case 'cloudena_cloud':
			
		case 'hp_cloud':
		case 'openstack_cloud':
			$this->create('S3_ACCESS_KEY', 'S3_SECRET_KEY', array('S3_HOST', 'S3_SERVICE_PATH', 'STORAGE_API', 'TENANT_NAME'));
			$this->authType = $props['STORAGE_API'];
			$this->opt['query_string'] = array('format' => 'json', 'limit' => 1000 );
			break;

		case 'google_cloud':
			if (empty($this->device['device_property_list']['S3_ACCESS_KEY']) && empty($this->google_client))
			{
				set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__) . '/google');
				require "google/apiClient.php";
				require "google/contrib/apiPlusService.php";
				$this->google_client = new apiClient();
				$this->google_client->setAccessType('offline');
				$this->google_client->setApprovalPrompt('auto');
				$this->google_plus = new apiPlusService($client);
				ZMC::quit('@TODO');
			}
		default:
			$this->create('S3_ACCESS_KEY', 'S3_SECRET_KEY');
			break;
	}

	$this->opt['curlopts'] = array(
		CURLOPT_CONNECTTIMEOUT	=> ZMC::$registry->proc_open_ultrashort_timeout,
		CURLOPT_TIMEOUT			=> ZMC::$registry->proc_open_ultrashort_timeout,
		CURLOPT_SSL_VERIFYPEER	=> ((empty($this->device['ssl_ca_cert_ignore']) || !$this->device['ssl_ca_cert_ignore']) ? 1:0),
		CURLOPT_CAINFO			=> $this->cainfo,
		CURLOPT_CAPATH			=> ZMC::$registry->curlopt_capath,
	);
	if (!empty($this->device['device_property_list']['S3_STORAGE_CLASS']))
		$this->opt['storage'] = (($this->device['device_property_list']['S3_STORAGE_CLASS'] === 'REDUCED_REDUNDANCY') ? AmazonS3::STORAGE_REDUCED : AmazonS3::STORAGE_STANDARD);
	if ($this->authType === 'SWIFT-2.0')
		$this->getSwiftAuthToken();
}

public function getXAuthToken()
{
	if (empty($this->xHeaders['X-Auth-Token']))
		return false;
	$parts = explode(' ', $this->xHeaders['X-Auth-Token']);
	return $parts[1];
}

private function create($user, $password, $requiredKeys = array())
{
	$this->usernameKey = $user;
	foreach(array_merge(array($user, $password), $requiredKeys) as $key)
		if (empty($this->device['device_property_list'][$key]))
			throw new ZMC_Exception($this->zmc_device_name . ": Invalid device (missing key $key): " . ZMC::dump($this->device, null, 'pretty'));

	parent::__construct(array(
		'key' => $this->device['device_property_list'][$user],
		'secret' => $this->device['device_property_list'][$password],
		'certificate_authority' => ZMC::$registry->curlopt_cainfo,
	));
}

public function setDefaultEndpoint()
{
	$this->setRegionInfo($this->regionInfo);
	$this->setEndPoint($this->regionInfo['endpoint']);
	return $this->regionInfo['endpoint'];
}

public function setRegionInfo(&$result, $regionCode = null)
{
	if ($regionCode === null)
		if (!empty($this->device['device_property_list']['S3_BUCKET_LOCATION']))
			$regionCode = $this->device['device_property_list']['S3_BUCKET_LOCATION'];
		elseif (isset(ZMC_Type_Where::$cloudRegions[$this->cloud_type]))
			$regionCode = '';
		elseif (!empty($this->device['device_property_list']['S3_HOST']))
			$regionCode = strtok($this->device['device_property_list']['S3_HOST'], ':');
		else
			throw new ZMC_Exception("Can not determine a default region code.");

	$result['Region'] = $result['endpoint'] = $result['location_constraint'] = $regionCode;
	if (isset(ZMC_Type_Where::$cloudRegions[$this->cloud_type]))
	{
		$region =& ZMC_Type_Where::$cloudRegions[$this->cloud_type];
		$loc = strtolower($regionCode);
		if (!isset($region[$loc]))
		{
			$this->pm->addWarning("Using default region and default endpoint for the cloud provider ($loc:$regionCode).");
			if (ZMC::$registry->dev_only) ZMC::quit($this);
			$loc = '';
		}
		if (empty($result['location_constraint']))
			$result['location_constraint'] = $region[$loc][0];
		$result['endpoint'] = $region[$loc][1];
		$result['Region'] = $region[$loc][2];
	}

	return $result['endpoint'];
}

protected function setEndpoint($endpoint)
{
	if (empty($endpoint)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	ZMC::debugLog(__FUNCTION__ . ": $endpoint");
	switch ($this->authType)
	{
		case 'SWIFT-2.0':
			$this->x_storage_url = $endpoint;
			$parts = parse_url($this->x_storage_url);
			$this->hostname = $parts['host'] . (empty($parts['port']) ? '': ":$parts[port]");
			$this->set_resource_prefix($parts['path']);
			
			break;

		case 's3':
			$this->set_region($endpoint);
			break;

		default:
			throw new ZMC_Exception('Unknown auth type: ' . $this->authType);
	}
}

public function useBucketEndpoint($bucket)
{
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	if ($this->bucketList === null) $this->getBucketList();
	if (empty($this->bucketList['buckets'][$bucket]))
	{
		$this->pm->addError("Bucket 找不到: $bucket (code #" . __LINE__ . ")");
		
		return false;
	}

	if (!isset($this->bucketList['buckets'][$bucket]['location_constraint']))
		throw new ZMC_Exception("Bucket/Container has no endpoint: " . ZMC::dump($this->bucketList['buckets'][$bucket], null, 'pretty'));

	$this->setRegionInfo($this->regionInfo, $this->bucketList['buckets'][$bucket]['location_constraint']);
	if ($this->bucketList['buckets'][$bucket]['endpoint'] !== $this->regionInfo['endpoint'])
		$this->pm->addWarnError('当前bucket的主机名是 ' . $this->bucketList['buckets'][$bucket]['endpoint'] . ', 但是我们使用的是 ' . $this->regionInfo['endpoint']);
	$this->setEndPoint($this->regionInfo['endpoint']);
	return $this->regionInfo;
}

public function createBucket()
{
	if ($this->bucketList === null) $this->getBucketList();
	$this->setDefaultEndpoint(); 
	$bucket = $this->device['changer']['changerdev'];
	$location_constraint = strtoupper($this->regionInfo['location_constraint']);
	$this->regionInfo['CreationDate'] = str_replace('+00:00', '.000Z', gmdate('c'));
	$this->regionInfo['Name'] = $bucket;

	if (empty($this->resource_prefix) && (!$this->validate_bucketname_create($bucket)))
		throw new ZMC_Exception('"' . $bucket . '" is not DNS-valid (i.e., <bucketname>.<endpoint>), and cannot be used as an Cloud bucket name.');

	$msg = "Create cloud bucket $bucket at $location_constraint: ";
	try
	{
		$acl = self::ACL_PRIVATE;
		if ($this->cloud_type === 's3_cloud')
			$this->create_bucket($bucket, $this->regionInfo['endpoint'], $acl, $this->getOpts());
		else
		{
			switch($this->cloud_type)
			{
	
				case 'iij_cloud':
					$opt['body'] = '<CreateBucketConfiguration xmlns="http://acs.iijgio.com/doc/2006-03-01/"><LocationConstraint>' .
						(empty($location_constraint) ? 'JP-WEST1' : $location_constraint)
						. '</LocationConstraint></CreateBucketConfiguration>';
					break;
	
				case 'google_cloud':
					$durable_reduced_avaibility_storage = (isset($this->device['private']['google_durable_reduced_avaibility_storage']) && $this->device['private']['google_durable_reduced_avaibility_storage'] == "on")? "<StorageClass>DURABLE_REDUCED_AVAILABILITY</StorageClass>" : "";
					$opt['body'] = '<'.'?xml version="1.0" encoding="UTF-8" ?'.'><CreateBucketConfiguration><LocationConstraint>'
						. (empty($location_constraint) ? 'US' : $location_constraint)
						. '</LocationConstraint>'.$durable_reduced_avaibility_storage.'</CreateBucketConfiguration>';
					break;
	
				case 's3_compatible_cloud':
					$opt['body'] = (empty($location_constraint) ? '' : '<'.'?xml version="1.0" encoding="UTF-8" ?'.'><CreateBucketConfiguration xmlns="http://s3.amazonaws.com/doc/' . $this->api_version . '/"><LocationConstraint>' . strtoupper($location_constraint) . '</LocationConstraint></CreateBucketConfiguration>');
					break;
	
				default:
			}
	
			$opt['verb'] = 'PUT';
			if ($this->authType !== 'SWIFT-2.0')
			{
				$opt['headers'] = array(
					'Content-Type' => 'application/xml',
					'x-amz-acl' => $acl
				);
			}

			$this->authenticate($bucket, $this->getOpts($opt));
		}
	}
	catch(Exception $e)
	{
		$this->pm->addWarnError("$msg failed: $e");
		return false;
	}
	$this->updateBucketListCache($bucket, $this->regionInfo);
	ZMC::auditLog("$msg OK; " . (ZMC::$registry->debug ?  print_r($this->result, true) : ''));
	return $this->regionInfo;
}

public function delete_bucket($bucket, $force = false, $opt = null)
{
	if ($this->bucketList === null) $this->getBucketList();
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	$this->useBucketEndpoint($bucket);
	$qs = $this->opt['query_string'];
	$this->opt['query_string'] = '';
	try { parent::delete_bucket($bucket, $force, $opts = $this->getOpts($opt)); }
	catch(Exception $e)
	{
	$this->opt['query_string'] = $qs;
		if ($this->return['body']['Code'] === 'BucketNotEmpty')
		{
			sleep(3); 
			try { parent::deleteBucket($bucket, false, $opts); } 
			catch(Exception $e)
			{
				$this->pm->addWarnError(($this->pm->deleted_objects ? '删除 ' . $this->pm->deleted_objects . ' 对象, before ' : '') . "删除bucket时发生未知错误.". ZMC::dump($this->return, null, 'pretty'));
				return false;
			}
		}
		$this->pm->addWarnError("Bucket删除失败: $e");
		return false;
	}
	$this->opt['query_string'] = $qs;
	
	$this->updateBucketListCache($bucket);
	return $this->return;
}

private function listBuckets($opt = null)
{
	try{ parent::list_buckets($this->getOpts($opt)); }
	catch(Exception $e)
	{
		$this->pm->addWarning("是用缓存数据, 因为云备份系统没有从对象存储系统收到可用连接. $e");
		return false;
	}

	if ($this->authType !== 'SWIFT-2.0')
	{
		if (empty($this->return['body']['Buckets']['Bucket']['Name']))
			$this->return['buckets'] = $this->return['body']['Buckets']['Bucket'];
		else
			$this->return['buckets'] = array(
				$this->return['body']['Buckets']['Bucket']['Name'] =>
				$this->return['body']['Buckets']['Bucket']
			);
		$this->return['body']['Buckets'] = null;
		return $this->return;
	}

	$this->return['buckets'] = array();
	
	
	foreach($this->return['body'] as $bucket)
		$this->return['buckets'][$bucket['name']] = array(
			'Name' => $bucket['name'],
			'bucket_size' => round($bucket['bytes'] / 1024 / 1024, 1) . ' MiB',
			'bucket_objects' => $bucket['count'],
			
			'location_constraint' => $this->regionInfo['location_constraint'],
			'endpoint' => $this->x_storage_url,
			
		);
	$this->return['body']['Buckets'] = null;
	return $this->return;
}

public function getUsername()
{	return $this->device['device_property_list'][$this->usernameKey]; }

public function getBucketList($endpoint = null)
{
	$this->cacheFn = 'Clouds-Buckets-' . $this->device['_key_name'] . '-' . $this->device['device_property_list'][$this->usernameKey];
	if (!ZMC::useCache($this->pm, (ZMC::$registry->dev_only ? __FILE__ : null), $this->cacheFn, false, ZMC::$registry->cache_cloud_list_of_buckets, false))
	{
		if ($endpoint)
			$this->setEndPoint($endpoint);
		else
			$this->setDefaultEndpoint();

		if ($this->bucketList = $this->listBuckets())
		{
			if ($this->authType === 'SWIFT-2.0')
				ZMC::array_move($this->bucketList['header'], $this->bucketList['pmheader'], array(
					'x-account-container-count' => 'account_container_count',
					'x-account-object-count' => 'account_object_count',
					'x-account-bytes-used' => 'account_bytes_used'));
			else
			{
				$oldBuckets = array();
				if ($haveCachedRegions = file_exists($this->cacheFn))
				{
					$oldCache = require $this->cacheFn;
					if (is_array($oldCache))
						$oldBuckets =& $oldCache['buckets'];
					else
						$haveCachedRegions = false;
				}
				$buckets = array();
				foreach($this->bucketList['buckets'] as &$rbucket)
				{
					if(!preg_match("/^zmc-(.*)+/", $rbucket['Name']))
						continue;
					$buckets[$rbucket['Name']] =& $rbucket;
					if ($haveCachedRegions && isset($oldBuckets[$rbucket['Name']]))
					{
						$rbucket['location_constraint']	= $oldBuckets[$rbucket['Name']]['location_constraint'];
						$rbucket['Region']		= $oldBuckets[$rbucket['Name']]['Region'];
						$rbucket['endpoint']	= $oldBuckets[$rbucket['Name']]['endpoint'];
						continue;
					}
					$region = $this->get_bucket_region($rbucket['Name']);
					$this->setRegionInfo($rbucket, $region);
				}
				$this->bucketList['buckets'] =& $buckets;
			}
			$this->updateBucketListCache();
		}
	}

	if (empty($this->bucketList) && file_exists($this->cacheFn))
		$this->bucketList = require $this->cacheFn;

	$this->pm->merge($this->bucketList['pmheader']);
	return $this->bucketList['buckets'];
}

protected function updateBucketListCache($bucket = null, $info = null) 
{
	if (ZMC::$registry->debug && !is_array($this->bucketList)) ZMC::quit();

	if (!empty($bucket))
		if (empty($info))
			unset($this->bucketList['buckets'][$bucket]);
		else
			$this->bucketList['buckets'][$bucket] = $info;

	if (false === file_put_contents($this->cacheFn, '<'.'? return ' . var_export($this->bucketList, true) . ';', LOCK_EX))
		throw new ZMC_Exception("Unable to write to " . $this->cacheFn . ':' . ZMC::getFilePermHelp($this->cacheFn));
}

public function get_bucket_region($bucket, $opt = null)
{
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	switch($this->cloud_type)
	{
		case 'openstack_cloud':
		case 'hp_cloud':
			if (empty($this->bucketList)) ZMC::quit($bucket);
			return $this->bucketList['buckets'][$bucket]['endpoint'];
			break;

		





		default:
			$opt['verb'] = 'GET';
			$opt['sub_resource'] = 'location';
			$this->authenticate($bucket, $this->getOpts($opt));
			unset($this->return['body']['@attributes']);
			if (array_key_exists(0, $this->return['body']))
				return $this->return['body'][0];
			if (empty($this->return['body']))
				return '';
			ZMC::quit(array($bucket, $this->return));
			




	}
}

private function listObjects($bucket, $opt = null)
{
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	if (!isset($this->bucketList['buckets'][$bucket]))
		throw new ZMC_Exception("Cloud bucket $bucket not found");

	parent::list_objects($bucket, $this->getOpts($opt));
	
	if ($this->authType !== 'SWIFT-2.0')
	{
		if (!empty($this->return['body']['Contents']['Key']))
			$this->return['body']['Contents'] = array($this->return['body']['Contents']); 
		return $this->return;
		
		
		
		
		
		
		
		
	}

	$contents = array();
	foreach($this->return['body'] as $object)
		$contents[] = array(
			'Key' => $object['name'],
			'LastModified' => $object['last_modified'],
			'ETag' => $object['hash'],
			'Size' => $object['bytes'],
		);
	$this->is_truncated = ((count($this->return['body']) < $this->return['header']['x-container-object-count']) ? 'true' : false);
	$this->return['body'] = array(
		'Contents' => $contents,
		'IsTruncated' => $this->is_truncated,
	);
	
	
	
	
	return $this->return;
}

public function createObject($bucket, $filename, $opt = null)
{
	$this->useBucketEndpoint($bucket);
	parent::create_object($bucket, $filename, $this->getOpts($opt));
	return true;
}

public function get_object($bucket, $filename, $opt = null)
{ return parent::get_object($bucket, $filename, $this->getOpts($opt)); }


public function delete_object($bucket, $key, $opt = null)
{
	error_log(__FUNCTION__ . "(): $bucket, $key");
	$qs = $this->opt['query_string'];
	$this->opt['query_string'] = '';
	$result = parent::delete_object($bucket, $key, $this->getOpts($opt));
	$this->opt['query_string'] = $qs;
	return $result;
}

public function delete_objects($bucket, $opt = null)
{ return parent::delete_objects($bucket, $this->getOpts($opt)); }

public function get_object_url($bucket, $filename, $preauth = 0, $opt = null)
{ return parent::delete_objects($bucket, $filename, $preauth, $this->getOpts($opt)); }

protected function getOpts($opt = null)
{
	$opt = (($opt === null) ? $this->opt : array_merge($this->opt, $opt));
	if (!empty($this->xHeaders))
	{
		$opt['curlopts'][CURLOPT_HEADER] = 1;
		$opt['curlopts'][CURLOPT_HTTPHEADER] = $this->xHeaders;
	}

	if (empty($opt['curlopts'][CURLOPT_CAINFO]) && !empty($this->cainfo))
		$opt['curlopts'][CURLOPT_CAINFO] = $this->cainfo;

	if (empty($opt['curlopts'][CURLOPT_CAPATH]))
		$opt['curlopts'][CURLOPT_CAPATH] = ZMC::$registry->curlopt_capath;

	return $opt;
}

public function createTape($config, $slot, $label)
{
	return $this->createObject($this->device['changer']['changerdev'], $tapeName = "$config-tape{$slot}special-tapestart",
		array('body' => ($body = "AMANDA: TAPESTART DATE X TAPE $label\n\0c\n"), 'length' => strlen($body)));
}

private function getSwiftAuthToken()
{
	$props = $this->device['device_property_list'];
	$url = 'http' . ($this->use_ssl ? 's':'') . '://';
	
	$url .= $props['S3_HOST'];
	$url .= $props['S3_SERVICE_PATH'];
	$details = $httpCode = '';
	$credential_type = 'API Access Key and Secret Key';
	try
	{
		if ($props['STORAGE_API'] === 'SWIFT-1.0')
			$result = ZMC::httpGet($url, array("X-Auth-User: " . $this->key, "X-Auth-Key: " . $this->secret_key), $post = array(), $this->cainfo, ZMC::$registry->curlopt_capath);
		else
		{
			if (empty($props['USE_API_KEYS']) || ($props['USE_API_KEYS'] === 'off'))
			{
				$props['USERNAME'] = $props['S3_ACCESS_KEY'];
				$props['PASSWORD'] = $props['S3_SECRET_KEY'];
				$credential_type = 'username and password';
			}
			if (!empty($props['USERNAME']) && !empty($props['PASSWORD']))
				$auth = array("auth" => array("passwordCredentials" =>	array("username" => $props['USERNAME'], "password" => $props['PASSWORD'])));
			elseif (!empty($props['S3_ACCESS_KEY']) && !empty($props['S3_SECRET_KEY']))
				$auth['auth']['apiAccessKeyCredentials'] =	array("accessKey" => $props['S3_ACCESS_KEY'], "secretKey" => $props['S3_SECRET_KEY']);
			else
				throw new ZMC_Exception('缺少API验证的Access Key 和 Secret Key', 499);

			
			
			if (!empty($props['TENANT_ID']))
				$auth['auth']['tenantId'] = $props['TENANT_ID'];
			elseif (!empty($props['TENANT_NAME']))
				$auth['auth']['tenantName'] = $props['TENANT_NAME'];

			ZMC::httpGet($result, $details, $httpCode, $url, array('Content-type' => 'application/json'), $auth, $this->cainfo, ZMC::$registry->curlopt_capath);
		}
		if (ZMC::$registry->dev_only) ZMC::errorLog(__FUNCTION__ . print_r(array('results' => $result, 'details' => $details, 'HTTP code' => $httpCode), true));
	}
	catch (Exception $e)
	{	$details = $e->getMessage(); }

	if (stripos($result, 'tenant not found'))
		$details = 'Please try a different tenant name.';
	elseif (stripos($details, 'unknown protocol'))
		if ($this->use_ssl)
			$details = 'Perhaps this cloud does not support SSL/TLS/secure communications.  Please uncheck the "Secure Communications" checkbox on either the Admin|devices or Backup|where page (see "Advanced Options").' . "\n$details";

	if (!empty($result))
	{
		$dataPos = strpos($result, "\r\n\r\n") + 3;
		foreach(explode("\n", substr($result, 0, $dataPos -3)) as $line)
			if ($line === '')
				break;
			elseif ($line[0] === 'X' && $line[1] === '-')
				if (strncmp($line, 'X-Storage-Url', 13))
						$this->xHeaders[substr($line, 0, strpos($line, ':'))] = trim($line);
					else
						$this->x_storage_url = substr(trim($line), 15);
	
		if ($dataPos < strlen($result))
			if ($this->swift_token = json_decode(substr($result, $dataPos), true))
				if (!empty($this->swift_token['access']))
					if (!empty($this->swift_token['access']['serviceCatalog']))
					{
						$this->xHeaders['X-Auth-Token'] = 'X-Auth-Token: ' . $this->swift_token['access']['token']['id'];
						$objectStores = $services = array();
						foreach($this->swift_token['access']['serviceCatalog'] as &$service)
						{
							$service['name'] = strtolower($service['name']);
							$services[$service['name']] =& $service;
							if ($service['type'] === 'object-store')
								$objectStores[$service['name']] =& $service;
						}
						$this->swift_token['access']['serviceCatalog'] =& $services;
						foreach($services as &$service)
							if (!empty($service['endpoints']))
							{
								$endpoints = array();
								foreach($service['endpoints'] as &$endpoint)
								{
									$endpoint['region'] = strtolower($endpoint['region']);
									$endpoints[$endpoint['region']] =& $endpoint;
								}
								$service['endpoints'] =& $endpoints;
								unset($endpoints);
							}
					}
	}

	switch($httpCode)
	{
		case 401:
			if ($props['STORAGE_API'] === 'SWIFT-1.0')
				$details .= ' Are the username/account id (X-AUTH-USER) and password/secret key (X-AUTH-KEY) correct?';
			else
				$details .= " Please check your credentials ($credential_type) with the keys given to ZMC\nAccess Key used: \"" . $props[$this->usernameKey] . '"';
			break;

		case 400:
		case 412:
			$details .= ' Is the ZMC Authentication Plugin Path correct?';
			break;
	}
	if (!empty($details) || $httpCode >= 400)
	{
		if (isset($this->swift_token['unauthorized']))
			$response = $this->swift_token['unauthorized'];
		elseif (isset($this->swift_token['message']) || isset($this->swift_token['details']))
			$response = $this->swift_token;
		elseif (isset($this->swift_token['error']) || isset($this->swift_token['error']['message']))
			$response = array('message' => $this->swift_token['error']['message'], 'details' => $this->swift_token['error']['title']);
		else
			throw new ZMC_Exception("$details\n" . ZMC::dump($this->swift_token, null, 'pretty'));

		$err = '';
		if (!empty($response['message'])) 
			$err = $response['message'] . ': ';
		if (!empty($response['details']))
			$err .= $response['details'];

		throw new ZMC_Exception($this->device['private']['zmc_device_name'] . ": $err\n$details");
	}

	if (empty($services))
		throw new ZMC_Exception("Unable to process authentication results received from the identity service: $result");

	$errAppend = '';
	if (!empty($props['TENANT_NAME']))
		$errAppend .= " for tenant $props[TENANT_NAME]";
	if (!empty($props['TENANT_ID']))
		$errAppend .= " for tenant $props[TENANT_ID]";
	if (!empty($props['USERNAME']))
		$errAppend .= " for user $props[USERNAME]";
	if (!empty($props['S3_ACCESS_KEY']))
		$errAppend .= " for access key $props[S3_ACCESS_KEY]";
	if (empty($objectStores))
		throw new ZMC_Exception("The Object Storage service is not enabled $errAppend");

	if (count($objectStores) !== 1)
		throw new ZMC_Exception("ZMC does not currently support selection of object stores.  Found " . count($objectStores) . " object stores at:\n$url");

	reset($objectStores);
	$store = current($objectStores);
	if (empty($store['endpoints']))
		throw new ZMC_Exception("Unable to locate any available regions (endpoints) of the Object Storage service $errApend");
	$this->endpoints =& $store['endpoints'];
	ZMC_Type_Where::$cloudRegions[$this->cloud_type] =& $this->endpoints;
	
	

	foreach($this->endpoints as $key => &$endpoint)
	{
		$endpoint[0] = true;
		$endpoint[1] = $endpoint['publicURL'];
		$endpoint[2] = ucfirst(strtolower($key)); 
		$endpoint[3] = $key; 
	}

	reset($this->endpoints);
	$this->endpoints[''] = current($this->endpoints); 
	$this->endpoints[''][0] = key($this->endpoints);
	$storeRequiresSecure = !strncasecmp($this->endpoints[''][1], 'https', 5);
	if ($this->use_ssl != $storeRequiresSecure)
	{
		$not = ($this->use_ssl ? '' : '*NOT*');
		$secure = ($storeRequiresSecure ? 'secure':'');
		if ($secure)
			$this->use_ssl = $secure;
		else
			throw new ZMC_Exception("This ZMC storage device was configured to $not use secure communications, but the object store requires $secure communications.");
	}

}

public function listBucket($bucket, $returnFalseIfNotExist = false)
{
	if ($this->bucketList === null) $this->getBucketList();
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	try
	{
		$next = '';
		$objects = array();
		$cacheFn = "Clouds-Bucket-Objects-$bucket";
		ZMC::useCache($this->pm, (ZMC::$registry->dev_only ? __FILE__ : null), $cacheFn, false, ZMC::$registry->cache_cloud_list_of_buckets, false);
		
		$this->pm->total_object_size = '0';
		if (empty($this->bucketList['buckets'][$bucket]))
			if ($returnFalseIfNotExist)
				return false;
			else
				$this->pm->addError("Bucket不存在: $bucket (code #" . __LINE__ . ")");

		$this->useBucketEndpoint($bucket);
		do
		{
			$this->listObjects($bucket, array ('marker' => urlencode($next)), $this->opt);
			$body =& $this->return['body'];

			if (empty($body['Contents']))
			{
				$this->pm->addMessage('Bucket已清空');
				break;
			}
			foreach($body['Contents'] as $object)
			{
				if (!isset($object['Key']))
					ZMC::quit($body);
				$objects[$object['Key']] = $object;
				$this->pm->total_object_size = bcadd($object['Size'], $this->pm->total_object_size);
			}

			if ($this->is_truncated)
				$next = $object['Key'];

		} while ($this->is_truncated && count($objects) < 1000); 

		if (!empty($result['header']['x-container-bytes-used']))
		{
			$this->pm->total_objects_in_bucket = $result['header']['x-container-object-count'];
			$this->pm->total_object_size = $result['header']['x-container-bytes-used'];
		}
		else
			$this->pm->total_objects_in_bucket = ($this->is_truncated ? 'more than ' : '') . count($objects);
	}
	catch(Exception $e)
	{	$this->pm->addInternal("无法读取云设备 (#".__LINE__.") 的内容: $e"); }

	file_put_contents($cacheFn, var_export(array(
		'total_objects_in_bucket' => $this->pm->total_objects_in_bucket,
		'total_object_size' => $this->pm->total_object_size,
		$objects), true));
	return $objects;
}

public function deleteBuckets($buckets, $isVault)
{
	if($isVault){
		$bl = $this->list_buckets();
		$bucketNames = array();
		foreach($bl['body']['Buckets']['Bucket'] as $index => $bucket)
			$bucketNames[$bucket['Name']] = $bucket['CreationDate'];
	}
	
	if ($this->bucketList === null) $this->getBucketList();
	if (!is_array($buckets)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	$deleted_buckets = 0;
	$statFn = ZMC::$registry->cnf->zmc_tmp_path . 'bucket-delete-status' . $this->device['_key_name'];
	unlink($statFn);
	$result = false;
	foreach($buckets as $name => $ignore)
	{
		$this->pm->setPrefix($name);
		if (!isset($this->bucketList['buckets'][$name]) && $isVault && !isset($bucketNames[$name])) 
		{
			$this->pm->addError("Bucket $name 不存在. 已删除?");
			continue;
		}

		if ($result = $this->deleteAllObjects($name, true, $statFn))
			$deleted_buckets++;

		$this->pm->setPrefix('');
		if (!empty($this->buckets_list['body']['Buckets']['Bucket'][$name]))
		{
			unset($this->buckets_list['body']['Buckets']['Bucket'][$name]);
			$this->updateBucketListCache();
		}
	}

	$this->pm->addWarning("从云存储设备中删除 $deleted_buckets 个备份集 " . $this->zmc_device_name . '.');
	ZMC::auditLog("Deleted ZMC Cloud device " . $this->zmc_device_name . " buckets: " . implode(', ', array_keys($buckets)));
	return $result;
}

protected function deleteAllObjects($bucket, $deleteBucket = false, $statFn)
{
	if (empty($bucket)) throw new ZMC_Exception("Internal error (code #" . __LINE__ . ')', 1, __FILE__, __LINE__);
	$this->pm->deleted_objects = 0;
	$result = null;
	try
	{
		$this->is_truncated = true;
		$mod = 1;
		$fp = fopen($statFn, 'a');
		while (($this->is_truncated === true)
			&& ($s3Objects = $this->listBucket($bucket, true))
			&& !empty($s3Objects))
		{
			if ($this->cloud_type !== 's3_cloud')
				foreach ($s3Objects as $object)
				{
					try { $this->delete_object($bucket, $object['Key']); }
					catch(Exception $e)
					{
						sleep(3); 
						
						$result = $this->delete_object($bucket, $object['Key']);
						if (!is_object($result) || !$result->isOK())
						{
							$this->pm->addInternal("无法删除 bucket.". ZMC::dump($result, null, 'pretty')." 中的部分/所有对象");
							break;
						}
					}
					$this->pm->deleted_objects++;
					if ($fp && (0===($this->pm->deleted_objects % $mod)))
					{
						if ($this->pm->deleted_objects >= 10)
							$mod = 10;
						fputs($fp, "Bucket $bucket: total objects deleted " . $this->pm->deleted_objects . "\n");
					}
				}
			else
			{
				$objects = array();
				foreach ($s3Objects as $object)
					$objects[] = array('key' => $object['Key']);

				$this->delete_objects($bucket, array('objects' => &$objects));
				ZMC::debugLog(__FUNCTION__ . ':AWSRESPONSE:' . print_r($this->return, true));
				unset($objects);
				$this->pm->deleted_objects += (empty($this->return['body']['Deleted']) ? count($s3Objects) : count($this->return['body']['Deleted']));
				if ($fp)
					fputs($fp, "Bucket $bucket: total objects deleted " . $this->pm->deleted_objects . "\n");
			}

		}

		if ($fp) fclose($fp);
		if ($s3Objects === false) return true;
	}
	catch(Exception $e)
	{
		if ($fp) fclose($fp);
		$this->pm->addInternal("Unable to delete some or all objects in bucket. $e". ZMC::dump($this->return, null, 'pretty'));
		return false;
	}

	$deletedMsg = "Deleted " . $this->pm->deleted_objects . " objects.";
	if (is_integer($this->pm->total_objects_in_bucket))
		$this->pm->total_objects_in_bucket -= $this->pm->deleted_objects;

	$this->pm->total_object_size = 'NA';
	if ($deleteBucket)
	{
		if ($this->deleteBucket($bucket) === false)
			return false;
		if ($this->pm->deleted_objects === 0)
			$this->pm->addMessage("删除空 bucket.");
		else
			$this->pm->addMessage("删除 bucket 及其所有 " . $this->pm->deleted_objects . " 对象.");
		return true;
	}

	if ($this->pm->deleted_objects === 0)
		return $this->pm->addError("Bucket 已经清空.");

	$this->pm->addMessage($deletedMsg);
	return false;
}

public function authenticate($bucket, $opt = null)
{

	$where = 'Cloud Request #' . self::$nRequests++ . ' ' . __FUNCTION__ . "($bucket) - ";
	if (ZMC::$registry->debug)
	{
		list ($function2, $file2, $line2) = ZMC_Error::getFileLine($function, $file, $line);
		$where = "$file2:#$line2:$function2() => $file:#$line:$function() => ";
	}
	ZMC::debugLog($where);
	try{
		$this->result = parent::authenticate($bucket, $opt);
	}
	catch(Exception $e)
	{
		if(preg_match('/\/ZMC_X_Cloud/', $this->pm->url))
			$this->pm->addWarning("Bucket : $bucket; ". $e->getMessage());
		ZMC::debugLog( "Bucket: $bucket; " .$e->getMessage());
		return false;
	}

	
	if (!empty($this->equit))
		ZMC::quit($this->result);

	if (!is_object($this->result))
		throw new ZMC_Exception("Cloud request failed. Code #" . ZMC::dump($this->result, null, 'pretty'), __LINE__, __FILE__, __LINE__);

	if (is_string($this->result->body) && ($this->result->body[0] === '{') || ($this->result->body[0] === '['))
	{
		$body = json_decode($this->result->body, true);
		if ($body !== null)
			$this->result->body = null;
	}
	$this->return = json_decode(json_encode($this->result), true);
	if (!empty($body))
	{
		$this->return['body'] =& $body;
		$this->is_truncated = false;
		
	}
	else
		$this->is_truncated = (!empty($this->return['body']['IsTruncated']) && ($this->return['body']['IsTruncated'] === 'true'));

	if (ZMC::$registry->debug) ZMC::debugLog("$where\nResult:" . ZMC::dump($this->return, null, 'pretty'));
	if ($this->result->isOK())
		return $this->return;

	if (   is_array($this->return)
		&& !empty($this->return['body']) 
		&& $this->result->isOK(202))
	{
		if(!is_array($this->return['body']))
			if(strpos($this->return['body'], 'accepted for processing' ) === true)
				sleep(30);
				return $this->return;
	}

	if (($this->cloud_type === 'cloudena_cloud') || ($this->cloud_type === 'openstack_cloud'))
		if ($this->result->status == 503 || $this->result->status == 404)
			return $this->return;

	if (!empty($this->return['body'])){
		ZMC::debugLog(print_r($this->return['body'], true));
		$result = (is_array($this->return['body']) && array_key_exists('Message', $this->return['body'])) ? $this->return['body']['Message'] : $this->return['body'];
		throw new ZMC_Exception_A3(ZMC::dump($result, null, 'pretty'), $this->result->status, __FILE__, __LINE__);
	}

	throw new ZMC_Exception_A3("Cloud request failed (code #" . $this->status . ') '. ZMC::dump($this->return, null, 'pretty'), $this->result->status, __FILE__, __LINE__);
}
}
