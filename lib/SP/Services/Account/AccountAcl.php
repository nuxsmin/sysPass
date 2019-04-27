<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use SP\Core\Acl\Acl;

/**
 * Class AccountAcl
 *
 * @package SP\Account
 */
final class AccountAcl
{
    /**
     * @var bool
     */
    private $userInGroups = false;
    /**
     * @var bool
     */
    private $userInUsers = false;
    /**
     * @var bool
     */
    private $resultView = false;
    /**
     * @var bool
     */
    private $resultEdit = false;
    /**
     * @var bool
     */
    private $modified = false;
    /**
     * @var bool
     */
    private $showView = false;
    /**
     * @var bool
     */
    private $showHistory = false;
    /**
     * @var bool
     */
    private $showDetails = false;
    /**
     * @var bool
     */
    private $showPass = false;
    /**
     * @var bool
     */
    private $showFiles = false;
    /**
     * @var bool
     */
    private $showViewPass = false;
    /**
     * @var bool
     */
    private $showSave = false;
    /**
     * @var bool
     */
    private $showEdit = false;
    /**
     * @var bool
     */
    private $showEditPass = false;
    /**
     * @var bool
     */
    private $showDelete = false;
    /**
     * @var bool
     */
    private $showRestore = false;
    /**
     * @var bool
     */
    private $showLink = false;
    /**
     * @var bool
     */
    private $showCopy = false;
    /**
     * @var bool
     */
    private $showPermission = false;
    /**
     * @var bool
     */
    private $compiledAccountAccess = false;
    /**
     * @var bool
     */
    private $compiledShowAccess = false;
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var int
     */
    private $actionId;
    /**
     * @var int
     */
    private $time = 0;
    /**
     * @var bool
     */
    private $isHistory;

    /**
     * AccountAcl constructor.
     *
     * @param int  $actionId
     * @param bool $isHistory
     */
    public function __construct($actionId, $isHistory = false)
    {
        $this->actionId = (int)$actionId;
        $this->isHistory = $isHistory;
    }

    /**
     * @return bool
     */
    public function isUserInGroups(): bool
    {
        return $this->userInGroups;
    }

    /**
     * @param bool $userInGroups
     *
     * @return AccountAcl
     */
    public function setUserInGroups(bool $userInGroups)
    {
        $this->userInGroups = $userInGroups;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUserInUsers(): bool
    {
        return $this->userInUsers;
    }

    /**
     * @param bool $userInUsers
     *
     * @return AccountAcl
     */
    public function setUserInUsers(bool $userInUsers)
    {
        $this->userInUsers = $userInUsers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isResultView(): bool
    {
        return $this->resultView;
    }

    /**
     * @param bool $resultView
     *
     * @return AccountAcl
     */
    public function setResultView(bool $resultView)
    {
        $this->resultView = $resultView;

        return $this;
    }

    /**
     * @return bool
     */
    public function isResultEdit(): bool
    {
        return $this->resultEdit;
    }

    /**
     * @param bool $resultEdit
     *
     * @return AccountAcl
     */
    public function setResultEdit(bool $resultEdit)
    {
        $this->resultEdit = $resultEdit;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowDetails(): bool
    {
        return $this->resultView && ($this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_HISTORY_VIEW
                || $this->actionId === Acl::ACCOUNT_DELETE);
    }

    /**
     * @param bool $showDetails
     *
     * @return AccountAcl
     */
    public function setShowDetails(bool $showDetails)
    {
        $this->showDetails = $showDetails;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowPass(): bool
    {
        return ($this->actionId === Acl::ACCOUNT_CREATE
            || $this->actionId === Acl::ACCOUNT_COPY);
    }

    /**
     * @param bool $showPass
     *
     * @return AccountAcl
     */
    public function setShowPass(bool $showPass)
    {
        $this->showPass = $showPass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowFiles(): bool
    {
        return $this->showFiles
            && ($this->actionId === Acl::ACCOUNT_EDIT
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_HISTORY_VIEW);
    }

    /**
     * @param bool $showFiles
     *
     * @return AccountAcl
     */
    public function setShowFiles(bool $showFiles)
    {
        $this->showFiles = $this->resultView && $showFiles;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowViewPass(): bool
    {
        return $this->showViewPass
            && ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_VIEW_PASS
                || $this->actionId === Acl::ACCOUNT_HISTORY_VIEW
                || $this->actionId === Acl::ACCOUNT_EDIT);
    }

    /**
     * @param bool $showViewPass
     *
     * @return AccountAcl
     */
    public function setShowViewPass(bool $showViewPass)
    {
        $this->showViewPass = $this->resultView && $showViewPass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowSave(): bool
    {
        return $this->actionId === Acl::ACCOUNT_EDIT
            || $this->actionId === Acl::ACCOUNT_CREATE
            || $this->actionId === Acl::ACCOUNT_COPY;
    }

    /**
     * @param bool $showSave
     *
     * @return AccountAcl
     */
    public function setShowSave(bool $showSave)
    {
        $this->showSave = $showSave;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowEdit(): bool
    {
        return $this->showEdit
            && ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW);
    }

    /**
     * @param bool $showEdit
     *
     * @return AccountAcl
     */
    public function setShowEdit(bool $showEdit)
    {
        $this->showEdit = $this->resultEdit && $showEdit && !$this->isHistory;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowEditPass(): bool
    {
        return $this->showEditPass
            && ($this->actionId === Acl::ACCOUNT_EDIT
                || $this->actionId === Acl::ACCOUNT_VIEW);
    }

    /**
     * @param bool $showEditPass
     *
     * @return AccountAcl
     */
    public function setShowEditPass(bool $showEditPass)
    {
        $this->showEditPass = $this->resultEdit && $showEditPass && !$this->isHistory;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowDelete(): bool
    {
        return $this->showDelete
            && ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_DELETE
                || $this->actionId === Acl::ACCOUNT_EDIT);
    }

    /**
     * @param bool $showDelete
     *
     * @return AccountAcl
     */
    public function setShowDelete(bool $showDelete)
    {
        $this->showDelete = $this->resultEdit && $showDelete;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowRestore(): bool
    {
        return $this->actionId === Acl::ACCOUNT_HISTORY_VIEW && $this->showRestore;
    }

    /**
     * @param bool $showRestore
     *
     * @return AccountAcl
     */
    public function setShowRestore(bool $showRestore)
    {
        $this->showRestore = $this->resultEdit && $showRestore;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowLink(): bool
    {
        return $this->showLink;
    }

    /**
     * @param bool $showLink
     *
     * @return AccountAcl
     */
    public function setShowLink(bool $showLink)
    {
        $this->showLink = $showLink;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowHistory(): bool
    {
        return $this->showHistory
            && ($this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_HISTORY_VIEW);
    }

    /**
     * @param bool $showHistory
     *
     * @return AccountAcl
     */
    public function setShowHistory(bool $showHistory)
    {
        $this->showHistory = $showHistory;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShow(): bool
    {
        return ($this->showView || $this->showEdit || $this->showViewPass || $this->showCopy || $this->showDelete);
    }

    /**
     * @return int
     */
    public function getActionId(): int
    {
        return $this->actionId;
    }

    /**
     * @param int $actionId
     *
     * @return AccountAcl
     */
    public function setActionId($actionId)
    {
        $this->actionId = (int)$actionId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     *
     * @return AccountAcl
     */
    public function setTime($time)
    {
        $this->time = (int)$time;

        return $this;
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param null $actionId
     *
     * @return bool
     */
    public function checkAccountAccess($actionId)
    {
        if ($this->compiledAccountAccess === false) {
            return false;
        }

        switch ($actionId) {
            case Acl::ACCOUNT_VIEW:
            case Acl::ACCOUNT_SEARCH:
            case Acl::ACCOUNT_VIEW_PASS:
            case Acl::ACCOUNT_HISTORY_VIEW:
            case Acl::ACCOUNT_COPY:
                return $this->resultView;
            case Acl::ACCOUNT_EDIT:
            case Acl::ACCOUNT_DELETE:
            case Acl::ACCOUNT_EDIT_PASS:
            case Acl::ACCOUNT_EDIT_RESTORE:
                return $this->resultEdit;
            default:
                return false;
        }
    }

    /**
     * @return boolean
     */
    public function isModified(): bool
    {
        return $this->modified;
    }

    /**
     * @param boolean $modified
     *
     * @return AccountAcl
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowView(): bool
    {
        return $this->showView;
    }

    /**
     * @param bool $showView
     *
     * @return AccountAcl
     */
    public function setShowView(bool $showView)
    {
        $this->showView = $this->resultView && $showView;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowCopy(): bool
    {
        return $this->showCopy
            && ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_EDIT);
    }

    /**
     * @param bool $showCopy
     *
     * @return AccountAcl
     */
    public function setShowCopy(bool $showCopy)
    {
        $this->showCopy = $this->resultView && $showCopy;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShowPermission(): bool
    {
        return $this->showPermission;
    }

    /**
     * @param bool $showPermission
     *
     * @return AccountAcl
     */
    public function setShowPermission(bool $showPermission)
    {
        $this->showPermission = $showPermission;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     *
     * @return AccountAcl
     */
    public function setAccountId($accountId)
    {
        $this->accountId = (int)$accountId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHistory(): bool
    {
        return $this->isHistory;
    }

    /**
     * @param bool $isHistory
     *
     * @return AccountAcl
     */
    public function setIsHistory(bool $isHistory)
    {
        $this->isHistory = $isHistory;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCompiledShowAccess(): bool
    {
        return $this->compiledShowAccess;
    }

    /**
     * @param bool $compiledShowAccess
     *
     * @return AccountAcl
     */
    public function setCompiledShowAccess($compiledShowAccess)
    {
        $this->compiledShowAccess = (bool)$compiledShowAccess;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCompiledAccountAccess(): bool
    {
        return $this->compiledAccountAccess;
    }

    /**
     * @param bool $compiledAccountAccess
     *
     * @return AccountAcl
     */
    public function setCompiledAccountAccess($compiledAccountAccess)
    {
        $this->compiledAccountAccess = (bool)$compiledAccountAccess;

        return $this;
    }

    public function reset()
    {
        foreach ($this as $property => $value) {
            if (strpos($property, 'show') === 0) {
                $this->{$property} = false;
            }
        }
    }
}