<?php

declare(strict_types=1);
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

namespace SP\Tests\Stubs;

use SP\Domain\File\Ports\FileHandlerInterface;

/**
 * In-memory FileHandlerInterface fake backed by a string.
 */
final class InMemoryFileHandler implements FileHandlerInterface
{
    public function __construct(
        private string $file = '',
        private string $content = ''
    ) {
    }

    public function write(string $data): FileHandlerInterface
    {
        $this->content .= $data;

        return $this;
    }

    public function open(string $mode = 'rb', ?bool $lock = false): FileHandlerInterface
    {
        return $this;
    }

    public function readToString(): string
    {
        return $this->content;
    }

    public function save(string $data): FileHandlerInterface
    {
        $this->content = $data;

        return $this;
    }

    public function readChunked(callable $chunker = null, ?float $rate = null): void
    {
        $chunker?->__invoke($this->content);
    }

    public function checkIsWritable(): FileHandlerInterface
    {
        return $this;
    }

    public function checkFileExists(): FileHandlerInterface
    {
        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getFileSize(bool $isExceptionOnZero = false): int
    {
        return strlen($this->content);
    }

    public function clearCache(): FileHandlerInterface
    {
        return $this;
    }

    public function delete(): FileHandlerInterface
    {
        $this->content = '';

        return $this;
    }

    public function getFileType(): string
    {
        return 'text/xml';
    }

    public function checkIsReadable(): FileHandlerInterface
    {
        return $this;
    }

    public function getFileTime(): int
    {
        return 0;
    }

    public function chmod(int $permissions): FileHandlerInterface
    {
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
        return sha1($this->content);
    }

    public function readFromCsv(string $delimiter): iterable
    {
        $lines = preg_split('/\R/', $this->content) ?: [];

        foreach ($lines as $line) {
            yield explode($delimiter, $line);
        }
    }

    public function read(): iterable
    {
        $lines = preg_split('/\R/', $this->content) ?: [];

        foreach ($lines as $line) {
            yield $line;
        }
    }
}
