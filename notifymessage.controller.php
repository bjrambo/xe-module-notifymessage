<?php

class notifymessageController extends notifymessage
{
	function init()
	{
	}

	/**
	 * 알림센터가 등록될 경우 발생하는 액션
	 * @param $obj
	 * @return Object
	 */
	function triggerAfterinsertNotify(&$obj)
	{
		$oNotifymessageModel = getModel('notifymessage');
		$config = $oNotifymessageModel->getConfig();
		$oTextmessageController = getController('textmessage');

		if($config->ncenterlite_use !== 'Y')
		{
			return new Object();
		}

		$admin_member_info = getModel('member')->getMemberInfoByUserId($config->user_id);

		$args = new stdClass();
		$args->notify_type = $obj->type;
		$args->notify_target_type = $obj->target_type;
		$args->target_nick_name = $obj->target_nick_name;
		$args->target_browser = $obj->target_browser;
		$args->target_summary = $obj->target_summary;
		$args->content = $oNotifymessageModel->getNotifyMessage($args);
		if($args->content === false)
		{
			return new Object();
		}
		$args->sender_no = $config->sender_no;
		$args->recipient_no = $config->admin_phones;
		$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
		$set_time = false;
		if($config->reserv_switch == 'on')
		{
			$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
		}
		if($set_time == false)
		{
			$output = $oTextmessageController->sendMessage($args, FALSE);
			if(!$output->toBool())
			{
				return $output;
			}
			else
			{
				if($output->get('success_count') >= 1)
				{
					// 문자 전송상황을 뜻함
					$args->state = '1';
				}
				else
				{
					$args->state = '2';
				}
				if($admin_member_info)
				{
					$args->member_srl = $admin_member_info->member_srl;
					$args->nick_name = $admin_member_info->nick_name;
				}
				else
				{
					$args->member_srl = '0';
					$args->nick_name = '관리자';
				}
				$log_output = self::insertNotifymessageLog($args);
				if(!$log_output->toBool())
				{
					return $output;
				}
			}
		}

		// 알림을 받는 대상에게도 문자를 보낼지 여부를 지정하여 해당 맴버에게 문자를 발송 (친구톡우선)
		if($config->send_member_type === 'Y')
		{
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

			// 위쪽에 내용을 그대로 가져와야 하기 때문에 $args 는 초기화하지 않는다.
			$args->recipient_no = $member_info->{$config->variable_name}[0].$member_info->{$config->variable_name}[1].$member_info->{$config->variable_name}[2];
			$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
			if($config->reserv_switch == 'on')
			{
				$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
			}
			if($set_time == false)
			{
				$output = $oTextmessageController->sendMessage($args, FALSE);
				if(!$output->toBool())
				{
					return $output;
				}
				else
				{
					if($output->get('success_count') >= 1)
					{
						// 문자 전송상황을 뜻함
						$args->state = '1';
					}
					else
					{
						$args->state = '2';
					}
					$args->member_srl = $member_info->member_srl;
					$args->nick_name = $member_info->nick_name;
					$log_output = self::insertNotifymessageLog($args);
					if(!$log_output->toBool())
					{
						return $output;
					}
				}
			}
		}
	}

	/**
	 * 알림센터의 기능이 아닌 새로운글을 등록시 받을 알림
	 * @param $obj
	 * @return Object
	 */
	function triggerAfterInsertDocument(&$obj)
	{
		$config = getModel('notifymessage')->getConfig();

		if($config->group_message)
		{
			$output = self::pushDocumentGroupMessage($obj, $config);
		}
		else
		{
			$output = self::pushDocumentDefaultMessage($obj, $config);
		}
	}

	/**
	 * 댓글 작성에 대한 문자 알림
	 * @param $obj
	 * @return Object
	 */
	function triggerAfterInsertComment(&$obj)
	{
		$oMemberModel = getModel('member');
		$oModuleModel = getModel('module');
		$oDocumentModel = getModel('document');
		$oNotifymessageModel = getModel('notifymessage');
		$oTextmessageController = getController('textmessage');

		$config = $oNotifymessageModel->getConfig();
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

		$admin_member_info = getModel('member')->getMemberInfoByUserId($config->user_id);

		$set_time = false;
		if(is_array($config->seleted_module_srls) && in_array($module_info->module_srl, $config->seleted_module_srls) && $config->ncenterlite_use !== 'Y')
		{
			$args = new stdClass();
			$args->notify_type = 'C';
			$args->notify_target_type = 'A';
			$args->target_nick_name = $obj->nick_name;
			$args->target_browser = $module_info->browser_title;
			$args->target_summary = cut_str(strip_tags($obj->content), 20);
			$args->content = $oNotifymessageModel->getNotifyMessage($args);
			if($args->content === false)
			{
				return new Object();
			}
			$args->sender_no = $config->sender_no;
			$args->recipient_no = $config->admin_phones;
			$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
			if($config->reserv_switch == 'on')
			{
				$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
			}
			if($set_time == false)
			{
				$output = $oTextmessageController->sendMessage($args, FALSE);
				if(!$output->toBool())
				{
					return $output;
				}
				else
				{
					if($output->get('success_count') >= 1)
					{
						// 문자 전송상황을 뜻함
						$args->state = '1';
					}
					else
					{
						$args->state = '2';
					}
					if($admin_member_info)
					{
						$args->member_srl = $admin_member_info->member_srl;
						$args->nick_name = $admin_member_info->nick_name;
					}
					else
					{
						$args->member_srl = '0';
						$args->nick_name = '관리자';
					}
					$log_output = self::insertNotifymessageLog($args);
					if(!$log_output->toBool())
					{
						return $output;
					}
				}
			}
			// 알림을 받는 대상에게도 문자를 보낼지 여부를 지정하여 해당 맴버에게 문자를 발송 (친구톡우선)
			if($config->send_member_type === 'Y')
			{
				$oDocument = $oDocumentModel->getDocument($obj->document_srl);
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($oDocument->get('member_srl'));

				// 위쪽에 내용을 그대로 가져와야 하기 때문에 $args 는 초기화하지 않는다.
				$args->recipient_no = $member_info->{$config->variable_name}[0].$member_info->{$config->variable_name}[1].$member_info->{$config->variable_name}[2];
				$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
				if($config->reserv_switch == 'on')
				{
					$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
				}
				if($set_time == false)
				{
					$output = $oTextmessageController->sendMessage($args, FALSE);
					if(!$output->toBool())
					{
						return $output;
					}
					else
					{
						if($output->get('success_count') >= 1)
						{
							// 문자 전송상황을 뜻함
							$args->state = '1';
						}
						else
						{
							$args->state = '2';
						}
						$args->member_srl = $member_info->member_srl;
						$args->nick_name = $member_info->nick_name;
						$log_output = self::insertNotifymessageLog($args);
						if(!$log_output->toBool())
						{
							return $output;
						}
					}
				}
			}
		}
	}

	/**
	 * 전체 문자 알림 시스템 로그를 디비에 저장
	 * @param $logs
	 * @return object
	 */
	public static function insertNotifymessageLog($logs)
	{
		$args = new stdClass();
		$args->notify_srl = getNextSequence();
		$args->notify_type = $logs->notify_type;
		$args->notify_target_type = $logs->notify_target_type;
		$args->sender_no = $logs->sender_no;
		$args->recipient_no = $logs->recipient_no;
		$args->sms_type = $logs->type;
		$args->member_srl = $logs->member_srl;
		$args->nick_name = $logs->nick_name;
		$args->content = $logs->content;
		$args->state = $logs->state;
		$args->regdate = date('YmdHis');

		$output = executeQuery('notifymessage.insertNotifymessageLog', $args);

		return $output;
	}

	/**
	 * 기본적인 메세지 전송방법을 실행함.
	 * @param $obj
	 * @return bool
	 */
	public static function pushDocumentDefaultMessage(&$obj, $config)
	{
		$oNotifymessageModel = getModel('notifymessage');

		$oTextmessageController = getController('textmessage');
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

		$admin_member_info = getModel('member')->getMemberInfoByUserId($config->user_id);

		$set_time = false;
		if(is_array($config->seleted_module_srls) && in_array($module_info->module_srl, $config->seleted_module_srls) && $config->ncenterlite_use !== 'Y')
		{
			$args = new stdClass();
			$args->notify_type = 'D';
			$args->notify_target_type = 'NP';
			$args->target_nick_name = $obj->nick_name;
			$args->target_browser = $module_info->browser_title;
			$args->target_summary = $obj->title;
			$args->content = $oNotifymessageModel->getNotifyMessage($args);
			if($args->content === false)
			{
				return new Object();
			}
			$args->sender_no = $config->sender_no;
			$args->recipient_no = $config->admin_phones;
			$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
			if($config->reserv_switch == 'on')
			{
				$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
			}
			if($set_time == false)
			{
				$output = $oTextmessageController->sendMessage($args, FALSE);
				if(!$output->toBool())
				{
					return $output;
				}
				else
				{
					if($output->get('success_count') >= 1)
					{
						// 문자 전송상황을 뜻함
						$args->state = '1';
					}
					else
					{
						$args->state = '2';
					}
					if($admin_member_info)
					{
						$args->member_srl = $admin_member_info->member_srl;
						$args->nick_name = $admin_member_info->nick_name;
					}
					else
					{
						$args->member_srl = '0';
						$args->nick_name = '관리자';
					}
					$log_output = self::insertNotifymessageLog($args);
					if(!$log_output->toBool())
					{
						return $output;
					}
				}
			}

			// 굳이 설정을 만든 이유는 쿼리 1개라도 줄이기 위함 (분류관리자를 확인하기 위해 쿼리가 실행됨)
			if($config->category_message == 'Y')
			{
				// 분류별 설정에서 관리자설정이 되어있으면 문자알림 또는 친구톡을 사용
				$category_args = new stdClass();
				$category_args->category_srl = $obj->category_srl;
				$category_output = executeQuery('notifymessage.getAdminInfo', $category_args);
				$category_admins = $category_output->data;
				if($category_admins->cellphone)
				{
					$args->recipient_no = $category_admins->cellphone;
					$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
					if($config->reserv_switch == 'on')
					{
						$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
					}
					if($set_time == false)
					{
						$output = $oTextmessageController->sendMessage($args, FALSE);
						if(!$output->toBool())
						{
							return $output;
						}
						else
						{
							if($output->get('success_count') >= 1)
							{
								// 문자 전송상황을 뜻함
								$args->state = '1';
							}
							else
							{
								$args->state = '2';
							}
							if($admin_member_info)
							{
								$args->member_srl = $admin_member_info->member_srl;
								$args->nick_name = $admin_member_info->nick_name;
							}
							else
							{
								$args->member_srl = '0';
								$args->nick_name = '관리자';
							}
							$log_output = self::insertNotifymessageLog($args);
							if(!$log_output->toBool())
							{
								return $output;
							}
						}
					}
				}
			}
		}

		return true;
	}

	public static function pushDocumentGroupMessage($obj, $config)
	{
		$oModuleModel = getModel('module');
		$oNotifymessageModel = getModel('notifymessage');
		$oTextmessageController = getController('textmessage');

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);
		$admin_member_info = getModel('member')->getMemberInfoByUserId($config->user_id);

		$member_list = $oNotifymessageModel->getGroupMemberList($config->group_srls);

		$member_number = array();
		foreach($member_list as $member_info)
		{
			$member_extra_var = unserialize($member_info->extra_vars);
			$member_number[$member_info->member_srl] = $member_extra_var->{$config->variable_name}[0].$member_extra_var->{$config->variable_name}[1].$member_extra_var->{$config->variable_name}[2];
		}

		$phone_numbers = implode(',', $member_number);

		$set_time = false;

		$args = new stdClass();
		$args->notify_type = 'D';
		$args->notify_target_type = 'NP';
		$args->target_nick_name = $obj->nick_name;
		$args->target_browser = $module_info->browser_title;
		$args->target_summary = $obj->title;
		$args->content = $oNotifymessageModel->getNotifyMessage($args);
		if($args->content === false)
		{
			return new Object();
		}
		$args->sender_no = $config->sender_no;
		$args->recipient_no = $phone_numbers;
		$args = $oNotifymessageModel->getFriendTalkSenderKey($args, $config);
		if($config->reserv_switch == 'on')
		{
			$set_time = $oNotifymessageModel->getReservedReportTime($args, $config);
		}
		if($set_time == false)
		{
			$output = $oTextmessageController->sendMessage($args, FALSE);
			if(!$output->toBool())
			{
				return $output;
			}
			else
			{
				if($output->get('success_count') >= 1)
				{
					// 문자 전송상황을 뜻함
					$args->state = '1';
				}
				else
				{
					$args->state = '2';
				}
				if($admin_member_info)
				{
					$args->member_srl = $admin_member_info->member_srl;
					$args->nick_name = $admin_member_info->nick_name;
				}
				else
				{
					$args->member_srl = '0';
					$args->nick_name = '관리자';
				}
				$log_output = self::insertNotifymessageLog($args);
				if(!$log_output->toBool())
				{
					return $output;
				}
			}
		}
	}
}
/* End of file notifymessage.controller.php */
/* Location: ./modules/notifymessage/notifymessage.controller.php */
