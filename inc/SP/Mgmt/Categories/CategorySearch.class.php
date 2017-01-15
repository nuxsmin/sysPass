<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Mgmt\Categories;

defined('APP_ROOT') || die();

use SP\DataModel\ItemSearchData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class CategorySearch
 *
 * @package SP\Mgmt\Categories
 */
class CategorySearch extends CategoryBase implements ItemSearchInterface
{
    /**
     * Obtiene el listado de categorías mediante una búsqueda
     *
     * @param ItemSearchData $SearchData
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('category_id, category_name, category_description');
        $Data->setFrom('categories');
        $Data->setOrder('category_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('category_name LIKE ? OR category_description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}