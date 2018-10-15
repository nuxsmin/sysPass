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

use DI\Container;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\UnauthorizedPageException;
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
     * SimpleControllerBase constructor.
     *
     * @param Container $container
     * @param           $actionName
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(Container $container, $actionName)
    {
        $this->dic = $container;
        $this->actionName = $actionName;

        $this->setUp($container);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Comprobaciones
     */
    protected function checks()
    {
        $this->checkLoggedInSession(
            $this->session,
            $this->request,
            function ($redirect) {
                $this->router->response()
                    ->redirect($redirect)
                    ->send(true);
            }
        );
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
        if (!$this->session->getUserData()->getIsAdminApp() && !$this->acl->checkUserAccess($action)) {
            throw new UnauthorizedPageException(UnauthorizedPageException::INFO);
        }
    }
}