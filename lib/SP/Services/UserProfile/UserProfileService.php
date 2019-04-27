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

namespace SP\Services\UserProfile;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\UserProfile\UserProfileRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;
use SP\Util\Util;

/**
 * Class UserProfileService
 *
 * @package SP\Services\UserProfile
 */
final class UserProfileService extends Service
{
    use ServiceItemTrait;

    /**
     * @var UserProfileRepository
     */
    protected $userProfileRepository;

    /**
     * @param $id
     *
     * @return UserProfileData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
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
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->userProfileRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        if ($this->userProfileRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Profile not found'), NoSuchItemException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->userProfileRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while removing the profiles'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param $itemData
     *
     * @return int
     * @throws SPException
     */
    public function create($itemData)
    {
        return $this->userProfileRepository->create($itemData);
    }

    /**
     * @param $itemData
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData)
    {
        if ($this->userProfileRepository->update($itemData) === 0) {
            throw new ServiceException(__u('Error while updating the profile'));
        }
    }

    /**
     * @param $id
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsersForProfile($id)
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
    public function getAllBasic()
    {
        return $this->userProfileRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userProfileRepository = $this->dic->get(UserProfileRepository::class);
    }
}