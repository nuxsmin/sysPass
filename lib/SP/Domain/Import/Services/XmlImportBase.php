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
use Iterator;
use SP\Core\Application;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Import\Ports\ImportHelperInterface;

/**
 * Class XmlImportBase
 *
 * @package SP\Domain\Import\Services
 */
abstract class XmlImportBase extends ImportBase
{
    protected readonly ConfigDataInterface $configData;

    /**
     * ImportBase constructor.
     */
    public function __construct(
        Application                    $application,
        ImportHelperInterface $importHelper,
        CryptInterface                 $crypt,
        protected readonly DOMDocument $document
    ) {
        parent::__construct($application, $importHelper, $crypt);
        $this->configData = $application->getConfig()->getConfigData();
    }

    /**
     * Get data from child nodes
     *
     * @param string $nodeName Parent node name
     * @param string $childNodeName Child node name
     *
     * @return iterable<DOMElement>
     */
    protected function getNodesData(string $nodeName, string $childNodeName): iterable
    {
        /** @var Iterator<int, DOMElement> $outerNodeList */
        $outerNodeList = $this->document->getElementsByTagName($nodeName)->getIterator();

        foreach ($outerNodeList as $outerNode) {
            /** @var Iterator<int, DOMElement> $innerNodeList */
            $innerNodeList = $outerNode->getElementsByTagName($childNodeName)->getIterator();

            foreach ($innerNodeList as $innerNode) {
                yield $innerNode;
            }
        }
    }
}
