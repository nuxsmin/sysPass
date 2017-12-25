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

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Session\Session;
use SP\Core\Template;
use SP\Core\Traits\InjectableTrait;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeIconsBase;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Storage\Database;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    /**
     * Constantes de errores
     */
    const ERR_UNAVAILABLE = 0;
    const ERR_ACCOUNT_NO_PERMISSION = 1;
    const ERR_PAGE_NO_PERMISSION = 2;
    const ERR_UPDATE_MPASS = 3;
    const ERR_OPERATION_NO_PERMISSION = 4;
    const ERR_EXCEPTION = 5;


    /** @var Template Instancia del motor de plantillas a utilizar */
    protected $view;
    /** @var  int ID de la acción */
    protected $action;
    /** @var string Nombre de la acción */
    protected $actionName;
    /** @var ThemeIconsBase Instancia de los iconos del tema visual */
    protected $icons;
    /** @var string Nombre del controlador */
    protected $controllerName;
    /** @var  JsonResponse */
    protected $jsonResponse;
    /** @var  UserData */
    protected $userData;
    /** @var  ProfileData */
    protected $userProfileData;
    /** @var  EventDispatcher */
    protected $eventDispatcher;
    /** @var bool */
    protected $loggedIn = false;
    /** @var  ConfigData */
    protected $configData;
    /** @var  Config */
    protected $config;
    /** @var  Session */
    protected $session;
    /** @var  Database */
    protected $db;
    /** @var  Theme */
    protected $theme;
    /** @var  \SP\Core\Acl\Acl */
    protected $acl;

    use InjectableTrait;

    /**
     * Constructor
     *
     * @param $actionName
     */
    public function __construct($actionName)
    {
        $this->injectDependencies();

        $class = static::class;
        $this->controllerName = substr($class, strrpos($class, '\\') + 1, -strlen('Controller'));
        $this->actionName = $actionName;

        $this->view = new Template();
        $this->view->setBase(strtolower($this->controllerName));

        $this->icons = $this->theme->getIcons();

        $this->setViewVars();

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Set view variables
     */
    private function setViewVars()
    {
        $this->userData = $this->session->getUserData();
        $this->userProfileData = $this->session->getUserProfile();

        $this->view->assign('timeStart', $_SERVER['REQUEST_TIME_FLOAT']);
        $this->view->assign('icons', $this->icons);
        $this->view->assign('SessionUserData', $this->userData);

        $this->view->assign('actionId', Request::analyze('actionId', 0));
        $this->view->assign('id', Request::analyze('itemId', 0));
        $this->view->assign('queryTimeStart', microtime());
        $this->view->assign('userId', $this->userData->getUserId());
        $this->view->assign('userGroupId', $this->userData->getUserGroupId());
        $this->view->assign('userIsAdminApp', $this->userData->isUserIsAdminApp());
        $this->view->assign('userIsAdminAcc', $this->userData->isUserIsAdminAcc());
        $this->view->assign('themeUri', $this->view->getTheme()->getThemeUri());
        $this->view->assign('isDemo', $this->configData->isDemoEnabled());
    }

    /**
     * @param Config          $config
     * @param Session         $session
     * @param Database        $db
     * @param Theme           $theme
     * @param EventDispatcher $ev
     * @param Acl             $acl
     */
    public function inject(Config $config, Session $session, Database $db, Theme $theme, EventDispatcher $ev, Acl $acl)
    {
        $this->config = $config;
        $this->configData = $config->getConfigData();
        $this->session = $session;
        $this->db = $db;
        $this->theme = $theme;
        $this->eventDispatcher = $ev;
        $this->acl = $acl;
    }

    /**
     * @return int El id de la acción
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Establecer el módulo a presentar.
     *
     * @param int $action El id de la acción
     */
    public function setAction($action)
    {
        $this->action = (int)$action;
    }

    /**
     * Mostrar los datos de la plantilla
     */
    public function view()
    {
        try {
            echo $this->view->render();
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage(), true);

            echo $e->getMessage();
        }

        die();
    }

    /**
     * Renderizar los datos de la plantilla y devolverlos
     */
    public function render()
    {
        try {
            return $this->view->render();
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage(), true);

            echo $e->getMessage();
        }
    }

    /**
     * Obtener los datos para la vista de depuración
     */
    public function getDebug()
    {
        global $memInit;

        $this->view->addTemplate('debug', 'common');

        $this->view->assign('time', getElapsedTime());
        $this->view->assign('memInit', $memInit / 1000);
        $this->view->assign('memEnd', memory_get_usage() / 1000);
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param int  $type int con el tipo de error
     * @param bool $reset
     * @param null $replace
     * @deprecated Use ErrorUtil class
     */
    public function showError($type, $reset = true, $replace = null)
    {

    }

    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {

    }

    /**
     * Comprobar si el usuario está logado.
     */
    public function checkLoggedIn()
    {
        if (!$this->session->isLoggedIn()) {
            if (Checks::isJson()) {
                $jsonResponse = new JsonResponse();
                $jsonResponse->setDescription(__('La sesión no se ha iniciado o ha caducado', false));
                $jsonResponse->setStatus(10);

                Json::returnJson($jsonResponse);
            } else {
                Util::logout();
            }
        }
    }

    /**
     * @param bool $loggedIn
     */
    protected function setLoggedIn($loggedIn)
    {
        $this->loggedIn = (bool)$loggedIn;
        $this->view->assign('loggedIn', $this->loggedIn);
    }

    /**
     * Establecer la instancia del motor de plantillas a utilizar.
     *
     * @param Template $template
     */
    protected function setTemplate(Template $template)
    {
        $this->view = $template;
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param null $action La acción a comprobar
     * @return bool
     */
    protected function checkAccess($action = null)
    {
        $checkAction = $this->action;

        if (null !== $action) {
            $checkAction = $action;
        }

        return $this->session->getUserData()->isUserIsAdminApp() || $this->acl->checkUserAccess($checkAction);
    }
}