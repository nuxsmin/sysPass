<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Upgrade;


use SP\Core\Acl\Actions;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class IndexController
 */
final class IndexController extends ControllerBase
{
    private Actions $actions;

    public function __construct(Application $application, WebControllerHelper $webControllerHelper, ActionsInterface $actions)
    {
        parent::__construct($application, $webControllerHelper);

        $this->actions = $actions;
    }

    /**
     * indexAction
     *
     * @throws \SP\Infrastructure\File\FileException
     */
    public function indexAction(): void
    {
        $this->layoutHelper->getPublicLayout('index', 'upgrade');

        $this->actions->reset();

        $this->view();
    }
}
