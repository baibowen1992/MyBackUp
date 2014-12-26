<?
//zhoulin-admin-users 201409172350












class ZMC_Admin_Users
{
	public static function run(ZMC_Registry_MessageBox $pm)
	{
		$pm->edit = null; 
		$pm->goto = null;
		$pm->disabled = 'Disabled';
		$pm->skip_backupset_start = true;
		ZMC_HeaderFooter::$instance->header($pm, 'Admin', '云备份 -  用户管理', 'users');
		ZMC_HeaderFooter::$instance->addYui('wocloud-utils', array('dom', 'event', 'connection'));
		self::form($pm);
		ZMC_User::getPaginator($pm);
		$pm->origUsername = (($pm->edit && !empty($pm->edit['origUsername'])) ? $pm->edit['origUsername'] : '');
		if (!$pm->edit)
			$pm->addDefaultInstruction('管理用户 - 创建，编辑，查看，删除用户。.');
		elseif (!empty($pm->edit['user_id']))
			$pm->addInstruction('你正在编辑用户 ；  ' . $pm->origUsername);
		else
			$pm->addInstruction('你创建了一个新账户');

		return 'AdminUsers';
	}

	










	public static function form(ZMC_Registry_MessageBox $pm)
	{
		$admin = ZMC_User::hasRole('Administrator');
		if (!empty($_POST['action']) && ($_POST['action'] === 'Add' || $_POST['action'] === 'Update'))
		{
			if (isset($_POST['action']))
			{
				$orig = '';
				if ($id = ZMC_User::saveUser($pm))
					$orig = ZMC_User::get('user', $id);
				elseif(!empty($_REQUEST['edit_id']))
					$orig = ZMC_User::get('user', $_REQUEST['edit_id']);

				if ($_POST['action'] === 'Add')
				{
					ZMC_Paginator_Reset::reset('registration_date');
					
				}
				$pm->disabled = ($orig === $_POST['user'] ? 'Disabled' : '');

				$pm->edit= array(
					'user_id' => ((empty($id) && isset($_REQUEST['edit_id'])) ? intval($_REQUEST['edit_id']) : $id),
					'user' => $_POST['user'],
					'email' => $_POST['email'],
					'user_role' => $_POST['user_role'],
					'password' => $_POST['password'],
					'confirm' => $_POST['confirm'],
					'origUsername' => $orig,
				);
			}
		}

		if(isset($_POST['UpdateZN']))
			self::updateZN($pm);
		
		if (!$admin)
			$_REQUEST['action'] = 'edit';
	
		if ($admin && isset($_POST['action']) && $_POST['action'] === 'DeleteConfirm') 
		{
			if (!isset($_POST['ConfirmationYes']))
				return $pm->addWarning("编辑/新增  取消.");

			foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore)
				if (ZMC_User::deleteUser($pm, $id))
					$pm->addMessage("删除用户: " . ZMC_User::get('user', $id));
		}
		elseif (isset($_REQUEST['action']))
		{
			if ($admin && isset($_POST['action']) && $_POST['action'] === 'Delete' && !empty(ZMC::$userRegistry['selected_ids']))
			{
				$names = $ids = array();
				foreach(ZMC::$userRegistry['selected_ids'] as $id => $ignore)
					if (intval($id) > 0 && $user = ZMC_User::get(null, intval($id)))
						$names[] = $user['user'];

				$pm->confirm_template = 'ConfirmationWindow';
				
				$pm->prompt ='确认要删除用户?<br /><ul>'
					. '<li style="list-style-position:inside; list-style-type:square">'
					. implode("\n<li style='list-style-position:inside; list-style-type:square'>", $names)
					. "\n</ul>\n";
				$pm->confirm_action = 'DeleteConfirm';
				$pm->yes = 'Delete';
				$pm->no = 'Cancel';
			}
			else
			{
				$pm->edit = ZMC_User::get(null, $admin ? intval($_GET['edit_id']) : $_SESSION['user_id']);
				if (!empty($pm->edit['user_id']))
					$pm->edit['origUsername'] = $pm->edit['user'];
				$pm->disabled = 'Disabled';
				$pm->edit['password'] = ''; 
			}
		}
	}

	





/*	public static function updateZN(ZMC_Registry_MessageBox $pm, &$userId = null)
	{
		if (!ZMC_User::hasRole('Administrator')) 
			$userId = $_SESSION['user_id'];
		elseif (isset($_REQUEST['edit_id']))
			$userId = intval($_REQUEST['edit_id']);

		if(empty($userId))
			return $pm->addError('Cannot update wocloud Information as no user was given to update');

		$networkId = trim($_POST['zmandaNetworkID']);
		$networkPassword = @trim($_POST['zmandaNetworkPassword']);
		ZMC_ZmandaNetwork::verifyAndSave($pm, $networkId, $networkPassword, $userId);
	}*/
}
