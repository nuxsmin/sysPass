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

/**
 * Clase encargada de de preparar la presentación de las vistas de gestión de usuarios
 *
 * @package Controller
 */
class UsersMgmtC extends Controller implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;

    /**
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', \SP\Util::demoIsEnabled());
        $this->view->assign('sk', \SP\Common::getSessionKey());
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     */
    public function getUsersList()
    {
        $this->setAction(self::ACTION_USR_USERS);

        $this->view->assign('sk', \SP\Common::getSessionKey(true));

        if (!$this->checkAccess()) {
            return;
        }

        $arrUsersTableProp = array(
            'tblId' => 'tblUsers',
            'header' => '',
            'tblHeaders' => array(
                _('Nombre'),
                _('Login'),
                _('Perfil'),
                _('Grupo'),
                _('Propiedades')),
            'tblRowSrc' => array(
                'user_name',
                'user_login',
                'userprofile_name',
                'usergroup_name', array(
                    'user_isAdminApp' => array(
                        'img_file' => 'check_blue.png',
                        'img_title' => _('Admin Aplicación')),
                    'user_isAdminAcc' => array(
                        'img_file' => 'check_orange.png',
                        'img_title' => _('Admin Cuentas')),
                    'user_isLdap' => array(
                        'img_file' => 'ldap.png',
                        'img_title' => _('Usuario de LDAP')),
                    'user_isDisabled' => array(
                        'img_file' => 'disabled.png',
                        'img_title' => _('Deshabilitado'))
                )
            ),
            'tblRowSrcId' => 'user_id',
            'onCloseAction' => self::ACTION_USR,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_USR_USERS_NEW,
                    'title' => _('Nuevo Usuario'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_USERS_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'view' => array(
                    'id' => self::ACTION_USR_USERS_VIEW,
                    'title' => _('Ver Detalles de Usuario'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_USERS_VIEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/view.png'
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_USERS_EDIT,
                    'title' => _('Editar Usuario'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_USERS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_USERS_DELETE,
                    'title' => _('Eliminar Usuario'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_USERS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'isdelete' => true
                ),
                'pass' => array(
                    'id' => self::ACTION_USR_USERS_EDITPASS,
                    'title' => _('Cambiar Clave de Usuario'),
                    'onclick' => 'usrUpdPass(this,' . self::ACTION_USR_USERS_EDITPASS . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/key.png'
                )
            )
        );

        $arrUsersTableProp['cellWidth'] = floor(65 / count($arrUsersTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Usuarios'),
                'query' => \SP\Users::getUsers(),
                'props' => $arrUsersTableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5))
        );

    }

    /**
     * Obtener los datos para la pestaña de grupos
     */
    public function getGroupsList()
    {
        $this->setAction(self::ACTION_USR_GROUPS);

        $this->view->assign('sk', \SP\Common::getSessionKey(true));

        if (!$this->checkAccess()) {
            return;
        }

        $arrGroupsTableProp = array(
            'tblId' => 'tblGroups',
            'header' => '',
            'tblHeaders' => array(_('Nombre'), _('Descripción')),
            'tblRowSrc' => array('usergroup_name', 'usergroup_description'),
            'tblRowSrcId' => 'usergroup_id',
            'onCloseAction' => self::ACTION_USR,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_USR_GROUPS_NEW,
                    'title' => _('Nuevo Grupo'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_GROUPS_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_GROUPS_EDIT,
                    'title' => _('Editar Grupo'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_GROUPS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_GROUPS_DELETE,
                    'title' => _('Eliminar Grupo'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_GROUPS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'isdelete' => true
                )
            )
        );

        $arrGroupsTableProp['cellWidth'] = floor(65 / count($arrGroupsTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Grupos'),
                'query' => \SP\Groups::getGroups(),
                'props' => $arrGroupsTableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5))
        );
    }

    /**
     * Obtener los datos para la pestaña de perfiles
     */
    public function getProfilesList()
    {
        $this->setAction(self::ACTION_USR_PROFILES);

        $this->view->assign('sk', \SP\Common::getSessionKey(true));

        if (!$this->checkAccess()) {
            return;
        }

        $arrProfilesTableProp = array(
            'tblId' => 'tblProfiles',
            'header' => '',
            'tblHeaders' => array(_('Nombre')),
            'tblRowSrc' => array('userprofile_name'),
            'tblRowSrcId' => 'userprofile_id',
            'onCloseAction' => self::ACTION_USR,
            'actions' => array(
                'new' => array(
                    'id' => self::ACTION_USR_PROFILES_NEW,
                    'title' => _('Nuevo Perfil'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_PROFILES_NEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/new.png',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_PROFILES_EDIT,
                    'title' => _('Editar Perfil'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_PROFILES_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_PROFILES_DELETE,
                    'title' => _('Eliminar Perfil'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_PROFILES_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'isdelete' => true
                )
            )
        );

        $arrProfilesTableProp['cellWidth'] = floor(65 / count($arrProfilesTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Perfiles'),
                'query' => \SP\Profiles::getProfiles(),
                'props' => $arrProfilesTableProp,
                'time' => round(microtime() - $this->view->queryTimeStart, 5)
            )
        );
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->view->addTemplate('tabs-start');
        $this->view->addTemplate('mgmttabs');
        $this->view->addTemplate('tabs-end');

        $this->view->assign('tabs', array());
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la ficha de usuario
     */
    public function getUser()
    {
        $this->view->addTemplate('users');

        $this->view->assign('isDisabled', ($this->view->isDemo || $this->view->actionId === self::ACTION_USR_USERS_VIEW) ? 'disabled' : '');
        $this->view->assign('user', \SP\Users::getUserData($this->view->itemId));

        $this->view->assign(
            'profilesSelProp', array('name' => 'profileid',
                'id' => 'selProfile',
                'class' => '',
                'size' => 1,
                'label' => '',
                'selected' => $this->view->user['user_profileId'],
                'default' => '',
                'js' => '',
                'attribs' => array('required', $this->view->isDisabled))
        );

        $this->view->assign(
            'groupsSelProp', array('name' => 'groupid',
                'id' => 'selGroup',
                'class' => '',
                'size' => 1,
                'label' => '',
                'selected' => $this->view->user['user_groupId'],
                'default' => '',
                'js' => '',
                'attribs' => array('required', $this->view->isDisabled))
        );

        $this->view->assign('ro', ($this->view->user['checks']['user_isLdap']) ? 'READONLY' : '');
    }

    /**
     * Obtener los datos para la ficha de grupo
     */
    public function getGroup()
    {
        $this->view->addTemplate('groups');

        $this->view->assign('group', \SP\Groups::getGroupData($this->view->itemId));
    }

    /**
     * Obtener los datos para la ficha de perfil
     */
    public function getProfile()
    {
        $this->view->addTemplate('profiles');

        $this->view->assign('profile', \SP\Profiles::getProfileData($this->view->itemId));
    }

    /**
     * Inicializar la vista de cambio de clave de usuario
     */
    public function getUserPass()
    {
        $this->setAction(self::ACTION_USR_USERS_EDITPASS);

        // Comprobar si el usuario a modificar es distinto al de la sesión
        if ($this->view->userId != \SP\Session::getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('userspass');

        $this->view->assign('actionId', self::ACTION_USR_USERS_EDITPASS);
        $this->view->assign('sk', \SP\Common::getSessionKey());
    }

}