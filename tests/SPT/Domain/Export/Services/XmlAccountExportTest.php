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

namespace SPT\Domain\Export\Services;

use DOMDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\DataModel\ItemItemWithIdAndName;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountToTagService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Services\XmlAccountExport;
use SPT\Generators\AccountDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class XmlAccountExportTest
 *
 * @group unitary
 */
class XmlAccountExportTest extends UnitaryTestCase
{
    use XmlTrait;

    private AccountService|MockObject      $accountService;
    private AccountToTagService|MockObject $accountToTagService;
    private XmlAccountExport               $xmlAccountExport;

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExport()
    {
        $account = AccountDataGenerator::factory()->buildAccount();
        $tag = new ItemItemWithIdAndName(['id' => self::$faker->randomNumber(3)]);

        $document = new DOMDocument();

        $this->accountService
            ->expects(self::once())
            ->method('getAllBasic')
            ->willReturn([$account]);

        $this->accountToTagService
            ->expects(self::once())
            ->method('getTagsByAccountId')
            ->with($account->getId())
            ->willReturn([$tag]);

        $out = $this->xmlAccountExport->export();

        $this->assertEquals('Accounts', $out->nodeName);
        $this->assertEquals('Account', $out->firstChild->nodeName);
        $this->assertEquals($account->getId(), $out->firstChild->attributes->getNamedItem('id')->nodeValue);
        $this->assertEquals(9, $out->firstChild->childNodes->count());

        $nodes = [
            'name' => $account->getName(),
            'clientId' => $account->getClientId(),
            'categoryId' => $account->getCategoryId(),
            'login' => $account->getLogin(),
            'url' => $account->getUrl(),
            'notes' => $account->getNotes(),
            'pass' => $account->getPass(),
            'key' => $account->getKey(),
            'tags' => '',
        ];

        $this->checkNodes($out->firstChild->childNodes, $nodes);

        $tagsNode = $out->firstChild->childNodes->item(8);

        $this->assertEquals('tag', $tagsNode->firstChild->nodeName);
        $this->assertEquals(
            $tag->getId(),
            $tagsNode->firstChild->attributes->getNamedItem('id')->nodeValue
        );
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithoutAccounts()
    {
        $document = new DOMDocument();

        $this->accountService
            ->expects(self::once())
            ->method('getAllBasic')
            ->willReturn([]);

        $this->accountToTagService
            ->expects(self::never())
            ->method('getTagsByAccountId');

        $out = $this->xmlAccountExport->export();

        $this->assertEquals('Accounts', $out->nodeName);
        $this->assertEquals(0, $out->childNodes->count());
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithException()
    {
        $document = new DOMDocument();

        $this->accountService
            ->expects(self::once())
            ->method('getAllBasic')
            ->willThrowException(new RuntimeException('test'));

        $this->accountToTagService
            ->expects(self::never())
            ->method('getTagsByAccountId');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');
        $this->xmlAccountExport->export();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = $this->createMock(AccountService::class);
        $this->accountToTagService = $this->createMock(AccountToTagService::class);

        $this->xmlAccountExport = new XmlAccountExport(
            $this->application,
            $this->accountService,
            $this->accountToTagService
        );
    }
}
