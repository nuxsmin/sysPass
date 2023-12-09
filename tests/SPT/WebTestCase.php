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

namespace SPT;

use Goutte\Client;
use JsonException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\BrowserKit\Response;

/**
 * Class WebTestCase
 */
abstract class WebTestCase extends TestCase
{
    /**
     * @throws JsonException
     */
    protected static function postJson(string $url, $content = ''): Client
    {
        $client = self::createClient();
        $client->request(
            'POST',
            $url,
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($content, JSON_THROW_ON_ERROR)
        );

        return $client;
    }

    protected static function createClient(array $server = []): Client
    {
        return new Client($server);
    }

    protected static function checkAndProcessJsonResponse(
        Client $client,
        int    $httpCode = 200
    ): stdClass
    {
        /** @var Response $response */
        $response = $client->getResponse();

        self::assertEquals($httpCode, $response->getStatus());
        self::assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        return json_decode(
            $response->getContent(),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
