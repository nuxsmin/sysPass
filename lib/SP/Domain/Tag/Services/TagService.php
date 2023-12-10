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

namespace SP\Domain\Tag\Services;

use SP\Core\Application;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Tag\Ports\TagRepositoryInterface;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Tag\Repositories\TagRepository;

/**
 * Class TagService
 *
 * @package SP\Domain\Tag\Services
 */
final class TagService extends Service implements TagServiceInterface
{
    use ServiceItemTrait;

    private TagRepository $tagRepository;

    public function __construct(Application $application, TagRepositoryInterface $tagRepository)
    {
        parent::__construct($application);

        $this->tagRepository = $tagRepository;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->tagRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): TagData
    {
        $result = $this->tagRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Tag not found'), SPException::INFO);
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
            throw new NoSuchItemException(__u('Tag not found'), SPException::INFO);
        }

        return $result->getData();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): TagServiceInterface
    {
        if ($this->tagRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Tag not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): TagServiceInterface
    {
        if ($this->tagRepository->deleteByIdBatch($ids) !== count($ids)) {
            throw new ServiceException(__u('Error while removing the tags'), SPException::WARNING);
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
    public function getAll(): array
    {
        return $this->tagRepository->getAll();
    }
}
