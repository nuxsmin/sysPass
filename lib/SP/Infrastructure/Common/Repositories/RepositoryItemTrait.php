<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Infrastructure\Common\Repositories;

use Exception;
use RuntimeException;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Domain\Common\Out\DataModelInterface;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\DbStorageInterface;

/**
 * Trait RepositoryItemTrait
 *
 * @package SP\Infrastructure\Common\Repositories
 */
trait RepositoryItemTrait
{
    /**
     * Eliminar elementos en lotes
     *
     * @param  int[]  $ids
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteBatch(array $ids): array
    {
        if (!class_implements($this, RepositoryInterface::class)) {
            throw new RuntimeException(
                sprintf(
                    'This class must implement %s',
                    RepositoryInterface::class
                )
            );
        }

        $items = $this->getByIdBatch($ids)->getDataAsArray();

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
     */
    protected function makeItemHash(
        string $name,
        DbStorageInterface $DBStorage
    ): string {
        $charsSrc = ['.', ' ', '_', ', ', '-', ';', '\'', '"', ':', '(', ')', '|', '/'];

        $databaseUtil = new DatabaseUtil($DBStorage);

        return md5(
            strtolower(
                str_replace($charsSrc, '', $databaseUtil->escape($name))
            )
        );
    }

    /**
     * Devuelve una cadena con los parámetros para una consulta SQL desde un array
     *
     * @param  array  $items
     * @param  string  $placeholder  Cadena a utilizar para los parámetros
     *
     * @return string
     */
    protected function buildParamsFromArray(
        array $items,
        string $placeholder = '?'
    ): string {
        return implode(
            ',',
            array_fill(0, count($items), $placeholder)
        );
    }
}