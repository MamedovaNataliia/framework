<?php


namespace Aniart\Main\Models;


class SalePaySystem extends AbstractModel
{
    protected $action;
	protected $actionParams;

    public function getName($lang = null)
    {
        $lang = $lang ?: i18n()->lang();
        if(i18n()->isLangDefault($lang)){
            $name = $this->fields['NAME'];
        }
        else{
            $name = i18n("PAY_SYSTEM_".$this->getId(), 'order', $lang);
        }
        return $name;
    }

    public function getDescription($lang = 'ru')
    {
        $lang = $lang ?: i18n()->lang();
        if(i18n()->isLangDefault($lang)){
            $desc = $this->fields['DESCRIPTION'];
        }
        else{
            $desc = i18n("PAY_SYSTEM_".$this->getId().'_DESCRIPTION', 'order', $lang);
        }
        return $desc;

    }

    public function isChecked()
    {
        return isset($this->fields['CHECKED']) && $this->fields['CHECKED'] == 'Y';
    }

    public function hasLogo()
    {
        $logo = $this->getLogo();
        return !empty($logo);
    }

    public function getLogo()
    {
        return $this->fields['PSA_LOGOTIP'];
    }

    public function resizeLogo($width, $height)
    {
        $logo = $this->getLogo();
        if($logo['ID']){
            return \CFile::ResizeImageGet(
                $logo['ID'], array('width' => $width, 'height' => $height, 'BX_RESIZE_IMAGE_PROPORTIONAL', true)
            );
        }
        return false;
    }

    protected function getActionFileId($personTypeId)
    {
        $actionFileId = false;
        $actionFile   = $this->getActionFile($personTypeId);
        if($actionFile){
            $actionFile = explode('/', $actionFile);
            $actionFileId = end($actionFile);
        }
        return $actionFileId ? $actionFileId : false;
    }

	public function getActionFile($personTypeId)
	{
        if(!$this->fields['ACTION_FILE']){
            $this->fields['ACTION_FILE'] = $this->getAction($personTypeId)['ACTION_FILE'];
        }
        return $this->fields['ACTION_FILE'];
	}

    public function getAction($personTypeId)
    {
        if(is_null($this->action[$personTypeId])){
            $this->obtainActionParams($personTypeId);
        }
        return $this->action[$personTypeId];
    }

	public function getActionParams($personTypeId)
	{
		if(is_null($this->actionParams[$personTypeId])){
			$this->obtainActionParams($personTypeId);
		}
		return $this->actionParams[$personTypeId];
	}

	public function obtainActionParams($personTypeId)
	{
		$this->actionParams[$personTypeId] = array();
		$rsActions = \CSalePaySystemAction::GetList(array(), array(
			'PAY_SYSTEM_ID' => $this->getId(), 'PERSON_TYPE_ID' => $personTypeId
		));
		if($arAction = $rsActions->Fetch()){
            $this->action[$personTypeId] = $arAction;
            $this->actionParams[$personTypeId] = unserialize($arAction['PARAMS']);
		}
		return $this;
	}
}