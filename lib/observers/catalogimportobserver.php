<?php

namespace Aniart\Main\Observers;

use Aniart\Main\Exchange\ProductsImport;
use Aniart\Main\Exchange\OffersImport;
use Aniart\Main\Exchange\ProductsImportWriter;
use Aniart\Main\Exchange\OffersImportWriter;

/**
 * Catalog import
 * 
 */
class CatalogImportObserver
{
	public function onBeforeCatalogImport1C($params, $path)
	{
        $request = $_REQUEST;

        if($request['filename'] == 'import.xml')
        {
            $import = new ProductsImportWriter(
                new ProductsImport(),
                $path
            );

            return $import->write();
        }
        elseif($request['filename'] == 'offers.xml')
        {
            $import = new OffersImportWriter(
                new OffersImport(),
                $path
            );

            return $import->write();
        }
	}
}
