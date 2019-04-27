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

namespace SP\Services\Task;

use SP\Core\Context\SessionContext;
use SP\Core\Messages\TaskMessage;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\Util;

/**
 * Class Task
 *
 * @package SP\Core
 */
final class Task
{
    /**
     * @var string Nombre de la tarea
     */
    private $name;
    /**
     * @var string ID de la tarea
     */
    private $taskId;
    /**
     * @var FileHandler
     */
    private $fileOut;
    /**
     * @var FileHandler
     */
    private $fileTask;
    /**
     * @var int Intérvalo en segundos
     */
    private $interval = 5;
    /**
     * @var bool Si se ha inicializado para escribir en el archivo
     */
    private $initialized = false;
    /**
     * @var string
     */
    private $uid;

    /**
     * Task constructor.
     *
     * @param string $name Nombre de la tarea
     * @param string $id
     */
    public function __construct($name, $id)
    {
        $this->name = $name;
        $this->taskId = $id;
        $this->initialized = $this->checkFile();
        $this->uid = $this->genUid();
    }

    /**
     * Comprobar si se puede escribir en el archivo
     *
     * @return bool
     */
    private function checkFile()
    {
        $tempDir = Util::getTempDir();

        if ($tempDir !== false) {
            $this->fileOut = new FileHandler($tempDir . DIRECTORY_SEPARATOR . $this->taskId . '.out');
            $this->fileTask = new FileHandler($tempDir . DIRECTORY_SEPARATOR . $this->taskId . '.task');

            $this->deleteTaskFiles();

            return true;
        }

        return false;
    }

    /**
     * Eliminar los archivos de la tarea no usados
     */
    private function deleteTaskFiles()
    {
        $filesOut = dirname($this->fileOut->getFile()) . DIRECTORY_SEPARATOR . $this->taskId . '*.out';
        $filesTask = dirname($this->fileTask->getFile()) . DIRECTORY_SEPARATOR . $this->taskId . '*.task';

        array_map('unlink', array_merge(glob($filesOut), glob($filesTask)));
    }

    /**
     * @return string
     */
    public function genUid()
    {
        return md5($this->name . $this->taskId);
    }

    /**
     * Generar un ID de tarea
     *
     * @param $name
     *
     * @return string
     */
    public static function genTaskId($name)
    {
        return uniqid($name);
    }

    /**
     * Escribir el tado de la tarea a un archivo
     *
     * @param TaskMessage $message
     *
     * @return bool
     */
    public function writeStatusAndFlush(TaskMessage $message)
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
     *
     * @param TaskMessage $message
     */
    public function writeJsonStatusAndFlush(TaskMessage $message)
    {
        try {
            if ($this->initialized === true) {
                $this->fileOut->save($message->composeJson());
            }
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * Iniciar la tarea
     */
    public function end()
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
    public function unregister()
    {
        logger("Unregister Task: {$this->name}");

        $this->fileTask->delete();
    }

    /**
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param int $interval
     *
     * @return Task
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * @return string
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @return FileHandler
     */
    public function getFileOut(): FileHandler
    {
        return $this->fileOut;
    }

    /**
     * Register a task
     *
     * @return Task
     * @throws FileException
     */
    public function register()
    {
        logger("Register Task: {$this->name}");

        $this->fileTask->save(serialize($this));

        return $this;
    }

    /**
     * Register a task
     *
     * Session is locked in order to allow other scripts execution
     *
     * @return Task
     * @throws FileException
     */
    public function registerSession()
    {
        logger("Register Task (session): {$this->name}");

        $this->fileTask->save(serialize($this));

        SessionContext::close();

        return $this;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @return FileHandler
     */
    public function getFileTask(): FileHandler
    {
        return $this->fileTask;
    }
}