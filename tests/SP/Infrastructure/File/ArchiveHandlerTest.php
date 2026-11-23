<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Infrastructure\File;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Core\PhpExtensionChecker;
use SP\Infrastructure\File\ArchiveHandler;

/**
 * Class ArchiveHandlerTest
 */
#[Group('unitary')]
class ArchiveHandlerTest extends TestCase
{
    private string $directory;

    #[Test]
    public function compressFileCompressesAndDeletesIntermediates(): void
    {
        $source = $this->directory . DIRECTORY_SEPARATOR . 'sysPass_db-hash.sql';
        file_put_contents($source, 'test_data');

        $archive = $this->directory . DIRECTORY_SEPARATOR . 'sysPass_db-hash.tar';
        $handler = new ArchiveHandler($source, new PhpExtensionChecker());

        $path = $handler->compressFile($source);

        $this->assertSame($archive . '.gz', $path);
        $this->assertSame('gz', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($source);
        $this->assertFileDoesNotExist($archive);
    }

    #[Test]
    public function compressDirectoryCompressesAndDeletesIntermediate(): void
    {
        $source = $this->directory . DIRECTORY_SEPARATOR . 'application';
        mkdir($source);

        file_put_contents($source . DIRECTORY_SEPARATOR . 'config.xml', 'test_data');

        $archive = $this->directory . DIRECTORY_SEPARATOR . 'sysPass_app-hash.tar';
        $handler = new ArchiveHandler($archive, new PhpExtensionChecker());

        $path = $handler->compressDirectory($source, '#.*#');

        $this->assertSame($archive . '.gz', $path);
        $this->assertSame('gz', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($archive);
        $this->assertFileExists($source . DIRECTORY_SEPARATOR . 'config.xml');
    }

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'archive_handler_' . uniqid('', true);

        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : @unlink($file);
        }

        @rmdir($this->directory);
    }

    private function deleteDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : @unlink($file);
        }

        @rmdir($directory);
    }
}
