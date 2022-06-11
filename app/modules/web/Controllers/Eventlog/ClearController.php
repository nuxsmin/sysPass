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

namespace SP\Modules\Web\Controllers\Eventlog;


use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Security\EventlogServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ClearController
 */
final class ClearController extends ControllerBase
{
    use JsonTrait;

    private EventlogServiceInterface $eventlogService;

    /**
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \SP\Domain\Auth\Services\AuthException
     * @throws \JsonException
     */
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        EventlogServiceInterface $eventlogService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->eventlogService = $eventlogService;
    }


    /**
     * @return bool
     * @throws \JsonException
     */
    public function clearAction(): bool
    {
        try {
            $this->eventlogService->clear();

            $this->eventDispatcher->notifyEvent(
                'clear.eventlog',
                new Event($this, EventMessage::factory()->addDescription(__u('Event log cleared')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Event log cleared'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}