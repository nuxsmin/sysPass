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

namespace SP\Services\Account;

use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\InvalidImageException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountFileRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;
use SP\Util\FileUtil;
use SP\Util\ImageUtil;

/**
 * Class AccountFileService
 *
 * @package SP\Services\Account
 */
final class AccountFileService extends Service
{
    /**
     * @var AccountFileRepository
     */
    protected $accountFileRepository;

    /**
     * Creates an item
     *
     * @param FileData $itemData
     *
     * @return int
     * @throws InvalidImageException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if (FileUtil::isImage($itemData)) {
            try {
                $imageUtil = $this->dic->get(ImageUtil::class);
                $itemData->setThumb($imageUtil->createThumbnail($itemData->getContent()));
            } catch (CheckException $e) {
                processException($e);

                $itemData->setThumb('no_thumb');
            }
        } else {
            $itemData->setThumb('no_thumb');
        }

        return $this->accountFileRepository->create($itemData);
    }

    /**
     * @param $id
     *
     * @return FileExtData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getInfoById($id)
    {
        return $this->accountFileRepository->getInfoById($id)->getData();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return FileExtData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
    {
        return $this->accountFileRepository->getById($id)->getData();
    }

    /**
     * Returns all the items
     *
     * @return FileExtData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
    {
        return $this->accountFileRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return FileExtData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByIdBatch(array $ids)
    {
        return $this->accountFileRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->accountFileRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error al eliminar archivos'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return AccountFileService
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        if ($this->accountFileRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Archivo no encontrado'));
        }

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $searchData
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $searchData)
    {
        return $this->accountFileRepository->search($searchData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return FileData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByAccountId($id)
    {
        return $this->accountFileRepository->getByAccountId($id)->getDataAsArray();
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountFileRepository = $this->dic->get(AccountFileRepository::class);
    }
}