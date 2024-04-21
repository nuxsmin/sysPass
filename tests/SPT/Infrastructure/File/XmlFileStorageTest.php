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

namespace SPT\Infrastructure\File;

use DOMDocument;
use DOMException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\XmlFileStorage;
use SPT\UnitaryTestCase;

/**
 * Class XmlFileStorageTest
 */
#[Group('unitary')]
class XmlFileStorageTest extends UnitaryTestCase
{

    private const XML =
        '<?xml version="1.0" encoding="utf-8"?>' .
        '<config>' .
        '<objects class="stdClass">' .
        '<prop1>test</prop1>' .
        '<prop2>123</prop2>' .
        '</objects>' .
        '<strings>' .
        '<item type="strings">a</item>' .
        '<item type="strings">b</item>' .
        '<item type="strings">c</item>' .
        '</strings>' .
        '<test>123</test>' .
        '</config>';

    private FileHandlerInterface|MockObject $fileHandler;
    private XmlFileStorage                  $xmlFileStorage;

    /**
     * @throws FileException
     */
    public function testGetFileTime()
    {
        $time = self::$faker->unixTime();

        $this->fileHandler->expects($this->once())
                          ->method('getFileTime')
                          ->willReturn($time);

        $this->assertEquals($time, $this->xmlFileStorage->getFileTime());
    }

    /**
     * @throws FileException
     * @throws DOMException
     */
    public function testSave()
    {
        $document = new DOMDocument();
        $document->formatOutput = true;
        $document->loadXML(self::XML);

        $data = [
            'test' => 123,
            'strings' => ['a', 'b', 'c'],
            'objects' => (object)['prop1' => 'test', 'prop2' => 123]
        ];

        $this->fileHandler
            ->expects($this->once())
            ->method('checkIsWritable');

        $this->fileHandler
            ->expects($this->once())
            ->method('save')
            ->with(
                self::callback(static function (string $outXml) use ($document) {
                    return $outXml === $document->saveXML();
                })
            );

        $this->xmlFileStorage->save($data, 'config');
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testLoad()
    {
        $file = self::$faker->filePath();
        file_put_contents($file, self::XML) or die('Unable to write file');

        $this->fileHandler
            ->expects($this->once())
            ->method('checkIsReadable');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFileSize');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFile')
            ->willReturn($file);

        $out = $this->xmlFileStorage->load('config');

        $expected = [
            'objects' => ['prop1' => 'test', 'prop2' => 123, '__class__' => 'stdClass'],
            'strings' => ['a', 'b', 'c'],
            'test' => 123
        ];

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testLoadWithWrongFile()
    {
        $this->fileHandler
            ->expects($this->once())
            ->method('checkIsReadable');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFileSize');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFile')
            ->willReturn(self::$faker->filePath());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->xmlFileStorage->load('config');
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testLoadWithNoNodes()
    {
        $file = self::$faker->filePath();
        file_put_contents($file, self::XML) or die('Unable to write file');

        $this->fileHandler
            ->expects($this->once())
            ->method('checkIsReadable');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFileSize');

        $this->fileHandler
            ->expects($this->once())
            ->method('getFile')
            ->willReturn($file);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('XML node does not exist');

        $this->xmlFileStorage->load();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->createMock(FileHandlerInterface::class);
        $this->xmlFileStorage = new XmlFileStorage($this->fileHandler);
    }
}
