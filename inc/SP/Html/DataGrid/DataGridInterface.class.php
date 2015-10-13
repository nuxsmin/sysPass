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

namespace SP\Html\DataGrid;

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
     * @param DataGridHeader $header
     */
    public function setHeader(DataGridHeader $header);

    /**
     * @return DataGridHeader
     */
    public function getHeader();

    /**
     * @param DataGridData $data
     */
    public function setData(DataGridData $data);

    /**
     * @return DataGridData
     */
    public function getData();

    /**
     * @param DataGridAction $action
     */
    public function setDataActions(DataGridAction $action);

    /**
     * @return DataGridAction
     */
    public function getDataActions();

    /**
     * @return mixed
     */
    public function getGrid();

    /**
     * @param ActionsInterface $action
     */
    public function setOnCloseAction(ActionsInterface $action);
}