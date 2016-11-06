<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl;
use SP\Log\Log;
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
    protected $action;
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
     * @return boolean
     */
    public function isShowDetails()
    {
        return $this->showDetails;
    }

    /**
     * @return boolean
     */
    public function isShowPass()
    {
        return $this->showPass;
    }

    /**
     * @return boolean
     */
    public function isShowFiles()
    {
        return $this->showFiles;
    }

    /**
     * @return boolean
     */
    public function isShowViewPass()
    {
        return $this->showViewPass;
    }

    /**
     * @return boolean
     */
    public function isShowSave()
    {
        return $this->showSave;
    }

    /**
     * @return boolean
     */
    public function isShowEdit()
    {
        return $this->showEdit;
    }

    /**
     * @return boolean
     */
    public function isShowEditPass()
    {
        return $this->showEditPass;
    }

    /**
     * @return boolean
     */
    public function isShowDelete()
    {
        return $this->showDelete;
    }

    /**
     * @return boolean
     */
    public function isShowRestore()
    {
        return $this->showRestore;
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
        return $this->showHistory;
    }

    /**
     * Obtener la ACL de una cuenta
     *
     * @param AccountBase $Account
     * @param int         $action
     * @return $this
     */
    public function getAcl (AccountBase $Account, $action)
    {
        $this->Account = $Account;
        $this->action = $action;

        $this->makeAcl();

        return $this;
    }

    /**
     * Crear la ACL de una cuenta
     */
    protected function makeAcl()
    {
        $aclData = $this->Account->getAccountDataForACL();

        // Mostrar historial
        $this->showHistory =
            ($this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_HISTORY)
            && ($this->modified || $this->action === Acl::ACTION_ACC_VIEW_HISTORY);

        // Mostrar detalles
        $this->showDetails =
            $this->action === Acl::ACTION_ACC_VIEW
            || $this->action === Acl::ACTION_ACC_VIEW_HISTORY
            || $this->action === Acl::ACTION_ACC_DELETE;

        // Mostrar campo de clave
        $this->showPass = $this->action === Acl::ACTION_ACC_NEW || $this->action === Acl::ACTION_ACC_COPY;

        // Mostrar lista archivos
        $this->showFiles =
            ($this->action === Acl::ACTION_ACC_EDIT
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY)
            && Checks::fileIsEnabled()
            && Acl::checkUserAccess(Acl::ACTION_ACC_FILES);

        // Mostrar acción de ver clave
        $this->showViewPass =
            ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_VIEW_HISTORY)
            && Acl::checkAccountAccess(Acl::ACTION_ACC_VIEW_PASS, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS);

        // Mostrar acción de guardar
        $this->showSave = $this->action === Acl::ACTION_ACC_EDIT || $this->action === Acl::ACTION_ACC_NEW || $this->action === Acl::ACTION_ACC_COPY;

        // Mostrar acción de editar
        $this->showEdit =
            ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW)
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)
            && !$this->Account->getAccountIsHistory();

        // Mostrar acción de editar clave
        $this->showEditPass =
            ($this->action === Acl::ACTION_ACC_EDIT
                || $this->action === Acl::ACTION_ACC_VIEW)
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT_PASS, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT_PASS)
            && !$this->Account->getAccountIsHistory();

        // Mostrar acción de eliminar
        $this->showDelete =
            ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_DELETE
                || $this->action === Acl::ACTION_ACC_EDIT)
            && Acl::checkAccountAccess(Acl::ACTION_ACC_DELETE, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_DELETE);

        // Mostrar acción de restaurar
        $this->showRestore = $this->action === Acl::ACTION_ACC_VIEW_HISTORY
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $this->Account->getAccountDataForACL($this->Account->getAccountParentId()))
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT);

        // Mostrar acción de enlace público
        $this->showLink = Checks::publicLinksIsEnabled() && Acl::checkUserAccess(Acl::ACTION_MGM_PUBLICLINKS);

        // Mostrar acción de ver cuenta
        $this->showView = Acl::checkAccountAccess(Acl::ACTION_ACC_VIEW, $aclData) && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW);

        // Mostrar acción de copiar cuenta
        $this->showCopy =
            ($this->action === Acl::ACTION_ACC_SEARCH
                || $this->action === Acl::ACTION_ACC_VIEW
                || $this->action === Acl::ACTION_ACC_EDIT)
            && Acl::checkAccountAccess(Acl::ACTION_ACC_COPY, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_COPY);
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
        return $this->showCopy;
    }
}