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

namespace SP\Domain\Export\Ports;

use DOMDocument;
use DOMElement;
use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Tag\Services\TagService;

use function SP\__u;

/**
 * Class XmlTagExport
 */
final class XmlTagExport extends Service implements XmlTagExportService
{
    public function __construct(
        Application $application,
        private readonly TagService $tagService,

    ) {
        parent::__construct($application);
    }

    /**
     * Crear el nodo con los datos
     *
     * @throws ServiceException
     */
    public function export(DOMDocument $document): DOMElement
    {
        try {
            $this->eventDispatcher->notify(
                'run.export.process.tag',
                new Event($this, EventMessage::factory()->addDescription(__u('Exporting tags')))
            );

            $tags = $this->tagService->getAll();

            $nodeTags = $document->createElement('Tags');

            if ($nodeTags === false) {
                throw ServiceException::error(__u('Unable to create node'));
            }

            if (count($tags) === 0) {
                return $nodeTags;
            }

            foreach ($tags as $tag) {
                $nodeTag = $document->createElement('Tag');
                $nodeTags->appendChild($nodeTag);

                $nodeTag->setAttribute('id', $tag->getId());
                $nodeTag->appendChild(
                    $document->createElement('name', $document->createTextNode($tag->getName())->nodeValue)
                );
            }

            return $nodeTags;
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }
}
