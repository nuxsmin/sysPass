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

use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Services\ServiceException;
use SP\Services\Task\TaskFactory;
use SP\Services\Task\TaskService;
use SP\Storage\File\FileException;

/**
 * Class TaskController
 *
 * @package SP\Modules\Web\Controllers
 */
final class TaskController
{
    /**
     * @var TaskService
     */
    private $taskService;
    /**
     * @var Klein
     */
    private $router;

    /**
     * TaskController constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->router = $container->get(Klein::class);
        $this->taskService = $container->get(TaskService::class);
    }

    /**
     * @param string $taskId
     */
    public function trackStatusAction($taskId)
    {
        $response = $this->router->response();
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-store, no-cache');
        $response->header('Access-Control-Allow-Origin', '*');
        $response->send(true);

        ob_end_flush();

        try {
            $this->taskService->trackStatus($taskId,
                function ($id, $message) {
                    echo 'id: ', $id, PHP_EOL, 'data: ', $message, PHP_EOL, PHP_EOL;

                    ob_flush();
                    flush();
                });
        } catch (ServiceException $e) {
            processException($e);
        }
    }

    /**
     * @param $taskId
     *
     * @throws FileException
     */
    public function testTaskAction($taskId)
    {
        $task = TaskFactory::create($taskId, $taskId);

        echo $task->getTaskId();

        $count = 0;

        while ($count < 60) {
            TaskFactory::update($task,
                TaskFactory::createMessage($task->getTaskId(), "Test Task $count")
            );

            sleep(1);
            $count++;
        }

        TaskFactory::end($task);
    }
}