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

use SP\Core\Exceptions\SPException;
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
        try {
            DB::beginTransaction();

            $Data = new QueryData();

            $query = /** @lang SQL */
                'UPDATE usrData SET user_groupId = ? WHERE user_groupId NOT IN (SELECT usergroup_id FROM usrGroups ORDER BY usergroup_id) OR user_groupId IS NULL';
            $Data->setQuery($query);
            $Data->addParam($groupId);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM usrToGroups WHERE usertogroup_groupId NOT IN (SELECT usergroup_id FROM usrGroups ORDER BY usergroup_id) OR usertogroup_groupId IS NULL';
            $Data->setQuery($query);
            $Data->setParams([]);

            DB::getQuery($Data);

            DB::endTransaction();

            return true;
        } catch (SPException $e) {
            DB::rollbackTransaction();

            return false;
        }
    }

    /**
     * Crear un grupo para elementos huérfanos
     *
     * @return int
     */
    public static function createOrphanGroup()
    {
        $query = /** @lang SQL */
            'INSERT INTO usrGroups SET
            usergroup_name = \'Orphan group\',
            usergroup_description = \'Created by the upgrade process\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al crear el grupo', false));

        DB::getQuery($Data);

        return DB::getLastId();
    }
}