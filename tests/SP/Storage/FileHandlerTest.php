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
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;

/**
 * Class FileHandlerTest
 *
 * Tests unitarios para comprobar el funcionamiento de la clase SP\Storage\File\FileHandler
 *
 * @package SP\Tests
 */
class FileHandlerTest extends TestCase
{
    /**
     * @var string Archvivo de prueba válido
     */
    protected static $validFile = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'valid_file.test';
    /**
     * @var string Archvivo de prueba inmutable
     */
    protected static $immutableFile = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'immutable_file.test';
    /**
     * @var string Archivo de prueba no existente
     */
    protected static $missingFile = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'missing_file.test';

    /**
     * Comprobar la escritura de texto en un archivo
     *
     * @throws FileException
     */
    public function testWrite()
    {
        $handler = new FileHandler(self::$validFile);
        $handler->write('valid_file');

        $this->assertEquals('valid_file', $handler->readToString());

        $handler->close();

        $this->assertFileExists(self::$validFile);
    }

    /**
     * Comprobar si es posible escribir en el archivo
     *
     * @throws FileException
     */
    public function testCheckIsWritable()
    {
        (new FileHandler(self::$validFile))
            ->clearCache()
            ->checkIsWritable();

        $this->assertTrue(true);
    }

    /**
     * Comprobar el tamaño del archivo
     *
     * @throws FileException
     */
    public function testGetFileSize()
    {
        $size = (new FileHandler(self::$validFile))->getFileSize();

        $this->assertEquals(10, $size);
    }

    /**
     * Comprobar un archivo válido
     *
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testCheckFileExists()
    {
        (new FileHandler(self::$validFile))
            ->clearCache()
            ->checkFileExists();

        $this->expectException(FileException::class);

        (new FileHandler(self::$missingFile))
            ->clearCache()
            ->checkFileExists();
    }

    /**
     * Abrir un archivo
     *
     * @throws FileException
     */
    public function testOpenAndRead()
    {
        $handler = new FileHandler(self::$validFile);
        $handler->open('rb');
        $this->assertEquals('valid_file', $handler->read());
        $this->assertEquals('valid_file', $handler->readToString());
    }

    /**
     * Comprobar a cerrar un archivo
     *
     * @throws FileException
     */
    public function testClose()
    {
        $handler = new FileHandler(self::$validFile);
        $handler->open('rb');
        $handler->close();

        $this->expectException(FileException::class);
        $handler->close();
    }

    /**
     * Comprobar si es posible leer el archivo
     *
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testCheckIsReadable()
    {
        (new FileHandler(self::$validFile))
            ->clearCache()
            ->checkIsReadable();
    }

    /**
     * Comprobar la eliminación de un archivo
     *
     * @throws FileException
     */
    public function testDelete()
    {
        (new FileHandler(self::$validFile))->delete();

        $this->assertFileNotExists(self::$validFile);
    }
}
