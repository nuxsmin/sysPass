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
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class Category
 *
 * @package SP\Core\Upgrade
 */
class Category
{
    /**
     * Actualizar registros con categorías no existentes
     *
     * @param int $categoryId Id de categoría por defecto
     * @return bool
     */
    public static function fixCategoriesId($categoryId)
    {
        TaskFactory::$Message->setTask(__FUNCTION__);
        TaskFactory::$Message->setMessage(__('Actualizando IDs de categorías'));
        TaskFactory::update();

        try {
            DbWrapper::beginTransaction();

            if ($categoryId === 0) {
                $categoryId = self::createOrphanCategory();
            }

            $Data = new QueryData();
            $Data->addParam($categoryId);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_categoryId = ? WHERE acchistory_categoryId NOT IN (SELECT id FROM categories ORDER BY id) OR acchistory_categoryId IS NULL';
            $Data->setQuery($query);

            DbWrapper::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE Account SET account_categoryId = ? WHERE account_categoryId NOT IN (SELECT id FROM categories ORDER BY id) OR account_categoryId IS NULL';
            $Data->setQuery($query);

            DbWrapper::getQuery($Data);

            DbWrapper::endTransaction();

            return true;
        } catch (SPException $e) {
            DbWrapper::rollbackTransaction();

            return false;
        }
    }

    /**
     * Crear una categoría para elementos huérfanos
     *
     * @return int
     */
    public static function createOrphanCategory()
    {
        $query = /** @lang SQL */
            '\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al crear la categoría', false));

        DbWrapper::getQuery($Data);

        return DbWrapper::getLastId();
    }
}