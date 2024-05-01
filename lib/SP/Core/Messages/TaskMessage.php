<?php
declare(strict_types=1);
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

namespace SP\Core\Messages;

use JsonSerializable;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Messages\MessageInterface;

/**
 * Class TaskMessage
 *
 * @package SP\Core\Messages
 */
final class TaskMessage implements MessageInterface, JsonSerializable
{
    protected ?string $message  = null;
    protected int     $time     = 0;
    protected int     $progress = 0;
    protected int     $end      = 0;

    public function __construct(private string $taskId, private string $task)
    {
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function setTask(string $task): TaskMessage
    {
        $this->task = $task;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): TaskMessage
    {
        $this->message = $message;

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): TaskMessage
    {
        $this->time = $time;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): TaskMessage
    {
        $this->progress = $progress;

        return $this;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function setEnd(int $end): TaskMessage
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Componer un mensaje en formato HTML
     */
    public function composeHtml(): string
    {
        return $this->composeText();
    }

    /**
     * Componer un mensaje en formato texto
     */
    public function composeText(string $delimiter = ';'): string
    {
        return implode($delimiter, [
            'taskId' => $this->taskId,
            'task' => $this->task,
            'message' => $this->message,
            'time' => $this->time,
            'progress' => $this->progress,
            'end' => $this->end,
        ]);
    }

    /**
     * Componer un mensaje en formato JSON
     *
     * @throws SPException
     */
    public function composeJson(): bool|string
    {
        return Serde::serializeJson($this);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setTaskId(string $taskId): TaskMessage
    {
        $this->taskId = $taskId;

        return $this;
    }
}
