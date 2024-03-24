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

namespace SP\Domain\Task\Services;

use Closure;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Task\Ports\TaskServiceInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Util\Util;

/**
 * Class TaskService
 *
 * @package SP\Domain\Common\Services
 */
final class TaskService extends Service implements TaskServiceInterface
{
    /**
     * Time for waiting to initialization
     */
    private const STARTUP_WAIT_TIME = 5;
    /**
     * Initialization attempts
     */
    private const STARTUP_WAIT_COUNT = 30;

    private ?Closure     $messagePusher = null;
    private ?Task        $task          = null;
    private ?string      $taskDirectory = null;
    private ?string      $taskId        = null;
    private ?FileHandler $taskFile      = null;

    /**
     * Track task status
     *
     * @throws \JsonException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function trackStatus(string $taskId, Closure $messagePusher): void
    {
        $this->taskId = $taskId;
        $this->taskDirectory = Util::getTempDir();
        $this->messagePusher = $messagePusher;

        if ($this->taskDirectory === false || !$this->getLock()) {
            throw new ServiceException(__('Unable to create the lock file'));
        }

        $count = 0;

        while (!$this->checkTaskRegistered()
               || !file_exists($this->task->getFileOut()->getFile())
        ) {
            if ($count >= self::STARTUP_WAIT_COUNT) {
                throw new ServiceException(__('Task not set within wait time'));
            }

            logger(
                sprintf(
                    'Waiting for task "%s" (%ds) ...',
                    $taskId,
                    (self::STARTUP_WAIT_COUNT - $count) * self::STARTUP_WAIT_TIME
                )
            );

            $count++;

            sleep(self::STARTUP_WAIT_TIME);
        }

        $this->readTaskStatus();
    }

    /**
     * Get a lock for task execution
     */
    private function getLock(): bool
    {
        $lockFile = new FileHandler($this->taskDirectory.DIRECTORY_SEPARATOR.$this->taskId.'.lock');

        try {
            if ($lockFile->getFileTime() + (self::STARTUP_WAIT_COUNT * self::STARTUP_WAIT_TIME) < time()) {
                $lockFile->delete();
            }
        } catch (FileException $e) {
            processException($e);
        }

        try {
            $lockFile->write(time());

            return true;
        } catch (FileException $e) {
            processException($e);

            return false;
        }
    }

    /**
     * Check whether the task's file has been registered
     */
    private function checkTaskRegistered(): bool
    {
        if (is_object($this->task)) {
            logger('Task detected: '.$this->task->getTaskId());

            return true;
        }

        try {
            $this->taskFile = new FileHandler($this->taskDirectory.DIRECTORY_SEPARATOR.$this->taskId.'.task');
            $this->taskFile->checkFileExists();
            $this->task = unserialize($this->taskFile->readToString());

            return is_object($this->task);
        } catch (FileException $e) {
            return false;
        }
    }

    /**
     * Read a task status and send it back to the browser (messagePusher)
     *
     * @throws \JsonException
     */
    private function readTaskStatus(): void
    {
        logger('Tracking task status: '.$this->task->getTaskId());

        $id = 0;
        $failCount = 0;
        $outputFile = $this->task->getFileOut();

        while ($failCount <= self::STARTUP_WAIT_COUNT
               && $this->checkTaskFile()
        ) {
            try {
                $content = $outputFile->readToString();

                if (!empty($content)) {
                    $this->messagePusher->call($this, $id, $content);
                    $id++;
                } else {
                    $message = TaskFactory::createMessage(
                        $this->task->getTaskId(),
                        __('Waiting for progress updating ...')
                    );

                    logger($message->getTask());

                    $this->messagePusher->call($this, $id, $message->composeJson());

                    $failCount++;
                }
            } catch (FileException $e) {
                processException($e);

                $this->messagePusher->call(
                    $this,
                    $id,
                    TaskFactory::createMessage(
                        $this->task->getTaskId(),
                        $e->getMessage()
                    )->composeJson()
                );

                $failCount++;
            }

            sleep($this->task->getInterval());
        }
    }

    /**
     *  Check whether the task's output file exists
     */
    private function checkTaskFile(): bool
    {
        try {
            $this->taskFile->checkFileExists();

            return true;
        } catch (FileException $e) {
            return false;
        }
    }
}
