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
use stdClass;

/**
 * Class ClientControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
class ClientControllerTest extends WebTestCase
{
    /**
     * @return int
     */
    public function testCreateAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/create',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'name' => 'API Client',
                'description' => "API test\ndescription",
                'global' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals(4, $result->result->itemId);
        $this->assertEquals('Client added', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);

        return $result->result->itemId;
    }

    /**
     * @depends testCreateAction
     *
     * @param $id
     */
    public function testViewAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/view',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals($id, $result->result->result->id);
        $this->assertEquals('API Client', $result->result->result->name);
        $this->assertEquals("API test\ndescription", $result->result->result->description);
        $this->assertEquals(1, $result->result->result->isGlobal);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testEditAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/edit',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
                'name' => 'API Client edit',
                'description' => "API test\ndescription\nedit",
                'global' => 0
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('Client updated', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
    }

    public function testSearchAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(4, $result->result->count);
        $this->assertCount(4, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'count' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);
    }

    public function testSearchByTextAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'text' => 'API Client edit'
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);
        $this->assertEquals('API Client edit', $result->result->result[0]->name);
        $this->assertEquals("API test\ndescription\nedit", $result->result->result[0]->description);
        $this->assertEquals(0, $result->result->result[0]->isGlobal);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testDeleteAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'client/delete',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('Client deleted', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
    }
}
