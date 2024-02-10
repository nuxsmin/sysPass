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
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Services\XmlTagExport;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SPT\Generators\TagGenerator;
use SPT\UnitaryTestCase;

/**
 * Class XmlTagExportTest
 *
 * @group unitary
 */
class XmlTagExportTest extends UnitaryTestCase
{

    private TagServiceInterface|MockObject $tagService;
    private XmlTagExport                   $xmlTagExport;

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExport()
    {
        $tag = TagGenerator::factory()->buildTag();

        $document = new DOMDocument();

        $this->tagService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([$tag]);

        $out = $this->xmlTagExport->export($document);

        $this->assertEquals('Tags', $out->nodeName);
        $this->assertEquals('Tag', $out->firstChild->nodeName);
        $this->assertEquals($tag->getId(), $out->firstChild->attributes->getNamedItem('id')->nodeValue);
        $this->assertEquals(1, $out->firstChild->childNodes->count());

        $nodes = ['name' => $tag->getName()];

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

        $this->tagService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $out = $this->xmlTagExport->export($document);

        $this->assertEquals('Tags', $out->nodeName);
        $this->assertEquals(0, $out->childNodes->count());
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithException()
    {
        $document = new DOMDocument();

        $this->tagService
            ->expects(self::once())
            ->method('getAll')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');
        $this->xmlTagExport->export($document);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagService = $this->createMock(TagServiceInterface::class);

        $this->xmlTagExport = new XmlTagExport(
            $this->application,
            $this->tagService
        );
    }
}
