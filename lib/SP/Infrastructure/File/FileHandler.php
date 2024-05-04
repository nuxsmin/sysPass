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

namespace SP\Infrastructure\File;

use RuntimeException;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Util\Util;
use SplFileObject;

use function SP\__;
use function SP\__u;
use function SP\logger;

/**
 * Class FileHandler
 */
final class FileHandler extends SplFileObject implements FileHandlerInterface
{
    public const  CHUNK_FACTOR = 3;

    /**
     * FileHandler constructor.
     */
    public function __construct(private readonly string $file, private readonly string $mode = 'r')
    {
        parent::__construct($this->file, $this->mode);
    }

    /**
     * Writes data into file
     *
     * @throws FileException
     */
    public function write(string $data): FileHandlerInterface
    {
        if ($this->fwrite($data) === false) {
            throw FileException::error(sprintf(__('Unable to read/write the file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * Reads data from file into a string
     *
     * @throws FileException
     */
    public function readToString(): string
    {
        $this->autoDetectEOL();

        $data = $this->fread($this->getSize());

        if ($data === false) {
            throw FileException::error(sprintf(__('Unable to read from file (%s)'), $this->file));
        }

        return $data;
    }

    /**
     * Reads data from a CSV file
     *
     * @throws FileException
     */
    public function readFromCsv(string $delimiter): iterable
    {
        $this->autoDetectEOL();

        while (!$this->eof()) {
            $data = $this->fgetcsv($delimiter);

            if ($data === false) {
                throw FileException::error(__u('Error while reading the CSV file file'), $this->file);
            }

            yield $data;
        }
    }

    /**
     * Reads data from a file line by line
     */
    public function read(): iterable
    {
        $this->autoDetectEOL();

        while (!$this->eof()) {
            yield $this->fgets();
        }
    }

    /**
     * Saves a string into a file
     *
     * @throws FileException
     */
    public function save(string $data): FileHandlerInterface
    {
        $this->lock();

        if ($this->fwrite($data) === false) {
            throw FileException::error(sprintf(__('Unable to read/write the file (%s)'), $this->file));
        }

        $this->unlock();

        return $this;
    }

    /**
     * Lock the file
     *
     * @throws FileException
     */
    private function lock(): void
    {
        if (!$this->flock(LOCK_EX)) {
            throw FileException::error(sprintf(__('Unable to obtain a lock (%s)'), $this->file));
        }

        logger(sprintf('File locked: %s', $this->file));
    }

    /**
     * Lock the file
     *
     * @throws FileException
     */
    private function unlock(): void
    {
        if (!$this->flock(LOCK_UN)) {
            throw FileException::error(sprintf(__('Unable to release a lock (%s)'), $this->file));
        }

        logger(sprintf('File unlocked: %s', $this->file));
    }

    /**
     * @param callable|null $chunker
     * @param float|null $rate
     *
     * @throws FileException
     */
    public function readChunked(callable $chunker = null, ?float $rate = null): void
    {
        $maxRate = Util::getMaxDownloadChunk() / self::CHUNK_FACTOR;

        if ($rate === null || $rate > $maxRate) {
            $rate = (float)$maxRate;
        }

        while (!$this->eof()) {
            if ($chunker !== null) {
                $chunker($this->fread(round($rate)));
            } else {
                print $this->fread(round($rate));
                ob_flush();
                flush();
            }
        }
    }

    /**
     * Opens the file
     *
     * @return FileHandler
     * @throws FileException
     */
    public function open(string $mode = 'rb', ?bool $lock = false): FileHandlerInterface
    {
        try {
            $file = new self($mode);

            if ($lock) {
                $file->lock();
            }
        } catch (RuntimeException) {
            throw FileException::error(sprintf(__('Unable to open the file (%s)'), $this->file));
        }

        return $file;
    }

    /**
     * Checks if the file is writable
     *
     * @throws FileException
     */
    public function checkIsWritable(): FileHandlerInterface
    {
        if (!$this->isWritable()) {
            throw FileException::error(sprintf(__('Unable to write in file (%s)'), $this->file));
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
        if (!$this->isReadable()) {
            throw FileException::error(sprintf(__('File not found (%s)'), $this->file));
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
        $size = $this->getSize();

        if ($size === false
            || ($isExceptionOnZero === true && $size === 0)
        ) {
            throw FileException::error(sprintf(__('Unable to read/write file (%s)'), $this->file));
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
            throw FileException::error(sprintf(__('Unable to delete file (%s)'), $this->file));
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
        if (!$this->isReadable()) {
            throw FileException::error(sprintf(__('Unable to read/write file (%s)'), $this->file));
        }

        return $this;
    }

    /**
     * @throws FileException
     */
    public function getFileTime(): int
    {
        $this->checkIsReadable();

        return $this->getMTime() ?: 0;
    }

    /**
     * @param int $permissions Octal permissions
     *
     * @return FileHandlerInterface
     * @throws FileException
     */
    public function chmod(int $permissions): FileHandlerInterface
    {
        if (chmod($this->file, $permissions) === false) {
            throw FileException::error(sprintf(__('Unable to set permissions for file (%s)'), $this->file));
        }

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
        return sha1_file($this->file);
    }

    /**
     * @return void
     */
    private function autoDetectEOL(): void
    {
        ini_set('auto_detect_line_endings', true);
    }
}
