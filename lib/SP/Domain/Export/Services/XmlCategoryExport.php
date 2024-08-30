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
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Ports\XmlCategoryExportService;

use function SP\__u;

/**
 * Class XmlCategoryExport
 */
final class XmlCategoryExport extends XmlExportEntityBase implements XmlCategoryExportService
{
    public function __construct(
        Application                      $application,
        private readonly CategoryService $categoryService
    ) {
        parent::__construct($application);
    }

    /**
     * Crear el nodo con los datos
     *
     * @throws ServiceException
     */
    public function export(): DOMElement
    {
        try {
            $this->eventDispatcher->notify(
                'run.export.process.category',
                new Event($this, EventMessage::build()->addDescription(__u('Exporting categories')))
            );

            $categories = $this->categoryService->getAll();

            $nodeCategories = $this->document->createElement('Categories');

            if ($nodeCategories === false) {
                throw ServiceException::error(__u('Unable to create node'));
            }

            if (count($categories) === 0) {
                return $nodeCategories;
            }

            foreach ($categories as $category) {
                $nodeCategory = $this->document->createElement('Category');
                $nodeCategories->appendChild($nodeCategory);

                $nodeCategory->setAttribute('id', (string)$category->getId());
                $nodeCategory->appendChild(
                    $this->document->createElement(
                        'name',
                        $this->document->createTextNode($category->getName())->nodeValue
                    )
                );
                $nodeCategory->appendChild(
                    $this->document->createElement(
                        'description',
                        $this->document->createTextNode($category->getDescription())->nodeValue
                    )
                );
            }

            return $nodeCategories;
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }
}
