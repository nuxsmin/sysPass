<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Task\Ports;

use SP\Core\Messages\TaskMessage;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\File\FileException;

/**
 * Class Task
 *
 * @package SP\Core
 */
interface TaskInterface
{
    public function genUid(): string;

    /**
     * Escribir el tado de la tarea a un archivo
     */
    public function writeStatusAndFlush(TaskMessage $message): bool;

    /**
     * Escribir un mensaje en el archivo de la tarea en formato JSON
     */
    public function writeJsonStatusAndFlush(TaskMessage $message): void;

    /**
     * Iniciar la tarea
     */
    public function end(): void;

    /**
     * Desregistrar la tarea en la sesión
     *
     * @throws FileException
     */
    public function unregister(): void;

    public function getInterval(): int;

    public function setInterval(int $interval): TaskInterface;

    public function getTaskId(): string;

    public function getFileOut(): ?FileHandlerInterface;

    /**
     * Register a task
     *
     * @throws FileException
     */
    public function register(): TaskInterface;

    /**
     * Register a task
     *
     * Session is locked in order to allow other scripts execution
     *
     * @throws FileException
     */
    public function registerSession(): TaskInterface;

    public function getUid(): string;

    public function getFileTask(): ?FileHandlerInterface;
}
