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

namespace SP\Tests\Modules\Api;

use SP\Services\Api\JsonRpcResponse;
use SP\Tests\WebTestCase;
use Symfony\Component\BrowserKit\Response;

/**
 * Class ApiTest
 *
 * @package SP\Tests\SP\Modules\Api
 */
class ApiTest extends WebTestCase
{

    const API_TOKEN = 'ca28f2ad2af09064ce0bfc2aff144cacb4a48df09b0978c4b6dc3970db7b2c48';
    const API_PASS = '123456';
    const API_URL = 'http://syspass-app-test/api.php';

    public function testInvalidRequest()
    {
        $client = self::postJson(self::API_URL);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('2.0', $result->jsonrpc);
        $this->assertEquals(JsonRpcResponse::INVALID_REQUEST, $result->error->code);
        $this->assertNull($result->error->data);
        $this->assertEquals(0, $result->id);
    }

    public function testNoInvalidToken()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => 'this_is_a_test'
            ],
            'id' => 1
        ];

        $client = self::postJson(self::API_URL, $data);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('2.0', $result->jsonrpc);
        $this->assertEquals('Internal error', $result->error->message);
        $this->assertEquals(0, $result->error->code);
        $this->assertNull($result->error->data);
        $this->assertEquals(1, $result->id);
    }

    public function testNoInvalidMethod()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'test/test',
            'params' => [
                'authToken' => 'this_is_a_test'
            ],
            'id' => 1
        ];

        $client = self::postJson(self::API_URL, $data);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('2.0', $result->jsonrpc);
        $this->assertEquals('Oops, it looks like this content doesn\'t exist...', $result->error->message);
        $this->assertEquals(JsonRpcResponse::METHOD_NOT_FOUND, $result->error->code);
        $this->assertNull($result->error->data);
        $this->assertEquals(1, $result->id);
    }
}