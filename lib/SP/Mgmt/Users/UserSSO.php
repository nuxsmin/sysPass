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
use SP\DataModel\UserLoginData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Class UserSSO
 *
 * @package SP\Mgmt\Users
 */
class UserSSO extends User
{
    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @param $userLogin
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkUserInDB($userLogin)
    {
        $query = /** @lang SQL */
            'SELECT login FROM User WHERE LOWER(login) = LOWER(?) OR LOWER(ssoLogin) = LOWER(?) LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin);
        $Data->addParam($userLogin);

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __u('Login/email de usuario duplicados'));
        }

        $groupId = $this->ConfigData->getSsoDefaultGroup();
        $profileId = $this->ConfigData->getSsoDefaultProfile();

        $this->itemData->setIsDisabled(($groupId === 0 || $profileId === 0) ? 1 : 0);

        $query = /** @lang SQL */
            'INSERT INTO usrData SET
            user_name = ?,
            user_login = ?,
            user_ssoLogin = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_mPass = \'\',
            user_mKey = \'\',
            user_isDisabled = ?,
            user_pass = ?,
            user_hashSalt = \'\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getLogin());
        $Data->addParam($this->itemData->getLogin());
        $Data->addParam($this->itemData->getLogin());
        $Data->addParam(__('Usuario de SSO'));
        $Data->addParam($groupId);
        $Data->addParam($profileId);
        $Data->addParam((int)$this->itemData->isIsDisabled());
        $Data->addParam(Hash::hashKey($this->itemData->getLoginPass()));
        $Data->setOnErrorMessage(__('Error al guardar los datos de SSO', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::getLastId());

        $Log = new Log();
        $Log->getLogMessage()
            ->setAction(__('Nuevo usuario de SSO', false))
            ->addDescription(sprintf('%s (%s)', $this->itemData->getName(), $this->itemData->getLogin()));
        $Log->writeLog();

        Email::sendEmail($Log->getLogMessage());

        return $this;
    }

    /**
     * Comprobar duplicados por login e email en minúsculas
     *
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT user_login FROM usrData WHERE LOWER(user_login) = LOWER(?) OR LOWER(user_ssoLogin) = LOWER(?)';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getLogin());
        $Data->addParam($this->itemData->getLogin());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Actualizar al realizar login
     *
     * @param UserLoginData $itemData
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateOnLogin(UserLoginData $itemData)
    {
        $query = 'UPDATE User SET 
            pass = ?,
            hashSalt = \'\',
            lastUpdate = NOW(),
            lastLogin = NOW()
            WHERE LOWER(login) = LOWER(?) OR LOWER(ssoLogin) = LOWER(?) LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Hash::hashKey($itemData->getLoginPass()));
        $Data->addParam($itemData->getLoginUser());
        $Data->addParam($itemData->getLoginUser());
        $Data->setOnErrorMessage(__u('Error al actualizar la clave del usuario en la BBDD'));

        DbWrapper::getQuery($Data);

        return $this;
    }
}