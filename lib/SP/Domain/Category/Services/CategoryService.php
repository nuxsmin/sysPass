<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Category\CategoryServiceInterface;
use SP\Domain\Category\In\CategoryRepositoryInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CategoryService
 *
 * @package SP\Domain\Category\Services
 */
final class CategoryService extends Service implements CategoryServiceInterface
{
    use ServiceItemTrait;

    protected CategoryRepositoryInterface $categoryRepository;

    public function __construct(Application $application, CategoryRepositoryInterface $categoryRepository)
    {
        parent::__construct($application);

        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->categoryRepository->search($itemSearchData);
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): CategoryData
    {
        $result = $this->categoryRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByName(string $name): ?CategoryData
    {
        $result = $this->categoryRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): CategoryServiceInterface
    {
        if ($this->categoryRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Category not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->categoryRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(__u('Error while deleting categories'), SPException::WARNING);
        }

        return $count;
    }

    /**
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create(CategoryData $itemData): int
    {
        return $this->categoryRepository->create($itemData);
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(CategoryData $itemData): int
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
    public function getAllBasic(): array
    {
        return $this->categoryRepository->getAll()->getDataAsArray();
    }
}