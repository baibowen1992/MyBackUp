<?














class ZMC_Backup_Media extends ZMC_Backup
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		unset($_POST['selected_ids_mm']['99990000000000']); 
		ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - Manage Media', 'media');
		$pm->addDefaultInstruction('管理备份集使用的存储设备。请确保同一时间内仅有一个用户在本页面进行交互操作。在执行一个操作后请耐心等待操作成功完成再进行下一个操作。');
		$mediaPage = new self($pm);
		if (!empty($pm->selected_name)) ZMC_BackupSet::readConf($pm, $pm->selected_name);

		if($_POST['action'] == 'Update' || $_POST['action'] == 'Refresh Table'){
			if (!empty($_POST['dumpcycle']) && !empty($pm->edit['configuration_id']) && (intval($_POST['initial_retention']) != 0))
			{
				ZMC_Mysql::update('configurations', $config = array(
					'initial_retention' => intval($_POST['initial_retention']),
					'father_retention' => intval($_POST['father_retention']),
					'grandfather_retention' => intval($_POST['grandfather_retention']
				)), array('configuration_id' => $pm->edit['configuration_id']), "Can not find and update backup set '{$pm->selected_name}'");
				ZMC::merge($pm->edit, $config);
				ZMC_BackupSet::modifyConf($pm, $pm->selected_name, array('dumpcycle' => intval($_POST['dumpcycle'])));
				if (!empty($pm->selected_name)) ZMC_BackupSet::readConf($pm, $pm->selected_name);
				$mediaPage->getSelectedBinding($pm);
				$pm->binding['schedule']['dumpcycle'] = $_POST['dumpcycle'];
				$pm->binding['private']['initial_retention'] = $_POST['initial_retention'] .' days';
				$update = 'merge_and_apply';
				$result = ZMC_Yasumi::operation($pm, array(
					'pathInfo' => '/Device-Binding/' . ($update ? 'merge_and_apply/' : 'create/') . $pm->selected_name,
					'data' => array(
					'commit_comment' => $pm->tombstone . '|' . $pm->subnav . ' add/update device binding',
					'binding_name' => $pm->binding['private']['zmc_device_name'], 
					'binding_conf' => $pm->binding,
					),
				));
				$pm->merge($result);
				header("Location : ". $_SERVER['HTTP_REFERER']);
				exit;
			}
		}
		$pm->confirm_window = true;
		if($_POST['formName'] === 'confirmDialog' && $_POST['ConfirmationYes'] == 'Save Labels' && isset($_SESSION['confirm_post'])){
			$_POST = $_SESSION['confirm_post'];
			unset($_SESSION['confirm_post']);
			$pm->confirm_window = false;
		}elseif($_POST['formName'] === 'confirmDialog' && $_POST['ConfirmationNo'] == 'Cancel' && isset($_SESSION['confirm_post'])){
			$pm->state = "Edit";
			unset($_SESSION['confirm_post']);
			$_POST = array();
		}
		
		if ($template = $mediaPage->runState($pm))
			return $template;

		$mediaPage->getPaginator($pm);
		return 'BackupMedia';
	}

	protected function runState(ZMC_Registry_MessageBox $pm, $state = null)
	{
		if (!empty($state))
			$pm->state = $state;

		switch($pm->state)
		{
			case 'Verify Integrity': 
				return ZMC::redirectPage('ZMC_Report_Integrity', $pm);

			case 'Save Labels':
				
				foreach($_POST['selected_ids_lm'] as $key => &$value) 
					$value = trim($_POST['label'][$key]);

				$this->getSelectedBinding($pm);

				if ($pm->binding['dev_meta']['media_type'] === 'tape')
					$this->inventory($pm);

				$res = array();
				if(!empty($pm->binding['schedule']['tapelist']) && !empty($_POST['selected_ids_lm'])){
					foreach($_POST['selected_ids_lm'] as $new_slot => $new_label){
						foreach($pm->binding['schedule']['tapelist'] as $old_label => $old_label_details){
							if($new_slot === $old_label_details['slot']){
								if($new_label === $old_label)
									continue;
								$res[$old_label] = $old_label_details['slot'];
							}
						}
					}
				}
				if(!empty($res) && $pm->confirm_window == true){
					foreach($res as $i => $j)
						$pm->addError("Found label '$i' on slot #$j. Please drop the label manually from Backup|Media page. ");

					$pm->confirm_template = 'ConfirmationWindow';
					$pm->confirm_help = 'Confirm Label Media';
					foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
						$this->vtapesMessage($pm, $name);
					$pm->prompt ='Are you sure you want to Label Media?<br />';
					$pm->confirm_action = 'Save Labels';
					$_SESSION['confirm_post'] = $_POST;
					$pm->yes = 'Save Labels';
					$pm->no = 'Cancel';
					break;
				}
				$pm->state = 'Save Labels';
				$this->addSingleUserWarning($pm);
				ZMC_BackupSet::mediaOperation($pm, $pm->state, $_POST['selected_ids_lm'], array(
					'include_barcode' => empty($_POST['include_barcode']) ? false : true,
					'overwrite_media' => empty($_POST['overwrite_media']) ? false : true)
				);


				$this->runState($pm, 'Edit');
				break;

			case 'Abort Labeling':
				ZMC_BackupSet::mediaOperation($pm, $pm->state);
				$this->runState($pm, 'Edit');
				break;

			case 'Scan All Slots':
				ZMC_BackupSet::mediaOperation($pm, 'scan_slots', empty($_POST['selected_ids_lm']) ? array() : $_POST['selected_ids_lm']);
				$pm->confirm_template = 'ConfirmationWindow';
				$pm->addMessage("Scan in progress ..");
				$this->addSingleUserWarning($pm);
				$pm->prompt = 'When the changer has finished running, press "Done" to refresh the display of slot contents.';
				$pm->confirm_icon = 'progressbar';
				$pm->confirm_action = 'Edit';
				$pm->yes = 'Changer is Done';
				
				ZMC_HeaderFooter::$instance->injectYuiCode("YAHOO.zmc.utils.monitor('progress_status', '$pm->state', '/Backup/progress.php', 2000, 5000, '/ZMC_Backup_Media')");
				break;

			case 'Comment': 
				$pm->addError('Feature coming soon ..');
			case 'Drop':
			case 'Recycle':
			case 'Archive':
				if (empty($_POST['selected_ids_mm']))
				{
					$pm->addError("No media selected.");
					break;
				}
				$this->addSingleUserWarning($pm);
				$this->getSelectedBinding($pm);  
				ZMC_BackupSet::mediaOperation($pm, $pm->state, $_POST['selected_ids_mm'], $pm->binding);
				$this->runState($pm, 'Edit');
				break;

			case 'Explore':
				ZMC_Yasumi::operation($pm, array('pathInfo' => "/amadmin/get_records/" . $pm->selected_name, 'data' => array(), 'post' => null, 'postData' => null,), true);
				reset($_POST['selected_ids_mm']);
				$label = key($_POST['selected_ids_mm']);
				foreach($pm->records as $record)
					if ($record['tape_label'] === $label){
						ZMC::headerRedirect("/ZMC_Restore_What?client=$record[host]&disk_name=$record[disk_name]&date_time_human=" . ZMC::amandaDate2humanDate($record[datetime]) . ':59', __FILE__, __LINE__);
						exit(0);
					}
				$pm->addError('Backup image not found. Try the "Verify Integrity" option on the Backup|media page.');
				break;

			case 'Cancel':
				ZMC_BackupSet::cancelEdit();
				$pm->addWarning("编辑/新增  取消.");
				break;

			default:
				$this->edit($pm);
		}
	}

	



	public function getPaginator(ZMC_Registry_MessageBox $pm)
	{
		$this->getBindingList($pm);
		if (empty($pm->binding_list))
			return;

		if (empty(ZMC::$userRegistry['sort']))
			ZMC_Paginator_Reset::reset('config_name', false);

		
		foreach($pm->binding_list as &$binding)
			if (!empty($binding['schedule']))
			{
				if (strpos($binding['schedule']['schedule_type'], 'Days of the Month'))
				{
					$binding['schedule']['when'] = '';
					foreach($binding['schedule']['dom'] as $day => &$type)
						$binding['schedule']['when'] .= $day . ':' . $type[0] . ', ';
				}
				else
				{
					$binding['schedule']['when'] = '-|-|-|-|-|-|-';
					if (is_array($binding['schedule']['days']))
						foreach($binding['schedule']['days'] as $day => &$type)
							$binding['schedule']['when'][($day * 2)] = (empty($type) ? '-' : ucFirst($type[0]));
					else 
					{
						if (!ZMC::$registry->debug)
							ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex('Internal Scheduling Error'), __FILE__, __LINE__);
						$pm->addInternal("Internal Scheduling Error");
						ZMC::$registry->dev_only && $pm->addDetail('<pre>' . print_r($pm->binding, true) . '</pre>');
					}
				}

				$binding['schedule']['hours'] = implode(', ', array_keys(array_filter($binding['schedule']['hours'])));
				$binding['schedule']['full_hours'] = implode(', ', array_keys(array_filter($binding['schedule']['full_hours'])));
			}
		$flattened =& ZMC::flattenArrays($pm->binding_list);
		
		$paginator = new ZMC_Paginator_Array($pm, $flattened, $pm->cols = array(
			'config_name',
			'_key_name',
			'schedule:schedule_type',
			'schedule:archived_media',
			'schedule:dumpcycle',
			'schedule:desired_retention_period',
			'schedule:hours',
			'schedule:minute',
			'schedule:when',
			
			'private:last_modified_time',
			'private:last_modified_by',
			'schedule:status',
		));
		$paginator->createColUrls($pm);
		
		$pm->rows = $paginator->get();
		$pm->goto = $paginator->footer($pm->url);
		if (!empty($pm->rows))
			foreach($pm->rows as &$row)
			{
				
				
				
				if (!empty($pm->sets))
					unset($pm->sets[$row['config_name']]);
			}
	}

	protected function edit(ZMC_Registry_MessageBox $pm)
	{
		if (!$this->getSelectedBinding($pm))
			return;

		if ($pm->binding['dev_meta']['media_type'] === 'tape')
			$this->inventory($pm);
		if($pm->binding['_key_name'] === "changer_library"){
			$res = $slot_with_duplicate_label =  array();
			if(!empty($pm->binding['schedule']['tapelist']))
				foreach($pm->binding['schedule']['tapelist'] as $old_label => $old_label_details)
					$res[$old_label] = $old_label_details['slot'];

			$count_slot_in_group = array_count_values($res);
			if(!empty($count_slot_in_group))
				foreach($count_slot_in_group as $slot => $total_times){
					if($total_times == 1)
						continue;
					foreach($pm->binding['schedule']['tapelist'] as $old_label => $old_label_details)
						if($slot == $old_label_details['slot'])
							$slot_with_duplicate_label[$old_label] = $old_label_details['slot'];
				}
			if(!empty($slot_with_duplicate_label))
				foreach($slot_with_duplicate_label as $i => $j){
					if(!empty($pm->label_status)){
						foreach($pm->label_status as $current_label_key => $current_label_details){
							if(($j == $current_label_details['slot']) && ($i== $current_label_details['label'])) 
								continue;
							if(($j == $current_label_details['slot']) && ($i != $current_label_details['label'])) 
								$pm->addError("Found label '$i' on slot #$j. Please drop the label manually from Backup|Media page. ");
						}
					}
				}
		}
		$pm->tapeListPm = new ZMC_Registry_MessageBox(array(
			'level0_tapelist' => $pm->binding['schedule']['level0_tapelist'],
			'tombstone' => $pm->tombstone,
			'subnav' => $pm->subnav));
		if ($pm->binding['dev_meta']['media_type'] === 'tape')
			$groupedTapelist = $this->paginateTapeMedia($pm);
		else
			$this->paginateVtapeMedia($pm);

		switch($d = $pm->binding['dev_meta']['device_type'])
		{
			case ZMC_Type_Devices::TYPE_SINGLE_TAPE:
			case ZMC_Type_Devices::TYPE_MULTIPLE_TAPE:
				$labelList = $this->calculateChangerView($pm, $groupedTapelist);
				break;
			case ZMC_Type_Devices::TYPE_ATTACHED:
			case ZMC_Type_Devices::TYPE_CLOUD:
				
				break;
			default:
				$pm->addError("This version of ZMC does not support device type \"$d\" on this page.");
		}

		if (!empty($labelList))
		{
			$pm->labelListPm = new ZMC_Registry_MessageBox(array('tombstone' => $pm->tombstone, 'subnav' => $pm->subnav, 'url' => $pm->url, 'label_status' => $pm->label_status));
			$flattened =& ZMC::flattenArrays($labelList, false);
			$paginator = new ZMC_Paginator_Array(
				$pm->labelListPm,
				$flattened,
				$pm->labelListPm->cols = array(
					'slot',
					'barcode',
					'last_used',
					'label',
				),
				'sort_labellist',
				20
			);
			$paginator->createColUrls($pm->labelListPm);
			$pm->labelListPm->rows = $paginator->get();
			$pm->labelListPm->goto = $paginator->shortFooter($pm->url);
		}
	}

	protected function paginateVtapeMedia($pm)
	{
		$retention = $pm->edit['initial_retention'];
		if ($pm->state === 'Prune')
		{
			











			{
				$retention = (empty($_POST['initial_retention']) ? null : intval($_POST['initial_retention']));
				$this->addSingleUserWarning($pm);
				if (empty($retention))
					$pm->addError("Can not prune expired media using retention \"$_POST[initial_retention]\".");
				else
					ZMC_BackupSet::pruneAllExpired($pm, $pm->binding, $retention);
			}
		}
		$tl =& ZMC_BackupSet::mergeFindTapelist($pm, $pm->binding, $retention);
		
		ZMC_Paginator_Reset::defaultSortOrder(array('datetime', 'label'), $sortKey = 'sort_vtapelist');
		$paginator = new ZMC_Paginator_Array(
			$pm->tapeListPm,
			$tl,
			$pm->tapeListPm->cols = array(
				'host',
				'directory' => 'disk_name',
				'media_label' => 'label',
				'age',
				'prune_reason',
				'backup_level' => 'level',
				'percent_use',
				'size',
				'reuse',
				'datetime',
				
				'time_duration',
				'zmc_type',
				'encrypt',
				'compress',
				'status',
				'nb' => ZMC::$registry->dev_only ? true:null,
				'nc' => ZMC::$registry->dev_only ? true:null,
				
			),
			$sortKey,
			20
		);
		$paginator->createColUrls($pm->tapeListPm);
		$pm->tapeListPm->rows = $paginator->get();
		$pm->tapeListPm->goto = $paginator->shortFooter($pm->url);
		$pm->merge($pm->tapeListPm, null, true); 
	}

	protected function paginateTapeMedia($pm)
	{
		ZMC_HeaderFooter::$instance->addRegistry(array('barcodes_enabled' => ($pm->binding['changer']['ignore_barcodes'] === 'off')));
		$groupedTapelist = null;
		
		try{
			ZMC_Yasumi::operation($pm, array('pathInfo' => "/amadmin/get_records/" . $pm->selected_name, 'data' => array(), 'post' => null, 'postData' => null,), true);
		}
		catch(Exception $e)
		{ return false; }
		$tapelist = array();
		ZMC_BackupSet::getTapeList($pm, $tapelist, $pm->selected_name);
		if (empty($tapelist))
			return;
		else
			$pm->binding['schedule']['tapelist'] = $tapelist['tapelist'];
		
		$groupedTapelist =& $this->reformatTapeList($pm->binding['schedule']['tapelist'] , $pm->binding['schedule']['tapecycle'], $pm->binding['schedule']['dumpcycle_start_time'], $pm->binding['dev_meta']['media_type'] === 'vtape');
		$pm->addDetail('<pre>' . print_r($groupedTapelist, true) . '</pre>');
		$flattened =& ZMC::flattenArrays($groupedTapelist, false);
		ZMC_Paginator_Reset::defaultSortOrder(array('last_used', 'reuse'), $sortKey = 'sort_tapelist');
		$paginator = new ZMC_Paginator_Array(
			$pm->tapeListPm,
			$flattened,
			$pm->tapeListPm->cols = array(
				'last_used',
				'labels',
				'reuse',
				
			),
			$sortKey,
			20
		);
		$paginator->createColUrls($pm->tapeListPm);
		$pm->tapeListPm->rows = $paginator->get();
		$pm->tapeListPm->goto = $paginator->shortFooter($pm->url);
		$pm->merge($pm->tapeListPm, null, true); 
		return $groupedTapelist;
	}

	protected function inventory(ZMC_Registry_MessageBox $pm)
	{
		try
		{
			$result = ZMC_Yasumi::operation($pm, array(
				'pathInfo' => '/label/inventory/' . $pm->binding['config_name'],
				'data' => array(
					'commit_comment' => "Backup|Media Inventory",
					'binding_name' => $pm->binding['private']['zmc_device_name'],
					'barcodes_enabled' => ($pm->binding['changer']['ignore_barcodes'] === 'off'),
				),
			));
			unset($result['request']);
		}
		catch (Exception $e)
		{
			$pm->addError("An unexpected problem occurred while reading the list of devices:'. $e");
			return false;
		}
		if ($pm->binding['dev_meta']['media_type'] === 'tape')
		{
			foreach($result->slots2labels as $slot => $label)
				if ($label)
					if (isset($labels2slots[$label]))
						$labels2slots[$label] = null; 
					else
						$labels2slots[$label] = $slot;
			$barcodes2slots = array_flip(array_filter($result->slots2barcodes));
			foreach($pm->binding['schedule']['tapelist'] as $label => &$mediaInTapelist)
				
				if (isset($mediaInTapelist['barcode']))
					$mediaInTapelist['slot'] = $barcodes2slots[$mediaInTapelist['barcode']];
				elseif (!empty($labels2slots[$label]))
					$mediaInTapelist['slot'] = $labels2slots[$label];
		}
		$pm->merge($result);
		if ($pm->binding['dev_meta']['media_type'] === 'tape')
		{
			$pm->barcodes2slots =& $barcodes2slots;
			$pm->labels2slots =& $labels2slots;
		}
		return true;
	}
	public function slots2array($range){

		$slots_array = array();
	    if(!empty($range)){
	        if(!preg_match('/^((\d+(-\d+)?,?)?){1,}$/', $range)){
	            $pm->addError("Please specify slot range in correct format i.e 1-4,6-8,12,17,34-21,etc...");
			}else{
	            $range = rtrim($range, " , ");
		        if(preg_match('/,/', $range)){
					$spl_slot = explode(",", $range);
					foreach($spl_slot as $key => $value){
						if(preg_match('/-+/', $value)){
							$dash_slot = explode("-", $value);
							$slots_array= array_merge($slots_array, range($dash_slot[0], $dash_slot[1]));
						}
						else{
							$slots_array = array_merge($slots_array, (array)$value);
						}
	                }
				}else if(preg_match('/-+/', $range)){
						$dash_slot = explode("-", $range);
						$slots_array = array_merge($slots_array, range($dash_slot[0], $dash_slot[1]));
				}
				else
					$slots_array = array_merge($slots_array, (array)$range);
			}
		}
		$slots_array=array_unique($slots_array);
		sort($slots_array);
		return $slots_array;
	}


	protected function calculateChangerView(ZMC_Registry_MessageBox $pm, $groupedTapelist)
	{
		$dups = $labelList = array();
		$emptyOrUnknown = ($pm->binding['changer']['ignore_barcodes'] === 'on') ? 'unknown' : 'empty';
		$slots = array();

		$slots = $this->slots2array($pm->binding['changer']['slotrange']);

		if(count($slots) <= 0)
			return;
		
		foreach($slots as $key => $i)
		{
			$barcode = empty($pm->slots2barcodes[$i]) ? $emptyOrUnknown : $pm->slots2barcodes[$i];
			$label = ($barcode === 'empty' ? 'empty slot' : 'unknown');
			$last_used = 'Unknown';
			if (!empty($pm->slots2labels[$i]))
			{
				$label = $pm->slots2labels[$i];
				$last_used = 'NA';
				if (!empty($groupedTapelist) && isset($pm->binding['schedule']['tapelist'][$label]))
					if ($time = $pm->binding['schedule']['tapelist'][$label]['timestring'])
						$last_used = $groupedTapelist[$time]['last_used'];
			}

			$key = $label;
			$dup = false;
			if (isset($labelList[$label]))
			{
				$key = $i;
				$labelList[$label]['dup'] = $dup = true;
				$dups[$label][] = $label;
				$dups[$label][] = $key;
			}
			$labelList[$key] = array(
				'slot' => $i,
				'barcode' => $barcode,
				'last_used' => $last_used === 'empty' ? 'default2' : $last_used,
				'label' => $label,
				'dup' => $dup,
				'label_status' => (empty($pm->label_status[$i]) ? null : $pm->label_status[$i]),
			);
		}

		if (!empty($pm->label_status))
		{
			foreach($pm->label_status as $slot => $job)
				if ($job['result'] === 'success')
					continue;
					
				elseif ($job['result'] === 'failure'){
					if(!empty($job[stderr])){
						if(preg_match("/Label\S*(.*)\S*doesn't(.*)match(.*)*/", $job[stderr]))
							$job[stderr] = "The tape label must be in the format: ".$pm->edit['configuration_name']."-[...]";
					}
					$pm->addError("Label of slot #$slot with label '$job[label]' failed:\n$job[stdout]\n$job[stderr]");
				}
				else
					$inProgress = true;

			if (!empty($inProgress))
			{
				$this->autoRefreshPage();
				$pm->labelling_in_progress = true;
			}
		}

		$tapelist =& $pm->binding['schedule']['tapelist'];
		foreach($dups as $label => $labelListKeys)
		{
			$keep = $slots = '';
			foreach($labelListKeys as $labelListKey) {

				if($label === 'unknown' && $labelList[$labelListKey]['label_status']['result'] === 'progress')
					continue;

				if (	!isset($tapelist[$label])
					||	!isset($tapelist[$label]['barcode'])
					|| ($pm->binding['schedule']['tapelist'][$label]['barcode'] !== $labelList[$labelListKey]['barcode']))
				{
					$labelList[$labelListKey]['label_status']['result'] = 'failure';
					$slots .= $labelList[$labelListKey]['slot'] . ', ';
				}
				else
					$keep = "\nRecommendation: Keep media in slot <b>" . $labelList[$labelListKey]['slot'] . '</b> with barcode ' . $labelList[$labelListKey]['slot']['barcode'] . ' and overwrite label(s) of duplicate(s) in slots: ' . $slots;
			}
			if (!empty($slots) && ($label !== 'unknown') && ($label !== 'empty slot'))
				$pm->addEscapedError("Duplicate label found ($label) in slots: " . rtrim($slots, ', ') . $keep);
		}
		return $labelList;
	}

	private function addSingleUserWarning($pm)
	{
		$pm->addMessage('<span class="note">Warning:</span> Please ensure that only one user at a time is interacting with the forms on the <b>&quot;Backup Media&quot;</b> page. Please wait for the requested operation to complete before starting a new operation.');
	}

	protected function &reformatTapeList($tapelist, $tapecycle, $dumpcycleStartTime, $vtape)
	{
		$grouped = array();
		$i = 1;
		$tapeUsed = $tapecycle; 
		foreach ($tapelist as $record)
		{
			ZMC::merge($grouped, array(
				((empty($record['timestring'])||$vtape) ? $record['label'] : $record['timestring']) => array(
					'labels' => array(
						$record['label'] => array(
							'comment' => $record['comment'],
							'priority' => $i++, 
							'tapecycle' => ($tapeUsed-- > 1 && $record['timestring'] > 0) ? $tapeUsed : false,
						)
					),
					'reuse' => $record['reuse'],
			)));
		}

		foreach($grouped as $timestring => &$record)
			$record['last_used'] = ctype_digit($timestring) ? ZMC::humanDate(ZMC::mktime($timestring)) : 'NA';

		ksort($grouped);
		$next = true;
		$numberAvailableTapes = count($tapelist) - $tapecycle;
		if ($numberAvailableTapes < 0)
		{
			$next = 'tapecycle'; 
			$grouped['99990000000000'] = array('labels' =>
				array(abs($numberAvailableTapes) . ' New Tape(s)' =>
					array(	'comment' => 'New tape(s) needed.',
							'priority' => 0,
							'tapecycle' => false
					),
				),
				'reuse' => 'reuse',
				'last_used' => '',
				'next' => true,
			);
		}

		$oldest = key($grouped);
		$grouped[$oldest]['next'] = $next;
		if (strcmp($oldest, $dumpcycleStartTime) > 0)
			$grouped[$oldest]['next'] = 'retention'; 

		return $grouped;
	}
}
