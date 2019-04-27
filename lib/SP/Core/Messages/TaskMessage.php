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

namespace SP\Core\Messages;

use JsonSerializable;

/**
 * Class TaskMessage
 *
 * @package SP\Core\Messages
 */
final class TaskMessage implements MessageInterface, JsonSerializable
{
    /**
     * @var string
     */
    protected $taskId;
    /**
     * @var string
     */
    protected $task;
    /**
     * @var string
     */
    protected $message;
    /**
     * @var int
     */
    protected $time = 0;
    /**
     * @var int
     */
    protected $progress = 0;
    /**
     * @var int
     */
    protected $end = 0;

    /**
     * TaskMessage constructor.
     *
     * @param string $taskId
     * @param string $task
     */
    public function __construct($taskId, $task)
    {
        $this->taskId = $taskId;
        $this->task = $task;
    }

    /**
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param string $task
     *
     * @return TaskMessage
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return TaskMessage
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     *
     * @return TaskMessage
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param int $progress
     *
     * @return TaskMessage
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param int $end
     *
     * @return TaskMessage
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Componer un mensaje en formato texto
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function composeText($delimiter = ';')
    {
        return implode($delimiter, [
            'taskId' => $this->taskId,
            'task' => $this->task,
            'message' => $this->message,
            'time' => $this->time,
            'progress' => $this->progress,
            'end' => $this->end
        ]);
    }

    /**
     * Componer un mensaje en formato HTML
     *
     * @return mixed
     */
    public function composeHtml()
    {
        return [
            'taskId' => $this->taskId,
            'task' => $this->task,
            'message' => $this->message,
            'time' => $this->time,
            'progress' => $this->progress,
            'end' => $this->end
        ];
    }

    /**
     * Componer un mensaje en formato JSON
     */
    public function composeJson()
    {
        return json_encode($this);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @param string $taskId
     *
     * @return TaskMessage
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }
}