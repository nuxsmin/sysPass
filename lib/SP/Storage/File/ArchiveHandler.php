<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
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
    const COMPRESS_EXTENSION = '.tar.gz';

    /**
     * @var PhpExtensionChecker
     */
    private $extensionChecker;
    /**
     * @var FileHandler
     */
    private $archive;

    /**
     * ArchiveHandler constructor.
     *
     * @param string              $archive
     * @param PhpExtensionChecker $extensionChecker
     */
    public function __construct(string $archive, PhpExtensionChecker $extensionChecker)
    {
        $this->extensionChecker = $extensionChecker;
        $this->archive = new FileHandler(self::makeArchiveName($archive));
    }

    /**
     * @param string $archive
     *
     * @return bool|string
     */
    private static function makeArchiveName(string $archive)
    {
        $archiveExtension = substr(self::COMPRESS_EXTENSION, 0, strrpos(self::COMPRESS_EXTENSION, '.'));

        if (is_file($archive)) {
            return substr($archive, 0, strrpos($archive, '.')) . $archiveExtension;
        }

        return $archive . $archiveExtension;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @param string      $directory
     * @param string|null $regex
     *
     * @throws CheckException
     * @throws FileException
     */
    public function compressDirectory(string $directory, string $regex = null)
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
     * @param string $file
     *
     * @throws CheckException
     * @throws FileException
     */
    public function compressFile(string $file)
    {
        $this->extensionChecker->checkPharAvailable(true);

        $archive = new PharData($this->archive->getFile());
        $archive->addFile($file, basename($file));
        $archive->compress(Phar::GZ);

        // Delete the non-compressed archive
        $this->archive->delete();
    }
}