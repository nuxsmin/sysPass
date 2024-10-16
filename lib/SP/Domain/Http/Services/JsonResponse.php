<?php

declare(strict_types=1);
/**
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

use Klein\Response;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Header;
use SP\Domain\Http\Ports\JsonResponseService;

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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function send(JsonMessage $jsonMessage): bool
    {
        $this->response->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value);

        try {
            $this->response->body(Serde::serializeJson($jsonMessage));
        } catch (SPException $e) {
            $jsonMessage = new JsonMessage($e->getMessage());

            if ($e->getHint()) {
                $jsonMessage->addMessage($e->getHint());
            }

            $this->response->body(Serde::serializeJson($jsonMessage));
        }

        return $this->response->send(true)->isSent();
    }
}
