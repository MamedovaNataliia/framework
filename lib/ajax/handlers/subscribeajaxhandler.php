<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/15/2017
 * Time: 11:27 AM
 */

namespace Aniart\Main\Ajax\Handlers;

use Aniart\Main\Ajax\AbstractAjaxHandler;
use Aniart\Main\Exceptions\AniartException;

class SubscribeAjaxHandler extends AbstractAjaxHandler
{
	protected function getFunction()
	{
		return $this->request['func'];
	}

	public function subscribeUserOnRubric() {
		if(\CModule::IncludeModule("subscribe")) {

			$userSubscribtionId = $this->request["user_subscription_id"];
			$rubricList = $this->request["rubric_list"];

			global $USER;
			if (!is_object($USER)) {
				$USER = new \CUser;
			}

			$subscribe = new \CSubscription;

			if($userSubscribtionId != 'false') {
				$arFields = Array(
					"ID" => intval($userSubscribtionId),
					"USER_ID" => ($USER->IsAuthorized() ? $USER->GetID() : false),
					"EMAIL" => $USER->GetEmail(),
					"RUB_ID" => $rubricList,
					"ACTIVE"=>"Y",
				);
				$subscribe->Update(intval($userSubscribtionId), $arFields);
			}
			else 
			{
				$addSubscribe = $subscribe->Add([
					'USER_ID' => ($USER->IsAuthorized() ? $USER->GetID() : false),
					'FORMAT' => 'html',
					'EMAIL' => $USER->GetEmail(),
					'ACTIVE' => 'Y',
					'RUB_ID' => $rubricList,
					'SEND_CONFIRM' => 'N'
				]);
			}
			
			$result['ID'] = $addSubscribe;
			return $this->setOK($result);
		}
	}
}