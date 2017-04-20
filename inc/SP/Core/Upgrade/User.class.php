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

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\SPException;
use SP\Core\OldCrypt;
use SP\Core\TaskFactory;
use SP\DataModel\UserLoginData;
use SP\Mgmt\Users\UserPass;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class User
 *
 * @package SP\Core\Upgrade
 */
class User
{
    /**
     * Actualizar registros con usuarios no existentes
     *
     * @param int $userId Id de usuario por defecto
     * @return bool
     */
    public static function fixUsersId($userId)
    {
        TaskFactory::$Message->setTask(__FUNCTION__);
        TaskFactory::$Message->setMessage(__('Actualizando IDs de usuarios'));
        TaskFactory::sendTaskMessage();

        try {
            DB::beginTransaction();

            $Data = new QueryData();
            $Data->setQuery('SELECT user_id FROM usrData ORDER BY user_id');

            $users = DB::getResultsArray($Data);

            $paramsIn = trim(str_repeat(',?', count($users)), ',');

            if ($userId === 0) {
                $groupId = Group::createOrphanGroup();
                $profileId = Profile::createOrphanProfile();
                $userId = self::createOrphanUser($groupId, $profileId);
            }

            $Data->addParam($userId);

            foreach ($users as $user) {
                $Data->addParam($user->user_id);
            }

            $query = /** @lang SQL */
                'UPDATE accounts SET account_userId = ? WHERE account_userId NOT IN (' . $paramsIn . ') OR account_userId IS NULL ';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accounts SET account_userEditId = ? WHERE account_userEditId NOT IN (' . $paramsIn . ') OR account_userEditId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_userId = ? WHERE acchistory_userId NOT IN (' . $paramsIn . ') OR acchistory_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_userEditId = ? WHERE acchistory_userEditId NOT IN (' . $paramsIn . ') OR acchistory_userEditId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM usrPassRecover WHERE userpassr_userId <> ? AND userpassr_userId NOT IN (' . $paramsIn . ')';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM usrToGroups WHERE usertogroup_userId <> ? AND usertogroup_userId NOT IN (' . $paramsIn . ') OR usertogroup_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM accUsers WHERE accuser_userId <> ? AND accuser_userId NOT IN (' . $paramsIn . ') OR accuser_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            DB::endTransaction();

            return true;
        } catch (SPException $e) {
            DB::rollbackTransaction();

            return false;
        }
    }

    /**
     * Crear un usuario para elementos huérfanos
     *
     * @param $groupId
     * @param $profileId
     * @return int
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function createOrphanUser($groupId, $profileId)
    {
        $query = /** @lang SQL */
            'INSERT INTO usrData SET
            user_name = \'Orphan User\',
            user_login = \'orphan_user\',
            user_notes = \'Created by the upgrade process\',
            user_groupId = ?,
            user_profileId = ?,
            user_mIV = \'\',
            user_isDisabled = 1,
            user_pass = \'\',
            user_hashSalt = \'\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($groupId);
        $Data->addParam($profileId);
        $Data->setOnErrorMessage(__('Error al crear el usuario', false));

        DB::getQuery($Data);

        return DB::getLastId();
    }

    /**
     * Actualizar la clave maestra
     *
     * @param UserLoginData $UserData
     * @return bool
     */
    public static function upgradeMasterKey(UserLoginData $UserData)
    {
        $key = OldCrypt::generateAesKey($UserData->getLoginPass() . $UserData->getLogin());
        $mKey = OldCrypt::getDecrypt($UserData->getUserMPass(), $UserData->getUserMKey(), $key);

        try {
            return $mKey && UserPass::updateUserMPass($mKey, $UserData);
        } catch (SPException $e) {
        } catch (CryptoException $e) {
        }

        return false;
    }
}