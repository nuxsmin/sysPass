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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Account\Account;
use SP\Account\AccountAcl;
use SP\Account\AccountHistory;
use SP\Core\Acl;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\Init;
use SP\Core\Template;
use SP\DataModel\AccountExtData;
use SP\DataModel\CustomFieldData;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\Tags\Tag;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Exceptions\SPException;
use SP\Account\UserAccounts;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;
use SP\Util\ImageUtil;
use SP\Util\Json;

/**
 * Clase encargada de preparar la presentación de las vistas de una cuenta
 *
 * @package Controller
 */
class AccountController extends ControllerBase implements ActionsInterface
{
    /**
     * @var \SP\Account\Account|AccountHistory instancia para el manejo de datos de una cuenta
     */
    private $Account;
    /**
     * @var int con el id de la cuenta
     */
    private $id;
    /**
     * @var AccountExtData
     */
    private $AccountData;

    /**
     * Constructor
     *
     * @param Template $template   instancia del motor de plantillas
     * @param int      $lastAction int con la última acción realizada
     * @param int      $accountId  int con el id de la cuenta
     */
    public function __construct(Template $template = null, $lastAction = null, $accountId = null)
    {
        parent::__construct($template);

        $this->setId($accountId);

        $this->view->assign('changesHash', '');
        $this->view->assign('chkUserEdit', '');
        $this->view->assign('chkGroupEdit', '');
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('isView', false);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }

    /**
     * @param int $id
     */
    private function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->AccountData !== null;
    }

    /**
     * Obtener los datos para mostrar el interface para nueva cuenta
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getNewAccount()
    {
        $this->setAction(self::ACTION_ACC_NEW);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('account');
        $this->view->assign('title',
            [
                'class' => 'titleGreen',
                'name' => _('Nueva Cuenta'),
                'icon' => $this->icons->getIconAdd()->getIcon()
            ]
        );

        Session::setLastAcountId(0);
        $this->setCommonData();
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param null $action
     * @return bool
     */
    protected function checkAccess($action = null)
    {
        $this->view->assign('showLogo', false);

        if (!Acl::checkUserAccess($this->getAction())) {
            $this->showError(self::ERR_PAGE_NO_PERMISSION);
            return false;
        } elseif (!UserPass::checkUserUpdateMPass(Session::getUserId())) {
            $this->showError(self::ERR_UPDATE_MPASS);
            return false;
        } elseif ($this->id > 0 && !Acl::checkAccountAccess($this->getAction(), $this->Account->getAccountDataForACL())) {
            $this->showError(self::ERR_ACCOUNT_NO_PERMISSION);
            return false;
        }

        return true;
    }

    /**
     * Establecer variables comunes del formulario para todos los interfaces
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function setCommonData()
    {
        $this->getCustomFieldsForItem();

        if ($this->isGotData()) {
            $this->view->assign('accountIsHistory', $this->getAccount()->getAccountIsHistory());
            $this->view->assign('accountOtherUsers', UserAccounts::getUsersInfoForAccount($this->getId()));
            $this->view->assign('accountOtherGroups', GroupAccountsUtil::getGroupsInfoForAccount($this->getId()));
            $this->view->assign('accountTagsJson', Json::getJson(array_keys($this->getAccount()->getAccountData()->getTags())));
            $this->view->assign('historyData', AccountHistory::getAccountList($this->getAccount()->getAccountParentId()));
            $this->view->assign('isModified', $this->AccountData->getAccountDateEdit() && $this->AccountData->getAccountDateEdit() !== '0000-00-00 00:00:00');
            $this->view->assign('maxFileSize', round(Config::getConfig()->getFilesAllowedSize() / 1024, 1));
            $this->view->assign('filesAllowedExts', implode(',', Config::getConfig()->getFilesAllowedExts()));

            $publicLinkUrl = (Checks::publicLinksIsEnabled() && $this->AccountData->getPublicLinkHash() ? Init::$WEBURI . '/?h=' . $this->AccountData->getPublicLinkHash() . '&a=link' : '');
            $this->view->assign('publicLinkUrl', $publicLinkUrl);

            $this->view->assign('accountPassDate', gmdate('Y-m-d H:i:s', $this->AccountData->getAccountPassDate()));
            $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $this->AccountData->getAccountPassDateChange()));
        }

        $this->view->assign('actionId', $this->getAction());
        $this->view->assign('accountParentId', Session::getLastAcountId());
        $this->view->assign('categories', Category::getItem()->getItemsForSelect());
        $this->view->assign('customers', Customer::getItem()->getItemsForSelect());
        $this->view->assign('otherUsers', UserUtil::getUsersLogin());
        $this->view->assign('otherUsersJson', Json::getJson($this->view->otherUsers));
        $this->view->assign('otherGroups', Group::getItem()->getItemsForSelect());
        $this->view->assign('otherGroupsJson', Json::getJson($this->view->otherGroups));
        $this->view->assign('tagsJson', Json::getJson(Tag::getItem()->getItemsForSelect()));
        $this->view->assign('allowPrivate', Session::getUserProfile()->isAccPrivate());

        $this->view->assign('disabled', $this->view->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->view->isView ? 'readonly' : '');

        $AccountAcl = new AccountAcl();
        $AccountAcl->setModified($this->isGotData() ? $this->view->isModified : false);
        $AccountAcl->getAcl($this->getAccount(), $this->getAction());
        $this->view->assign('AccountAcl', $AccountAcl);
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        // Establecer el id de la cuenta en activo y no del historial
        $id = (Session::getLastAcountId() !== 0) ? Session::getLastAcountId() : $this->getId();

        $this->view->assign('customFields', CustomField::getItem(new CustomFieldData(ActionsInterface::ACTION_ACC_NEW))->getById($id));
    }

    /**
     * @return int
     */
    private function getId()
    {
        return $this->id;
    }

    /**
     * @return \SP\Account\Account|AccountHistory
     */
    private function getAccount()
    {
        return $this->Account ?: new Account(new AccountExtData());
    }

    /**
     * Obtener los datos para mostrar el interface para copiar cuenta
     *
     * @throws \SP\Core\Exceptions\SPException
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
            [
                'class' => 'titleGreen',
                'name' => _('Copiar Cuenta'),
                'icon' => $this->icons->getIconCopy()->getIcon()
            ]
        );

        $this->setCommonData();
    }

    /**
     * Establecer las variables que contienen la información de la cuenta.
     *
     * @return bool
     */
    private function setAccountData()
    {
        try {
            $this->setAccount(new Account(new AccountExtData($this->getId())));
            $this->Account->setAccountParentId($this->getId());
            $this->AccountData = $this->getAccount()->getData();

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->AccountData);
            $this->view->assign('gotData', $this->isGotData());
        } catch (SPException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param \SP\Account\Account|AccountHistory $account
     */
    private function setAccount($account)
    {
        $this->Account = $account;
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
            [
                'class' => 'titleOrange',
                'name' => _('Editar Cuenta'),
                'icon' => $this->icons->getIconEdit()->getIcon()
            ]
        );

        $this->setCommonData();
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
            [
                'class' => 'titleRed',
                'name' => _('Eliminar Cuenta'),
                'icon' => $this->icons->getIconDelete()->getIcon()
            ]
        );

        $this->setCommonData();
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
            [
                'class' => 'titleNormal',
                'name' => _('Detalles de Cuenta'),
                'icon' => $this->icons->getIconView()->getIcon()
            ]
        );

        $this->view->assign('isView', true);

        Session::setAccountParentId($this->getId());
        $this->Account->incrementViewCounter();

        $this->setCommonData();
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
            [
                'class' => 'titleNormal',
                'name' => _('Detalles de Cuenta'),
                'icon' => 'access_time'
            ]
        );

        $this->view->assign('isView', true);
        $this->Account->setAccountIsHistory(1);

        $this->setCommonData();
    }

    /**
     * Establecer las variables que contienen la información de la cuenta en una fecha concreta.
     *
     * @return bool
     */
    private function setAccountDataHistory()
    {
        try {
            $this->setAccount(new AccountHistory(new AccountExtData($this->getId())));
            $this->Account->setAccountParentId(Session::getAccountParentId());
            $this->AccountData = $this->getAccount()->getData();

            $this->view->assign('accountId', $this->getId());
            $this->view->assign('accountData', $this->AccountData);
            $this->view->assign('gotData', $this->isGotData());
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

        $this->view->addTemplate('account-editpass');
        $this->view->assign('title',
            [
                'class' => 'titleOrange',
                'name' => _('Modificar Clave de Cuenta'),
                'icon' => $this->icons->getIconEditPass()->getIcon()
            ]
        );

        $this->view->assign('accountPassDateChange', gmdate('Y-m-d', $this->AccountData->getAccountPassDateChange()));
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
     * @param \SP\Mgmt\PublicLinks\PublicLink $PublicLink
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
            [
                'class' => 'titleNormal',
                'name' => _('Detalles de Cuenta'),
                'icon' => $this->icons->getIconView()->getIcon()
            ]
        );
        $this->Account->incrementViewCounter();
        $this->Account->incrementDecryptCounter();
        $this->Account->getAccountPassData();

        // Desencriptar la clave de la cuenta
        $pass = Crypt::generateAesKey($PublicLink->getItemData()->getLinkHash());
        $masterPass = Crypt::getDecrypt($PublicLink->getItemData()->getPass(), $PublicLink->getItemData()->getPassIV(), $pass);
        $accountPass = Crypt::getDecrypt($this->Account->getAccountData()->getAccountPass(), $this->Account->getAccountData()->getAccountIV(), $masterPass);

        if (Config::getConfig()->isPublinksImageEnabled()) {
            $accountPass = ImageUtil::convertText($accountPass);
        }

        $this->view->assign('accountPass', $accountPass);
    }
}