<?php
namespace Aniart\Main\Ajax\Handlers;
use Aniart\Main\Ajax\AbstractAjaxHandler;
use Aniart\Main\FavoritesTable;
use Bitrix\Main\Security\Sign\BadSignatureException;

class FavoritesAjaxHandler extends AbstractAjaxHandler
{


    public function __construct()
    {

        parent::__construct();
    }

    protected function getUserId($userId = 0){
        $userId = (int)$userId;
        if($userId <= 0){
            global $USER;
            if(is_object($USER)){
                $userId = $USER->GetID();
            }
        }

        return $userId;
    }

    public function addToFavorite()
    {
        $userId = $this->getUserId();
        $prodId = (int)$this->post['prodId'];
        if($this->isAdded($prodId)){
            return $this->setError('Этот товар уже находится у вас в "Избранном"');
        }
        else{

            if($userId && $prodId){
                $result = FavoritesTable::add(array(
                    'USER_ID' => $userId,
                    'PRODUCT_ID' => $prodId
                ));
                if($result->isSuccess()){
                    return $this->setOk(array(
                            'type' => 'ok',
                            'message' => 'Товар добавлен в "Избранное"'
                    ));
                }
                else{
                    //случайная ошибка, перевод не требуется
                    return $this->setError($result->getErrorMessages());
                }
            }
            if(!$userId) {
                return $this->setError('need_auth');
            }
        }
    }

    public function removeFromFavorite()
    {
        $prodId = (int)$this->post['prodId'];
        if($favId = $this->isAdded($prodId)){
            $result = FavoritesTable::delete($favId);
            if($result->isSuccess()){
                return $this->setOk();
            }
            else{
                return $this->setError($result->getErrorMessages());
            }
        }
        else{
            return $this->setError('Этого товара уже нет у вас в "Избранном"');
        }
    }
    public function loadPage()
    {
        $page = (int)$this->post['page'];
        if($page < 0){
            return $this->setError('Invalid page value "'.$page.'"');
        }
        try{
            $componentParams = $this->getComponentParamsFromRequest('user.favorites', 'componentParams');
            return $this->setOK(['html' => $this->getProductListHTML($componentParams)]);
        }
        catch (BadSignatureException $e){
            return $this->setError($e->getMessage());
        }

    }
    protected function isAdded($prodId, $userId = null)
    {
        $userId = $this->getUserId($userId);
        $prodId = (int)$prodId;
        if($prodId && $userId){
            $rsFav = FavoritesTable::getList(array(
                'select' => array('ID'),
                'filter' => array('=USER_ID' => $userId, '=PRODUCT_ID' => $prodId)
            ));
            if($fav = $rsFav->fetch()){
                return $fav['ID'];
            }

        }
        return false;
    }

    private function getProductListHTML($params)
    {

        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent('aniart:user.favorites', 'wish_list', $params);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
?>