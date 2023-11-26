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

namespace SP\Tests\Modules\Api\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use JsonException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Tests\Modules\Api\ApiTestCase;
use stdClass;

/**
 * Class ClientControllerTest
 *
 * @package SP\Tests\Modules\Api\Controllers
 */
class ClientControllerTest extends ApiTestCase
{
    private const PARAMS = [
        'name' => 'API Client',
        'description' => "API test\ndescription",
        'global' => 1
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateAction(): void
    {
        $response = $this->createClient(self::PARAMS);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNull($response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(5, $response->result->itemId);
        $this->assertEquals('Client added', $response->result->resultMessage);

        $resultItem = $response->result->result;

        $this->assertEquals($response->result->itemId, $resultItem->id);
        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['description'], $resultItem->description);
        $this->assertEquals(self::PARAMS['global'], $resultItem->isGlobal);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    private function createClient(?array $params = null): stdClass
    {
        $api = $this->callApi(
            AclActionsInterface::CLIENT_CREATE,
            $params ?? self::PARAMS
        );

        return self::processJsonResponse($api);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionDuplicated(): void
    {
        $response = $this->createClient(['name' => 'Google']);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Duplicated client', $response->error->message);
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

        $response = $this->createClient($params);

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
    public function testViewAction(): void
    {
        $response = $this->createClient(self::PARAMS);

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::CLIENT_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result->data;

        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['description'], $resultItem->description);
        $this->assertEquals(self::PARAMS['global'], $resultItem->isGlobal);
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
            AclActionsInterface::CLIENT_VIEW,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Client not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditAction(): void
    {
        $response = $this->createClient(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API Client edit',
            'description' => "API test\ndescription\nedit",
            'global' => 0
        ];

        $api = $this->callApi(
            AclActionsInterface::CLIENT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Client updated', $response->result->resultMessage);
        $this->assertEquals($id, $response->result->itemId);

        $api = $this->callApi(
            AclActionsInterface::CLIENT_VIEW,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(1, $response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals($id, $response->result->itemId);

        $resultItem = $response->result->result->data;

        $this->assertEquals($params['name'], $resultItem->name);
        $this->assertEquals($params['description'], $resultItem->description);
        $this->assertEquals($params['global'], $resultItem->isGlobal);
        $this->assertNull($resultItem->customFields);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionDuplicated(): void
    {
        $response = $this->createClient(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'Google'
        ];

        $api = $this->callApi(
            AclActionsInterface::CLIENT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Duplicated client', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionRequiredParameters(): void
    {
        $response = $this->createClient(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id
        ];

        $api = $this->callApi(
            AclActionsInterface::CLIENT_EDIT,
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
            'name' => 'API Client edit',
            'description' => "API test\ndescription\nedit",
            'global' => 0
        ];

        $api = $this->callApi(
            AclActionsInterface::CLIENT_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertEquals(0, $response->result->count);
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
            AclActionsInterface::CLIENT_SEARCH,
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
        $response = $this->createClient();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::CLIENT_DELETE,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Client deleted', $response->result->resultMessage);
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
            AclActionsInterface::CLIENT_DELETE,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Client not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteActionRequiredParameters(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CLIENT_DELETE,
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
                4
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
                ['text' => 'Inc'],
                3
            ],
            [
                ['text' => 'Spotify'],
                0
            ]
        ];
    }
}
