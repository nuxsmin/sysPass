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

namespace SP\Mgmt\Groups;


use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupsUtil
 *
 * @package SP\Mgmt\Groups
 */
class GroupsUtil
{
    /**
     * Obtener el id y nombre de los grupos disponibles
     *
     * @return array
     */
    public static function getGroupsName()
    {
        $query = 'SELECT usergroup_id, usergroup_name FROM usrGroups ORDER BY usergroup_name';

        $Data = new QueryData();
        $Data->setMapClassName('\SP\DataModel\GroupData');
        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }
}