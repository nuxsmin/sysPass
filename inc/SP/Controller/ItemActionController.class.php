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

namespace SP\Controller;

use SP\Account\Account;
use SP\Account\AccountFavorites;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\DataModel\CustomFieldData;
use SP\DataModel\PluginData;
use SP\DataModel\PublicLinkData;
use SP\Forms\AccountForm;
use SP\Forms\ApiTokenForm;
use SP\Forms\CategoryForm;
use SP\Forms\CustomerForm;
use SP\Forms\CustomFieldDefForm;
use SP\Forms\GroupForm;
use SP\Forms\ProfileForm;
use SP\Forms\TagForm;
use SP\Forms\UserForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Files\File;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Plugins\Plugin;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserLdapSync;
use SP\Util\Json;

/**
 * Class AjaxSaveController
 *
 * @package SP\Controller
 */
class ItemActionController implements ItemControllerInterface
{
    use RequestControllerTrait;

    /**
     * @var CustomFieldData
     */
    protected $CustomFieldData;

    /**
     * AjaxSaveController constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Ejecutar la acción solicitada
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        try {
            switch ($this->actionId) {
                case ActionsInterface::ACTION_USR_USERS_NEW:
                case ActionsInterface::ACTION_USR_USERS_EDIT:
                case ActionsInterface::ACTION_USR_USERS_EDITPASS:
                case ActionsInterface::ACTION_USR_USERS_DELETE:
                    $this->userAction();
                    break;
                case ActionsInterface::ACTION_USR_GROUPS_NEW:
                case ActionsInterface::ACTION_USR_GROUPS_EDIT:
                case ActionsInterface::ACTION_USR_GROUPS_DELETE:
                    $this->groupAction();
                    break;
                case ActionsInterface::ACTION_USR_PROFILES_NEW:
                case ActionsInterface::ACTION_USR_PROFILES_EDIT:
                case ActionsInterface::ACTION_USR_PROFILES_DELETE:
                    $this->profileAction();
                    break;
                case ActionsInterface::ACTION_MGM_CUSTOMERS_NEW:
                case ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT:
                case ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE:
                    $this->customerAction();
                    break;
                case ActionsInterface::ACTION_MGM_CATEGORIES_NEW:
                case ActionsInterface::ACTION_MGM_CATEGORIES_EDIT:
                case ActionsInterface::ACTION_MGM_CATEGORIES_DELETE:
                    $this->categoryAction();
                    break;
                case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
                case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
                case ActionsInterface::ACTION_MGM_APITOKENS_DELETE:
                    $this->tokenAction();
                    break;
                case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW:
                case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT:
                case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE:
                    $this->customFieldAction();
                    break;
                case ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW:
                case ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE:
                case ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH:
                    $this->publicLinkAction();
                    break;
                case ActionsInterface::ACTION_MGM_TAGS_NEW:
                case ActionsInterface::ACTION_MGM_TAGS_EDIT:
                case ActionsInterface::ACTION_MGM_TAGS_DELETE:
                    $this->tagAction();
                    break;
                case ActionsInterface::ACTION_MGM_FILES_DELETE:
                    $this->fileAction();
                    break;
                case ActionsInterface::ACTION_MGM_PLUGINS_ENABLE:
                case ActionsInterface::ACTION_MGM_PLUGINS_DISABLE:
                    $this->pluginAction();
                    break;
                case ActionsInterface::ACTION_ACC_NEW:
                case ActionsInterface::ACTION_ACC_COPY:
                case ActionsInterface::ACTION_ACC_EDIT:
                case ActionsInterface::ACTION_ACC_EDIT_PASS:
                case ActionsInterface::ACTION_ACC_EDIT_RESTORE:
                case ActionsInterface::ACTION_ACC_DELETE:
                case ActionsInterface::ACTION_MGM_ACCOUNTS_DELETE:
                    $this->accountAction();
                    break;
                case ActionsInterface::ACTION_ACC_FAVORITES_ADD:
                case ActionsInterface::ACTION_ACC_FAVORITES_DELETE:
                    $this->favoriteAction();
                    break;
                case ActionsInterface::ACTION_USR_SYNC_LDAP:
                    $this->ldapImportAction();
                    break;
                default:
                    $this->invalidAction();
            }
        } catch (\Exception $e) {
            $this->jsonResponse->setDescription($e->getMessage());
        }

        Json::returnJson($this->jsonResponse);
    }

    /**
     * Acciones sobre usuarios
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ValidationException
     */
    protected function userAction()
    {
        $Form = new UserForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_USR_USERS);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_USR_USERS_NEW:
                User::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Usuario creado'));
                break;
            case ActionsInterface::ACTION_USR_USERS_EDIT:
                User::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Usuario actualizado'));
                break;
            case ActionsInterface::ACTION_USR_USERS_DELETE:
                User::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->jsonResponse->setDescription(_('Usuario eliminado'));
                break;
            case ActionsInterface::ACTION_USR_USERS_EDITPASS:
                User::getItem($Form->getItemData())->updatePass();

                $this->jsonResponse->setDescription(_('Clave actualizada'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Guardar los datos de los campos personalizados del módulo
     *
     * @param $moduleId
     */
    protected function setCustomFieldData($moduleId)
    {
        $this->CustomFieldData = new CustomFieldData();
        $this->CustomFieldData->setId($this->itemId);
        $this->CustomFieldData->setModule($moduleId);
    }

    /**
     * Guardar los datos de los campos personalizados del módulo
     */
    protected function addCustomFieldData()
    {
        $customFields = Request::analyze('customfield');

        if (is_array($customFields)) {
            CustomFieldsUtil::addItemCustomFields($customFields, $this->CustomFieldData);
        }
    }

    /**
     * Actualizar los datos de los campos personalizados del módulo
     */
    protected function updateCustomFieldData()
    {
        $customFields = Request::analyze('customfield');

        if (is_array($customFields)) {
            CustomFieldsUtil::updateItemCustomFields($customFields, $this->CustomFieldData);
        }
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function deleteCustomFieldData()
    {
        CustomField::getItem($this->CustomFieldData)->delete($this->itemId);
    }

    /**
     * Acciones sobre grupos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function groupAction()
    {
        $Form = new GroupForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_USR_GROUPS);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_USR_GROUPS_NEW:
                Group::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Grupo creado'));
                break;
            case ActionsInterface::ACTION_USR_GROUPS_EDIT:
                Group::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Grupo actualizado'));
                break;
            case ActionsInterface::ACTION_USR_GROUPS_DELETE:
                Group::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->jsonResponse->setDescription(_('Grupo eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre perfiles
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function profileAction()
    {
        $Form = new ProfileForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_USR_PROFILES);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_USR_PROFILES_NEW:
                Profile::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Perfil creado'));
                break;
            case ActionsInterface::ACTION_USR_PROFILES_EDIT:
                Profile::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Perfil actualizado'));
                break;
            case ActionsInterface::ACTION_USR_PROFILES_DELETE:
                Profile::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->jsonResponse->setDescription(_('Perfil eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre clientes
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function customerAction()
    {
        $Form = new CustomerForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_MGM_CUSTOMERS);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_CUSTOMERS_NEW:
                Customer::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Cliente creado'));
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT:
                Customer::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Cliente actualizado'));
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE:
                Customer::getItem()->delete($this->itemId);

                $this->jsonResponse->setDescription(_('Cliente eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre categorías
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function categoryAction()
    {
        $Form = new CategoryForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_MGM_CATEGORIES);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_CATEGORIES_NEW:
                Category::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Categoría creada'));
                break;
            case ActionsInterface::ACTION_MGM_CATEGORIES_EDIT:
                Category::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Categoría actualizada'));
                break;
            case ActionsInterface::ACTION_MGM_CATEGORIES_DELETE:
                Category::getItem()->delete($this->itemId);

                $this->jsonResponse->setDescription(_('Categoría eliminada'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre tokens API
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function tokenAction()
    {
        $Form = new ApiTokenForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
                $Form->getItemData()->addToken();

                $this->jsonResponse->setDescription(_('Autorización creada'));
                break;
            case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
                $Form->getItemData()->updateToken();

                $this->jsonResponse->setDescription(_('Autorización actualizada'));
                break;
            case ActionsInterface::ACTION_MGM_APITOKENS_DELETE:
                $Form->getItemData()->deleteToken();

                $this->jsonResponse->setDescription(_('Autorización eliminada'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre campos personalizados
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function customFieldAction()
    {
        $Form = new CustomFieldDefForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW:
                CustomFieldDef::getItem($Form->getItemData())->add();

                $this->jsonResponse->setDescription(_('Campo creado'));
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT:
                CustomFieldDef::getItem($Form->getItemData())->update();

                $this->jsonResponse->setDescription(_('Campo actualizado'));
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE:
                CustomFieldDef::getItem()->delete($this->itemId);

                $this->jsonResponse->setDescription(_('Campo eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre enlaces públicos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function publicLinkAction()
    {
        $PublicLinkData = new PublicLinkData();
        $PublicLinkData->setPublicLinkItemId($this->itemId);
        $PublicLinkData->setTypeId(PublicLink::TYPE_ACCOUNT);
        $PublicLinkData->setNotify(Request::analyze('notify', false, false, true));

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW:
                $PublicLinkData->setItemId($this->itemId);
                PublicLink::getItem($PublicLinkData)->add();

                $this->jsonResponse->setDescription(_('Enlace creado'));
                break;
            case ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH:
                PublicLink::getItem(PublicLink::getItem()->getById($this->itemId))->refresh();

                $this->jsonResponse->setDescription(_('Enlace actualizado'));
                break;
            case ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE:
                PublicLink::getItem()->delete($this->itemId);

                $this->jsonResponse->setDescription(_('Enlace eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre etiquetas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function tagAction()
    {
        $Form = new TagForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_TAGS_NEW:
                Tag::getItem($Form->getItemData())->add();

                $this->jsonResponse->setDescription(_('Etiqueta creada'));
                break;
            case ActionsInterface::ACTION_MGM_TAGS_EDIT:
                Tag::getItem($Form->getItemData())->update();

                $this->jsonResponse->setDescription(_('Etiqueta actualizada'));
                break;
            case ActionsInterface::ACTION_MGM_TAGS_DELETE:
                Tag::getItem()->delete($this->itemId);

                $this->jsonResponse->setDescription(_('Etiqueta eliminada'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre archivos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function fileAction()
    {
        File::getItem()->delete($this->itemId);
        $this->jsonResponse->setDescription(_('Archivo actualizado'));

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre cuentas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function accountAction()
    {
        $Form = new AccountForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACTION_ACC);

        $Account = new Account($Form->getItemData());

        switch ($this->actionId) {
            case ActionsInterface::ACTION_ACC_NEW:
            case ActionsInterface::ACTION_ACC_COPY:
                $Form->getItemData()->setAccountUserId(Session::getUserData()->getUserId());

                $Account->createAccount();

                $this->CustomFieldData->setId($Account->getAccountData()->getId());

                $this->addCustomFieldData();

                $this->jsonResponse->setDescription(_('Cuenta creada'));
                break;
            case ActionsInterface::ACTION_ACC_EDIT:
                $Account->updateAccount();
                $this->updateCustomFieldData();

                $this->jsonResponse->setDescription(_('Cuenta actualizada'));
                break;
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                $Account->updateAccountPass();

                $this->jsonResponse->setDescription(_('Clave actualizada'));
                break;
            case ActionsInterface::ACTION_ACC_EDIT_RESTORE:
                $Account->restoreFromHistory(Request::analyze('accountHistoryId', 0));

                $this->jsonResponse->setDescription(_('Cuenta restaurada'));
                break;
            case ActionsInterface::ACTION_ACC_DELETE:
                $Account->deleteAccount();

                $this->jsonResponse->setDescription(_('Cuenta eliminada'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre cuentas favoritas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    private function favoriteAction()
    {
        $userId = Session::getUserData()->getUserId();

        switch ($this->actionId) {
            case ActionsInterface::ACTION_ACC_FAVORITES_ADD:
                AccountFavorites::addFavorite($this->itemId, $userId);

                $this->jsonResponse->setDescription(_('Favorito añadido'));
                break;
            case ActionsInterface::ACTION_ACC_FAVORITES_DELETE:
                AccountFavorites::deleteFavorite($this->itemId, $userId);

                $this->jsonResponse->setDescription(_('Favorito eliminado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }

    /**
     * Importar usuarios de LDAP
     */
    private function ldapImportAction()
    {
        if (UserLdapSync::run()) {
            $this->jsonResponse->setStatus(0);
            $this->jsonResponse->setDescription(_('Importación de usuarios de LDAP realizada'));
            $this->jsonResponse->addMessage(sprintf(_('Usuarios importados %d/%d'), UserLdapSync::$syncedObjects, UserLdapSync::$totalObjects));
            $this->jsonResponse->addMessage(sprintf(_('Errores: %d'), UserLdapSync::$errorObjects));
        } else {
            $this->jsonResponse->setDescription(_('Error al importar usuarios de LDAP'));
        }

        $this->jsonResponse->addMessage(_('Revise el registro de eventos para más detalles'));
    }

    /**
     * Acciones sobre plugins
     */
    private function pluginAction()
    {
        $PluginData = new PluginData();
        $PluginData->setPluginId($this->itemId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_PLUGINS_ENABLE:
                $PluginData->setPluginEnabled(1);
                Plugin::getItem($PluginData)->toggle();

                $this->jsonResponse->setDescription(_('Plugin habilitado'));
                break;
            case ActionsInterface::ACTION_MGM_PLUGINS_DISABLE:
                $PluginData->setPluginEnabled(0);
                Plugin::getItem($PluginData)->toggle();

                $this->jsonResponse->setDescription(_('Plugin deshabilitado'));
                break;
        }

        $this->jsonResponse->setStatus(0);
    }
}