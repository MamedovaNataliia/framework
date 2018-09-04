<?php

use Bitrix\Main\EventManager;
$eventManager = EventManager::getInstance();

//mod catalog import *
$eventManager->addEventHandler(
    'catalog', 
    'OnBeforeCatalogImport1C', 
    ['Aniart\Main\Observers\CatalogImportObserver', 'onBeforeCatalogImport1C']
);