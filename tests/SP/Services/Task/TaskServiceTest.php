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

namespace SP\Tests\Services\Task;

use PHPUnit\Framework\TestCase;
use SP\Core\Context\ContextException;
use SP\Services\Task\Task;
use SP\Services\Task\TaskFactory;
use SP\Services\Task\TaskService;
use SP\Storage\File\FileException;
use function SP\Tests\setupContext;

/**
 * Class TaskServiceTest
 *
 * @package SP\Tests\Services\Task
 */
class TaskServiceTest extends TestCase
{
    private static $pids = [];

    /**
     * @throws FileException
     * @throws ContextException
     */
    public function testTrackStatus()
    {
        $this->markTestSkipped();

        $task = TaskFactory::create(__FUNCTION__, Task::genTaskId(__FUNCTION__));

        $this->assertFileExists($task->getFileTask()->getFile());

        TaskFactory::update($task,
            TaskFactory::createMessage($task->getTaskId(), "Test Task (INIT)")
        );

        $dic = setupContext();

        $this->fork(function () use ($task, $dic) {
            $taskService = new TaskService($dic);

            $taskService->trackStatus($task->getTaskId(),
                function ($id, $message) {
                    logger("id: $id; data: $message");
                });
        });

        $this->fork(function () use ($task) {
            $count = 0;

            while ($count < 2) {
                sleep(10);

                TaskFactory::update($task,
                    TaskFactory::createMessage($task->getTaskId(), "Test Task #$count")
                );

                $count++;
            }

            TaskFactory::end($task);
        });

        while (count(self::$pids) > 0) {
            foreach (self::$pids as $key => $pid) {
                $res = pcntl_waitpid($pid, $status);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    unset(self::$pids[$key]);
                }
            }

            sleep(1);
        }

        $this->assertFileNotExists($task->getFileTask()->getFile());
        $this->assertFileNotExists($task->getFileOut()->getFile());
    }

    /**
     * Fork for running a piece of code in child process
     *
     * @param callable $code
     */
    private function fork(callable $code)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('Could not fork');
        } elseif ($pid === 0) {
            echo "Child execution\n";

            $code();

            exit();
        } else {
            echo "Child $pid\n";

            self::$pids[] = $pid;
        }
    }
}
