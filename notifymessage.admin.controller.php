<?php
class notifymessageAdminController extends notifymessage
{
	/**
	 * 알림설정 액션
	 * @return Object|void (void 완료, Object 실패)
	 */
	function procNotifymessageAdminConfig()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();

		$oNotifymessageModel = getModel('notifymessage');
		$config = $oNotifymessageModel->getConfig();

		// $obj 에 넘겨받은 값중 옵션으로 해당하는 값들을 array으로 배열시킨다음 아래에서 실행시키도록함
		$config_vars = array(
			'sending_method',
			'ncenterlite_use',
			'user_id',
			'sender_no',
			'admin_phones',
			'admin_emails',
			'sender_name',
			'sender_email',
			'variable_name',
			'send_member_type',
			'sender_key',
			'seleted_module_srls',
			'category_message',
			'time_start',
			'time_end',
			'time_switch',
			'reserv_switch',
			'group_message',
			'group_srls',
		);

		foreach($config_vars as $val)
		{
			if($obj->{$val})
			{
				$config->{$val} = $obj->{$val};
			}
		}

		// 각각의 옵션중에 array으로 저장하는 옵션들의 경우 빈값을 저장할 수 없는 버그가 있어서 빈값으로 초기화시켜야함.
		if($obj->disp_act == 'dispNotifymessageAdminSeletedmid')
		{
			if(!$obj->seleted_module_srls)
			{
				$config->seleted_module_srls = array();
			}
		}

		if($obj->disp_act == 'dispNotifymessageAdminAdvancedconfig')
		{
			if(!$obj->time_switch)
			{
				$config->time_switch = array();
			}
			if(!$obj->reserv_switch)
			{
				$config->reserv_switch = array();
			}
		}

		if($obj->disp_act == 'dispNotifymessageAdminConfig')
		{
			if(!$obj->sending_method)
			{
				$config->sending_method = array();
			}
		}

		$output = $oModuleController->insertModuleConfig('notifymessage', $config);
		if(!$output->toBool())
		{
			return new Object(-1, '모듈설정을 저장하지 못했습니다.');
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', $obj->disp_act);
			header('location: ' . $returnUrl);
			return;
		}
	}

	/**
	 * 게시판별 분류에 전화번호설정 페이지
	 * @return Object|void
	 */
	function procNotifymessageAdminCategories()
	{
		$category_srls = Context::get('category_srls');
		$cellphones = Context::get('cellphones');
		$emails = Context::get('emails');
		$module_srls = Context::get('module_srls');
		$parent_srls = Context::get('parent_srls');
		$titles = Context::get('titles');
		if(empty($category_srls))
		{
			return new Object(-1, '카테고리번호는 필수입니다.');
		}

		foreach($category_srls as $key => $val)
		{
			$args = new stdClass();
			$args->category_srl = $val;
			$args->email = $emails[$key];
			$args->cellphone = $cellphones[$key];
			$args->module_srl = $module_srls[$key];
			$args->parent_srl = $parent_srls[$key];
			$args->title = $titles[$key];
			$output = executeQuery('notifymessage.getAdminInfo', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			$admin_info = $output->data;
			if(empty($admin_info))
			{
				$output = executeQuery('notifymessage.insertAdminInfo', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				$output = executeQuery('notifymessage.updateAdminInfo', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispNotifymessageAdminCategories');
			header('location: ' . $returnUrl);
			return;
		}
	}
}
