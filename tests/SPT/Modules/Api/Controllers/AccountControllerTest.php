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

namespace SPT\Modules\Api\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use JsonException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Api\Controllers\Account\AccountController;
use SPT\Modules\Api\ApiTestCase;
use stdClass;

/**
 * Class AccountControllerTest
 *
 * @package SP\Modules\Api\Controllers
 */
class AccountControllerTest extends ApiTestCase
{
    private const PARAMS = [
        'name' => 'API test',
        'categoryId' => 2,
        'clientId' => 2,
        'login' => 'root',
        'pass' => 'password_test',
        'expireDate' => 1634395912,
        'url' => 'http://syspass.org',
        'notes' => "test\n\ntest",
        'private' => 0,
        'privateGroup' => 0,
        'userId' => 2,
        'userGroupId' => 2,
        'parentId' => 0,
        'tagsId' => [3]
    ];

    protected AccountController $controller;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateAction(): void
    {
        $params = self::PARAMS;
        $params['private'] = 1;
        $params['privateGroup'] = 1;
        $params['parentId'] = 1;

        $response = $this->createAccount($params);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNull($response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(5, $response->result->itemId);
        $this->assertEquals('Account created', $response->result->resultMessage);

        $resultItem = $response->result->result;

        $this->assertEquals($response->result->itemId, $resultItem->id);
        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['categoryId'], $resultItem->categoryId);
        $this->assertEquals(self::PARAMS['clientId'], $resultItem->clientId);
        $this->assertEquals(self::PARAMS['login'], $resultItem->login);
        $this->assertEquals(self::PARAMS['expireDate'], $resultItem->passDateChange);
        $this->assertEquals(self::PARAMS['url'], $resultItem->url);
        $this->assertEquals(self::PARAMS['notes'], $resultItem->notes);
        $this->assertEquals(self::PARAMS['userId'], $resultItem->userId);
        $this->assertEquals(self::PARAMS['userGroupId'], $resultItem->userGroupId);
        $this->assertEquals($params['private'], $resultItem->isPrivate);
        $this->assertEquals($params['privateGroup'], $resultItem->isPrivateGroup);
        $this->assertEquals($params['parentId'], $resultItem->parentId);
        $this->assertEmpty($resultItem->pass);
        $this->assertEmpty($resultItem->key);
        $this->assertNull($resultItem->dateEdit);
        $this->assertEquals(0, $resultItem->countView);
        $this->assertEquals(0, $resultItem->countDecrypt);
        $this->assertGreaterThan(0, $resultItem->passDate);
        $this->assertEquals(self::PARAMS['expireDate'], $resultItem->passDateChange);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    private function createAccount(?array $params = null): stdClass
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_CREATE,
            $params ?? self::PARAMS
        );

        return self::processJsonResponse($api);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionNoUserData(): void
    {
        $params = self::PARAMS;

        unset($params['userId'], $params['userGroupId']);

        $response = $this->createAccount($params);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNull($response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(5, $response->result->itemId);
        $this->assertEquals('Account created', $response->result->resultMessage);

        $resultItem = $response->result->result;

        $this->assertEquals($response->result->itemId, $resultItem->id);
        $this->assertEquals($params['name'], $resultItem->name);
        $this->assertEquals($params['categoryId'], $resultItem->categoryId);
        $this->assertEquals($params['clientId'], $resultItem->clientId);
        $this->assertEquals($params['login'], $resultItem->login);
        $this->assertEquals($params['expireDate'], $resultItem->passDateChange);
        $this->assertEquals($params['url'], $resultItem->url);
        $this->assertEquals($params['notes'], $resultItem->notes);
        $this->assertEquals($params['private'], $resultItem->isPrivate);
        $this->assertEquals($params['privateGroup'], $resultItem->isPrivateGroup);
        $this->assertEquals(1, $resultItem->userId);
        $this->assertEquals(1, $resultItem->userGroupId);
    }

    /**
     * @dataProvider getUnsetParams
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionRequiredParameters(string $unsetParam): void
    {
        $params = self::PARAMS;

        unset($params[$unsetParam]);

        $response = $this->createAccount($params);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);

    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewPassAction(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result;

        $this->assertEquals(self::PARAMS['pass'], $resultItem->password);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewPassActionRequiredParamater(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            []
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditPassAction(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_EDIT_PASS,
            [
                'id' => $id,
                'pass' => 'test_123',
                'expireDate' => time() + 86400
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Password updated', $response->result->resultMessage);
        $this->assertEquals($id, $response->result->itemId);

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result;

        $this->assertEquals('test_123', $resultItem->password);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditPassActionRequiredParameters(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_EDIT_PASS,
            [
                'id' => $id
            ]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewPassActionNonExistant(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Account not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewAction(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result->data;

        $this->assertInstanceOf(stdClass::class, $resultItem);
        $this->assertEquals($id, $resultItem->id);
        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['categoryId'], $resultItem->categoryId);
        $this->assertEquals(self::PARAMS['clientId'], $resultItem->clientId);
        $this->assertEquals(self::PARAMS['login'], $resultItem->login);
        $this->assertEquals(self::PARAMS['expireDate'], $resultItem->passDateChange);
        $this->assertEquals(self::PARAMS['url'], $resultItem->url);
        $this->assertEquals(self::PARAMS['notes'], $resultItem->notes);
        $this->assertEquals(self::PARAMS['private'], $resultItem->isPrivate);
        $this->assertEquals(self::PARAMS['privateGroup'], $resultItem->isPrivateGroup);
        $this->assertEquals(self::PARAMS['userId'], $resultItem->userId);
        $this->assertEquals(self::PARAMS['userGroupId'], $resultItem->userGroupId);
        $this->assertNull($resultItem->publicLinkHash);
        $this->assertEquals(0, $resultItem->dateEdit);
        $this->assertEquals(0, $resultItem->countView);
        $this->assertEquals(0, $resultItem->countDecrypt);
        $this->assertEquals(0, $resultItem->isPrivate);
        $this->assertEquals(0, $resultItem->isPrivateGroup);
        $this->assertGreaterThan(0, $resultItem->passDate);
        $this->assertEquals(self::PARAMS['expireDate'], $resultItem->passDateChange);
        $this->assertEquals(self::PARAMS['parentId'], $resultItem->parentId);
        $this->assertIsArray($resultItem->tags);
        $this->assertCount(1, $resultItem->tags);
        $this->assertEquals(self::PARAMS['tagsId'][0], $resultItem->tags[0]->id);
        $this->assertEquals('Linux', $resultItem->tags[0]->name);
        $this->assertIsArray($resultItem->users);
        $this->assertCount(0, $resultItem->users);
        $this->assertIsArray($resultItem->userGroups);
        $this->assertCount(0, $resultItem->userGroups);
        $this->assertNull($resultItem->customFields);
        $this->assertIsArray($resultItem->links);
        $this->assertEquals('self', $resultItem->links[0]->rel);
        $this->assertNotEmpty($resultItem->links[0]->uri);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewActionNonExistant(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('The account doesn\'t exist', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewActionRequiredParameter(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW,
            []
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);
    }

    /**
     * @dataProvider searchProvider
     *
     * @throws DependencyException
     * @throws JsonException
     * @throws NotFoundException
     */
    public function testSearchActionByFilter(array $filter, int $resultsCount): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_SEARCH,
            $filter
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals($resultsCount, $response->result->count);
        $this->assertCount($resultsCount, $response->result->result);
    }

    /**
     * @throws DependencyException
     * @throws JsonException
     * @throws NotFoundException
     */
    public function testEditAction(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API test edit',
            'categoryId' => 3,
            'clientId' => 3,
            'login' => 'admin',
            'expireDate' => time() + 86400,
            'url' => 'http://demo.syspass.org',
            'notes' => "test\n\ntest\nedit",
            'private' => 0,
            'privateGroup' => 0,
            'userId' => 1,
            'userGroupId' => 1,
            'parentId' => 1,
            'tagsId' => [1]
        ];

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);
        $this->assertEquals('Account updated', $response->result->resultMessage);

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result->data;

        $this->assertInstanceOf(stdClass::class, $resultItem);
        $this->assertEquals($id, $resultItem->id);
        $this->assertEquals($params['name'], $resultItem->name);
        $this->assertEquals($params['categoryId'], $resultItem->categoryId);
        $this->assertEquals($params['clientId'], $resultItem->clientId);
        $this->assertEquals($params['login'], $resultItem->login);
        $this->assertEquals($params['expireDate'], $resultItem->passDateChange);
        $this->assertEquals($params['url'], $resultItem->url);
        $this->assertEquals($params['notes'], $resultItem->notes);
        $this->assertEquals($params['private'], $resultItem->isPrivate);
        $this->assertEquals($params['privateGroup'], $resultItem->isPrivateGroup);
        $this->assertEquals($params['userId'], $resultItem->userId);
        $this->assertEquals($params['userGroupId'], $resultItem->userGroupId);
        $this->assertNull($resultItem->publicLinkHash);
        $this->assertGreaterThan(0, $resultItem->dateEdit);
        $this->assertEquals(0, $resultItem->countView);
        $this->assertEquals(0, $resultItem->countDecrypt);
        $this->assertEquals(0, $resultItem->isPrivate);
        $this->assertEquals(0, $resultItem->isPrivateGroup);
        $this->assertGreaterThan(0, $resultItem->passDate);
        $this->assertEquals($params['expireDate'], $resultItem->passDateChange);
        $this->assertEquals($params['parentId'], $resultItem->parentId);
        $this->assertNull($resultItem->customFields);
        $this->assertIsArray($resultItem->tags);
        $this->assertCount(1, $resultItem->tags);
        $this->assertEquals($params['tagsId'][0], $resultItem->tags[0]->id);
        $this->assertEquals('www', $resultItem->tags[0]->name);
        $this->assertIsArray($resultItem->users);
        $this->assertCount(0, $resultItem->users);
        $this->assertIsArray($resultItem->userGroups);
        $this->assertCount(0, $resultItem->userGroups);
        $this->assertNull($resultItem->customFields);
        $this->assertIsArray($resultItem->links);
        $this->assertEquals('self', $resultItem->links[0]->rel);
        $this->assertNotEmpty($resultItem->links[0]->uri);
    }

    /**
     * @dataProvider getUnsetParams
     *
     * @throws DependencyException
     * @throws JsonException
     * @throws NotFoundException
     */
    public function testEditActionRequiredParameter(string $unsetParam): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API test edit',
            'categoryId' => 3,
            'clientId' => 3,
            'login' => 'admin',
            'expireDate' => time() + 86400,
            'url' => 'http://demo.syspass.org',
            'notes' => "test\n\ntest\nedit",
            'private' => 0,
            'privateGroup' => 0,
            'userId' => 1,
            'userGroupId' => 1,
            'parentId' => 1,
            'tagsId' => [1]
        ];

        unset($params[$unsetParam]);

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);
    }

    /**
     * @throws DependencyException
     * @throws JsonException
     * @throws NotFoundException
     */
    public function testEditActionNonExistant(): void
    {
        $params = [
            'id' => 10,
            'name' => 'API test edit',
            'categoryId' => 3,
            'clientId' => 3,
            'login' => 'admin',
            'expireDate' => time() + 86400,
            'url' => 'http://demo.syspass.org',
            'notes' => "test\n\ntest\nedit",
            'private' => 0,
            'privateGroup' => 0,
            'userId' => 1,
            'userGroupId' => 1,
            'parentId' => 1,
            'tagsId' => [1]
        ];

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('The account doesn\'t exist', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteAction(): void
    {
        $response = $this->createAccount();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_DELETE,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Account removed', $response->result->resultMessage);
        $this->assertEquals($id, $response->result->itemId);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteActionNonExistant(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_DELETE,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('The account doesn\'t exist', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteActionRequiredParameters(): void
    {
        $api = $this->callApi(
            AclActionsInterface::ACCOUNT_DELETE,
            []
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Wrong parameters', $response->error->message);
        $this->assertInstanceOf(stdClass::class, $response->error->data);
        $this->assertIsArray($response->error->data->help);
    }

    public static function searchProvider(): array
    {
        return [
            [
                [],
                2
            ],
            [
                ['count' => 1],
                1
            ],
            [
                ['text' => 'Google'],
                1
            ],
            [
                ['text' => 'admin'],
                2
            ],
            [
                ['text' => 'aaa'],
                1
            ],
            [
                ['clientId' => 2],
                1
            ],
            [
                ['clientId' => 3],
                0
            ],
            [
                ['categoryId' => 1],
                1
            ],
            [
                ['categoryId' => 2],
                1
            ],
            [
                ['categoryId' => 10],
                0
            ],
            [
                ['tagsId' => [3]],
                1
            ],
            [
                ['tagsId' => [1, 3]],
                1
            ],
            [
                [
                    'tagsId' => [1, 3],
                    'op' => 'or'
                ],
                2
            ],
            [
                ['tagsId' => [1, 4]],
                0
            ],
            [
                ['tagsId' => [10]],
                0
            ],
            [
                [
                    'categoryId' => 1,
                    'clientId' => 1
                ],
                1
            ],
            [
                [
                    'categoryId' => 2,
                    'clientId' => 1
                ],
                0
            ],
            [
                [
                    'categoryId' => 2,
                    'clientId' => 1,
                    'op' => 'or'
                ],
                2
            ],
        ];
    }

    public static function getUnsetParams(): array
    {
        return [
            ['name'],
            ['clientId'],
            ['categoryId'],
        ];
    }
}
