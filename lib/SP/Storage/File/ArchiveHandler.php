<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage\File;


use Phar;
use PharData;
use SP\Core\Exceptions\CheckException;
use SP\Core\PhpExtensionChecker;

/**
 * Class ArchiveHandler
 *
 * @package SP\Storage\File
 */
final class ArchiveHandler
{
    public const COMPRESS_EXTENSION = '.tar.gz';

    private PhpExtensionChecker $extensionChecker;
    private FileHandler $archive;

    public function __construct(
        string              $archive,
        PhpExtensionChecker $extensionChecker
    )
    {
        $this->extensionChecker = $extensionChecker;
        $this->archive = new FileHandler(self::makeArchiveName($archive));
    }

    private static function makeArchiveName(string $archive): string
    {
        $archiveExtension = substr(
            self::COMPRESS_EXTENSION,
            0,
            strrpos(self::COMPRESS_EXTENSION, '.')
        );

        if (is_file($archive)) {
            return substr(
                    $archive,
                    0,
                    strrpos($archive, '.')
                ) . $archiveExtension;
        }

        return $archive . $archiveExtension;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws CheckException
     * @throws FileException
     */
    public function compressDirectory(
        string  $directory,
        ?string $regex = null
    ): void
    {
        $this->extensionChecker->checkPharAvailable(true);

        $archive = new PharData($this->archive->getFile());
        $archive->buildFromDirectory($directory, $regex);
        $archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        $this->archive->delete();
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws CheckException
     * @throws FileException
     */
    public function compressFile(string $file): void
    {
        $this->extensionChecker->checkPharAvailable(true);

        $archive = new PharData($this->archive->getFile());
        $archive->addFile($file, basename($file));
        $archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        $this->archive->delete();
    }
}