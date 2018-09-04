<?
namespace Aniart\Main\Repositories;

use Aniart\Main\Models\HLElementModel;
use Aniart\Main\Models\Type;
use Bitrix\Highloadblock\HighloadBlockTable;

class TypesRepository extends AbstractHLBlockElementsRepository {

    public function newInstance(array $fields)
    {
        return new Type($fields);
    }
}