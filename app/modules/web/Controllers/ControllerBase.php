<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

defined('APP_ROOT') || die();

use DI\Container;
use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Context\SessionContext;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Language;
use SP\Core\UI\Theme;
use SP\DataModel\ProfileData;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Mvc\Controller\ControllerTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Auth\AuthException;
use SP\Services\User\UserLoginResponse;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    use ControllerTrait;

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
     * @var Template Instancia del motor de plantillas a utilizar
     */
    protected $view;
    /**
     * @var  int ID de la acción
     */
    protected $action;
    /**
     * @var string Nombre de la acción
     */
    protected $actionName;
    /**
     * @var string Nombre del controlador
     */
    protected $controllerName;
    /**
     * @var  UserLoginResponse
     */
    protected $userData;
    /**
     * @var  ProfileData
     */
    protected $userProfileData;
    /**
     * @var  EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var bool
     */
    protected $loggedIn = false;
    /**
     * @var  ConfigData
     */
    protected $configData;
    /**
     * @var  Config
     */
    protected $config;
    /**
     * @var  SessionContext
     */
    protected $session;
    /**
     * @var  Theme
     */
    protected $theme;
    /**
     * @var  \SP\Core\Acl\Acl
     */
    protected $acl;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var ContainerInterface
     */
    protected $dic;
    /**
     * @var
     */
    protected $isAjax = false;

    /**
     * Constructor
     *
     * @param Container $container
     * @param           $actionName
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public final function __construct(Container $container, $actionName)
    {
        $this->dic = $container;

        $this->controllerName = $this->getControllerName();
        $this->actionName = $actionName;

        $this->config = $this->dic->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->session = $this->dic->get(SessionContext::class);
        $this->theme = $this->dic->get(Theme::class);
        $this->eventDispatcher = $this->dic->get(EventDispatcher::class);
        $this->acl = $this->dic->get(Acl::class);
        $this->router = $this->dic->get(Klein::class);
        $this->view = $this->dic->get(Template::class);

        $this->view->setBase(strtolower($this->controllerName));

        $this->isAjax = $this->router->request()->headers()->get('X_REQUESTED_WITH') === 'XMLHttpRequest';

        if ($this->session->isLoggedIn()) {
            $this->userData = clone $this->session->getUserData();
            $this->userProfileData = clone $this->session->getUserProfile();

            $this->setViewVars();
        }

        $this->view->assign('language', substr(Language::$globalLang, 0, 2));

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Set view variables
     */
    private function setViewVars()
    {
        $this->view->assign('timeStart', $this->router->request()->server()->get('REQUEST_TIME_FLOAT'));
        $this->view->assign('queryTimeStart', microtime());
        $this->view->assign('userId', $this->userData->getId());
        $this->view->assign('userGroupId', $this->userData->getUserGroupId());
        $this->view->assign('userIsAdminApp', $this->userData->getIsAdminApp());
        $this->view->assign('userIsAdminAcc', $this->userData->getIsAdminAcc());
        $this->view->assign('themeUri', $this->view->getTheme()->getThemeUri());
        $this->view->assign('isDemo', $this->configData->isDemoEnabled());
        $this->view->assign('icons', $this->theme->getIcons());
        $this->view->assign('configData', $this->configData);
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view()
    {
        try {
            echo $this->view->render();
        } catch (FileNotFoundException $e) {
            processException($e);

            echo __($e->getMessage());
        }

        die();
    }

    /**
     * Renderizar los datos de la plantilla y devolverlos
     */
    protected function render()
    {
        try {
            return $this->view->render();
        } catch (FileNotFoundException $e) {
            processException($e);

            return $e->getMessage();
        }
    }

    /**
     * Upgrades a View to use a full page layout
     *
     * @param string $page
     */
    protected function upgradeView($page = null)
    {
        $this->view->upgrade();

        if ($this->view->isUpgraded() === false) {
            return;
        }

        $this->view->assign('contentPage', $page ?: strtolower($this->controllerName));

        try {
            $this->dic->get(LayoutHelper::class)->getFullLayout('main', $this->acl);
        } catch (\Exception $e) {
            processException($e);
        }
    }

    /**
     * Obtener los datos para la vista de depuración
     */
    protected function getDebug()
    {
        global $memInit;

        $this->view->addTemplate('debug', 'common');

        $this->view->assign('time', getElapsedTime($this->router->request()->server()->get('REQUEST_TIME_FLOAT')));
        $this->view->assign('memInit', $memInit / 1000);
        $this->view->assign('memEnd', memory_get_usage() / 1000);
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws AuthException
     */
    protected function checkLoggedIn()
    {
        if ($this->session->isLoggedIn()
            && $this->session->getAuthCompleted() === true
        ) {
            $browser = $this->dic->get(Browser::class);

            // Comprobar si se ha identificado mediante el servidor web y el usuario coincide
            if ($browser->checkServerAuthUser($this->userData->getLogin()) === false
                && $browser->checkServerAuthUser($this->userData->getSsoLogin()) === false
            ) {
                throw new AuthException('Invalid browser auth');
            }
        }

        $this->checkLoggedInSession($this->session, $this->router);
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param null $action La acción a comprobar
     * @return bool
     */
    protected function checkAccess($action)
    {
        return $this->userData->getIsAdminApp() || $this->acl->checkUserAccess($action);
    }
}