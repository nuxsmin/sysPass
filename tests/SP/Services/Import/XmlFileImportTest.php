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

namespace SP\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportException;
use SP\Services\Import\XmlFileImport;
use SP\Storage\File\FileException;

/**
 * Class XmlFileImportTest
 *
 * @package SP\Tests\Services\Import
 */
class XmlFileImportTest extends TestCase
{

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDetectXMLFormat()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $import = new XmlFileImport(FileImport::fromFilesystem($file));

        $this->assertEquals('syspass', $import->detectXMLFormat());

        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_keepass.xml';

        $import = new XmlFileImport(FileImport::fromFilesystem($file));

        $this->assertEquals('keepass', $import->detectXMLFormat());
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testInvalidFile()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data.csv';

        $this->expectException(ImportException::class);

        new XmlFileImport(FileImport::fromFilesystem($file));
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testEmptyFile()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_empty.xml';

        $import = new XmlFileImport(FileImport::fromFilesystem($file));

        $this->expectException(ImportException::class);

        $import->detectXMLFormat();
    }
}
