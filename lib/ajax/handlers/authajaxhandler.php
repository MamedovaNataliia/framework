<?php

namespace Aniart\Main\Ajax\Handlers;

use Aniart\Main\Ajax\AbstractAjaxHandler;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::includeModule('subscribe');

Loc::loadMessages(__FILE__);

class AuthAjaxHandler extends AbstractAjaxHandler
{

    protected function getFunction()
    {
        return $this->request['func'];
    }

    protected function getLogin()
    {
        global $USER;
        global $APPLICATION;
        if(!is_object($USER))
        {
            $USER = new \CUser;
        }
        $data = $this->post['form'];
        $authResult = $USER->Login($data['LOGIN'], $data['PASSWORD'], 'Y');
        $APPLICATION->arAuthResult = $authResult;
        if($authResult['TYPE'] == 'ERROR')
        {
            return $this->setError($authResult);
        }
        return $this->setOK($authResult);
    }
    
    protected function getRegister()
    {
        global $USER;
        if(!is_object($USER))
        {
            $USER = new \CUser;
        }
        $data = $this->post['form'];
        $result = $USER->Register(
            $data['EMAIL'], 
            $data['NAME'], 
            $data['LAST_NAME'], 
            $data['PASSWORD'], 
            $data['CONFIRM_PASSWORD'], 
            $data['EMAIL']
        );
        
        //save dop params!
        
        if($result['TYPE'] == 'ERROR')
        {
            return $this->setError($result);
        }
        $userId = $USER->GetID();
        
        //update user params..
        $update = $USER->update(
            $userId,
            [
                'PERSONAL_CITY'=>$data['CITY'],
                'PERSONAL_PHONE'=>$data['PHONE'],
                'PERSONAL_STREET'=>$data['STREET'],
                'UF_HOUSE'=>$data['HOUSE'],
                'UF_FLAT'=>$data['FLAT']
            ]
        );
        if($update)
        {
            $result['UPDATE'] = $update;
        }
        else
        {
            $result['UPDATE'] = $update->LAST_ERROR;
        }
        //..update user params
        
        //add subscribe..
        if($data['SUB'] != 'on')
        {
            return $this->setOK($result);
        }
        $subscribe = new \CSubscription;
        $addSubscribe = $subscribe->Add([
            'USER_ID' => ($USER->IsAuthorized() ? $userId : false),
            'FORMAT' => 'html',
            'EMAIL' => $data['EMAIL'],
            'ACTIVE' => 'Y',
            'RUB_ID' => [1],
            'SEND_CONFIRM' => 'Y'
        ]);
        if(!empty($addSubscribe))
        {
            $result['SUB'] = \CSubscription::Authorize($addSubscribe);
        }
        else
        {
            $result['SUB'] = $subscribe->LAST_ERROR;
        }
        //..add subscribe
        
        return $this->setOK($result);
    }
    
    protected function getLogout()
    {
        global $USER;
        if(!is_object($USER))
        {
            $USER = new \CUser;
        }
        $result = $USER->Logout();
        return $this->setOK($result);
    }

}
