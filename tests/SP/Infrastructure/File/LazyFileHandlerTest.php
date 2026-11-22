<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Exceptions\FileException;
use SP\Infrastructure\File\LazyFileHandler;

#[Group('unitary')]
final class LazyFileHandlerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/syspass-lazy-file-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0770, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function constructDoesNotCreateOrTruncateFileUntilIoOccurs(): void
    {
        $missingFile = $this->tempDir . '/missing.txt';
        $lazyMissing = new LazyFileHandler($missingFile, 'r');

        self::assertFileDoesNotExist($missingFile);
        self::assertSame($missingFile, $lazyMissing->getFile());

        $existingFile = $this->tempDir . '/existing.txt';
        file_put_contents($existingFile, 'preserved-content');
        $lazyWrite = new LazyFileHandler($existingFile, 'wb');

        self::assertSame('preserved-content', file_get_contents($existingFile));
        unset($lazyMissing, $lazyWrite);
    }

    #[Test]
    public function openWithWriteModeWorksOnMissingFileEvenWhenConstructedWithReadMode(): void
    {
        $file = $this->tempDir . '/created-via-open.txt';
        $lazy = new LazyFileHandler($file, 'r');

        $opened = $lazy->open('wb');
        $opened->write('written-via-open');

        self::assertSame('written-via-open', file_get_contents($file));
    }

    #[Test]
    public function openWithReadModePreservesExistingContentEvenWhenConstructedWithTruncatingMode(): void
    {
        $file = $this->tempDir . '/preserve-on-read.txt';
        file_put_contents($file, 'original-content');
        $lazy = new LazyFileHandler($file, 'wb+');

        $opened = $lazy->open('rb');

        self::assertSame('original-content', $opened->readToString());
        self::assertSame('original-content', file_get_contents($file));
    }

    #[Test]
    public function openWithReadModeOnMissingFileThrowsFileExceptionWithoutCreatingFile(): void
    {
        $file = $this->tempDir . '/should-not-exist.txt';
        $lazy = new LazyFileHandler($file, 'wb');

        try {
            $lazy->open('rb');
            self::fail('Expected FileException when opening missing file in rb mode.');
        } catch (FileException $e) {
            self::assertStringContainsString($file, $e->getMessage());
            self::assertFileDoesNotExist($file);
        }
    }

    #[Test]
    public function openWithLockAcquiresExclusiveLock(): void
    {
        $file = $this->tempDir . '/locked.txt';
        $lazy = new LazyFileHandler($file, 'r');

        $opened = $lazy->open('wb', true);
        $opened->write('locked-data');

        $probe = fopen($file, 'rb');
        self::assertIsResource($probe);

        try {
            $wouldBlock = 0;
            $lockedByProbe = flock($probe, LOCK_EX | LOCK_NB, $wouldBlock);
            self::assertFalse($lockedByProbe);
            self::assertSame(1, $wouldBlock);
        } finally {
            fclose($probe);
        }
    }
}
