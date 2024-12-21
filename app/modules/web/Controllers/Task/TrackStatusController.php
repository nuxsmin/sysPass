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

namespace SP\Modules\Web\Controllers\Task;

use Klein\Response;
use SP\Domain\Common\Services\ServiceException;

/**
 * Class TrackStatusController
 *
 * @package SP\Modules\Web\Controllers
 */
final class TrackStatusController
{
    private \SP\Domain\Task\Ports\TaskServiceInterface $taskService;
    private Response                                   $response;

    public function __construct(Response $response, \SP\Domain\Task\Ports\TaskServiceInterface $taskService)
    {
        $this->response = $response;
        $this->taskService = $taskService;
    }

    /**
     * @param  string  $taskId
     *
     * @throws \JsonException
     */
    public function trackStatusAction(string $taskId): void
    {
        $this->response->header('Content-Type', 'text/event-stream');
        $this->response->header('Cache-Control', 'no-store, no-cache');
        $this->response->header('Access-Control-Allow-Origin', '*');
        $this->response->send(true);

        ob_end_flush();

        try {
            $this->taskService->trackStatus(
                $taskId,
                function ($id, $message) {
                    echo 'id: ', $id, PHP_EOL, 'data: ', $message, PHP_EOL, PHP_EOL;

                    ob_flush();
                    flush();
                }
            );
        } catch (ServiceException $e) {
            processException($e);
        }
    }
}
