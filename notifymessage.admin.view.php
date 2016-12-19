<?php
class notifymessageAdminView extends notifymessage
{
	/**
	 * 각 템플릿파일을 변수명을 인식하여 자동으로 불러오는 시스템을 마련.
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispNotifymessageAdmin', '', $this->act)));
	}

	/**
	 * 관리자 설정 페이지
	 */
	function dispNotifymessageAdminConfig()
	{
		$oNotifymessageModel = getModel('notifymessage');

		$group_list = getModel('member')->getGroups();
		$config = $oNotifymessageModel->getConfig();

		Context::set('config', $config);
		Context::set('group_list', $group_list);
	}

	/**
	 * 관리자 고급설정 페이지
	 */
	function dispNotifymessageAdminAdvancedconfig()
	{
		$oNotifymessageModel = getModel('notifymessage');

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $value)
		{
			if($value->type == 'tel')
			{
				$variable_name[] = $value->name;
			}
		}
		$config = $oNotifymessageModel->getConfig();

		Context::set('variable_name', $variable_name);
		Context::set('config', $config);
	}

	/**
	 * 모듈별 알림설정 페이지
	 */
	function dispNotifymessageAdminSeletedmid()
	{
		$oNotifyMessageModel = getModel('notifymessage');
		$config = $oNotifyMessageModel->getConfig();
		$oModuleModel = getModel('module');

		$mid_list = $oModuleModel->getMidList(null, array('module_srl', 'mid', 'browser_title', 'module'));

		Context::set('config', $config);
		Context::set('mid_list', $mid_list);
	}

	/**
	 * 분류별 전화번호 설정 페이지
	 */
	function dispNotifymessageAdminCategories()
	{
		$oModuleModel = getModel('module');
		$oNotifyMessageModel = getModel('notifymessage');
		$config = $oNotifyMessageModel->getConfig();
		$module_srls = $config->seleted_module_srls;

		$category_admins = array();
		foreach($module_srls as $key => $module_srl)
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$output = executeQueryArray('notifymessage.getDocumentCategories', $args);
			$category_list = $output->data;
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			$obj = new stdClass();
			$obj->title = $module_info->browser_title;
			$obj->data = array();

			if(is_array($category_list))
			{
				foreach($category_list as $no => $val)
				{
					$args = new stdClass();
					$args->category_srl = $val->category_srl;
					$args->parent_srl = $val->parent_srl;
					$args->title = $val->title;
					$output = executeQuery('notifymessage.getAdminInfo', $args);
					if(!$output->toBool())
					{
						return $output;
					}
					$admin_info = $output->data;

					// view 볼때마다 여기에 직접 쿼리를 input 시키지 않고 가져온 데이터만사용.
					if(empty($admin_info))
					{
						$admin_info = $args;
					}

					$obj->data[] = $admin_info;
				}
			}

			if(count($category_list) != 0)
			{
				$category_admins[$module_info->module_srl] = $obj;
			}
			else
			{
				array_splice($category_admins, $key, 1);
			}
		}

		Context::set('outputs', $category_admins);
	}

	/**
	 * 로그를 확인할 수 있는 공간.
	 */
	function dispNotifymessageAdminLogs()
	{
		$args = new stdClass();
		$args->sort_index = 'notify_srl';
		$args->list_count = 20;
		$args->page_count = 10;
		$args->page = Context::get('page');
		$output = executeQueryArray('notifymessage.getNotifymessageLogList', $args);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('notify_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
	}
}
