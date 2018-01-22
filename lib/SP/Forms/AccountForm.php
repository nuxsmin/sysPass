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

use SP\Account\AccountRequest;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionFactory;
use SP\Http\Request;

/**
 * Class AccountForm
 *
 * @package SP\Account
 */
class AccountForm extends FormBase implements FormInterface
{
    /**
     * @var AccountRequest
     */
    protected $accountRequest;

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
            case ActionsInterface::ACCOUNT_EDIT_PASS:
                $this->analyzeRequestData();
                $this->checkPass();
                break;
            case ActionsInterface::ACCOUNT_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
            case ActionsInterface::ACCOUNT_CREATE:
            case ActionsInterface::ACCOUNT_COPY:
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
        $this->accountRequest = new AccountRequest();
        $this->accountRequest->id = $this->itemId;
        $this->accountRequest->name = Request::analyze('name');
        $this->accountRequest->clientId = Request::analyze('clientId', 0);
        $this->accountRequest->categoryId = Request::analyze('categoryId', 0);
        $this->accountRequest->login = Request::analyze('login');
        $this->accountRequest->url = Request::analyze('url');
        $this->accountRequest->notes = Request::analyze('notes');
        $this->accountRequest->userEditId = SessionFactory::getUserData()->getId();
        $this->accountRequest->otherUserEdit = Request::analyze('userEditEnabled', 0, false, 1);
        $this->accountRequest->otherUserGroupEdit = Request::analyze('groupEditEnabled', 0, false, 1);
        $this->accountRequest->pass = Request::analyzeEncrypted('pass');
        $this->accountRequest->isPrivate = Request::analyze('privateEnabled', 0, false, 1);
        $this->accountRequest->isPrivateGroup = Request::analyze('privateGroupEnabled', 0, false, 1);
        $this->accountRequest->passDateChange = Request::analyze('passworddatechange_unix', 0);
        $this->accountRequest->parentId = Request::analyze('parentAccountId', 0);

        // Arrays
        $accountOtherGroups = Request::analyze('otherGroups', 0);
        $accountOtherUsers = Request::analyze('otherUsers', 0);
        $accountTags = Request::analyze('tags', 0);

        if (is_array($accountOtherUsers)) {
            $this->accountRequest->users = $accountOtherUsers;
        }

        if (is_array($accountOtherGroups)) {
            $this->accountRequest->userGroups = $accountOtherGroups;
        }

        if (is_array($accountTags)) {
            $this->accountRequest->tags = $accountTags;
        }

        $accountMainGroupId = Request::analyze('mainGroupId', 0);

        // Cambiar el grupo principal si el usuario es Admin
        if ($accountMainGroupId !== 0
            && (SessionFactory::getUserData()->isIsAdminApp() || SessionFactory::getUserData()->isIsAdminAcc())
        ) {
            $this->accountRequest->userGroupId = $accountMainGroupId;
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass()
    {
        if ($this->accountRequest->parentId > 0) {
            return;
        }

        if (!$this->accountRequest->pass) {
            throw new ValidationException(__u('Es necesaria una clave'));
        }

        if (Request::analyzeEncrypted('passR') !== $this->accountRequest->pass) {
            throw new ValidationException(__u('Las claves no coinciden'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->accountRequest->name) {
            throw new ValidationException(__u('Es necesario un nombre de cuenta'));
        }

        if (!$this->accountRequest->clientId) {
            throw new ValidationException(__u('Es necesario un nombre de cliente'));
        }

        if (!$this->accountRequest->login) {
            throw new ValidationException(__u('Es necesario un usuario'));
        }

        if (!$this->accountRequest->categoryId) {
            throw new ValidationException(__u('Es necesario una categoría'));
        }
    }

    /**
     * @return AccountRequest
     */
    public function getItemData()
    {
        return $this->accountRequest;
    }
}