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

namespace SP\Services\Account;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountHistoryRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountHistoryService
 *
 * @package SP\Services\Account
 */
final class AccountHistoryService extends Service
{
    /**
     * @var AccountHistoryRepository
     */
    protected $accountHistoryRepository;
    /**
     * @var AccountToUserGroupRepository
     */
    protected $accountToUserGroupRepository;
    /**
     * @var AccountToUserRepository
     */
    protected $accountToUserRepository;

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return AccountHistoryData
     * @throws SPException
     * @throws SPException
     */
    public function getById($id)
    {
        $results = $this->accountHistoryRepository->getById($id);

        if ($results->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Error while retrieving account\'s data'));
        }

        return $results->getData();
    }

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $id
     *
     * @return array Con los registros con id como clave y fecha - usuario como valor
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getHistoryForAccount($id)
    {
        return self::mapHistoryForDateSelect($this->accountHistoryRepository->getHistoryForAccount($id)->getDataAsArray());
    }

    /**
     * Masps history items to fill in a date select
     *
     * @param array $history
     *
     * @return array
     */
    private static function mapHistoryForDateSelect(array $history)
    {
        $items = [];

        foreach ($history as $item) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if (empty($item->dateEdit) || $item->dateEdit === '0000-00-00 00:00:00') {
                $date = $item->dateAdd . ' - ' . $item->userAdd;
            } else {
                $date = $item->dateEdit . ' - ' . $item->userEdit;
            }

            $items[$item->id] = $date;
        };

        return $items;
    }

    /**
     * @param $id
     *
     * @return ItemData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getUsersByAccountId($id)
    {
        return $this->accountToUserRepository->getUsersByAccountId($id)->getDataAsArray();
    }

    /**
     * @param $id
     *
     * @return ItemData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getUserGroupsByAccountId($id)
    {
        return $this->accountToUserGroupRepository->getUserGroupsByAccountId($id)->getDataAsArray();
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->accountHistoryRepository->search($itemSearchData);
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountHistoryCreateDto $dto
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(AccountHistoryCreateDto $dto)
    {
        return $this->accountHistoryRepository->create($dto);
    }

    /**
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAccountsPassData()
    {
        return $this->accountHistoryRepository->getAccountsPassData()->getDataAsArray();
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param array|int $id
     *
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     */
    public function delete($id)
    {
        if ($this->accountHistoryRepository->delete($id) === 0) {
            throw new ServiceException(__u('Error while deleting the account'));
        }
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteByIdBatch(array $ids)
    {
        return $this->accountHistoryRepository->deleteByIdBatch($ids);
    }

    /**
     * Deletes all the items for given accounts id
     *
     * @param array $ids
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteByAccountIdBatch(array $ids)
    {
        return $this->accountHistoryRepository->deleteByAccountIdBatch($ids);
    }

    /**
     * @param AccountPasswordRequest $accountRequest
     *
     * @throws SPException
     * @throws ConstraintException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest)
    {
        if ($this->accountHistoryRepository->updatePassword($accountRequest) !== 1) {
            throw new ServiceException(__u('Error while updating the password'));
        }
    }

    /**
     * Returns all the items
     *
     * @return array
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll()
    {
        return $this->accountHistoryRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountHistoryRepository = $this->dic->get(AccountHistoryRepository::class);
        $this->accountToUserRepository = $this->dic->get(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->dic->get(AccountToUserGroupRepository::class);
    }
}