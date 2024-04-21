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

namespace SP\Infrastructure\File;

use Phar;
use PharData;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\File\Ports\ArchiveHandlerInterface;

/**
 * Class ArchiveHandler
 */
final class ArchiveHandler implements ArchiveHandlerInterface
{
    public const COMPRESS_EXTENSION = '.tar.gz';

    private readonly PharData $archive;

    public function __construct(string $archive, PhpExtensionCheckerService $phpExtensionCheckerService)
    {
        $phpExtensionCheckerService->checkPhar(true);

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
                   ) . $archiveExtension;
        }

        return $archive . $archiveExtension;
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
    public function compressFile(string $file): string
    {
        $this->archive->addFile($file, basename($file));
        $this->archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        (new FileHandler($this->archive->getPath()))->delete();

        return $this->archive->getFileInfo()->getPathname();
    }
}
