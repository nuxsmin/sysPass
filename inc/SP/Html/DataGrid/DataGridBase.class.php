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

use InvalidArgumentException;
use SP\Core\ActionsInterface;
use SP\Core\Themes;
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
     * El paginador
     *
     * @var DataGridPagerBase
     */
    private $_pager;
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
     * La pantilla a utilizar para presentar la cabecera
     *
     * @var string
     */
    private $_headerTemplate;
    /**
     * La pantilla a utilizar para presentar las acciones
     *
     * @var string
     */
    private $_actionsTemplate;
    /**
     * La pantilla a utilizar para presentar el paginador
     *
     * @var string
     */
    private $_pagerTemplate;
    /**
     * La pantilla a utilizar para presentar los datos
     *
     * @var string
     */
    private $_rowsTemplate;

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
     * @param DataGridActionBase $action
     */
    public function setDataActions(DataGridActionBase $action)
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
     * @return $this
     */
    public function getGrid()
    {
        return $this;
    }

    /**
     * Establecer la plantilla utilizada para la cabecera
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataHeaderTemplate($template)
    {
        $this->_headerTemplate = $this->checkTemplate($template);
    }

    /**
     * Comprobar si existe una plantilla y devolver la ruta completa
     *
     * @param $template
     * @return string
     */
    protected function checkTemplate($template)
    {
        $file = VIEW_PATH . DIRECTORY_SEPARATOR . Themes::$theme . DIRECTORY_SEPARATOR . $template . '.inc';

        if (!is_readable($file)) {
            throw new InvalidArgumentException(sprintf(_('No es posible obtener la plantilla "%s" : %s'), $template, $file));
        }

        return $file;
    }

    /**
     * Devolver la plantilla utilizada para la cabecera
     *
     * @return string
     */
    public function getDataHeaderTemplate()
    {
        return $this->_headerTemplate;
    }

    /**
     * Establecer la plantilla utilizada para las acciones
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataActionsTemplate($template)
    {
        $this->_actionsTemplate = $this->checkTemplate($template);
    }

    /**
     * Devolver la plantilla utilizada para las acciones
     *
     * @return string
     */
    public function getDataActionsTemplate()
    {
        return $this->_actionsTemplate;
    }

    /**
     * Establecer la plantilla utilizada para el paginador
     *
     * @param string $template El nombre de la plantilla a utilizar
     */
    public function setDataPagerTemplate($template)
    {
        $this->_pagerTemplate = $this->checkTemplate($template);
    }

    /**
     * Devolver la plantilla utilizada para el paginador
     *
     * @return string
     */
    public function getDataPagerTemplate()
    {
        return $this->_pagerTemplate;
    }

    /**
     * @param string $template El nombre de la plantilla a utilizar
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function setDataRowTemplate($template)
    {
        $this->_rowsTemplate = $this->checkTemplate($template);
    }

    /**
     * @return string
     */
    public function getDataRowTemplate()
    {
        return $this->_rowsTemplate;
    }

    /**
     * Establecer el paginador
     *
     * @param DataGridPagerBase $pager
     */
    public function setPager(DataGridPagerBase $pager)
    {
        $this->_pager = $pager;
    }

    /**
     * Devolver el paginador
     *
     * @return DataGridPagerBase
     */
    public function getPager()
    {
        return $this->_pager;
    }

}