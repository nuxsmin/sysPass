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

namespace SP\Mgmt\Users;

use SP\Config\Config;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\LogMessage;
use SP\DataModel\UserLoginData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Class UserLdap
 *
 * @package SP
 * @property UserLoginData $itemData
 */
class UserLdap extends User
{
    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @param $userLogin
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function checkLDAPUserInDB($userLogin)
    {
        $query = /** @lang SQL */
            'SELECT user_login FROM usrData WHERE LOWER(user_login) = LOWER(?) LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin);

        DB::getQuery($Data);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * @return mixed
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __('Login/email de usuario duplicados', false));
        }

        $groupId = Config::getConfig()->getLdapDefaultGroup();
        $profileId = Config::getConfig()->getLdapDefaultProfile();
        $this->itemData->setUserIsDisabled(($groupId === 0 || $profileId === 0) ? 1 : 0);

        $query = /** @lang SQL */
            'INSERT INTO usrData SET
            user_name = ?,
            user_login = ?,
            user_email = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_mPass = \'\',
            user_mKey = \'\',
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_isChangePass = ?,
            user_isLdap = 1,
            user_pass = ?,
            user_hashSalt = \'\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam(__('Usuario de LDAP'));
        $Data->addParam($groupId);
        $Data->addParam($profileId);
        $Data->addParam((int)$this->itemData->isUserIsAdminApp());
        $Data->addParam((int)$this->itemData->isUserIsAdminAcc());
        $Data->addParam((int)$this->itemData->isUserIsDisabled());
        $Data->addParam((int)$this->itemData->isUserIsChangePass());
        $Data->addParam(Hash::hashKey($this->itemData->getUserPass()));
        $Data->setOnErrorMessage(__('Error al guardar los datos de LDAP', false));

        DB::getQuery($Data);

        $this->itemData->setUserId(DB::getLastId());

        if (!$groupId || !$profileId) {
            $LogEmail = new LogMessage();
            $LogEmail->setAction(__('Activación Cuenta', false));
            $LogEmail->addDescription(__('Su cuenta está pendiente de activación.', false));
            $LogEmail->addDescription(__('En breve recibirá un email de confirmación.', false));

            Email::sendEmail($LogEmail, $this->itemData->getUserEmail(), false);
        }

        $Log = new Log();
        $Log->getLogMessage()
            ->setAction(__('Nuevo usuario de LDAP', false))
            ->addDescription(sprintf('%s (%s)', $this->itemData->getUserName(), $this->itemData->getUserLogin()));
        $Log->writeLog();

        Email::sendEmail($Log->getLogMessage());

        return $this;
    }

    /**
     * Comprobar duplicados por login e email en minúsculas
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT user_login, user_email
            FROM usrData
            WHERE LOWER(user_login) = LOWER(?) OR (? <> \'\' AND LOWER(user_email) = LOWER(?))';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserEmail());

        DB::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_name = ?,
            user_email = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_lastUpdate = NOW(),
            user_isLdap = 1
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserNotes());
        $Data->addParam($this->itemData->getUserGroupId());
        $Data->addParam($this->itemData->getUserProfileId());
        $Data->addParam($this->itemData->isUserIsAdminApp());
        $Data->addParam($this->itemData->isUserIsAdminAcc());
        $Data->addParam($this->itemData->isUserIsDisabled());
        $Data->addParam($this->itemData->getUserId());
        $Data->setOnErrorMessage(__('Error al actualizar el usuario', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateOnLogin()
    {
        $query = 'UPDATE usrData SET 
            user_pass = ?,
            user_hashSalt = \'\',
            user_name = ?,
            user_email = ?,
            user_lastUpdate = NOW(),
            user_lastLogin = NOW(),
            user_isLdap = 1 
            WHERE LOWER(user_login) = LOWER(?) LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Hash::hashKey($this->itemData->getLoginPass()));
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserLogin());
        $Data->setOnErrorMessage(__('Error al actualizar la clave del usuario en la BBDD', false));

        DB::getQuery($Data);

        return $this;
    }
}