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

defined('APP_ROOT') || die();

use SP\Core\ActionsInterface;

/**
 * Interface DataGridInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridInterface
{
    /**
     * @param $id string
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param DataGridHeaderInterface $header
     */
    public function setHeader(DataGridHeaderInterface $header);

    /**
     * @return DataGridHeaderInterface
     */
    public function getHeader();

    /**
     * @param DataGridDataInterface $data
     */
    public function setData(DataGridDataInterface $data);

    /**
     * @return DataGridDataInterface
     */
    public function getData();

    /**
     * @param DataGridActionInterface $action
     * @param bool                    $isMenu Añadir al menu de acciones
     * @return $this
     */
    public function setDataActions(DataGridActionInterface $action, $isMenu = false);

    /**
     * @return DataGridActionInterface
     */
    public function getDataActions();

    /**
     * @return mixed
     */
    public function getGrid();

    /**
     * Establecer el paginador
     *
     * @param DataGridPagerInterface $pager
     */
    public function setPager(DataGridPagerInterface $pager);

    /**
     * Devolver el paginador
     *
     * @return DataGridPagerInterface
     */
    public function getPager();

    /**
     * @param ActionsInterface $action
     */
    public function setOnCloseAction(ActionsInterface $action);

    /**
     * Establecer la plantilla utilizada para la cabecera
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataHeaderTemplate($template);

    /**
     * Devolver la plantilla utilizada para la cabecera
     *
     * @return string
     */
    public function getDataHeaderTemplate();

    /**
     * Establecer la plantilla utilizada para las acciones
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataActionsTemplate($template);

    /**
     * Devolver la plantilla utilizada para las acciones
     *
     * @return string
     */
    public function getDataActionsTemplate();

    /**
     * Establecer la plantilla utilizada para el paginador
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataPagerTemplate($template);

    /**
     * Devolver la plantilla utilizada para el paginador
     *
     * @return string
     */
    public function getDataPagerTemplate();

    /**
     * Establcer la plantilla utilizada para los datos de la consulta
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataRowTemplate($template);

    /**
     * Devolver la plantilla utilizada para los datos de la consulta
     *
     * @return string
     */
    public function getDataRowTemplate();

    /**
     * Devuelve el tiempo total de carga del DataGrid
     *
     * @return int
     */
    public function getTime();

    /**
     * Establece el tiempo total de carga del DataGrid
     *
     * @param int $time
     */
    public function setTime($time);

    /**
     * Devolver las acciones que se muestran en un menu
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenu();
}