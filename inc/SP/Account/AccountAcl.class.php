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

namespace SP\Account;

use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\DataModel\UserData;
use SP\Mgmt\Groups\GroupUsers;
use SP\Util\ArrayUtil;
use SP\Util\Checks;

/**
 * Class AccountAcl
 *
 * @package SP\Account
 */
class AccountAcl
{
    /**
     * @var AccountBase
     */
    protected $Account;
    /**
     * @var int
     */
    protected $accountId;
    /**
     * @var int
     */
    protected $action;
    /**
     * @var int
     */
    protected $time = 0;
    /**
     * @var bool
     */
    protected $userInGroups = false;
    /**
     * @var bool
     */
    protected $userInUsers = false;
    /**
     * @var bool
     */
    protected $resultView = false;
    /**
     * @var bool
     */
    protected $resultEdit = false;
    /**
     * @var bool
     */
    protected $modified = false;
    /**
     * @var bool
     */
    protected $showView = false;
    /**
     * @var bool
     */
    protected $showHistory = false;
    /**
     * @var bool
     */
    protected $showDetails = false;
    /**
     * @var bool
     */
    protected $showPass = false;
    /**
     * @var bool
     */
    protected $showFiles = false;
    /**
     * @var bool
     */
    protected $showViewPass = false;
    /**
     * @var bool
     */
    protected $showSave = false;
    /**
     * @var bool
     */
    protected $showEdit = false;
    /**
     * @var bool
     */
    protected $showEditPass = false;
    /**
     * @var bool
     */
    protected $showDelete = false;
    /**
     * @var bool
     */
    protected $showRestore = false;
    /**
     * @var bool
     */
    protected $showLink = false;
    /**
     * @var bool
     */
    protected $showCopy = false;
    /**
     * @var bool
     */
    protected $showPermission = false;
    /**
     * @var UserData
     */
    protected $UserData;
    /**
     * @var bool
     */
    protected $compiled = false;

    /**
     * AccountAcl constructor.
     *
     * @param AccountBase $Account
     * @param int $action
     */
    public function __construct(AccountBase $Account = null, $action)
    {
        $this->action = $action;
        $this->UserData = Session::getUserData();

        if (null !== $Account) {
            $this->Account = $Account;
            $this->accountId = $Account->getAccountData()->getAccountId();
        }
    }

    /**
     * Resetaear los datos de ACL en la sesión
     */
    public static function resetData()
    {
        unset($_SESSION['accountAcl']);
    }

    /**
     * @return boolean
     */
    public function isShowDetails()
    {
        return $this->action === Acl::ACTION_ACC_VIEW
            || $this->action === Acl::ACTION_ACC_VIEW_HISTORY
            || $this->action === Acl::ACTION_ACC_DELETE;
    }

    /**
     * @return boolean
     */
    public function isShowPass()
    {
        return ($this->action === Acl::ACTION_ACC_NEW
            || $this->action === Acl::ACTION_ACC_COPY);
    }

    /**
     * @return boolean
     */
    public function isShowFiles()
    {
        return Checks::fileIsEnabled() &&
            ($this->action === Acl::ACTION_ACC_EDIT
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY)
            && $this->showFiles;
    }

    /**
     * @return boolean
     */
    public function isShowViewPass()
    {
        return ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_PASS
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY
                || $this->action === Acl::ACTION_ACC_EDIT)
            && $this->showViewPass;
    }

    /**
     * @return boolean
     */
    public function isShowSave()
    {
        return $this->action === Acl::ACTION_ACC_EDIT
            || $this->action === Acl::ACTION_ACC_NEW
            || $this->action === Acl::ACTION_ACC_COPY;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW)
            && $this->showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowEditPass()
    {
        return ($this->action === Acl::ACTION_ACC_EDIT
                || $this->action === Acl::ACTION_ACC_VIEW)
            && $this->showEditPass;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_DELETE
                || $this->action === Acl::ACTION_ACC_EDIT)
            && $this->showDelete;
    }

    /**
     * @return boolean
     */
    public function isShowRestore()
    {
        return $this->action === Acl::ACTION_ACC_VIEW_HISTORY && $this->showRestore;
    }

    /**
     * @return boolean
     */
    public function isShowLink()
    {
        return Checks::publicLinksIsEnabled() && $this->showLink;
    }

    /**
     * @return boolean
     */
    public function isShowHistory()
    {
        return ($this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY)
            && $this->showHistory;
    }

    /**
     * Obtener la ACL de una cuenta
     *
     * @return $this
     */
    public function getAcl()
    {
        $sessionAcl = $this->getStoredAcl();

        if (null !== $sessionAcl
            && !($this->modified = (int)strtotime($this->Account->getAccountData()->getAccountDateEdit()) > $sessionAcl->getTime())
        ) {
            return $sessionAcl;
        }

        return $this->updateAcl();
    }

    /**
     * Devolver una ACL almacenada
     *
     * @return AccountAcl
     */
    public function getStoredAcl()
    {
        $sessionAcl = Session::getAccountAcl($this->accountId);

        if (null !== $sessionAcl && $sessionAcl->getAction() !== $this->action) {
            $sessionAcl->setAction($this->action);
        }

        return $sessionAcl;
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return (int)$this->action;
    }

    /**
     * @param int $action
     */
    public function setAction($action)
    {
        $this->action = (int)$action;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Actualizar la ACL
     *
     * @return $this
     */
    public function updateAcl()
    {
        $this->makeAcl();
        $this->saveAcl();

        return $this;
    }

    /**
     * Crear la ACL de una cuenta
     */
    protected function makeAcl()
    {
        $this->compileAccountAccess();

        // Mostrar historial
        $this->showHistory = Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_HISTORY);

        // Mostrar lista archivos
        $this->showFiles = Acl::checkUserAccess(Acl::ACTION_ACC_FILES);

        // Mostrar acción de ver clave
        $this->showViewPass = $this->checkAccountAccess(Acl::ACTION_ACC_VIEW_PASS)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS);

        // Mostrar acción de editar
        $this->showEdit = $this->checkAccountAccess(Acl::ACTION_ACC_EDIT)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)
            && !$this->Account->getAccountIsHistory();

        // Mostrar acción de editar clave
        $this->showEditPass = $this->checkAccountAccess(Acl::ACTION_ACC_EDIT_PASS)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT_PASS)
            && !$this->Account->getAccountIsHistory();

        // Mostrar acción de eliminar
        $this->showDelete = $this->checkAccountAccess(Acl::ACTION_ACC_DELETE)
            && Acl::checkUserAccess(Acl::ACTION_ACC_DELETE);

        // Mostrar acción de restaurar
        $this->showRestore = $this->checkAccountAccess(Acl::ACTION_ACC_EDIT)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT);

        // Mostrar acción de enlace público
        $this->showLink = Acl::checkUserAccess(Acl::ACTION_MGM_PUBLICLINKS_NEW);

        // Mostrar acción de ver cuenta
        $this->showView = $this->checkAccountAccess(Acl::ACTION_ACC_VIEW)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW);

        // Mostrar acción de copiar cuenta
        $this->showCopy = $this->checkAccountAccess(Acl::ACTION_ACC_COPY)
            && Acl::checkUserAccess(Acl::ACTION_ACC_COPY);
    }

    /**
     * Evaluar la ACL
     */
    protected function compileAccountAccess()
    {
        if ($this->UserData->isUserIsAdminApp()
            || $this->UserData->isUserIsAdminAcc()
        ) {
            $this->resultView = true;
            $this->resultEdit = true;

            return;
        }

        $AccountData = $this->Account->getAccountData();

        $this->userInGroups = $this->getIsUserInGroups();
        $this->userInUsers = in_array($this->UserData->getUserId(), $AccountData->getAccountUsersId());

        $this->resultView = ($this->UserData->getUserId() === $AccountData->getAccountUserId()
            || $this->UserData->getUserGroupId() === $AccountData->getAccountUserGroupId()
            || $this->userInUsers
            || $this->userInGroups);

        $this->resultEdit = ($this->UserData->getUserId() === $AccountData->getAccountUserId()
            || $this->UserData->getUserGroupId() === $AccountData->getAccountUserGroupId()
            || ($this->userInUsers && $AccountData->getAccountOtherUserEdit())
            || ($this->userInGroups && $AccountData->getAccountOtherGroupEdit()));
    }

    /**
     * Comprobar si el usuario o el grupo del usuario se encuentran los grupos asociados a la
     * cuenta.
     *
     * @return bool
     */
    protected function getIsUserInGroups()
    {
        $AccountData = $this->Account->getAccountData();

        // Comprobar si el usuario está vinculado desde el grupo principal de la cuenta
        if (GroupUsers::getItem()->checkUserInGroup($AccountData->getAccountUserGroupId(), $this->UserData->getUserId())) {
            return true;
        }

        // Grupos en los que se encuentra el usuario
        $groupsId = GroupUsers::getItem()->getGroupsForUser($this->UserData->getUserId());

        // Comprobar si el grupo del usuario está vinculado desde los grupos secundarios de la cuenta
        foreach ($AccountData->getUserGroupsId() as $groupId) {
            // Consultar el grupo principal del usuario
            if ($groupId === $this->UserData->getUserGroupId()
                // o... permitir los grupos que no sean el principal del usuario?
                || (Config::getConfig()->isAccountFullGroupAccess()
                    // Comprobar si el usuario está vinculado desde los grupos secundarios de la cuenta
                    && ArrayUtil::checkInObjectArray($groupsId, 'groupId', $groupId))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param null $actionId
     * @return bool
     */
    public function checkAccountAccess($actionId = null)
    {
        $action = null === $actionId ? $this->getAction() : $actionId;

        switch ($action) {
            case ActionsInterface::ACTION_ACC_VIEW:
            case ActionsInterface::ACTION_ACC_VIEW_PASS:
            case ActionsInterface::ACTION_ACC_VIEW_HISTORY:
            case ActionsInterface::ACTION_ACC_COPY:
                return $this->resultView;
            case ActionsInterface::ACTION_ACC_EDIT:
            case ActionsInterface::ACTION_ACC_DELETE:
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                return $this->resultEdit;
            default:
                return false;
        }
    }

    /**
     * Guardar la ACL
     */
    protected function saveAcl()
    {
        $this->time = time();

        // No guardar el objeto de la cuenta ni de usuario
        unset($this->Account, $this->UserData);

        Session::setAccountAcl($this);
    }

    /**
     * @return boolean
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * @param boolean $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return boolean
     */
    public function isShowView()
    {
        return $this->showView;
    }

    /**
     * @return boolean
     */
    public function isShowCopy()
    {
        return ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_EDIT)
            && $this->showCopy;
    }

    /**
     * @return boolean
     */
    public function isShowPermission()
    {
        $UserProfile = Session::getUserProfile();
        $UserData = Session::getUserData();

        return $UserData->isUserIsAdminAcc()
            || $UserData->isUserIsAdminApp()
            || $UserProfile->isAccPermission()
            || $UserProfile->isAccPrivateGroup()
            || $UserProfile->isAccPrivate();
    }

    /**
     * @param boolean $showPermission
     */
    public function setShowPermission($showPermission)
    {
        $this->showPermission = $showPermission;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }
}