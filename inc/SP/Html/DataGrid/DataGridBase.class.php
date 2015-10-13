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
use SplObjectStorage;

/**
 * Class DataGridBase para crear una matriz de datos
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridBase implements DataGridInterface
{
    /**
     * El id de la matriz
     *
     * @var string
     */
    private $_id = '';
    /**
     * La cabecera de la matriz
     *
     * @var DataGridHeader
     */
    private $_header;
    /**
     * Los datos de la matriz
     *
     * @var DataGridData
     */
    private $_data;
    /**
     * Las acciones asociadas a los elementos de la matriz
     *
     * @var DataGridAction[]
     */
    private $_actions = null;
    /**
     * La acción a realizar al cerrar la matriz
     *
     * @var int
     */
    private $_onCloseAction = 0;

    /**
     * @return int
     */
    public function getOnCloseAction()
    {
        return $this->_onCloseAction;
    }

    /**
     * @param ActionsInterface $action
     */
    public function setOnCloseAction(ActionsInterface $action)
    {
        $this->_onCloseAction = $action;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id string
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return DataGridHeader
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * @param DataGridHeader $header
     */
    public function setHeader(DataGridHeader $header)
    {
        $this->_header = $header;
    }

    /**
     * @return DataGridData
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param DataGridData $data
     */
    public function setData(DataGridData $data)
    {
        $this->_data = $data;
    }

    /**
     * @param DataGridAction $action
     */
    public function setDataActions(DataGridAction $action)
    {
        if (is_null($this->_actions)) {
            $this->_actions = new SplObjectStorage();
        }

        $this->_actions->attach($action);
    }

    /**
     * @return DataGridAction[]
     */
    public function getDataActions()
    {
        return $this->_actions;
    }

    /**
     * @return mixed
     */
    public function getGrid()
    {
        // TODO: Implement getGrid() method.
    }
}