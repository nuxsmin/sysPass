<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Adapters;

use League\Fractal\Resource\Collection;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\Mvc\Controller\ItemTrait;
use SP\Util\Link;

/**
 * Class CategoryAdapter
 *
 * @package SP\Adapters
 */
final class CategoryAdapter extends AdapterBase
{
    use ItemTrait;

    protected $availableIncludes = [
        'customFields'
    ];

    /**
     * @param CategoryData $data
     *
     * @return Collection
     * @throws SPException
     */
    public function includeCustomFields(CategoryData $data)
    {
        return $this->collection(
            $this->getCustomFieldsForItem(ActionsInterface::CATEGORY, $data->id),
            new CustomFieldAdapter($this->configData)
        );
    }

    /**
     * @param CategoryData $data
     *
     * @return array
     */
    public function transform(CategoryData $data): array
    {
        return [
            'id' => (int)$data->getId(),
            'name' => $data->getName(),
            'description' => $data->getDescription(),
            'customFields' => null,
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink($data->getId(),
                        ActionsInterface::CATEGORY_VIEW,
                        $this->configData,
                        true)
                ]
            ],
        ];
    }
}