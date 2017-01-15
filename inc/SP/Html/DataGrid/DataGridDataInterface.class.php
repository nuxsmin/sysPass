<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Html\DataGrid;

use SP\Html\Assets\IconInterface;

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
     * @param $source string
     */
    public function addDataRowSource($source);

    /**
     * Devolver los orígenes de datos de la consulta
     *
     * @return array
     */
    public function getDataRowSources();

    /**
     * Establecer el origen de datos utilizado como Id de los elementos
     *
     * @param $id string
     */
    public function setDataRowSourceId($id);

    /**
     * Devolver el origen de datos utilizado como Id de los elementos
     *
     * @return string
     */
    public function getDataRowSourceId();

    /**
     * Establecer los datos de la consulta
     *
     * @param $data array
     */
    public function setData(array $data);

    /**
     * Devolver los datos de la consulta
     *
     * @return array
     */
    public function getData();

    /**
     * Establecer los orígenes de datos que se muestran con iconos
     *
     * @param $source string
     * @param $icon IconInterface
     * @param mixed $value Valor para mostrar el icono
     * @return
     */
    public function addDataRowSourceWithIcon($source, IconInterface $icon, $value = 1);

    /**
     * Devolver los orígenes de datos que se muestran con iconos
     *
     * @return array
     */
    public function getDataRowSourcesWithIcon();

    /**
     * Devolver el número de elementos obtenidos
     *
     * @return int
     */
    public function getDataCount();
}