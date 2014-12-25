<?













class ZMC_Report_Media
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->enable_switcher = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Report', 'ZMC - Backup media usage report', 'Media');
		$pm->addDefaultInstruction('View reports about backup media usage.');
		if (!ZMC_BackupSet::assertSelected($pm))
			return 'MessageBox'; 
		$pm->addError(ZMC_BackupCalendar::initReportCalendar($pm));
		ZMC_Report::refreshDB($pm);
		ZMC_Report::setUpBIDArrays($pm, ZMC_BackupSet::getId());
		$pm->numBackup=0;
		if (isset($pm->BIDRangeArray[0]['bidinfo']))
			if (!($pm->numBackup = $pm->BIDRangeArray[0]['bidinfo']->mGetNumItem()))
				$pm->addMessage('No BACKUPS on this day.');

		ZMC_Report::renderMessageArea($pm);
		return 'ReportMedia';
	}

	public static function getMediaImage($detailRow)
	{
		$mediaType = 'disk';
		
		

		$imgLoc = "/images/section/report/";
		if($detailRow['level'] == 0)
			$full = true;
		elseif($detailRow['level'] >= 1)
			$full = false;
		else 
			$full = true;
		$mediaType = ($detailRow['_key_name'])? $detailRow['_key_name'] : 'attached_storage';
	
		switch($mediaType)
		{
			case "disk":
			case "attached_storage":
				$icon = ($full)?"media_disk_full.gif":"media_disk_half_full.gif";
				break;

			case "tape":
				$icon = ($full)?"media_tape_full.gif":"media_tape_half_full.gif";
				break;
			case "changer":
			case "changer_ndmp":
			case "changer_library":
				$icon = ($full)?"media_changer_full.gif":"media_changer_half_full.gif";
				break;

			case "cloudena_cloud":
			case "s3_cloud":
			case "google_cloud":
			case "openstack_cloud":
			case "iij_cloud":
			case "hp_cloud":
			case "s3_compatible_cloud":
				$icon = ($full)?"media_s3_full.gif":"media_s3_half_full.gif";
				break;

		}

		return $imgLoc.$icon;
	}
	
	public static function getTapeIdArrayPerBackupId($backuprun_id)
	{
		return ZMC_Mysql::getAllOneValue('SELECT backuprun_tape_id FROM backuprun_tape_usage WHERE configuration_id=' . ZMC_BackupSet::getId() . " AND backuprun_id='$backuprun_id'");
	}
	
	public static function getBarCode($tape_label)
	{
		ZMC::quit('@TODO: fixme .. we now read barcodes directly from tapelist');
		
	}
}
