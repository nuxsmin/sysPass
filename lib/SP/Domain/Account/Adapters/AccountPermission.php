<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Account\Adapters;

use SP\Domain\Core\Acl\AclActionsInterface;

/**
 * Class AccountPermission
 */
class AccountPermission
{
    private const ACTIONS_VIEW = [
        AclActionsInterface::ACCOUNT_VIEW,
        AclActionsInterface::ACCOUNT_SEARCH,
        AclActionsInterface::ACCOUNT_VIEW_PASS,
        AclActionsInterface::ACCOUNT_HISTORY_VIEW,
        AclActionsInterface::ACCOUNT_COPY,
    ];

    private const ACTIONS_EDIT = [
        AclActionsInterface::ACCOUNT_EDIT,
        AclActionsInterface::ACCOUNT_DELETE,
        AclActionsInterface::ACCOUNT_EDIT_PASS,
        AclActionsInterface::ACCOUNT_EDIT_RESTORE,
    ];
    private bool $userInGroups          = false;
    private bool $userInUsers           = false;
    private bool $resultView            = false;
    private bool $resultEdit            = false;
    private bool $modified              = false;
    private bool $showView              = false;
    private bool $showHistory           = false;
    private bool $showDetails           = false;
    private bool $showPass              = false;
    private bool $showFiles             = false;
    private bool $showViewPass          = false;
    private bool $showSave              = false;
    private bool $showEdit              = false;
    private bool $showEditPass          = false;
    private bool $showDelete            = false;
    private bool $showRestore           = false;
    private bool $showLink              = false;
    private bool $showCopy              = false;
    private bool $showPermission        = false;
    private bool $compiledAccountAccess = false;
    private bool $compiledShowAccess    = false;
    private ?int $accountId             = null;
    private int  $actionId;
    private int  $time                  = 0;
    private bool $isHistory;

    public function __construct(int $actionId, bool $isHistory = false)
    {
        $this->actionId = $actionId;
        $this->isHistory = $isHistory;
    }

    public function isUserInGroups(): bool
    {
        return $this->userInGroups;
    }

    public function setUserInGroups(bool $userInGroups): AccountPermission
    {
        $this->userInGroups = $userInGroups;

        return $this;
    }

    public function isUserInUsers(): bool
    {
        return $this->userInUsers;
    }

    public function setUserInUsers(bool $userInUsers): AccountPermission
    {
        $this->userInUsers = $userInUsers;

        return $this;
    }

    public function isResultView(): bool
    {
        return $this->resultView;
    }

    public function setResultView(bool $resultView): AccountPermission
    {
        $this->resultView = $resultView;

        return $this;
    }

    public function isResultEdit(): bool
    {
        return $this->resultEdit;
    }

    public function setResultEdit(bool $resultEdit): AccountPermission
    {
        $this->resultEdit = $resultEdit;

        return $this;
    }

    public function isShowDetails(): bool
    {
        return $this->resultView
               && ($this->actionId === AclActionsInterface::ACCOUNT_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_HISTORY_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_DELETE);
    }

    /**
     * @param bool $showDetails
     *
     * @return AccountPermission
     */
    public function setShowDetails(bool $showDetails): AccountPermission
    {
        $this->showDetails = $showDetails;

        return $this;
    }

    public function isShowPass(): bool
    {
        return ($this->actionId === AclActionsInterface::ACCOUNT_CREATE
                || $this->actionId === AclActionsInterface::ACCOUNT_COPY);
    }

    /**
     * @param bool $showPass
     *
     * @return AccountPermission
     */
    public function setShowPass(bool $showPass): AccountPermission
    {
        $this->showPass = $showPass;

        return $this;
    }

    public function isShowFiles(): bool
    {
        return $this->showFiles
               && ($this->actionId === AclActionsInterface::ACCOUNT_EDIT
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_HISTORY_VIEW);
    }

    public function setShowFiles(bool $showFiles): AccountPermission
    {
        $this->showFiles = $this->resultView && $showFiles;

        return $this;
    }

    public function isShowViewPass(): bool
    {
        return $this->showViewPass
               && ($this->actionId === AclActionsInterface::ACCOUNT_SEARCH
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW_PASS
                   || $this->actionId === AclActionsInterface::ACCOUNT_HISTORY_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_EDIT);
    }

    public function setShowViewPass(bool $showViewPass): AccountPermission
    {
        $this->showViewPass = $this->resultView && $showViewPass;

        return $this;
    }

    public function isShowSave(): bool
    {
        return $this->actionId === AclActionsInterface::ACCOUNT_EDIT
               || $this->actionId === AclActionsInterface::ACCOUNT_CREATE
               || $this->actionId === AclActionsInterface::ACCOUNT_COPY;
    }

    /**
     * @param bool $showSave
     *
     * @return AccountPermission
     */
    public function setShowSave(bool $showSave): AccountPermission
    {
        $this->showSave = $showSave;

        return $this;
    }

    public function isShowEdit(): bool
    {
        return $this->showEdit
               && ($this->actionId === AclActionsInterface::ACCOUNT_SEARCH
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW);
    }

    public function setShowEdit(bool $showEdit): AccountPermission
    {
        $this->showEdit = $this->resultEdit && $showEdit && !$this->isHistory;

        return $this;
    }

    public function isShowEditPass(): bool
    {
        return $this->showEditPass
               && ($this->actionId === AclActionsInterface::ACCOUNT_EDIT
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW);
    }

    public function setShowEditPass(bool $showEditPass): AccountPermission
    {
        $this->showEditPass = $this->resultEdit && $showEditPass && !$this->isHistory;

        return $this;
    }

    public function isShowDelete(): bool
    {
        return $this->showDelete
               && ($this->actionId === AclActionsInterface::ACCOUNT_SEARCH
                   || $this->actionId === AclActionsInterface::ACCOUNT_DELETE
                   || $this->actionId === AclActionsInterface::ACCOUNT_EDIT);
    }

    public function setShowDelete(bool $showDelete): AccountPermission
    {
        $this->showDelete = $this->resultEdit && $showDelete;

        return $this;
    }

    public function isShowRestore(): bool
    {
        return $this->actionId === AclActionsInterface::ACCOUNT_HISTORY_VIEW && $this->showRestore;
    }

    public function setShowRestore(bool $showRestore): AccountPermission
    {
        $this->showRestore = $this->resultEdit && $showRestore;

        return $this;
    }

    public function isShowLink(): bool
    {
        return $this->showLink;
    }

    public function setShowLink(bool $showLink): AccountPermission
    {
        $this->showLink = $showLink;

        return $this;
    }

    public function isShowHistory(): bool
    {
        return $this->showHistory
               && ($this->actionId === AclActionsInterface::ACCOUNT_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_HISTORY_VIEW);
    }

    public function setShowHistory(bool $showHistory): AccountPermission
    {
        $this->showHistory = $showHistory;

        return $this;
    }

    public function isShow(): bool
    {
        return ($this->showView || $this->showEdit || $this->showViewPass || $this->showCopy || $this->showDelete);
    }

    public function getActionId(): int
    {
        return $this->actionId;
    }

    public function setActionId(int $actionId): AccountPermission
    {
        $this->actionId = $actionId;

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): AccountPermission
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     */
    public function checkAccountAccess(int $actionId): bool
    {
        if ($this->compiledAccountAccess === false) {
            return false;
        }

        if (in_array($actionId, self::ACTIONS_VIEW, true)) {
            return $this->resultView;
        }

        if (in_array($actionId, self::ACTIONS_EDIT, true)) {
            return $this->resultEdit;
        }

        return false;
    }

    public function isModified(): bool
    {
        return $this->modified;
    }

    public function setModified(bool $modified): AccountPermission
    {
        $this->modified = $modified;

        return $this;
    }

    public function isShowView(): bool
    {
        return $this->showView;
    }

    public function setShowView(bool $showView): AccountPermission
    {
        $this->showView = $this->resultView && $showView;

        return $this;
    }

    public function isShowCopy(): bool
    {
        return $this->showCopy
               && ($this->actionId === AclActionsInterface::ACCOUNT_SEARCH
                   || $this->actionId === AclActionsInterface::ACCOUNT_VIEW
                   || $this->actionId === AclActionsInterface::ACCOUNT_EDIT);
    }

    public function setShowCopy(bool $showCopy): AccountPermission
    {
        $this->showCopy = $this->resultView && $showCopy;

        return $this;
    }

    public function isShowPermission(): bool
    {
        return $this->showPermission;
    }

    public function setShowPermission(bool $showPermission): AccountPermission
    {
        $this->showPermission = $showPermission;

        return $this;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): AccountPermission
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function isCompiledShowAccess(): bool
    {
        return $this->compiledShowAccess;
    }

    public function setCompiledShowAccess(bool $compiledShowAccess): AccountPermission
    {
        $this->compiledShowAccess = $compiledShowAccess;

        return $this;
    }

    public function isCompiledAccountAccess(): bool
    {
        return $this->compiledAccountAccess;
    }

    public function setCompiledAccountAccess(bool $compiledAccountAccess): AccountPermission
    {
        $this->compiledAccountAccess = $compiledAccountAccess;

        return $this;
    }
}
