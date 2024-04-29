<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\Client\Adapters;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Client\Adapters\Client;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\ActionNotFoundException;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Models\Action;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SPT\Generators\ClientGenerator;
use SPT\Generators\CustomFieldGenerator;
use SPT\UnitaryTestCase;

/**
 * Class ClientTest
 */
#[Group('unitary')]
class ClientTest extends UnitaryTestCase
{
    private MockObject|ActionsInterface       $actions;
    private MockObject|CustomFieldDataService $customFieldDataService;

    /**
     * @throws ActionNotFoundException
     */
    public function testAdapt(): void
    {
        $client = ClientGenerator::factory()->buildClient();

        $adapter = new Client(
            $this->config->getConfigData(),
            'testUrl',
            $this->customFieldDataService,
            $this->actions
        );

        $out = $adapter->transform($client);

        $this->assertEquals($client->getId(), $out['id']);
        $this->assertEquals($client->getName(), $out['name']);
        $this->assertEquals($client->getDescription(), $out['description']);
        $this->assertEquals($client->getIsGlobal(), $out['isGlobal']);
        $this->assertNull($out['customFields']);

        $this->assertEquals('self', $out['links'][0]['rel']);
        $this->assertNotEmpty($out['links'][0]['uri']);
    }

    /**
     * @throws Exception
     */
    public function testIncludeCustomFields(): void
    {
        $client = ClientGenerator::factory()->buildClient();
        $customFieldData = CustomFieldGenerator::factory()->buildSimpleModel();

        $this->customFieldDataService
            ->expects(self::once())
            ->method('getBy')
            ->with(AclActionsInterface::CLIENT, $client->getId())
            ->willReturn([$customFieldData]);

        $adapter = new Client(
            $this->config->getConfigData(),
            'testUrl',
            $this->customFieldDataService,
            $this->actions
        );

        $fractal = new Manager();
        $fractal->parseIncludes('customFields');
        $out = $fractal->createData(new Item($client, $adapter))->toArray();

        $data = $out['data'];

        $this->assertEquals($client->getId(), $data['id']);
        $this->assertEquals($client->getName(), $data['name']);
        $this->assertEquals($client->getDescription(), $data['description']);
        $this->assertEquals($client->getIsGlobal(), $data['isGlobal']);

        $this->assertArrayHasKey('customFields', $data);
        $this->assertEquals($customFieldData['typeName'], $data['customFields']['data'][0]['type']);
        $this->assertEquals($customFieldData['typeText'], $data['customFields']['data'][0]['typeText']);
        $this->assertEquals($customFieldData['definitionId'], $data['customFields']['data'][0]['definitionId']);
        $this->assertEquals(
            $customFieldData['definitionName'],
            $data['customFields']['data'][0]['definitionName']
        );
        $this->assertEquals($customFieldData['help'], $data['customFields']['data'][0]['help']);
        $this->assertEquals($customFieldData['value'], $data['customFields']['data'][0]['value']);
        $this->assertEquals($customFieldData['encrypted'], $data['customFields']['data'][0]['isEncrypted']);
        $this->assertEquals($customFieldData['required'], $data['customFields']['data'][0]['required']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actions = $this->createMock(ActionsInterface::class);
        $this->actions->expects(self::once())
                      ->method('getActionById')
                      ->with(AclActionsInterface::CLIENT_VIEW)
                      ->willReturn(
                          new Action(
                              self::$faker->randomNumber(),
                              self::$faker->colorName,
                              self::$faker->sentence,
                              self::$faker->colorName
                          )
                      );

        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
    }
}
