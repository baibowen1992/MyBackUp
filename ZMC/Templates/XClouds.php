<?













global $pm;
echo "\n<form method='post' disabled='disabled' name='foober' action='$pm->url'>\n";
echo "<input type='hidden' name='device' value='", $pm->zmc_device_name, "' />\n";
if (!isset($pm->confirm_template))
{
?>

<div class="zmcWindow"">
	<?
		if (empty($pm->selected))
			ZMC::titleHelpBar($pm, 'List of Cloud Containers for ZMC device: '. $pm->zmc_device_name);
		else
			ZMC::titleHelpBar($pm, 'List of Objects in Container ' . $pm->selected . ' for ZMC device: '. $pm->zmc_device_name);
	?>
	<div class="zmcFormWrapperText"><?
		if (ZMC_A3::$nRequests)
			echo "Total Cloud Requests Performed: " . ZMC_A3::$nRequests;
		else
			echo "No Cloud requests performed.  All results from cache.";
		
		if ($pm->url === '/ZMC_X_Cloud')
		{
			if (empty($_REQUEST['unhide']))
				if ($pm->total_buckets > $pm->zmc_buckets)
					echo '<div style="float:right;"><label for="unhide">Show hidden containers that were not created by ZMC?</label><input id="unhide" type="submit" name="unhide" value="List" /></div>';

			
			if (empty($pm->zmc_buckets))
				echo "<br />No ZMC containers found.";

			if (!empty($pm->zmc_buckets))
			{
				echo "<br clear=all />ZMC Containers: ", $pm->zmc_buckets;
				if (!empty($pm->zmc_buckets_legacy))
					echo "<br />ZMC Legacy Containers: ", $pm->zmc_buckets_legacy;
				
				if (!empty($pm->account_container_count) && ($pm->account_container_count != $pm->total_buckets))
					echo "<br />Total Containers Reported: ", $pm->account_container_count;
				if (!empty($pm->account_object_count))
					echo "<br />Total Objects: ", $pm->account_object_count;
				if (!empty($pm->account_bytes_used))
					echo "<br />Total Bytes Used: ", ZMC::prettyPrintByteCount($pm->account_bytes_used);
			}
		}
		elseif (!empty($pm->object))
			echo ZMC::escape($pm->object);
		else
		{
			echo "Total Objects in Container: ", $pm->total_objects_in_bucket;
			echo "\n<br />Total Size of All Objects in Container: ", ZMC::prettyPrintByteCount($pm->total_object_size);
			echo "\n<input type='hidden' name='bucket' value='" . urlencode($pm->selected) . "' />\n";
		}
	?>
	</div>
</div>
<?
}

if (empty($pm->rows))
	return;










$pm->buttons = array(
    'Refresh Table' => true,
    'Edit' => false,
    'List' => 'onclick="this.form.action = \'/ZMC_X_Cloud_Bucket\'; return true;"',
    'Delete' => 'onclick="this.form.action = \'/ZMC_X_Cloud_Bucket\'; return true;"',
);
$pm->tableTitle = 'Cloud Containers';
if ($pm->url === '/ZMC_X_Cloud_Bucket')
{
	$pm->buttons = array(
		'Get' => true,
	);
	$pm->tableTitle = 'Cloud Objects';
}
$pm->disable_onclick = true;
ZMC_Loader::renderTemplate('tableWhereStagingWhen', $pm);
