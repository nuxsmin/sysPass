<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileData;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Auth\AuthException;
use SP\Services\User\UserLoginResponse;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    use WebControllerTrait;

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
     * @var  UserLoginResponse
     */
    protected $userData;
    /**
     * @var  ProfileData
     */
    protected $userProfileData;
    /**
     * @var ContainerInterface
     */
    protected $dic;
    /**
     * @var bool
     */
    protected $isAjax = false;
    /**
     * @var string
     */
    protected $previousSk;

    /**
     * Constructor
     *
     * @param Container $container
     * @param           $actionName
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SessionTimeout
     */
    public final function __construct(Container $container, $actionName)
    {
        $this->dic = $container;
        $this->actionName = $actionName;

        $this->setUp($container);

        $this->view = $this->dic->get(Template::class);
        $this->view->setBase(strtolower($this->controllerName));

        $this->isAjax = $this->request->isAjax();
        $this->previousSk = $this->session->getSecurityKey();

        $loggedIn = $this->session->isLoggedIn();

        if ($loggedIn) {
            $this->userData = clone $this->session->getUserData();
            $this->userProfileData = clone $this->session->getUserProfile();
        }

        $this->setViewVars($loggedIn);

        try {
            $this->initialize();
        } catch (SessionTimeout $sessionTimeout) {
            $this->handleSessionTimeout();

            throw $sessionTimeout;
        }
    }

    /**
     * Set view variables
     *
     * @param bool $loggedIn
     */
    private function setViewVars($loggedIn = false)
    {
        $this->view->assign('timeStart', $this->request->getServer('REQUEST_TIME_FLOAT'));
        $this->view->assign('queryTimeStart', microtime());

        if ($loggedIn) {
            $this->view->assign('ctx_userId', $this->userData->getId());
            $this->view->assign('ctx_userGroupId', $this->userData->getUserGroupId());
            $this->view->assign('ctx_userIsAdminApp', $this->userData->getIsAdminApp());
            $this->view->assign('ctx_userIsAdminAcc', $this->userData->getIsAdminAcc());
        }

        $this->view->assign('isDemo', $this->configData->isDemoEnabled());
        $this->view->assign('themeUri', $this->view->getTheme()->getThemeUri());
        $this->view->assign('configData', $this->configData);
        $this->view->assign('sk', $loggedIn ? $this->session->generateSecurityKey($this->configData->getPasswordSalt()) : '');

        // Pass the action name to the template as a variable
        $this->view->assign($this->actionName, true);
    }

    /**
     * @return void
     */
    protected abstract function initialize();

    /**
     * @return void
     */
    private function handleSessionTimeout()
    {
        $this->sessionLogout(
            $this->request,
            $this->configData,
            function ($redirect) {
                $this->router->response()
                    ->redirect($redirect)
                    ->send(true);
            }
        );
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view()
    {
        try {
            $this->router->response()
                ->body($this->view->render())
                ->send();
        } catch (FileNotFoundException $e) {
            processException($e);

            $this->router->response()
                ->body(__($e->getMessage()))
                ->send(true);
        }
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
        } catch (Exception $e) {
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
     * @param bool $requireAuthCompleted
     *
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function checkLoggedIn($requireAuthCompleted = true)
    {
        if ($this->session->isLoggedIn() === false
            || $this->session->getAuthCompleted() !== $requireAuthCompleted
        ) {
            throw new SessionTimeout();
        }

        if ($this->session->isLoggedIn()
            && $this->session->getAuthCompleted() === $requireAuthCompleted
            && $this->configData->isAuthBasicEnabled()
        ) {
            $browser = $this->dic->get(Browser::class);

            // Comprobar si se ha identificado mediante el servidor web y el usuario coincide
            if ($browser->checkServerAuthUser($this->userData->getLogin()) === false
                && $browser->checkServerAuthUser($this->userData->getSsoLogin()) === false
            ) {
                throw new AuthException('Invalid browser auth');
            }
        }
    }

    /**
     * prepareSignedUriOnView
     *
     * Prepares view's variables to pass in a signed URI
     */
    final protected function prepareSignedUriOnView()
    {
        $from = $this->request->analyzeString('from');

        if ($from) {
            try {
                $this->request->verifySignature($this->configData->getPasswordSalt());

                $this->view->assign('from', $from);
                $this->view->assign('from_hash', Hash::signMessage($from, $this->configData->getPasswordSalt()));
            } catch (SPException $e) {
                processException($e);
            }
        }
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param null $action La acción a comprobar
     *
     * @return bool
     */
    protected function checkAccess($action)
    {
        return $this->userData->getIsAdminApp() || $this->acl->checkUserAccess($action);
    }
}