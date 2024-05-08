<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\UserPassReset;

use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Util\ErrorUtil;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    public function indexAction(): void
    {
        $this->layoutHelper->getCustomLayout('request', strtolower($this->routeContextData->getActionName()));

        if (!$this->configData->isMailEnabled()) {
            ErrorUtil::showErrorInView($this->view, self::ERR_UNAVAILABLE, true, 'request');
        }

        $this->view();
    }
}
