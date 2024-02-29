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
use ValueError;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class XmlFile
 */
final class XmlFile implements XmlFileService
{
    private ?DOMDocument $document = null;

    /**
     * @throws ImportException
     */
    private function __construct(protected readonly FileHandlerInterface $fileHandler)
    {
        $this->readXMLFile();
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ImportException
     */
    protected function readXMLFile(): void
    {
        libxml_use_internal_errors(true);

        // Cargar el XML con DOM
        $this->document = new DOMDocument();
        $this->document->formatOutput = false;
        $this->document->preserveWhiteSpace = false;

        if ($this->document->load($this->fileHandler->getFile(), LIBXML_PARSEHUGE) === false) {
            foreach (libxml_get_errors() as $error) {
                logger(__METHOD__ . ' - ' . $error->message);
            }

            throw ImportException::error(__u('Internal error'), __u('Unable to process the XML file'));
        }
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public static function builder(FileHandlerInterface $fileHandler): XmlFileService
    {
        $fileHandler->checkIsReadable();

        return new self($fileHandler);
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws ImportException
     */
    public function detectFormat(): XmlFormat
    {
        $nodes = $this->document->getElementsByTagName('Generator');

        try {
            if ($nodes->length > 0) {
                return XmlFormat::from(strtolower($nodes->item(0)->nodeValue));
            }
        } catch (ValueError $e) {
            processException($e);
        }

        throw ImportException::error(
            __u('XML file not supported'),
            __u('Unable to guess the application which data was exported from')
        );
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }
}
