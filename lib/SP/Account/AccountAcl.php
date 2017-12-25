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

use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\AccountExtData;
use SP\DataModel\UserData;
use SP\Services\UserGroup\UserToGroupService;
use SP\Util\ArrayUtil;

/**
 * Class AccountAcl
 *
 * @package SP\Account
 */
class AccountAcl
{
    /**
     * @var AccountExtData
     */
    protected $accountData;
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
    protected $userData;
    /**
     * @var bool
     */
    protected $compiled = false;
    /**
     * @var ConfigData;
     */
    protected $configData;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var \SP\Core\Acl\Acl
     */
    protected $acl;
    /**
     * @var bool
     */
    protected $isHistory;

    use InjectableTrait;

    /**
     * AccountAcl constructor.
     *
     * @param int            $action
     * @param AccountExtData $accountData
     * @param bool           $isHistory
     */
    public function __construct($action, AccountExtData $accountData = null, $isHistory = false)
    {
        $this->injectDependencies();

        $this->action = $action;
        $this->isHistory = $isHistory;
        $this->userData = $this->session->getUserData();

        if (null !== $accountData) {
            $this->accountData = $accountData;
            $this->accountId = $accountData->getAccountId();
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
     * @param ConfigData       $configData
     * @param Session          $session
     * @param \SP\Core\Acl\Acl $acl
     */
    public function inject(ConfigData $configData, Session $session, Acl $acl)
    {
        $this->configData = $configData;
        $this->session = $session;
        $this->acl = $acl;
    }

    /**
     * @return boolean
     */
    public function isShowDetails()
    {
        return $this->action === Acl::ACCOUNT_VIEW
            || $this->action === Acl::ACCOUNT_VIEW_HISTORY
            || $this->action === Acl::ACCOUNT_DELETE;
    }

    /**
     * @return boolean
     */
    public function isShowPass()
    {
        return ($this->action === Acl::ACCOUNT_CREATE
            || $this->action === Acl::ACCOUNT_COPY);
    }

    /**
     * @return boolean
     */
    public function isShowFiles()
    {
        return $this->configData->isFilesEnabled() &&
            ($this->action === Acl::ACCOUNT_EDIT
                || $this->action === Acl::ACCOUNT_VIEW
                || $this->action === Acl::ACCOUNT_VIEW_HISTORY)
            && $this->showFiles;
    }

    /**
     * @return boolean
     */
    public function isShowViewPass()
    {
        return ($this->action === Acl::ACCOUNT_SEARCH
                || $this->action === Acl::ACCOUNT_VIEW
                || $this->action === Acl::ACCOUNT_VIEW_PASS
                || $this->action === Acl::ACCOUNT_VIEW_HISTORY
                || $this->action === Acl::ACCOUNT_EDIT)
            && $this->showViewPass;
    }

    /**
     * @return boolean
     */
    public function isShowSave()
    {
        return $this->action === Acl::ACCOUNT_EDIT
            || $this->action === Acl::ACCOUNT_CREATE
            || $this->action === Acl::ACCOUNT_COPY;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return ($this->action === Acl::ACCOUNT_SEARCH
                || $this->action === Acl::ACCOUNT_VIEW)
            && $this->showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowEditPass()
    {
        return ($this->action === Acl::ACCOUNT_EDIT
                || $this->action === Acl::ACCOUNT_VIEW)
            && $this->showEditPass;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return ($this->action === Acl::ACCOUNT_SEARCH
                || $this->action === Acl::ACCOUNT_DELETE
                || $this->action === Acl::ACCOUNT_EDIT)
            && $this->showDelete;
    }

    /**
     * @return boolean
     */
    public function isShowRestore()
    {
        return $this->action === Acl::ACCOUNT_VIEW_HISTORY && $this->showRestore;
    }

    /**
     * @return boolean
     */
    public function isShowLink()
    {
        return $this->configData->isPublinksEnabled() && $this->showLink;
    }

    /**
     * @return boolean
     */
    public function isShowHistory()
    {
        return ($this->action === Acl::ACCOUNT_VIEW
                || $this->action === Acl::ACCOUNT_VIEW_HISTORY)
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
            && !($this->modified = (int)strtotime($this->accountData->getAccountDateEdit()) > $sessionAcl->getTime())
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
        $sessionAcl = $this->session->getAccountAcl($this->accountId);

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

        return $this;
    }

    /**
     * Crear la ACL de una cuenta
     */
    protected function makeAcl()
    {
        $this->compileAccountAccess();

        // Mostrar historial
        $this->showHistory = $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW_HISTORY);

        // Mostrar lista archivos
        $this->showFiles = $this->acl->checkUserAccess(Acl::ACCOUNT_FILE);

        // Mostrar acción de ver clave
        $this->showViewPass = $this->checkAccountAccess(Acl::ACCOUNT_VIEW_PASS)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW_PASS);

        // Mostrar acción de editar
        $this->showEdit = $this->checkAccountAccess(Acl::ACCOUNT_EDIT)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT)
            && !$this->isHistory;

        // Mostrar acción de editar clave
        $this->showEditPass = $this->checkAccountAccess(Acl::ACCOUNT_EDIT_PASS)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT_PASS)
            && !$this->isHistory;

        // Mostrar acción de eliminar
        $this->showDelete = $this->checkAccountAccess(Acl::ACCOUNT_DELETE)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_DELETE);

        // Mostrar acción de restaurar
        $this->showRestore = $this->checkAccountAccess(Acl::ACCOUNT_EDIT)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT);

        // Mostrar acción de enlace público
        $this->showLink = $this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE);

        // Mostrar acción de ver cuenta
        $this->showView = $this->checkAccountAccess(Acl::ACCOUNT_VIEW)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW);

        // Mostrar acción de copiar cuenta
        $this->showCopy = $this->checkAccountAccess(Acl::ACCOUNT_COPY)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_COPY);
    }

    /**
     * Evaluar la ACL
     */
    protected function compileAccountAccess()
    {
        if ($this->userData->isUserIsAdminApp()
            || $this->userData->isUserIsAdminAcc()
        ) {
            $this->resultView = true;
            $this->resultEdit = true;

            return;
        }

        $this->userInGroups = $this->getIsUserInGroups();
        $this->userInUsers = in_array($this->userData->getUserId(), $this->accountData->getAccountUsersId());

        $this->resultView = ($this->userData->getUserId() === $this->accountData->getAccountUserId()
            || $this->userData->getUserGroupId() === $this->accountData->getAccountUserGroupId()
            || $this->userInUsers
            || $this->userInGroups);

        $this->resultEdit = ($this->userData->getUserId() === $this->accountData->getAccountUserId()
            || $this->userData->getUserGroupId() === $this->accountData->getAccountUserGroupId()
            || ($this->userInUsers && $this->accountData->getAccountOtherUserEdit())
            || ($this->userInGroups && $this->accountData->getAccountOtherGroupEdit()));
    }

    /**
     * Comprobar si el usuario o el grupo del usuario se encuentran los grupos asociados a la
     * cuenta.
     *
     * @return bool
     */
    protected function getIsUserInGroups()
    {
        // Comprobar si el usuario está vinculado desde el grupo principal de la cuenta
        if (UserToGroupService::checkUserInGroup($this->accountData->getAccountUserGroupId(), $this->userData->getUserId())) {
            return true;
        }

        // Grupos en los que se encuentra el usuario
        $groupsId = UserToGroupService::getGroupsForUser($this->userData->getUserId());

        // Comprobar si el grupo del usuario está vinculado desde los grupos secundarios de la cuenta
        foreach ($this->accountData->getUserGroupsId() as $groupId) {
            // Consultar el grupo principal del usuario
            if ($groupId === $this->userData->getUserGroupId()
                // o... permitir los grupos que no sean el principal del usuario?
                || ($this->configData->isAccountFullGroupAccess()
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
            case ActionsInterface::ACCOUNT_VIEW:
            case ActionsInterface::ACCOUNT_VIEW_PASS:
            case ActionsInterface::ACCOUNT_VIEW_HISTORY:
            case ActionsInterface::ACCOUNT_COPY:
                return $this->resultView;
            case ActionsInterface::ACCOUNT_EDIT:
            case ActionsInterface::ACCOUNT_DELETE:
            case ActionsInterface::ACCOUNT_EDIT_PASS:
                return $this->resultEdit;
            default:
                return false;
        }
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
        return ($this->action === Acl::ACCOUNT_SEARCH
                || $this->action === Acl::ACCOUNT_VIEW
                || $this->action === Acl::ACCOUNT_EDIT)
            && $this->showCopy;
    }

    /**
     * @return boolean
     */
    public function isShowPermission()
    {
        $userProfile = $this->session->getUserProfile();
        $userData = $this->session->getUserData();

        return $userData->isUserIsAdminAcc()
            || $userData->isUserIsAdminApp()
            || $userProfile->isAccPermission()
            || $userProfile->isAccPrivateGroup()
            || $userProfile->isAccPrivate();
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

    /**
     * unserialize() checks for the presence of a function with the magic name __wakeup.
     * If present, this function can reconstruct any resources that the object may have.
     * The intended use of __wakeup is to reestablish any database connections that may have been lost during
     * serialization and perform other reinitialization tasks.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __wakeup()
    {
        $this->injectDependencies();
    }

    /**
     * serialize() checks if your class has a function with the magic name __sleep.
     * If so, that function is executed prior to any serialization.
     * It can clean up the object and is supposed to return an array with the names of all variables of that object that should be serialized.
     * If the method doesn't return anything then NULL is serialized and E_NOTICE is issued.
     * The intended use of __sleep is to commit pending data or perform similar cleanup tasks.
     * Also, the function is useful if you have very large objects which do not need to be saved completely.
     *
     * @return string[]
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __sleep()
    {
        $this->time = time();

        unset($this->accountData, $this->userData, $this->session, $this->dic, $this->acl, $this->configData);

        $props = [];

        foreach ((array)$this as $prop => $value) {
            if ($prop !== "\0*\0configData"
                && $prop !== "\0*\0dic"
                && $prop !== "\0*\0accountData"
                && $prop !== "\0*\0UserData"
                && $prop !== "\0*\0acl"
                && $prop !== "\0*\0session") {
                $props[] = $prop;
            }
        }

        return $props;
    }
}