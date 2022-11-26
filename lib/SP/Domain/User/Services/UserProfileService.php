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

namespace SP\Domain\User\Services;

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\User\Ports\UserProfileRepositoryInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\User\Repositories\UserProfileRepository;
use SP\Util\Util;

/**
 * Class UserProfileService
 *
 * @package SP\Domain\Common\Services\UserProfile
 */
final class UserProfileService extends Service implements UserProfileServiceInterface
{
    use ServiceItemTrait;

    protected UserProfileRepository $userProfileRepository;

    public function __construct(Application $application, UserProfileRepositoryInterface $userProfileRepository)
    {
        parent::__construct($application);

        $this->userProfileRepository = $userProfileRepository;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function getById(int $id): UserProfileData
    {
        $result = $this->userProfileRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Profile not found'));
        }

        $userProfileData = $result->getData();
        $userProfileData->setProfile(Util::unserialize(ProfileData::class, $userProfileData->getProfile()));

        return $userProfileData;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->userProfileRepository->search($itemSearchData);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function delete(int $id): UserProfileServiceInterface
    {
        if ($this->userProfileRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Profile not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param  int[]  $ids
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userProfileRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while removing the profiles'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\DuplicatedItemException
     */
    public function create(UserProfileData $itemData): int
    {
        return $this->userProfileRepository->create($itemData);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\DuplicatedItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function update(UserProfileData $itemData): void
    {
        $update = $this->userProfileRepository->update($itemData);

        if ($update === 0) {
            throw new ServiceException(__u('Error while updating the profile'));
        }
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUsersForProfile(int $id): array
    {
        return $this->userProfileRepository->getUsersForProfile($id)->getDataAsArray();
    }

    /**
     * Get all items from the service's repository
     *
     * @return UserProfileData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->userProfileRepository->getAll()->getDataAsArray();
    }
}
