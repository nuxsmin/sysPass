<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\Container;
use DOMDocument;
use DOMElement;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\Events\EventDispatcher;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Config\ConfigService;
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
     * @var DOMDocument
     */
    protected $xmlDOM;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * ImportBase constructor.
     *
     * @param Container     $dic
     * @param XmlFileImport $xmlFileImport
     * @param ImportParams  $importParams
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Container $dic, XmlFileImport $xmlFileImport, ImportParams $importParams)
    {
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
        $this->xmlDOM = $xmlFileImport->getXmlDOM();

        $this->configData = $dic->get(ConfigData::class);
        $this->accountService = $dic->get(AccountService::class);
        $this->categoryService = $dic->get(CategoryService::class);
        $this->clientService = $dic->get(ClientService::class);
        $this->tagService = $dic->get(TagService::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);
        $this->configService = $dic->get(ConfigService::class);
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param string   $nodeName      Nombre del nodo principal
     * @param string   $childNodeName Nombre de los nodos hijos
     * @param callable $callback      Método a ejecutar
     * @param bool     $required      Indica si el nodo es requerido
     *
     * @throws ImportException
     */
    protected function getNodesData($nodeName, $childNodeName, $callback, $required = true)
    {
        $nodeList = $this->xmlDOM->getElementsByTagName($nodeName);

        if ($nodeList->length > 0) {
            if (!is_callable($callback)) {
                throw new ImportException(__u('Invalid Method'), ImportException::WARNING);
            }

            /** @var DOMElement $nodes */
            foreach ($nodeList as $nodes) {
                /** @var DOMElement $Account */
                foreach ($nodes->getElementsByTagName($childNodeName) as $node) {
                    $callback($node);
                }
            }
        } elseif ($required === true) {
            throw new ImportException(
                __u('Invalid XML format'),
                ImportException::WARNING,
                sprintf(__('"%s" node doesn\'t exist'), $nodeName)
            );
        }
    }
}