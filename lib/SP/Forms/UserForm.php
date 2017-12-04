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

namespace SP\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionFactory;
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
     * @var int
     */
    protected $isLdap = 0;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::USER_CREATE:
                $this->analyzeRequestData();
                $this->checkCommon();
                $this->checkPass();
                break;
            case ActionsInterface::USER_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
            case ActionsInterface::USER_EDIT_PASS:
                $this->analyzeRequestData();
                $this->checkPass();
                break;
            case ActionsInterface::USER_DELETE:
                $this->checkDelete();
                break;
        }

        return true;
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
        $this->UserData->setUserSsoLogin(Request::analyze('login_sso'));
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
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->isLdap && !$this->UserData->getUserName()) {
            throw new ValidationException(__u('Es necesario un nombre de usuario'));
        }

        if (!$this->isLdap && !$this->UserData->getUserLogin()) {
            throw new ValidationException(__u('Es necesario un login'));
        }

        if (!$this->UserData->getUserProfileId()) {
            throw new ValidationException(__u('Es necesario un perfil'));
        }

        if (!$this->UserData->getUserGroupId()) {
            throw new ValidationException(__u('Es necesario un grupo'));
        }

        if (!$this->isLdap && !$this->UserData->getUserEmail()) {
            throw new ValidationException(__u('Es necesario un email'));
        }

        if ($this->ConfigData->isDemoEnabled() && !SessionFactory::getUserData()->isUserIsAdminApp() && $this->UserData->getUserLogin() === 'demo') {
            throw new ValidationException(__u('Ey, esto es una DEMO!!'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass()
    {
        $userPassR = Request::analyzeEncrypted('passR');

        if ($this->ConfigData->isDemoEnabled() && UserUtil::getUserLoginById($this->itemId) === 'demo') {
            throw new ValidationException(__u('Ey, esto es una DEMO!!'));
        }

        if (!$userPassR || !$this->UserData->getUserPass()) {
            throw new ValidationException(__u('La clave no puede estar en blanco'));
        }

        if ($this->UserData->getUserPass() !== $userPassR) {
            throw new ValidationException(__u('Las claves no coinciden'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkDelete()
    {
        if ($this->ConfigData->isDemoEnabled() && UserUtil::getUserLoginById($this->itemId) === 'demo') {
            throw new ValidationException(__u('Ey, esto es una DEMO!!'));
        }

        if ((!is_array($this->itemId) === SessionFactory::getUserData()->getUserId())
            || (is_array($this->itemId) && in_array(SessionFactory::getUserData()->getUserId(), $this->itemId))
        ) {
            throw new ValidationException(__u('No es posible eliminar, usuario en uso'));
        }
    }

    /**
     * @return UserData
     */
    public function getItemData()
    {
        return $this->UserData;
    }

    /**
     * @return int
     */
    public function getIsLdap()
    {
        return $this->isLdap;
    }

    /**
     * @param int $isLdap
     */
    public function setIsLdap($isLdap)
    {
        $this->isLdap = $isLdap;
    }
}