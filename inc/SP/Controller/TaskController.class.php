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
use SP\Core\Session;
use SP\Core\Task;
use SP\Http\Request;

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
     * Realizar acción
     *
     * @return bool
     */
    public function doAction()
    {
        $source = Request::analyze('source');

        if ($this->checkWait($source)) {
            return false;
        }

        $count = 0;

        do {
            session_write_close();

            if ($count >= $this->startupWaitCount) {
                debugLog('Aborting ...');

                $this->removeLock();

                return false;
            }

            debugLog('Waiting task ...');

            sleep($this->startupWaitTime);

            session_start();

            $this->Task = Session::getTask();
            $count++;
        } while (!is_object($this->Task));

        session_write_close();

        if ($this->checkFile()) {
            $this->readTaskStatus();
        }

        $this->removeLock();

        return true;
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

        if (file_exists($this->lockFile) || !touch($this->lockFile)) {
            return true;
        }

        return false;
    }

    /**
     *  Comprobar si el archivo de salida de la tarea existe
     */
    protected function checkFile()
    {
        return file_exists($this->Task->getFile());
    }

    /**
     * Leer el estdo de una tarea y enviarlo
     */
    protected function readTaskStatus()
    {
        $id = 0;
        $failCount = 0;
        $file = $this->Task->getFile();
        $interval = $this->Task->getInterval();

        $Message = new TaskMessage();
        $Message->setTask($this->Task->getTaskId());
        $Message->setMessage(__('Esperando actualización de progreso ...'));

        while ($failCount <= 10) {
            $content = file_get_contents($file);

            if ($content !== false) {
                $this->sendMessage($id, $content);
                $id++;
            } else {
                debugLog($Message->composeText());

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
     * Eliminar bloqueo
     */
    protected function removeLock()
    {
        debugLog(__METHOD__);

        unlink($this->lockFile);
    }
}