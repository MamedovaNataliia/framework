<?php

namespace Aniart\Main\Ajax\Handlers;

use Aniart\Main\Ajax\AbstractAjaxHandler;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CommonAjaxHandler extends AbstractAjaxHandler
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function getFunction()
    {
        return $this->request['func'];
    }

    public function addError()
    {
        $text = $this->request['text'];
        $url = $this->request['url'];
        if(!empty($text) && !empty($url))
        {
            $error = new \Aniart\Main\Repositories\HandbooksRepository(HL_ERRORS_ID);
            $error->add(["UF_URL" => $url, "UF_TEXT" => $text]);
            $this->setOK(array('json' => $this->request));
        }
    }
    
    public function getInstagramProduct()
    {
    	global $APPLICATION;
    	ob_start();
    	
    	$id = $this->request['id_post'];
    	$GLOBALS['arrFilterInstagram'] = array(
    		'=ID'=> $id,
		);
    	$APPLICATION->IncludeComponent(
    			"bitrix:news.list",
    			"window",
    			array(
    					"IBLOCK_TYPE" => "content",
    					"IBLOCKS" => INSTAGRAM_IBLOCK_ID,
    					"NEWS_COUNT" => "1",
    					"FIELD_CODE" => array(
    							0 => "",
    							1 => "4",
    							2 => "Инстаграм",
    							3 => "",
    					),
    					"SORT_BY1" => "ACTIVE_FROM",
    					"SORT_ORDER1" => "DESC",
    					"SORT_BY2" => "SORT",
    					"SORT_ORDER2" => "ASC",
    					"DETAIL_URL" => "news_detail.php?ID=#ELEMENT_ID#",
    					"ACTIVE_DATE_FORMAT" => "d.m.Y",
    					"CACHE_TYPE" => "A",
    					"CACHE_TIME" => "300",
    					"CACHE_GROUPS" => "Y",
    					"COMPONENT_TEMPLATE" => "instagram",
    					"IBLOCK_ID" => "4",
    					"FILTER_NAME" => "arrFilterInstagram",
    					"PROPERTY_CODE" => array(
    							0 => "",
    							1 => "",
    					),
    					"CHECK_DATES" => "Y",
    					"AJAX_MODE" => "N",
    					"AJAX_OPTION_JUMP" => "N",
    					"AJAX_OPTION_STYLE" => "Y",
    					"AJAX_OPTION_HISTORY" => "N",
    					"AJAX_OPTION_ADDITIONAL" => "",
    					"CACHE_FILTER" => "N",
    					"PREVIEW_TRUNCATE_LEN" => "",
    					"SET_TITLE" => "Y",
    					"SET_BROWSER_TITLE" => "Y",
    					"SET_META_KEYWORDS" => "Y",
    					"SET_META_DESCRIPTION" => "Y",
    					"SET_LAST_MODIFIED" => "N",
    					"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
    					"ADD_SECTIONS_CHAIN" => "Y",
    					"HIDE_LINK_WHEN_NO_DETAIL" => "N",
    					"PARENT_SECTION" => "",
    					"PARENT_SECTION_CODE" => "",
    					"INCLUDE_SUBSECTIONS" => "Y",
    					"STRICT_SECTION_CHECK" => "N",
    					"PAGER_TEMPLATE" => ".default",
    					"DISPLAY_TOP_PAGER" => "N",
    					"DISPLAY_BOTTOM_PAGER" => "Y",
    					"PAGER_TITLE" => "Новости",
    					"PAGER_SHOW_ALWAYS" => "N",
    					"PAGER_DESC_NUMBERING" => "N",
    					"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
    					"PAGER_SHOW_ALL" => "N",
    					"PAGER_BASE_LINK_ENABLE" => "N",
    					"SET_STATUS_404" => "N",
    					"SHOW_404" => "N",
    					"MESSAGE_404" => ""
    			),
    			false
		);
    	
    	$html = ob_get_contents();

			//$html = 'test';

    	ob_end_clean();
    	$this->setOK($html);
    }

}
