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

namespace SP\Domain\User\Services;

use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Domain\User\Ports\UserProfileRepository;
use SP\Domain\User\Ports\UserProfileService;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class UserProfile
 */
final class UserProfile extends Service implements UserProfileService
{

    public function __construct(Application $application, private readonly UserProfileRepository $userProfileRepository)
    {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): UserProfileModel
    {
        $result = $this->userProfileRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw NoSuchItemException::info(__u('Profile not found'));
        }

        return $result->getData(UserProfileModel::class);
    }

    /**
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<UserProfileModel>
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->userProfileRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void
    {
        if ($this->userProfileRepository->delete($id)->getAffectedNumRows() === 0) {
            throw NoSuchItemException::info(__u('Profile not found'));
        }
    }

    /**
     * @param int[] $ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->userProfileRepository->deleteByIdBatch($ids)->getAffectedNumRows();

        if ($count !== count($ids)) {
            throw ServiceException::warning(__u('Error while removing the profiles'));
        }

        return $count;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(UserProfileModel $userProfile): int
    {
        return $this->userProfileRepository->create($userProfile)->getLastId();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws ServiceException
     */
    public function update(UserProfileModel $userProfile): void
    {
        if ($this->userProfileRepository->update($userProfile) === 0) {
            throw ServiceException::error(__u('Error while updating the profile'));
        }
    }

    /**
     * @param int $id
     * @return array
     *
     * TODO: Move to UserService instead?
     */
    public function getUsersForProfile(int $id): array
    {
        return $this->userProfileRepository
            ->getAny(
                ['id', 'login'],
                UserModel::TABLE,
                'userProfileId = :userProfileId',
                ['userProfileId' => $id]
            )
            ->getDataAsArray();
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<UserProfileModel>
     */
    public function getAll(): array
    {
        return $this->userProfileRepository->getAll()->getDataAsArray(UserProfileModel::class);
    }
}
