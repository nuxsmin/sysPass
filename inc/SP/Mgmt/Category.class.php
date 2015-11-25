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

namespace SP\Mgmt;

use SP\Log\Email;
use SP\Storage\DB;
use SP\Html\Html;
use SP\Log\Log;
use SP\Core\SPException;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre las categorías de sysPass.
 */
class Category
{
    public static $categoryName;
    public static $categoryDescription;
    public static $categoryLastId;

    /**
     * Eliminar una categoría de la BBDD.
     *
     * @param int $id con el id de la categoría
     * @throws SPException
     */
    public static function deleteCategory($id)
    {
        $resCategoryUse = self::checkCategoryInUse($id);

        if ($resCategoryUse !== true) {
            throw new SPException(SPException::SP_WARNING, _('No es posible eliminar') . ';;' . _('Categoría en uso por:') . ';;' . $resCategoryUse);
        }

        $curCategoryName = self::getCategoryNameById($id);

        $query = 'DELETE FROM categories WHERE category_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar la categoría'));
        }

        $Log = new Log(_('Eliminar Categoría'));
        $Log->addDetails(Html::strongText(_('Categoría')), $curCategoryName . ' (' . $id . ')');
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * Comprobar si una categoría está en uso por cuentas.
     *
     * @param int $id con el Id de la categoría a consultar
     * @return bool|string
     */
    public static function checkCategoryInUse($id)
    {
        $numAccounts = self::getCategoriesInAccounts($id);

        $out = '';

        if ($numAccounts) {
            $out[] = _('Cuentas') . " (" . $numAccounts . ")";
        }

        if (is_array($out)) {
            return implode('<br>', $out);
        }

        return true;
    }

    /**
     * Obtener el número de cuentas que usan una categoría.
     *
     * @param int $id con el Id de la categoría a consultar
     * @return false|integer con el número total de cuentas
     */
    private static function getCategoriesInAccounts($id)
    {
        $query = 'SELECT account_id FROM accounts WHERE account_categoryId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');

        DB::getQuery($Data);

        return DB::$lastNumRows;
    }

    /**
     * Obtiene el nombre de la categoría a partir del Id.
     *
     * @param int $id con el Id de la categoría a consultar
     * @return false|string con el nombre de la categoría
     */
    public static function getCategoryNameById($id)
    {
        $query = 'SELECT category_name FROM categories WHERE category_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->category_name;
    }

    /**
     * Actualizar una categoría en la BBDD con el id.
     *
     * @param int $id con el Id de la categoría a consultar
     * @throws SPException
     */
    public static function updateCategory($id)
    {
        if (self::checkDupCategory($id)) {
            throw new SPException(SPException::SP_WARNING, _('Nombre de categoría duplicado'));
        }

        $curCategoryName = self::getCategoryNameById($id);

        $query = 'UPDATE categories '
            . 'SET category_name = :name, category_description = :description '
            . 'WHERE category_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');
        $Data->addParam(self::$categoryName, 'name');
        $Data->addParam(self::$categoryDescription, 'description');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al actualizar la categoría'));
        }

        $Log = new Log(_('Modificar Categoría'));
        $Log->addDetails(Html::strongText(_('Categoría')), $curCategoryName . ' > ' . self::$categoryName);
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * Comprobar si existe una categoría duplicada.
     *
     * @param int $id con el Id de la categoría a consultar
     * @return bool
     */
    public static function checkDupCategory($id = null)
    {

        $Data = new QueryData();

        if (is_null($id)) {
            $query = 'SELECT category_id FROM categories WHERE category_name = :name';
        } else {
            $query = 'SELECT category_id FROM categories WHERE category_name = :name AND category_id <> :id';

            $Data->addParam($id, 'id');
        }

        $Data->setQuery($query);
        $Data->addParam(self::$categoryName, 'name');

        return (DB::getQuery($Data) === false || DB::$lastNumRows >= 1);
    }

    /**
     * Obtener los datos de una categoría.
     *
     * @param int $id con el Id de la categoría a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getCategoryData($id = 0)
    {
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
     * Obtiene el listado de categorías.
     *
     * @param int  $id            con el Id de la categoría
     * @param bool $retAssocArray para devolver un array asociativo
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public static function getCategories($id = null, $retAssocArray = false)
    {
        $query = 'SELECT category_id, category_name,category_description FROM categories ';

        $Data = new QueryData();

        if (!is_null($id)) {
            $query .= "WHERE category_id = :id LIMIT 1";
            $Data->addParam($id, 'id');
        } else {
            $query .= "ORDER BY category_name";
        }

        $Data->setQuery($query);

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
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
     * Crear una categoría y devolver el id si existe o de la nueva
     *
     * @param $name        string El nombre de la categoría
     * @param $description string La descripción de la categoría
     * @return int
     */
    public static function addCategoryReturnId($name, $description = '')
    {
        // Comprobamos si existe la categoría o la creamos
        $newCategoryId = self::getCategoryIdByName($name);

        if ($newCategoryId == 0) {
            self::$categoryName = $name;
            self::$categoryDescription = $description;

            try {
                self::addCategory();
                $newCategoryId = self::$categoryLastId;
            } catch (SPException $e) {
            }
        }

        return (int)$newCategoryId;
    }

    /**
     * Obtener el id de una categoría por el nombre.
     *
     * @param string $categoryName con el nombre de la categoría
     * @return bool|int si la consulta es errónea devuelve bool. Si no hay registros o se obtiene el id, devuelve int
     */
    public static function getCategoryIdByName($categoryName)
    {
        $query = 'SELECT category_id FROM categories WHERE category_name = :name LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($categoryName, 'name');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false || DB::$lastNumRows === 0) {
            return false;
        }

        return $queryRes->category_id;
    }

    /**
     * Crear una nueva categoría en la BBDD.
     *
     * @throws SPException
     */
    public static function addCategory()
    {
        if (self::checkDupCategory()) {
            throw new SPException(SPException::SP_WARNING, _('Nombre de categoría duplicado'));
        }

        $query = 'INSERT INTO categories SET category_name = :name ,category_description = :description';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(self::$categoryName, 'name');
        $Data->addParam(self::$categoryDescription, 'description');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear la categoría'));
        }

        self::$categoryLastId = DB::$lastId;

        $Log = new Log(_('Nueva Categoría'));
        $Log->addDetails(Html::strongText(_('Categoría')), self::$categoryName);
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * Obtiene el listado de categorías mediante una búsqueda
     *
     * @param int    $limitCount
     * @param int    $limitStart
     * @param string $search La cadena de búsqueda
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public static function getCategoriesMgmtSearch($limitCount, $limitStart = 0, $search = "")
    {
        $query = 'SELECT category_id, category_name,category_description FROM categories';

        $Data = new QueryData();

        if (!empty($search)) {
            $search = '%' . $search . '%';

            $query .= ' WHERE category_name LIKE ? '
                . 'OR category_description LIKE ?';

            $Data->addParam($search);
            $Data->addParam($search);
        }

        $query .= ' ORDER BY category_name';
        $query .= ' LIMIT ?,?';

        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }
}
