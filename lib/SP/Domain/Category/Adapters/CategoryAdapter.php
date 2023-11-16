<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Acl\AclActionsInterface;
use SP\DataModel\CategoryData;
use SP\Domain\Category\Ports\CategoryAdapterInterface;
use SP\Domain\Common\Adapters\Adapter;
use SP\Domain\CustomField\Adapters\CustomFieldAdapter;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Mvc\Controller\ItemTrait;
use SP\Util\Link;

/**
 * Class CategoryAdapter
 *
 * @package SP\Adapters
 */
final class CategoryAdapter extends Adapter implements CategoryAdapterInterface
{
    use ItemTrait;

    protected array $availableIncludes = ['customFields'];

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function includeCustomFields(CategoryData $data, CustomFieldServiceInterface $customFieldService): Collection
    {
        return $this->collection(
            $this->getCustomFieldsForItem(AclActionsInterface::CATEGORY, $data->id, $customFieldService),
            new CustomFieldAdapter($this->configData)
        );
    }

    public function transform(CategoryData $data): array
    {
        return [
            'id'           => $data->getId(),
            'name'         => $data->getName(),
            'description'  => $data->getDescription(),
            'customFields' => null,
            'links'        => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink(
                        $data->getId(),
                        AclActionsInterface::CATEGORY_VIEW,
                        $this->configData,
                        true
                    ),
                ],
            ],
        ];
    }
}
