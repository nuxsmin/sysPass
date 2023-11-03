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


use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Modules\Web\Controllers\Helpers\Account\AccountSearchHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * SearchController
 */
final class SearchController extends AccountControllerBase
{
    use JsonTrait;

    private AccountSearchHelper $accountSearchHelper;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountSearchHelper $accountSearchHelper
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountSearchHelper = $accountSearchHelper;
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function searchAction(): ?bool
    {
        try {
            $this->accountSearchHelper->getAccountSearch();

            $this->eventDispatcher->notify('show.account.search', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
