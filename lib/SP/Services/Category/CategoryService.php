<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Category;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Category\CategoryRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class CategoryService
 *
 * @package SP\Services\Category
 */
final class CategoryService extends Service
{
    use ServiceItemTrait;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->categoryRepository->search($itemSearchData);
    }

    /**
     * @param int $id
     *
     * @return CategoryData
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $result = $this->categoryRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the item for given id
     *
     * @param string $name
     *
     * @return CategoryData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByName($name)
    {
        $result = $this->categoryRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete($id)
    {
        if ($this->categoryRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Category not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->categoryRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting categories'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param $itemData
     *
     * @return int
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create($itemData)
    {
        return $this->categoryRepository->create($itemData);
    }

    /**
     * @param $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData)
    {
        return $this->categoryRepository->update($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return CategoryData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic()
    {
        return $this->categoryRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->categoryRepository = $this->dic->get(CategoryRepository::class);
    }
}