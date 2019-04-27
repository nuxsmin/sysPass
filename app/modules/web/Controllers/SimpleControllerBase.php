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

use DI\Container;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use WebControllerTrait;

    /**
     * @var ContainerInterface
     */
    protected $dic;
    /**
     * @var string
     */
    protected $previousSk;

    /**
     * SimpleControllerBase constructor.
     *
     * @param Container $container
     * @param           $actionName
     *
     * @throws SessionTimeout
     */
    public function __construct(Container $container, $actionName)
    {
        $this->dic = $container;
        $this->actionName = $actionName;

        $this->setUp($container);

        $this->previousSk = $this->session->getSecurityKey();

        try {
            $this->initialize();
        } catch (SessionTimeout $sessionTimeout) {
            $this->handleSessionTimeout();

            throw $sessionTimeout;
        }
    }

    /**
     * @return void
     */
    protected abstract function initialize();

    /**
     * @return void
     */
    public function handleSessionTimeout()
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
     * Comprobaciones
     *
     * @throws SessionTimeout
     */
    protected function checks()
    {
        if ($this->session->isLoggedIn() === false
            || $this->session->getAuthCompleted() !== true
        ) {
            throw new SessionTimeout();
        }

//        $this->checkSecurityToken($this->session, $this->request);
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param null $action La acción a comprobar
     *
     * @throws UnauthorizedPageException
     */
    protected function checkAccess($action)
    {
        if (!$this->session->getUserData()->getIsAdminApp()
            && !$this->acl->checkUserAccess($action)
        ) {
            throw new UnauthorizedPageException(UnauthorizedPageException::INFO);
        }
    }
}