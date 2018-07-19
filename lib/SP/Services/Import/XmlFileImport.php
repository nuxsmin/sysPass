<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Import;

/**
 * Class XmlFileImport
 *
 * @package Import
 */
class XmlFileImport
{
    /**
     * @var FileImport
     */
    protected $fileImport;
    /**
     * @var \DOMDocument
     */
    protected $xmlDOM;

    /**
     * XmlFileImport constructor.
     *
     * @param FileImport $fileImport
     *
     * @throws ImportException
     * @throws \SP\Storage\FileException
     */
    public function __construct(FileImport $fileImport)
    {
        $this->fileImport = $fileImport;

        $this->readXMLFile();
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ImportException
     * @throws \SP\Storage\FileException
     */
    protected function readXMLFile()
    {
        libxml_use_internal_errors(true);

        // Cargar el XML con DOM
        $this->xmlDOM = new \DOMDocument();
        $this->xmlDOM->formatOutput = false;
        $this->xmlDOM->preserveWhiteSpace = false;

        if ($this->xmlDOM->loadXML($this->fileImport->readFileToString()) === false) {
            foreach (libxml_get_errors() as $error) {
                debugLog(__METHOD__ . ' - ' . $error->message);
            }

            throw new ImportException(
                __u('Error interno'),
                ImportException::ERROR,
                __u('No es posible procesar el archivo XML')
            );
        }
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @return string
     * @throws ImportException
     */
    public function detectXMLFormat()
    {
        $nodes = $this->xmlDOM->getElementsByTagName('Generator');

        if ($nodes->length > 0) {
            $value = strtolower($nodes->item(0)->nodeValue);

            if ($value === 'keepass' || $value === 'syspass') {
                return $value;
            }
        }

        throw new ImportException(
            __u('Archivo XML no soportado'),
            ImportException::ERROR,
            __u('No es posible detectar la aplicación que exportó los datos')
        );
    }

    /**
     * @return \DOMDocument
     */
    public function getXmlDOM()
    {
        return $this->xmlDOM;
    }

    /**
     * Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas.
     */
    protected function parseFileHeader()
    {
        if (($handle = @fopen($this->fileImport->getFilePath(), 'r')) !== false) {
            // No. de líneas a leer como máximo
            $maxLines = 5;
            $count = 0;

            while (($buffer = fgets($handle, 4096)) !== false && $count <= $maxLines) {
                if (preg_match('/(?P<header>KEEPASSX_DATABASE|revelationdata)/i', $buffer, $matches)) {
                    fclose($handle);
                    return strtolower($matches['header']);
                }
                $count++;
            }

            fclose($handle);
        }

        return null;
    }
}