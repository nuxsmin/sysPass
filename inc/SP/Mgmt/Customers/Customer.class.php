<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Mgmt\Customers;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;
use SP\DataModel\CustomerData;
use SP\DataModel\CustomFieldData;
use SP\Log\Email;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Html\Html;
use SP\Log\Log;
use SP\Core\Exceptions\SPException;
use SP\Storage\DBUtil;
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
            throw new SPException(SPException::SP_WARNING, _('Cliente duplicado'));
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
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear el cliente'));
        }

        $this->itemData->setCustomerId(DB::$lastId);

        $Log = new Log(_('Nuevo Cliente'));
        $Log->addDetails(Html::strongText(_('Cliente')), $this->itemData->getCustomerName());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCustomerName()));

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() >= 1);
    }

    /**
     * @param $id int|array
     * @return mixed
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
            throw new SPException(SPException::SP_WARNING, _('No es posible eliminar'));
        }

        $oldCustomer = $this->getById($id);

        $query = /** @lang SQL */
            'DELETE FROM customers WHERE customer_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar el cliente'));
        }

        $Log = new Log(_('Eliminar Cliente'));
        $Log->addDetails(Html::strongText(_('Cliente')), sprintf('%s (%d)', $oldCustomer->getCustomerName(), $id));


        try {
            $CustomFieldData = new CustomFieldData();
            $CustomFieldData->setModule(ActionsInterface::ACTION_MGM_CUSTOMERS);
            CustomField::getItem($CustomFieldData)->delete($id);
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription($e->getMessage());
        }

        $Log->writeLog();

        Email::sendEmail($Log);

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
            throw new SPException(SPException::SP_WARNING, _('Cliente duplicado'));
        }

        $oldCustomer = $this->getById($this->itemData->getCustomerId());

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
            throw new SPException(SPException::SP_CRITICAL, _('Error al actualizar el cliente'));
        }

        $Log = new Log(_('Actualizar Cliente'));
        $Log->addDetails(Html::strongText(_('Nombre')), sprintf('%s > %s', $oldCustomer->getCustomerName(), $this->itemData->getCustomerName()));
        $Log->addDetails(Html::strongText(_('Descripción')), sprintf('%s > %s', $oldCustomer->getCustomerDescription(), $this->itemData->getCustomerDescription()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT customer_id FROM customers WHERE customer_hash = ? AND customer_id <> ?';

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
}
