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

namespace SP\Services\Account;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidImageException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
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
    protected ?AccountFileRepository $accountFileRepository = null;

    /**
     * Creates an item
     *
     * @throws InvalidImageException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(FileData $itemData): int
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getInfoById(int $id): ?FileExtData
    {
        return $this->accountFileRepository->getInfoById($id)->getData();
    }

    /**
     * Returns the item for given id
     *
     * @return mixed|null
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById(int $id)
    {
        return $this->accountFileRepository->getById($id)->getData();
    }

    /**
     * Returns all the items
     *
     * @return FileExtData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        return $this->accountFileRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param int[] $ids
     *
     * @return FileExtData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): array
    {
        return $this->accountFileRepository->getByIdBatch($ids)->getDataAsArray();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->accountFileRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while deleting the files'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * Deletes an item
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function delete(int $id): AccountFileService
    {
        if ($this->accountFileRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('File not found'));
        }

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $searchData): QueryResult
    {
        return $this->accountFileRepository->search($searchData);
    }

    /**
     * Returns the item for given id
     *
     * @return FileData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $id): array
    {
        return $this->accountFileRepository->getByAccountId($id)->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->accountFileRepository = $this->dic->get(AccountFileRepository::class);
    }
}