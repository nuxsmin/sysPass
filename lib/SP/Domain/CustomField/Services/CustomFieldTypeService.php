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

namespace SP\Domain\CustomField\Services;

use SP\Core\Application;
use SP\DataModel\CustomFieldTypeData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Ports\CustomFieldTypeRepository;
use SP\Domain\CustomField\Ports\CustomFieldTypeServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\CustomField\Repositories\CustomFieldTypeBaseRepository;

/**
 * Class CustomFieldTypeService
 *
 * @package SP\Domain\CustomField\Services
 */
final class CustomFieldTypeService extends Service implements CustomFieldTypeServiceInterface
{
    use ServiceItemTrait;

    private CustomFieldTypeBaseRepository $customFieldTypeRepository;

    public function __construct(Application $application, CustomFieldTypeRepository $customFieldTypeRepository)
    {
        parent::__construct($application);

        $this->customFieldTypeRepository = $customFieldTypeRepository;
    }

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldTypeData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id)
    {
        $result = $this->customFieldTypeRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Field type not found'));
        }

        return $result->getData();
    }
}
