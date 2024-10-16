<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Export\Services;

use DOMElement;
use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Ports\XmlClientExportService;

use function SP\__u;

/**
 * Class XmlClientExport
 */
final class XmlClientExport extends XmlExportEntityBase implements XmlClientExportService
{
    public function __construct(
        Application                    $application,
        private readonly ClientService $clientService
    ) {
        parent::__construct($application);
    }

    /**
     * Crear el nodo con los datos
     *
     * @throws ServiceException
     * @throws ServiceException
     */
    public function export(): DOMElement
    {
        try {
            $this->eventDispatcher->notify(
                'run.export.process.client',
                new Event($this, EventMessage::build()->addDescription(__u('Exporting clients')))
            );

            $clients = $this->clientService->getAll();

            $nodeClients = $this->document->createElement('Clients');

            if ($nodeClients === false) {
                throw ServiceException::error(__u('Unable to create node'));
            }

            if (count($clients) === 0) {
                return $nodeClients;
            }

            foreach ($clients as $client) {
                $nodeClient = $this->document->createElement('Client');
                $nodeClients->appendChild($nodeClient);

                $nodeClient->setAttribute('id', (string)$client->getId());
                $nodeClient->appendChild(
                    $this->document->createElement(
                        'name',
                        $this->document->createTextNode($client->getName())->nodeValue
                    )
                );
                $nodeClient->appendChild(
                    $this->document->createElement(
                        'description',
                        $this->document->createTextNode($client->getDescription())->nodeValue
                    )
                );
            }

            return $nodeClients;
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }
}
