<?













class ZMC_License 
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', '云备份 - 许可证状态', 'licenses');
		try{ $pm->merge(ZMC_License::readLicenses($pm)); }
		catch(Exception $e)
		{
			if (ZMC::$registry->dev_only)
				ZMC::quit($e);
		}
		$plural = count($pm->license_expires_list) > 1;
		$pm->addMessage("你的许可证有效期到 " . ($plural ? 'licenses expire':'license expires') . " on: "
			. ($plural ? "\n":'')
			. implode("\n", array_unique($pm->license_expires_list)));
		if ($pm->offsetExists('over_limit_errors'))
			$pm->addError($pm->over_limit_errors);
		return 'License';
	}

	public static function readLicenses(ZMC_Registry_MessageBox $pm)
	{
		return ZMC_Yasumi::operation($pm, array(
			'pathInfo' => '/stats/read',
			'post' => null,
			'postData' => null,
			'data' => array('zmc_device_histograms' => true), 
		));
	}
}
