<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Customer{

    var $customerId;
    var $customerName;
    var $customerDescription;
    var $customerLastId;
    var $customerHash;

    /**
     * @brief Obtener el listado de clientes
     * @return array con el id de cliente como clave y el nombre como valor
     */ 
    public static function getCustomers(){
        $strQuery = "SELECT customer_id, customer_name "
                . "FROM customers ORDER BY customer_name";
        $resQuery = DB::getResults($strQuery, __FUNCTION__);
        
        if ( $resQuery === FALSE || ! is_array($resQuery) ){
            return FALSE;
        }
        
        $resCustomers = array();
        
        foreach ( $resQuery as $customer ){
            $resCustomers[$customer->customer_id] = $customer->customer_name;
        }

        return $resCustomers;
    }
    
    /**
     * @brief Crear un nuevo cliente en la BBDD
     * @return bool
     */ 
    public function customerAdd(){
        $strQuery = "INSERT INTO customers SET "
                . "customer_name = '".DB::escape($this->customerName)."',"
                . "customer_hash = '".$this->mkCustomerHash()."'";
        
        if ( DB::doQuery($strQuery, __FUNCTION__) === FALSE ){
            return FALSE;
        }
        
        $this->customerLastId = DB::$lastId;
        
        return TRUE;
    }

    /**
     * @brief Eliminar un cliente de la BBDD
     * @return bool
     */ 
    public function customerDel(){
        $strQuery = "DELETE FROM customers"
                . " WHERE customer_id = $this->customerId LIMIT 1";
        
        if ( DB::doQuery($strQuery, __FUNCTION__) === FALSE ){
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * @brief Crear un hash con el nombre del cliente
     * @return string con el hash generado
     * 
     * Esta función crear un hash para detectar clientes duplicados mediante
     * la eliminación de carácteres especiales y capitalización
     */ 
    private function mkCustomerHash(){
        $charsSrc = array("."," ","_",",","-",";","'","\"",":","(",")","|","/");
        $newValue = strtolower(str_replace($charsSrc, '', DB::escape($this->customerName)));
        $hashValue = md5($newValue);
        
        return $hashValue; 
    }
    
    /**
     * @brief Comprobar si existe un cliente duplicado comprobando el hash
     * @return bool
     */ 
    public function chekDupCustomer(){
        $strQuery = "SELECT customer_id "
                . "FROM customers "
                . "WHERE customer_hash = '".$this->mkCustomerHash()."'";
        
        if ( DB::doQuery($strQuery, __FUNCTION__) === FALSE ){
            return FALSE;
        }
        
        if ( count(DB::$last_result) >= 1 ){
            return FALSE;
        }
        
        return TRUE;
    }
}