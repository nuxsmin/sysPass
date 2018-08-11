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

namespace SP\Tests\Modules\Api\Controllers;

use SP\Tests\Modules\Api\ApiTest;
use SP\Tests\WebTestCase;
use Symfony\Component\BrowserKit\Response;

/**
 * Class AccountControllerTest
 *
 * @package SP\Modules\Api\Controllers
 */
class AccountControllerTest extends WebTestCase
{
    public function testCreateAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'name' => 'API test',
                'categoryId' => 1,
                'clientId' => 1,
                'login' => 'root',
                'pass' => 'password_test',
                'passDateChange' => time() + 86400,
                'url' => 'http://syspass.org',
                'notes' => "test\ntest",
                'isPrivate' => 1,
                'isPrivateGroup' => 1,
            ],
            'id' => 1
        ];

        $client = self::postJson(ApiTest::API_URL, $data);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(2, $result->result->result);
        $this->assertEquals(1, $result->result->result->itemId);
        $this->assertNotEmpty($result->result->result->password);
    }

    public function testViewPassAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'id' => 1,
            ],
            'id' => 1
        ];

        $client = self::postJson(ApiTest::API_URL, $data);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(2, $result->result->result);
        $this->assertEquals(1, $result->result->result->itemId);
        $this->assertNotEmpty($result->result->result->password);
    }

    public function testSearchAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN
            ],
            'id' => 1
        ];

        $client = self::postJson(ApiTest::API_URL, $data);

        /** @var Response $response */
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeader('Content-Type'));

        $result = json_decode($response->getContent());

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(3, $result->result->count);
        $this->assertCount(3, $result->result->result);
    }

    public function testDeleteAction()
    {
        $this->markTestSkipped();
    }

    public function testViewAction()
    {
        $this->markTestSkipped();
    }
}
