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
use PHPUnit\Framework\Attributes\DataProvider;
use SP\Domain\Core\Acl\AclActionsInterface;
use SPT\Modules\Api\ApiTestCase;
use stdClass;

/**
 * Class UserGroupControllerTest
 *
 * @package SPT\Modules\Api\Controllers
 */
class UserGroupControllerTest extends ApiTestCase
{
    private const PARAMS = [
        'name' => 'API UserGroup',
        'description' => "API test\ndescription",
        'usersId' => [3, 4]
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateAction(): void
    {
        $response = $this->createUserGroup(self::PARAMS);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNull($response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(7, $response->result->itemId);
        $this->assertEquals('Group added', $response->result->resultMessage);

        $resultItem = $response->result->result;

        $this->assertEquals($response->result->itemId, $resultItem->id);
        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['description'], $resultItem->description);
        $this->assertCount(2, $resultItem->users);
        $this->assertEquals(self::PARAMS['usersId'][0], $resultItem->users[0]);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    private function createUserGroup(?array $params = null): stdClass
    {
        $api = $this->callApi(
            AclActionsInterface::GROUP_CREATE,
            $params ?? self::PARAMS
        );

        return self::processJsonResponse($api);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionInvalidUser(): void
    {
        $params = self::PARAMS;
        $params['usersId'] = [10];

        $response = $this->createUserGroup($params);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Integrity constraint', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionRequiredParameters(): void
    {
        $params = self::PARAMS;
        unset($params['name']);

        $response = $this->createUserGroup($params);

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
    public function testCreateActionDuplicatedName(): void
    {
        $params = self::PARAMS;
        $params['name'] = 'Admins';

        $response = $this->createUserGroup($params);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Duplicated group name', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewAction(): void
    {
        $response = $this->createUserGroup(self::PARAMS);

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::GROUP_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result;

        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['description'], $resultItem->description);
        $this->assertCount(2, $resultItem->users);
        $this->assertEquals(self::PARAMS['usersId'][0], $resultItem->users[0]);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testViewActionNonExistant(): void
    {
        $api = $this->callApi(
            AclActionsInterface::GROUP_VIEW,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Group not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    #[DataProvider('getGroupUsers')]
    public function testEditAction(array $users, int $usersCount): void
    {
        $response = $this->createUserGroup(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API test edit',
            'description' => "API test\ndescription",
            'usersId' => $users
        ];

        $api = $this->callApi(
            AclActionsInterface::GROUP_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals('Group updated', $response->result->resultMessage);
        $this->assertEquals($id, $response->result->itemId);

        $api = $this->callApi(
            AclActionsInterface::GROUP_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result;

        $this->assertEquals($params['name'], $resultItem->name);
        $this->assertEquals($params['description'], $resultItem->description);
        $this->assertCount($usersCount, $resultItem->users);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionInvalidUser(): void
    {
        $response = $this->createUserGroup(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API test edit',
            'description' => "API test\ndescription",
            'usersId' => [10]
        ];

        $api = $this->callApi(
            AclActionsInterface::GROUP_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Integrity constraint', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionRequiredParameters(): void
    {
        $response = $this->createUserGroup(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id
        ];

        $api = $this->callApi(
            AclActionsInterface::GROUP_EDIT,
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
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionNonExistant(): void
    {
        $params = [
            'id' => 10,
            'name' => 'API test edit'
        ];

        $api = $this->callApi(
            AclActionsInterface::GROUP_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(0, $response->result->count);
    }

    /**
     * @throws DependencyException
     * @throws JsonException
     * @throws NotFoundException
     */
    #[DataProvider('searchProvider')]
    public function testSearchActionByFilter(array $filter, int $resultsCount): void
    {
        $api = $this->callApi(
            AclActionsInterface::GROUP_SEARCH,
            $filter
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals($resultsCount, $response->result->count);
        $this->assertCount($resultsCount, $response->result->result);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteAction(): void
    {
        $response = $this->createUserGroup();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::GROUP_DELETE,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Group deleted', $response->result->resultMessage);
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
            AclActionsInterface::GROUP_DELETE,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Group not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteActionRequiredParameters(): void
    {
        $api = $this->callApi(
            AclActionsInterface::GROUP_DELETE,
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
                6
            ],
            [
                ['count' => 1],
                1
            ],
            [
                ['text' => 'Demo'],
                1
            ],
            [
                ['text' => 'Test'],
                3
            ],
            [
                ['text' => 'Grupo'],
                1
            ]
        ];
    }

    public static function getGroupUsers(): array
    {
        return [
            [
                [2, 3, 4],
                3
            ],
            [
                [2, 3],
                2
            ],
            [
                [],
                0
            ],
        ];
    }
}
