<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;

/**
 * Class AccountAcl
 *
 * @package SP\Account
 */
class AccountAcl
{
    /**
     * @var bool
     */
    public $userInGroups = false;
    /**
     * @var bool
     */
    public $userInUsers = false;
    /**
     * @var bool
     */
    public $resultView = false;
    /**
     * @var bool
     */
    public $resultEdit = false;
    /**
     * @var bool
     */
    public $modified = false;
    /**
     * @var bool
     */
    public $showView = false;
    /**
     * @var bool
     */
    public $showHistory = false;
    /**
     * @var bool
     */
    public $showDetails = false;
    /**
     * @var bool
     */
    public $showPass = false;
    /**
     * @var bool
     */
    public $showFiles = false;
    /**
     * @var bool
     */
    public $showViewPass = false;
    /**
     * @var bool
     */
    public $showSave = false;
    /**
     * @var bool
     */
    public $showEdit = false;
    /**
     * @var bool
     */
    public $showEditPass = false;
    /**
     * @var bool
     */
    public $showDelete = false;
    /**
     * @var bool
     */
    public $showRestore = false;
    /**
     * @var bool
     */
    public $showLink = false;
    /**
     * @var bool
     */
    public $showCopy = false;
    /**
     * @var bool
     */
    public $showPermission = false;
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
     * @return boolean
     */
    public function isShowDetails()
    {
        return $this->actionId === Acl::ACCOUNT_VIEW
            || $this->actionId === Acl::ACCOUNT_VIEW_HISTORY
            || $this->actionId === Acl::ACCOUNT_DELETE;
    }

    /**
     * @return boolean
     */
    public function isShowPass()
    {
        return ($this->actionId === Acl::ACCOUNT_CREATE
            || $this->actionId === Acl::ACCOUNT_COPY);
    }

    /**
     * @return boolean
     */
    public function isShowFiles()
    {
        return ($this->actionId === Acl::ACCOUNT_EDIT
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_VIEW_HISTORY)
            && $this->showFiles;
    }

    /**
     * @return boolean
     */
    public function isShowViewPass()
    {
        return ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_VIEW_PASS
                || $this->actionId === Acl::ACCOUNT_VIEW_HISTORY
                || $this->actionId === Acl::ACCOUNT_EDIT)
            && $this->showViewPass;
    }

    /**
     * @return boolean
     */
    public function isShowSave()
    {
        return $this->actionId === Acl::ACCOUNT_EDIT
            || $this->actionId === Acl::ACCOUNT_CREATE
            || $this->actionId === Acl::ACCOUNT_COPY;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW)
            && $this->showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowEditPass()
    {
        return ($this->actionId === Acl::ACCOUNT_EDIT
                || $this->actionId === Acl::ACCOUNT_VIEW)
            && $this->showEditPass;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_DELETE
                || $this->actionId === Acl::ACCOUNT_EDIT)
            && $this->showDelete;
    }

    /**
     * @return boolean
     */
    public function isShowRestore()
    {
        return $this->actionId === Acl::ACCOUNT_VIEW_HISTORY && $this->showRestore;
    }

    /**
     * @return boolean
     */
    public function isShowLink()
    {
        return $this->showLink;
    }

    /**
     * @return boolean
     */
    public function isShowHistory()
    {
        return ($this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_VIEW_HISTORY)
            && $this->showHistory;
    }

    /**
     * @return boolean
     */
    public function isShow()
    {
        return ($this->showView || $this->showEdit || $this->showViewPass || $this->showCopy || $this->showDelete);
    }

    /**
     * @return int
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * @param int $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = (int)$actionId;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime($time)
    {
        $this->time = (int)$time;
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param null $actionId
     * @return bool
     */
    public function checkAccountAccess($actionId)
    {
        if ($this->compiledAccountAccess === false) {
            return false;
        }

        switch ($actionId) {
            case ActionsInterface::ACCOUNT_VIEW:
            case ActionsInterface::ACCOUNT_SEARCH:
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
        return ($this->actionId === Acl::ACCOUNT_SEARCH
                || $this->actionId === Acl::ACCOUNT_VIEW
                || $this->actionId === Acl::ACCOUNT_EDIT)
            && $this->showCopy;
    }

    /**
     * @return boolean
     */
    public function isShowPermission()
    {
        return $this->showPermission;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId)
    {
        $this->accountId = (int)$accountId;
    }

    /**
     * @return bool
     */
    public function isHistory()
    {
        return $this->isHistory;
    }

    /**
     * @return bool
     */
    public function isCompiledShowAccess()
    {
        return $this->compiledShowAccess;
    }

    /**
     * @param bool $compiledShowAccess
     */
    public function setCompiledShowAccess($compiledShowAccess)
    {
        $this->compiledShowAccess = (bool)$compiledShowAccess;
    }

    /**
     * @return bool
     */
    public function isCompiledAccountAccess()
    {
        return $this->compiledAccountAccess;
    }

    /**
     * @param bool $compiledAccountAccess
     */
    public function setCompiledAccountAccess($compiledAccountAccess)
    {
        $this->compiledAccountAccess = (bool)$compiledAccountAccess;
    }
}