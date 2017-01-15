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

namespace SP\Controller;

use SP\Account\Account;
use SP\Account\AccountFavorites;
use SP\Account\AccountUtil;
use SP\Auth\AuthUtil;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\DataModel\CustomFieldData;
use SP\DataModel\NoticeData;
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
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Files\File;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Notices\Notice;
use SP\Mgmt\Plugins\Plugin;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserLdapSync;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;
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
     *
     * @throws \SP\Core\Exceptions\SPException
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
        $this->Log = new Log();

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
                case ActionsInterface::ACTION_MGM_PLUGINS_RESET:
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
                case ActionsInterface::ACTION_NOT_USER_CHECK:
                case ActionsInterface::ACTION_NOT_USER_VIEW:
                case ActionsInterface::ACTION_NOT_USER_DELETE:
                    $this->noticeAction();
                    break;
                case ActionsInterface::ACTION_ACC_REQUEST:
                    $this->requestAccountAction();
                    break;
                default:
                    $this->invalidAction();
            }
        } catch (\Exception $e) {
            $this->JsonResponse->setDescription($e->getMessage());
        }

        if ($this->Log->getAction() !== null) {
            $this->Log->writeLog();
            $this->JsonResponse->setDescription($this->Log->getHtmlDescription());
        }

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Acciones sobre usuarios
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Usuario', false));
                $this->Log->addDescription(__('Usuario creado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUserName());
                $this->Log->addDetails(__('Login', false), $Form->getItemData()->getUserLogin());

                if ($Form->getItemData()->isUserIsChangePass()
                    && !AuthUtil::mailPassRecover($Form->getItemData())
                ) {
                    $this->Log->addDescription(__('No se pudo realizar la petición de cambio de clave.', false));
                }
                break;
            case ActionsInterface::ACTION_USR_USERS_EDIT:
                User::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Usuario', false));
                $this->Log->addDescription(__('Usuario actualizado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUserName());
                $this->Log->addDetails(__('Login', false), $Form->getItemData()->getUserLogin());

                if ($Form->getItemData()->isUserIsChangePass()
                    && !AuthUtil::mailPassRecover($Form->getItemData())
                ) {
                    $this->Log->addDescription(__('No se pudo realizar la petición de cambio de clave.', false));
                }
                break;
            case ActionsInterface::ACTION_USR_USERS_DELETE:
                $UserData = User::getItem()->getById($this->itemId);

                User::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->Log->setAction(__('Eliminar Usuario', false));
                $this->Log->addDescription(__('Usuario eliminado', false));
                $this->Log->addDetails(__('Nombre', false), $UserData->getUserName());
                $this->Log->addDetails(__('Login', false), $UserData->getUserLogin());
                break;
            case ActionsInterface::ACTION_USR_USERS_EDITPASS:
                $UserData = User::getItem()->getById($this->itemId);

                User::getItem($Form->getItemData())->updatePass();

                $this->Log->setAction(__('Actualizar Clave Usuario', false));
                $this->Log->addDetails(__('Nombre', false), $UserData->getUserName());
                $this->Log->addDetails(__('Login', false), $UserData->getUserLogin());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
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
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
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
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
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
     * @throws \SP\Core\Exceptions\InvalidClassException
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
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Grupo', false));
                $this->Log->addDescription(__('Grupo creado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUsergroupName());
                break;
            case ActionsInterface::ACTION_USR_GROUPS_EDIT:
                Group::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Grupo', false));
                $this->Log->addDescription(__('Grupo actualizado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUsergroupName());
                break;
            case ActionsInterface::ACTION_USR_GROUPS_DELETE:
                $GroupData = Group::getItem()->getById($this->itemId);

                Group::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->Log->setAction(__('Eliminar Grupo', false));
                $this->Log->addDescription(__('Grupo eliminado', false));
                $this->Log->addDetails(__('Nombre', false), $GroupData->getUsergroupName());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre perfiles
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Perfil', false));
                $this->Log->addDescription(__('Perfil creado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUserprofileName());
                break;
            case ActionsInterface::ACTION_USR_PROFILES_EDIT:
                Profile::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Perfil', false));
                $this->Log->addDescription(__('Perfil actualizado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getUserprofileName());
                break;
            case ActionsInterface::ACTION_USR_PROFILES_DELETE:
                $ProfileData = Profile::getItem()->getById($this->itemId);

                Profile::getItem()->delete($this->itemId);
                $this->deleteCustomFieldData();

                $this->Log->setAction(__('Eliminar Perfil', false));
                $this->Log->addDescription(__('Perfil eliminado', false));
                $this->Log->addDetails(__('Nombre', false), $ProfileData->getUserprofileName());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre clientes
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Cliente', false));
                $this->Log->addDescription(__('Cliente creado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getCustomerName());
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT:
                Customer::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Cliente', false));
                $this->Log->addDescription(__('Cliente actualizado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getCustomerName());
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE:
                $CustomerData = Customer::getItem()->getById($this->itemId);
                Customer::getItem()->delete($this->itemId);

                $this->Log->setAction(__('Eliminar Cliente', false));
                $this->Log->addDescription(__('Cliente eliminado', false));
                $this->Log->addDetails(__('Nombre', false), $CustomerData->getCustomerName());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre categorías
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Categoría', false));
                $this->Log->addDescription(__('Categoría creada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getCategoryName());
                break;
            case ActionsInterface::ACTION_MGM_CATEGORIES_EDIT:
                Category::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Categoría', false));
                $this->Log->addDescription(__('Categoría actualizada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getCategoryName());
                break;
            case ActionsInterface::ACTION_MGM_CATEGORIES_DELETE:
                $CategoryData = Category::getItem()->getById($this->itemId);
                Category::getItem()->delete($this->itemId);

                $this->Log->setAction(__('Eliminar Categoría', false));
                $this->Log->addDescription(__('Categoría eliminada', false));
                $this->Log->addDetails(__('Nombre', false), $CategoryData->getCategoryName());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre tokens API
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    protected function tokenAction()
    {
        $Form = new ApiTokenForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
                $Form->getItemData()->addToken();

                $this->Log->setAction(__('Crear Autorización', false));
                $this->Log->addDescription(__('Autorización creada', false));
                $this->Log->addDetails(__('Usuario', false), UserUtil::getUserLoginById($Form->getItemData()->getUserId()));
                break;
            case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
                $Form->getItemData()->updateToken();

                $this->Log->setAction(__('Actualizar Autorización', false));
                $this->Log->addDescription(__('Autorización actualizada', false));
                $this->Log->addDetails(__('Usuario', false), UserUtil::getUserLoginById($Form->getItemData()->getUserId()));
                break;
            case ActionsInterface::ACTION_MGM_APITOKENS_DELETE:
                $Form->getItemData()->deleteToken();

                $this->Log->setAction(__('Eliminar Autorización', false));
                $this->Log->addDescription(__('Autorización eliminada', false));
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre campos personalizados
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
     */
    protected function customFieldAction()
    {
        $Form = new CustomFieldDefForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW:
                CustomFieldDef::getItem($Form->getItemData())->add();

                $this->Log->setAction(__('Crear Campo', false));
                $this->Log->addDescription(__('Campo creado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getName());
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT:
                CustomFieldDef::getItem($Form->getItemData())->update();

                $this->Log->setAction(__('Actualizar Campo', false));
                $this->Log->addDescription(__('Campo actualizado', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getName());
                break;
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE:
                CustomFieldDef::getItem()->delete($this->itemId);

                $this->Log->setAction(__('Eliminar Campo', false));
                $this->Log->addDescription(__('Campo eliminado', false));
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre enlaces públicos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Enlace', false));
                $this->Log->addDescription(__('Enlace creado', false));
                $this->Log->addDetails(__('Tipo', false), $PublicLinkData->getTypeId());
                $this->Log->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                $this->Log->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getUserId()));
                break;
            case ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH:
                $PublicLinkData = PublicLink::getItem()->getById($this->itemId);
                PublicLink::getItem($PublicLinkData)->refresh();

                $this->Log->setAction(__('Actualizar Enlace', false));
                $this->Log->addDescription(__('Enlace actualizado', false));
                $this->Log->addDetails(__('Tipo', false), $PublicLinkData->getTypeId());
                $this->Log->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                $this->Log->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getUserId()));
                break;
            case ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE:
                $PublicLinkData = PublicLink::getItem()->getById($this->itemId);
                PublicLink::getItem()->delete($this->itemId);

                $this->Log->setAction(__('Eliminar Enlace', false));
                $this->Log->addDescription(__('Enlace eliminado', false));
                $this->Log->addDetails(__('Tipo', false), $PublicLinkData->getTypeId());
                $this->Log->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                $this->Log->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getUserId()));
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre etiquetas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function tagAction()
    {
        $Form = new TagForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_TAGS_NEW:
                Tag::getItem($Form->getItemData())->add();

                $this->Log->setAction(__('Crear Etiqueta', false));
                $this->Log->addDescription(__('Etiqueta creada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getTagName());
                break;
            case ActionsInterface::ACTION_MGM_TAGS_EDIT:
                Tag::getItem($Form->getItemData())->update();

                $this->Log->setAction(__('Actualizar Etiqueta', false));
                $this->Log->addDescription(__('Etiqueta actualizada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getTagName());
                break;
            case ActionsInterface::ACTION_MGM_TAGS_DELETE:
                Tag::getItem()->delete($this->itemId);

                $this->Log->setAction(__('Eliminar Etiqueta', false));
                $this->Log->addDescription(__('Etiqueta eliminada', false));
                break;
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre archivos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
     */
    protected function fileAction()
    {
        $FileData = File::getItem()->getInfoById($this->itemId);

        File::getItem()->delete($this->itemId);

        $this->Log->setAction(__('Eliminar Archivo', false));
        $this->Log->addDescription(__('Archivo eliminado', false));
        $this->Log->addDetails(__('ID', false), $this->itemId);
        $this->Log->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($FileData->getAccfileAccountId()));
        $this->Log->addDetails(__('Archivo', false), $FileData->getAccfileName());
        $this->Log->addDetails(__('Tipo', false), $FileData->getAccfileType());
        $this->Log->addDetails(__('Tamaño', false), $FileData->getRoundSize() . 'KB');

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre plugins
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
     */
    protected function pluginAction()
    {
        $PluginData = new PluginData();
        $PluginData->setPluginId($this->itemId);

        switch ($this->actionId) {
            case ActionsInterface::ACTION_MGM_PLUGINS_ENABLE:
                $PluginData->setPluginEnabled(1);
                Plugin::getItem($PluginData)->toggle();

                $this->Log->setAction(__('Actualizar Plugin', false));
                $this->Log->addDescription(__('Plugin habilitado', false));
                $this->Log->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
            case ActionsInterface::ACTION_MGM_PLUGINS_DISABLE:
                $PluginData->setPluginEnabled(0);
                Plugin::getItem($PluginData)->toggle();

                $this->Log->setAction(__('Actualizar Plugin', false));
                $this->Log->addDescription(__('Plugin deshabilitado', false));
                $this->Log->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
            case ActionsInterface::ACTION_MGM_PLUGINS_RESET:
                Plugin::getItem()->reset($this->itemId);

                $this->Log->setAction(__('Actualizar Plugin', false));
                $this->Log->addDescription(__('Plugin restablecido', false));
                $this->Log->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre cuentas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
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

                $this->Log->setAction(__('Crear Cuenta', false));
                $this->Log->addDescription(__('Cuenta creada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getAccountName());
                break;
            case ActionsInterface::ACTION_ACC_EDIT:
                $Account->updateAccount();
                $this->updateCustomFieldData();

                $this->Log->setAction(__('Actualizar Cuenta', false));
                $this->Log->addDescription(__('Cuenta actualizada', false));
                $this->Log->addDetails(__('Nombre', false), $Form->getItemData()->getAccountName());
                break;
            case ActionsInterface::ACTION_ACC_EDIT_PASS:
                $Account->updateAccountPass();

                $this->Log->setAction(__('Actualizar Cuenta', false));
                $this->Log->addDescription(__('Clave actualizada', false));
                $this->Log->addDetails(__('Nombre', false), ''); // FIXME: nombre de cuenta?
                break;
            case ActionsInterface::ACTION_ACC_EDIT_RESTORE:
                $Account->restoreFromHistory(Request::analyze('accountHistoryId', 0));

                $this->Log->setAction(__('Restaurar Cuenta', false));
                $this->Log->addDescription(__('Cuenta restaurada', false));
                $this->Log->addDetails(__('Nombre', false), ''); // FIXME: nombre de cuenta?
                break;
            case ActionsInterface::ACTION_ACC_DELETE:
            case ActionsInterface::ACTION_MGM_ACCOUNTS_DELETE:
                $Account->deleteAccount($this->itemId);

                $this->Log->setAction(__('Eliminar Cuenta', false));
                $this->Log->addDescription(__('Cuenta eliminada', false));
                $this->Log->addDetails(__('Nombre', false), ''); // FIXME: nombre de cuenta?
                break;
        }

        Email::sendEmail($this->Log);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre cuentas favoritas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function favoriteAction()
    {
        $userId = Session::getUserData()->getUserId();

        switch ($this->actionId) {
            case ActionsInterface::ACTION_ACC_FAVORITES_ADD:
                AccountFavorites::addFavorite($this->itemId, $userId);

                $this->JsonResponse->setDescription(__('Favorito añadido'));
                break;
            case ActionsInterface::ACTION_ACC_FAVORITES_DELETE:
                AccountFavorites::deleteFavorite($this->itemId, $userId);

                $this->JsonResponse->setDescription(__('Favorito eliminado'));
                break;
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Importar usuarios de LDAP
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function ldapImportAction()
    {
        if (UserLdapSync::run()) {
            $this->JsonResponse->setStatus(0);
            $this->Log->addDescription(__('Importación de usuarios de LDAP realizada', false));
            $this->Log->addDetails(__('Usuarios importados', false), sprintf('%d/%d', UserLdapSync::$syncedObjects, UserLdapSync::$totalObjects));
            $this->Log->addDetails(__('Errores', false), UserLdapSync::$errorObjects);
        } else {
            $this->Log->addDescription(__('Error al importar usuarios de LDAP', false));
        }

        $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
    }

    /**
     * Acciones sobre notificaciones
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function noticeAction()
    {
        switch ($this->actionId) {
            case ActionsInterface::ACTION_NOT_USER_CHECK:
                Notice::getItem()->setChecked($this->itemId);

                $this->JsonResponse->setDescription(__('Notificación leída'));
                break;
            case ActionsInterface::ACTION_NOT_USER_DELETE:
                Notice::getItem()->delete($this->itemId);

                $this->JsonResponse->setDescription(__('Notificación eliminada'));
                break;
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones para peticiones sobre cuentas
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    protected function requestAccountAction()
    {
        $description = Request::analyze('description');

        if (!$description) {
            $this->JsonResponse->setDescription(__('Es necesaria una descripción', false));
            return;
        }

        $account = AccountUtil::getAccountRequestData($this->itemId);

        if ($account->account_userId === $account->account_userEditId) {
            $users = [$account->account_userId];
        } else {
            $users = [$account->account_userId, $account->account_userEditId];
        }

        $requestUsername = Session::getUserData()->getUserName();
        $requestLogin = Session::getUserData()->getUserLogin();

        $this->Log->setAction(__('Solicitud de Modificación de Cuenta', false));
        $this->Log->addDetails(__('Solicitante', false), sprintf('%s (%s)', $requestUsername, $requestLogin));
        $this->Log->addDetails(__('Cuenta', false), $account->account_name);
        $this->Log->addDetails(__('Cliente', false), $account->customer_name);
        $this->Log->addDetails(__('Descripción', false), $description);

        // Enviar por correo si está disponible
        if (Checks::mailrequestIsEnabled()) {
            $recipients = [];

            foreach ($users as $user) {
                $recipients[] = UserUtil::getUserEmail($user);
            }

            $mailto = implode(',', $recipients);

            if (strlen($mailto) > 1
                && Email::sendEmail($this->Log, $mailto)
            ) {
                $this->Log->addDescription(__('Solicitud enviada por correo', false));
            } else {
                $this->Log->addDescription(__('Solicitud no enviada por correo', false));
            }
        }

        // Crear notificaciones
        foreach ($users as $user) {
            $NoticeData = new NoticeData();
            $NoticeData->setNoticeUserId($user);
            $NoticeData->setNoticeComponent('Accounts');
            $NoticeData->setNoticeType(__('Solicitud'));
            $NoticeData->setNoticeDescription(utf8_decode($this->Log->getDetails()));

            Notice::getItem($NoticeData)->add();
        }


        $this->Log->addDescription(__('Solicitud realizada', false));
        $this->JsonResponse->setStatus(0);
    }
}