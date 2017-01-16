<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\CustomerData;
use SP\DataModel\CustomFieldData;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Esta clase es la encargada de realizar las operaciones sobre los clientes de sysPass
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

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, __('Error al crear el cliente', false));
        }

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
     * @param $id int|array
     * @return mixed
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $itemId) {
                $this->delete($itemId);
            }

            return $this;
        }

        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, __('No es posible eliminar', false));
        }

        // FIXME: utilizar SQL
        $oldCustomer = $this->getById($id);

        if (!is_object($oldCustomer)) {
            throw new SPException(SPException::SP_CRITICAL, __('Cliente no encontrado', false));
        }

        $query = /** @lang SQL */
            'DELETE FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, __('Error al eliminar el cliente', false));
        }

        try {
            $CustomFieldData = new CustomFieldData();
            $CustomFieldData->setModule(ActionsInterface::ACTION_MGM_CUSTOMERS);
            CustomField::getItem($CustomFieldData)->delete($id);
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }


        return $this;
    }

    /**
     * @param $id int
     * @return mixed
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

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, __('Error al actualizar el cliente', false));
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? AND customer_id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));
        $Data->addParam($this->itemData->getCustomerId());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() >= 1);
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

        // Si el perfil no permite búsquedas globales, se acotan los resultados
        if (!Session::getUserProfile()->isAccGlobalSearch()) {
            $queryWhere = AccountUtil::getAccountFilterUser($Data);

            $query = 'SELECT customer_id, customer_name 
            FROM accounts LEFT JOIN customers ON customer_id = account_customerId
            WHERE ' . implode(' AND ', $queryWhere) . '
            GROUP BY customer_id
            ORDER BY customer_name';
        } else {
            $query = 'SELECT customer_id, customer_name 
            FROM accounts LEFT JOIN customers ON customer_id = account_customerId 
            GROUP BY customer_id
            ORDER BY customer_name';
        }

        $Data->setQuery($query);

        $items = [];
        
        /** @var ItemInterface $this */
        foreach (DB::getResultsArray($Data) as $item) {
            $obj = new \stdClass();
            $obj->id = (int)$item->customer_id;
            $obj->name = $item->customer_name;

            $items[] = $obj;
        }

        return $items;
    }
}
