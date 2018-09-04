<?php


namespace Aniart\Main\Models;


use Aniart\Main\Multilang\Models\IblockSectionModelML;

class ProductSection extends IblockSectionModelML
{
    public function getCoverPicture()
    {
        $picture = [
            'src' => '',
            'height' => '',
            'width' => '',
            'alt' => ''
        ];
        if($coverPictureId = $this->getCoverPictureId()){
            $fileData = \CFile::GetFileArray($coverPictureId);
            if(!empty($fileData)){
                $picture = [
                    'src' => $fileData['SRC'],
                    'width' => $fileData['WIDTH'],
                    'height' => $fileData['HEIGHT'],
                    'alt' => $picture['alt']
                ];
            }
        }
        return $picture;
    }

    public function getCoverPictureId()
    {
        return $this->getPropertyValue('CATALOG_COVER');
    }
}