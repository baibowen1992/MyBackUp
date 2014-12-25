<?













global $pm;
if (empty($pm->zmc_type_histograms) === 0)
	$pm->addEscapedMessage('No client licenses used.');

show($pm, $pm->licenses);
?>

<div class='zmcFormWrapper' style='clear:both; padding:0'>
	<?
	if (!empty($pm->zmc_type_histograms))
	{
		showLicensesBy($pm, $pm->zmc_type_histograms, 'Feature', 'Hosts');
		showLicensesBy($pm, $pm->zmc_host_histograms, 'Host', 'Features');
	}
	if (!empty($pm->zmc_device_histograms))
		showLicensesBy($pm, $pm->zmc_device_histograms, 'Device', 'Backup Sets');
	?>
	<div class="zmcButtonBar">
		<form>
			<input type="button" value="OK" name="btnBack" id="btnBack" onclick="history.back()"/>
		</form>
	</div>
</div><!-- content -->

<?
function feature2human($product, $feature = null)
{
	static $feature2human = array();
	if (empty($feature2human[$product]))
	{
		if ($product === 'zrm' || $product === 'nbumysql')
		{
			$feature2human[$product] = array(
				'lvm' => 'Logical Volume Manager',
				'vss' => 'Virtual System Snapshot',
				'vfs' => 'Virtual File System',
				'zfs' => 'ZFS',
				'rep' => 'REP',
				'ihb' => 'IHB',
				'emcsnapview' => 'EMCSNAPVIEW',
				'ebssnap' => 'EBSSNAP',
				'xtrabackup' => 'XTRABACKUP',
				'tsm' => 'TSM',
				'ntap' => 'NTAP',
				'bluearcsnap' => 'BLUEARCSNAP',
				'netbackup' => 'NETBACKUP',
			);
		}
		else
		{

			$a1 = ZMC_Type_What::getPrettyNames('group_names');

			if(in_array("Linux", $a1))
				$a1['unix'] = "Linux, Mac, Solaris";
			if(in_array("Mac", $a1))
				unset($a1['mac']);
			if(in_array("Solaris", $a1))
				unset($a1['solaris']);

			$a1['backupserver'] = "Backup Server";
			$a2 = ZMC_Type_Devices::getPrettyNames('group_names');
			$a2['backupserver'] = "Backup Server";
			$a2['vault'] = "Vault";
			asort($a1);
			asort($a2);
			$feature2human[$product] = array_merge($a1, $a2);
		}
	}

	return ($feature ? $feature2human[$product][$feature] : array_keys($feature2human[$product]));
}

function show($pm, $things)
{
	$things['zmc']['Licensed']['backupserver'] = 1;
	$things['zmc']['Licensed']['unix'] -= 1;
	$things['zmc']['Expires']['backupserver'] = $things['zmc']['Expires']['unix'];
	if($pm['zmc_type_histograms']['unix']['localhost']){ 
		$things['zmc']['Used']['backupserver'] = 1;
		$things['zmc']['Used']['unix'] -= 1;
		$things['zmc']['Remaining']['backupserver'] = 0;
	} else {
		$things['zmc']['Used']['backupserver'] = 0;
		$things['zmc']['Remaining']['backupserver'] = 1;
		$things['zmc']['Remaining']['unix'] -= 1;
	}
	$subWindowClass = 'adminAddUser';
	ksort($things);
	require 'ads.php';
	foreach($things as $product => $state)
	{
		if (!ZMC::$registry->dev_only && ($product === 'zrm' || $product === 'nbumysql'))
			continue;
		$name = ($product === 'zmc' ? ZMC::$registry->name : 'Zmanda Recovery Manager for MySQL');
		$wiki = ZMC::$registry->wiki;
		$shopLink = ZMC::$registry->links['shopping'];
		echo '<div class="zmcLeftWindow">';
	    ZMC::titleHelpBar($pm, "<a href='$shopLink'>$name - Licensed Features Summary</a>", $product);
		echo '<div class="zmcFormWrapper" style="padding:0"><form action="/Yasumi/createLicense.php" method="post">';
		$subWindowClass = 'adminAssignPassword';
		echo <<<EOD
				<div class="dataTable centerHeadings">
					<table border="0" width="633" cellspacing="0" cellpadding="0"><tbody>
						<tr>
							<th title='Total licensed, excluding expired (if any)' scope='col'>Licensed</th>
							<th title='Total used' scope='col'>Used</th>
							<th title='Total licensed remaining (not yet used)' scope='col'>Remaining</th>
							<th title='Licensed, but expiring soon' scope='col'>Expiring</th>
							<th title='Total expired' scope='col'>Expired</th>
							<th title='Expires On This Date' scope='col'>Expires On</th>
							<th title='Feature' scope='col' class="leftHeading">Feature</th>
						</tr>
EOD;
		$i = 0;
		foreach(feature2human($product) as $feature)
		{
			if (!ZMC::$registry->dev_only && empty($state['Expires'][$feature]))
				if (strpos($feature, 'cloudlic') || ($feature === 's3compatiblelic') || ($feature === 's3'))
					continue;
			$color = (($i++ % 2) ? 'White' : 'Gray');
			$human = feature2human($product, $feature);
			$foundFeature = false;
			foreach(array('Licensed', 'Used', 'Remaining', 'Expiring', 'Expired', 'Expires') as $status)
				if (!empty($state[$status][$feature]))
				{
					$count[$status] = $state[$status][$feature];
					$foundFeature = true;
				}
				else
					$count[$status] = '-';

			if (!is_string($count['Expires']))
				$count['Expires'] = date('Y-m-d', $count['Expires']);

			$expireStyle = '';
			if (($count['Expired'] > 0) )
			{
				if (!empty($_GET['license_group']) && ($feature === $_GET['license_group']))
					$color = 'BrightYellow';
				$expireStyle = 'style="color:#c00; font-weight:bold;"';
				
			}

			if (ZMC::$registry->dev_only)
				$input = "&nbsp;<input type='text' maxlength='2' style='width:20px;' name='$feature' />";
			if (!$foundFeature)
			{
				echo <<<EOD
					<tr class='stripe$color'>
						<td class='zmcCenterNoLeftPad' style='border-left:none'>$count[Licensed]$input</td>
						<td class='zmcCenterNoLeftPad'>-</td>
						<td class='zmcCenterNoLeftPad'>-</td>
						<td class='zmcCenterNoLeftPad'>-</td>
						<td class='zmcCenterNoLeftPad'>-</td>
						<td class='zmcCenterNoLeftPad'>-</td>
						<td>$human</td>
					</tr>
EOD;
				continue;
			}

			if (ZMC_Type_Devices::hasLicenseGroup($feature, $pm->feature2group) || $feature === 'vault' || $feature === 'changer_ndmp')
			{
				$devs = array(
					'Licensed' => $count['Licensed'] > 0 ? "<img src='/images/global/calendar/icon_calendar_success.gif' title='$count[Licensed]' />" : $count['Licensed'],
					'Used' => $count['Used'],
					
					'Remaining' => '<td class="zmcCenterNoLeftPad">-</td>',
					'Expiring' => ($count['Licensed'] !== '-' && ($count['Expiring'] > 0)) ? "<img src='/images/global/calendar/icon_calendar_warning.gif' title='Expiring Soon' />" : '-',
					'Expired' => (!($count['Licensed'] > 0) && $count['Expired'] > 0) ? "<img src='/images/global/calendar/icon_calendar_failure.gif' title='Expired' />" : '-',
					'Expires' => $count['Expires'],
				);
				$count = $devs;
			}
			else
			{
				if ($count[$status = 'Remaining'] !== '-')
				{
					if ($count[$status] < '0')
						$count[$status] = "<td class='zmcCenterNoLeftPad' style='color:#c00; background-color:#FF9; font-weight:bold;'>$count[$status]</td>";
					elseif ($count[$status] == '0')
						$count[$status] = "<td class='zmcCenterNoLeftPad' style='color:#c00; font-weight:bold;'>$count[$status]</td>";
					elseif ($count[$status] == 1)
						$count[$status] = "<td class='zmcCenterNoLeftPad' style='color:#B24700; font-weight:bold;'>$count[$status]</td>";
					else
						$count[$status] = "<td class='zmcCenterNoLeftPad'>$count[$status]</td>";
				}
				else
					$count['Remaining'] = '<td class="zmcCenterNoLeftPad">-</td>';

				if ($count[$status = 'Expiring'] !== '-')
					$count[$status] = "<span class='zmcIconWarning zmcUserErrorsText'>$count[$status]</span>";
			}

			echo <<<EOD
						<tr class='stripe$color'>
							<td class='zmcCenterNoLeftPad' style='border-left:none'>{$count['Licensed']}$input</td>
							<td class='zmcCenterNoLeftPad'>{$count['Used']}</td>
							{$count['Remaining']}
							<td class='zmcCenterNoLeftPad'>{$count['Expiring']}</td>
							<td class='zmcCenterNoLeftPad'>{$count['Expired']}</td>
							<td class='zmcCenterNoLeftPad' $expireStyle>{$count['Expires']}</td>
							<td>$human</td>
						</tr>
EOD;
		}
		echo "
					</table>
				</div><!-- dataTable -->\n"; 
		if (ZMC::$registry->dev_only)
			echo "
				<div class='zmcButtonBar'><small>Expiration:
					YYYY: <input class='zmcUltraShortInput' type='text' name='Y' />
					MM:   <input class='zmcUltraShortInput' type='text' name='M' />
					DD:   <input class='zmcUltraShortInput' type='text' name='D' /> 
					<input type='submit' name='submit' value='Create License' />
					</small>
				</div></form>
			";
		echo "
			</div><!-- zmcFormWrapper -->
		</div><!-- zmcLeftWindow -->\n";
	}
}

function showLicensesBy($pm, $table, $by, $for)
{	
	$caption = "Licenses Used by $by";
	$licenses = $pm->licenses;
	if ($by === 'Device'){
		$caption = "Devices Used by Backup Sets";
	} elseif ($by === 'Feature' && $table['unix']['localhost']){ 
		$table['backupserver']['localhost'] = $table['unix']['localhost'];
		unset($table['unix']['localhost']);
		if(empty($table['unix']))
			unset($table['unix']);
		$licenses['zmc']['Used']['backupserver'] = 1;
		$licenses['zmc']['Used']['unix'] -= 1;
	} elseif ($by == 'Host' && $table['localhost']['unix']){
		$table['localhost']['backupserver'] = $table['localhost']['unix'];
		unset($table['localhost']['unix']);
	}

    ZMC::titleHelpBar($pm, $caption, null, 'zmcTitleBarTable');
	echo <<<EOD
		<div class="dataTable centerHeadings">
			<table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody>
				<tr>
					<th width='80' title='Total $for for this $by' scope='col'>$for</th>
					<th width='155' title='License feature by $by' scope='col' class="leftHeading">$by</th>
					<th width='80' title='Total DLEs used by this $by' scope='col'>Total DLEs</th>
					<th title='$for using DLEs of this $by' scope='col' class="leftHeading">$for</th>
				</tr>
EOD;
	$i=0;
	ksort($table);
	foreach($table as $typeGroup => $hosts)
	{
		$color = (($i++ % 2) ? 'White' : 'Gray');
		if ($by === 'Feature')
			$totalUsed = isset($licenses['zmc']['Used'][$typeGroup]) ? $licenses['zmc']['Used'][$typeGroup] : 0;
		else
			$totalUsed = count($hosts);

		$hostList = '';
		if ($by === 'Host')
		{
			$humanTypeGroup = $typeGroup;
			foreach(array_keys($hosts) as $type)
				$hostList .= feature2human('zmc', $type) . ";\n";
			$hostList = substr($hostList, 0, -2);
		}
		else
		{
			$humanTypeGroup = (strtolower($typeGroup) === 'unknown') ? 'Unknown' : (feature2human('zmc', $typeGroup));
			$hostList = implode(",\n", array_keys($hosts));
		}
		$count = array_sum($hosts);
		echo <<<EOD
				<tr class='stripe$color'>
					<td class='zmcCenterNoLeftPad'>$totalUsed</td>
					<td>$humanTypeGroup</td>
					<td class='zmcCenterNoLeftPad'>$count</td>
					<td>$hostList</td>
				</tr>
EOD;
	}
	echo "
			</table>
		</div><!-- dataTable -->\n";
}
