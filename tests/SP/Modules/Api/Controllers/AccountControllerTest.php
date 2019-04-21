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
 * Class AccountControllerTest
 *
 * @package SP\Modules\Api\Controllers
 */
class AccountControllerTest extends WebTestCase
{
    /**
     * @return int
     */
    public function testCreateAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'name' => 'API test',
                'categoryId' => 2,
                'clientId' => 2,
                'login' => 'root',
                'pass' => 'password_test',
                'expireDate' => time() + 86400,
                'url' => 'http://syspass.org',
                'notes' => "test\n\ntest",
                'isPrivate' => 1,
                'isPrivateGroup' => 1,
                'userId' => 1,
                'userGroupId' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result);
        $this->assertEquals(3, $result->result->itemId);
        $this->assertEquals('Account created', $result->result->resultMessage);

        return $result->result->itemId;
    }

    public function testCreateActionNoUserData()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'name' => 'API test',
                'categoryId' => 2,
                'clientId' => 2,
                'login' => 'root',
                'pass' => 'password_test',
                'expireDate' => time() + 86400,
                'url' => 'http://syspass.org',
                'notes' => "test\n\ntest",
                'isPrivate' => 1,
                'isPrivateGroup' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result);
        $this->assertEquals(4, $result->result->itemId);
        $this->assertEquals('Account created', $result->result->resultMessage);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $result->result->itemId,
            ],
            'id' => 1
        ];

        self::postJson(ApiTest::API_URL, $data);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testViewPassAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('password_test', $result->result->result->password);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testEditPassAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/editPass',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'id' => $id,
                'pass' => 'test_123',
                'expireDate' => time() + 86400
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
        $this->assertEquals('Password updated', $result->result->resultMessage);
        $this->assertEquals($id, $result->result->itemId);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tokenPass' => ApiTest::API_PASS,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result->result);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertEquals('test_123', $result->result->result->password);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testViewAction($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/view',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertNull($result->result->count);

        $this->assertInstanceOf(stdClass::class, $result->result->result);
        $this->assertEquals($id, $result->result->result->id);
        $this->assertEquals(1, $result->result->result->userId);
        $this->assertEquals(1, $result->result->result->userGroupId);
        $this->assertEquals(1, $result->result->result->userEditId);
        $this->assertEquals('API test', $result->result->result->name);
        $this->assertEquals(2, $result->result->result->clientId);
        $this->assertEquals(2, $result->result->result->categoryId);
        $this->assertEquals('root', $result->result->result->login);
        $this->assertEquals('http://syspass.org', $result->result->result->url);
        $this->assertEmpty($result->result->result->pass);
        $this->assertEmpty($result->result->result->key);
        $this->assertEquals("test\n\ntest", $result->result->result->notes);
        $this->assertNotNull($result->result->result->dateEdit);
        $this->assertEquals(0, $result->result->result->countView);
        $this->assertEquals(2, $result->result->result->countDecrypt);
        $this->assertEquals(0, $result->result->result->isPrivate);
        $this->assertEquals(0, $result->result->result->isPrivateGroup);
        $this->assertGreaterThan(0, $result->result->result->passDate);
        $this->assertGreaterThan(0, $result->result->result->passDateChange);
        $this->assertEquals(0, $result->result->result->parentId);
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

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(3, $result->result->count);
        $this->assertCount(3, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
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
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'text' => 'Simple'
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'text' => 'admin'
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
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'text' => 'cloud'
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);
    }

    public function testSearchByClientAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'clientId' => 2
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'clientId' => 3
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'clientId' => 10
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);
    }

    public function testSearchByCategoryAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 3
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 10
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);
    }

    public function testSearchByTagsAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tagsId' => [3]
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tagsId' => [3, 6]
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tagsId' => [3, 6],
                'op' => 'or'
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
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'tagsId' => [10]
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);
    }

    public function testSearchByCategoryAndClientAction()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 1,
                'clientId' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 2,
                'clientId' => 3
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(1, $result->result->count);
        $this->assertCount(1, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 2,
                'clientId' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(0, $result->result->count);
        $this->assertCount(0, $result->result->result);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'categoryId' => 1,
                'clientId' => 3,
                'op' => 'or'
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals(2, $result->result->count);
        $this->assertCount(2, $result->result->result);
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
            'method' => 'account/edit',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
                'name' => 'API test edit',
                'categoryId' => 3,
                'clientId' => 1,
                'login' => 'admin',
                'expireDate' => time() + 86400,
                'url' => 'http://demo.syspass.org',
                'notes' => "test\n\ntest\nedit",
                'isPrivate' => 0,
                'isPrivateGroup' => 0,
                'userId' => 1,
                'userGroupId' => 1
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result);
        $this->assertEquals(3, $result->result->itemId);
        $this->assertEquals('Account updated', $result->result->resultMessage);

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/view',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertNull($result->result->count);

        $this->assertInstanceOf(stdClass::class, $result->result->result);
        $this->assertEquals($id, $result->result->result->id);
        $this->assertEquals(1, $result->result->result->userId);
        $this->assertEquals(1, $result->result->result->userGroupId);
        $this->assertEquals(1, $result->result->result->userEditId);
        $this->assertEquals('API test edit', $result->result->result->name);
        $this->assertEquals(1, $result->result->result->clientId);
        $this->assertEquals(3, $result->result->result->categoryId);
        $this->assertEquals('admin', $result->result->result->login);
        $this->assertEquals('http://demo.syspass.org', $result->result->result->url);
        $this->assertEmpty($result->result->result->pass);
        $this->assertEmpty($result->result->result->key);
        $this->assertEquals("test\n\ntest\nedit", $result->result->result->notes);
        $this->assertNotNull($result->result->result->dateEdit);
        $this->assertEquals(0, $result->result->result->isPrivate);
        $this->assertEquals(0, $result->result->result->isPrivateGroup);
        $this->assertGreaterThan(0, $result->result->result->passDate);
        $this->assertGreaterThan(0, $result->result->result->passDateChange);
        $this->assertEquals(0, $result->result->result->parentId);
    }

    /**
     * @depends testCreateAction
     *
     * @param int $id
     */
    public function testEditActionNoUserData($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
                'name' => 'API test edit',
                'categoryId' => 3,
                'clientId' => 1,
                'login' => 'admin',
                'expireDate' => time() + 86400,
                'url' => 'http://demo.syspass.org',
                'notes' => "test\n\ntest\nedit",
                'isPrivate' => 0,
                'isPrivateGroup' => 0
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertNull($result->result->count);
        $this->assertInstanceOf(stdClass::class, $result->result);
        $this->assertEquals(3, $result->result->itemId);
        $this->assertEquals('Account updated', $result->result->resultMessage);
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
            'method' => 'account/delete',
            'params' => [
                'authToken' => ApiTest::API_TOKEN,
                'id' => $id,
            ],
            'id' => 1
        ];

        $result = self::checkAndProcessJsonResponse(self::postJson(ApiTest::API_URL, $data));

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals(0, $result->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $result->result);
        $this->assertEquals('Account removed', $result->result->resultMessage);
        $this->assertEquals($id, $result->result->itemId);
        $this->assertNull($result->result->count);
    }
}
