<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories;

use Exception;
use SP\DataModel\DataModelInterface;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\Database\DBStorageInterface;

/**
 * Trait RepositoryItemTrait
 *
 * @package SP\Repositories
 */
trait RepositoryItemTrait
{
    /**
     * Eliminar elementos en lotes
     *
     * @param $ids
     *
     * @return array
     */
    public function deleteBatch(array $ids)
    {
        /** @var RepositoryItemInterface $this */
        $items = $this->getByIdBatch($ids);

        /** @var DataModelInterface[] $items */
        foreach ($items as $key => $item) {
            try {
                $this->delete($item->getId());
            } catch (Exception $e) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Crear un hash con el nombre del elemento.
     *
     * Esta función crear un hash para detectar nombres de elementos duplicados mediante
     * la eliminación de carácteres especiales y capitalización
     *
     * @param string             $name
     * @param DBStorageInterface $DBStorage
     *
     * @return string con el hash generado
     */
    protected function makeItemHash($name, DBStorageInterface $DBStorage)
    {
        $charsSrc = ['.', ' ', '_', ', ', '-', ';', '\'', '"', ':', '(', ')', '|', '/'];

        $databaseUtil = new DatabaseUtil($DBStorage);

        return md5(strtolower(str_replace($charsSrc, '', $databaseUtil->escape($name))));
    }

    /**
     * Devuelve una cadena con los parámetros para una consulta SQL desde un array
     *
     * @param array  $items
     * @param string $string Cadena a utilizar para los parámetros
     *
     * @return string
     */
    protected function getParamsFromArray(array $items, $string = '?')
    {
        return implode(',', array_fill(0, count($items), $string));
    }
}