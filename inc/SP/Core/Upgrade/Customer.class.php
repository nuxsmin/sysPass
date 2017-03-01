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

namespace SP\Core\Upgrade;

use SP\Core\Exceptions\SPException;
use SP\Core\TaskFactory;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Customer
 *
 * @package SP\Core\Upgrade
 */
class Customer
{
    /**
     * Actualizar registros con clientes no existentes
     *
     * @param int $customerId Id de cliente por defecto
     * @return bool
     */
    public static function fixCustomerId($customerId)
    {
        TaskFactory::$Message->setTask(__FUNCTION__);
        TaskFactory::$Message->setMessage(__('Actualizando IDs de clientes'));
        TaskFactory::sendTaskMessage();

        try {
            DB::beginTransaction();

            if ($customerId === 0) {
                $customerId = self::createOrphanCustomer();
            }

            $Data = new QueryData();
            $Data->addParam($customerId);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_customerId = ? WHERE acchistory_customerId NOT IN (SELECT customer_id FROM customers ORDER BY customer_id) OR acchistory_customerId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accounts SET account_customerId = ? WHERE account_customerId NOT IN (SELECT customer_id FROM customers ORDER BY customer_id) OR account_customerId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);
            
            DB::endTransaction();

            return true;
        } catch (SPException $e) {
            DB::rollbackTransaction();

            return false;
        }
    }

    /**
     * Crear un cliente para elementos huérfanos
     *
     * @return int
     */
    public static function createOrphanCustomer()
    {
        $query = /** @lang SQL */
            'INSERT INTO customers SET
            customer_name = \'Orphan customer\',
            customer_hash = MD5(\'Orphan customer\'),
            customer_description = \'Created by the upgrade process\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al crear el cliente', false));

        DB::getQuery($Data);

        return DB::getLastId();
    }
}