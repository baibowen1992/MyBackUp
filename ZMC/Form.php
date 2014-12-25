<?













class ZMC_Form {

protected function buildForm(ZMC_Registry_MessageBox $pm, $form = array(), $formVals = null, $add = false, $filterClass = 'ZMC_DeviceFilter')
{
	if (empty($pm->form_type['form']))
		ZMC::quit($pm->form_type); 

	if (!empty($formVals))
	{
		ZMC::flattenArray($inputVals, $formVals);
		$formVals = $filterClass::filter($pm, 'output', $inputVals, true);
		unset($inputVals);
	}
	
	foreach($pm->form_type['form'] as $fieldName => &$meta)
	{
		$class = '';
		if (empty($meta))
			continue;

		
		if ($meta['worm'] && !$add)
		{
			$meta['enabled'] = false;
			$meta['mutable'] = false;
		}

		
		if (is_array($meta['default']) && count($meta['default']) === 2 && isset($meta['default'][0])
			&& $meta['default'][0][0] === 'Z' && $meta['default'][0][1] === 'M' && $meta['default'][0][2] === 'C')

					$meta['default'] = call_user_func($meta['default'], $pm);

		
		if (is_array($meta['default']) && count($meta['default']) === 2 && isset($meta['default'][1])
			&& $meta['default'][1][0][0] === 'Z' && $meta['default'][1][0][1] === 'M' && $meta['default'][1][0][2] === 'C'){
			$meta['default'] = $meta['default'][0]. "-".call_user_func($meta['default'][1], $pm);
		}	

		$field = "\n";
		if (ZMC::$registry->debug)
			$field .= "<!-- vermpaw:$meta[vermpaw] -->";
		$visible = $meta['visible']; 
		if ($meta['form_type'] === 'html')
		{
			if ($visible)
				$field .= $meta['default'];
		}
		elseif ($visible)
		{
			$disabledAttribute = '';
			if (!$meta['enabled'])
			{
				$disabledAttribute = ' disabled="disabled" ';
				if ($meta['required'] && !$meta['mutable']) 
					
					$field .= self::hiddenInput($meta, $formVals, $fieldName, true);
			}

			if ($meta['label'] === false)
				$field .= "\n\t\t\t\t<div style='display:inline;' id='{$fieldName}_div'>\n";
			elseif (!empty($meta['label'])) {
                //added by zhoulin 20141123,hidden   设备扫描 位置锁定 两个参数
                if (ZMC_User::hasRole('Administrator')){
                    $field .= "\n\t\t\t\t<div class='p' id='{$fieldName}_div'><label for='$fieldName"
                        . (strpos($meta['label'], $fieldName . '_box') ? "_box'" : "'");}
                else{
                    if (($fieldName == 'device_property_list:S3_BUCKET_LOCATION') || ($fieldName == 'taperscan:plugin')||
                        ($fieldName == 'holdingdisk_list:zmc_default_holding:strategy')) {
                        $field .= "\n\t\t\t\t<div class='p' style='display: none' id='{$fieldName}_div'><label for='$fieldName"
                            . (strpos($meta['label'], $fieldName . '_box') ? "_box'" : "'");
                    }
                    else{
                        $field .= "\n\t\t\t\t<div class='p' id='{$fieldName}_div'><label for='$fieldName"
                            . (strpos($meta['label'], $fieldName . '_box') ? "_box'" : "'");}
                    if (($fieldName == 'holdingdisk_list:zmc_default_holding:filesystem_reserved_percent')||($fieldName == 'holdingdisk_list:zmc_default_holding:directory')){
                        $disabledAttribute = ' disabled="disabled" ';
                    }
                }
				$field .= (($meta['required'] && $meta['enabled']) ? 'style=\'font-weight:bold;\'>' : '>');
				$required = '<span class="required">*</span>:';
				if (!($pos = strpos($meta['label'], ':')))
					$field .= $meta['label'] . ($meta['required'] ? $required : ':');
				elseif ($meta['required'])
				{
					$meta['label'][$pos] = "\0"; 
					$field .= str_replace("\0", $required, $meta['label']);
				}
				else
					$field .= $meta['label'];
				$field .= "</label>\n";
			}

			if (!empty($meta['html_before']))
				$field .= $meta['html_before'];

			if (!strncasecmp($meta['form_type'], 'checkbox', 8))
			{
				

				if ($meta['form_type'] !== 'checkbox') 
					$class = 'class="' . str_replace('checkbox', 'zmc', $meta['form_type']) . '"';

				if (!isset($meta['default']['checked']))
					throw new ZMC_Exception("$fieldName misconfigured (missing 'checked' default key)"); 

				if($fieldName == "private:google_durable_reduced_avaibility_storage"){
					if(!empty($formVals[$fieldName])){
						$disabledAttribute = ' onclick="return false;" ';
					}
				}
				$checked = (($meta['default']['checked'] === 'off') || empty($meta['default']['checked'])) ? false : true;
				if ($formVals && isset($formVals[$fieldName]))
					if ($formVals[$fieldName] === $meta['default']['on'])
						$checked = true;
					elseif ($formVals[$fieldName] === $meta['default']['off'])
						$checked = false;
					elseif ($formVals[$fieldName] === 'on')
						$checked = true;
					elseif ($formVals[$fieldName] === 'off')
						$checked = false;
					else 
						$checked = !empty($formVals[$fieldName]);
				if (!array_key_exists('on', $meta['default']) || ($meta['default']['on'] === null))
					$on = null; 
				elseif ($meta['default']['on'] === true)
					$on = 'on';
				elseif (empty($meta['default']['on']))
					$on = 'off'; 
				else
					$on = ZMC::escape($meta['default']['on']);

				if (!array_key_exists('off', $meta['default']) || ($meta['default']['off'] === null))
					$off = null; 
				elseif (empty($meta['default']['off']))
					$off = 'off'; 
				else
					$off = ZMC::escape($meta['default']['off']);

				$checkedValue = ((!$meta['mutable'] && $checked) ? $on : $off);

				$field .= "<input type='hidden' value='$checkedValue' name='$fieldName' />"
					. "<input id='$fieldName'
					$class
					name='$fieldName'
					type='checkbox'
					$disabledAttribute
					value='$on'
					$meta[attributes]
					title='$meta[tip]'
					" . ($checked ? ' checked="checked" ' : '') . ' />';
			}
			elseif (!strncasecmp($meta['form_type'], 'select', 6)) 
			{
				if ($meta['form_type'] !== 'select') 
					$class = 'class="' . str_replace('select', 'zmc', $meta['form_type']) . '"';

				$field .= "<select id='$fieldName'
					$class
					$disabledAttribute
					name='$fieldName'
					$meta[attributes]
					title='$meta[tip]'
					>\n";

				$found = '';
				if ($formVals && isset($formVals[$fieldName]))
				   	if (isset($meta['default'][$formVals[$fieldName]]))
						$found = $formVals[$fieldName];
					elseif (isset($meta['default']['0']))
						$found = 0; 

				
				 if($fieldName == 'device_property_list:BLOCK_SIZE')
					if(!isset($meta['default'][$formVals[$fieldName]]))
						$found = 256;

				if($fieldName == 'device_property_list:NB_THREADS_BACKUP' || $fieldName == 'device_property_list:NB_THREADS_RECOVERY')
					if(!isset($meta['default'][$formVals[$fieldName]]))
						$found = 4;
				foreach($meta['default'] as $key => $value)
				{
					$field .= '<option ';
					if ("$key" === "$found")
						$field .= ' selected=\'selected\' ';
					$field .= " value='" . ZMC::escape($key) . "'>"
						. (is_array($value) ? ZMC::escape($value['label']) : ZMC::escape($value)) . "</option>\n";
				}
				$field .= "</select>";
			}
			elseif (!strncasecmp($meta['form_type'], 'multiple', 8)) 
			{
				if ($meta['form_type'] !== 'multiple') 
					$class = 'class="' . str_replace('multiple', 'zmc', $meta['form_type']) . '"';

				
				if($pm->binding['_key_name'] === "changer_library"){
					$verified_tape = ZMC::$registry->etc_amanda. $pm->binding['config_name']. "/binding-".$pm->binding['private']['zmc_device_name'].".verify_tape_drive_status";
					if(file_exists($verified_tape)){
						$pm->binding['verified_tape_status'] = include($verified_tape);
						
						
					}
					$verify_lock = ZMC::$registry->etc_amanda. $pm->binding['config_name']. "/binding-".$pm->binding['private']['zmc_device_name'].".verify_tape_drive_lock";
					if(file_exists($verify_lock)){
						$verified_tape_lock = true;
					}
				}

				$field .= "<fieldset><legend>Identify Drive Elements to Use</legend>";
				$field .= "<table width='300'><tr><th style='text-align:left;'>Skip?</th><th style='text-align:left;'>Drive Slot</th><th style='text-align:left;'>OS Drive Path</th></tr>\n";
				foreach($formVals as $key => $ignored){
					if (!strncasecmp($key, 'changer:tapedev:Other', 21))
						continue;
					if (!strncmp($key, 'changer:tapedev:', 16))
					{
						$drive = substr($key, strrpos($key, ':')+1);
						$meta['default'][$drive] = $formVals[$key];
					}
				}
				asort($meta['default']);
				foreach($meta['default'] as $key => $value)
				{
					$other = !strncmp($key, 'Other', 5);
					$rid = 'driveslot_' . basename($key); 
					$id = "{$fieldName}[{$key}]";
					$field .= '<tr>';
					if ($formVals && isset($formVals[$k = "$fieldName:$key"]))
						$value = $formVals[$k];
					$checked = (($other || ($value === 'skip')) ? 'checked="checked"':'');
					$field .= "<td><input $checked type='checkbox' onclick=\"var o = gebi('$id');";
					$field .= "if (this.checked) { zmcRegistry.$rid = o.value; o.value ='skip'; }\n";
					$field .= "else { o.value = ((zmcRegistry['$rid'] !== undefined) ? zmcRegistry.$rid : '?') }\n";
					$field .= '" style="float:none;" /></td>';
					$field .= "<td><input type='text' $class id='$id' name='$id' value='$value' style='float:none;'></td>";
					if ($other)
						$field .= "<td><input type='text' class='wocloudShortInput' name='$key' value='/dev/' />";
					else
					{
						$field .= '<td><a href="#" onclick="YAHOO.zmc.utils.show_mt(this.innerHTML); return false;">';
						$field .= ZMC::escape($key);
						$field .= '</a>';
						if ($value !== 'skip'){
							$warning = false;
							if(isset($pm->binding['verified_tape_status'])){
							if($value != $pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['current_drive']){
								$field .= '&nbsp;<img alt="warning" title="Tape drive slots looks to be modified after the last update. Please re-verify tape drive by clicking on \'Update & Verify Tape Drive\' button. " src="/images/global/calendar/icon_calendar_warning.gif">';
								$warning = true;
							}
							}

							if(isset($pm->binding['verified_tape_status']) && !empty($pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['good']) && !$warning){
								$field .= '&nbsp;<img alt="Messages" src="/images/global/calendar/icon_calendar_success.gif">';
							}
							if(isset($pm->binding['verified_tape_status']) && !empty($pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['error']) && !$warning){
								$field .= '&nbsp;<img alt="error" title="'.$pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['error'].'" src="/images/global/calendar/icon_calendar_failure.gif">';
							}
							if(isset($pm->binding['verified_tape_status']) && !empty($pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['hint']) && !$warning){
								$field .= '&nbsp;<img alt="hint" title="'.$pm->binding['verified_tape_status'][$pm->binding['private']['zmc_device_name']][$key]['hint'].'" src="/images/icons/icon_info.png">';
							}
							if($verified_tape_lock){
								$this->autoRefreshPage();
								$field .= "&nbsp;<img id='progress_spinner' title='Verification of Tape drive is in Progress' src='/images/global/calendar/icon_calendar_progress.gif'>";
							}
						}

					}
					$field .= '</td></tr>';
				}
				$field .= "</table>";
				if(isset($pm->binding['verified_tape_status']) && !empty($pm->binding['verified_tape_status']['last_verified'])){
					$field .= "<br /><small>Last verified on : " . $pm->binding['verified_tape_status']['last_verified']."</small>";
				}
				$field .= "</fieldset>\n";
			}
			elseif (!strncasecmp($meta['form_type'], 'radio', 5))
			{
				if ($meta['form_type'] !== 'radio') 
					$class = 'class="' . str_replace('radio', 'zmc', $meta['form_type']) . '"';
				
				
				foreach($meta['default'] as $key => $value)
				{
					$checked = '';
					$val = ZMC::escape($key);
					$formContainsField = $formVals && isset($formVals[$fieldName]);
					if (	($formContainsField  && $formVals[$fieldName] == $key)
						||	(!$formContainsField && $value['default']))
						$checked = ' checked="checked" ';
					
					$field .= "\n<div style='float:left;' id='zmc" . str_replace(':', '', ucFirst($fieldName)). strtok(ucFirst($key), ' ') . "'><input id='label_{$fieldName}_$key'
						name='$fieldName'
						type='radio'
						$class
						value='$val'
						$checked
						$meta[attributes]
						/><label class='wocloudShortestLabel' for='label_{$fieldName}_$key'> " . ZMC::escape($value['label']) . "</label></div>\n";
						
				}
				
				
			}
			elseif (!strncasecmp($meta['form_type'], 'textarea', 8))
			{
				if ($meta['form_type'] !== 'textarea')
					$class = 'class="' . str_replace('textarea', 'zmc', $meta['form_type']) . '"';

				$field .= "<textarea id='$fieldName'
					name='$fieldName'
					$class
					$disabledAttribute
					$meta[attributes]
					title='$meta[tip]'>"
					. str_replace('\\n', "\r\n", ($formVals && isset($formVals[$fieldName]) ? $formVals[$fieldName] : ZMC::escape($meta['default'])))
					. '</textarea>';
			}
			else 
			{
				$ftype = 'text';
				if (!strncasecmp($meta['form_type'], 'password', 8))
				{
					$ftype = 'password';
					if ($meta['form_type'] !== 'password')
						$class = 'class="' . str_replace('text', 'zmc', $meta['form_type']) . '"';
				}
				elseif (!empty($meta['form_type']))
					if (!strncasecmp($meta['form_type'], 'text', 4))
						$class = 'class="' . str_replace('text', 'zmc', $meta['form_type']) . '"';
					else
						$class = "class='$meta[form_type]'";

				if($fieldName == 'device_property_list:MAX_SEND_SPEED'){
					if (!preg_match('/^[0-9.]*$/', $formVals[$fieldName]))
						$pm->addWarning("A value entered '$formVals[$fieldName]' for Max Upload Speed is too large. .");
				}   
				if($fieldName == 'device_property_list:MAX_RECV_SPEED'){
					if (!preg_match('/^[0-9.]*$/', $formVals[$fieldName]))
						$pm->addWarning("A value entered '$formVals[$fieldName]' for Max Download Speed is too large. .");
				}

				$field .= "<input id='$fieldName'
					$class
					type='$ftype'
					name='$fieldName'
					$disabledAttribute
					$meta[attributes]
					title='$meta[tip]'
					value='" . ($formVals && isset($formVals[$fieldName]) ? ZMC::escape($formVals[$fieldName]) : ZMC::escape($meta['default']))
					
					. "' />";
			}
			
			
			$field .= $meta['html_after'];

			if ($meta['html_after'] !== false) 
			
				
				$field .= "\n\t\t\t\t</div><!-- {$fieldName}_div -->\n\n";
		}
		else 
			$field .= self::hiddenInput($meta, $formVals, $fieldName);

		if (!empty($field))
			if ($meta['advanced'])
				$advanced[$meta['priority']] = $field;
			else
				$form[$meta['priority']] = $field;
	}
	ksort($form);
	$pm->form_html = '<input type="hidden" name="pm_state" value="' . urlencode($pm->state) . "\" />\n" . implode("\n", $form)
		. '<div style="clear:both;"></div>';
	if (!empty($advanced))
	{
		ksort($advanced);
		$pm->form_advanced_html = implode("\n", $advanced) . '<div style="clear:both;"></div>';
	}
}

protected function hiddenInput($meta, &$formVals, &$fieldName, $mirror = false)
{
	
	$field = "<input
		id='$fieldName'
		type='hidden'
		name='$fieldName'
		$meta[attributes]
		value='";
	$value = ($formVals && isset($formVals[$fieldName])) ? $formVals[$fieldName] : ZMC::escape($meta['default']);
	if ($mirror)
	{
		$formVals[$fieldName . '_disabled'] = $value; 
		$field .= ZMC::escape($value) . "' />";
		$fieldName .= '_disabled';
		return $field;
	}
	return $field . $value . "' />";
}

public static function tableRowCheckBox($id, $qualifier = '', $disabled = '', $visibility = '', $value = '')
{
	$checked = '';
	
	if (empty($qualifier))
	{
		if (isset(ZMC::$userRegistry['selected_ids']) && isset(ZMC::$userRegistry['selected_ids'][$id]))
			$checked = ' checked="checked" ';
	}
	$encName = urlencode($id);
	if ($disabled)
		$disabled = 'disabled="disabled" hasLabel="true"';

	if ($visibility)
		$visibility = 'visibility:hidden;';
	
	if ($value)
		$value = 'value="' . $value . '"';

	echo <<<EOD
			<td onclick='tdBoxClick(this, event)'><input $checked $disabled style='vertical-align:bottom; $visibility' type='checkbox' $value name='selected_ids{$qualifier}[{$encName}]' onclick='boxClick(this, event)' /></td>
EOD;
}

public static function thAll()
{
	?>
	<th title="选择">
		<a href="" onclick="YAHOO.zmc.utils.select_all_datatable_buttons(this); return false;">所有</a>
	</th>
	<?
}

protected static function getEditId(ZMC_Registry_MessageBox $pm, $idFormKey, $error = null)
{

	if (!empty($_REQUEST[$idFormKey]))
		$id = $_REQUEST[$idFormKey];
	elseif (!empty(ZMC::$userRegistry['selected_ids']))
		$id = key(ZMC::$userRegistry['selected_ids']);

	if (empty($id))
		if ($error)
			throw new ZMC_Exception($error); 
		else
			return false;

	ZMC_BackupSet::addEditWarning($pm); 
	return $id;
}











public static function &form2array(ZMC_Registry_MessageBox $pm, ZMC_Registry $type, array $post, array $result = array())
{
	
	foreach($type['form'] as $field => $meta)
	{
		if ($meta['form_type'] === 'html') 
			continue;

		if ($meta['form_type'] === 'textarea') 
			$post[$field] = str_replace(array("\r", "\n"), array('', '\n'), trim($post[$field]));

		if (!strncasecmp($meta['form_type'], 'multiple', 8))
		{
			$f = "$field:";
			$len = strlen($f);
			foreach(array_keys($post) as $key)
				if (!strncmp($f, $key, $len))
				{
					$post[$field][substr($key, $len)] = $post[$key];
					unset($post[$key]);
				}
		}

		$exists = array_key_exists($field, $post);
		
		
		if ($meta['required'] && (!$exists || $post[$field] === '')){
			if(preg_match('/Password/',$meta[label]))
				$pm->addError("A value is required for 'Password'.");
			else{
				if(preg_match('/Slot Range/', $meta[label]))
					$post[$field] = ' ';
				$pm->addError("参数 '$meta[label]' 需要一个值");
			}
		}

		if (!$meta['mutable']) 
		{
			if ($meta['required']) 
				$result[$field] = $post[$field]; 
			elseif ($meta['preset']) 
				if (!strncasecmp($meta['form_type'], 'checkbox', 8)){
					if(!empty($post['property_list:zmc_amanda_app'])){
						if(in_array($post['property_list:zmc_amanda_app'], array_values($meta['default']))){
							if(($post['property_list:zmc_amanda_app'] ==  $meta['default']['on']))
								$result[$field]   = $meta['default']['on'];
							elseif($post['property_list:zmc_amanda_app'] ==  $meta['default']['off'])
								$result[$field]   = $meta['default']['off'];

							continue;
						}
					
					}
					$result[$field] = ($meta['default']['checked'] ? $meta['default']['on'] : $meta['default']['off']);
				}
				else
					$result[$field] = $meta['default'];
			
			continue;
		}

		
		if (!strncmp($meta['form_type'], 'select', 6) && !empty($post[$field]) && $post[$field][0] === '-')
			unset($post[$field]); 

		if (!$exists || $post[$field] === '')
		{
			if (!$meta['preset'])
			{
				if ($exists && $post[$field] === '')
					$result[$field] = null; 
				continue; 
			}
			
			if (is_array($meta['default']))
			{
				if (!strncasecmp($meta['form_type'], 'checkbox', 8))
					$result[$field] = $meta['default']['off'];
				elseif (false === ($result[$field] = array_search(1, $meta['default'])))
					$result[$field] = null;
			}
			else
				$result[$field] = $meta['default'];
		}
		else 
			if (!strncasecmp($meta['form_type'], 'checkbox', 8))
				$result[$field] = $meta['default'][$post[$field]];
			else
				$result[$field] = $post[$field];
	}
	
	return $result;
}

protected function autoRefreshPage($interval = 30)
{
	$interval *= 1000;
	ZMC_HeaderFooter::$instance->injectYuiCode("setTimeout(\"gebi('refresh__button').click();\", $interval)");
}

protected function validateForm(ZMC_Registry_MessageBox $pm)
{
}

protected function buildFormWrapper(ZMC_Registry_MessageBox $pm, array $ignored = null)
{
	if (empty($pm->form_type) || (($pm->state !== 'Create2') && ($pm->state !== 'Use') && empty($pm->binding)))
	{
		if (ZMC::$registry->debug)
			ZMC::quit(array('Unknown object type (pm->form_type===false)' => $pm));
		ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex(__CLASS__ . ' - Unknown object type'), __FILE__, __LINE__);
	}
	$type = (empty($pm->binding) || empty($pm->binding['_key_name'])) ? $_REQUEST['_key_name'] : $pm->binding['_key_name'];
	$form = array(9000 => '<input type="hidden" name="_key_name" id="zmc_type" value="' . "$type\" />\n");
	$this->buildForm($pm, $form, empty($pm->binding) ? null : $pm->binding, $pm->state === 'Add' || $pm->state === 'Create2' || $pm->state === 'Use');
	if (empty($pm->form_advanced_id))
		$pm->form_advanced_id = uniqid('twirl');
	$id = $pm->form_advanced_id;
	ZMC_HeaderFooter::$instance->injectYuiCode("
		var o=gebi('private:zmc_show_advanced');
		if (o && (o.value === '1' || o.value === 'on')) YAHOO.zmc.utils.twirl('img_$id', 'div_$id');
	");
		
}
}
