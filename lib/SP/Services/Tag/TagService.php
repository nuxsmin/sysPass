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

namespace SP\Services\Tag;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Tag\TagRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class TagService
 *
 * @package SP\Services\Tag
 */
final class TagService extends Service
{
    use ServiceItemTrait;

    protected ?TagRepository $tagRepository = null;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->tagRepository->search($itemSearchData);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById(int $id): TagData
    {
        $result = $this->tagRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Tag not found'),
                SPException::INFO
            );
        }

        return $result->getData();
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): ?TagData
    {
        $result = $this->tagRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(
                __u('Tag not found'),
                SPException::INFO
            );
        }

        return $result->getData();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(int $id): TagService
    {
        if ($this->tagRepository->delete($id) === 0) {
            throw new NoSuchItemException(
                __u('Tag not found'),
                SPException::INFO
            );
        }

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): TagService
    {
        if ($this->tagRepository->deleteByIdBatch($ids) !== count($ids)) {
            throw new ServiceException(
                __u('Error while removing the tags'),
                SPException::WARNING
            );
        }

        return $this;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(TagData $itemData): int
    {
        return $this->tagRepository->create($itemData);
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(TagData $itemData): int
    {
        return $this->tagRepository->update($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return TagData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->tagRepository->getAll();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->tagRepository = $this->dic->get(TagRepository::class);
    }
}