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
use SP\DataModel\ItemData;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Services\XmlCategoryExport;
use SPT\Generators\CategoryGenerator;
use SPT\UnitaryTestCase;

/**
 * Class XmlCategoryExportTest
 *
 * @group unitary
 */
class XmlCategoryExportTest extends UnitaryTestCase
{

    private CategoryService|MockObject $categoryService;
    private XmlCategoryExport          $xmlCategoryExport;

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExport()
    {
        $category = CategoryGenerator::factory()->buildCategory();
        $tag = new ItemData(['id' => self::$faker->randomNumber(3)]);

        $document = new DOMDocument();

        $this->categoryService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([$category]);

        $out = $this->xmlCategoryExport->export($document);

        $this->assertEquals('Categories', $out->nodeName);
        $this->assertEquals('Category', $out->firstChild->nodeName);
        $this->assertEquals($category->getId(), $out->firstChild->attributes->getNamedItem('id')->nodeValue);
        $this->assertEquals(2, $out->firstChild->childNodes->count());

        $nodes = [
            'name' => $category->getName(),
            'description' => $category->getDescription()
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

        $this->categoryService
            ->expects(self::once())
            ->method('getAll')
            ->willReturn([]);

        $out = $this->xmlCategoryExport->export($document);

        $this->assertEquals('Categories', $out->nodeName);
        $this->assertEquals(0, $out->childNodes->count());
    }

    /**
     * @throws Exception
     * @throws ServiceException
     */
    public function testExportWithException()
    {
        $document = new DOMDocument();

        $this->categoryService
            ->expects(self::once())
            ->method('getAll')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');
        $this->xmlCategoryExport->export($document);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = $this->createMock(CategoryService::class);

        $this->xmlCategoryExport = new XmlCategoryExport(
            $this->application,
            $this->categoryService
        );
    }
}
