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
use DOMNodeList;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Services\XmlClientExport;
use SPT\Generators\ClientGenerator;
use SPT\UnitaryTestCase;

/**
 * Class XmlClientExportTest
 *
 * @group unitary
 */
class XmlClientExportTest extends UnitaryTestCase
{

    private ClientService|MockObject $clientService;
    private XmlClientExport          $xmlClientExport;

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExport()
    {
        $client = ClientGenerator::factory()->buildClient();

        $document = new DOMDocument();

        $this->clientService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([$client]);

        $out = $this->xmlClientExport->export($document);

        $this->assertEquals('Clients', $out->nodeName);
        $this->assertEquals('Client', $out->firstChild->nodeName);
        $this->assertEquals($client->getId(), $out->firstChild->attributes->getNamedItem('id')->nodeValue);
        $this->assertEquals(2, $out->firstChild->childNodes->count());

        $nodes = [
            'name' => $client->getName(),
            'description' => $client->getDescription()
        ];

        $this->checkNodes($out->firstChild->childNodes, $nodes);
    }

    private function checkNodes(DOMNodeList $nodeList, array $nodes): void
    {
        $names = array_keys($nodes);
        $values = array_values($nodes);

        foreach ($names as $key => $nodeName) {
            $this->assertEquals($nodeName, $nodeList->item($key)->nodeName);
            $this->assertEquals($values[$key], $nodeList->item($key)->nodeValue);
        }
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithoutCategories()
    {
        $document = new DOMDocument();

        $this->clientService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $out = $this->xmlClientExport->export($document);

        $this->assertEquals('Clients', $out->nodeName);
        $this->assertEquals(0, $out->childNodes->count());
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithException()
    {
        $document = new DOMDocument();

        $this->clientService
            ->expects(self::once())
            ->method('getAll')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');
        $this->xmlClientExport->export($document);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientService = $this->createMock(ClientService::class);

        $this->xmlClientExport = new XmlClientExport(
            $this->application,
            $this->clientService
        );
    }
}
