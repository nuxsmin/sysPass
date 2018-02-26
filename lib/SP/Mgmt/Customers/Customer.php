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

namespace SP\Mgmt\Customers;

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Esta clase es la encargada de realizar las operaciones sobre los clientes de sysPass
 *
 * @property ClientData $itemData
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
            throw new SPException(__('Cliente duplicado', false), SPException::WARNING);
        }

        $query = /** @lang SQL */
            'INSERT INTO Client
            SET name = ?,
            description = ?,
            isGlobal = ?,
            hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getDescription());
        $Data->addParam($this->itemData->getIsGlobal());
        $Data->addParam($this->makeItemHash($this->itemData->getName()));
        $Data->setOnErrorMessage(__('Error al crear el cliente', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::$lastId);

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT id FROM Client WHERE hash = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getName()));

        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes !== false) {
            if ($Data->getQueryNumRows() === 0) {
                return false;
            } elseif ($Data->getQueryNumRows() === 1) {
                $this->itemData->setId($queryRes->customer_id);
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
            throw new SPException(__('No es posible eliminar', false), SPException::WARNING);
        }

        $query = /** @lang SQL */
            'DELETE FROM Client WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar el cliente', false));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(__('Cliente no encontrado', false), SPException::INFO);
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
            'SELECT account_id FROM Account WHERE account_customerId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param $id int
     * @return ClientData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, name, description, isGlobal FROM Client WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data);
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(__('Cliente duplicado', false), SPException::WARNING);
        }

        $query = /** @lang SQL */
            'UPDATE Client
            SET name = ?,
            description = ?,
            isGlobal = ?,
            hash = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getDescription());
        $Data->addParam($this->itemData->getIsGlobal());
        $Data->addParam($this->makeItemHash($this->itemData->getName()));
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al actualizar el cliente', false));

        DbWrapper::getQuery($Data);

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
            'SELECT id FROM Client WHERE hash = ? AND id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getName()));
        $Data->addParam($this->itemData->getId());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @return ClientData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name, description, isGlobal FROM Client ORDER BY name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data);
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
            'SELECT C.id as id, C.name as name 
            FROM Account A
            RIGHT JOIN Client C ON C.id = A.clientId
            WHERE A.clientId IS NULL
            OR isGlobal = 1
            OR (' . implode(' AND ', $queryWhere) . ')
            GROUP BY id
            ORDER BY name';

        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return ClientData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id, name, description, isGlobal FROM Client WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
    }
}
