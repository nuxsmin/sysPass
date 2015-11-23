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

use SP\Account\AccountHistory;
use SP\Core\Acl;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\Init;
use SP\Mgmt\PublicLink;
use SP\Mgmt\CustomFields;
use SP\Mgmt\User\Groups;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\SPException;
use SP\Account\UserAccounts;
use SP\Mgmt\User\UserPass;
use SP\Storage\DBUtil;
use SP\Util\Checks;
use SP\Util\ImageUtil;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las vistas de una cuenta
 *
 * @package Controller
 */
class Account extends Controller implements ActionsInterface
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
     * @param \SP\Core\Template $template   instancia del motor de plantillas
     * @param int          $lastAction int con la última acción realizada
     * @param int          $accountId  int con el id de la cuenta
     */
    public function __construct(\SP\Core\Template $template = null, $lastAction = null, $accountId = null)
    {
        parent::__construct($template);

        $this->setId($accountId);

        $this->view->assign('changesHash', '');
        $this->view->assign('chkUserEdit', '');
        $this->view->assign('chkGroupEdit', '');
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }

    /**
     * @param int $id
     */
    private function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->_gotData;
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
                'class' => 'titleGreen',
                'name' => _('Nueva Cuenta'),
                'icon' => 'add'
            )
        );
        $this->view->assign('showform', true);
        $this->view->assign('nextaction', Acl::ACTION_ACC_NEW);

        Session::setLastAcountId(0);
        $this->setCommonData();
        $this->setShowData();
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @return bool
     */
    protected function checkAccess($action = null)
    {
        $this->view->assign('showLogo', false);

        if (!Acl::checkUserAccess($this->getAction())) {
            $this->showError(self::ERR_PAGE_NO_PERMISSION);
            return false;
        } elseif (!UserPass::checkUserUpdateMPass()) {
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
        $this->getCustomFieldsForItem();

        if ($this->isGotData()) {
            $this->view->assign('accountIsHistory', $this->getAccount()->getAccountIsHistory());
            $this->view->assign('accountOtherUsers', $this->getAccount()->getAccountUsersId());
            $this->view->assign('accountOtherUsersName', UserAccounts::getUsersNameForAccount($this->getId()));
            $this->view->assign('accountOtherGroups', $this->getAccount()->getAccountUserGroupsId());
            $this->view->assign('accountOtherGroupsName', \SP\Mgmt\User\Groups::getGroupsNameForAccount($this->getId()));
            $this->view->assign('changesHash', $this->getAccount()->getAccountModHash());
            $this->view->assign('chkUserEdit', ($this->getAccount()->getAccountOtherUserEdit()) ? 'checked' : '');
            $this->view->assign('chkGroupEdit', ($this->getAccount()->getAccountOtherGroupEdit()) ? 'checked' : '');
            $this->view->assign('historyData', \SP\Account\AccountHistory::getAccountList($this->getAccount()->getAccountParentId()));
            $this->view->assign('isModified', ($this->view->accountData->account_dateEdit && $this->view->accountData->account_dateEdit <> '0000-00-00 00:00:00'));
            $this->view->assign('maxFileSize', round(Config::getValue('files_allowed_size') / 1024, 1));
            $this->view->assign('filesAllowedExts', Config::getValue('files_allowed_exts'));
            $this->view->assign('filesDelete', ($this->_action == Acl::ACTION_ACC_EDIT) ? 1 : 0);

            $publicLinkUrl = (Checks::publicLinksIsEnabled() && isset($this->view->accountData->publicLink_hash)) ? Init::$WEBURI . '/?h=' . $this->view->accountData->publicLink_hash . '&a=link' : '';
            $this->view->assign('publicLinkUrl', $publicLinkUrl);
        }

        $this->view->assign('accountParentId', Session::getLastAcountId());
        $this->view->assign('categories', DBUtil::getValuesForSelect('categories', 'category_id', 'category_name'));
        $this->view->assign('customers', DBUtil::getValuesForSelect('customers', 'customer_id', 'customer_name'));
        $this->view->assign('otherUsers', DBUtil::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('otherGroups', DBUtil::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));


    }

    /**
     * @return Account|AccountHistory
     */
    private function getAccount()
    {
        return $this->_account;
    }

    /**
     * @return int
     */
    private function getId()
    {
        return $this->_id;
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        // Establecer el id de la cuenta en activo y no del historial
        $id = (Session::getLastAcountId() !== 0) ? Session::getLastAcountId() : $this->getId();

        // Se comprueba que hayan campos con valores para la cuenta actual
        if ($this->isGotData() && CustomFields::checkCustomFieldExists(ActionsInterface::ACTION_ACC_NEW, $id)) {
            $this->view->assign('customFields', CustomFields::getCustomFieldsData(ActionsInterface::ACTION_ACC_NEW, $id));
        } else {
            $this->view->assign('customFields', CustomFields::getCustomFieldsForModule(ActionsInterface::ACTION_ACC_NEW));
        }
    }

    /**
     * Establecer variables para los interfaces que muestran datos
     */
    private function setShowData()
    {
        $aclData = ($this->isGotData()) ? $this->_account->getAccountDataForACL() : null;

        $this->view->assign('showHistory', (($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_HISTORY)
            && ($this->view->isModified || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)));
        $this->view->assign('showDetails', ($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY || $this->_action == Acl::ACTION_ACC_DELETE));
        $this->view->assign('showPass', ($this->_action == Acl::ACTION_ACC_NEW || $this->_action == Acl::ACTION_ACC_COPY));
        $this->view->assign('showFiles', (($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && (Checks::fileIsEnabled() && Acl::checkUserAccess(Acl::ACTION_ACC_FILES))));
        $this->view->assign('showViewPass', (($this->_action == Acl::ACTION_ACC_VIEW || $this->_action == Acl::ACTION_ACC_VIEW_HISTORY)
            && (Acl::checkAccountAccess(Acl::ACTION_ACC_VIEW_PASS, $aclData)
                && Acl::checkUserAccess(Acl::ACTION_ACC_VIEW_PASS))));
        $this->view->assign('showSave', ($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_NEW || $this->_action == Acl::ACTION_ACC_COPY));
        $this->view->assign('showEdit', ($this->_action == Acl::ACTION_ACC_VIEW
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)
            && !$this->_account->getAccountIsHistory()));
        $this->view->assign('showEditPass', ($this->_action == Acl::ACTION_ACC_EDIT || $this->_action == Acl::ACTION_ACC_VIEW
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT_PASS, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT_PASS)
            && !$this->_account->getAccountIsHistory()));
        $this->view->assign('showDelete', ($this->_action == Acl::ACTION_ACC_DELETE || $this->_action == Acl::ACTION_ACC_EDIT
            && Acl::checkAccountAccess(Acl::ACTION_ACC_DELETE, $aclData)
            && Acl::checkUserAccess(Acl::ACTION_ACC_DELETE)));
        $this->view->assign('showRestore', ($this->_action == Acl::ACTION_ACC_VIEW_HISTORY
            && Acl::checkAccountAccess(Acl::ACTION_ACC_EDIT, $this->_account->getAccountDataForACL($this->_account->getAccountParentId()))
            && Acl::checkUserAccess(Acl::ACTION_ACC_EDIT)));
        $this->view->assign('showLink', Checks::publicLinksIsEnabled() && Acl::checkUserAccess(Acl::ACTION_MGM_PUBLICLINKS));
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
                'class' => 'titleGreen',
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
            $this->setAccount(new \SP\Account\Account());
            $this->_account->setAccountId($this->getId());
            $this->_account->setAccountParentId($this->getId());

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->getAccount()->getAccountData());
            $this->view->assign('gotData', true);

//            $this->setAccountDetails();
            $this->setGotData(true);

            Session::setLastAcountId($this->getId());
        } catch (SPException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param Account|AccountHistory $account
     */
    private function setAccount($account)
    {
        $this->_account = $account;
    }

    /**
     * Establecer variables que contienen la información detallada de la cuenta.
     */
    private function setAccountDetails()
    {
        $this->_account->setAccountUsersId(UserAccounts::getUsersForAccount($this->getId()));
        $this->_account->setAccountUserGroupsId(Groups::getGroupsForAccount($this->getId()));
    }

    /**
     * @param boolean $gotData
     */
    private function setGotData($gotData)
    {
        $this->_gotData = $gotData;
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
                'class' => 'titleOrange',
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
                'class' => 'titleRed',
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
                'class' => 'titleNormal',
                'name' => _('Detalles de Cuenta'),
                'icon' => 'visibility'
            )
        );
        $this->view->assign('showform', false);

        \SP\Core\Session::setAccountParentId($this->getId());
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
                'class' => 'titleNormal',
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
            $this->setAccount(new \SP\Account\AccountHistory());
            $this->_account->setAccountId($this->getId());
            $this->_account->setAccountParentId(Session::getAccountParentId());

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->getAccount()->getAccountData());
            $this->view->assign('gotData', true);

//            $this->setAccountDetails();
            $this->setGotData(true);

            Session::setLastAcountId(Session::getAccountParentId());
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
                'class' => 'titleOrange',
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

    /**
     * Obtener la vista de detalles de cuenta para enlaces públicos
     *
     * @param PublicLink $PublicLink
     * @return bool
     */
    public function getAccountFromLink(PublicLink $PublicLink)
    {
        $this->setAction(self::ACTION_ACC_VIEW);

        // Obtener los datos de la cuenta antes y comprobar el acceso
        if (!$this->setAccountData()) {
            return false;
        }

        $this->view->addTemplate('account-link');
        $this->view->assign('title',
            array(
                'class' => 'titleNormal',
                'name' => _('Detalles de Cuenta'),
                'icon' => 'visibility'
            )
        );
        $this->view->assign('showform', false);
        $this->_account->incrementViewCounter();
        $this->_account->incrementDecryptCounter();
        $this->_account->getAccountPassData();

        // Desencriptar la clave de la cuenta
        $pass = Crypt::generateAesKey($PublicLink->getLinkHash());
        $masterPass = Crypt::getDecrypt($PublicLink->getPass(), $PublicLink->getPassIV(), $pass);
        $accountPass = Crypt::getDecrypt($this->_account->getAccountPass(), $this->_account->getAccountIV(), $masterPass);

        if (Config::getValue('publinks_image_enabled', false)) {
            $accountPass = ImageUtil::convertText($accountPass);
        }

        $this->view->assign('accountPass', $accountPass);
    }
}