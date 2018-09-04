<?php


namespace Aniart\Main\Ajax\Handlers;


use Aniart\Main\Ajax\AbstractAjaxHandler;
use Bitrix\Main\Security\Sign\BadSignatureException;

class CatalogAjaxHandler extends AbstractAjaxHandler
{
	public function loadPage()
	{
		$page = (int)$this->post['page'];
		if($page < 0){
			return $this->setError('Invalid page value "'.$page.'"');
		}
		try{
			$componentParams = $this->getComponentParamsFromRequest('products.list', 'componentParams');
			return $this->setOK(['html' => $this->getProductListHTML($componentParams)]);
		}
		catch (BadSignatureException $e){
			return $this->setError($e->getMessage());
		}

	}

	private function getProductListHTML($params)
	{
        
        //new \dBug($params);
        
		global $APPLICATION;
		ob_start();
			$APPLICATION->IncludeComponent('aniart:products.list', 'main', $params);
			$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}