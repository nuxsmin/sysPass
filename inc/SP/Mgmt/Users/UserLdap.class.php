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

namespace SP\Mgmt\Users;

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserLdap
 *
 * @package SP
 */
class UserLdap extends UserBase implements ItemInterface
{
    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @param $userLogin
     * @return bool
     */
    public static function checkLDAPUserInDB($userLogin)
    {
        $query = /** @lang SQL */
            'SELECT user_login FROM usrData WHERE user_login = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin);

        return (DB::getQuery($Data) === true && $Data->getQueryNumRows() === 1);
    }

    /**
     * @return mixed
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, _('Login/email de usuario duplicados'));
        }

        $passdata = UserPass::makeUserPassHash($this->itemData->getUserPass());
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
            user_mIV = \'\',
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_isChangePass = ?,
            user_isLdap = 1,
            user_pass = ?,
            user_hashSalt = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam(_('Usuario de LDAP'));
        $Data->addParam($groupId);
        $Data->addParam($profileId);
        $Data->addParam(intval($this->itemData->isUserIsAdminApp()));
        $Data->addParam(intval($this->itemData->isUserIsAdminAcc()));
        $Data->addParam(intval($this->itemData->isUserIsDisabled()));
        $Data->addParam(intval($this->itemData->isUserIsChangePass()));
        $Data->addParam($passdata['pass'], 'pass');
        $Data->addParam($passdata['salt'], 'salt');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al guardar los datos de LDAP'));
        }

        $this->itemData->setUserId(DB::getLastId());

        if (!$groupId || !$profileId) {
            $LogEmail = new Log(_('Activación Cuenta'));
            $LogEmail->addDescription(_('Su cuenta está pendiente de activación.'));
            $LogEmail->addDescription(_('En breve recibirá un email de confirmación.'));

            Email::sendEmail($LogEmail, $this->itemData->getUserEmail(), false);
        }

        $Log = new Log(_('Nuevo usuario de LDAP'));
        $Log->addDescription(sprintf("%s (%s)", $this->itemData->getUserName(), $this->itemData->getUserLogin()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $passdata = UserPass::makeUserPassHash($this->itemData->getUserPass());

        $query = 'UPDATE usrData SET 
            user_pass = ?,
            user_hashSalt = ?,
            user_name = ?,
            user_email = ?,
            user_lastUpdate = NOW(),
            user_isLdap = 1 
            WHERE user_login = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($passdata['pass']);
        $Data->addParam($passdata['salt']);
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserLogin());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al actualizar la clave del usuario en la BBDD'));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}