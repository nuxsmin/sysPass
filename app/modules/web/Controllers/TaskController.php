<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\Container;
use Klein\Klein;
use SP\Core\Session\Session;
use SP\Core\Task;
use SP\Core\TaskFactory;
use SP\Services\ServiceException;
use SP\Services\Task\TaskService;

/**
 * Class TaskController
 *
 * @package SP\Modules\Web\Controllers
 */
class TaskController
{
    /**
     * @var Container
     */
    private $container;

    /**
     * TaskController constructor.
     *
     * @param Container $container
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $taskId
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function runTaskAction($taskId)
    {
        Session::close();

        $router = $this->container->get(Klein::class);
        $router->response()->header('Content-Type', 'text/event-stream');
        $router->response()->header('Cache-Control', 'no-store, no-cache');
        $router->response()->header('Access-Control-Allow-Origin', '*');
        $router->response()->send(true);

        try {
            $this->container->get(TaskService::class)->run($taskId);
        } catch (ServiceException $e) {
            processException($e);
        }
    }

    /**
     * @param $taskId
     */
    public function testTaskAction($taskId)
    {
        $task = TaskFactory::create($taskId, Task::genTaskId($taskId));

        TaskFactory::update($task->getTaskId(), TaskFactory::createMessage($task->getTaskId(), 'Prueba Tarea'));

        echo $task->getTaskId();
    }
}