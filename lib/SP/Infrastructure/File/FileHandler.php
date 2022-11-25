<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\File;

use SP\Util\Util;
use function SP\logger;

/**
 * Class FileHandler
 *
 * @package SP\Infrastructure\File;
 */
final class FileHandler implements FileHandlerInterface
{
    private const CHUNK_LENGTH = 8192;
    public const  CHUNK_FACTOR = 3;
    protected string $file;
    /**
     * @var resource
     */
    protected    $handle;
    private bool $locked = false;

    /**
     * FileHandler constructor.
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Writes data into file
     *
     * @throws FileException
     */
    public function write($data): FileHandlerInterface
    {
        if (!is_resource($this->handle)) {
            $this->open('wb');
        }

        if (@fwrite($this->handle, $data) === false) {
            throw new FileException(sprintf(__('Unable to read/write the file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Opens the file
     *
     * @return resource
     * @throws FileException
     */
    public function open(string $mode = 'rb', ?bool $lock = false)
    {
        $this->handle = @fopen($this->file, $mode);

        if ($lock && $this->locked === false) {
            $this->lock();
        }

        if ($this->handle === false) {
            throw new FileException(sprintf(__('Unable to open the file (%s)'), $this->file));
        }

        return $this->handle;
    }

    /**
     * Lock the file
     *
     * @throws FileException
     */
    private function lock(int $mode = LOCK_EX): void
    {
        $this->locked = flock($this->handle, $mode);

        if (!$this->locked) {
            throw new FileException(sprintf(__('Unable to obtain a lock (%s)'), $this->file));
        }

        logger(sprintf('File locked: %s', $this->file));
    }

    /**
     * Reads data from file into a string
     *
     * @throws FileException
     */
    public function readToString(): string
    {
        $data = file_get_contents($this->file);

        if ($data === false) {
            throw new FileException(sprintf(__('Unable to read from file (%s)'), $this->file));
        }

        return $data;
    }

    /**
     * Reads data from file into an array
     *
     * @throws FileException
     */
    public function readToArray(): array
    {
        $data = @file($this->file, FILE_SKIP_EMPTY_LINES);

        if ($data === false) {
            throw new FileException(sprintf(__('Unable to read from file (%s)'), $this->file));
        }

        return $data;
    }

    /**
     * Saves a string into a file
     *
     * @throws FileException
     */
    public function save(string $data): FileHandlerInterface
    {
        if (file_put_contents($this->file, $data, LOCK_EX) === false) {
            throw new FileException(sprintf(__('Unable to read/write the file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Reads data from file
     *
     * @throws FileException
     */
    public function read(): string
    {
        if (!is_resource($this->handle)) {
            $this->open();
        }

        $data = '';

        while (!feof($this->handle)) {
            $data .= fread($this->handle, self::CHUNK_LENGTH);
        }

        $this->close();

        return $data;
    }

    /**
     * Closes the file
     *
     * @throws FileException
     */
    public function close(): FileHandlerInterface
    {
        if ($this->locked) {
            $this->unlock();
        }

        if (!is_resource($this->handle) || @fclose($this->handle) === false) {
            throw new FileException(sprintf(__('Unable to close the file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Unlock the file
     */
    private function unlock(): void
    {
        $this->locked = !flock($this->handle, LOCK_UN);
    }

    /**
     * @param  callable|null  $chunker
     * @param  float|null  $rate
     *
     * @throws FileException
     */
    public function readChunked(
        callable $chunker = null,
        ?float $rate = null
    ): void {
        $maxRate = Util::getMaxDownloadChunk() / self::CHUNK_FACTOR;

        if ($rate === null || $rate > $maxRate) {
            $rate = (float)$maxRate;
        }

        if (!is_resource($this->handle)) {
            $this->open();
        }

        while (!feof($this->handle)) {
            if ($chunker !== null) {
                $chunker(fread($this->handle, round($rate)));
            } else {
                print fread($this->handle, round($rate));
                ob_flush();
                flush();
            }
        }

        $this->close();
    }

    /**
     * Checks if the file is writable
     *
     * @throws FileException
     */
    public function checkIsWritable(): FileHandlerInterface
    {
        if (!is_writable($this->file) && @touch($this->file) === false) {
            throw new FileException(sprintf(__('Unable to write in file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Checks if the file exists
     *
     * @throws FileException
     */
    public function checkFileExists(): FileHandlerInterface
    {
        if (!file_exists($this->file)) {
            throw new FileException(sprintf(__('File not found (%s)'), $this->file));
        }

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @throws FileException
     */
    public function getFileSize(bool $isExceptionOnZero = false): int
    {
        $size = filesize($this->file);

        if ($size === false
            || ($isExceptionOnZero === true && $size === 0)) {
            throw new FileException(sprintf(__('Unable to read/write file (%s)'), $this->file));
        }

        return $size;
    }

    /**
     * Clears the stat cache for the given file
     */
    public function clearCache(): FileHandlerInterface
    {
        clearstatcache(true, $this->file);

        return $this;
    }

    /**
     * Deletes a file
     *
     * @throws FileException
     */
    public function delete(): FileHandlerInterface
    {
        if (file_exists($this->file) && @unlink($this->file) === false) {
            throw new FileException(sprintf(__('Unable to delete file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Returns the content type in MIME format
     *
     * @throws FileException
     */
    public function getFileType(): string
    {
        $this->checkIsReadable();

        return mime_content_type($this->file);
    }

    /**
     * Checks if the file is readable
     *
     * @throws FileException
     */
    public function checkIsReadable(): FileHandlerInterface
    {
        if (!is_readable($this->file)) {
            throw new FileException(sprintf(__('Unable to read/write file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * @throws FileException
     */
    public function getFileTime(): int
    {
        $this->checkIsReadable();

        return filemtime($this->file) ?: 0;
    }
}
