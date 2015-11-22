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

use SP\Api\ApiTokens;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridIcon;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridTab;
use SP\Http\Request;
use SP\Mgmt\PublicLinkUtil;
use SP\Mgmt\CustomFields;
use SP\Mgmt\User\Groups;
use SP\Log\Log;
use SP\Mgmt\User\Profile;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Mgmt\User\UserUtil;
use SP\Storage\DBUtil;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de de preparar la presentación de las vistas de gestión de usuarios
 *
 * @package Controller
 */
class UsersMgmt extends Controller implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var int
     */
    private $_module = 0;
    /**
     * @var Icons
     */
    private $_icons;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $this->_icons = new Icons();
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     */
    public function getUsersList()
    {
        $this->setAction(self::ACTION_USR_USERS);

        if (!$this->checkAccess()) {
            return;
        }

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_USERS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Usuario'));
        $GridActionNew->setTitle(_('Nuevo Usuario'));
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_USERS_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_USERS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Detalles de Usuario'));
        $GridActionView->setTitle(_('Ver Detalles de Usuario'));
        $GridActionView->setIcon($this->_icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_USR_USERS_VIEW);
        $GridActionView->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_USERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Usuario'));
        $GridActionEdit->setTitle(_('Editar Usuario'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_USERS_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_USERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Usuario'));
        $GridActionDel->setTitle(_('Eliminar Usuario'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_USERS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridActionEditPass = new DataGridAction();
        $GridActionEditPass->setId(self::ACTION_USR_USERS_EDITPASS);
        $GridActionEditPass->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEditPass->setName(_('Cambiar Clave de Usuario'));
        $GridActionEditPass->setTitle(_('Cambiar Clave de Usuario'));
        $GridActionEditPass->setIcon(new DataGridIcon('lock_outline', 'imgs/pass.png', 'fg-orange80'));
        $GridActionEditPass->setOnClickFunction('sysPassUtil.Common.usrUpdPass');
        $GridActionEditPass->setOnClickArgs('this');
        $GridActionEditPass->setOnClickArgs(self::ACTION_USR_USERS_EDITPASS);
        $GridActionEditPass->setOnClickArgs($this->view->sk);
        $GridActionEditPass->setFilterRowSource('user_isLdap');

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Login'));
        $GridHeaders->addHeader(_('Perfil'));
        $GridHeaders->addHeader(_('Grupo'));
        $GridHeaders->addHeader(_('Propiedades'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('user_id');
        $GridData->addDataRowSource('user_name');
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('userprofile_name');
        $GridData->addDataRowSource('usergroup_name');
        $GridData->addDataRowSourceWithIcon('user_isAdminApp', new DataGridIcon('star', 'check_blue.png', null, _('Admin Cuentas')));
        $GridData->addDataRowSourceWithIcon('user_isAdminAcc', new DataGridIcon('star_half', 'check_orange.png', null, _('Admin Cuentas')));
        $GridData->addDataRowSourceWithIcon('user_isLdap', new DataGridIcon('business', 'ldap.png', null, _('Usuario de LDAP')));
        $GridData->addDataRowSourceWithIcon('user_isDisabled', new DataGridIcon('error', 'disabled.png', null, _('Deshabilitado')));
        $GridData->setData(UserUtil::getUsers());

        $Grid = new DataGridTab();
        $Grid->setId('tblUsers');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionEditPass);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridData->getDataCount(), !empty($search)));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Usuarios'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de grupos
     */
    public function getGroupsList()
    {
        $this->setAction(self::ACTION_USR_GROUPS);

        if (!$this->checkAccess()) {
            return;
        }

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_GROUPS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Grupo'));
        $GridActionNew->setTitle(_('Nuevo Grupo'));
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_GROUPS_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_GROUPS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Grupo'));
        $GridActionEdit->setTitle(_('Editar Grupo'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_GROUPS_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_GROUPS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Grupo'));
        $GridActionDel->setTitle(_('Eliminar Grupo'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_GROUPS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('usergroup_id');
        $GridData->addDataRowSource('usergroup_name');
        $GridData->addDataRowSource('usergroup_description');
        $GridData->setData(Groups::getGroups());

        $Grid = new DataGridTab();
        $Grid->setId('tblGroups');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridData->getDataCount(), !empty($search)));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Grupos'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la pestaña de perfiles
     */
    public function getProfilesList()
    {
        $this->setAction(self::ACTION_USR_PROFILES);

        if (!$this->checkAccess()) {
            return;
        }

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_PROFILES_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Perfil'));
        $GridActionNew->setTitle(_('Nuevo Perfil'));
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_PROFILES_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_PROFILES_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Detalles de Perfil'));
        $GridActionView->setTitle(_('Ver Detalles de Perfil'));
        $GridActionView->setIcon($this->_icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_USR_PROFILES_VIEW);
        $GridActionView->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_PROFILES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Perfil'));
        $GridActionEdit->setTitle(_('Editar Perfil'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_PROFILES_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_PROFILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Perfil'));
        $GridActionDel->setTitle(_('Eliminar Perfil'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_PROFILES_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('userprofile_id');
        $GridData->addDataRowSource('userprofile_name');
        $GridData->setData(Profile::getProfiles());

        $Grid = new DataGridTab();
        $Grid->setId('tblProfiles');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridData->getDataCount(), !empty($search)));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Perfiles'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->view->addTemplate('datatabs-grid');

        $this->view->assign('tabs', array());
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la ficha de usuario
     */
    public function getUser()
    {
        $this->_module = self::ACTION_USR_USERS;
        $this->view->addTemplate('users');

        $this->view->assign('user', UserUtil::getUserData($this->view->itemId));
        $this->view->assign('isDisabled', (($this->view->user['user_login'] === 'demo' && $this->view->isDemo) || $this->view->actionId === self::ACTION_USR_USERS_VIEW) ? 'disabled' : '');
        $this->view->assign('groups', DBUtil::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('profiles', DBUtil::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'));
        $this->view->assign('ro', ($this->view->user['checks']['user_isLdap']) ? 'READONLY' : '');

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        // Se comprueba que hayan campos con valores para el elemento actual
        if ($this->view->itemId && CustomFields::checkCustomFieldExists($this->_module, $this->view->itemId)) {
            $this->view->assign('customFields', CustomFields::getCustomFieldsData($this->_module, $this->view->itemId));
        } else {
            $this->view->assign('customFields', CustomFields::getCustomFieldsForModule($this->_module));
        }
    }

    /**
     * Obtener los datos para la ficha de grupo
     */
    public function getGroup()
    {
        $this->_module = self::ACTION_USR_GROUPS;
        $this->view->addTemplate('groups');

        $this->view->assign('group', Groups::getGroupData($this->view->itemId));
        $this->view->assign('users', DBUtil::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('groupUsers', Groups::getUsersForGroup($this->view->itemId));

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la ficha de perfil
     */
    public function getProfile()
    {
        $this->view->addTemplate('profiles');

        $profile = ($this->view->itemId) ? Profile::getProfile($this->view->itemId) : new Profile();

        $this->view->assign('profile', $profile);
        $this->view->assign('isDisabled', ($this->view->actionId === self::ACTION_USR_PROFILES_VIEW) ? 'disabled' : '');

        if ($this->view->isView === true) {
            $this->view->assign('usedBy', Profile::getProfileInUsersName($this->view->itemId));
        }
    }

    /**
     * Inicializar la vista de cambio de clave de usuario
     */
    public function getUserPass()
    {
        $this->setAction(self::ACTION_USR_USERS_EDITPASS);

        // Comprobar si el usuario a modificar es distinto al de la sesión
        if ($this->view->userId != Session::getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('userspass');

        $this->view->assign('actionId', self::ACTION_USR_USERS_EDITPASS);

        // Obtener de nuevo el token de seguridad por si se habñia regenerado antes
        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     */
    public function getAPITokensList()
    {
        $this->setAction(self::ACTION_MGM_APITOKENS);

        if (!$this->checkAccess()) {
            return;
        }

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_APITOKENS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nueva Autorización'));
        $GridActionNew->setTitle(_('Nueva Autorización'));
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_APITOKENS_NEW);
        $GridActionNew->setOnClickArgs($this->view->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_APITOKENS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver token de Autorización'));
        $GridActionView->setTitle(_('Ver token de Autorización'));
        $GridActionView->setIcon($this->_icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_APITOKENS_VIEW);
        $GridActionView->setOnClickArgs($this->view->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_APITOKENS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Autorización'));
        $GridActionEdit->setTitle(_('Editar Autorización'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_APITOKENS_EDIT);
        $GridActionEdit->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_APITOKENS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Autorización'));
        $GridActionDel->setTitle(_('Eliminar Autorización'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_APITOKENS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Usuario'));
        $GridHeaders->addHeader(_('Acción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('authtoken_id');
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('authtoken_actionId');
        $GridData->setData(ApiTokens::getTokens());

        $Grid = new DataGridTab();
        $Grid->setId('tblTokens');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridData->getDataCount(), !empty($search)));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Autorizaciones API'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la ficha de tokens de API
     */
    public function getToken()
    {
        $this->view->addTemplate('tokens');

        $token = ApiTokens::getTokens($this->view->itemId, true);

        $this->view->assign('users', DBUtil::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('actions', ApiTokens::getTokenActions());
        $this->view->assign('token', $token);
        $this->view->assign('gotData', is_object($token));

        if ($this->view->isView === true) {
            $msg = sprintf('%s ;;Usuario: %s', _('Token de autorización visualizado'), $token->user_login);
            Log::writeNewLogAndEmail(_('Autorizaciones'), $msg, null);
        }
    }

    /**
     * Obtener los datos para la pestaña de tokens de API
     */
    public function getPublicLinksList()
    {
        $this->setAction(self::ACTION_MGM_PUBLICLINKS);

        if (!$this->checkAccess()) {
            return;
        }

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_PUBLICLINKS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Enlace'));
        $GridActionView->setTitle(_('Ver Enlace'));
        $GridActionView->setIcon($this->_icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_VIEW);
        $GridActionView->setOnClickArgs($this->view->sk);

        $GridActionRefresh = new DataGridAction();
        $GridActionRefresh->setId(self::ACTION_MGM_PUBLICLINKS_REFRESH);
        $GridActionRefresh->setName(_('Renovar Enlace'));
        $GridActionRefresh->setTitle(_('Renovar Enlace'));
        $GridActionRefresh->setIcon(new DataGridIcon('refresh', 'imgs/view.png', 'fg-green80'));
        $GridActionRefresh->setOnClickFunction('sysPassUtil.Common.linksMgmtRefresh');
        $GridActionRefresh->setOnClickArgs('this');
        $GridActionRefresh->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_REFRESH);
        $GridActionRefresh->setOnClickArgs($this->view->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_PUBLICLINKS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Enlace'));
        $GridActionDel->setTitle(_('Eliminar Enlace'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_DELETE);
        $GridActionDel->setOnClickArgs($this->view->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Cuenta'));
        $GridHeaders->addHeader(_('Fecha Creación'));
        $GridHeaders->addHeader(_('Fecha Caducidad'));
        $GridHeaders->addHeader(_('Usuario'));
        $GridHeaders->addHeader(_('Notificar'));
        $GridHeaders->addHeader(_('Visitas'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('publicLink_id');
        $GridData->addDataRowSource('publicLink_account');
        $GridData->addDataRowSource('publicLink_dateAdd');
        $GridData->addDataRowSource('publicLink_dateExpire');
        $GridData->addDataRowSource('publicLink_user');
        $GridData->addDataRowSource('publicLink_notify');
        $GridData->addDataRowSource('publicLink_views');
        $GridData->setData(PublicLinkUtil::getLinks());

        $Grid = new DataGridTab();
        $Grid->setId('tblLinks');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionRefresh);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridData->getDataCount(), !empty($search)));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Enlaces'));
        $Grid->setTime(round(microtime() - $this->view->queryTimeStart, 5));

        $this->view->append('tabs', $Grid);
    }

    /**
     * Obtener los datos para la ficha de enlace público
     */
    public function getPublicLink()
    {
        $this->view->addTemplate('publiclinks');

        $this->view->assign('link', PublicLinkUtil::getLinks($this->view->itemId)[0]);
    }

    /**
     * Devolver el paginador
     *
     * @param int  $numRows El número de registros devueltos
     * @param bool $filter Si está activo el filtrado
     * @return DataGridPager
     */
    public function getPager($numRows, $filter = false)
    {
        $GridPager = new DataGridPager();
        $GridPager->setFilterOn($filter);
        $GridPager->setTotalRows($numRows);
        $GridPager->setLimitStart(Request::analyze('start', 0));
        $GridPager->setLimitCount(Request::analyze('count', Config::getValue('account_count', 15)));
        $GridPager->setOnClickFunction('sysPassUtil.Common.searchSort');

        return $GridPager;
    }
}