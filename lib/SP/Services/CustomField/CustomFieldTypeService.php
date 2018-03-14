<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\CustomField;

use SP\DataModel\CustomFieldTypeData;
use SP\Repositories\CustomField\CustomFieldTypeRepository;
use SP\Services\Service;
use SP\Services\ServiceItemTrait;

/**
 * Class CustomFieldTypeService
 *
 * @package SP\Services\CustomField
 */
class CustomFieldTypeService extends Service
{
    use ServiceItemTrait;

    /**
     * @var CustomFieldTypeRepository
     */
    protected $customFieldTypeRepository;

    /**
     * Returns all the items
     *
     * @return CustomFieldTypeData[]
     */
    public function getAll()
    {
        return $this->customFieldTypeRepository->getAll();
    }

    protected function initialize()
    {
        $this->customFieldTypeRepository = $this->dic->get(CustomFieldTypeRepository::class);
    }

    /**
     * Get all items from the service's repository
     *
     * @return CustomFieldTypeData[]
     */
    public function getAllBasic()
    {
        return $this->getAll();
    }
}