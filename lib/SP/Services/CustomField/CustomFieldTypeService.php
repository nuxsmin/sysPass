<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\CustomField;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\CustomFieldTypeData;
use SP\Repositories\CustomField\CustomFieldTypeRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceItemTrait;

/**
 * Class CustomFieldTypeService
 *
 * @package SP\Services\CustomField
 */
final class CustomFieldTypeService extends Service
{
    use ServiceItemTrait;

    protected ?CustomFieldTypeRepository $customFieldTypeRepository = null;

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldTypeData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->getAll();
    }

    /**
     * Returns all the items
     *
     * @return CustomFieldTypeData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        return $this->customFieldTypeRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById(int $id)
    {
        $result = $this->customFieldTypeRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Field type not found'));
        }

        return $result->getData();
    }

    protected function initialize(): void
    {
        $this->customFieldTypeRepository = $this->dic->get(CustomFieldTypeRepository::class);
    }
}