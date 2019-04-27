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

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->tagRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return TagData
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        $result = $this->tagRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Tag not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * @param string $name
     *
     * @return TagData
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName($name)
    {
        $result = $this->tagRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Tag not found'), NoSuchItemException::INFO);
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
        if ($this->tagRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Tag not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     *
     * @return $this
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        if ($this->tagRepository->deleteByIdBatch($ids) !== count($ids)) {
            throw new ServiceException(__u('Error while removing the tags'), ServiceException::WARNING);
        }

        return $this;
    }

    /**
     * @param $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create($itemData)
    {
        return $this->tagRepository->create($itemData);
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
        return $this->tagRepository->update($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return TagData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic()
    {
        return $this->tagRepository->getAll();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->tagRepository = $this->dic->get(TagRepository::class);
    }
}