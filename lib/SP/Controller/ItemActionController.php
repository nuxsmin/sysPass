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
use SP\Account\AccountHistory;
use SP\Account\AccountHistoryUtil;
use SP\Account\AccountUtil;
use SP\Auth\AuthUtil;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Messages\LogMessage;
use SP\Core\SessionFactory;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\CustomFieldData;
use SP\DataModel\NoticeData;
use SP\DataModel\PluginData;
use SP\DataModel\PublicLinkData;
use SP\Forms\AccountForm;
use SP\Forms\ApiTokenForm;
use SP\Forms\CategoryForm;
use SP\Forms\CustomerForm;
use SP\Forms\CustomFieldDefForm;
use SP\Forms\NoticeForm;
use SP\Forms\TagForm;
use SP\Forms\UserForm;
use SP\Forms\UserGroupForm;
use SP\Forms\UserProfileForm;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\ApiTokens\ApiToken;
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
use SP\Mgmt\Users\UserLdap;
use SP\Mgmt\Users\UserLdapSync;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class AjaxSaveController
 *
 * @package SP\Controller
 */
class ItemActionController implements ItemControllerInterface
{
    use InjectableTrait;
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
        $this->injectDependencies();
        $this->init();
    }

    /**
     * Ejecutar la acción solicitada
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        $this->LogMessage = new LogMessage();

        try {
            switch ($this->actionId) {
                case ActionsInterface::USER_CREATE:
                case ActionsInterface::USER_EDIT:
                case ActionsInterface::USER_EDIT_PASS:
                case ActionsInterface::USER_DELETE:
                    $this->userAction();
                    break;
                case ActionsInterface::GROUP_CREATE:
                case ActionsInterface::GROUP_EDIT:
                case ActionsInterface::GROUP_DELETE:
                    $this->groupAction();
                    break;
                case ActionsInterface::PROFILE_CREATE:
                case ActionsInterface::PROFILE_EDIT:
                case ActionsInterface::PROFILE_DELETE:
                    $this->profileAction();
                    break;
                case ActionsInterface::CLIENT_CREATE:
                case ActionsInterface::CLIENT_EDIT:
                case ActionsInterface::CLIENT_DELETE:
                    $this->customerAction();
                    break;
                case ActionsInterface::CATEGORY_CREATE:
                case ActionsInterface::CATEGORY_EDIT:
                case ActionsInterface::CATEGORY_DELETE:
                    $this->categoryAction();
                    break;
                case ActionsInterface::APITOKEN_CREATE:
                case ActionsInterface::APITOKEN_EDIT:
                case ActionsInterface::APITOKEN_DELETE:
                    $this->tokenAction();
                    break;
                case ActionsInterface::CUSTOMFIELD_CREATE:
                case ActionsInterface::CUSTOMFIELD_EDIT:
                case ActionsInterface::CUSTOMFIELD_DELETE:
                    $this->customFieldAction();
                    break;
                case ActionsInterface::PUBLICLINK_CREATE:
                case ActionsInterface::PUBLICLINK_DELETE:
                case ActionsInterface::PUBLICLINK_REFRESH:
                    $this->publicLinkAction();
                    break;
                case ActionsInterface::TAG_CREATE:
                case ActionsInterface::TAG_EDIT:
                case ActionsInterface::TAG_DELETE:
                    $this->tagAction();
                    break;
                case ActionsInterface::FILE_DELETE:
                    $this->fileAction();
                    break;
                case ActionsInterface::PLUGIN_ENABLE:
                case ActionsInterface::PLUGIN_DISABLE:
                case ActionsInterface::PLUGIN_RESET:
                    $this->pluginAction();
                    break;
                case ActionsInterface::ACCOUNT_CREATE:
                case ActionsInterface::ACCOUNT_COPY:
                case ActionsInterface::ACCOUNT_EDIT:
                case ActionsInterface::ACCOUNT_EDIT_PASS:
                case ActionsInterface::ACCOUNT_EDIT_RESTORE:
                case ActionsInterface::ACCOUNT_DELETE:
                case ActionsInterface::ACCOUNTMGR_DELETE:
                    $this->accountAction();
                    break;
                case ActionsInterface::ACCOUNTMGR_DELETE_HISTORY:
                    $this->accountHistoryAction();
                    break;
                case ActionsInterface::ACCOUNT_FAVORITE_ADD:
                case ActionsInterface::ACCOUNT_FAVORITE_DELETE:
                    $this->favoriteAction();
                    break;
                case ActionsInterface::LDAP_SYNC:
                    $this->ldapImportAction();
                    break;
                case ActionsInterface::NOTICE_USER_CHECK:
                case ActionsInterface::NOTICE_USER_VIEW:
                case ActionsInterface::NOTICE_USER_CREATE:
                case ActionsInterface::NOTICE_USER_EDIT:
                case ActionsInterface::NOTICE_USER_DELETE:
                    $this->noticeAction();
                    break;
                case ActionsInterface::ACCOUNT_REQUEST:
                    $this->requestAccountAction();
                    break;
                default:
                    $this->invalidAction();
            }
        } catch (\Exception $e) {
            $this->JsonResponse->setDescription($e->getMessage());
        }

        if ($this->LogMessage->getAction() !== null) {
            $Log = new Log($this->LogMessage);
            $Log->writeLog();

            $this->JsonResponse->setDescription($this->LogMessage->getHtmlDescription(true));
        }

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Acciones sobre usuarios
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    protected function userAction()
    {
        $Form = new UserForm($this->itemId);
        $Form->setIsLdap(Request::analyze('isLdap', 0));
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::USER);

        switch ($this->actionId) {
            case ActionsInterface::USER_CREATE:
                User::getItem($Form->getItemData())->add();

                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Usuario', false));
                $this->LogMessage->addDescription(__('Usuario creado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUserName());
                $this->LogMessage->addDetails(__('Login', false), $Form->getItemData()->getUserLogin());

                if ($Form->getItemData()->isUserIsChangePass()
                    && !AuthUtil::mailPassRecover($Form->getItemData())
                ) {
                    $this->LogMessage->addDescription(__('No se pudo realizar la petición de cambio de clave.', false));
                }
                break;
            case ActionsInterface::USER_EDIT:
                if ($Form->getIsLdap()) {
                    UserLdap::getItem($Form->getItemData())->update();
                } else {
                    User::getItem($Form->getItemData())->update();
                }

                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Usuario', false));
                $this->LogMessage->addDescription(__('Usuario actualizado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUserName());
                $this->LogMessage->addDetails(__('Login', false), $Form->getItemData()->getUserLogin());
                break;
            case ActionsInterface::USER_DELETE:
                if (is_array($this->itemId)) {
                    $UsersData = User::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Usuarios eliminados', false));
                } else {
                    $UsersData = [User::getItem()->getById($this->itemId)];

                    User::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Usuario eliminado', false));
                }

                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Usuario', false));

                foreach ($UsersData as $UserData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $UserData->getUserName());
                    $this->LogMessage->addDetails(__('Login', false), $UserData->getUserLogin());
                }
                break;
            case ActionsInterface::USER_EDIT_PASS:
                $UserData = User::getItem()->getById($this->itemId);

                User::getItem($Form->getItemData())->updatePass();

                $this->LogMessage->setAction(__('Actualizar Clave Usuario', false));
                $this->LogMessage->addDescription(__('Clave actualizada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $UserData->getUserName());
                $this->LogMessage->addDetails(__('Login', false), $UserData->getUserLogin());
                break;
        }

        Email::sendEmail($this->LogMessage);

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
        if (is_array($this->itemId)) {
            CustomField::getItem($this->CustomFieldData)->deleteBatch($this->itemId);
        } else {
            CustomField::getItem($this->CustomFieldData)->delete($this->itemId);
        }
    }

    /**
     * Acciones sobre grupos
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function groupAction()
    {
        $Form = new UserGroupForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::GROUP);

        switch ($this->actionId) {
            case ActionsInterface::GROUP_CREATE:
                Group::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Grupo', false));
                $this->LogMessage->addDescription(__('Grupo creado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUsergroupName());
                break;
            case ActionsInterface::GROUP_EDIT:
                Group::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Grupo', false));
                $this->LogMessage->addDescription(__('Grupo actualizado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUsergroupName());
                break;
            case ActionsInterface::GROUP_DELETE:
                if (is_array($this->itemId)) {
                    $GroupsData = Group::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Grupos eliminados', false));
                } else {
                    $GroupsData = [Group::getItem()->getById($this->itemId)];

                    Group::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Grupo eliminado', false));
                }

                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Grupo', false));

                foreach ($GroupsData as $GroupData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $GroupData->getUsergroupName());
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre perfiles
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     */
    protected function profileAction()
    {
        $Form = new UserProfileForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::PROFILE);

        switch ($this->actionId) {
            case ActionsInterface::PROFILE_CREATE:
                Profile::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Perfil', false));
                $this->LogMessage->addDescription(__('Perfil creado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUserprofileName());
                break;
            case ActionsInterface::PROFILE_EDIT:
                Profile::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Perfil', false));
                $this->LogMessage->addDescription(__('Perfil actualizado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getUserprofileName());
                break;
            case ActionsInterface::PROFILE_DELETE:
                if (is_array($this->itemId)) {
                    $ProfilesData = Profile::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Perfiles eliminados', false));
                } else {
                    $ProfilesData = [Profile::getItem()->getById($this->itemId)];

                    Profile::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Perfil eliminado', false));
                }

                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Perfil', false));

                foreach ($ProfilesData as $ProfileData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $ProfileData->getUserprofileName());
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre clientes
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function customerAction()
    {
        $Form = new CustomerForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::CLIENT);

        switch ($this->actionId) {
            case ActionsInterface::CLIENT_CREATE:
                Customer::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Cliente', false));
                $this->LogMessage->addDescription(__('Cliente creado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getCustomerName());
                break;
            case ActionsInterface::CLIENT_EDIT:
                Customer::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Cliente', false));
                $this->LogMessage->addDescription(__('Cliente actualizado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getCustomerName());
                break;
            case ActionsInterface::CLIENT_DELETE:
                if (is_array($this->itemId)) {
                    $CustomersData = Customer::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Clientes eliminados', false));
                } else {
                    $CustomersData = [Customer::getItem()->getById($this->itemId)];

                    Customer::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Cliente eliminado', false));
                }

                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Cliente', false));

                foreach ($CustomersData as $CustomerData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $CustomerData->getCustomerName());
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre categorías
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function categoryAction()
    {
        $Form = new CategoryForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::CATEGORY);

        switch ($this->actionId) {
            case ActionsInterface::CATEGORY_CREATE:
                Category::getItem($Form->getItemData())->add();
                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Categoría', false));
                $this->LogMessage->addDescription(__('Categoría creada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getCategoryName());
                break;
            case ActionsInterface::CATEGORY_EDIT:
                Category::getItem($Form->getItemData())->update();
                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Categoría', false));
                $this->LogMessage->addDescription(__('Categoría actualizada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getCategoryName());
                break;
            case ActionsInterface::CATEGORY_DELETE:

                if (is_array($this->itemId)) {
                    $CategoriesData = Category::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Categorías eliminadas', false));
                } else {
                    $CategoriesData = [Category::getItem()->getById($this->itemId)];

                    Category::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Categoría eliminada', false));
                }

                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Categoría', false));

                foreach ($CategoriesData as $CategoryData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $CategoryData->getCategoryName());
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre tokens API
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function tokenAction()
    {
        $Form = new ApiTokenForm($this->itemId);

        $refresh = Request::analyze('refreshtoken', false, false, true);

        switch ($this->actionId) {
            case ActionsInterface::APITOKEN_CREATE:
                $Form->validate($this->actionId);

                if ($refresh === true) {
                    ApiToken::getItem($Form->getItemData())->refreshToken()->add();
                } else {
                    ApiToken::getItem($Form->getItemData())->add();
                }

                $this->LogMessage->setAction(__('Crear Autorización', false));
                $this->LogMessage->addDescription(__('Autorización creada', false));
                $this->LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($Form->getItemData()->getAuthtokenUserId()));
                break;
            case ActionsInterface::APITOKEN_EDIT:
                $Form->validate($this->actionId);

                if ($refresh === true) {
                    ApiToken::getItem($Form->getItemData())->refreshToken()->update();
                } else {
                    ApiToken::getItem($Form->getItemData())->update();
                }

                $this->LogMessage->setAction(__('Actualizar Autorización', false));
                $this->LogMessage->addDescription(__('Autorización actualizada', false));
                $this->LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($Form->getItemData()->getAuthtokenUserId()));
                break;
            case ActionsInterface::APITOKEN_DELETE:
                if (is_array($this->itemId)) {
                    ApiToken::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Autorizaciones eliminadas', false));
                } else {
                    ApiToken::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Autorización eliminada', false));
                }

                $this->LogMessage->setAction(__('Eliminar Autorización', false));
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre campos personalizados
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     */
    protected function customFieldAction()
    {
        $Form = new CustomFieldDefForm($this->itemId);
        $Form->validate($this->actionId);

        switch ($this->actionId) {
            case ActionsInterface::CUSTOMFIELD_CREATE:
                CustomFieldDef::getItem($Form->getItemData())->add();

                $this->LogMessage->setAction(__('Crear Campo', false));
                $this->LogMessage->addDescription(__('Campo creado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getName());
                break;
            case ActionsInterface::CUSTOMFIELD_EDIT:
                CustomFieldDef::getItem($Form->getItemData())->update();

                $this->LogMessage->setAction(__('Actualizar Campo', false));
                $this->LogMessage->addDescription(__('Campo actualizado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getName());
                break;
            case ActionsInterface::CUSTOMFIELD_DELETE:
                if (is_array($this->itemId)) {
                    CustomFieldDef::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Campos eliminados', false));
                } else {
                    CustomFieldDef::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Campo eliminado', false));
                }

                $this->LogMessage->setAction(__('Eliminar Campo', false));
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre enlaces públicos
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function publicLinkAction()
    {
        $PublicLinkData = new PublicLinkData();
        $PublicLinkData->setPublicLinkItemId($this->itemId);
        $PublicLinkData->setPublicLinkTypeId(PublicLink::TYPE_ACCOUNT);
        $PublicLinkData->setPublicLinkNotify(Request::analyze('notify', false, false, true));

        switch ($this->actionId) {
            case ActionsInterface::PUBLICLINK_CREATE:
                $PublicLinkData->setItemId($this->itemId);
                PublicLink::getItem($PublicLinkData)->add();

                $this->LogMessage->setAction(__('Crear Enlace', false));
                $this->LogMessage->addDescription(__('Enlace creado', false));
                $this->LogMessage->addDetails(__('Tipo', false), $PublicLinkData->getPublicLinkTypeId());
                $this->LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                $this->LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getPublicLinkUserId()));
                break;
            case ActionsInterface::PUBLICLINK_REFRESH:
                $PublicLinkData = PublicLink::getItem()->getById($this->itemId);
                PublicLink::getItem($PublicLinkData)->refresh();

                $this->LogMessage->setAction(__('Actualizar Enlace', false));
                $this->LogMessage->addDescription(__('Enlace actualizado', false));
                $this->LogMessage->addDetails(__('Tipo', false), $PublicLinkData->getPublicLinkTypeId());
                $this->LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                $this->LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getPublicLinkUserId()));
                break;
            case ActionsInterface::PUBLICLINK_DELETE:
                if (is_array($this->itemId)) {
                    PublicLink::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Enlaces eliminados', false));
                } else {
                    $PublicLinkData = PublicLink::getItem()->getById($this->itemId);

                    PublicLink::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Enlace eliminado', false));
                    $this->LogMessage->addDetails(__('Tipo', false), $PublicLinkData->getPublicLinkTypeId());
                    $this->LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
                    $this->LogMessage->addDetails(__('Usuario', false), UserUtil::getUserLoginById($PublicLinkData->getPublicLinkUserId()));
                }

                $this->LogMessage->setAction(__('Eliminar Enlace', false));
                break;
        }

        Email::sendEmail($this->LogMessage);

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
            case ActionsInterface::TAG_CREATE:
                Tag::getItem($Form->getItemData())->add();

                $this->LogMessage->setAction(__('Crear Etiqueta', false));
                $this->LogMessage->addDescription(__('Etiqueta creada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getTagName());
                break;
            case ActionsInterface::TAG_EDIT:
                Tag::getItem($Form->getItemData())->update();

                $this->LogMessage->setAction(__('Actualizar Etiqueta', false));
                $this->LogMessage->addDescription(__('Etiqueta actualizada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getTagName());
                break;
            case ActionsInterface::TAG_DELETE:
                if (is_array($this->itemId)) {
                    $TagsData = Tag::getItem()->deleteBatch($this->itemId);

                    $this->LogMessage->addDescription(__('Etiquetas eliminadas', false));
                } else {
                    $TagsData = [Tag::getItem()->getById($this->itemId)];

                    Tag::getItem()->delete($this->itemId);

                    $this->LogMessage->addDescription(__('Etiqueta eliminada', false));
                }

                $this->LogMessage->setAction(__('Eliminar Etiqueta', false));

                foreach ($TagsData as $TagData) {
                    $this->LogMessage->addDetails(__('Nombre', false), $TagData->getTagName());
                }
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
     * @throws phpmailerException
     */
    protected function fileAction()
    {
        if (is_array($this->itemId)) {
            $FilesData = File::getItem()->deleteBatch($this->itemId);

            $this->LogMessage->addDescription(__('Archivos eliminados', false));
        } else {
            $FilesData = [File::getItem()->getById($this->itemId)];

            File::getItem()->delete($this->itemId);

            $this->LogMessage->addDescription(__('Archivo eliminado', false));
        }

        $this->LogMessage->setAction(__('Eliminar Archivo', false));

        foreach ($FilesData as $FileData) {
            $this->LogMessage->addDetails(__('Cuenta', false), $FileData->getAccountName());
            $this->LogMessage->addDetails(__('Cliente', false), $FileData->getCustomerName());
            $this->LogMessage->addDetails(__('Archivo', false), $FileData->getAccfileName());
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre plugins
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     */
    protected function pluginAction()
    {
        $PluginData = new PluginData();
        $PluginData->setPluginId($this->itemId);

        switch ($this->actionId) {
            case ActionsInterface::PLUGIN_ENABLE:
                $PluginData->setPluginEnabled(1);
                Plugin::getItem($PluginData)->toggleEnabled();

                $this->LogMessage->setAction(__('Actualizar Plugin', false));
                $this->LogMessage->addDescription(__('Plugin habilitado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
            case ActionsInterface::PLUGIN_DISABLE:
                $PluginData->setPluginEnabled(0);
                Plugin::getItem($PluginData)->toggleEnabled();

                $this->LogMessage->setAction(__('Actualizar Plugin', false));
                $this->LogMessage->addDescription(__('Plugin deshabilitado', false));
                $this->LogMessage->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
            case ActionsInterface::PLUGIN_RESET:
                Plugin::getItem()->reset($this->itemId);

                $this->LogMessage->setAction(__('Actualizar Plugin', false));
                $this->LogMessage->addDescription(__('Plugin restablecido', false));
                $this->LogMessage->addDetails(__('Nombre', false), $PluginData->getPluginName());
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones sobre cuentas
     *
     * @throws \SP\Core\Exceptions\ValidationException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function accountAction()
    {
        $Form = new AccountForm($this->itemId);
        $Form->validate($this->actionId);

        $this->setCustomFieldData(ActionsInterface::ACCOUNT);

        $Account = new Account($Form->getItemData());

        switch ($this->actionId) {
            case ActionsInterface::ACCOUNT_CREATE:
            case ActionsInterface::ACCOUNT_COPY:
                $Form->getItemData()->setAccountUserId(SessionFactory::getUserData()->getUserId());

                $Account->createAccount();

                $this->CustomFieldData->setId($Account->getAccountData()->getId());

                $this->addCustomFieldData();

                $this->LogMessage->setAction(__('Crear Cuenta', false));
                $this->LogMessage->addDescription(__('Cuenta creada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getAccountName());

                $this->JsonResponse->setData(['itemId' => $Account->getAccountData()->getId(), 'nextActionId' => ActionsInterface::ACCOUNT_EDIT]);
                break;
            case ActionsInterface::ACCOUNT_EDIT:
                $Account->updateAccount();
                $this->updateCustomFieldData();

                $this->LogMessage->setAction(__('Actualizar Cuenta', false));
                $this->LogMessage->addDescription(__('Cuenta actualizada', false));
                $this->LogMessage->addDetails(__('Nombre', false), $Form->getItemData()->getAccountName());

                $this->JsonResponse->setData(['itemId' => $this->itemId, 'nextActionId' => ActionsInterface::ACCOUNT_VIEW]);
                break;
            case ActionsInterface::ACCOUNT_EDIT_PASS:
                $Account->updateAccountPass();

                $this->LogMessage->setAction(__('Actualizar Cuenta', false));
                $this->LogMessage->addDescription(__('Clave actualizada', false));
                $this->LogMessage->addDetails(__('Nombre', false), AccountUtil::getAccountNameById($this->itemId));

                $this->JsonResponse->setData(['itemId' => $this->itemId, 'nextActionId' => ActionsInterface::ACCOUNT_VIEW]);
                break;
            case ActionsInterface::ACCOUNT_EDIT_RESTORE:
                $Account->restoreFromHistory(Request::analyze('accountHistoryId', 0));

                $this->LogMessage->setAction(__('Restaurar Cuenta', false));
                $this->LogMessage->addDescription(__('Cuenta restaurada', false));
                $this->LogMessage->addDetails(__('Nombre', false), AccountUtil::getAccountNameById($this->itemId));

                $this->JsonResponse->setData(['itemId' => $this->itemId, 'nextActionId' => ActionsInterface::ACCOUNT_VIEW]);
                break;
            case ActionsInterface::ACCOUNT_DELETE:
            case ActionsInterface::ACCOUNTMGR_DELETE:
                if (is_array($this->itemId)) {
                    $accounts = AccountUtil::getAccountNameByIdBatch($this->itemId);
                    $numAccounts = count($accounts);
                } else {
                    $accounts = AccountUtil::getAccountNameById($this->itemId);
                    $numAccounts = 1;
                }

                $Account->deleteAccount($this->itemId);
                $this->deleteCustomFieldData();

                $this->LogMessage->setAction(__('Eliminar Cuenta', false));

                if ($numAccounts > 1) {
                    $this->LogMessage->addDescription(__('Cuentas eliminadas', false));

                    foreach ($accounts as $account) {
                        $this->LogMessage->addDetails(__('Nombre', false), $account->account_name);
                    }
                } elseif ($numAccounts === 1) {
                    $this->LogMessage->addDescription(__('Cuenta eliminada', false));
                    $this->LogMessage->addDetails(__('Nombre', false), $accounts);
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acción para eliminar una cuenta del historial
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function accountHistoryAction()
    {
        $Account = new AccountHistory();

        switch ($this->actionId) {
            case ActionsInterface::ACCOUNTMGR_RESTORE:
                AccountHistoryUtil::restoreFromHistory($this->itemId, Request::analyze('accountId', 0));

                $this->LogMessage->setAction(__('Restaurar Cuenta', false));
                $this->LogMessage->addDescription(__('Cuenta restaurada', false));
                $this->LogMessage->addDetails(__('Nombre', false), AccountUtil::getAccountNameById($this->itemId));

                $this->JsonResponse->setData(['itemId' => $this->itemId, 'nextActionId' => ActionsInterface::ACCOUNT_VIEW]);
                break;
            case ActionsInterface::ACCOUNTMGR_DELETE_HISTORY:
                if (is_array($this->itemId)) {
                    $accounts = AccountHistoryUtil::getAccountNameByIdBatch($this->itemId);
                    $numAccounts = count($accounts);
                } else {
                    $accounts = AccountHistoryUtil::getAccountNameById($this->itemId);
                    $numAccounts = 1;
                }

                $Account->deleteAccount($this->itemId);

                $this->LogMessage->setAction(__('Eliminar Cuenta (H)', false));

                if ($numAccounts > 1) {
                    $this->LogMessage->addDescription(__('Cuentas eliminadas', false));

                    foreach ($accounts as $account) {
                        $this->LogMessage->addDetails(__('Nombre', false), $account->acchistory_name);
                    }
                } elseif ($numAccounts === 1) {
                    $this->LogMessage->addDescription(__('Cuenta eliminada', false));
                    $this->LogMessage->addDetails(__('Nombre', false), $accounts->acchistory_name);
                }
                break;
        }

        Email::sendEmail($this->LogMessage);

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
        $userId = SessionFactory::getUserData()->getUserId();

        switch ($this->actionId) {
            case ActionsInterface::ACCOUNT_FAVORITE_ADD:
                AccountFavorites::addFavorite($this->itemId, $userId);

                $this->JsonResponse->setDescription(__('Favorito añadido'));
                break;
            case ActionsInterface::ACCOUNT_FAVORITE_DELETE:
                AccountFavorites::deleteFavorite($this->itemId, $userId);

                $this->JsonResponse->setDescription(__('Favorito eliminado'));
                break;
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Importar usuarios de LDAP
     *
     * @throws phpmailerException
     */
    protected function ldapImportAction()
    {
        $this->LogMessage->setAction(__('Importar usuarios de LDAP', false));

        $options = [
            'loginAttribute' => Request::analyze('ldap_loginattribute'),
            'nameAttribute' => Request::analyze('ldap_nameattribute'),
            'isADS' => Util::boolval(Request::analyze('ldap_ads'))
        ];

        if (UserLdapSync::run($options)) {
            $this->LogMessage->addDescription(__('Importación de usuarios de LDAP realizada', false));
            $this->LogMessage->addDetails(__('Usuarios importados', false), sprintf('%d/%d', UserLdapSync::$syncedObjects, UserLdapSync::$totalObjects));
            $this->LogMessage->addDetails(__('Errores', false), UserLdapSync::$errorObjects);

            $this->JsonResponse->setStatus(0);
        } else {
            $this->LogMessage->addDescription(__('Error al importar usuarios de LDAP', false));
        }

        $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
    }

    /**
     * Acciones sobre notificaciones
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ValidationException
     */
    protected function noticeAction()
    {
        switch ($this->actionId) {
            case ActionsInterface::NOTICE_USER_CHECK:
                Notice::getItem()->setChecked($this->itemId);

                $this->JsonResponse->setDescription(__('Notificación leída'));
                break;
            case ActionsInterface::NOTICE_USER_CREATE:
                $Form = new NoticeForm($this->itemId);
                $Form->validate($this->actionId);

                Notice::getItem($Form->getItemData())->add();

                $this->JsonResponse->setDescription(__('Notificación creada'));
                break;
            case ActionsInterface::NOTICE_USER_EDIT:
                $Form = new NoticeForm($this->itemId);
                $Form->validate($this->actionId);

                Notice::getItem($Form->getItemData())->update();

                $this->JsonResponse->setDescription(__('Notificación actualizada'));
                break;
            case ActionsInterface::NOTICE_USER_DELETE:
                if (is_array($this->itemId)) {
                    Notice::getItem()->deleteBatch($this->itemId);

                    $this->JsonResponse->setDescription(__('Notificaciones eliminadas'));
                } else {
                    Notice::getItem()->delete($this->itemId);

                    $this->JsonResponse->setDescription(__('Notificación eliminada'));
                }
                break;
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acciones para peticiones sobre cuentas
     *
     * @throws \SP\Core\Exceptions\SPException
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

        $requestUsername = SessionFactory::getUserData()->getUserName();
        $requestLogin = SessionFactory::getUserData()->getUserLogin();

        $this->LogMessage->setAction(__('Solicitud de Modificación de Cuenta', false));
        $this->LogMessage->addDetails(__('Solicitante', false), sprintf('%s (%s)', $requestUsername, $requestLogin));
        $this->LogMessage->addDetails(__('Cuenta', false), $account->account_name);
        $this->LogMessage->addDetails(__('Cliente', false), $account->customer_name);
        $this->LogMessage->addDetails(__('Descripción', false), $description);

        // Enviar por correo si está disponible
        if ($this->ConfigData->isMailRequestsEnabled()) {
            $recipients = [];

            foreach ($users as $user) {
                $recipients[] = UserUtil::getUserEmail($user);
            }

            $mailto = implode(',', $recipients);

            if (strlen($mailto) > 1
                && Email::sendEmail($this->LogMessage, $mailto)
            ) {
                $this->LogMessage->addDescription(__('Solicitud enviada por correo', false));
            } else {
                $this->LogMessage->addDescription(__('Solicitud no enviada por correo', false));
            }
        }

        // Crear notificaciones
        foreach ($users as $user) {
            $NoticeData = new NoticeData();
            $NoticeData->setNoticeUserId($user);
            $NoticeData->setNoticeComponent('Accounts');
            $NoticeData->setNoticeType(__('Solicitud'));
            $NoticeData->setNoticeDescription($this->LogMessage);

            Notice::getItem($NoticeData)->add();
        }

        $this->LogMessage->addDescription(__('Solicitud realizada', false));
        $this->JsonResponse->setStatus(0);
    }
}