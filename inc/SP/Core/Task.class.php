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

namespace SP\Core;

use SP\Core\Messages\TaskMessage;

/**
 * Class Task
 *
 * @package SP\Core
 */
class Task
{
    /**
     * @var string Nombre de la tarea
     */
    protected $name;
    /**
     * @var string ID de la tarea
     */
    protected $taskId;
    /**
     * @var string Ruta y archivo de la tarea
     */
    protected $file;
    /**
     * @var resource Manejador del archivo
     */
    protected $fileHandler;
    /**
     * @var int Intérvalo en segundos
     */
    protected $interval = 5;
    /**
     * @var bool Si se ha inicializado para escribir en el archivo
     */
    protected $initialized = false;

    /**
     * Task constructor.
     *
     * @param string $name Nombre de la tarea
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->taskId = uniqid($this->name, true);
        $this->initialized = $this->checkFile();
    }

    /**
     * Comprobar si se puede escribir en el archivo
     *
     * @return bool
     */
    protected function checkFile()
    {
        $fileName = $this->taskId . '.out';
        $fileTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;

        if (touch($fileTmp)) {
            $this->file = $fileTmp;
        } else {
            $fileTmpAlt = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $fileName;
            $dirAlt = dirname($fileTmpAlt);

            if (!file_exists($dirAlt) && !mkdir($dirAlt)) {
                return false;
            }

            if (!touch($fileTmpAlt)) {
                return false;
            }

            $this->file = $fileTmpAlt;
        }

        debugLog('Start Task: ' . $this->name);

        return true;
    }

    /**
     * Iniciar la tarea
     *
     * @return bool
     */
    public function start()
    {
        return $this->openFile();
    }

    /**
     * Abrir el archivo para escritura
     *
     * @return  bool
     */
    protected function openFile()
    {
        if ($this->initialized === false
            || !$this->fileHandler = fopen($this->file, 'wb')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Escribir el tado de la tarea a un archivo
     *
     * @param TaskMessage $Message
     * @return bool
     */
    public function writeStatus(TaskMessage $Message)
    {
        if ($this->initialized === false
            || !is_resource($this->fileHandler)
        ) {
            return false;
        }

        fwrite($this->fileHandler, $Message->composeText());

        return true;
    }

    /**
     * Escribir el tado de la tarea a un archivo
     *
     * @param TaskMessage $Message
     * @return bool
     */
    public function writeStatusAndFlush(TaskMessage $Message)
    {
        return $this->initialized === true
            && !is_resource($this->fileHandler)
            && file_put_contents($this->file, $Message->composeText()) !== false;
    }

    /**
     * Escribir un mensaje en el archivo de la tarea en formato JSON
     *
     * @param TaskMessage $Message
     * @return bool
     */
    public function writeJsonStatusAndFlush(TaskMessage $Message)
    {
        return $this->initialized === true
            && !is_resource($this->fileHandler)
            && file_put_contents($this->file, $Message->composeJson()) !== false;
    }

    /**
     * Iniciar la tarea
     *
     * @return bool
     */
    public function end()
    {
        debugLog('End Task: ' . $this->name);

        return $this->closeFile() && @unlink($this->file);
    }

    /**
     * Abrir el archivo para escritura
     *
     * @return  bool
     */
    protected function closeFile()
    {
        if ($this->initialized === true && is_resource($this->fileHandler)) {
            return fclose($this->fileHandler);
        }

        return $this->initialized;
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
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;
    }

    /**
     * @return string
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
}