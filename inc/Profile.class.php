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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre los perfiles de usuarios.
 */
class Profile extends ProfileBase
{
    /**
     * Migrar los perfiles con formato anterior a v1.2
     *
     * @return bool
     */
    public static function migrateProfiles()
    {
        $query = 'SELECT userprofile_id AS id,'
            . 'userprofile_name AS name,'
            . 'BIN(userProfile_pView) AS pView,'
            . 'BIN(userProfile_pViewPass) AS pViewPass,'
            . 'BIN(userProfile_pViewHistory) AS pViewHistory,'
            . 'BIN(userProfile_pEdit) AS pEdit,'
            . 'BIN(userProfile_pEditPass) AS pEditPass,'
            . 'BIN(userProfile_pAdd) AS pAdd,'
            . 'BIN(userProfile_pDelete) AS pDelete,'
            . 'BIN(userProfile_pFiles) AS pFiles,'
            . 'BIN(userProfile_pConfig) AS pConfig,'
            . 'BIN(userProfile_pConfigMasterPass) AS pConfigMasterPass,'
            . 'BIN(userProfile_pConfigBackup) AS pConfigBackup,'
            . 'BIN(userProfile_pAppMgmtCategories) AS pAppMgmtCategories,'
            . 'BIN(userProfile_pAppMgmtCustomers) AS pAppMgmtCustomers,'
            . 'BIN(userProfile_pUsers) AS pUsers,'
            . 'BIN(userProfile_pGroups) AS pGroups,'
            . 'BIN(userProfile_pProfiles) AS pProfiles,'
            . 'BIN(userProfile_pEventlog) AS pEventlog '
            . 'FROM usrProfiles';

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            Log::writeNewLog(_('Migrar Perfiles'), _('Error al obtener perfiles'));
            return false;
        }

        foreach ($queryRes as $oldProfile){
            $profile = new Profile();
            $profile->setId($oldProfile->id);
            $profile->setName($oldProfile->name);
            $profile->setAccAdd($oldProfile->pAdd);
            $profile->setAccView($oldProfile->pView);
            $profile->setAccViewPass($oldProfile->pViewPass);
            $profile->setAccViewHistory($oldProfile->pViewHistory);
            $profile->setAccEdit($oldProfile->pEdit);
            $profile->setAccEditPass($oldProfile->pEditPass);
            $profile->setAccDelete($oldProfile->pDelete);
            $profile->setConfigGeneral($oldProfile->pConfig);
            $profile->setConfigEncryption($oldProfile->pConfigMasterPass);
            $profile->setConfigBackup($oldProfile->pConfigBackup);
            $profile->setMgmCategories($oldProfile->pAppMgmtCategories);
            $profile->setMgmCustomers($oldProfile->pAppMgmtCustomers);
            $profile->setMgmUsers($oldProfile->pUsers);
            $profile->setMgmGroups($oldProfile->pGroups);
            $profile->setMgmProfiles($oldProfile->pProfiles);
            $profile->setEvl($oldProfile->pEventlog);

            if ($profile->profileUpdate() === false){
                return false;
            }
        }

        $query = 'ALTER TABLE usrProfiles '
            . 'DROP COLUMN userProfile_pAppMgmtCustomers,'
            . 'DROP COLUMN userProfile_pAppMgmtCategories,'
            . 'DROP COLUMN userProfile_pAppMgmtMenu,'
            . 'DROP COLUMN userProfile_pUsersMenu,'
            . 'DROP COLUMN userProfile_pConfigMenu,'
            . 'DROP COLUMN userProfile_pFiles,'
            . 'DROP COLUMN userProfile_pViewHistory,'
            . 'DROP COLUMN userProfile_pEventlog,'
            . 'DROP COLUMN userProfile_pEditPass,'
            . 'DROP COLUMN userProfile_pViewPass,'
            . 'DROP COLUMN userProfile_pDelete,'
            . 'DROP COLUMN userProfile_pProfiles,'
            . 'DROP COLUMN userProfile_pGroups,'
            . 'DROP COLUMN userProfile_pUsers,'
            . 'DROP COLUMN userProfile_pConfigBackup,'
            . 'DROP COLUMN userProfile_pConfigMasterPass,'
            . 'DROP COLUMN userProfile_pConfig,'
            . 'DROP COLUMN userProfile_pAdd,'
            . 'DROP COLUMN userProfile_pEdit,'
            . 'DROP COLUMN userProfile_pView';

        $queryRes = DB::getQuery($query, __FUNCTION__);

        $log = new Log(_('Migrar Perfiles'));

        if ($queryRes) {
            $log->addDescription(_('Operación realizada correctamente'));
        } else {
            $log->addDescription(_('Migrar Perfiles'), _('Fallo al realizar la operación'));
        }

        $log->writeLog();

        Email::sendEmail($log);

        return $queryRes;
    }

    /**
     * Comprobar si un perfil existe
     *
     * @param $id int El id de perfil
     * @param $name string El nombre del perfil
     * @return bool
     */
    public static function checkProfileExist($id, $name)
    {
        $query = 'SELECT userprofile_name '
            . 'FROM usrProfiles '
            . 'WHERE UPPER(userprofile_name) = :name';

        $data['name'] = $name;

        if ($id !== 0) {
            $query .= ' AND userprofile_id != :id';

            $data['id'] = $id;
        }

        return (DB::getQuery($query, __FUNCTION__, $data) === true && DB::$lastNumRows >= 1);
    }

    /**
     * Comprobar si un perfil está en uso.
     *
     * @param $id int El id del perfil
     * @return bool|int Cadena con el número de usuarios, o bool si no está en uso
     */
    public static function checkProfileInUse($id)
    {
        $count['users'] = self::getProfileInUsersCount($id);
        return $count;
    }

    /**
     * Obtener el número de usuarios que usan un perfil.
     *
     * @param $id int El id del perfil
     * @return false|int con el número total de cuentas
     */
    private static function getProfileInUsersCount($id)
    {
        $query = 'SELECT user_profileId FROM usrData WHERE user_profileId = :id';

        $data['id'] = $id;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$lastNumRows;
    }

    /**
     * Obtener el nombre de los usuarios que usan un perfil.
     *
     * @param $id int El id del perfil
     * @return false|int con el número total de cuentas
     */
    public static function getProfileInUsersName($id)
    {
        $query = 'SELECT user_login FROM usrData WHERE user_profileId = :id';

        $data['id'] = $id;

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__, $data);
    }

    /**
     * Obtener el nombre de un perfil por a partir del Id.
     *
     * @param int $id con el Id del perfil
     * @return false|string con el nombre del perfil
     */
    public static function getProfileNameById($id)
    {
        $query = 'SELECT userprofile_name FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->userprofile_name;
    }
}
