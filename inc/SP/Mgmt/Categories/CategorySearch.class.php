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

namespace SP\Mgmt\Categories;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * @param int    $limitCount
     * @param int    $limitStart
     * @param string $search La cadena de búsqueda
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public function getMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = 'SELECT category_id, category_name, category_description FROM categories';

        $Data = new QueryData();

        if (!empty($search)) {
            $query .= ' WHERE category_name LIKE ? OR category_description LIKE ?';
            $search = '%' . $search . '%';

            $Data->addParam($search);
            $Data->addParam($search);
        }

        $query .= ' ORDER BY category_name';
        $query .= ' LIMIT ?,?';

        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }
}