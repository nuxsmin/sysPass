<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Controller;

use SP\Core\Messages\TaskMessage;
use SP\Core\Task;
use SP\Http\Request;
use SP\Util\Util;

/**
 * Class TaskController
 *
 * @package SP\Controller
 */
class TaskController
{
    /**
     * @var Task Instancia de la tarea
     */
    protected $Task;
    /**
     * @var int Tiempo de espera en cada intendo de inicialización
     */
    protected $startupWaitTime = 10;
    /**
     * @var int Intentos de inicialización
     */
    protected $startupWaitCount = 30;
    /**
     * @var string Archivo de bloqueo
     */
    protected $lockFile;
    /**
     * @var string Directorio de las tareas
     */
    protected $dir;
    /**
     * @var string ID de la tarea
     */
    protected $taskId;
    /**
     * @var string Archivo de la tarea
     */
    protected $taskFile;

    /**
     * TaskController constructor.
     */
    public function __construct()
    {
        $this->dir = Util::getTempDir();
        $this->taskId = Request::analyze('taskId');
    }

    /**
     * Realizar acción
     *
     * @return bool
     */
    public function doAction()
    {
        $source = Request::analyze('source');

        if ($this->dir === false || !$this->getLock($source)) {
            return false;
        }

        $this->taskFile = $this->dir . DIRECTORY_SEPARATOR . $this->taskId . '.task';

        $count = 0;

        while (!$this->checkTaskRegistered() || !$this->checkTaskFile()) {
            if ($count >= $this->startupWaitCount) {
                debugLog('Aborting ...');

                die(1);
            }

            debugLog('Waiting for task ...');

            $count++;
            sleep($this->startupWaitTime);
        }

        $this->readTaskStatus();

        die(0);
    }

    /**
     * Comprueba si una tarea ha sido registrada en la sesión
     *
     * @return bool
     */
    protected function checkTaskRegistered()
    {
        if (is_object($this->Task)) {
            debugLog('Task detected: ' . $this->Task->getTaskId());

            return true;
        }

        if (file_exists($this->taskFile)) {
            $task = file_get_contents($this->taskFile);

            if ($task !== false) {
                $this->Task = unserialize($task);
            }

            return is_object($this->Task);
        }

        return false;
    }

    /**
     *  Comprobar si el archivo de salida de la tarea existe
     */
    protected function checkTaskFile()
    {
        return file_exists($this->Task->getFileOut());
    }

    /**
     * Leer el estado de una tarea y enviarlo
     */
    protected function readTaskStatus()
    {
        debugLog('Tracking task: ' . $this->Task->getTaskId());

        $id = 0;
        $failCount = 0;
        $file = $this->Task->getFileOut();
        $interval = $this->Task->getInterval();

        $Message = new TaskMessage();
        $Message->setTask($this->Task->getTaskId());
        $Message->setMessage(__('Esperando actualización de progreso ...'));

        while ($failCount <= 30 && file_exists($this->taskFile)) {
            $content = file_get_contents($file);

            if (!empty($content)) {
                $this->sendMessage($id, $content);
                $id++;
            } else {
                debugLog($Message->composeJson());

                $this->sendMessage($id, $Message->composeJson());
                $failCount++;
            }

            sleep($interval);
        }
    }

    /**
     * Enviar un mensaje
     *
     * @param $id
     * @param $message
     */
    protected function sendMessage($id, $message)
    {
        echo 'id: ', $id, PHP_EOL, 'data: ', $message, PHP_EOL, PHP_EOL;

        ob_flush();
        flush();
    }

    /**
     * Comprobar si hay una tarea a la espera
     *
     * @param $source
     * @return bool
     */
    protected function checkWait($source)
    {
        $this->lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $source . '.lock';

        if (file_exists($this->lockFile)) {
            $timeout = $this->startupWaitCount * $this->startupWaitTime;

            if (filemtime($this->lockFile) + $timeout < time()) {
                unlink($this->lockFile);

                return false;
            }

            return true;
        }

        touch($this->lockFile);

        return false;
    }

    /**
     * Eliminar bloqueo
     */
    protected function removeLock()
    {
        debugLog(__METHOD__);

        unlink($this->lockFile);
    }

    /**
     * Obtener un bloqueo para la ejecución de la tarea
     *
     * @param $source
     *
     * @return bool
     */
    private function getLock($source)
    {
        if ($source === '') {
            $source = 'task';
        }

        $this->lockFile = $this->dir . DIRECTORY_SEPARATOR . $source . '.lock';

        if (file_exists($this->lockFile)) {
            $timeout = $this->startupWaitCount * $this->startupWaitTime;

            if (filemtime($this->lockFile) + $timeout < time()) {
                unlink($this->lockFile);

                return $this->updateLock();
            }

            return false;
        } else {
            return $this->updateLock();
        }
    }

    /**
     * Actualizar el tiempo del archivo de bloqueo
     */
    protected function updateLock()
    {
        return file_put_contents($this->lockFile, time()) !== false;
    }
}