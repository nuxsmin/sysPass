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

namespace SPT\Domain\Import\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\XmlFile;
use SP\Domain\Import\Services\XmlFormat;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SPT\UnitaryTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class XmlFileTest
 *
 */
#[Group('unitary')]
class XmlFileTest extends UnitaryTestCase
{
    private const KEEPASS_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                 'data_keepass.xml';
    private const SYSPASS_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                 'data_syspass.xml';

    public static function fileFormatProvider(): array
    {
        return [
            [self::KEEPASS_FILE, XmlFormat::Keepass],
            [self::SYSPASS_FILE, XmlFormat::Syspass],
        ];
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    #[DataProvider('fileFormatProvider')]
    public function testDetectFormat(string $file, XmlFormat $format)
    {
        $fileHandler = new FileHandler($file);

        $xmlFile = new XmlFile();
        $out = $xmlFile->builder($fileHandler)->detectFormat();

        $this->assertEquals($format, $out);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDetectFormatWithException()
    {
        $fileHandler = new FileHandler(self::$faker->filePath(), 'w');
        $fileHandler->write('<?xml version="1.0" encoding="utf-8" standalone="yes"?><Test></Test>');

        $xmlFile = new XmlFile();

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('XML file not supported');

        $xmlFile->builder($fileHandler)->detectFormat();
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testBuilder()
    {
        $fileHandler = new FileHandler(self::$faker->filePath(), 'w');
        $fileHandler->write('<?xml version="1.0" encoding="utf-8" standalone="yes"?><Test></Test>');

        $xmlFile = new XmlFile();
        $out = $xmlFile->builder($fileHandler);

        $this->assertFalse(spl_object_id($xmlFile) === spl_object_id($out));
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testBuilderWithException()
    {
        $fileHandler = new FileHandler(self::$faker->filePath(), 'w');
        $fileHandler->write('<?xml version="1.0" encoding="utf-8" standalone="yes"?><Test>');

        $xmlFile = new XmlFile();

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('Internal error');

        $xmlFile->builder($fileHandler);
    }

    public function testGetDocument()
    {
        $xmlFile = new XmlFile();
        $out = $xmlFile->getDocument();

        $this->assertFalse($out->formatOutput);
        $this->assertFalse($out->preserveWhiteSpace);
    }
}
