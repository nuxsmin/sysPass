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

namespace SP\Domain\Import\Ports;

use DOMDocument;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\XmlFormat;
use SP\Infrastructure\File\FileException;

/**
 * Class XmlFileImport
 *
 * @package Import
 */
interface XmlFileService
{
    /**
     * Detects the XML format.
     * If {@link  XmlFileService::builder} is not called first, it will fail.
     *
     * @throws ImportException
     */
    public function detectFormat(): XmlFormat;

    /**
     * Returns the XML document.
     * If {@link  XmlFileService::builder} is not called first, it will return a blank document.
     *
     * @return DOMDocument
     */
    public function getDocument(): DOMDocument;

    /**
     * Builds a new instance of {@link XmlFileService} with the XML file loaded.
     *
     * @throws ImportException
     * @throws FileException
     */
    public function builder(FileHandlerInterface $fileHandler): XmlFileService;
}
