<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use function SP\__;

/**
 * Class Json para definir la estructura de una respuesta en formato JSON
 */
final class JsonMessage implements JsonSerializable
{
    public const JSON_SUCCESS        = 0;
    public const JSON_SUCCESS_STICKY = 100;
    public const JSON_ERROR          = 1;
    public const JSON_WARNING        = 2;
    protected int     $status      = self::JSON_ERROR;
    protected ?string $description = null;
    protected array   $data        = [];
    protected array   $messages    = [];

    /**
     * JsonResponse constructor.
     */
    public function __construct(?string $description = null)
    {
        $this->description = $description;
    }

    public function setStatus(int $status): JsonMessage
    {
        $this->status = $status;

        return $this;
    }

    public function setDescription(string $description): JsonMessage
    {
        $this->description = __($description);

        return $this;
    }

    /**
     * @param array|stdClass $data
     * @return JsonMessage
     */
    public function setData(array|stdClass $data): JsonMessage
    {
        $this->data = $data;

        return $this;
    }

    public function setMessages(array $messages): JsonMessage
    {
        $this->messages = array_map('__', $messages);

        return $this;
    }

    public function addMessage(string $message): JsonMessage
    {
        $this->messages[] = __($message);
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
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
}
