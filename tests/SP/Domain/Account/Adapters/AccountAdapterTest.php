<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Account\Adapters;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use SP\Domain\Account\Adapters\AccountAdapter;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\CustomFieldGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountAdapterTest
 *
 * @group unitary
 */
class AccountAdapterTest extends UnitaryTestCase
{
    public function testAdapt(): void
    {
        $dataGenerator = AccountDataGenerator::factory();

        $adapter = new AccountAdapter(
            $this->config->getConfigData(),
            $this->createStub(CustomFieldServiceInterface::class)
        );
        $accountData = $dataGenerator->buildAccountEnrichedDto();

        $out = $adapter->transform($accountData);

        $this->assertEquals($accountData->getId(), $out['id']);
        $this->assertEquals(
            SelectItemAdapter::factory($accountData->getTags())->getItemsFromModel(),
            $out['tags']
        );
        $this->assertEquals(
            SelectItemAdapter::factory($accountData->getUsers())->getItemsFromModel(),
            $out['users']
        );
        $this->assertEquals(
            SelectItemAdapter::factory($accountData->getUserGroups())->getItemsFromModel(),
            $out['userGroups']
        );

        $accountVData = $accountData->getAccountDataView();

        $this->assertEquals($accountVData->getName(), $out['name']);
        $this->assertEquals($accountVData->getClientId(), $out['clientId']);
        $this->assertEquals($accountVData->getClientName(), $out['clientName']);
        $this->assertEquals($accountVData->getCategoryId(), $out['categoryId']);
        $this->assertEquals($accountVData->getCategoryName(), $out['categoryName']);
        $this->assertEquals($accountVData->getUserId(), $out['userId']);
        $this->assertEquals($accountVData->getUserName(), $out['userName']);
        $this->assertEquals($accountVData->getUserLogin(), $out['userLogin']);
        $this->assertEquals($accountVData->getUserGroupId(), $out['userGroupId']);
        $this->assertEquals($accountVData->getUserGroupName(), $out['userGroupName']);
        $this->assertEquals($accountVData->getUserEditId(), $out['userEditId']);
        $this->assertEquals($accountVData->getUserEditName(), $out['userEditName']);
        $this->assertEquals($accountVData->getUserEditLogin(), $out['userEditLogin']);
        $this->assertEquals($accountVData->getLogin(), $out['login']);
        $this->assertEquals($accountVData->getUrl(), $out['url']);
        $this->assertEquals($accountVData->getNotes(), $out['notes']);
        $this->assertEquals($accountVData->getOtherUserEdit(), $out['otherUserEdit']);
        $this->assertEquals($accountVData->getOtherUserGroupEdit(), $out['otherUserGroupEdit']);
        $this->assertEquals($accountVData->getDateAdd(), $out['dateAdd']);
        $this->assertEquals($accountVData->getDateEdit(), $out['dateEdit']);
        $this->assertEquals($accountVData->getCountView(), $out['countView']);
        $this->assertEquals($accountVData->getCountDecrypt(), $out['countDecrypt']);
        $this->assertEquals($accountVData->getIsPrivate(), $out['isPrivate']);
        $this->assertEquals($accountVData->getIsPrivateGroup(), $out['isPrivateGroup']);
        $this->assertEquals($accountVData->getPassDate(), $out['passDate']);
        $this->assertEquals($accountVData->getPassDateChange(), $out['passDateChange']);
        $this->assertEquals($accountVData->getParentId(), $out['parentId']);
        $this->assertEquals($accountVData->getPublicLinkHash(), $out['publicLinkHash']);
        $this->assertNull($out['customFields']);

        $this->assertEquals('self', $out['links'][0]['rel']);
        $this->assertNotEmpty($out['links'][0]['uri']);
    }

    public function testIncludeCustomFields(): void
    {
        $customFieldData = CustomFieldGenerator::factory()->buildSimpleModel();
        $customFieldsService = $this->createStub(CustomFieldServiceInterface::class);
        $customFieldsService->expects(self::once())
                            ->method('getForModuleAndItemId')
                            ->willReturn([$customFieldData]);

        $adapter = new AccountAdapter($this->config->getConfigData(), $customFieldsService);

        $fractal = new Manager();
        $fractal->parseIncludes('customFields');
        $out = $fractal->createData(
            new Item(AccountDataGenerator::factory()->buildAccountEnrichedDto(), $adapter)
        )->toArray();

        $this->assertArrayHasKey('customFields', $out['data']);
        $this->assertEquals($customFieldData->typeName, $out['data']['customFields']['data'][0]['type']);
        $this->assertEquals($customFieldData->typeText, $out['data']['customFields']['data'][0]['typeText']);
        $this->assertEquals($customFieldData->definitionId, $out['data']['customFields']['data'][0]['definitionId']);
        $this->assertEquals(
            $customFieldData->definitionName,
            $out['data']['customFields']['data'][0]['definitionName']
        );
        $this->assertEquals($customFieldData->help, $out['data']['customFields']['data'][0]['help']);
        $this->assertEquals($customFieldData->value, $out['data']['customFields']['data'][0]['value']);
        $this->assertEquals($customFieldData->encrypted, $out['data']['customFields']['data'][0]['isEncrypted']);
        $this->assertEquals($customFieldData->required, $out['data']['customFields']['data'][0]['required']);
    }
}
