<?













global $pm;
$action = rtrim($pm->state, '012');
echo "\n<form method='post' action='$pm->url'>\n";
?>
<div class="wocloudWindow">
    <div class="wocloudTitleBar">
	<?
		$objectType = isset($pm->form_type) ? ' ' . $pm->form_type['name'] : '';
		$preposition = 'in';
		if ($action === 'Copy')
			$preposition = 'to';
		if (isset($pm->form_type))
//			echo "$action Object ", ZMC::escape($objectType), " (", ZMC::escape($pm->form_type['category']), ") $preposition list: ", ZMC::escape($pm->selected_name);
			echo "在备份集 ",ZMC::escape($pm->selected_name),"配置",ZMC::escape($objectType)," 文件系统 " ;
		if ($pm->state === 'Create1')
			echo '新增';
//		elseif ($pm->offsetExists('form_type'))
//			echo '<div style="float:right; margin-right:83px;"><small>支持: <a href="',
//				ZMC_HeaderFooter::$instance->getUrl('Admin', 'licenses') ,
//				'">', $pm->licensesRemaining, "</a></small></div>\n";
		?>
	</div>
<!--	<a class="wocloudHelpLink" id="wocloudHelpLinkId" href="http://www.wocloud.cn" target="_blank"></a>-->
<?
if ($pm->state === 'Create1')
{
	?>
	<div wrapperCreate1 class="wocloudFormWrapper" style="padding:20px 0 20px 200px; width:auto; border-top:0px;">
		<img class="wocloudWindowBackgroundimageRight"src="/images/3.1/add.png" />
		<input type="hidden" name="action" value="Create2" />
	<?
	$i=0;
	$prettyNames2types = ZMC_Type_What::getPrettyNames();
	$zmcTypeApps = ZMC_Type_What::get();
    echo "<table><tr>";
    foreach(array('File Systems', 'Databases') as $category)
    {
        $i++;
        if($category=="File Systems"){
            echo "<td><font color='blue'>文件系统：</font></td><td><select name='selection$i' style='margin-right:20px' onchange=\"if (this.value != '') this.form.submit();\"><option value=''>请选择...</option>";
        }
        if($category=="Databases"){
            echo "<td><font color='blue'>数据库：</font></td><td><select name='selection$i' style='margin-right:20px' onchange=\"if (this.value != '') this.form.submit();\"><option value=''>请选择...</option>";
        }
		$options = array();
		foreach($zmcTypeApps as $zmcType => $info)
			if ($info['category'] === $category)
				$options[$prettyNames2types[$zmcType]] = $zmcType;

		ksort($options);
		foreach($options as $name => $zmcType)
		{
            if ($name === 'Oracle on Windows')
                continue;
			$disabled = '';
			$type = $zmcTypeApps[$zmcType]['license_group'];
			if (	($name === 'vmware' && empty(ZMC::$registry->vcli))
				||	empty($pm->lstats['licenses']['zmc']['Licensed'][$type])
				||	isset($pm->lstats['over_limit'][$type]))
				$disabled = ' disabled="disabled" ';
			echo "\t\t\t\t<option value='$zmcType' $disabled>$name</option>\n";
		}
		echo "\t\t\t</select>\n";
        echo "</td>";
	}
	?>
    </tr></table>
	</div><!-- wocloudFormWrapper -->
	<?
}
else 
{
	?>
	<input type="hidden" name="action" value="Create2" />
	<input type="hidden" name="selection1" value="<?= $pm->form_type['_key_name'] ?>" />
	<div wrapperCreate2 class="wocloudFormWrapperRight <?= $pm->form_type['form_classes'] ?>" style="min-height:70px;">
		<img class="wocloudWindowBackgroundimageRight"src="/images/3.1/edit.png" />
		<?= $pm->form_html ?>
	</div><!-- wocloudFormWrapper -->
	<?
	if (!empty($pm->form_advanced_html))
		ZMC_Loader::renderTemplate('formAdvanced', $pm);
	?>
	<div class="wocloudButtonBar" style="position:relative;">
		<button id="zmcSubmitButton" type="submit" name="action" value="<? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? 'Update' : 'create'); ?>" /><? echo (($pm->state === 'Edit' || $pm->state === 'Update') ? '更新' : '新增'); ?></button>
		<? if ($pm->state === 'Edit')
//			echo '<input id="addButton" type="submit" name="action" value="create" disabled="disabled" />';
			echo '<button id="addButton" type="submit" name="action" value="create" disabled="disabled" />'.'新增</button>';
		?>
        <button type="submit" value="Discover" id="discoverButton" name="action"/>扫描</button>
        <button type="submit" value="Cancel" id="cancelButton" name="action"/>取消</button>
	</div>
<?
}
?>
</div><!-- wocloudWindow -->
<?



if (empty($pm->rows))
	return print("<div style='height:250px;'>&nbsp;</div>\n</form>\n\n\n");

$only1user = (ZMC_User::count() === 1);
ZMC::titleHelpBar($pm, $pm->goto . "查看、添加、编辑和删除备份集 " . $pm->selected_name."  中的备份项", 'DLE+Table', 'wocloudTitleBarTable');
?>
	<div class="dataTable">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<? ZMC_Form::thAll() ?>
				<th title='类型'>
					<a href='<?= $pm->colUrls['property_list:zmc_type'] ?>'>类型<? if ($pm->sortImageIdx == 'property_list:zmc_type') echo $pm->sortImageUrl; ?></a></th>
				<? if (!empty($pm->aliases))
						echo "<th title='别名 (默认是目录名)'><a href='{$pm->colUrls['disk_name']}'>别名",
							($pm->sortImageIdx == 'disk_name' ? $pm->sortImageUrl : ''), "</a></th>\n";
					if (!empty($pm->comments))
						echo "<th title='备注'><a href='{$pm->colUrls['property_list:zmc_comments']}'>备注",
							($pm->sortImageIdx == 'property_list:zmc_comments' ? $pm->sortImageUrl : ''), "</a></th>\n";
				?>
				<th title='客户端名/ 备份项状态检查' style='min-width:200px'>
					<a href='<?= $pm->colUrls['host_name'] ?>'>客户端名/ 备份项状态检查<? if ($pm->sortImageIdx == 'host_name') echo $pm->sortImageUrl; ?></a></th>
				<th title='备份目录'>
					<a href='<?= $pm->colUrls['disk_device'] ?>'>备份目录<? if ($pm->sortImageIdx == 'disk_device') echo $pm->sortImageUrl; ?></a></th>
				<? if (!empty($pm->templates))
						echo "<th title='模板名'><a href='{$pm->colUrls['property_list:zmc_dle_template']}'>模板",
							($pm->sortImageIdx == 'property_list:zmc_dle_template' ? $pm->sortImageUrl : ''), "</a></th>\n";
				?>
				<th title='# L0 备份镜像'>
					<a href='<?= $pm->colUrls['L0'] ?>'># L0<? if ($pm->sortImageIdx == 'L0') echo $pm->sortImageUrl; ?></a></th>
				<th title='# L1+ 备份镜像'>
					<a href='<?= $pm->colUrls['Ln'] ?>'># L1+<? if ($pm->sortImageIdx == 'Ln') echo $pm->sortImageUrl; ?></a></th>
				<th title='客户端版本'>
					<a href='<?= $pm->colUrls['property_list:zmc_amcheck_version'] ?>'>AE版本
					<? if ($pm->sortImageIdx == 'property_list:zmc_amcheck_version') echo $pm->sortImageUrl; ?></a></th>
				<th title='客户端操作系统'>
					<a href='<?= $pm->colUrls['property_list:zmc_amcheck_platform'] ?>'>操作系统<? if ($pm->sortImageIdx == 'property_list:zmc_amcheck_platform') echo $pm->sortImageUrl; ?></a></th>
				<th title='加密模式'>
					<a href='<?= $pm->colUrls['encrypt'] ?>'>加密<? if ($pm->sortImageIdx == 'encrypt') echo $pm->sortImageUrl; ?></a></th>
				<th title='压缩模式'>
					<a href='<?= $pm->colUrls['compress'] ?>'>压缩<? if ($pm->sortImageIdx == 'compress') echo $pm->sortImageUrl; ?></a></th>
				<th title='上次编辑时间'>
					<a href='<?= $pm->colUrls['property_list:last_modified_time'] ?>'>上次编辑<? if ($pm->sortImageIdx == 'property_list:last_modified_time') echo $pm->sortImageUrl; ?></a></th>
				<? if (!$only1user) { ?>
				<th title='上次编辑人'>
					<a href='<?= $pm->colUrls['property_list:last_modified_by'] ?>'>上次编辑人<? if ($pm->sortImageIdx == 'property_list:last_modified_by') echo $pm->sortImageUrl; ?></a></th>
				<? } ?>
			</tr>
<?
$poll = $i = 0;


foreach ($pm->rows as $row)
{
	if (!empty($row['zmc_status']) && $row['zmc_status'] === 'deleted')
	{
		$deleted = true;
		$color = (($i++ % 2) ? 'stripeGrayDeleted':'stripeDeleted');
		echo "<tr class='$color' onclick=\"window.confirm('已经删除的备份项不能被编辑.'); return false;\">\n";
	}
	else
	{
		if (!empty($row['strategy']) && ($row['strategy'] === 'skip'))
		{
			$skipped = true;
			$color = (($i++ % 2) ? 'stripeGraySkip':'stripeWhiteSkip');
		}
		else
			$color = (($i++ % 2) ? 'stripeGray':'');
		echo "<tr style='cursor:pointer' class='$color' onclick=\"noBubble(event); window.location.href = '$pm[url]?edit_id=" . urlencode($row['natural_key']) . "&amp;action=Edit'; return true;\">\n";
	}
	echo ZMC_Form::tableRowCheckBox($row['natural_key']);

	foreach ($pm->columns as $key)
	{
		$escaped = (isset($row[$key]) ? ZMC::escape($row[$key]) : '');
		$escapedTd = "<td>$escaped</td>\n";
//        echo '<td hidden="hidden">'.$key.'--->>>'.$escaped.'</td>';
		switch($key)
		{
			case 'natural_key':
			case 'property_list:zmc_amcheck':
			case 'property_list:zmc_amcheck_date':
			case 'property_list:zmc_status':
			case 'strategy':
			case 'uid':
				break;

			case 'property_list:zmc_amcheck_version':
				echo "<td>$escaped", (empty($row['property_list:zmc_amcheck_app']) ? '':'/'.$row['property_list:zmc_amcheck_app']), "</td>\n";
				break;

			case 'property_list:zmc_disklist':
				break;
				$displayName = ZMC_BackupSet::displayName($row[$key]);
		        if (ZMC::$registry->advanced_disklists)
					echo '<td><a onclick="noBubble(event)" href="', ZMC_HeaderFooter::$instance->getUrl('Backup', 'list'),
						'?id=', urlencode($row[$key]), "\">$displayName</a></td>\n";
				else
					echo "<td>$displayName</td>\n";
				break;

			case 'property_list:last_modified_time':
				if ($escaped === '')
					echo "<td>-</td>\n";
				else
					echo '<td>', ZMC::escape(substr($row[$key], 0, -3)), "</td>\n";
				break;

			case 'disk_name':
				if (empty($pm->aliases))
					break;
				if ($row[$key] === $row['disk_device'])
					echo "<td>-</td>\n"; 
				else
					echo $escapedTd;
				break;

			case 'property_list:zmc_type':
				echo '<td>';
				if (isset($pm->lstats['over_limit'][$row[$key]]))
					echo '<img style="vertical-align:text-top; padding:0; margin:0" src="/images/global/calendar/icon_calendar_failure.gif" title="系统注册已过期，备份项被停用" />';
				echo ZMC_Type_What::getName($row[$key]), "</td>\n";
				break;

			case 'creation_date':
				echo "<td>", ZMC::escape(substr($row[$key], 0, -9)), "</td>\n";
				break;

			case 'live':
				echo "<td>", $row[$key] ? 'Yes' : 'No', "</td>\n";
				break;

			case 'property_list:zmc_comments':
				if (!empty($pm->comments))
					echo "<td>", ZMC::moreExpand(ZMC::escape($row[$key]), 20, '&gt;&gt;'), "</td>\n";
				break;

			case 'host_name':
				//$escaped = '<a onclick="noBubble(event)" href="/ZMC_Admin_Advanced?form=adminTasks&amp;action=Apply&amp;command=amadmin+' . ZMC::escape($pm->selected_name) . '+find+' . $row[$key] . "\">$escaped</a>";
				$escaped = '<a' . "\">$escaped</a>";
				$escapedTd = "<td>$escaped</td>\n";
				$last_modified_time = -1;
				if (!empty($row['property_list:last_modified_time']))
					$last_modified_time = ZMC::mktime($row['property_list:last_modified_time']);

				$zmc_amcheck_date = 0;
				if (!empty($row['property_list:zmc_amcheck_date']))
					$zmc_amcheck_date = ZMC::mktime($row['property_list:zmc_amcheck_date']);

				if (!empty($row['property_list:zmc_amcheck']) && !strncmp($row['property_list:zmc_amcheck'], 'checking', 8))
				{
					$poll++;
					echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_progress.gif' /> $escaped</td>\n";
					break;
				}

				if ($last_modified_time > $zmc_amcheck_date)
				{
					echo $escapedTd;
					break;
				}

				if (!empty($row['property_list:zmc_amcheck']))
				{
					$err = null;
					$icon = 'warning';
					
					if (false !== strpos($row['property_list:zmc_amcheck'], 'selfcheck request failed: Connection refused'))
					{
						$err = 'client refused connection';
						$icon = 'failure';
					}
					elseif (false !== strpos($row['property_list:zmc_amcheck'], 'resolve_hostname'))
					{
						if (false !== strpos($row['property_list:zmc_amcheck'], 'Name or service not known'))
							$err = 'client hostname not found';
					}
					elseif (false !== strpos($row['property_list:zmc_amcheck'], 'can not stat'))
					{
						$err = ZMC::escape("location not found: $row[disk_device]");
						$row['property_list:zmc_amcheck'] .= ' 1 problem found'; 
						$icon = 'failure';
					}

					if (empty($err) && strpos($row['property_list:zmc_amcheck'], ' 0 problems found'))
						echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_success.gif' /> $escaped</td>\n";
					elseif (empty($err) || false === strpos($row['property_list:zmc_amcheck'], ' 1 problem')) 
						echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_failure.gif' /> $escaped</td>\n";
					else
					{
						echo "<td
						onMouseOut=\"if (!(this.saveHTML === undefined) && !(this.saveState === undefined)) { delete this.saveState; this.innerHTML = this.saveHTML }\"
						onMouseOver=\"if (this.saveHTML === undefined) this.saveHTML = this.innerHTML;
						   	if (this.saveState === undefined)
							{ this.saveState = 1; this.innerHTML='<img style=vertical-align:text-top;padding:0;margin:0 src=/images/global/calendar/icon_calendar_$icon.gif /><span class=stripeYellow>&nbsp;$err&nbsp;</span>'; } \"
						><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_$icon.gif' /> $escaped</td>\n";
					}
				}
				elseif ($zmc_amcheck_date >= $last_modified_time)
					echo "<td><img style='vertical-align:text-top; padding:0; margin:0' src='/images/global/calendar/icon_calendar_success.gif' /> $escaped</td>\n";
				else
					throw new ZMC_Exception('column Host Name malformed');
				break;

			case 'property_list:last_modified_by':
				if (!$only1user) 
					echo $escaped === '' ? "<td>-</td>\n" : $escapedTd;
				break;

			case 'disk_device':
				echo '<td>', str_replace('/', '<wbr/>/', ZMC::escape($row[$key])), '</td>';
				break;

			case 'property_list:zmc_dle_template':
				if (empty($pm->templates))
					break;
				if (empty($row[$key]))
				{
					echo "<td>-</td>\n";
					break;
				}
            case 'encrypt':
                if($escaped=='none')
                    echo "<td>  </td>\n";
                elseif($escaped=='client')
                    echo "<td>客户端</td>\n";
                elseif($escaped=='server')
                    echo "<td>服务端</td>\n";
                break;
            case 'compress':
                if($escaped=='none')
                    echo "<td>  </td>\n";
                elseif($escaped=='client fast')
                    echo "<td>客户端最快</td>\n";
                elseif($escaped=='client best')
                    echo "<td>客户端最佳</td>\n";
                elseif($escaped=='server fast')
                    echo "<td>服务端最快</td>\n";
                elseif($escaped=='server best')
                    echo "<td>服务端最佳</td>\n";
                elseif($escaped=='client custom')
                    echo "<td>客户端自定义</td>\n";
                elseif($escaped=='server custom')
                    echo "<td>服务端自定义</td>\n";
                break;
			default:
				echo $escapedTd;
		}
	}
	echo "</tr>\n";
}
echo "      </table>
	    </div><!-- dataTable -->\n\n";

if (!empty($poll) && $pm->state === 'Create1')
	echo '<script>setTimeout(function () { window.location.replace(window.location.pathname) }, 5000)</script>';

$html = '';
if (!empty($deleted) || !empty($skipped))
{
	$html = '<div style="padding:4px; float:right;"><b>&nbsp;&nbsp;&nbsp;行传:</b> ';
	if (!empty($deleted))
		$html .= '<span class="stripeWhite" style="text-decoration: line-through">删除</span>';

	if (!empty($skipped))
		$html .= '<span class="stripeGraySkip">跳过</span>';

	$html .= "</div>\n";
}

ZMC_Loader::renderTemplate('tableButtonBar', array('goto' => $pm->goto,
	'buttons' => array(
		'Refresh Table' => true,
		'Edit' => true,
		
		'Check Hosts' => "onclick=\"var sel=true; var o=gebi('dataTable').getElementsByTagName('input'); for(var i = 0; i < o.length; i++) { b = o.item(i); if (b.checked) sel=true; } if (sel) return true; return window.confirm('检查所有备份项?');\"",


		'Delete' => "onclick=\"return window.confirm('删除备份项不会删除备份文件。如果还有备份存在，备份项会标记为已删除，但是在本页面依然可见直到最后一个备份被删除。删除操作不能撤销，但备份集系统数据会被备份下，是否继续？')\"",
	),
	'html' => $html
));

echo "\n</form>\n";
