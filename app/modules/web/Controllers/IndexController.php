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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    /**
     * Index action
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function indexAction()
    {
        if ($this->session->isLoggedIn() === false
            || $this->session->getAuthCompleted() === false
        ) {
            $this->router->response()
                ->redirect('index.php?r=login');
        } else {
            $this->dic->get(LayoutHelper::class)->getFullLayout('main', $this->acl);

            $this->view();
        }
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // TODO: Implement initialize() method.
    }
}