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

namespace SP\Mgmt\Customers;

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomerData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Esta clase es la encargada de realizar las operaciones sobre los clientes de sysPass
 *
 * @property CustomerData $itemData
 */
class Customer extends CustomerBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_WARNING, __('Cliente duplicado', false));
        }

        $query = /** @lang SQL */
            'INSERT INTO customers
            SET customer_name = ?,
            customer_description = ?,
            customer_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getCustomerName());
        $Data->addParam($this->itemData->getCustomerDescription());
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));
        $Data->setOnErrorMessage(__('Error al crear el cliente', false));

        DB::getQuery($Data);

        $this->itemData->setCustomerId(DB::$lastId);

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));

        $queryRes = DB::getResults($Data);

        if ($queryRes !== false) {
            if ($Data->getQueryNumRows() === 0) {
                return false;
            } elseif ($Data->getQueryNumRows() === 1) {
                $this->itemData->setCustomerId($queryRes->customer_id);
            }
        }

        return true;
    }

    /**
     * @param $id int
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, __('No es posible eliminar', false));
        }

        $query = /** @lang SQL */
            'DELETE FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar el cliente', false));

        DB::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __('Cliente no encontrado', false));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT account_id FROM accounts WHERE account_customerId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param $id int
     * @return CustomerData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getResults($Data);
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_WARNING, __('Cliente duplicado', false));
        }

        $query = /** @lang SQL */
            'UPDATE customers
            SET customer_name = ?,
            customer_description = ?,
            customer_hash = ?
            WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getCustomerName());
        $Data->addParam($this->itemData->getCustomerDescription());
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));
        $Data->addParam($this->itemData->getCustomerId());
        $Data->setOnErrorMessage(__('Error al actualizar el cliente', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? AND customer_id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));
        $Data->addParam($this->itemData->getCustomerId());

        DB::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @return CustomerData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description FROM customers ORDER BY customer_name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
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
        $queryWhere = AccountUtil::getAccountFilterUser($Data);

        $query = /** @lang SQL */
            'SELECT customer_id as id, customer_name as name 
            FROM accounts 
            RIGHT JOIN customers ON customer_id = account_customerId
            WHERE account_customerId IS NULL 
            OR (' . implode(' AND ', $queryWhere) . ')
            GROUP BY customer_id
            ORDER BY customer_name';

        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return CustomerData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT customer_id, customer_name, customer_description FROM customers WHERE customer_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DB::getResultsArray($Data);
    }
}
