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

namespace Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las vistas de una cuenta
 *
 * @package Controller
 */
class AccountC extends \SP_Controller implements ActionsInterface
{
    /**
     * Tipos de acciones
     */
    const SAVETYPE_NONE = 0;
    const SAVETYPE_NEW = 1;
    const SAVETYPE_EDIT = 2;
    const SAVETYPE_HISTORY = 5;
    /**
     * @var ultima acción realizada
     */
    public $lastAction;
    /**
     * @var int con la acción a realizar
     */
    protected $_action;
    /**
     * @var \SP_Accounts instancia para el manejo de datos de una cuenta
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
     * @param \SP_Template $template instancia del motor de plantillas
     * @param $lastAction            int con la última acción realizada
     * @param null $accountId               int con el id de la cuenta
     */
    public function __construct(\SP_Template $template = null, $lastAction, $accountId = null)
    {
        parent::__construct($template);

        $this->setId($accountId);

        $this->_account = new \SP_Accounts();
        $this->_account->accountId = $accountId;
        $this->_account->lastAction = $lastAction;
        $this->_account->accountParentId = \SP_Common::parseParams('s', 'accParentId', 0);

        $this->view->assign('account', $this->_account);
        $this->view->assign('changesHash', '');
        $this->view->assign('chkUserEdit', '');
        $this->view->assign('chkGroupEdit', '');
        $this->view->assign('sk', \SP_Common::getSessionKey(true));
    }

    /**
     * Establecer el id de la cuenta
     *
     * @param $id int con el id de la cuenta
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
        $this->view->assign('savetype', self::SAVETYPE_NEW);
        $this->view->assign('title', array('class' => 'titleGreen', 'name' => _('Nueva Cuenta')));
        $this->view->assign('showform', true);
        $this->view->assign('nextaction', \SP_Acl::ACTION_ACC_SEARCH);

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
        if (!\SP_Acl::checkUserAccess($this->_action)) {
            $this->showError(self::ERR_PAGE_NO_PERMISSION);
            return false;
        } elseif (!\SP_Users::checkUserUpdateMPass()) {
            $this->showError(self::ERR_UPDATE_MPASS);
            return false;
        } elseif ($this->_id > 0 && !\SP_Acl::checkAccountAccess($this->_action, $this->_account->getAccountDataForACL())) {
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
        if ($this->_gotData) {
            $this->view->assign('changesHash', $this->_account->calcChangesHash());
            $this->view->assign('chkUserEdit', ($this->view->accountData->account_otherUserEdit) ? 'checked' : '');
            $this->view->assign('chkGroupEdit', ($this->view->accountData->account_otherGroupEdit) ? 'checked' : '');
        }

        $this->view->assign('customersSelProp', array("name" => "customerId",
            "id" => "selCustomer",
            "class" => "",
            "size" => 1,
            "label" => "",
            "selected" => ($this->_gotData) ? $this->view->accountData->account_customerId : '',
            "default" => "",
            "js" => "",
            "attribs" => ""
        ));

        $this->view->assign('categoriesSelProp', array("name" => "categoryId",
            "id" => "selCategory",
            "class" => "",
            "size" => 1,
            "label" => "",
            "selected" => ($this->_gotData) ? $this->view->accountData->account_categoryId : '',
            "default" => "",
            "js" => "",
            "attribs" => ""
        ));

        $this->view->assign('isModified', ($this->_gotData && $this->view->accountData->account_dateEdit && $this->view->accountData->account_dateEdit <> '0000-00-00 00:00:00'));
        $this->view->assign('filesDelete', ($this->_action == \SP_Acl::ACTION_ACC_EDIT) ? 1 : 0);
        $this->view->assign('maxFileSize', round(\SP_Config::getValue('files_allowed_size') / 1024, 1));
    }

    /**
     * Establecer variables para los interfaces que muestran datos
     */
    private function setShowData()
    {
        $this->view->assign('showHistory', (($this->_action == \SP_Acl::ACTION_ACC_VIEW || $this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY)
            && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_VIEW_HISTORY)
            && ($this->view->isModified || $this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY)));
        $this->view->assign('showDetails', ($this->_action == \SP_Acl::ACTION_ACC_VIEW || $this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY || $this->_action == \SP_Acl::ACTION_ACC_DELETE));
        $this->view->assign('showPass', ($this->_action == \SP_Acl::ACTION_ACC_NEW || $this->_action == \SP_Acl::ACTION_ACC_COPY));
        $this->view->assign('showFiles', (($this->_action == \SP_Acl::ACTION_ACC_EDIT || $this->_action == \SP_Acl::ACTION_ACC_VIEW || $this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY)
            && (\SP_Util::fileIsEnabled() && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_FILES))));
        $this->view->assign('showViewPass', (($this->_action == \SP_Acl::ACTION_ACC_VIEW || $this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY)
            && (\SP_Acl::checkAccountAccess(\SP_Acl::ACTION_ACC_VIEW_PASS, $this->_account->getAccountDataForACL())
                && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_VIEW_PASS))));
        $this->view->assign('showSave', ($this->_action == \SP_Acl::ACTION_ACC_EDIT || $this->_action == \SP_Acl::ACTION_ACC_NEW || $this->_action == \SP_Acl::ACTION_ACC_COPY));
        $this->view->assign('showEdit', ($this->_action == \SP_Acl::ACTION_ACC_VIEW
            && \SP_Acl::checkAccountAccess(\SP_Acl::ACTION_ACC_EDIT, $this->_account->getAccountDataForACL())
            && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_EDIT)
            && !$this->_account->accountIsHistory));
        $this->view->assign('showEditPass', ($this->_action == \SP_Acl::ACTION_ACC_EDIT
            && \SP_Acl::checkAccountAccess(\SP_Acl::ACTION_ACC_EDIT_PASS, $this->_account->getAccountDataForACL())
            && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_EDIT_PASS)
            && !$this->_account->accountIsHistory));
        $this->view->assign('showDelete', ($this->_action == \SP_Acl::ACTION_ACC_DELETE
            && \SP_Acl::checkAccountAccess(\SP_Acl::ACTION_ACC_DELETE, $this->_account->getAccountDataForACL())
            && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_DELETE)));
        $this->view->assign('showRestore', ($this->_action == \SP_Acl::ACTION_ACC_VIEW_HISTORY
            && \SP_Acl::checkAccountAccess(\SP_Acl::ACTION_ACC_EDIT, $this->_account->getAccountDataForACL($this->_account->accountParentId))
            && \SP_Acl::checkUserAccess(\SP_Acl::ACTION_ACC_EDIT)));
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
        $this->view->assign('savetype', self::SAVETYPE_NEW);
        $this->view->assign('title', array('class' => 'titleGreen', 'name' => _('Copiar Cuenta')));
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
            $this->view->assign('accountData', $this->_account->getAccount());
            $this->view->assign('gotData', true);
            $this->setAccountDetails();
            $this->_gotData = true;
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Establecer variables que contienen la información detallada de la cuenta.
     */
    private function setAccountDetails()
    {
        $this->_account->accountUsersId = \SP_Users::getUsersForAccount($this->_id);
        $this->_account->accountUserGroupsId = \SP_Groups::getGroupsForAccount($this->_id);
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
        $this->view->assign('savetype', self::SAVETYPE_EDIT);
        $this->view->assign('title', array('class' => 'titleOrange', 'name' => _('Editar Cuenta')));
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
        $this->view->assign('savetype', self::SAVETYPE_NONE);
        $this->view->assign('title', array('class' => 'titleRed', 'name' => _('Eliminar Cuenta')));
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
        $this->view->assign('savetype', self::SAVETYPE_NONE);
        $this->view->assign('title', array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta')));
        $this->view->assign('showform', false);

        $_SESSION["accParentId"] = $this->_id;
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
        $this->view->assign('savetype', self::SAVETYPE_HISTORY);
        $this->view->assign('title', array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta')));
        $this->view->assign('showform', false);

        $this->_account->accountIsHistory = true;

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
            $this->view->assign('accountData', $this->_account->getAccountHistory());
            $this->setAccountDetails();
            $this->_gotData = true;
        } catch (\Exception $e) {
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
        $this->view->assign('savetype', self::SAVETYPE_EDIT);
        $this->view->assign('title', array('class' => 'titleOrange', 'name' => _('Modificar Clave de Cuenta')));
        $this->view->assign('nextaction', self::ACTION_ACC_VIEW);
    }

    /**
     * Obtener los datos para mostrar el interface de solicitud de cambios en una cuenta
     */
    public function getRequestAccountAccess()
    {
        $this->view->addTemplate('request');
    }
}