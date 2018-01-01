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
    protected $userData;
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
        $this->userData = new UserData();
        $this->userData->setUserId($this->itemId);
        $this->userData->setUserName(Request::analyze('name'));
        $this->userData->setUserLogin(Request::analyze('login'));
        $this->userData->setUserSsoLogin(Request::analyze('login_sso'));
        $this->userData->setUserEmail(Request::analyze('email'));
        $this->userData->setUserNotes(Request::analyze('notes'));
        $this->userData->setUserGroupId(Request::analyze('groupid', 0));
        $this->userData->setUserProfileId(Request::analyze('profileid', 0));
        $this->userData->setUserIsAdminApp(Request::analyze('adminapp', 0, false, 1));
        $this->userData->setUserIsAdminAcc(Request::analyze('adminacc', 0, false, 1));
        $this->userData->setUserIsDisabled(Request::analyze('disabled', 0, false, 1));
        $this->userData->setUserIsChangePass(Request::analyze('changepass', 0, false, 1));
        $this->userData->setUserPass(Request::analyzeEncrypted('pass'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->isLdap && !$this->userData->getUserName()) {
            throw new ValidationException(__u('Es necesario un nombre de usuario'));
        }

        if (!$this->isLdap && !$this->userData->getUserLogin()) {
            throw new ValidationException(__u('Es necesario un login'));
        }

        if (!$this->userData->getUserProfileId()) {
            throw new ValidationException(__u('Es necesario un perfil'));
        }

        if (!$this->userData->getUserGroupId()) {
            throw new ValidationException(__u('Es necesario un grupo'));
        }

        if (!$this->isLdap && !$this->userData->getUserEmail()) {
            throw new ValidationException(__u('Es necesario un email'));
        }

        if ($this->ConfigData->isDemoEnabled()
            && $this->userData->getUserLogin() === 'demo'
            && !SessionFactory::getUserData()->isUserIsAdminApp()) {
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

        if (!$userPassR || !$this->userData->getUserPass()) {
            throw new ValidationException(__u('La clave no puede estar en blanco'));
        }

        if ($this->userData->getUserPass() !== $userPassR) {
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
        return $this->userData;
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