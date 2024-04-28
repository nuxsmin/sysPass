<?php
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

namespace SP\Domain\Http\Services;

use JsonException;
use Klein\Response;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Header;
use SP\Domain\Http\Ports\JsonResponseService;

use function SP\__u;

/**
 * Class JsonResponse
 */
final readonly class JsonResponse implements JsonResponseService
{
    /**
     * Json constructor.
     */
    public function __construct(private Response $response)
    {
    }

    public static function factory(Response $response): JsonResponseService
    {
        return new self($response);
    }

    /**
     * Devuelve una respuesta en formato JSON
     *
     * @param string $data JSON string
     *
     * @return bool
     */
    public function sendRaw(string $data): bool
    {
        return $this->response
            ->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value)
            ->body($data)
            ->send(true)
            ->isSent();
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @param JsonMessage $jsonMessage
     *
     * @return bool
     * @throws SPException
     */
    public function send(JsonMessage $jsonMessage): bool
    {
        $this->response->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value);

        try {
            $this->response->body(self::buildJsonFrom($jsonMessage));
        } catch (SPException $e) {
            $jsonMessage = new JsonMessage($e->getMessage());
            $jsonMessage->addMessage($e->getHint());

            $this->response->body(self::buildJsonFrom($jsonMessage));
        }

        return $this->response->send(true)->isSent();
    }

    /**
     * Devuelve una cadena en formato JSON
     *
     * @param mixed $data
     * @param int $flags JSON_* flags
     *
     * @return string
     * @throws SPException
     */
    public static function buildJsonFrom(mixed $data, int $flags = 0): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | $flags);
        } catch (JsonException $e) {
            throw new SPException(__u('Encoding error'), SPException::ERROR, $e->getMessage());
        }
    }
}
