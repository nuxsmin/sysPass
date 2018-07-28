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

namespace SP\Services\Task;

use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Util\Util;

/**
 * Class TaskService
 *
 * @package SP\Services
 */
final class TaskService extends Service
{
    /**
     * Tiempo de espera en cada intento de inicialización
     */
    const STARTUP_WAIT_TIME = 5;
    /**
     * Intentos de inicialización
     */
    const STARTUP_WAIT_COUNT = 30;

    /**
     * @var Task Instancia de la tarea
     */
    protected $task;
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
     * Realizar acción
     *
     * @param string $taskId
     *
     * @throws ServiceException
     */
    public function run($taskId)
    {
        $this->taskId = $taskId;
        $this->dir = Util::getTempDir();

        if ($this->dir === false || !$this->getLock()) {
            throw new ServiceException(__('No es posible crear archivo de bloqueo'));
        }

        $this->taskFile = $this->dir . DIRECTORY_SEPARATOR . $this->taskId . '.task';

        $count = 0;

        while (!$this->checkTaskRegistered() || !$this->checkTaskFile()) {
            if ($count >= self::STARTUP_WAIT_COUNT || !file_exists($this->lockFile)) {
                debugLog('Aborting ...');

                die(1);
            } else {
                debugLog(sprintf('Waiting for task "%s" (%ds) ...', $taskId, (self::STARTUP_WAIT_COUNT - $count) * self::STARTUP_WAIT_TIME));

                $count++;
                sleep(self::STARTUP_WAIT_TIME);
            }
        }

        $this->readTaskStatus();

        die(0);
    }

    /**
     * Obtener un bloqueo para la ejecución de la tarea
     *
     * @return bool
     */
    private function getLock()
    {
        $this->lockFile = $this->dir . DIRECTORY_SEPARATOR . $this->taskId . '.lock';

        if (file_exists($this->lockFile)) {
            $timeout = self::STARTUP_WAIT_COUNT * self::STARTUP_WAIT_TIME;

            if (filemtime($this->lockFile) + $timeout < time()) {
                unlink($this->lockFile);

                return $this->updateLock();
            }

            return false;
        }

        return $this->updateLock();
    }

    /**
     * Actualizar el tiempo del archivo de bloqueo
     */
    protected function updateLock()
    {
        return file_put_contents($this->lockFile, time()) !== false;
    }

    /**
     * Comprueba si una tarea ha sido registrada en la sesión
     *
     * @return bool
     */
    protected function checkTaskRegistered()
    {
        if (is_object($this->task)) {
            debugLog('Task detected: ' . $this->task->getTaskId());

            return true;
        }

        if (file_exists($this->taskFile)) {
            $task = file_get_contents($this->taskFile);

            if ($task !== false) {
                $this->task = unserialize($task);
            }

            return is_object($this->task);
        }

        return false;
    }

    /**
     *  Comprobar si el archivo de salida de la tarea existe
     */
    protected function checkTaskFile()
    {
        return file_exists($this->task->getFileOut());
    }

    /**
     * Leer el estado de una tarea y enviarlo
     */
    protected function readTaskStatus()
    {
        debugLog('Tracking task: ' . $this->task->getTaskId());

        $id = 0;
        $failCount = 0;
        $file = $this->task->getFileOut();
        $interval = $this->task->getInterval();

        $taskMessage = TaskFactory::createMessage($this->task->getTaskId(), __('Esperando actualización de progreso ...'));

        while ($failCount <= self::STARTUP_WAIT_COUNT && file_exists($this->taskFile)) {
            $content = file_get_contents($file);

            if (!empty($content)) {
                $this->sendMessage($id, $content);
                $id++;
            } else {
                debugLog($taskMessage->composeJson());

                $this->sendMessage($id, $taskMessage->composeJson());
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
}