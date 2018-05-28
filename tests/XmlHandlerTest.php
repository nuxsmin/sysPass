<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use SP\Storage\FileException;
use SP\Storage\FileHandler;
use SP\Storage\XmlHandler;

/**
 * Class XmlHandlerTest
 *
 * Tests unitarios para comprobar el funcionamiento de la clase SP\Storage\XmlHandler
 *
 * @package Tests
 */
class XmlHandlerTest extends TestCase
{
    /**
     * @var array Elementos del archivo XML
     */
    protected $items;
    /**
     * @var XmlHandler
     */
    protected $xmlHandler;
    /**
     * @var object Objeto con los datos a guardar en el archivo XML
     */
    protected $itemsData;

    /**
     * Test para comprobar el guardado de un archivo XML
     *
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testSave()
    {
        $this->xmlHandler->save($this->itemsData, 'config');
    }

    /**
     * Test para comprobar la carga de un archivo XML
     *
     * @throws FileException
     */
    public function testLoadMissingNode()
    {
        $this->expectException(\RuntimeException::class);

        $this->xmlHandler->load('root')->getItems();
    }

    /**
     * Test para comprobar la carga de un archivo XML
     *
     * @depends testSave
     * @throws FileException
     */
    public function testLoad()
    {
        $this->items = $this->xmlHandler->load('config')->getItems();

        $this->assertTrue(is_array($this->items));
        $this->assertCount(3, $this->items);

        $this->assertSame($this->itemsData->configString, $this->items['configString']);
        $this->assertSame($this->itemsData->configNumber, $this->items['configNumber']);

        $this->assertTrue(is_array($this->items['configArray']));
        $this->assertCount(count($this->itemsData->configArray), $this->items['configArray']);
    }

    /**
     * Test para comprobar el guardado de un archivo XML
     *
     * @depends testLoad
     * @throws FileException
     */
    public function testSaveNoItems()
    {
        $this->expectException(\RuntimeException::class);

        $this->xmlHandler->save(null, 'config');
    }

    protected function setUp()
    {
        $file = TEST_ROOT . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'config.xml';
        $this->xmlHandler = new XmlHandler(new FileHandler($file));

        $this->itemsData = new \stdClass();
        $this->itemsData->configString = 'Hello world.';
        $this->itemsData->configNumber = 1;
        $this->itemsData->configArray = [1, 2, 3, 4];
    }
}