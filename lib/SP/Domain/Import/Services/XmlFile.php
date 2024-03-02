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

/**
 * Class XmlFile
 */
final class XmlFile implements XmlFileService
{
    private readonly DOMDocument $document;

    private function __construct()
    {
        $this->createDocument();
    }

    /**
     * @return void
     */
    private function createDocument(): void
    {
        $this->document = new DOMDocument();
        $this->document->formatOutput = false;
        $this->document->preserveWhiteSpace = false;
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function builder(FileHandlerInterface $fileHandler): XmlFileService
    {
        $fileHandler->checkIsReadable();

        $self = new self();
        $self->readXMLFile($fileHandler->getFile());

        return $self;
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ImportException
     */
    protected function readXMLFile(string $file): void
    {
        libxml_use_internal_errors(true);

        $this->createDocument();

        if ($this->document->load($file, LIBXML_PARSEHUGE) === false) {
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
    public function detectFormat(): XmlFormat
    {
        $nodes = $this->document->getElementsByTagName('Generator');

        try {
            return XmlFormat::from(strtolower($nodes->item(0)?->nodeValue));
        } catch (ValueError $e) {
            throw ImportException::error(
                __u('XML file not supported'),
                __u('Unable to guess the application which data was exported from'),
                $e->getCode(),
                $e
            );
        }
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }
}
