<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\DataModel\CustomFieldDefData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class CustomFieldSearch
 *
 * @package SP\Mgmt\CustomFields
 */
class CustomFieldDefSearch extends CustomFieldBase implements ItemSearchInterface
{
    /**
     * @param        $limitCount
     * @param int    $limitStart
     * @param string $search
     * @return CustomFieldDefData[]|array
     */
    public function getMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = /** @lang SQL */
            'SELECT customfielddef_id,
            customfielddef_module,
            customfielddef_field
            FROM customFieldsDef
            ORDER BY customfielddef_module
            LIMIT ?, ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldDefData');
        $Data->setQuery($query);
        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $customFields = array();

        foreach ($queryRes as $CustomField) {
            /**
             * @var CustomFieldDefData $CustomField
             * @var CustomFieldDefData $fieldDef
             */
            $fieldDef = unserialize($CustomField->getCustomfielddefField());

            if (get_class($fieldDef) === '__PHP_Incomplete_Class') {
                $fieldDef = Util::castToClass('SP\DataModel\CustomFieldDefData', $fieldDef);
            }

            if (empty($search)
                || stripos($fieldDef->getName(), $search) !== false
                || stripos(CustomFieldTypes::getFieldsTypes($fieldDef->getType(), true), $search) !== false
                || stripos(CustomFieldTypes::getFieldsModules($CustomField->getCustomfielddefModule()), $search) !== false
            ) {
                $fieldDef->setId($CustomField->getCustomfielddefId());

                $customFields[] = $fieldDef;
            }
        }

        $customFields['count'] = DB::$lastNumRows;

        return $customFields;
    }
}