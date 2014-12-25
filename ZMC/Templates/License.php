<?













global $pm;
if (empty($pm->zmc_type_histograms) === 0)
	$pm->addEscapedMessage('没有任何客户端许可证');

show($pm, $pm->licenses);
?>

<div class='wocloudFormWrapper' style='clear:both; padding:0'>
	<?
	if (!empty($pm->zmc_type_histograms))
	{
		showLicensesBy($pm, $pm->zmc_type_histograms, '属性', '主机数');
		showLicensesBy($pm, $pm->zmc_host_histograms, '主机数', '属性');
	}
	if (!empty($pm->zmc_device_histograms))
		showLicensesBy($pm, $pm->zmc_device_histograms, '设备', '备份集');
	?>
	<div class="wocloudButtonBar">
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
		$name = ($product === 'zmc' ? ZMC::$registry->name : 'Recovery Manager for MySQL');
		$wiki = ZMC::$registry->wiki;
		$shopLink = ZMC::$registry->links['shopping'];
		echo '<div class="wocloudLeftWindow">';
	    ZMC::titleHelpBar($pm, "<a href='$shopLink'>云备份 - 许可证授权情况汇总</a>", $product);
		echo '<div class="wocloudFormWrapper" style="padding:0"><form action="/Yasumi/createLicense.php" method="post">';
		$subWindowClass = 'adminAssignPassword';
		echo <<<EOD
				<div class="dataTable centerHeadings">
					<table border="0" width="633" cellspacing="0" cellpadding="0"><tbody>
						<tr>
							<th title='总许可证数目，包含已过期。'>许可证数</th>
							<th title='已经使用的总许可证数目'>已使用</th>
							<th title='还剩下的许可证数目'>未使用</th>
							<th title='即将到期的许可证'>即将过期</th>
							<th title='已过期的许可证数'>已过期</th>
							<th title='过期日期'>过期时间</th>
							<th title='许可证类型' scope='col' class="leftHeading">属性</th>
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
						<td class='wocloudCenterNoLeftPad' style='border-left:none'>$count[Licensed]$input</td>
						<td class='wocloudCenterNoLeftPad'>-</td>
						<td class='wocloudCenterNoLeftPad'>-</td>
						<td class='wocloudCenterNoLeftPad'>-</td>
						<td class='wocloudCenterNoLeftPad'>-</td>
						<td class='wocloudCenterNoLeftPad'>-</td>
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
					
					'Remaining' => '<td class="wocloudCenterNoLeftPad">-</td>',
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
						$count[$status] = "<td class='wocloudCenterNoLeftPad' style='color:#c00; background-color:#FF9; font-weight:bold;'>$count[$status]</td>";
					elseif ($count[$status] == '0')
						$count[$status] = "<td class='wocloudCenterNoLeftPad' style='color:#c00; font-weight:bold;'>$count[$status]</td>";
					elseif ($count[$status] == 1)
						$count[$status] = "<td class='wocloudCenterNoLeftPad' style='color:#B24700; font-weight:bold;'>$count[$status]</td>";
					else
						$count[$status] = "<td class='wocloudCenterNoLeftPad'>$count[$status]</td>";
				}
				else
					$count['Remaining'] = '<td class="wocloudCenterNoLeftPad">-</td>';

				if ($count[$status = 'Expiring'] !== '-')
					$count[$status] = "<span class='wocloudIconWarning wocloudUserErrorsText'>$count[$status]</span>";
			}

			echo <<<EOD
						<tr class='stripe$color'>
							<td class='wocloudCenterNoLeftPad' style='border-left:none'>{$count['Licensed']}$input</td>
							<td class='wocloudCenterNoLeftPad'>{$count['Used']}</td>
							{$count['Remaining']}
							<td class='wocloudCenterNoLeftPad'>{$count['Expiring']}</td>
							<td class='wocloudCenterNoLeftPad'>{$count['Expired']}</td>
							<td class='wocloudCenterNoLeftPad' $expireStyle>{$count['Expires']}</td>
							<td>$human</td>
						</tr>
EOD;
		}
		echo "
					</table>
				</div><!-- dataTable -->\n"; 
		if (ZMC::$registry->dev_only)
			echo "
				<div class='wocloudButtonBar'><small>Expiration:
					YYYY: <input class='wocloudUltraShortInput' type='text' name='Y' />
					MM:   <input class='wocloudUltraShortInput' type='text' name='M' />
					DD:   <input class='wocloudUltraShortInput' type='text' name='D' /> 
					<input type='submit' name='submit' value='Create License' />
					</small>
				</div></form>
			";
		echo "
			</div><!-- wocloudFormWrapper -->
		</div><!-- wocloudLeftWindow -->\n";
	}
}

function showLicensesBy($pm, $table, $by, $for)
{	
	$caption = "$by 中已使用的许可证";
	$licenses = $pm->licenses;
	if ($by === 'Device'){
		$caption = "已被备份集使用的设备";
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

    ZMC::titleHelpBar($pm, $caption, null, 'wocloudTitleBarTable');
	echo <<<EOD
		<div class="dataTable centerHeadings">
			<table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody>
				<tr>
					<th width='80' scope='col'>$for</th>
					<th width='155' scope='col' class="leftHeading">$by</th>
					<th width='80' scope='col'>总备份集数目</th>
					<th t scope='col' class="leftHeading">$for</th>
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
					<td class='wocloudCenterNoLeftPad'>$totalUsed</td>
					<td>$humanTypeGroup</td>
					<td class='wocloudCenterNoLeftPad'>$count</td>
					<td>$hostList</td>
				</tr>
EOD;
	}
	echo "
			</table>
		</div><!-- dataTable -->\n";
}
