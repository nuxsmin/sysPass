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
 * Esta clase es la encargada de realizar las operaciones sobre las categorías de sysPass.
 */
class SP_Category {
    public static $categoryName;
    public static $categoryDescription;
    public static $categoryLastId;

    /**
     * @brief Obtener el id de una categoría por el nombre
     * @param string $categoryName con el nombre de la categoría
     * @return bool|int si la consulta es errónea devuelve bool. Si no hay registros o se obtiene el id, devuelve int 
     */
    public static function getCategoryIdByName($categoryName) {
        $query = "SELECT category_id "
                . "FROM categories "
                . "WHERE category_name = '" . DB::escape($categoryName) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if (DB::$num_rows == 0) {
            return FALSE;
        } else {
            return $queryRes->category_id;
        }
    }

    /**
     * @brief Crear una nueva categoría en la BBDD
     * @param string $categoryName con el nombre de la categoría
     * @return bool
     */
    public static function addCategory() {
        $query = "INSERT INTO categories "
                . "SET category_name = '" . DB::escape(self::$categoryName) . "',"
                . "category_description = '" . DB::escape(self::$categoryDescription) . "'";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        self::$categoryLastId = DB::$lastId;

        $message['action'] = _('Nueva Categoría');
        $message['text'][] = _('Nombre') . ': ' . self::$categoryName;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }
   
    /**
     * @brief Comprobar si existe una categoría duplicada
     * @param int $id con el Id de la categoría a consultar
     * @return bool
     */
    public static function checkDupCategory($id = NULL) {

        if ($id === NULL) {
            $query = "SELECT category_id "
                    . "FROM categories "
                    . "WHERE category_name = '" . DB::escape(self::$categoryName) . "'";
        } else {
            $query = "SELECT category_id "
                    . "FROM categories "
                    . "WHERE category_name = '" . DB::escape(self::$categoryName) . "' AND category_id <> " . $id;
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
     * @brief Eliminar una categoría de la BBDD
     * @param int $id con el id de la categoría
     * @return bool
     */
    public static function delCategory($id) {
        $categoryName = self::getCategoryNameById($id);
        
        $query = "DELETE FROM categories "
                . "WHERE category_id = " . (int) $id . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = _('Eliminar Categoría');
        $message['text'][] = _('Nombre') . ': ' .$categoryName.' ('. $id.')';

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);
        
        return TRUE;
    }

    /**
     * @brief Actualizar una categoría en la BBDD con el id
     * @param int $id con el Id de la categoría a consultar
     * @return bool
     */
    public static function updateCategory($id) {
        $categoryName = self::getCategoryNameById($id);
        
        $query = "UPDATE categories "
                . "SET category_name = '" . DB::escape(self::$categoryName) . "',"
                . "category_description = '" . DB::escape(self::$categoryDescription) . "' "
                . "WHERE category_id = " . (int) $id . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }
        
        $message['action'] = _('Modificar Categoría');
        $message['text'][] = _('Nombre') . ': ' . $categoryName.' > '.self::$categoryName;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }

    /**
     * @brief Obtiene el listado de categorías
     * @param int $id con el Id de la categoría
     * @param bool $retAssocArray para devolver un array asociativo
     * @return array con en id de categorioa como clave y en nombre como valor
     */
    public static function getCategories($id = NULL, $retAssocArray = FALSE) {
        $query = "SELECT category_id,"
                . "category_name,"
                . "category_description "
                . "FROM categories ";

        if (!is_null($id)) {
            $query .= "WHERE category_id = " . (int) $id . " LIMIT 1";
        } else {
            $query .= "ORDER BY category_name";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return array();
        }

        if ($retAssocArray) {
            $resCategories = array();

            foreach ($queryRes as $category) {
                $resCategories[$category->category_id] = $category->category_name;
            }

            return $resCategories;
        }

        return $queryRes;
    }

    /**
     * @brief Obtiene el nombre de la categoría a partir del Id
     * @param int $id con el Id de la categoría a consultar
     * @return string con el nombre de la categoría
     */
    public static function getCategoryNameById($id) {
        $query = "SELECT category_name "
                . "FROM categories "
                . "WHERE category_id = " . (int) $id;
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->category_name;
    }

    /**
     * @brief Obtener los datos de una categoría
     * @param int $id con el Id de la categoría a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getCategoryData($id = 0) {
        $category = array('category_id' => 0,
            'category_name' => '',
            'category_description' => '',
            'action' => 1);

        if ($id > 0) {
            $categories = self::getCategories($id);

            if ($categories) {
                foreach ($categories[0] as $name => $value) {
                    $category[$name] = $value;
                }
                $category['action'] = 2;
            }
        }

        return $category;
    }
    
    /**
     * @brief Comprobar si una categoría está en uso
     * @param int $id con el Id de la categoría a consultar
     * @return bool
     * 
     * Esta función comprueba si una categoría está en uso por cuentas.
     */
    public static function checkCategoryInUse($id) {

        $numAccounts = self::getCategoriesInAccounts($id);

        $out = '';

        if ($numAccounts) {
            $out[] = _('Cuentas') . " (" . $numAccounts . ")";
        }

        if (is_array($out)) {
            return implode('<br>', $out);
        }

        return TRUE;
    }

    /**
     * @brief Obtener el número de cuentas que usan una categoría
     * @param int $id con el Id de la categoría a consultar
     * @return integer con el número total de cuentas
     */
    private static function getCategoriesInAccounts($id) {
        $query = "SELECT COUNT(*) as uses "
                . "FROM accounts "
                . "WHERE account_categoryId = " . (int) $id;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->uses;
    }

}
