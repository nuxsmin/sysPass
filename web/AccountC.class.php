<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Controller;

use SP\Account;
use SP\AccountHistory;
use SP\Acl;
use SP\Common;
use SP\Groups;
use SP\Session;
use SP\SPException;
use SP\Users;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las vistas de una cuenta
 *
 * @package Controller
 */
class AccountC extends Controller implements ActionsInterface
{
    /**
     * @var int con la acción a realizar
     */
    protected $_action;
    /**
     * @var Account|AccountHistory instancia para el manejo de datos de una cuenta
     */
    private $_account;
    /**
     * @var bool indica si se han obtenido datos de la cuenta
     */
    private $_gotData = false;
    /**
     * @var int con el id de la cuenta
     */
    private $_id;

    /**
     * Constructor
     *
     * @param \SP\Template $template              instancia del motor de plantillas
     * @param              $lastAction            int con la última acción realizada
     * @param null         $accountId             int con el id de la cuenta
     */
    public function __construct(\SP\Template $template = null, $lastAction, $accountId = null)
    {
        parent::__construct($template);

        $this->setId($accountId);

        $this->view->assign('changesHash', '');
        $this->view->assign('chkUserEdit', '');
        $this->view->assign('chkGroupEdit', '');
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('sk', Common::getSessionKey(true));
    }

    /**
     * @return Account|AccountHistory
     */
    private function getAccount()
    {
        return $this->_account;
    }

    /**
     * @param Account|AccountHistory $account
     */
    private function setAccount($account)
    {
        $this->_account = $account;
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->_gotData;
    }

    /**
     * @param boolean $gotData
     */
    private function setGotData($gotData)
    {
        $this->_gotData = $gotData;
    }

    /**
     * @return int
     */
    private function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    private function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Obtener los datos para mostrar el interface para nueva cuenta
     */
    public function getNewAccount()
    {
        $this->setAction(self::ACTION_ACC_NEW);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Nueva Cuenta'),
                'icon' => 'add'
            )
        );
        $this->view->assign('showform', true);
        $this->view->assign('nextaction', Acl::ACTION_ACC_SEARCH);

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @return bool
     */
    protected function checkAccess()
    {
        if (!Acl::checkUserAccess($this->getAction())) {
            $this->showError(self::ERR_PAGE_NO_PERMISSION);
            return false;
        } elseif (!Users::checkUserUpdateMPass()) {
            $this->showError(self::ERR_UPDATE_MPASS);
            return false;
        } elseif ($this->_id > 0 && !Acl::checkAccountAccess($this->_action, $this->_account->getAccountDataForACL())) {
            $this->showError(self::ERR_ACCOUNT_NO_PERMISSION);
            return false;
        }

        return true;
    }

    /**
     * Establecer variables comunes del formulario para todos los interfaces
     */
    private function setCommonData()
    {
        if ($this->isGotData()) {
//            $this->view->assign('accountParentId', $this->getAccount()->getAccountParentId());
            $this->view->assign('accountIsHistory', $this->getAccount()->getAccountIsHistory());
            $this->view->assign('accountOtherUsers', $this->getAccount()->getAccountUsersId());
            $this->view->assign('accountOtherUsersName', \SP\Users::getUsersNameForAccount($this->getId()));
            $this->view->assign('accountOtherGroups', $this->getAccount()->getAccountUserGroupsId());
            $this->view->assign('accountOtherGroupsName', \SP\Groups::getGroupsNameForAccount($this->getId()));
            $this->view->assign('changesHash', $this->getAccount()->calcChangesHash());
            $this->view->assign('chkUserEdit', ($this->view->accountData->account_otherUserEdit) ? 'checked' : '');
            $this->view->assign('chkGroupEdit', ($this->view->accountData->account_otherGroupEdit) ? 'checked' : '');
            $this->view->assign('historyData', \SP\AccountHistory::getAccountList($this->getAccount()->getAccountParentId()));
            $this->view->assign('isModified', ($this->view->accountData->account_dateEdit && $this->view->accountData->account_dateEdit <> '0000-00-00 00:00:00'));
            $this->view->assign('maxFileSize', round(\SP\Config::getValue('files_allowed_size') / 1024, 1));
            $this->view->assign('filesAllowedExts', \SP\Config::getValue('files_allowed_exts'));
            $this->view->assign('filesDelete', ($this->_action == Acl::ACTION_ACC_EDIT) ? 1 : 0);
        }

        $this->view->assign('accountParentId', Session::getLastAcountId());
        $this->view->assign('categories', \SP\DB::getValuesForSelect('categories', 'category_id', 'category_name'));
        $this->view->assign('customers', \SP\DB::getValuesForSelect('customers', 'customer_id', 'customer_name'));
        $this->view->assign('otherUsers', \SP\DB::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('otherGroups', \SP\DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
    }

    /**
     * Establecer variables para los interfaces que muestran datos
     */
    private function setShowData()
    {
        $this->view->assign('showHistory', (($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_HISTORY)
            && ($this->view->isModified || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)));
        $this->view->assign('showDetails', ($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY || $this->_action == Acl::ACTION_ACC_DELETE));
        $this->view->assign('showPass', ($this->_action == Acl::ACTION_ACC_NEW || $this->_action == Acl::ACTION_ACC_COPY));
        $this->view->assign('showFiles', (($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && (\SP\Util::fileIsEnabled() && Acl::checkUserAccess(Acl::ACTION_ACC_FILES))));
        $this->view->assign('showViewPass', (($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && (Acl::checkAccountAccess(Acl::ACTION_ACC_VIEW_PASS, $this->_account->getAccountDataForACL())
                && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS))));
        $this->view->assign('showSave', ($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_NEW || $this->_action == Acl::ACTION_ACC_COPY));
        $this->view->assign('showEdit', ($this->_action == Acl::ACTION_ACC_VIEW
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $this->_account->getAccountDataForACL())
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)
            && !$this->_account->getAccountIsHistory()));
        $this->view->assign('showEditPass', ($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_VIEW
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT_PASS, $this->_account->getAccountDataForACL())
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT_PASS)
            && !$this->_account->getAccountIsHistory()));
        $this->view->assign('showDelete', ($this->_action == Acl::ACTION_ACC_DELETE || $this->_action == Acl::ACTION_ACC_EDIT
            && Acl::checkAccountAccess(Acl::ACTION_ACC_DELETE, $this->_account->getAccountDataForACL())
            && Acl::checkUserAccess(Acl::ACTION_ACC_DELETE)));
        $this->view->assign('showRestore', ($this->_action == Acl::ACTION_ACC_VIEW_HISTORY
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $this->_account->getAccountDataForACL($this->_account->getAccountParentId()))
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)));
    }

    /**
     * Obtener los datos para mostrar el interface para copiar cuenta
     */
    public function getCopyAccount()
    {
        $this->setAction(self::ACTION_ACC_COPY);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountData() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Copiar Cuenta'),
                'icon' => 'content_copy'
            )
        );
        $this->view->assign('showform', true);
        $this->view->assign('nextaction', self::ACTION_ACC_COPY);

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Establecer las variables que contienen la información de la cuenta.
     *
     * @return bool
     */
    private function setAccountData()
    {
        try {
            $this->setAccount(new Account());
            $this->_account->setAccountId($this->getId());
            $this->_account->setAccountParentId($this->getId());

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->getAccount()->getAccountData());
            $this->view->assign('gotData', true);

            $this->setAccountDetails();
            $this->setGotData(true);

            Session::setLastAcountId($this->getId());
        } catch (SPException $e) {
            return false;
        }
        return true;
    }

    /**
     * Establecer variables que contienen la información detallada de la cuenta.
     */
    private function setAccountDetails()
    {
        $this->_account->setAccountUsersId(Users::getUsersForAccount($this->getId()));
        $this->_account->setAccountUserGroupsId(Groups::getGroupsForAccount($this->getId()));
    }

    /**
     * Obtener los datos para mostrar el interface para editar cuenta
     */
    public function getEditAccount()
    {
        $this->setAction(self::ACTION_ACC_EDIT);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountData() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Editar Cuenta'),
                'icon' => 'mode_edit'
            )
        );
        $this->view->assign('showform', true);
        $this->view->assign('nextaction', self::ACTION_ACC_VIEW);

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Obtener los datos para mostrar el interface de eliminar cuenta
     */
    public function getDeleteAccount()
    {
        $this->setAction(self::ACTION_ACC_DELETE);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountData() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Eliminar Cuenta'),
                'icon' => 'delete'
            )
        );
        $this->view->assign('showform', false);

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta
     */
    public function getViewAccount()
    {
        $this->setAction(self::ACTION_ACC_VIEW);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountData() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Detalles de Cuenta'),
                'icon' => 'visibility'
            )
        );
        $this->view->assign('showform', false);

        \SP\Session::setAccountParentId($this->getId());
        $this->_account->incrementViewCounter();

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Obtener los datos para mostrar el interface para ver cuenta en fecha concreta
     */
    public function getViewHistoryAccount()
    {
        $this->setAction(self::ACTION_ACC_VIEW_HISTORY);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountDataHistory() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Detalles de Cuenta'),
                'icon' => 'access_time'
            )
        );
        $this->view->assign('showform', false);

        $this->_account->setAccountIsHistory(1);

        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Establecer las variables que contienen la información de la cuenta en una fecha concreta.
     *
     * @return bool
     */
    private function setAccountDataHistory()
    {
        try {
            $this->setAccount(new AccountHistory());
            $this->_account->setAccountId($this->getId());
            $this->_account->setAccountParentId(\SP\Session::getAccountParentId());

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->getAccount()->getAccountData());
            $this->view->assign('gotData', true);

            $this->setAccountDetails();
            $this->setGotData(true);

            Session::setLastAcountId(\SP\Session::getAccountParentId());
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     */
    public function getEditPassAccount()
    {
        $this->setAction(self::ACTION_ACC_EDIT_PASS);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        $isOk = ($this->setAccountData() && $this->checkAccess());

        if (!$isOk) {
            return;
        }

        $this->view->addTemplate('editpass');
        $this->view->assign('title',
            array(
                'class' => 'titleBlue',
                'name' => _('Modificar Clave de Cuenta'),
                'icon' => 'mode_edit'
            )
        );
        $this->view->assign('nextaction', self::ACTION_ACC_VIEW);
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     */
    public function getRequestAccountAccess()
    {
        // Obtener los datos de la cuenta
        $this->setAccountData();

        $this->view->addTemplate('request');
    }
}