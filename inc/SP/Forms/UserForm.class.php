<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Forms;

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Session;
use SP\DataModel\UserData;
use SP\Http\Request;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;

/**
 * Class UserForm
 *
 * @package SP\Forms
 */
class UserForm extends FormBase implements FormInterface
{
    /**
     * @var UserData
     */
    protected $UserData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACTION_USR_USERS_NEW:
                $this->checkCommon();
                $this->checkPass();
                break;
            case ActionsInterface::ACTION_USR_USERS_EDIT:
                $this->checkCommon();
                break;
            case ActionsInterface::ACTION_USR_USERS_EDITPASS:
                $this->checkPass();
                break;
            case ActionsInterface::ACTION_USR_USERS_DELETE:
                $this->checkDelete();
                break;
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        $isLdap = Request::analyze('isLdap', 0);

        if (!$isLdap && !$this->UserData->getUserName()) {
            throw new ValidationException(_('Es necesario un nombre de usuario'));
        } elseif (!$isLdap && !$this->UserData->getUserLogin()) {
            throw new ValidationException(_('Es necesario un login'));
        } elseif (!$this->UserData->getUserProfileId()) {
            throw new ValidationException(_('Es necesario un perfil'));
        } elseif (!$this->UserData->getUserGroupId()) {
            throw new ValidationException(_('Es necesario un grupo'));
        } elseif (!$isLdap && !$this->UserData->getUserEmail()) {
            throw new ValidationException(_('Es necesario un email'));
        } elseif (Checks::demoIsEnabled() && !Session::getUserData()->isUserIsAdminApp() && $this->UserData->getUserLogin() === 'demo') {
            throw new ValidationException(_('Ey, esto es una DEMO!!'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass()
    {
        $userPassR = Request::analyzeEncrypted('passR');

        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($this->UserData->getUserId()) === 'demo') {
            throw new ValidationException(_('Ey, esto es una DEMO!!'));
        } elseif (!$userPassR || !$this->UserData->getUserPass()) {
            throw new ValidationException(_('La clave no puede estar en blanco'));
        } elseif ($this->UserData->getUserPass() !== $userPassR) {
            throw new ValidationException(_('Las claves no coinciden'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkDelete()
    {
        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($this->UserData->getUserId()) === 'demo') {
            throw new ValidationException(_('Ey, esto es una DEMO!!'));
        } elseif ($this->UserData->getUserId() === Session::getUserData()->getUserId()) {
            throw new ValidationException(_('No es posible eliminar, usuario en uso'));
        }
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->UserData = new UserData();
        $this->UserData->setUserId($this->itemId);
        $this->UserData->setUserName(Request::analyze('name'));
        $this->UserData->setUserLogin(Request::analyze('login'));
        $this->UserData->setUserEmail(Request::analyze('email'));
        $this->UserData->setUserNotes(Request::analyze('notes'));
        $this->UserData->setUserGroupId(Request::analyze('groupid', 0));
        $this->UserData->setUserProfileId(Request::analyze('profileid', 0));
        $this->UserData->setUserIsAdminApp(Request::analyze('adminapp', 0, false, 1));
        $this->UserData->setUserIsAdminAcc(Request::analyze('adminacc', 0, false, 1));
        $this->UserData->setUserIsDisabled(Request::analyze('disabled', 0, false, 1));
        $this->UserData->setUserIsChangePass(Request::analyze('changepass', 0, false, 1));
        $this->UserData->setUserPass(Request::analyzeEncrypted('pass'));
    }

    /**
     * @return UserData
     */
    public function getItemData()
    {
        return $this->UserData;
    }
}