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

namespace SP\Domain\Task\Services;

use JsonException;
use SP\Core\Context\Session;
use SP\Core\Messages\TaskMessage;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Task\Ports\TaskInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Util\FileSystem;
use SP\Util\Serde;

use function SP\logger;
use function SP\processException;

/**
 * Class Task
 *
 * @package SP\Core
 */
final class Task implements TaskInterface
{
    private ?FileHandler $fileOut  = null;
    private ?FileHandler $fileTask = null;
    /**
     * @var int Intérvalo en segundos
     */
    private int $interval = 5;
    /**
     * @var bool Si se ha inicializado para escribir en el archivo
     */
    private bool   $initialized;
    private string $uid;

    /**
     * Task constructor.
     *
     * @param  string  $name  Nombre de la tarea
     * @param  string  $taskId
     */
    public function __construct(private string $name, private string $taskId)
    {
        $this->initialized = $this->checkFile();
        $this->uid = $this->genUid();
    }

    /**
     * Comprobar si se puede escribir en el archivo
     */
    private function checkFile(): bool
    {
        $tempDir = FileSystem::getTempDir();

        if ($tempDir !== false) {
            $this->fileOut = new FileHandler(
                $tempDir.
                DIRECTORY_SEPARATOR.
                $this->taskId.
                '.out'
            );
            $this->fileTask = new FileHandler(
                $tempDir.
                DIRECTORY_SEPARATOR.
                $this->taskId.
                '.task'
            );

            $this->deleteTaskFiles();

            return true;
        }

        return false;
    }

    /**
     * Eliminar los archivos de la tarea no usados
     */
    private function deleteTaskFiles(): void
    {
        $filesOut =
            dirname($this->fileOut->getFile()).
            DIRECTORY_SEPARATOR.
            $this->taskId.
            '*.out';
        $filesTask =
            dirname($this->fileTask->getFile()).
            DIRECTORY_SEPARATOR.
            $this->taskId.
            '*.task';

        array_map(
            'unlink',
            array_merge(glob($filesOut), glob($filesTask))
        );
    }

    public function genUid(): string
    {
        return md5($this->name.$this->taskId);
    }

    /**
     * Generar un ID de tarea
     */
    public static function genTaskId(string $name): string
    {
        return uniqid($name, true);
    }

    /**
     * Escribir el tado de la tarea a un archivo
     */
    public function writeStatusAndFlush(TaskMessage $message): bool
    {
        try {
            if ($this->initialized === true) {
                $this->fileOut->save($message->composeText());

                return true;
            }
        } catch (FileException $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Escribir un mensaje en el archivo de la tarea en formato JSON
     */
    public function writeJsonStatusAndFlush(TaskMessage $message): void
    {
        try {
            if ($this->initialized === true) {
                $this->fileOut->save($message->composeJson());
            }
        } catch (FileException|JsonException $e) {
            processException($e);
        }
    }

    /**
     * Iniciar la tarea
     */
    public function end(): void
    {
        try {
            logger("End Task: {$this->name}");

            $this->unregister();

            $this->fileOut->delete();
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * Desregistrar la tarea en la sesión
     *
     * @throws FileException
     */
    public function unregister(): void
    {
        logger("Unregister Task: $this->name");

        $this->fileTask->delete();
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval(int $interval): TaskInterface
    {
        $this->interval = $interval;

        return $this;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getFileOut(): ?FileHandlerInterface
    {
        return $this->fileOut;
    }

    /**
     * Register a task
     *
     * @throws FileException
     */
    public function register(): TaskInterface
    {
        logger("Register Task: $this->name");

        $this->fileTask->save(Serde::serialize($this));

        return $this;
    }

    /**
     * Register a task
     *
     * Session is locked in order to allow other scripts execution
     *
     * @throws FileException
     */
    public function registerSession(): TaskInterface
    {
        logger("Register Task (session): $this->name");

        $this->fileTask->save(Serde::serialize($this));

        Session::close();

        return $this;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getFileTask(): ?FileHandlerInterface
    {
        return $this->fileTask;
    }
}
