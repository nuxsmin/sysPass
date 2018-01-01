<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Account\AccountUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemSearchData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class ClientService
 *
 * @package SP\Services\Client
 */
class ClientService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Creates an item
     *
     * @param ClientData $itemData
     * @return mixed
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Cliente duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO customers
            SET customer_name = ?,
            customer_description = ?,
            customer_isGlobal = ?,
            customer_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getCustomerName());
        $Data->addParam($itemData->getCustomerDescription());
        $Data->addParam($itemData->getCustomerIsGlobal());
        $Data->addParam($this->makeItemHash($itemData->getCustomerName()));
        $Data->setOnErrorMessage(__u('Error al crear el cliente'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param ClientData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getCustomerName()));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param ClientData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('Cliente duplicado'));
        }

        $query = /** @lang SQL */
            'UPDATE customers
            SET customer_name = ?,
            customer_description = ?,
            customer_isGlobal = ?,
            customer_hash = ?
            WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getCustomerName());
        $Data->addParam($itemData->getCustomerDescription());
        $Data->addParam($itemData->getCustomerIsGlobal());
        $Data->addParam($this->makeItemHash($itemData->getCustomerName()));
        $Data->addParam($itemData->getCustomerId());
        $Data->setOnErrorMessage(__u('Error al actualizar el cliente'));

        DbWrapper::getQuery($Data, $this->db);

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ClientData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? AND customer_id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($itemData->getCustomerName()));
        $Data->addParam($itemData->getCustomerId());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description, customer_isGlobal FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return array
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description, customer_isGlobal FROM customers ORDER BY customer_name';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description, customer_isGlobal FROM customers WHERE customer_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return ClientService
     * @throws SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, __u('No es posible eliminar'));
        }

        $query = /** @lang SQL */
            'DELETE FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el cliente'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Cliente no encontrado'));
        }

        return $this;
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT account_id FROM accounts WHERE account_customerId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return ClientData[]
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(ClientData::class);
        $Data->setSelect('customer_id, customer_name, customer_description, customer_isGlobal');
        $Data->setFrom('customers');
        $Data->setOrder('customer_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('customer_name LIKE ? OR customer_description LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Devolver los clientes visibles por el usuario
     *
     * @return array
     */
    public function getItemsForSelectByUser()
    {
        $Data = new QueryData();

        // Acotar los resultados por usuario
        $queryWhere = AccountUtil::getAccountFilterUser($Data, $this->session);

        $query = /** @lang SQL */
            'SELECT customer_id AS id, customer_name AS name 
            FROM accounts 
            RIGHT JOIN customers ON customer_id = account_customerId
            WHERE account_customerId IS NULL
            OR customer_isGlobal = 1
            OR (' . implode(' AND ', $queryWhere) . ')
            GROUP BY customer_id
            ORDER BY customer_name';

        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }
}