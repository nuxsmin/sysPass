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

namespace SP\Modules\Web\Controllers\Install;

use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    public function indexAction(): void
    {
        if ($this->configData->isInstalled()) {
            $this->router->response()->redirect('index.php?r=login');

            return;
        }

        $this->layoutHelper->getPublicLayout('index', 'install');

        $errors = [];

        foreach ($this->extensionChecker->getMissing() as $module) {
            $errors[] = [
                'type'        => SPException::WARNING,
                'description' => sprintf('%s (%s)', __('Module unavailable'), $module),
                'hint'        => __('Without this module the application could not run correctly'),
            ];
        }

        $this->view->assign('errors', $errors);
        $this->view->assign(
            'langs',
            SelectItemAdapter::factory(Language::getAvailableLanguages())
                ->getItemsFromArraySelected([Language::$globalLang])
        );

        $this->view();
    }
}