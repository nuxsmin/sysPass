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

namespace SP\Import;

use SP\Core\Exceptions\SPException;

defined('APP_ROOT') || die();

/**
 * Class XmlImportBase abstracta para manejar archivos de importación en formato XML
 *
 * @package SP
 */
abstract class XmlImportBase extends ImportBase
{
    /**
     * @var \SimpleXMLElement
     */
    protected $xml;
    /**
     * @var \DOMDocument
     */
    protected $xmlDOM;

    /**
     * ImportBase constructor.
     *
     * @param FileImport   $File
     * @param ImportParams $ImportParams
     * @throws SPException
     */
    public function __construct(FileImport $File, ImportParams $ImportParams)
    {
        parent::__construct($File, $ImportParams);

        try {
            $this->readXMLFile();
        } catch (SPException $e) {
            throw $e;
        }
    }


    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws SPException
     */
    protected function readXMLFile()
    {
        $this->xml = simplexml_load_file($this->file->getTmpFile());

        // Cargar el XML con DOM
        $this->xmlDOM = new \DOMDocument();
        $this->xmlDOM->load($this->file->getTmpFile());

        if ($this->xml === false) {
            throw new SPException(
                SPException::SP_CRITICAL,
                __('Error interno', false),
                __('No es posible procesar el archivo XML', false)
            );
        }
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws SPException
     */
    public function detectXMLFormat()
    {
        if ((string)$this->xml->Meta->Generator === 'KeePass') {
            return 'keepass';
        } else if ((string)$this->xml->Meta->Generator === 'sysPass') {
            return 'syspass';
        } else if ($xmlApp = $this->parseFileHeader()) {
            switch ($xmlApp) {
                case 'keepassx_database':
                    return 'keepassx';
                case 'revelationdata':
                    return 'revelation';
                default:
                    break;
            }
        } else {
            throw new SPException(
                SPException::SP_CRITICAL,
                __('Archivo XML no soportado', false),
                __('No es posible detectar la aplicación que exportó los datos', false)
            );
        }

        return '';
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param string $nodeName      Nombre del nodo principal
     * @param string $childNodeName Nombre de los nodos hijos
     * @throws SPException
     */
    protected function getNodesData($nodeName, $childNodeName, $callback)
    {
        $ParentNode = $this->xmlDOM->getElementsByTagName($nodeName);

        if ($ParentNode->length === 0) {
            throw new SPException(
                SPException::SP_WARNING,
                __('Formato de XML inválido', false),
                sprintf(__('El nodo "%s" no existe'), $nodeName));
        } elseif (!is_callable([$this, $callback])) {
            throw new SPException(SPException::SP_WARNING, __('Método inválido', false));
        }

        /** @var \DOMElement $nodes */
        foreach ($ParentNode as $nodes) {
            /** @var \DOMElement $Account */
            foreach ($nodes->getElementsByTagName($childNodeName) as $Node) {
                $this->$callback($Node);
            }
        }
    }
}