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
 * Trait XmlImportTrait para manejar archivos de importación en formato XML
 *
 * @package SP
 */
trait XmlImportTrait
{
    /**
     * @var \DOMDocument
     */
    protected $xmlDOM;

    /**
     * @param \DOMDocument $xmlDOM
     */
    public function setXmlDOM($xmlDOM)
    {
        $this->xmlDOM =& $xmlDOM;
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param string $nodeName      Nombre del nodo principal
     * @param string $childNodeName Nombre de los nodos hijos
     * @param string $callback      Método a ejecutar
     * @param bool   $required      Indica si el nodo es requerido
     * @throws SPException
     */
    protected function getNodesData($nodeName, $childNodeName, $callback, $required = true)
    {
        $ParentNode = $this->xmlDOM->getElementsByTagName($nodeName);

        if ($ParentNode->length === 0) {
            if ($required === true) {
                throw new SPException(
                    SPException::SP_WARNING,
                    __('Formato de XML inválido', false),
                    sprintf(__('El nodo "%s" no existe'), $nodeName));
            }

            return;
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