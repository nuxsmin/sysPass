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
 * Class UserGroupControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
class UserGroupControllerTest extends WebTestCase
{
    /**
     * @return int
     */
    public function testCreateAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'userGroup/create',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'name' => 'API UserGroup',
                'description' => "API test\ndescription",
                'usersId' => [1]
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals(2, $result->result->itemId);
        $this->assertEquals('Group added', $result->result->resultMessage);
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
            'method' => 'userGroup/view',
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
        $this->assertEquals('API UserGroup', $result->result->result->name);
        $this->assertEquals("API test\ndescription", $result->result->result->description);
        $this->assertArrayHasKey(0, $result->result->result->users);
        $this->assertEquals(1, $result->result->result->users[0]);
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
            'method' => 'userGroup/edit',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
                'name' => 'API UserGroup edit',
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('Group updated', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     *
     * @return int
     */
    public function testEditActionNoUsers($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'userGroup/edit',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
                'name' => 'API UserGroup edit',
                'usersId' => []
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('Group updated', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);

        return $id;
    }

    /**
     * @depends testEditActionNoUsers
     *
     * @param $id
     */
    public function testViewActionNoUsers($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'userGroup/view',
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
        $this->assertEquals('API UserGroup edit', $result->result->result->name);
        $this->assertEmpty($result->result->result->description);
        $this->assertCount(0, $result->result->result->users);
    }

    public function testSearchAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'userGroup/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(2, $result->result->count);
        $this->assertCount(2, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'userGroup/search',
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
            'method' => 'userGroup/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'text' => 'API UserGroup edit'
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);
        $this->assertEquals('API UserGroup edit', $result->result->result[0]->name);
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
            'method' => 'userGroup/delete',
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
        $this->assertEquals('Group deleted', $result->result->resultMessage);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
    }
}
