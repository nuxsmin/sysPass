<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Services\ServiceItemTrait;

/**
 * Class CustomFieldDefService
 *
 * @package SP\Services\CustomField
 */
class CustomFieldDefService
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var CustomFieldDefRepository
     */
    protected $customFieldDefRepository;

    /**
     * ClientService constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param CustomFieldDefRepository $customFieldDefRepository
     */
    public function inject(CustomFieldDefRepository $customFieldDefRepository)
    {
        $this->customFieldDefRepository = $customFieldDefRepository;
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return \SP\DataModel\CustomFieldDefinitionData[]
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->customFieldDefRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return \SP\DataModel\CustomFieldDefinitionData
     */
    public function getById($id)
    {
        return $this->customFieldDefRepository->getById($id);
    }

    /**
     * @param $id
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        return $this->customFieldDefRepository->delete($id);
    }

    /**
     * @param $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        return $this->customFieldDefRepository->create($itemData);
    }

    /**
     * @param $itemData
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        return $this->customFieldDefRepository->update($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     */
    public function getAllBasic()
    {
        return $this->customFieldDefRepository->getAll();
    }
}