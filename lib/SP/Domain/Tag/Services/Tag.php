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

namespace SP\Domain\Tag\Services;

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Tag\Models\Tag as TagModel;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\Ports\TagService;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class Tag
 */
final class Tag extends Service implements TagService
{

    public function __construct(Application $application, private readonly TagRepository $tagRepository)
    {
        parent::__construct($application);
    }

    /**
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<TagModel>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->tagRepository->search($itemSearchData);
    }

    /**
     * @param int $id
     * @return TagModel
     * @throws NoSuchItemException
     */
    public function getById(int $id): TagModel
    {
        $result = $this->tagRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Tag not found'));
        }

        return $result->getData(TagModel::class);
    }

    /**
     * @param string $name
     * @return TagModel
     * @throws NoSuchItemException
     */
    public function getByName(string $name): TagModel
    {
        $result = $this->tagRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Tag not found'));
        }

        return $result->getData(TagModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): TagService
    {
        if ($this->tagRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Tag not found'));
        }

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->tagRepository->deleteByIdBatch($ids)->getAffectedNumRows() !== count($ids)) {
            throw ServiceException::warning(__u('Error while removing the tags'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(TagModel $tag): int
    {
        return $this->tagRepository->create($tag)->getLastId();
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(TagModel $tag): int
    {
        return $this->tagRepository->update($tag);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<TagModel>
     */
    public function getAll(): array
    {
        return $this->tagRepository->getAll()->getDataAsArray(TagModel::class);
    }
}
