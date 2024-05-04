<?php
declare(strict_types=1);
/**
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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Domain\Account\Models\File;
use SP\Domain\Account\Models\FileExtData;
use SP\Domain\Account\Ports\AccountFileRepository;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Image\Ports\ImageService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileSystem;

use function SP\__u;

/**
 * Class AccountFile
 */
final class AccountFile extends Service implements AccountFileService
{

    public function __construct(
        Application                            $application,
        private readonly AccountFileRepository $accountFileRepository,
        private readonly ImageService $imageUtil
    ) {
        parent::__construct($application);
    }

    /**
     * Creates an item
     *
     * @param File $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws InvalidImageException
     * @throws QueryException
     */
    public function create(File $itemData): int
    {
        if (FileSystem::isImage($itemData)) {
            $itemData->setThumb($this->imageUtil->createThumbnail($itemData->getContent()));
        } else {
            $itemData->setThumb('no_thumb');
        }

        return $this->accountFileRepository->create($itemData);
    }

    /**
     * Returns the file with its content
     *
     * @param int $id
     *
     * @return FileExtData|null
     * @throws SPException
     */
    public function getById(int $id): ?FileExtData
    {
        return $this->accountFileRepository->getById($id)->getData();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param int[] $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
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
     * @param int $id
     *
     * @return AccountFileService
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): AccountFileService
    {
        if (!$this->accountFileRepository->delete($id)) {
            throw new NoSuchItemException(__u('File not found'));
        }

        return $this;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $searchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchDto $searchData): QueryResult
    {
        return $this->accountFileRepository->search($searchData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return File[]
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByAccountId(int $id): array
    {
        return $this->accountFileRepository->getByAccountId($id)->getDataAsArray();
    }
}
