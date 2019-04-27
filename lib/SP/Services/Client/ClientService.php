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

namespace SP\Services\Client;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Client\ClientRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountFilterUser;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;

/**
 * Class ClientService
 *
 * @package SP\Services\Client
 */
final class ClientService extends Service
{
    use ServiceItemTrait;

    /**
     * @var ClientRepository
     */
    protected $clientRepository;

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->clientRepository->search($itemSearchData);
    }

    /**
     * @param int $id
     *
     * @return ClientData
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        if (($result = $this->clientRepository->getById($id))->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Client not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return ClientData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getByName($name)
    {
        if (($result = $this->clientRepository->getByName($name))->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Client not found'), NoSuchItemException::INFO);
        }

        return $result->getData();
    }

    /**
     * @param $id
     *
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->clientRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Client not found'), NoSuchItemException::INFO);
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
        if (($count = $this->clientRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while deleting the clients'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param $itemData
     *
     * @return int
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create($itemData)
    {
        return $this->clientRepository->create($itemData);
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
        return $this->clientRepository->update($itemData);
    }

    /**
     * Get all items from the service's repository
     *
     * @return ClientData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic()
    {
        return $this->clientRepository->getAll()->getDataAsArray();
    }

    /**
     * Returns all clients visible for a given user
     *
     * @return ItemData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllForUser()
    {
        return $this->clientRepository->getAllForFilter($this->dic->get(AccountFilterUser::class)->getFilter())->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->clientRepository = $this->dic->get(ClientRepository::class);
    }
}