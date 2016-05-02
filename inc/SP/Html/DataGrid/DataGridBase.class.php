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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * Tiempo de ejecución
     *
     * @var int
     */
    protected $_time = 0;
    /**
     * El id de la matriz
     *
     * @var string
     */
    private $_id = '';
    /**
     * La cabecera de la matriz
     *
     * @var DataGridHeaderInterface
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
     * @var DataGridActionInterface[]
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
     * @return $this
     */
    public function setOnCloseAction(ActionsInterface $action)
    {
        $this->_onCloseAction = $action;

        return $this;
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
     * @return $this
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * @return DataGridHeader|DataGridHeaderSort
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * @param DataGridHeaderInterface $header
     * @return $this
     */
    public function setHeader(DataGridHeaderInterface $header)
    {
        $this->_header = $header;

        return $this;
    }

    /**
     * @return DataGridDataInterface
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param DataGridDataInterface $data
     * @return $this
     */
    public function setData(DataGridDataInterface $data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * @param DataGridActionInterface $action
     * @return $this
     */
    public function setDataActions(DataGridActionInterface $action)
    {
        if (is_null($this->_actions)) {
            $this->_actions = new SplObjectStorage();
        }

        $this->_actions->attach($action);

        return $this;
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
     * @return $this
     */
    public function setDataHeaderTemplate($template)
    {
        $this->_headerTemplate = $this->checkTemplate($template);

        return $this;
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
     * @return $this
     */
    public function setDataActionsTemplate($template)
    {
        $this->_actionsTemplate = $this->checkTemplate($template);

        return $this;
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
     * @return $this
     */
    public function setDataPagerTemplate($template)
    {
        $this->_pagerTemplate = $this->checkTemplate($template);

        return $this;
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

        return $this;
    }

    /**
     * @return string
     */
    public function getDataRowTemplate()
    {
        return $this->_rowsTemplate;
    }

    /**
     * Devolver el paginador
     *
     * @return DataGridPagerInterface
     */
    public function getPager()
    {
        return $this->_pager;
    }

    /**
     * Establecer el paginador
     *
     * @param DataGridPagerInterface $pager
     * @return $this
     */
    public function setPager(DataGridPagerInterface $pager)
    {
        $this->_pager = $pager;

        return $this;
    }

    /**
     * Actualizar los datos del paginador
     */
    public function updatePager()
    {
        if ($this->_pager instanceof DataGridPagerInterface) {
            $this->_pager->setTotalRows($this->_data->getDataCount());
        }
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return abs($this->_time);
    }

    /**
     * @param int $time
     * @return $this
     */
    public function setTime($time)
    {
        $this->_time = $time;

        return $this;
    }
}