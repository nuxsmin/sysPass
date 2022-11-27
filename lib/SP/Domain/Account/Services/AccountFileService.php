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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFileRepositoryInterface;
use SP\Domain\Account\Ports\AccountFileServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Util\FileUtil;
use SP\Util\ImageUtilInterface;
use function SP\__u;

/**
 * Class AccountFileService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountFileService extends Service implements AccountFileServiceInterface
{
    private AccountFileRepositoryInterface $accountFileRepository;
    private ImageUtilInterface             $imageUtil;

    public function __construct(
        Application $application,
        AccountFileRepositoryInterface $accountFileRepository,
        ImageUtilInterface $imageUtil
    ) {
        $this->accountFileRepository = $accountFileRepository;
        $this->imageUtil = $imageUtil;

        parent::__construct($application);
    }

    /**
     * Creates an item
     *
     * @param  \SP\DataModel\FileData  $itemData
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidImageException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(FileData $itemData): int
    {
        if (FileUtil::isImage($itemData)) {
            $itemData->setThumb($this->imageUtil->createThumbnail($itemData->getContent()));
        } else {
            $itemData->setThumb('no_thumb');
        }

        return $this->accountFileRepository->create($itemData);
    }

    /**
     * Returns the file with its content
     *
     * @param  int  $id
     *
     * @return \SP\DataModel\FileExtData|null
     */
    public function getById(int $id): ?FileExtData
    {
        return $this->accountFileRepository->getById($id)->getData();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  int[]  $ids
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
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
     * @param  int  $id
     *
     * @return \SP\Domain\Account\Ports\AccountFileServiceInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): AccountFileServiceInterface
    {
        if (!$this->accountFileRepository->delete($id)) {
            throw new NoSuchItemException(__u('File not found'));
        }

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @param  \SP\DataModel\ItemSearchData  $searchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
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
}
