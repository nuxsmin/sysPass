<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Core\Upgrade;

use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Group
 * @package SP\Core\Upgrade
 */
class Group
{
    /**
     * Actualizar registros con grupos no existentes
     * @param int $groupId Id de grupo por defecto
     * @return bool
     */
    public static function fixGroupId($groupId)
    {
        $Data = new QueryData();
        $Data->setQuery('SELECT usergroup_id FROM usrGroups ORDER BY usergroup_id');

        $groups = DB::getResultsArray($Data);

        $paramsIn = trim(str_repeat(',?', count($groups)), ',');
        $Data->addParam($groupId);

        foreach ($groups as $group) {
            $Data->addParam($group->usergroup_id);
        }

        $query = /** @lang SQL */
            'UPDATE usrData SET user_groupId = ? WHERE user_groupId NOT IN (' . $paramsIn . ')';
        $Data->setQuery($query);

        DB::getQuery($Data);

        return true;
    }
}