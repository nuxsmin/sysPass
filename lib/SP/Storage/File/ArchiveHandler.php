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
use SP\Core\PhpExtensionChecker;

/**
 * Class ArchiveHandler
 *
 * @package SP\Storage\File
 */
final class ArchiveHandler
{
    public const COMPRESS_EXTENSION = '.tar.gz';

    private PharData $archive;

    /**
     * @throws \SP\Core\Exceptions\CheckException
     */
    public function __construct(
        string $archive,
        PhpExtensionChecker $extensionChecker
    ) {
        $extensionChecker->checkPharAvailable(true);

        $this->archive = new PharData(self::makeArchiveName($archive));
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
                       strrpos($archive, '.') ?: strlen($archive)
                   ).$archiveExtension;
        }

        return $archive.$archiveExtension;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws FileException
     */
    public function compressDirectory(string $directory, ?string $regex = null): void
    {
        $this->archive->buildFromDirectory($directory, $regex);
        $this->archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        (new FileHandler($this->archive->getPath()))->delete();
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws FileException
     */
    public function compressFile(string $file): void
    {
        $this->archive->addFile($file, basename($file));
        $this->archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        (new FileHandler($this->archive->getPath()))->delete();
    }
}