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

namespace SP\Domain\File\Ports;

use SP\Infrastructure\File\FileException;

/**
 * Class FileHandler
 */
interface FileHandlerInterface
{
    /**
     * Writes data into file
     *
     * @throws FileException
     */
    public function write(string $data): FileHandlerInterface;

    /**
     * Opens the file
     *
     * @return resource
     * @throws FileException
     */
    public function open(string $mode = 'rb', ?bool $lock = false): FileHandlerInterface;

    /**
     * Reads data from file into a string
     *
     * @throws FileException
     */
    public function readToString(): string;

    /**
     * Saves a string into a file
     *
     * @throws FileException
     */
    public function save(string $data): FileHandlerInterface;

    /**
     * @param callable|null $chunker
     * @param float|null $rate
     *
     * @throws FileException
     */
    public function readChunked(callable $chunker = null, ?float $rate = null): void;

    /**
     * Checks if the file is writable
     *
     * @throws FileException
     */
    public function checkIsWritable(): FileHandlerInterface;

    /**
     * Checks if the file exists
     *
     * @throws FileException
     */
    public function checkFileExists(): FileHandlerInterface;

    public function getFile(): string;

    /**
     * @throws FileException
     */
    public function getFileSize(bool $isExceptionOnZero = false): int;

    /**
     * Clears the stat cache for the given file
     */
    public function clearCache(): FileHandlerInterface;

    /**
     * Deletes a file
     *
     * @throws FileException
     */
    public function delete(): FileHandlerInterface;

    /**
     * Returns the content type in MIME format
     *
     * @throws FileException
     */
    public function getFileType(): string;

    /**
     * Checks if the file is readable
     *
     * @throws FileException
     */
    public function checkIsReadable(): FileHandlerInterface;

    /**
     * @throws FileException
     */
    public function getFileTime(): int;

    /**
     * Changes file permissions
     *
     * @param int $permissions Octal permissions
     *
     * @return FileHandlerInterface
     * @throws FileException
     */
    public function chmod(int $permissions): FileHandlerInterface;

    public function getBase(): string;

    public function getName(): string;

    public function getHash(): string;

    /**
     * Reads data from a CSV file
     *
     * @throws FileException
     */
    public function readFromCsv(string $delimiter): iterable;

    /**
     * Reads data from a file line by line
     */
    public function read(): iterable;
}
