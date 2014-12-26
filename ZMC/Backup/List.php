<?













class ZMC_Backup_List
{
	const CHOOSE_MESSAGE = '需要编辑备份项，请在表格中单击备份项名称, 或者新建一个.';

	public static function run(ZMC_Registry_MessageBox $pm)
	{
		ZMC_BackupSet::select($pm);
		ZMC_HeaderFooter::$instance->header($pm, 'Backup', '云备份 - 管理备份列表', 'list');
		
		$pm->edit = $pm->goto = null;
		$pm->state = (empty($_REQUEST['action']) ? '' : $_REQUEST['action']);
		if (!empty($_REQUEST['action']))
			$pm->state = $_REQUEST['action'];
		if (empty($pm->state) && ZMC_BackupSet::getName())
			$pm->state = 'Edit';

		$pm->users = ZMC_User::$users; 
		$ownerId  = (empty($_POST['ownerSelect']) ? $_SESSION['user_id'] : $_POST['ownerSelect']);

		ZMC_Disklists::start();
		
		if  (		!empty($_REQUEST['name'])
				&&	ZMC_Disklists::get($_REQUEST['name'])
				&&	false === ZMC_Disklists::getMine($_REQUEST['name'])
			)
			$pm->addError('只有管理员和备份项所属者才能执行该请求.');
		else
		{
			$listPage = new self($pm);
			$listPage->runState($pm);
		}

		ZMC_Disklists::getPaginator($pm);
		if ($pm->edit)
		{
			$pm->name = ZMC::escape($pm->edit['id']);
			$pm->edit['live'] = $pm->rows[$pm->edit['id']]['live'];
			if (isset($_GET['action']) && $_GET['action'] === 'migrate')
				unset($pm->rows[$pm->name]['status']); 
		}

		return 'BackupList';
	}

	protected function runState($pm)
	{
		$comments = (empty($_POST['comments']) ? '' : $_POST['comments']);
		$ownerId  = (empty($_POST['ownerSelect']) ? $_SESSION['user_id'] : $_POST['ownerSelect']);

		switch($pm->state)
		{
			case 'Update': 
				ZMC_Disklists::update($pm, $_POST['name'], $comments, $ownerId);
				break;

			case 'Add': 
				if (empty($_POST['name']))
					return $pm->addError('请输入名称.');
				elseif (!ZMC_BackupSet::isValidName($pm, $_POST['name']))
					return;
				ZMC_Disklists::create($pm, $_POST['name'], $comments, $ownerId);
				break;

			case 'Edit':
				$pm->edit = ZMC_Disklists::get(ZMC_Form::getEditId($pm, 'name', '编辑失败没有指明名称'));
				break;

			case 'Delete':
				$pm->confirm_template = 'ConfirmationWindow';
				$ids = ZMC::$userRegistry['selected_ids'];
				foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore)
				{
					if (false === ($list = ZMC_Disklists::getMine($id)))
						$pm->addError("无法删除: $id (必须是管理员或者所属者)");
					if (ZMC_Disklists::getColumn($id, 'live'))
					{
						$live = true;
						unset($ids[$id]);
						$ids["<span class='wocloudUserWarnings'><b>$id (LIVE)</b></span>"] = true;
					}
				}
				if (!empty($live))
					$pm->addWarning('删除一个 "活动的" 备份项仅仅指在该表中删除。删除一个备份集则会删除备份集中的活动备份项.');

				$pm->addWarning('没有撤销.');
				$pm->prompt ='Are you sure you want to DELETE the object list(s)?<br /><ul>'
					. '<li>'
					. implode("\n<li>", array_keys($ids))
					. "\n</ul>\n";
				$pm->confirm_action = 'DeleteConfirm';
				$pm->yes = 'Delete';
				$pm->no = 'Cancel';
				break;

			case 'DeleteConfirm':
				
				if (isset($_POST['ConfirmationYes']))
					foreach(ZMC::$userRegistry['selected_ids'] as $name => $ignore)
						if (!ZMC_Disklists::rm($pm, $name))
							$pm->addError("无法删除: $name");

				if (ZMC_Disklists::count() == 0)
					$pm->addWarning("没有可用备份项.");
				$pm->next_state = '';
				break;

			case 'DuplicateConfirm':
				$pm->addError('@TODO');
				if (isset($_POST['ConfirmationYes']))
				{
					ZMC::quit();
					$newName = $_REQUEST['new_backup_set_name'];
					$editId = ZMC_Disklists::duplicate($pm, $_REQUEST['name'], $newName);
					$pm->addMessage("复制 '{$pm->name}' 为 '$newName'.");
				}
				else
				{
					$pm->prompt = '请输入复制备份项的名称.';
					$pm->url = '?' . str_replace('action=duplicate', 'action=duplicateConfirm', $_SERVER['QUERY_STRING']);
					$pm->yes = "Duplicate";
					$pm->no = "Cancel";
					ZMC_Loader::renderTemplate('DuplicationWindow', $pm);
				}
				break;

			case 'Choose_Message':
				$pm->addWarning($this->CHOOSE_MESSAGE);
				break;

			case 'Refresh Table':
			case '':
				$pm->state = 'Refresh Table'; 
				$pm->addInstruction($this->CHOOSE_MESSAGE);
				
				
				break;

			case 'Cancel':
				break;

			default:
				ZMC::headerRedirect(ZMC::$registry->bomb_url_php . '?error=' . bin2hex(__CLASS__ . " - Unknown state: $pm->state"), __FILE__, __LINE__);
		}
	}
}
