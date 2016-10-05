<?php

class notifymessage extends ModuleObject
{
	/**
	 * 설치할 트리거를 배열형태로 저장한다음 배열대로 각각 설치를 할 수 잇도록 추가
	 * @var array
	 */
	private $triggers = array(
		array('ncenterlite._insertNotify', 'notifymessage', 'controller', 'triggerAfterinsertNotify', 'after'),
		array('document.insertDocument', 'notifymessage', 'controller', 'triggerAfterInsertDocument', 'after'),
		array('comment.insertComment', 'notifymessage', 'controller', 'triggerAfterInsertComment', 'after'),
	);

	/**
	 * Install notifymessage module
	 * @return Object
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * If update is necessary it returns true
	 * @return bool
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		$config = getModel('notifymessage')->getConfig();

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $value)
		{
			if($value->type == 'tel')
			{
				$variable_name[] = $value->name;
			}
		}
		if(!$config->variable_name && count($variable_name) == 1)
		{
			return true;
		}

		return FALSE;
	}

	/**
	 * Update module
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		$config = getModel('notifymessage')->getConfig();
		if(!$config)
		{
			$config = new stdClass();
		}
		if(!$config->variable_name)
		{
			$member_config = getModel('member')->getMemberConfig();
			$variable_name = array();
			foreach($member_config->signupForm as $value)
			{
				if($value->type == 'tel')
				{
					$variable_name[] = $value->name;
				}
			}
			if(count($variable_name) == 1)
			{
				foreach($variable_name as $item)
				{
					$config->variable_name = $item;
				}
				$output = $oModuleController->insertModuleConfig('notifymessage', $config);
				if(!$output->toBool())
				{
					return new Object(-1, '모듈설정을 저장하지 못했습니다.');
				}
			}
		}

	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache()
	{
		
	}

	/**
	 * module unstall.
	 * @return Object
	 */
	function moduleUninstall()
	{
		$oModuleController = getController('module');
		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
		return new Object();
	}
}
/* End of file notifymessage.class.php */
/* Location: ./modules/notifymessage/notifymessage.class.php */
