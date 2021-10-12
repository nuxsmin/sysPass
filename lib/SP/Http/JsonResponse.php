<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Http;

use JsonSerializable;
use stdClass;

/**
 * Class Json para definir la estructura de una respuesta en formato JSON
 *
 * @package SP\Http
 */
final class JsonResponse implements JsonSerializable
{
    public const JSON_SUCCESS = 0;
    public const JSON_SUCCESS_STICKY = 100;
    public const JSON_ERROR = 1;
    public const JSON_ERROR_STICKY = 101;
    public const JSON_WARNING = 2;
    public const JSON_WARNING_STICKY = 102;
    public const JSON_LOGOUT = 10;

    protected int $status = 1;
    protected ?string $description = null;
    protected string $action = '';
    protected array $data = [];
    protected array $messages = [];
    protected string $container = '';
    protected string $csrf = '';

    /**
     * JsonResponse constructor.
     */
    public function __construct(?string $description = null)
    {
        $this->description = $description;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): JsonResponse
    {
        $this->status = $status;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): JsonResponse
    {
        $this->description = __($description);

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): JsonResponse
    {
        $this->action = $action;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array|stdClass $data
     */
    public function setData($data): JsonResponse
    {
        $this->data = $data;

        return $this;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function setMessages(array $messages): JsonResponse
    {
        $this->messages = array_map('__', $messages);

        return $this;
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    public function setContainer(string $container): JsonResponse
    {
        $this->container = $container;

        return $this;
    }

    public function getCsrf(): string
    {
        return $this->csrf;
    }

    public function setCsrf(string $csrf): JsonResponse
    {
        $this->csrf = $csrf;

        return $this;
    }

    public function addMessage(string $message): JsonResponse
    {
        $this->messages[] = __($message);
        return $this;
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function addParam($param): JsonResponse
    {
        if (is_numeric($param)) {
            $param = (int)$param;
        }

        $this->data[] = $param;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getJsonArray();
    }

    /**
     * Devolver un array con las propiedades del objeto
     */
    public function getJsonArray(): array
    {
        $out = [];

        foreach ($this as $key => $value) {
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Establecer los valores por defecto
     */
    public function clear(): JsonResponse
    {
        $this->status = 0;
        $this->action = '';
        $this->data = [];
        $this->messages = [];
        $this->container = '';
        $this->csrf = '';

        return $this;
    }
}