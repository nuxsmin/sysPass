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

namespace SP\Modules\Web\Controllers\Install;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Ports\LanguageList;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

use function SP\__;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly LanguageList $languageList
    ) {
        parent::__construct($webControllerHelper);
    }

    #[Action(ResponseType::PLAIN_TEXT)]
    public function indexAction(): ActionResponse
    {
        $skipInstalled = $this->request->analyzeBool('skipInstalled', false);

        if ($skipInstalled === false && $this->configData->isInstalled()) {
            $this->router->response()->redirect('index.php?r=login');

            return ActionResponse::ok('');
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
            SelectItemAdapter::factory($this->languageList->getAvailableLanguages())
                             ->getItemsFromArraySelected([$this->configData->getSiteLang()])
        );

        return ActionResponse::ok($this->render());
    }
}
