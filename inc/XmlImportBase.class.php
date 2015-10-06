<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rub�n Dom�nguez nuxsmin@syspass.org
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
 *
 */

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
    protected $_xml;
    /**
     * @var \DOMDocument
     */
    protected $_xmlDOM;

    /**
     * Constructor
     *
     * @param $file FileImport Instancia de la clase FileImport
     * @throws SPException
     */
    public function __construct($file)
    {
        try {
            $this->_file = $file;
            $this->readXMLFile();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws SPException
     * @return \SimpleXMLElement Con los datos del archivo XML
     */
    protected function readXMLFile()
    {
        $this->_xml = simplexml_load_file($this->_file->getTmpFile());

        // Cargar el XML con DOM
        $this->_xmlDOM = new \DOMDocument();
        $this->_xmlDOM->load($this->_file->getTmpFile());

        if ($this->_xml === false) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('No es posible procesar el archivo XML')
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
        if ($this->_xml->Meta->Generator == 'KeePass') {
            return 'keepass';
        } else if ($this->_xml->Meta->Generator == 'sysPass') {
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
                _('Archivo XML no soportado'),
                _('No es posible detectar la aplicación que exportó los datos')
            );
        }

        return '';
    }
}