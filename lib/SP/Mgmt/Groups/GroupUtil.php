<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Groups;


use SP\DataModel\UserGroupData;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class GroupsUtil
 *
 * @package SP\Mgmt\Groups
 */
class GroupUtil
{
    /**
     * Obtener el id de un grupo por a partir del nombre.
     *
     * @param int $name con el nombre del grupo
     * @return UserGroupData|false
     */
    public static function getGroupIdByName($name)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name FROM usrGroups WHERE usergroup_name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserGroupData::class);
        $Data->setQuery($query);
        $Data->addParam($name);

        return DbWrapper::getResults($Data);
    }
}