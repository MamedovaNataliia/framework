<?
namespace Aniart\Main\Repositories;

use Aniart\Main\Models\HLElementModel;
use Bitrix\Highloadblock\HighloadBlockTable;

class HLBlockRepository extends AbstractHLBlockElementsRepository {

    public static function resolveTableName($tableName)
    {
        $res = HighloadBlockTable::getList(["filter" => ["TABLE_NAME" => $tableName]])->Fetch();
        return $res["ID"];
    }

    public function newInstance(array $fields)
    {
        return new HLElementModel($fields);
    }
}