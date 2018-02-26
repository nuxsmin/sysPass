<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

namespace SP\Services\UserProfile;

use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\UserProfile\UserProfileRepository;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Util\Util;

/**
 * Class UserProfileService
 *
 * @package SP\Services\UserProfile
 */
class UserProfileService extends Service
{
    use InjectableTrait;
    use ServiceItemTrait;

    /**
     * @var UserProfileRepository
     */
    protected $userProfileRepository;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->userProfileRepository = $this->dic->get(UserProfileRepository::class);
    }

    /**
     * @param $id
     * @return UserProfileData
     */
    public function getById($id)
    {
        $userProfileData = $this->userProfileRepository->getById($id);
        $userProfileData->setProfile(Util::unserialize(ProfileData::class, $userProfileData->getProfile()));

        return $userProfileData;
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return \SP\DataModel\ClientData[]
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->userProfileRepository->search($itemSearchData);
    }

    /**
     * @param $id
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->userProfileRepository->delete($id) === 0) {
            throw new ServiceException(__u('Perfil no encontrado'), ServiceException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return int
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->userProfileRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error al eliminar los perfiles'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param $itemData
     * @return int
     * @throws SPException
     */
    public function create($itemData)
    {
        return $this->userProfileRepository->create($itemData);
    }

    /**
     * @param $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        return $this->userProfileRepository->update($itemData);
    }

    /**
     * @param $id
     * @return array
     */
    public function getUsersForProfile($id)
    {
        return $this->userProfileRepository->getUsersForProfile($id);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array
     */
    public function getAllBasic()
    {
        return $this->userProfileRepository->getAll();
    }
}