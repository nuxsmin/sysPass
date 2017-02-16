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
 * Class Profile
 * @package SP\Core\Upgrade
 */
class Profile
{
    /**
     * Actualizar registros con perfiles no existentes
     * @param int $profileId Id de perfil por defecto
     * @return bool
     */
    public static function fixProfilesId($profileId)
    {
        $Data = new QueryData();
        $Data->setQuery('SELECT userprofile_id FROM usrProfiles ORDER BY userprofile_id');

        $profiles = DB::getResultsArray($Data);

        $paramsIn = trim(str_repeat(',?', count($profiles)), ',');
        $Data->addParam($profileId);

        foreach ($profiles as $profile) {
            $Data->addParam($profile->userprofile_id);
        }

        $query = /** @lang SQL */
            'UPDATE usrData SET user_profileId = ? WHERE user_profileId NOT IN (' . $paramsIn . ') OR user_profileId IS NULL';
        $Data->setQuery($query);

        DB::getQuery($Data);

        return true;
    }
}