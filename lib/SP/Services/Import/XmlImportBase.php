<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use SP\Bootstrap;
use SP\Core\Events\EventDispatcher;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;

/**
 * Class XmlImportBase
 *
 * @package SP\Services\Import
 */
abstract class XmlImportBase
{
    use ImportTrait;

    /**
     * @var XmlFileImport
     */
    protected $xmlFileImport;
    /**
     * @var \DOMDocument
     */
    protected $xmlDOM;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * ImportBase constructor.
     *
     * @param XmlFileImport $xmlFileImport
     * @param ImportParams  $importParams
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(XmlFileImport $xmlFileImport, ImportParams $importParams)
    {
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
        $this->xmlDOM = $xmlFileImport->getXmlDOM();

        $dic = Bootstrap::getContainer();
        $this->accountService = $dic->get(AccountService::class);
        $this->categoryService = $dic->get(CategoryService::class);
        $this->clientService = $dic->get(ClientService::class);
        $this->tagService = $dic->get(TagService::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param string   $nodeName      Nombre del nodo principal
     * @param string   $childNodeName Nombre de los nodos hijos
     * @param callable $callback      Método a ejecutar
     * @param bool     $required      Indica si el nodo es requerido
     * @throws ImportException
     */
    protected function getNodesData($nodeName, $childNodeName, $callback, $required = true)
    {
        $nodeList = $this->xmlDOM->getElementsByTagName($nodeName);

        if ($nodeList->length === 0) {
            if ($required === true) {
                throw new ImportException(
                    __u('Formato de XML inválido'),
                    ImportException::WARNING,
                    sprintf(__('El nodo "%s" no existe'), $nodeName)
                );
            }

            if (!is_callable($callback)) {
                throw new ImportException(__u('Método inválido'), ImportException::WARNING);
            }

            /** @var \DOMElement $nodes */
            foreach ($nodeList as $nodes) {
                /** @var \DOMElement $Account */
                foreach ($nodes->getElementsByTagName($childNodeName) as $node) {
                    $callback($node);
                }
            }
        }
    }
}