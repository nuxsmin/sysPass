<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2014 Rubén Domínguez nuxsmin@syspass.org
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
defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre los clientes de sysPass
 */
class SP_Customer {

    public static $customerName;
    public static $customerDescription;
    public static $customerLastId;
    public static $customerHash;

    /**
     * @brief Obtener el listado de clientes
     * @param int $customerId con el Id del cliente
     * @param bool $retAssocArray para devolver un array asociativo
     * @return array con el id de cliente como clave y el nombre como valor
     */
    public static function getCustomers($customerId = NULL, $retAssocArray = FALSE) {
        $query = "SELECT customer_id,"
                . "customer_name, "
                . "customer_description "
                . "FROM customers ";

        if (!is_null($customerId)) {
            $query .= "WHERE customer_id = " . (int) $customerId . " LIMIT 1";
        } else {
            $query .= "ORDER BY customer_name";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if ($retAssocArray) {
            $resCustomers = array();

            foreach ($queryRes as $customer) {
                $resCustomers[$customer->customer_id] = $customer->customer_name;
            }

            return $resCustomers;
        }

        return $queryRes;
    }

    /**
     * @brief Crear un nuevo cliente en la BBDD
     * @return bool
     */
    public static function addCustomer() {
        $query = "INSERT INTO customers "
                . "SET customer_name = '" . DB::escape(self::$customerName) . "',"
                . "customer_hash = '" . self::mkCustomerHash() . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        self::$customerLastId = DB::$lastId;

        $message['action'] = _('Nuevo Cliente');
        $message['text'][] = _('Nombre') . ': ' . self::$customerName;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }

    /**
     * @brief Actualizar un cliente en la BBDD
     * @return bool
     */
    public static function updateCustomer($id) {
        $query = "UPDATE customers "
                . "SET customer_name = '" . DB::escape(self::$customerName) . "',"
                . "customer_description = '" . DB::escape(self::$customerDescription) . "',"
                . "customer_hash = '" . self::mkCustomerHash() . "' "
                . "WHERE customer_id = " . (int) $id;

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = _('Actualizar Cliente');
        $message['text'][] = _('Nombre') . ': ' . self::$customerName;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }

    /**
     * @brief Eliminar un cliente de la BBDD
     * @param int $id con el Id del cliente a eliminar
     * @return bool
     */
    public static function delCustomer($id) {
        $customerName = self::getCustomerById($id);

        $query = "DELETE FROM customers "
                . "WHERE customer_id = " . (int) $id . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = _('Eliminar Cliente');
        $message['text'][] = _('Nombre') . ': ' . $customerName;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }

    /**
     * @brief Crear un hash con el nombre del cliente
     * @return string con el hash generado
     * 
     * Esta función crear un hash para detectar clientes duplicados mediante
     * la eliminación de carácteres especiales y capitalización
     */
    private static function mkCustomerHash() {
        $charsSrc = array(
            ".", " ", "_", ", ", "-", ";
                ", "'", "\"", ":", "(", ")", "|", "/");
        $newValue = strtolower(str_replace($charsSrc, '', DB::escape(self::$customerName)));
        $hashValue = md5($newValue);

        return $hashValue;
    }

    /**
     * @brief Comprobar si existe un cliente duplicado comprobando el hash
     * @return bool
     */
    public static function checkDupCustomer($id = NULL) {
        if ($id === NULL) {
            $query = "SELECT customer_id "
                    . "FROM customers "
                    . "WHERE customer_hash = '" . self::mkCustomerHash() . "'";
        } else {
            $query = "SELECT customer_id "
                    . "FROM customers "
                    . "WHERE customer_hash = '" . self::mkCustomerHash() . "' AND customer_id <> " . $id;
        }
        
        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) >= 1) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Obtener el Id de un cliente por su nombre
     * @return int con el Id del cliente
     */
    public static function getCustomerByName() {
        $query = "SELECT customer_id "
                . "FROM customers "
                . "WHERE customer_hash = '" . self::mkCustomerHash() . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->customer_id;
    }

    /**
     * @brief Obtener el Nombre de un cliente por su Id
     * @param int $id con el Id del cliente
     * @return string con el nombre del cliente
     */
    public static function getCustomerById($id) {
        $query = "SELECT customer_name "
                . "FROM customers "
                . "WHERE customer_id = " . (int) $id . " LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->customer_name;
    }

    /**
     * @brief Obtener los datos de un cliente
     * @param int $id con el Id del cliente a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getCustomerData($id = 0) {
        $customer = array('customer_id' => 0,
            'customer_name' => '',
            'customer_description' => '',
            'action' => 1);

        if ($id > 0) {
            $customers = self::getCustomers($id);

            if ($customers) {
                foreach ($customers[0] as $name => $value) {
                    $customer[$name] = $value;
                }
                $customer['action'] = 2;
            }
        }

        return $customer;
    }

    /**
     * @brief Comprobar si un cliente está en uso
     * @param int $id con el Id del cliente a consultar
     * @return bool
     * 
     * Esta función comprueba si un cliente está en uso por cuentas.
     */
    public static function checkCustomerInUse($id) {
        $count['accounts'] = self::getCustomerInAccounts($id);
        return $count;
    }

    /**
     * @brief Obtener el número de cuentas que usan un cliente
     * @param int $id con el Id del cliente a consultar
     * @return integer con el número total de cuentas
     */
    private static function getCustomerInAccounts($id) {
        $query = "SELECT COUNT(*) as uses "
                . "FROM accounts "
                . "WHERE account_customerId = " . (int) $id;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->uses;
    }
}