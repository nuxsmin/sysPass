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

use SP\Core\Acl;
use SP\Core\DiFactory;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\Template;
use SP\Core\UI\ThemeIconsBase;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\Http\JsonResponse;

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

    /**
     * Instancia del motor de plantillas a utilizar
     *
     * @var Template
     */
    public $view;
    /**
     * Módulo a usar
     *
     * @var int
     */
    protected $action;
    /**
     * Instancia de los iconos del tema visual
     *
     * @var ThemeIconsBase
     */
    protected $icons;
    /**
     * Nombre del controlador
     *
     * @var string
     */
    protected $controllerName;
    /**
     * @var JsonResponse
     */
    protected $Json;
    /**
     * @var UserData
     */
    protected $UserData;
    /**
     * @var ProfileData
     */
    protected $UserProfileData;
    /**
     * @var EventDispatcherInterface
     */
    protected $EventDispatcher;
    /**
     * @var bool
     */
    protected $loggedIn = false;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        $class = get_called_class();
        $this->controllerName = substr($class, strrpos($class, '\\') + 1, -strlen('Controller'));

        $this->view = null === $template ? $this->getTemplate() : $template;
        $this->view->setBase(strtolower($this->controllerName));

        $this->icons = DiFactory::getTheme()->getIcons();
        $this->EventDispatcher = DiFactory::getEventDispatcher();

        $this->setViewVars();
    }

    /**
     * Obtener una nueva instancia del motor de plantillas.
     *
     * @param null $template string con el nombre de la plantilla
     * @return Template
     */
    protected function getTemplate($template = null)
    {
        return new Template($template);
    }

    private function setViewVars()
    {
        $this->UserData = Session::getUserData();
        $this->UserProfileData = Session::getUserProfile();

        $this->view->assign('timeStart', $_SERVER['REQUEST_TIME_FLOAT']);
        $this->view->assign('icons', $this->icons);
        $this->view->assign('SessionUserData', $this->UserData);
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
        }
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
     * @return JsonResponse
     */
    public function getJson()
    {
        return $this->Json;
    }

    /**
     * @param JsonResponse $Json
     */
    public function setJson(JsonResponse $Json)
    {
        $this->Json = $Json;
    }

    /**
     * @return UserData
     */
    public function getUserData()
    {
        return $this->UserData;
    }

    /**
     * @return ProfileData
     */
    public function getUserProfileData()
    {
        return $this->UserProfileData;
    }

    /**
     * @return ThemeIconsBase
     */
    public function getIcons()
    {
        return $this->icons;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

    /**
     * @param bool $loggedIn
     */
    public function setLoggedIn($loggedIn)
    {
        $this->loggedIn = (bool)$loggedIn;
        $this->view->assign('loggedIn', $this->loggedIn);
    }

    /**
     * Establecer la plantilla de error con el código indicado.
     *
     * @param int  $type int con el tipo de error
     * @param bool $reset
     * @param bool $fancy
     */
    public function showError($type, $reset = true, $fancy = false)
    {
        $errorsTypes = [
            self::ERR_UNAVAILABLE => ['txt' => __('Opción no disponible'), 'hint' => __('Consulte con el administrador')],
            self::ERR_ACCOUNT_NO_PERMISSION => ['txt' => __('No tiene permisos para acceder a esta cuenta'), 'hint' => __('Consulte con el administrador')],
            self::ERR_PAGE_NO_PERMISSION => ['txt' => __('No tiene permisos para acceder a esta página'), 'hint' => __('Consulte con el administrador')],
            self::ERR_OPERATION_NO_PERMISSION => ['txt' => __('No tiene permisos para realizar esta operación'), 'hint' => __('Consulte con el administrador')],
            self::ERR_UPDATE_MPASS => ['txt' => __('Clave maestra actualizada'), 'hint' => __('Reinicie la sesión para cambiarla')],
            self::ERR_EXCEPTION => ['txt' => __('Se ha producido una excepción'), 'hint' => __('Consulte con el administrador')]
        ];

        if ($reset) {
            $this->view->resetTemplates();
        }

        if ($fancy) {
            $this->view->addTemplate('errorfancy');
        } else {
            $this->view->addTemplate('error', 'main');
        }

        $this->view->append('errors',
            [
                'type' => SPException::SP_WARNING,
                'description' => $errorsTypes[$type]['txt'],
                'hint' => $errorsTypes[$type]['hint']]
        );
    }

    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public abstract function doAction($type = null);

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

        return Session::getUserData()->isUserIsAdminApp() || Acl::checkUserAccess($checkAction);
    }
}