<?php

class notifymessageModel extends notifymessage
{
	/**
	 * getConfig에서 리턴된 함수를 $config 변수로 캐시저장
	 * @var null
	 */
	private static $config = NULL;

	/**
	 * 기본 설정 및 이 모듈의 설정을 저장
	 * @return null|stdClass
	 */
	function getConfig()
	{
		if(self::$config !== NULL)
		{
			return self::$config;
		}

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('notifymessage');

		if(!$config)
		{
			$config = new stdClass();
		}

		self::$config = $config;

		return self::$config;
	}

	/**
	 * 알림톡 유저정보에서 sender Key를 가져와 다시 $args 오브젝트로 믹스
	 * @param $args
	 * @param $config
	 * @return mixed
	 */
	function getFriendTalkSenderKey($args, $config)
	{
		if($config->sender_key)
		{
			$args->sender_key = $config->sender_key;
		}
		else
		{
			$alimtalk_user_info = self::getAlimtalkUserByUserId($config->user_id);
			$sender_key = $alimtalk_user_info->sender_key;
			$args->sender_key = $sender_key;
		}

		if(isset($config->sending_method['cta']) || isset($config->sending_method['sms']) && isset($config->sending_method['cta']))
		{
			$args->type = 'cta';
		}
		elseif(isset($config->sending_method['sms']))
		{
			$args->type = 'sms';
		}

		return $args;
	}

	/**
	 * 알림톡 리스트들중에 특정아이디에 대한 정보 출력
	 * @param $user_id
	 * @return object
	 */
	public static function getAlimtalkUserByUserId($user_id)
	{
		$args = new stdClass();
		$args->user_id = $user_id;
		$output = executeQuery('notifymessage.getAlimtalkUserByUserId', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		return $output->data;
	}

	/**
	 * 알림센터의 데이터를 이용하여 알림내용을 포맷정함.
	 * @param $notify
	 * @return bool|null|string
	 */
	function getNotifyMessage($notify)
	{
		$str = NULL;

		switch($notify->notify_type)
		{
			// Document.
			case 'D':
				$type = '문서';
				break;

			// Comment.
			case 'C':
				$type = '댓글';
				break;

			// Message.
			case 'E':
				$type = '쪽지';
				break;
		}

		switch($notify->notify_target_type)
		{
			// Comment on your document.
			case 'C':
				$str = sprintf('%1$s님이 회원님의 %2$s에 "%3$s"라고 댓글을 남겼습니다.', $notify->target_nick_name, $type, $notify->target_summary);
				break;

			// Comment on a board.
			case 'A':
				$str = sprintf('%1$s님이 "%2$s"게시판에 "%3$s"라고 댓글을 남겼습니다.', $notify->target_nick_name, $notify->target_browser, $notify->target_summary);
				break;

			// Mentioned.
			case 'M':
				$str = sprintf('%s님이 "%s" 게시판의 "%s" %s에서 회원님을 언급하였습니다.', $notify->target_nick_name, $notify->target_browser, $notify->target_summary, $type);
				break;

			// Message arrived.
			case 'E':
				$str = sprintf('%s님이 "%s"라고 메시지를 보내셨습니다.', $notify->target_nick_name, $notify->target_summary);
				break;
			//  New posts on a board.
			case 'NP':
				$str = sprintf('%s님이 "%s"에 새로운 게시글을 작성하였습니다.', $notify->target_nick_name, $notify->target_browser);
				break;
		}

		if($str === NULL)
		{
			return false;
		}

		return $str;
	}

	/**
	 * 문자의 예약기간이 있는지를 검사. 있다면 예약을 취소하고 다시 예약
	 * @param $obj
	 * @param $config
	 * @return bool|object
	 */
	function getReservedReportTime($obj, $config)
	{
		$oTextmessageController = getController('textmessage');

		$start = intval($config->time_start);
		$end = intval($config->time_end);
		$today = date('Ymd'); 	// 현재 년도 + 월 + 일
		$tomorrow = date('Ymd', strtotime("+1 day")); 	// 내일 년도 + 월 + 일
		$hour = date('H');		// 현재 시간
		$start = sprintf("%02d", $start);   	// config에 설정된 시작 시간
		$end = sprintf("%02d", $end);			// config에 설정된 끝나는 시간
		$args = new stdClass();
		if($hour < $start)
		{
			$args->reservdate = sprintf("%s%s0000", $today, $start);
		}
		elseif($hour >= $end)
		{
			$args->reservdate = sprintf("%s%s0000", $tomorrow, $start);
		}
		else
		{
			return false;
		}
		$user_reserved = new stdClass();
		$user_reserved->cellphone = $obj->recipient_no;
		$user_reserved->reservdate = $args->reservdate;
		$output = executeQuery('notifymessage.getUserReserved', $user_reserved);
		$reserved = $output->data;

		if(empty($reserved))
		{
			$args->count = '1';
		}
		else
		{
			if(substr($reserved->reservdate, 0, 8) == substr($args->reservdate, 0, 8))
			{
				$reserved->count++;
				$args->count = $reserved->count;
				if($reserved->group_id)
				{
					$output = $oTextmessageController->cancelGroupMessages($reserved->group_id);
					if(!$output->toBool())
					{
						return $output;
					}
				}
			}
			else
			{
				$args->count = '0';
			}
		}

		// 문자 내용 처리
		$args->sender_no = $obj->sender_no;
		$args->recipient_no = explode(',', $obj->recipient_no);
		$msg_format = "문자 예약기간동안 총 %d 건의 알림이 있었습니다.";
		$args->content = sprintf($msg_format, $args->count);
		// 문자 전송
		$result = false;
		if(count($args->recipient_no))
		{
			$result = $oTextmessageController->sendMessage($args);
			if(!$result->toBool())
			{
				return $result;
			}
		}

		$args->cellphone = $obj->recipient_no;
		if($result->variables['group_id'])
		{
			$args->group_id = $result->variables['group_id'];
		}

		if(!empty($reserved))
		{
			$output = executeQuery('notifymessage.updateUserReserved', $args);
		}
		else
		{
			$output = executeQuery('notifymessage.insertUserReserved', $args);
		}
		if(!$output->toBool())
		{
			return $output;
		}

		return true;
	}

	function getGroupMemberList($group_srl)
	{
		$args = new stdClass();
		$args->selected_group_srl = $group_srl;
		$args->page = 1;
		$args->list_count = 9999;
		$args->page_count = 10;
		$output = executeQuery('member.getMemberListWithinGroup', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$member_list = $output->data;

		return $member_list;
	}
}
/* End of file notifymessage.model.php */
/* Location: ./modules/notifymessage/notifymessage.model.php */
