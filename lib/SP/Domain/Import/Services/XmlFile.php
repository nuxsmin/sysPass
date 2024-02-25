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

namespace SP\Domain\Import\Services;

use DOMDocument;
use SP\Domain\Import\Ports\XmlFileService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;

use function SP\__u;
use function SP\logger;

/**
 * Class XmlFileImport
 *
 * @package Import
 */
final class XmlFile implements XmlFileService
{
    protected ?DOMDocument $xmlDOM = null;

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function __construct(protected readonly FileHandlerInterface $fileHandler)
    {
        $this->readXMLFile();
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ImportException
     * @throws FileException
     */
    protected function readXMLFile(): void
    {
        $this->fileHandler->checkIsReadable();

        libxml_use_internal_errors(true);

        // Cargar el XML con DOM
        $this->xmlDOM = new DOMDocument();
        $this->xmlDOM->formatOutput = false;
        $this->xmlDOM->preserveWhiteSpace = false;

        if ($this->xmlDOM->load($this->fileHandler->getFile(), LIBXML_PARSEHUGE) === false) {
            foreach (libxml_get_errors() as $error) {
                logger(__METHOD__ . ' - ' . $error->message);
            }

            throw ImportException::error(__u('Internal error'), __u('Unable to process the XML file'));
        }
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws ImportException
     */
    public function detectXMLFormat(): string
    {
        $nodes = $this->xmlDOM->getElementsByTagName('Generator');

        if ($nodes->length > 0) {
            $value = strtolower($nodes->item(0)->nodeValue);

            if ($value === 'keepass' || $value === 'syspass') {
                return $value;
            }
        }

        throw ImportException::error(
            __u('XML file not supported'),
            __u('Unable to guess the application which data was exported from')
        );
    }

    public function getXmlDOM(): DOMDocument
    {
        return $this->xmlDOM;
    }
}
