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

namespace SP\Core\Upgrade;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileData;
use SP\Services\Task\TaskFactory;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class Profile
 *
 * @package SP\Core\Upgrade
 */
class Profile
{
    protected static $orphanProfileId;

    /**
     * Actualizar registros con perfiles no existentes
     *
     * @param int $profileId Id de perfil por defecto
     * @return bool
     */
    public static function fixProfilesId($profileId)
    {
        $Data = new QueryData();

        try {
            TaskFactory::$Message->setTask(__FUNCTION__);
            TaskFactory::$Message->setMessage(__('Actualizando IDs de perfil'));
            TaskFactory::update();

            DbWrapper::beginTransaction();

            if ($profileId === 0){
                $profileId = self::$orphanProfileId ?: self::createOrphanProfile();
            }

            $query = /** @lang SQL */
                'UPDATE usrData SET user_profileId = ? WHERE user_profileId NOT IN (SELECT id FROM usrProfiles ORDER BY id) OR user_profileId IS NULL';
            $Data->setQuery($query);
            $Data->addParam($profileId);

            DbWrapper::getQuery($Data);

            DbWrapper::endTransaction();

            return true;
        } catch (SPException $e) {
            DbWrapper::rollbackTransaction();

            return false;
        }
    }

    /**
     * Crear un perfil para elementos huérfanos
     *
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function createOrphanProfile()
    {
        $query = /** @lang SQL */
            ',
            userProfile_profile = ?\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(serialize(new ProfileData()));
        $Data->setOnErrorMessage(__('Error al crear perfil', false));

        DbWrapper::getQuery($Data);

        self::$orphanProfileId = DbWrapper::getLastId();

        return self::$orphanProfileId;
    }
}