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

namespace SP\Tests\Storage;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Storage\File\XmlHandler;
use stdClass;

/**
 * Class XmlHandlerTest
 *
 * Tests unitarios para comprobar el funcionamiento de la clase SP\Storage\File\XmlHandler
 *
 * @package SP\Tests
 */
class XmlHandlerTest extends TestCase
{
    /**
     * @var XmlHandler
     */
    protected static $xmlHandler;
    /**
     * @var object Objeto con los datos a guardar en el archivo XML
     */
    protected static $itemsData;
    /**
     * @var array Elementos del archivo XML
     */
    protected $items;

    public static function setUpBeforeClass()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'config.xml';
        self::$xmlHandler = new XmlHandler(new FileHandler($file));

        self::$itemsData = new stdClass();
        self::$itemsData->configString = 'Hello world.';
        self::$itemsData->configNumber = 1;
        self::$itemsData->configArray = [1, 2, 3, 4];
    }

    /**
     * Test para comprobar el guardado de un archivo XML
     *
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testSave()
    {
        self::$xmlHandler->save(self::$itemsData, 'config');
    }

    /**
     * Test para comprobar la carga de un archivo XML
     *
     * @throws FileException
     */
    public function testLoadMissingNode()
    {
        $this->expectException(RuntimeException::class);

        self::$xmlHandler->load('root')->getItems();
    }

    /**
     * Test para comprobar la carga de un archivo XML
     *
     * @throws FileException
     */
    public function testLoad()
    {
        $this->items = self::$xmlHandler->load('config')->getItems();

        $this->assertTrue(is_array($this->items));
        $this->assertCount(3, $this->items);

        $this->assertSame(self::$itemsData->configString, $this->items['configString']);
        $this->assertSame(self::$itemsData->configNumber, $this->items['configNumber']);

        $this->assertTrue(is_array($this->items['configArray']));
        $this->assertCount(count(self::$itemsData->configArray), $this->items['configArray']);
    }

    /**
     * Test para comprobar el guardado de un archivo XML
     *
     * @throws FileException
     */
    public function testSaveNoItems()
    {
        $this->expectException(RuntimeException::class);

        self::$xmlHandler->save(null, 'config');
    }
}