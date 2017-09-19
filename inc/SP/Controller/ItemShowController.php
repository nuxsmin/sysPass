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

defined('APP_ROOT') || die();

use SP\Account\Account;
use SP\Account\AccountAcl;
use SP\Account\AccountHistory;
use SP\Mgmt\ApiTokens\ApiTokensUtil;
use SP\Core\ActionsInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\ItemException;
use SP\Core\Plugin\PluginUtil;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\AccountExtData;
use SP\DataModel\ApiTokenData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\DataModel\GroupData;
use SP\DataModel\ProfileData;
use SP\DataModel\TagData;
use SP\DataModel\UserData;
use SP\Http\Request;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\ApiTokens\ApiToken;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomFieldTypes;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Groups\GroupUsers;
use SP\Mgmt\Plugins\Plugin;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Profiles\ProfileUtil;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;
use SP\Util\ImageUtil;
use SP\Util\Json;

/**
 * Class AccItemMgmt
 *
 * @package SP\Controller
 */
class ItemShowController extends ControllerBase implements ActionsInterface, ItemControllerInterface
{
    use RequestControllerTrait;

    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var int
     */
    private $module = 0;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->init();

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('itemId', $this->itemId);
        $this->view->assign('activeTab', $this->activeTab);
        $this->view->assign('actionId', $this->actionId);
        $this->view->assign('isView', false);
        $this->view->assign('showViewCustomPass', true);
        $this->view->assign('readonly', '');
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @param mixed $type Tipo de acción
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction($type = null)
    {
        try {
            switch ($this->actionId) {
                case self::ACTION_USR_USERS_VIEW:
                    $this->view->assign('header', __('Ver Usuario'));
                    $this->view->assign('isView', true);
                    $this->getUser();
                    break;
                case self::ACTION_USR_USERS_EDIT:
                    $this->view->assign('header', __('Editar Usuario'));
                    $this->getUser();
                    break;
                case self::ACTION_USR_USERS_EDITPASS:
                    $this->view->assign('header', __('Cambio de Clave'));
                    $this->getUserPass();
                    break;
                case self::ACTION_USR_USERS_NEW:
                    $this->view->assign('header', __('Nuevo Usuario'));
                    $this->getUser();
                    break;
                case self::ACTION_USR_GROUPS_VIEW:
                    $this->view->assign('header', __('Ver Grupo'));
                    $this->view->assign('isView', true);
                    $this->getGroup();
                    break;
                case self::ACTION_USR_GROUPS_EDIT:
                    $this->view->assign('header', __('Editar Grupo'));
                    $this->getGroup();
                    break;
                case self::ACTION_USR_GROUPS_NEW:
                    $this->view->assign('header', __('Nuevo Grupo'));
                    $this->getGroup();
                    break;
                case self::ACTION_USR_PROFILES_VIEW:
                    $this->view->assign('header', __('Ver Perfil'));
                    $this->view->assign('isView', true);
                    $this->getProfile();
                    break;
                case self::ACTION_USR_PROFILES_EDIT:
                    $this->view->assign('header', __('Editar Perfil'));
                    $this->getProfile();
                    break;
                case self::ACTION_USR_PROFILES_NEW:
                    $this->view->assign('header', __('Nuevo Perfil'));
                    $this->getProfile();
                    break;
                case self::ACTION_MGM_CUSTOMERS_VIEW:
                    $this->view->assign('header', __('Ver Cliente'));
                    $this->view->assign('isView', true);
                    $this->getCustomer();
                    break;
                case self::ACTION_MGM_CUSTOMERS_EDIT:
                    $this->view->assign('header', __('Editar Cliente'));
                    $this->getCustomer();
                    break;
                case self::ACTION_MGM_CUSTOMERS_NEW:
                    $this->view->assign('header', __('Nuevo Cliente'));
                    $this->getCustomer();
                    break;
                case self::ACTION_MGM_CATEGORIES_VIEW:
                    $this->view->assign('header', __('Ver Categoría'));
                    $this->view->assign('isView', true);
                    $this->getCategory();
                    break;
                case self::ACTION_MGM_CATEGORIES_EDIT:
                    $this->view->assign('header', __('Editar Categoría'));
                    $this->getCategory();
                    break;
                case self::ACTION_MGM_CATEGORIES_NEW:
                    $this->view->assign('header', __('Nueva Categoría'));
                    $this->getCategory();
                    break;
                case self::ACTION_MGM_APITOKENS_VIEW:
                    $this->view->assign('header', __('Ver Autorización'));
                    $this->view->assign('isView', true);
                    $this->getToken();
                    break;
                case self::ACTION_MGM_APITOKENS_NEW:
                    $this->view->assign('header', __('Nueva Autorización'));
                    $this->getToken();
                    break;
                case self::ACTION_MGM_APITOKENS_EDIT:
                    $this->view->assign('header', __('Editar Autorización'));
                    $this->getToken();
                    break;
                case self::ACTION_MGM_CUSTOMFIELDS_NEW:
                    $this->view->assign('header', __('Nuevo Campo'));
                    $this->getCustomField();
                    break;
                case self::ACTION_MGM_CUSTOMFIELDS_EDIT:
                    $this->view->assign('header', __('Editar Campo'));
                    $this->getCustomField();
                    break;
                case self::ACTION_MGM_PUBLICLINKS_VIEW:
                    $this->view->assign('header', __('Ver Enlace Público'));
                    $this->view->assign('isView', true);
                    $this->getPublicLink();
                    break;
                case self::ACTION_MGM_TAGS_NEW:
                    $this->view->assign('header', __('Nueva Etiqueta'));
                    $this->getTag();
                    break;
                case self::ACTION_MGM_TAGS_EDIT:
                    $this->view->assign('header', __('Editar Etiqueta'));
                    $this->getTag();
                    break;
                case self::ACTION_ACC_VIEW_PASS:
                    $this->view->assign('header', __('Clave de Cuenta'));
                    $this->getAccountPass();
                    break;
                case self::ACTION_MGM_PLUGINS_VIEW:
                    $this->view->assign('header', __('Detalles de Plugin'));
                    $this->view->assign('isView', true);
                    $this->getPlugin();
                    break;
                default:
                    $this->invalidAction();
            }

            if (count($this->JsonResponse->getData()) === 0) {
                $this->JsonResponse->setData(['html' => $this->render()]);
            }
        } catch (\Exception $e) {
            $this->JsonResponse->setDescription($e->getMessage());
        }

        $this->JsonResponse->setCsrf($this->view->sk);

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Obtener los datos para la ficha de usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getUser()
    {
        $this->module = self::ACTION_USR_USERS;
        $this->view->addTemplate('users');

        $this->view->assign('user', $this->itemId ? User::getItem()->getById($this->itemId) : new UserData());
        $this->view->assign('isDisabled', $this->view->actionId === self::ACTION_USR_USERS_VIEW ? 'disabled' : '');
        $this->view->assign('isReadonly', $this->view->isDisabled ? 'readonly' : '');
        $this->view->assign('groups', Group::getItem()->getItemsForSelect());
        $this->view->assign('profiles', Profile::getItem()->getItemsForSelect());

        $this->getCustomFieldsForItem();

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function getCustomFieldsForItem()
    {
        $this->view->assign('customFields', CustomField::getItem(new CustomFieldData($this->module))->getById($this->itemId));
    }

    /**
     * Inicializar la vista de cambio de clave de usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getUserPass()
    {
        $this->module = self::ACTION_USR_USERS;
        $this->setAction(self::ACTION_USR_USERS_EDITPASS);

        // Comprobar si el usuario a modificar es distinto al de la sesión
        if ($this->itemId !== Session::getUserData()->getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->assign('user', User::getItem()->getById($this->itemId));
        $this->view->addTemplate('userspass');

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de grupo
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getGroup()
    {
        $this->module = self::ACTION_USR_GROUPS;
        $this->view->addTemplate('groups');

        $this->view->assign('group', $this->itemId ? Group::getItem()->getById($this->itemId) : new GroupData());
        $this->view->assign('users', User::getItem()->getItemsForSelect());
        $this->view->assign('groupUsers', GroupUsers::getItem()->getById($this->itemId));

        $this->getCustomFieldsForItem();

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de perfil
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getProfile()
    {
        $this->module = self::ACTION_USR_PROFILES;
        $this->view->addTemplate('profiles');

        $Profile = $this->itemId ? Profile::getItem()->getById($this->itemId) : new ProfileData();

        $this->view->assign('profile', $Profile);
        $this->view->assign('isDisabled', ($this->view->actionId === self::ACTION_USR_PROFILES_VIEW) ? 'disabled' : '');
        $this->view->assign('isReadonly', $this->view->isDisabled ? 'readonly' : '');

        if ($this->view->isView === true) {
            $this->view->assign('usedBy', ProfileUtil::getProfileInUsersName($this->itemId));
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de cliente
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getCustomer()
    {
        $this->module = self::ACTION_MGM_CUSTOMERS;
        $this->view->addTemplate('customers');

        $this->view->assign('customer', $this->itemId ? Customer::getItem()->getById($this->itemId) : new CustomerData());
        $this->getCustomFieldsForItem();

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de categoría
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getCategory()
    {
        $this->module = self::ACTION_MGM_CATEGORIES;
        $this->view->addTemplate('categories');

        $this->view->assign('category', $this->itemId ? Category::getItem()->getById($this->itemId) : new CategoryData());
        $this->getCustomFieldsForItem();

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de tokens de API
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    protected function getToken()
    {
        $this->module = self::ACTION_MGM_APITOKENS;
        $this->view->addTemplate('tokens');

        $ApiTokenData = $this->itemId ? ApiToken::getItem()->getById($this->itemId) : new ApiTokenData();

        $this->view->assign('users', User::getItem()->getItemsForSelect());
        $this->view->assign('actions', ApiTokensUtil::getTokenActions());
        $this->view->assign('ApiTokenData', $ApiTokenData);
        $this->view->assign('isDisabled', ($this->view->actionId === self::ACTION_MGM_APITOKENS_VIEW) ? 'disabled' : '');
        $this->view->assign('isReadonly', $this->view->isDisabled ? 'readonly' : '');

        if ($this->view->isView === true) {
            $Log = Log::newLog(__('Autorizaciones', false));
            $LogMessage = $Log->getLogMessage();
            $LogMessage->addDescription(__('Token de autorización visualizado'));
            $LogMessage->addDetails(__('Usuario'), UserUtil::getUserLoginById($ApiTokenData->authtoken_userId));
            $Log->writeLog();

            Email::sendEmail($LogMessage);
        }

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de campo personalizado
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getCustomField()
    {
        $this->module = self::ACTION_MGM_CUSTOMFIELDS;
        $this->view->addTemplate('customfields');

        $customField = $this->itemId ? CustomFieldDef::getItem()->getById($this->itemId) : new CustomFieldDefData();

        $this->view->assign('field', $customField);
        $this->view->assign('types', CustomFieldTypes::getFieldsTypes());
        $this->view->assign('modules', CustomFieldTypes::getFieldsModules());

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de enlace público
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getPublicLink()
    {
        $this->module = self::ACTION_MGM_PUBLICLINKS;
        $this->view->addTemplate('publiclinks');

        $PublicLink = PublicLink::getItem();

        $this->view->assign('link', $PublicLink->getItemForList($PublicLink->getById($this->itemId)));

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la ficha de categoría
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getTag()
    {
        $this->module = self::ACTION_MGM_TAGS;
        $this->view->addTemplate('tags');

        $this->view->assign('tag', $this->itemId ? Tag::getItem()->getById($this->itemId) : new TagData());

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Mostrar la clave de una cuenta
     *
     * @throws ItemException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function getAccountPass()
    {
        $this->setAction(self::ACTION_ACC_VIEW_PASS);

        $isHistory = Request::analyze('isHistory', false);
        $isFull = Request::analyze('isFull', false);

        $AccountData = new AccountExtData();

        if (!$isHistory) {
            $AccountData->setAccountId($this->itemId);
            $Account = new Account($AccountData);
        } else {
            $Account = new AccountHistory($AccountData);
            $Account->setId($this->itemId);
        }

        $Account->getAccountPassData();

        if ($isHistory && !$Account->checkAccountMPass()) {
            throw new ItemException(__('La clave maestra no coincide', false));
        }

        $AccountAcl = new AccountAcl($Account, ActionsInterface::ACTION_ACC_VIEW_PASS);
        $Acl = $AccountAcl->getAcl();

        if (!$Acl->isShowViewPass()) {
            throw new ItemException(__('No tiene permisos para acceder a esta cuenta', false));
        }

        if (!UserPass::checkUserUpdateMPass(Session::getUserData()->getUserId())) {
            throw new ItemException(__('Clave maestra actualizada') . '<br>' . __('Reinicie la sesión para cambiarla'));
        }

        $key = CryptSession::getSessionKey();
        $securedKey = Crypt::unlockSecuredKey($AccountData->getAccountKey(), $key);
        $accountClearPass = Crypt::decrypt($AccountData->getAccountPass(), $securedKey, $key);

        if (!$isHistory) {
            $Account->incrementDecryptCounter();

            $Log = new Log();
            $LogMessage = $Log->getLogMessage();
            $LogMessage->setAction(__('Ver Clave', false));
            $LogMessage->addDetails(__('ID', false), $this->itemId);
            $LogMessage->addDetails(__('Cuenta', false), $AccountData->getCustomerName() . ' / ' . $AccountData->getAccountName());
            $Log->writeLog();
        }

        $useImage = (int)Checks::accountPassToImageIsEnabled();

        if (!$useImage) {
            $pass = $isFull ? htmlentities(trim($accountClearPass)) : trim($accountClearPass);
        } else {
            $pass = ImageUtil::convertText($accountClearPass);
        }

        $this->JsonResponse->setStatus(0);

        if ($isFull) {
            $this->view->addTemplate('viewpass', 'account');

            $this->view->assign('login', $AccountData->getAccountLogin());
            $this->view->assign('pass', $pass);
            $this->view->assign('isImage', $useImage);
            $this->view->assign('isLinked', Request::analyze('isLinked', 0));

            return;
        }

        $data = [
            'acclogin' => $AccountData->getAccountLogin(),
            'accpass' => $pass,
            'useimage' => $useImage
        ];

        $this->JsonResponse->setData($data);
    }

    /**
     * Obtener los datos para la vista de plugins
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getPlugin()
    {
        $this->module = self::ACTION_MGM_PLUGINS;
        $this->view->addTemplate('plugins');

        $Plugin = Plugin::getItem()->getById($this->itemId);

        $this->view->assign('isReadonly', $this->view->isView ? 'readonly' : '');
        $this->view->assign('plugin', $Plugin);
        $this->view->assign('pluginInfo', PluginUtil::getPluginInfo($Plugin->getPluginName()));

        $this->JsonResponse->setStatus(0);
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function getAccountFiles()
    {
        $this->setAction(self::ACTION_ACC_FILES);

        $this->view->assign('accountId', Request::analyze('id', 0));
        $this->view->assign('deleteEnabled', Request::analyze('del', 0));
        $this->view->assign('files', FileUtil::getAccountFiles($this->view->accountId));

        if (!is_array($this->view->files) || count($this->view->files) === 0) {
            return;
        }

        $this->view->addTemplate('files');

        $this->JsonResponse->setStatus(0);
    }
}