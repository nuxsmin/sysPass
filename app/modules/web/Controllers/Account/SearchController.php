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

namespace SP\Modules\Web\Controllers\Account;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\Helpers\Account\AccountSearchHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * SearchController
 */
final class SearchController extends AccountControllerBase
{
    public function __construct(
        Application                          $application,
        WebControllerHelper                  $webControllerHelper,
        private readonly AccountSearchHelper $accountSearchHelper
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function searchAction(): ActionResponse
    {
        $this->accountSearchHelper->getAccountSearch();

        $this->eventDispatcher->notify('show.account.search', new Event($this));

        return ActionResponse::ok('', ['html' => $this->render()]);
    }
}
