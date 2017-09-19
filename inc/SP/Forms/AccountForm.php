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

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Session;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\Http\Request;

/**
 * Class AccountForm
 *
 * @package SP\Account
 */
class AccountForm extends FormBase implements FormInterface
{
    /**
     * @var AccountData
     */
    protected $AccountData;

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
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                $this->analyzeRequestData();
                $this->checkPass();
                break;
            case ActionsInterface::ACTION_ACC_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
            case ActionsInterface::ACTION_ACC_NEW:
            case ActionsInterface::ACTION_ACC_COPY:
                $this->analyzeRequestData();
                $this->checkCommon();
                $this->checkPass();
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
        $this->AccountData = new AccountExtData();
        $this->AccountData->setAccountId($this->itemId);
        $this->AccountData->setAccountName(Request::analyze('name'));
        $this->AccountData->setAccountCustomerId(Request::analyze('customerId', 0));
        $this->AccountData->setAccountCategoryId(Request::analyze('categoryId', 0));
        $this->AccountData->setAccountLogin(Request::analyze('login'));
        $this->AccountData->setAccountUrl(Request::analyze('url'));
        $this->AccountData->setAccountNotes(Request::analyze('notes'));
        $this->AccountData->setAccountUserEditId(Session::getUserData()->getUserId());
        $this->AccountData->setAccountOtherUserEdit(Request::analyze('userEditEnabled', 0, false, 1));
        $this->AccountData->setAccountOtherGroupEdit(Request::analyze('groupEditEnabled', 0, false, 1));
        $this->AccountData->setAccountPass(Request::analyzeEncrypted('pass'));
        $this->AccountData->setAccountIsPrivate(Request::analyze('privateEnabled', 0, false, 1));
        $this->AccountData->setAccountIsPrivateGroup(Request::analyze('privateGroupEnabled', 0, false, 1));
        $this->AccountData->setAccountPassDateChange(Request::analyze('passworddatechange_unix', 0));
        $this->AccountData->setAccountParentId(Request::analyze('parentAccountId', 0));

        // Arrays
        $accountOtherGroups = Request::analyze('otherGroups', 0);
        $accountOtherUsers = Request::analyze('otherUsers', 0);
        $accountTags = Request::analyze('tags', 0);

        if (is_array($accountOtherUsers)) {
            $this->AccountData->setUsersId($accountOtherUsers);
        }

        if (is_array($accountOtherGroups)) {
            $this->AccountData->setUserGroupsId($accountOtherGroups);
        }

        if (is_array($accountTags)) {
            $this->AccountData->setTags($accountTags);
        }

        $accountMainGroupId = Request::analyze('mainGroupId', 0);

        // Cambiar el grupo principal si el usuario es Admin
        if ($accountMainGroupId !== 0
            && (Session::getUserData()->isUserIsAdminApp() || Session::getUserData()->isUserIsAdminAcc())
        ) {
            $this->AccountData->setAccountUserGroupId($accountMainGroupId);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass()
    {
        if ($this->AccountData->getAccountParentId() > 0) {
            return;
        } elseif (!$this->AccountData->getAccountPass()) {
            throw new ValidationException(__('Es necesaria una clave', false));
        } elseif (Request::analyzeEncrypted('passR') !== $this->AccountData->getAccountPass()) {
            throw new ValidationException(__('Las claves no coinciden', false));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->AccountData->getAccountName()) {
            throw new ValidationException(__('Es necesario un nombre de cuenta', false));
        } elseif (!$this->AccountData->getAccountCustomerId()) {
            throw new ValidationException(__('Es necesario un nombre de cliente', false));
        } elseif (!$this->AccountData->getAccountLogin()) {
            throw new ValidationException(__('Es necesario un usuario', false));
        } elseif (!$this->AccountData->getAccountCategoryId()) {
            throw new ValidationException(__('Es necesario una categoría', false));
        }
    }

    /**
     * @return AccountData
     */
    public function getItemData()
    {
        return $this->AccountData;
    }
}