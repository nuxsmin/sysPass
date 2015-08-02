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

use SP\Common;
use SP\DB;
use SP\Groups;
use SP\Profile;
use SP\Session;
use SP\Template;
use SP\UserUtil;
use SP\Util;

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
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Util::demoIsEnabled());
        $this->view->assign('sk', Common::getSessionKey());
    }

    /**
     * Obtener los datos para la pestaña de usuarios
     */
    public function getUsersList()
    {
        $this->setAction(self::ACTION_USR_USERS);

        $this->view->assign('sk', Common::getSessionKey(true));

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
                'usergroup_name',
                'images' => array(
                    'user_isAdminApp' => array(
                        'img_file' => 'check_blue.png',
                        'img_title' => _('Admin Aplicación'),
                        'icon' => 'star'),
                    'user_isAdminAcc' => array(
                        'img_file' => 'check_orange.png',
                        'img_title' => _('Admin Cuentas'),
                        'icon' => 'star_half'),
                    'user_isLdap' => array(
                        'img_file' => 'ldap.png',
                        'img_title' => _('Usuario de LDAP'),
                        'icon' => 'business'),
                    'user_isDisabled' => array(
                        'img_file' => 'disabled.png',
                        'img_title' => _('Deshabilitado'),
                        'icon' => 'error')
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
                    'icon' => 'add',
                    'skip' => true
                ),
                'view' => array(
                    'id' => self::ACTION_USR_USERS_VIEW,
                    'title' => _('Ver Detalles de Usuario'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_USERS_VIEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/view.png',
                    'icon' => 'visibility'
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_USERS_EDIT,
                    'title' => _('Editar Usuario'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_USERS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'pass' => array(
                    'id' => self::ACTION_USR_USERS_EDITPASS,
                    'title' => _('Cambiar Clave de Usuario'),
                    'onclick' => 'usrUpdPass(this,' . self::ACTION_USR_USERS_EDITPASS . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/key.png',
                    'icon' => 'lock_outline'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_USERS_DELETE,
                    'title' => _('Eliminar Usuario'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_USERS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                ),
            )
        );

        $arrUsersTableProp['cellWidth'] = floor(65 / count($arrUsersTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Usuarios'),
                'query' => UserUtil::getUsers(),
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

        $this->view->assign('sk', Common::getSessionKey(true));

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
                    'icon' => 'add',
                    'skip' => true
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_GROUPS_EDIT,
                    'title' => _('Editar Grupo'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_GROUPS_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_GROUPS_DELETE,
                    'title' => _('Eliminar Grupo'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_GROUPS_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                )
            )
        );

        $arrGroupsTableProp['cellWidth'] = floor(65 / count($arrGroupsTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Grupos'),
                'query' => Groups::getGroups(),
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

        $this->view->assign('sk', Common::getSessionKey(true));

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
                    'icon' => 'add',
                    'skip' => true
                ),
                'view' => array(
                    'id' => self::ACTION_USR_PROFILES_VIEW,
                    'title' => _('Ver Detalles de Perfil'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_PROFILES_VIEW . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/view.png',
                    'icon' => 'visibility'
                ),
                'edit' => array(
                    'id' => self::ACTION_USR_PROFILES_EDIT,
                    'title' => _('Editar Perfil'),
                    'onclick' => 'appMgmtData(this,' . self::ACTION_USR_PROFILES_EDIT . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/edit.png',
                    'icon' => 'mode_edit'
                ),
                'del' => array(
                    'id' => self::ACTION_USR_PROFILES_DELETE,
                    'title' => _('Eliminar Perfil'),
                    'onclick' => 'appMgmtDelete(this,' . self::ACTION_USR_PROFILES_DELETE . ',\'' . $this->view->sk . '\')',
                    'img' => 'imgs/delete.png',
                    'icon' => 'delete',
                    'isdelete' => true
                )
            )
        );

        $arrProfilesTableProp['cellWidth'] = floor(65 / count($arrProfilesTableProp['tblHeaders']));

        $this->view->append(
            'tabs', array(
                'title' => _('Gestión de Perfiles'),
                'query' => Profile::getProfiles(),
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
        $this->view->assign('user', UserUtil::getUserData($this->view->itemId));
        $this->view->assign('groups', DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('profiles', DB::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'));
        $this->view->assign('ro', ($this->view->user['checks']['user_isLdap']) ? 'READONLY' : '');
    }

    /**
     * Obtener los datos para la ficha de grupo
     */
    public function getGroup()
    {
        $this->view->addTemplate('groups');

        $this->view->assign('group', Groups::getGroupData($this->view->itemId));
        $this->view->assign('users', \SP\DB::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('groupUsers', \SP\Groups::getUsersForGroup($this->view->itemId));
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

        if ( $this->view->isView === true ) {
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
        $this->view->assign('sk', Common::getSessionKey());
    }
}