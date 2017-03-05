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
 * Class TaskFactory
 *
 * @package SP\Core
 */
class TaskFactory
{
    /**
     * @var TaskMessage
     */
    public static $Message;
    /**
     * @var Task
     */
    private static $Task;

    /**
     * Crear una tarea para la actualización de estado de la actualización
     *
     * @param $name
     * @param $id
     */
    public static function createTask($name, $id)
    {
        if (self::$Task === null) {
            self::$Task = new Task($name, $id);
            self::$Task->register(false);
        }

        self::$Message = new TaskMessage();
        self::$Message->setTaskId($id);
    }

    /**
     * Finalizar la tarea
     */
    public static function endTask()
    {
        if (self::$Task !== null) {
            self::$Task->end(false);
            self::$Task = null;
        }
    }

    /**
     * Enviar un mensaje de actualización a la tarea
     */
    public static function sendTaskMessage()
    {
        if (self::$Task !== null) {
            self::$Task->writeJsonStatusAndFlush(self::$Message);
        }
    }
}