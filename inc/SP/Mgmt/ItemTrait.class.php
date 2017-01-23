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

namespace SP\Mgmt;

use SP\Core\Exceptions\SPException;
use SP\DataModel\DataModelInterface;
use SP\Storage\DBUtil;

/**
 * Class ItemTrait
 *
 * @package SP\Mgmt
 */
trait ItemTrait
{
    /**
     * Devolver los elementos para un campo select
     *
     * @return array
     */
    public function getItemsForSelect()
    {
        $items = [];

        /** @var DataModelInterface $item */
        /** @var ItemInterface $this */
        foreach ($this->getAll() as $item) {
            $obj = new \stdClass();
            $obj->id = (int)$item->getId();
            $obj->name = $item->getName();

            $items[] = $obj;
        }

        return $items;
    }

    /**
     * Eliminar elementos en lotes
     *
     * @param $ids
     * @return array
     */
    public function deleteBatch(array $ids)
    {
        $items = $this->getByIdBatch($ids);

        /** @var DataModelInterface[] $items */
        foreach ($items as $key => $item) {
            try {
                $this->delete($item->getId());
            } catch (SPException $e) {
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
     * @param $name
     * @return string con el hash generado
     */
    protected function makeItemHash($name)
    {
        $charsSrc = ['.', ' ', '_', ', ', '-', ';', '\'', '"', ':', '(', ')', '|', '/'];
        $newValue = strtolower(str_replace($charsSrc, '', DBUtil::escape($name)));

        return md5($newValue);
    }

    /**
     * Devuelve una cadena con los parámetros para una consulta SQL desde un array
     *
     * @param array  $items
     * @param string $string Cadena a utilizar para los parámetros
     * @return string
     */
    protected function getParamsFromArray(array $items, $string = '?')
    {
        return implode(',', array_fill(0, count($items), $string));
    }
}