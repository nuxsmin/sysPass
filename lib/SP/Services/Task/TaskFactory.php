<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Task;

use RuntimeException;
use SP\Core\Messages\TaskMessage;
use SP\Storage\File\FileException;

/**
 * Class TaskFactory
 *
 * @package SP\Core
 */
final class TaskFactory
{
    /**
     * @var Task[]
     */
    private static array $tasks = [];

    /**
     * Crear una tarea para la actualización de estado de la actualización
     *
     * @throws FileException
     */
    public static function create(
        string $name,
        string $id,
        bool   $hasSession = true
    ): Task
    {
        $task = self::add((new Task($name, $id)));

        if ($hasSession) {
            return $task->registerSession();
        }

        return $task->register();
    }

    private static function add(Task $task): Task
    {
        if (!isset(self::$tasks[$task->getUid()])) {
            self::$tasks[$task->getUid()] = $task;

            return $task;
        }

        throw new RuntimeException('Task already registered');
    }

    /**
     * Finalizar la tarea
     */
    public static function end(Task $task): void
    {
        self::get($task->getUid())
            ->end();

        self::delete($task->getUid());
    }

    private static function get(string $id): Task
    {
        if (isset(self::$tasks[$id])) {
            return self::$tasks[$id];
        }

        throw new RuntimeException('Task not registered');
    }

    private static function delete(string $id): void
    {
        if (isset(self::$tasks[$id])) {
            unset(self::$tasks[$id]);
        }
    }

    public static function createMessage(
        string $taskId,
        string $task
    ): TaskMessage
    {
        return new TaskMessage($taskId, $task);
    }

    /**
     * Enviar un mensaje de actualización a la tarea
     */
    public static function update(Task $task, TaskMessage $taskMessage): void
    {
        self::get($task->getUid())
            ->writeJsonStatusAndFlush($taskMessage);
    }
}