<?php
/*
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

namespace SP\Domain\Client\Services;

use SP\Core\Application;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFilterUserInterface;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Client\Ports\ClientRepositoryInterface;
use SP\Domain\Client\Ports\ClientServiceInterface;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class ClientService
 *
 * @template T of ClientModel
 */
final class Client extends Service implements ClientServiceInterface
{
    public function __construct(
        Application                                 $application,
        private readonly ClientRepositoryInterface  $clientRepository,
        private readonly AccountFilterUserInterface $accountFilterUser
    ) {
        parent::__construct($application);
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->clientRepository->search($itemSearchData);
    }

    /**
     * @param int $id
     * @return ClientModel
     * @throws NoSuchItemException
     * @throws SPException
     */
    public function getById(int $id): ClientModel
    {
        $result = $this->clientRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Client not found'), SPException::INFO);
        }

        return $result->getData(ClientModel::class);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     * @return ClientModel|null
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     */
    public function getByName(string $name): ?ClientModel
    {
        $result = $this->clientRepository->getByName($name);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Client not found'), SPException::INFO);
        }

        return $result->getData(ClientModel::class);
    }

    /**
     * @param int $id
     *
     * @return ClientServiceInterface
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function delete(int $id): ClientServiceInterface
    {
        if ($this->clientRepository->delete($id)->getAffectedNumRows() === 0) {
            throw new NoSuchItemException(__u('Client not found'), SPException::INFO);
        }

        return $this;
    }

    /**
     * @param int[] $ids
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->clientRepository->deleteByIdBatch($ids)->getAffectedNumRows() === 0) {
            throw new ServiceException(
                __u('Error while deleting the clients'),
                SPException::WARNING
            );
        }
    }

    /**
     * @param ClientModel $client
     *
     * @return int
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function create(ClientModel $client): int
    {
        return $this->clientRepository->create($client)->getLastId();
    }

    /**
     * @param ClientModel $client
     *
     * @return void
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function update(ClientModel $client): void
    {
        $this->clientRepository->update($client);
    }

    /**
     * Get all items from the service's repository
     *
     * @return array<T>
     * @throws SPException
     */
    public function getAll(): array
    {
        return $this->clientRepository->getAll()->getDataAsArray(ClientModel::class);
    }

    /**
     * Returns all clients visible for a given user
     *
     * @return Simple[]
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function getAllForUser(): array
    {
        return $this->clientRepository->getAllForFilter($this->accountFilterUser)->getDataAsArray();
    }
}
