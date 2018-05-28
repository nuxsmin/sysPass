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

/**
 * Class FileHandlerTest
 *
 * Tests unitarios para comprobar el funcionamiento de la clase SP\Storage\FileHandler
 */
class FileHandlerTest extends TestCase
{
    /**
     * @var string Archvivo de prueba válido
     */
    protected $validFile;
    /**
     * @var string Archvivo de prueba inválido
     */
    protected $invalidFile;

    /**
     * Comprobar la escritura de texto en un archivo
     *
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testWrite()
    {
        $handler = new FileHandler($this->validFile);
        $handler->write('valid_file');
        $this->assertEquals('valid_file', $handler->readString());
        $handler->close();
    }

    /**
     * Comprobar si es posible escribir en el archivo
     *
     * @depends testWrite
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testCheckIsWritable()
    {
        (new FileHandler($this->validFile))
            ->clearCache()
            ->checkIsWritable();

        $this->expectException(FileException::class);

        (new FileHandler($this->invalidFile))
            ->clearCache()
            ->checkIsWritable();
    }

    /**
     * Comprobar el tamaño del archivo
     *
     * @depends testWrite
     * @throws FileException
     */
    public function testGetFileSize()
    {
        $size = (new FileHandler($this->validFile))->getFileSize();

        $this->assertEquals(10, $size);
    }

    /**
     * Comprobar un archivo válido
     *
     * @depends testWrite
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testCheckFileExists()
    {
        (new FileHandler($this->validFile))
            ->clearCache()
            ->checkFileExists();

        $this->expectException(FileException::class);

        (new FileHandler($this->invalidFile))
            ->clearCache()
            ->checkFileExists();
    }

    /**
     * Abrir un archivo
     *
     * @depends testWrite
     * @throws FileException
     */
    public function testOpenAndRead()
    {
        $handler = new FileHandler($this->validFile);
        $handler->open('rb');
        $this->assertEquals('valid_file', $handler->read());
        $this->assertEquals('valid_file', $handler->readString());
    }

    /**
     * Comprobar a cerrar un archivo
     *
     * @depends testWrite
     * @throws FileException
     */
    public function testClose()
    {
        $handler = new FileHandler($this->validFile);
        $handler->open('rb');
        $handler->close();

        $this->expectException(FileException::class);
        $handler->close();
    }

    /**
     * Comprobar si es posible leer el archivo
     *
     * @depends testWrite
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testCheckIsReadable()
    {
        (new FileHandler($this->validFile))
            ->clearCache()
            ->checkIsReadable();

        $this->expectException(FileException::class);

        (new FileHandler($this->invalidFile))
            ->clearCache()
            ->checkIsReadable();
    }

    /**
     * Comprobar la eliminación de un archivo
     *
     * @depends testWrite
     * @doesNotPerformAssertions
     * @throws FileException
     */
    public function testDelete()
    {
        (new FileHandler($this->validFile))->delete();

        $this->expectException(FileException::class);

        (new FileHandler($this->invalidFile))->delete();
    }

    protected function setUp()
    {
        $this->validFile = TEST_ROOT . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'valid_file.test';
        $this->invalidFile = TEST_ROOT . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'invalid_file.test';
    }
}
