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

namespace SP\Domain\Category\Adapters;

use League\Fractal\Resource\Collection;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Category\Ports\CategoryAdapter;
use SP\Domain\Common\Adapters\Adapter;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Providers\Link;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\ActionNotFoundException;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Adapters\CustomField;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class Category
 */
final class Category extends Adapter implements CategoryAdapter
{
    use ItemTrait;

    protected array $availableIncludes = ['customFields'];

    public function __construct(
        ConfigDataInterface                     $configData,
        string                                  $baseUrl,
        private readonly CustomFieldDataService $customFieldDataService,
        private readonly ActionsInterface       $actions
    ) {
        parent::__construct($configData, $baseUrl);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function includeCustomFields(CategoryModel $data): Collection
    {
        return $this->collection(
            $this->getCustomFieldsForItem(AclActionsInterface::CATEGORY, $data->getId(), $this->customFieldDataService),
            new CustomField($this->configData, $this->baseUrl)
        );
    }

    /**
     * @throws ActionNotFoundException
     */
    public function transform(Model|CategoryModel $data): array
    {
        $actionRoute = $this->actions->getActionById(AclActionsInterface::CATEGORY_VIEW)->getRoute();

        return [
            'id' => $data->getId(),
            'name' => $data->getName(),
            'description' => $data->getDescription(),
            'customFields' => null,
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink(
                        $data->getId(),
                        $actionRoute,
                        $this->configData,
                        $this->baseUrl
                    ),
                ],
            ],
        ];
    }
}
