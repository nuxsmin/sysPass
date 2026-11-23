<?php
/**
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

namespace SP\Infrastructure\File;

use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\File\Ports\FileHandlerInterface;

use function SP\__;

/**
 * Defers opening the underlying SplFileObject until an I/O operation is invoked.
 */
final class LazyFileHandler implements FileHandlerInterface
{
    private ?FileHandler $handler = null;

    public function __construct(
        private readonly string $file,
        private readonly string $mode = 'r'
    ) {
    }

    public function write(string $data): FileHandlerInterface
    {
        $this->handler()->write($data);

        return $this;
    }

    public function open(string $mode = 'rb', ?bool $lock = false): FileHandlerInterface
    {
        return FileHandler::openForPath($this->file, $mode, $lock);
    }

    public function readToString(): string
    {
        return $this->handler()->readToString();
    }

    public function save(string $data): FileHandlerInterface
    {
        $this->handler()->save($data);

        return $this;
    }

    public function readChunked(?callable $chunker = null, ?float $rate = null): void
    {
        $this->handler()->readChunked($chunker, $rate);
    }

    public function checkIsWritable(): FileHandlerInterface
    {
        $this->handler()->checkIsWritable();

        return $this;
    }

    public function checkFileExists(): FileHandlerInterface
    {
        $this->handler()->checkFileExists();

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getFileSize(bool $isExceptionOnZero = false): int
    {
        return $this->handler()->getFileSize($isExceptionOnZero);
    }

    public function clearCache(): FileHandlerInterface
    {
        clearstatcache(true, $this->file);

        return $this;
    }

    public function delete(): FileHandlerInterface
    {
        $this->handler = null;

        if (file_exists($this->file) && @unlink($this->file) === false) {
            throw FileException::error(sprintf(__('Unable to delete file (%s)'), $this->file));
        }

        return $this;
    }

    public function getFileType(): string
    {
        return $this->handler()->getFileType();
    }

    public function checkIsReadable(): FileHandlerInterface
    {
        $this->handler()->checkIsReadable();

        return $this;
    }

    public function getFileTime(): int
    {
        return $this->handler()->getFileTime();
    }

    public function chmod(int $permissions): FileHandlerInterface
    {
        $this->handler()->chmod($permissions);

        return $this;
    }

    public function getBase(): string
    {
        return dirname($this->file);
    }

    public function getName(): string
    {
        return basename($this->file);
    }

    public function getHash(): string
    {
        return $this->handler()->getHash();
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    public function readFromCsv(string $delimiter): iterable
    {
        return $this->handler()->readFromCsv($delimiter);
    }

    /**
     * @return iterable<int, string|false>
     */
    public function read(): iterable
    {
        return $this->handler()->read();
    }

    private function handler(): FileHandler
    {
        return $this->handler ??= new FileHandler($this->file, $this->mode);
    }
}
