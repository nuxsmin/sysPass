<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP\Mgmt\Profiles;

use SP\Core\SPException;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ProfileUtil
 *
 * @package SP\Mgmt\User
 */
class ProfileUtil
{
    /**
     * Obtener los perfiles de una búsqueda
     *
     * @param $limitCount
     * @param int $limitStart
     * @param string $search
     * @return array|bool
     */
    public static function getProfilesMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = 'SELECT userprofile_id, userprofile_name FROM usrProfiles';

        $Data = new QueryData();

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query .= ' WHERE userprofile_name LIKE ?';

            if (Checks::demoIsEnabled()) {
                $query .= ' userprofile_name <> "Admin" AND userprofile_name <> "Demo"';
            }

            $Data->addParam($search);
        } elseif (Checks::demoIsEnabled()) {
            $query .= ' WHERE userprofile_name <> "Admin" AND userprofile_name <> "Demo"';
        }

        $query .= ' ORDER BY userprofile_name';
        $query .= ' LIMIT ?, ?';

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

    /**
     * Obtener los datos de un perfil
     *
     * @param $id int El id del perfil a obtener
     * @return array|Profile
     * @throws SPException
     */
    public static function getProfile($id)
    {
        $query = 'SELECT userprofile_profile FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        /**
         * @var Profile $profile
         */
        $profile = unserialize($queryRes->userprofile_profile);

        if (get_class($profile) === '__PHP_Incomplete_Class') {
            return Util::castToClass('SP\Mgmt\Profiles\Profile', $profile);
        }

        return $profile;
    }

    /**
     * Obtener los perfiles disponibles
     *
     * @return array|bool
     */
    public static function getProfiles()
    {
        if (Checks::demoIsEnabled()) {
            $query = 'SELECT userprofile_id, userprofile_name FROM usrProfiles WHERE userprofile_name <> "Admin" AND userprofile_name <> "Demo" ORDER BY userprofile_name';
        } else {
            $query = 'SELECT userprofile_id, userprofile_name FROM usrProfiles ORDER BY userprofile_name';
        }

        $Data = new QueryData();
        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }
}