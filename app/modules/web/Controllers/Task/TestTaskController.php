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


use SP\Domain\Task\Services\TaskFactory;
use SP\Infrastructure\File\FileException;

/**
 * Class TestTaskController
 */
final class TestTaskController
{
    /**
     * @param  string  $taskId
     *
     * @throws FileException
     */
    public function testTaskAction(string $taskId): void
    {
        $task = TaskFactory::create($taskId, $taskId);

        echo $task->getTaskId();

        $count = 0;

        while ($count < 60) {
            TaskFactory::update($task, TaskFactory::createMessage($task->getTaskId(), "Test Task $count"));

            sleep(1);
            $count++;
        }

        TaskFactory::end($task);
    }
}