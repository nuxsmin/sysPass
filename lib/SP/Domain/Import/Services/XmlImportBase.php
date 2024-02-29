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
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Import\Ports\XmlFileService;

use function SP\__;
use function SP\__u;

/**
 * Class XmlImportBase
 *
 * @package SP\Domain\Import\Services
 */
abstract class XmlImportBase extends ImportBase
{
    protected readonly DOMDocument         $document;
    protected readonly ConfigDataInterface $configData;

    /**
     * ImportBase constructor.
     */
    public function __construct(
        Application                       $application,
        ImportHelper                      $importHelper,
        CryptInterface                    $crypt,
        protected readonly XmlFileService $xmlFileImport
    ) {
        parent::__construct($application, $importHelper, $crypt);
        $this->configData = $application->getConfig()->getConfigData();
        $this->document = $xmlFileImport->getDocument();
    }

    /**
     * Obtener los datos de los nodos
     *
     * @param string $nodeName Nombre del nodo principal
     * @param string $childNodeName Nombre de los nodos hijos
     * @param callable $callback Método a ejecutar
     * @param bool $required Indica si el nodo es requerido
     *
     * @throws ImportException
     */
    protected function getNodesData(
        string $nodeName,
        string $childNodeName,
        callable $callback,
        bool   $required = true
    ): void {
        $nodeList = $this->document->getElementsByTagName($nodeName);

        if ($nodeList->length > 0) {
            if (!is_callable($callback)) {
                throw ImportException::warning(__u('Invalid Method'), $callback);
            }

            /** @var DOMElement $nodes */
            foreach ($nodeList as $nodes) {
                /** @var DOMElement $Account */
                foreach ($nodes->getElementsByTagName($childNodeName) as $node) {
                    $callback($node);
                }
            }
        } elseif ($required === true) {
            throw ImportException::warning(
                __u('Invalid XML format'),
                sprintf(__('"%s" node doesn\'t exist'), $nodeName)
            );
        }
    }
}
