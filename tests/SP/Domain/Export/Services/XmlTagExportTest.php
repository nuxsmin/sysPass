<?php
declare(strict_types=1);
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

namespace SP\Tests\Domain\Export\Services;

use DOMDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Services\XmlTagExport;
use SP\Domain\Tag\Ports\TagService;
use SP\Tests\Generators\TagGenerator;
use SP\Tests\UnitaryTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class XmlTagExportTest
 *
 */
#[Group('unitary')]
class XmlTagExportTest extends UnitaryTestCase
{
    use XmlTrait;

    private TagService|MockObject $tagService;
    private XmlTagExport          $xmlTagExport;

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

        $out = $this->xmlTagExport->export();

        $this->assertEquals('Tags', $out->nodeName);
        $this->assertEquals('Tag', $out->firstChild->nodeName);
        $this->assertEquals($tag->getId(), $out->firstChild->attributes->getNamedItem('id')->nodeValue);
        $this->assertEquals(1, $out->firstChild->childNodes->count());

        $nodes = ['name' => $tag->getName()];

        $this->checkNodes($out->firstChild->childNodes, $nodes);
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

        $out = $this->xmlTagExport->export();

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
        $this->xmlTagExport->export();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagService = $this->createMock(TagService::class);

        $this->xmlTagExport = new XmlTagExport(
            $this->application,
            $this->tagService
        );
    }
}
