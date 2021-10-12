<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers;

use Psr\Container\ContainerInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use WebControllerTrait;

    protected ContainerInterface $dic;

    /**
     * SimpleControllerBase constructor.
     *
     * @throws \JsonException
     */
    public function __construct(
        ContainerInterface $container,
        string             $actionName
    )
    {
        $this->dic = $container;
        $this->actionName = $actionName;

        $this->setUp($container);

        try {
            $this->initialize();
        } catch (SessionTimeout $sessionTimeout) {
            $this->handleSessionTimeout();

            throw $sessionTimeout;
        }
    }

    abstract protected function initialize(): void;

    /**
     * @throws \JsonException
     */
    public function handleSessionTimeout(): void
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
    protected function checks(): void
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
     * @throws UnauthorizedPageException
     */
    protected function checkAccess(int $action): void
    {
        if (!$this->acl->checkUserAccess($action)
            && !$this->session->getUserData()->getIsAdminApp()
        ) {
            throw new UnauthorizedPageException(SPException::INFO);
        }
    }
}