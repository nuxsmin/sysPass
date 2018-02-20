<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
     */
    public function __construct(FileImport $fileImport)
    {
        $this->fileImport = $fileImport;
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws ImportException
     */
    public function detectXMLFormat()
    {
        $this->readXMLFile();

        $tags = $this->xmlDOM->getElementsByTagName('Generator');

        /** @var \DOMElement[] $tags */
        foreach ($tags as $tag) {
            if ($tag->nodeValue === 'KeePass' || $tag->nodeValue === 'sysPass') {
                return strtolower($tag->nodeValue);
            }
        }

        if ($xmlApp = $this->parseFileHeader()) {
            switch ($xmlApp) {
                case 'keepassx_database':
                    return 'keepassx';
                case 'revelationdata':
                    return 'revelation';
                default:
                    break;
            }
        } else {
            throw new ImportException(
                __u('Archivo XML no soportado'),
                ImportException::ERROR,
                __u('No es posible detectar la aplicación que exportó los datos')
            );
        }

        return '';
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws ImportException
     */
    protected function readXMLFile()
    {
        // Cargar el XML con DOM
        $this->xmlDOM = new \DOMDocument();

        if ($this->xmlDOM->load($this->fileImport->getTmpFile()) === false) {
            throw new ImportException(
                __u('Error interno'),
                ImportException::ERROR,
                __u('No es posible procesar el archivo XML')
            );
        }
    }

    /**
     * Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas.
     *
     * @return bool
     */
    protected function parseFileHeader()
    {
        $handle = @fopen($this->fileImport->getTmpFile(), 'r');
        $headersRegex = '/(KEEPASSX_DATABASE|revelationdata)/i';

        if ($handle) {
            // No. de líneas a leer como máximo
            $maxLines = 5;
            $count = 0;

            while (($buffer = fgets($handle, 4096)) !== false && $count <= $maxLines) {
                if (preg_match($headersRegex, $buffer, $app)) {
                    fclose($handle);
                    return strtolower($app[0]);
                }
                $count++;
            }

            fclose($handle);
        }

        return false;
    }

    /**
     * @return \DOMDocument
     */
    public function getXmlDOM()
    {
        return $this->xmlDOM;
    }
}