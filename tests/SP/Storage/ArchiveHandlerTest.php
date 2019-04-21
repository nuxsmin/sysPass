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
use SP\Core\Exceptions\CheckException;
use SP\Core\PhpExtensionChecker;
use SP\Storage\File\ArchiveHandler;
use SP\Storage\File\FileException;
use UnexpectedValueException;

/**
 * Class ArchiveHandlerTest
 *
 * @package SP\Tests\Storage
 */
class ArchiveHandlerTest extends TestCase
{
    const  ARCHIVE = TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive';

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function testCompressFile()
    {
        $archive = TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive_file';

        $handler = new ArchiveHandler($archive, new PhpExtensionChecker());
        $handler->compressFile(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.xml');

        $this->assertFileExists($archive . ArchiveHandler::COMPRESS_EXTENSION);
    }

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function testCompressInvalidFile()
    {
        $this->expectException(RuntimeException::class);

        $archive = TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive_file';

        $handler = new ArchiveHandler($archive, new PhpExtensionChecker());
        $handler->compressFile(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'non_existant_file');
    }

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function testCompressDirectory()
    {
        $archive = TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive_dir';

        $handler = new ArchiveHandler($archive, new PhpExtensionChecker());
        $handler->compressDirectory(RESOURCE_DIR);

        $this->assertFileExists($archive . ArchiveHandler::COMPRESS_EXTENSION);
    }

    /**
     * @throws CheckException
     * @throws FileException
     */
    public function testCompressInvalidDirectory()
    {
        $this->expectException(UnexpectedValueException::class);

        $archive = TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive_dir';

        $handler = new ArchiveHandler($archive, new PhpExtensionChecker());
        $handler->compressDirectory(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'non_existant_dir');
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        array_map('unlink', glob(TMP_PATH . DIRECTORY_SEPARATOR . 'test_archive_*'));
    }
}
