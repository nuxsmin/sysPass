<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Plugin;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Plugin\Services\PluginDataService;
use SP\Domain\Plugin\Services\PluginService;
use SP\Http\JsonResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginModel;
use SP\Plugin\PluginManager;

/**
 * Class IndexController
 *
 * @package web\Controllers
 */
final class IndexController extends PluginSearchBase
{
    /**
     * indexAction
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function indexAction(): void
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PLUGIN)) {
            return;
        }

        $this->view->addTemplate('index');

        $this->view->assign('data', $this->getSearchGrid());

        $this->view();
    }











    /**
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();

        $this->pluginService = $this->dic->get(PluginService::class);
        $this->pluginDataService = $this->dic->get(PluginDataService::class);
    }
}