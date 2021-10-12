<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;
use SP\Storage\Database\QueryResult;

defined('APP_ROOT') || die();

/**
 * Interface DataGridDataInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridDataInterface
{
    /**
     * Establecer los orígenes de datos de la consulta
     *
     * @param string        $source
     * @param bool          $isMethod
     * @param callable|null $filter
     * @param bool          $truncate
     */
    public function addDataRowSource(
        string   $source,
        bool     $isMethod = false,
        callable $filter = null,
        bool     $truncate = true
    ): void;

    /**
     * Devolver los orígenes de datos de la consulta
     *
     * @return array
     */
    public function getDataRowSources(): array;

    /**
     * Establecer el origen de datos utilizado como Id de los elementos
     *
     * @param $id string
     */
    public function setDataRowSourceId(string $id): void;

    /**
     * Devolver el origen de datos utilizado como Id de los elementos
     *
     * @return string
     */
    public function getDataRowSourceId(): string;

    /**
     * Establecer los datos de la consulta
     *
     * @param QueryResult $queryResult
     */
    public function setData(QueryResult $queryResult): void;

    /**
     * Devolver los datos de la consulta
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Establecer los orígenes de datos que se muestran con iconos
     *
     * @param       $source string
     * @param       $icon   IconInterface
     * @param mixed $value  Valor para mostrar el icono
     */
    public function addDataRowSourceWithIcon(
        string        $source,
        IconInterface $icon,
        int           $value = 1
    ): void;

    /**
     * Devolver los orígenes de datos que se muestran con iconos
     *
     * @return array
     */
    public function getDataRowSourcesWithIcon(): array;

    /**
     * Devolver el número de elementos obtenidos
     *
     * @return int
     */
    public function getDataCount(): int;
}