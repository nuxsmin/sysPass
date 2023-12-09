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
use SPT\Modules\Api\ApiTestCase;
use stdClass;

/**
 * Class CategoryControllerTest
 *
 * @package SPT\Modules\Api\Controllers
 */
class CategoryControllerTest extends ApiTestCase
{
    private const PARAMS = [
        'name' => 'API Category',
        'description' => "API test\ndescription"
    ];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateAction(): void
    {
        $response = $this->createCategory(self::PARAMS);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertNull($response->result->count);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals(4, $response->result->itemId);
        $this->assertEquals('Category added', $response->result->resultMessage);

        $resultItem = $response->result->result;

        $this->assertEquals($response->result->itemId, $resultItem->id);
        $this->assertEquals(self::PARAMS['name'], $resultItem->name);
        $this->assertEquals(self::PARAMS['description'], $resultItem->description);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    private function createCategory(?array $params = null): stdClass
    {
        $api = $this->callApi(
            AclActionsInterface::CATEGORY_CREATE,
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
        $response = $this->createCategory(['name' => 'web']);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Duplicated category', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testCreateActionRequiredParameter(): void
    {
        $params = self::PARAMS;
        unset($params['name']);

        $response = $this->createCategory($params);

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
        $response = $this->createCategory(self::PARAMS);

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_VIEW,
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
            AclActionsInterface::CATEGORY_VIEW,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Category not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditAction(): void
    {
        $response = $this->createCategory(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'API test edit',
            'description' => "API test\ndescription\nedit"
        ];

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Category updated', $response->result->resultMessage);
        $this->assertEquals($id, $response->result->itemId);

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_VIEW,
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
        $this->assertNull($resultItem->customFields);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionDuplicated(): void
    {
        $response = $this->createCategory(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id,
            'name' => 'web'
        ];

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_EDIT,
            $params
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Duplicated category name', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testEditActionRequiredParameters(): void
    {
        $response = $this->createCategory(self::PARAMS);

        $id = $response->result->itemId;

        $params = [
            'id' => $id
        ];

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_EDIT,
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
            'name' => 'API test edit',
            'description' => "API test\ndescription\nedit"
        ];

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_EDIT,
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
            AclActionsInterface::CATEGORY_SEARCH,
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
        $response = $this->createCategory();

        $id = $response->result->itemId;

        $api = $this->callApi(
            AclActionsInterface::CATEGORY_DELETE,
            ['id' => $id]
        );

        $response = self::processJsonResponse($api);

        $this->assertEquals(0, $response->result->resultCode);
        $this->assertInstanceOf(stdClass::class, $response->result);
        $this->assertEquals('Category deleted', $response->result->resultMessage);
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
            AclActionsInterface::CATEGORY_DELETE,
            ['id' => 10]
        );

        $response = self::processJsonResponse($api);

        $this->assertInstanceOf(stdClass::class, $response->error);
        $this->assertEquals('Category not found', $response->error->message);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws JsonException
     */
    public function testDeleteActionRequiredParameters(): void
    {
        $api = $this->callApi(
            AclActionsInterface::CATEGORY_DELETE,
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
                3
            ],
            [
                ['count' => 1],
                1
            ],
            [
                ['text' => 'Linux'],
                1
            ],
            [
                ['text' => 'Windows'],
                0
            ]
        ];
    }
}
