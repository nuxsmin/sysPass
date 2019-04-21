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
use stdClass;

/**
 * Class ApiTest
 *
 * @package SP\Tests\SP\Modules\Api
 */
class ApiTest extends WebTestCase
{

    const API_TOKEN = '4eb7a989fab4c8fd9ade0ea80df7032d5ee78d4496c1c10f9c4388a872bfff28';
    const API_PASS = ')%Iykm*4A]wg';
    const API_URL = 'http://syspass-app-test/api.php';

    public function testInvalidRequest()
    {
        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL), 404);

        $this->assertInstanceOf(stdClass::class, $result);
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

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
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

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data), 404);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('2.0', $result->jsonrpc);
        $this->assertEquals(JsonRpcResponse::METHOD_NOT_FOUND, $result->error->code);
        $this->assertNull($result->error->data);
        $this->assertEquals(1, $result->id);
    }
}