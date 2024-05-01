<?php
declare(strict_types=1);
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
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldType as CustomFieldTypeModel;
use SP\Domain\CustomField\Ports\CustomFieldTypeRepository;
use SP\Domain\CustomField\Ports\CustomFieldTypeService;

/**
 * Class CustomFieldType
 */
final class CustomFieldType extends Service implements CustomFieldTypeService
{

    public function __construct(
        Application                                $application,
        private readonly CustomFieldTypeRepository $customFieldTypeRepository
    ) {
        parent::__construct($application);
    }

    /**
     * Returns all the items
     *
     * @template T of CustomFieldTypeModel
     *
     * @return T[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAll(): array
    {
        return $this->customFieldTypeRepository->getAll()->getDataAsArray(CustomFieldTypeModel::class);
    }
}
