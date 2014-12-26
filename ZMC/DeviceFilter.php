<?





















class ZMC_DeviceFilter {

public static function filter(ZMC_Registry_MessageBox $pm, $method, array $device, $formClass = false)
{
	if (empty($device['_key_name'])) ZMC::quit($device);
	if (method_exists($class = get_called_class(), $methodOverride = $method . $device['_key_name']))
		return call_user_func(array($class, $methodOverride), $pm, $device, $formClass);
	if (ZMC::$registry->debug) $pm->addWarning("$methodOverride not found");
	return self::$method($pm, $device, $formClass);
}


protected static function read(ZMC_Registry_MessageBox $pm, $device, $formClass)
{
	if (empty($device['private']['last_modified_time']))
		$device['private']['last_modified_time'] = 'NA';
	if (empty($device['private']['last_modified_by']))
		$device['private']['last_modified_by'] = 'NA';
	if (!empty($device['id']))
		$device['private']['used_with'] = implode(', ', ZMC_BackupSet::getNamesUsing($device['id'], true));

	ZMC::assertNoColons($device);
	ksort($device);
	return $device;
}

protected static function readAttached_Storage(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::read($pm, $device, $formClass); }

protected static function readChgrobot(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::read($pm, $device, $formClass); }

protected static function readChanger_Library(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readChgrobot($pm, $device, $formClass); }

protected static function readChanger_Ndmp(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readChgrobot($pm, $device, $formClass); }

protected static function readS3_Compatible_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{
	$device = static::read($pm, $device, $formClass);
	if (isset($device['device_property_list']['MAX_SEND_SPEED']))
	{
		$device['device_property_list']['MAX_SEND_SPEED'] /= 1024;
		$device['device_property_list']['MAX_RECV_SPEED'] /= 1024;
	}
	return $device;
}

protected static function readIIJ_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function readOpenstack_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function readHp_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readOpenstack_Cloud($pm, $device, $formClass); }

protected static function readCloudena_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readOpenstack_Cloud($pm, $device, $formClass); }

protected static function readS3_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function readGoogle_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::readS3_Compatible_Cloud($pm, $device, $formClass); }



protected static function input(ZMC_Registry_MessageBox $pm, $post, $formClass)
{
	unset($post['selected_ids']);
	ZMC::assertColons($post);
	if (!class_exists($formClass))
		if (ZMC::$registry->debug)
			ZMC::quit(array('Unknown object type (pm->form_type===false)' => $pm));
		else
			ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex(__CLASS__ . ' - Unknown object type'), __FILE__, __LINE__);
	if (empty($post['_key_name']))
		ZMC::quit();
	$pm->form_type = $formClass::get($post['_key_name']); 
	$device =& ZMC_Form::form2array($pm, $pm->form_type, $post, array(
			'zmc_version' => ZMC::$registry->zmc_version,
			'_key_name' => $post['_key_name'],
		));
	ksort($device);
	return $device;
}

protected static function inputAttached_Storage(ZMC_Registry_MessageBox $pm, $post, $formClass)
{	return static::input($pm, $post, $formClass); }

protected static function inputChgrobot(ZMC_Registry_MessageBox $pm, $input, $formClass)
{
	foreach(array_keys($input['changer:tapedev']) as $drive)
	{
		if ($input['changer:tapedev'][$drive] === '?')
			$input['changer:tapedev'][$drive] = 'skip';
		if (($drive === '/dev/') || ($drive === '/dev'))
		{
			$input['changer:tapedev'][$drive] = null;
			ZMC::quit($input['changer:tapedev']);
		}
		elseif (!strncasecmp($drive, 'other', 5))
		{
			if ($input['changer:tapedev'][$drive] === 'skip')
				unset($input['changer:tapedev'][$drive]);
			else
			{
				if (($input[$drive] !== '/dev/') && ($input[$drive] !== '/dev'))
					$input['changer:tapedev'][$input[$drive]] = $input['changer:tapedev'][$drive];
				$input[$drive] = $input['changer:tapedev'][$drive] = null;
			}
		}
	}
	ZMC::flattenArray($post, $input);
	if (empty($post['changer:changerdev']) && !empty($post['changer:changerdev_other']) && $post['changer:changerdev_other'] !== '/dev/')
		ZMC::$registry->mergeOverride('changerdev_user', $post['changer:changerdev'] = $post['changer:changerdev_other']);

	unset($post['changer:changerdev_other']);
	return static::inputTape($pm, $post, $formClass);
}

protected static function inputChanger_Library(ZMC_Registry_MessageBox $pm, $post, $formClass)
{	return static::inputChgrobot($pm, $post, $formClass); }

protected static function inputChanger_Ndmp(ZMC_Registry_MessageBox $pm, $post, $formClass)
{	return static::inputChgrobot($pm, $post, $formClass); }

protected static function inputTape(ZMC_Registry_MessageBox $pm, $post, $formClass)
{
	if (empty($post['changer:tapedev']) && !empty($post['changer:tapedev_other']) && $post['changer:tapedev_other'] !== '/dev/')
		ZMC::$registry->mergeOverride('tapedev_user', $post['changer:tapedev'] = $post['changer:tapedev_other']);

	unset($post['changer:tapedev_other']);
	return static::input($pm, $post, $formClass);
}

protected static function inputS3_Compatible_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{
	$device = static::input($pm, $device, $formClass);
	if (isset($device['device_property_list:MAX_SEND_SPEED']))
	{
		if ((!preg_match('/^[0-9.]*$/', $device['device_property_list:MAX_SEND_SPEED'])) || ($device['device_property_list:MAX_SEND_SPEED'] >= PHP_INT_MAX))
			$pm->addError("A value entered '".$device['device_property_list:MAX_SEND_SPEED']."' for Max Upload Speed is too large.");
		if ((!preg_match('/^[0-9.]*$/', $device['device_property_list:MAX_RECV_SPEED'])) || ($device['device_property_list:MAX_RECV_SPEED'] > PHP_INT_MAX))
			$pm->addError("A value entered '".$device['device_property_list:MAX_RECV_SPEED']."' for Max Download Speed is too large.");

		$device['device_property_list:MAX_SEND_SPEED'] = max(5, intval($device['device_property_list:MAX_SEND_SPEED']));
		$device['device_property_list:MAX_RECV_SPEED'] = max(5, intval($device['device_property_list:MAX_RECV_SPEED']));
		$device['device_property_list:MAX_SEND_SPEED'] *= 1024;
		$device['device_property_list:MAX_RECV_SPEED'] *= 1024;
	}

	if (isset($device['device_property_list:BLOCK_SIZE'])) 
	{
		$bs = $device['device_property_list:BLOCK_SIZE'];
		$unit = $device['device_property_list:BLOCK_SIZE_display'];
		$norm = array('bs' => $bs, 'bs_display' => $unit);
		ZMC::convertToDisplayUnits($norm);
		$display = ZMC::$registry->units['storage'][ZMC::$registry->units['storage_equivalents'][strtolower($unit)]];

		$suggest = "Try 256 MiB.";
		$device['device_output_buffer_size'] = $norm['bs'] * 2 . 'm'; 
		if ($norm['bs'] < 32)
			$warning = "块大小 $bs $display 太小.";
	
		if ($norm['bs'] > 2047)
			$pm->addWarnError("块大小 $bs $display 太大. $suggest");
		elseif ($norm['bs'] > 1024)
			$warning = "Block Size $bs $display is big.";

		if($norm['bs'] > 256 && $device['device_property_list:REUSE_CONNECTION'] == "on")
			$pm->addWarning("启用连接重用在对象大小 ".$norm['bs']."$display 的时候会影响性能.");
		
		if (!empty($warning))
			$pm->addWarning("$warning $suggest");
		if (empty($device['device_property_list:S3_SECRET_KEY']) && empty($device['device_property_list:PASSWORD']))
			$pm->addWarnError("secret key/密码为空.");
		else if (!empty($device['device_property_list:S3_SECRET_KEY']) && !ctype_alnum($key = $device['device_property_list:S3_ACCESS_KEY']))
			$pm->addWarnError("access key '$key' 含有非法字符.");
		if (empty($device['device_property_list:S3_ACCESS_KEY']) && empty($device['device_property_list:USERNAME']))
			$pm->addWarnError("填入的用户名/access key 为空.");
	}

	return $device;
}

protected static function inputOpenStack_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{
	if (isset($device['device_property_list:TENANT_NAME']))
		$device['device_property_list:TENANT_NAME'] = trim($device['device_property_list:TENANT_NAME']);
	
	
	return static::inputS3_Compatible_Cloud($pm, $device, $formClass);
}

protected static function inputS3_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::inputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function inputGoogle_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::inputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function inputHp_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::inputOpenStack_Cloud($pm, $device, $formClass); }

protected static function inputCloudena_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::inputOpenStack_Cloud($pm, $device, $formClass); }

protected static function inputIIJ_Cloud(ZMC_Registry_MessageBox $pm, $device, $formClass)
{	return static::inputS3_Compatible_Cloud($pm, $device, $formClass); }


protected static function output(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{
	ZMC::assertColons($device);
	ksort($device);
	return $device;
}

protected static function outputChgdisk(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{
	$device['changer:changerdev'] = $device['changer:changerdev_prefix'];
	return static::output($pm, $device, $formClass);
}

protected static function outputAttached_Storage(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputChgdisk($pm, $device, $formClass); }

protected static function outputS3_Compatible_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{
	if (isset($device['device_property_list:S3_HOST']))
		$device['changer:changerdev'] = $device['device_property_list:S3_HOST'];
	
	

	return static::output($pm, $device, $formClass);
}

protected static function outputS3_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function outputGoogle_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function outputOpenStack_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function outputHp_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputOpenStack_Cloud($pm, $device, $formClass); }

protected static function outputCloudena_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputOpenStack_Cloud($pm, $device, $formClass); }

protected static function outputIIJ_Cloud(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputS3_Compatible_Cloud($pm, $device, $formClass); }

protected static function outputChgrobot(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{
	if (!empty($device['changer:changerdev']))
		$device['changer:changerdev_other'] = $device['changer:changerdev'];

	ZMC_HeaderFooter::$instance->injectYuiCode("{ var o = gebi('changer:changerdev'); if (o && o.onchange) o.onchange() }");
	return static::outputTape($pm, $device, $formClass);
}

protected static function outputChanger_Library(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputChgrobot($pm, $device, $formClass); }

protected static function outputChanger_Ndmp(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{	return static::outputChgrobot($pm, $device, $formClass); }

protected static function outputTape(ZMC_Registry_MessageBox $pm, array $device, $formClass)
{
	if (isset($device['changer:tapedev:0']))
		ZMC::array_move($device, $device, array('changer:tapedev:0' => 'changer:tapedev'));

	if (!empty($device['changer:tapedev']))
		$device['changer:tapedev_other'] = $device['changer:tapedev'];

	if ($formClass)
		ZMC_HeaderFooter::$instance->injectYuiCode("{ var o = gebi('changer:tapedev'); if (o && o.onchange) o.onchange() }");

	return static::output($pm, $device, $formClass);
}

public static function filterNamedList(ZMC_Registry_MessageBox $pm, &$list)
{
	foreach($list as $name => &$item)
	{
		$item['id'] = $name;
		$item = self::filter($pm, 'read', $item);
	}
}
}
