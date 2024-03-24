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

namespace SP\Domain\Category\Services;

use Exception;
use SP\Core\Application;
use SP\DataModel\ItemSearchData;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Category\Ports\CategoryRepository;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Category
 *
 * @template T of CategoryModel
 */
final class Category extends Service implements CategoryService
{
    public function __construct(
        Application                                  $application,
        private readonly CategoryRepository $categoryRepository
    ) {
        parent::__construct($application);
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return QueryResult<T>
     * @throws Exception
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->categoryRepository->search($itemSearchData);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getById(int $id): CategoryModel
    {
        $result = $this->categoryRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $result->getData(CategoryModel::class);
    }

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getByName(string $name): ?CategoryModel
    {
        $result = $this->categoryRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $result->getData(CategoryModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): CategoryService
    {
        if ($this->categoryRepository->delete($id)->getAffectedNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->categoryRepository->deleteByIdBatch($ids)->getAffectedNumRows() === 0) {
            throw new ServiceException(__u('Error while deleting categories'), SPException::WARNING);
        }
    }

    /**
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create(CategoryModel $category): int
    {
        return $this->categoryRepository->create($category)->getLastId();
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CategoryModel $category): void
    {
        $this->categoryRepository->update($category);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getAll(): array
    {
        return $this->categoryRepository->getAll()->getDataAsArray(CategoryModel::class);
    }
}
