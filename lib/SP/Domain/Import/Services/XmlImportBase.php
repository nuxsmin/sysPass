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
use DOMElement;
use SP\Core\Application;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Class XmlImportBase
 *
 * @package SP\Domain\Import\Services
 */
abstract class XmlImportBase
{
    use ImportTrait;

    protected XmlFileImportInterface $xmlFileImport;
    protected DOMDocument            $xmlDOM;
    protected EventDispatcher     $eventDispatcher;
    protected ConfigService       $configService;
    protected ConfigDataInterface $configData;

    /**
     * ImportBase constructor.
     */
    public function __construct(
        Application   $application,
        ImportHelper  $importHelper,
        ConfigService $configService,
        XmlFileImportInterface $xmlFileImport,
        ImportParams  $importParams
    ) {
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->xmlFileImport = $xmlFileImport;
        $this->importParams = $importParams;
        $this->accountService = $importHelper->getAccountService();
        $this->categoryService = $importHelper->getCategoryService();
        $this->clientService = $importHelper->getClientService();
        $this->tagService = $importHelper->getTagService();
        $this->configService = $configService;

        $this->configData = $application->getConfig()->getConfigData();
        $this->xmlDOM = $xmlFileImport->getXmlDOM();
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param  string  $nodeName  Nombre del nodo principal
     * @param  string  $childNodeName  Nombre de los nodos hijos
     * @param  callable  $callback  Método a ejecutar
     * @param  bool  $required  Indica si el nodo es requerido
     *
     * @throws ImportException
     */
    protected function getNodesData(
        string $nodeName,
        string $childNodeName,
        callable $callback,
        bool $required = true
    ): void {
        $nodeList = $this->xmlDOM->getElementsByTagName($nodeName);

        if ($nodeList->length > 0) {
            if (!is_callable($callback)) {
                throw new ImportException(__u('Invalid Method'), SPException::WARNING);
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
                SPException::WARNING,
                sprintf(__('"%s" node doesn\'t exist'), $nodeName)
            );
        }
    }
}
