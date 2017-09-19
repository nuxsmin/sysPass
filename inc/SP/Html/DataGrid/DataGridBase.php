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

namespace SP\Html\DataGrid;

defined('APP_ROOT') || die();

use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
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
    protected $_id = '';
    /**
     * La cabecera de la matriz
     *
     * @var DataGridHeaderInterface
     */
    protected $_header;
    /**
     * Los datos de la matriz
     *
     * @var DataGridData
     */
    protected $_data;
    /**
     * El paginador
     *
     * @var DataGridPagerBase
     */
    protected $_pager;
    /**
     * Las acciones asociadas a los elementos de la matriz
     *
     * @var DataGridActionInterface[]
     */
    protected $_actions;
    /**
     * Las acciones asociadas a los elementos de la matriz que se muestran en un menú
     *
     * @var DataGridActionInterface[]
     */
    protected $_actionsMenu;
    /**
     * La acción a realizar al cerrar la matriz
     *
     * @var int
     */
    protected $_onCloseAction = 0;
    /**
     * La plantilla a utilizar para presentar la cabecera
     *
     * @var string
     */
    protected $_headerTemplate;
    /**
     * La plantilla a utilizar para presentar las acciones
     *
     * @var string
     */
    protected $_actionsTemplate;
    /**
     * La plantilla a utilizar para presentar el paginador
     *
     * @var string
     */
    protected $_pagerTemplate;
    /**
     * La plantilla a utilizar para presentar los datos
     *
     * @var string
     */
    protected $_rowsTemplate;

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
     * @param bool $isMenu Añadir al menu de acciones
     * @return $this
     */
    public function setDataActions(DataGridActionInterface $action, $isMenu = false)
    {
        if ($isMenu === false) {
            if (null === $this->_actions) {
                $this->_actions = new SplObjectStorage();
            }

            $this->_actions->attach($action);
        } else {
            if (null === $this->_actionsMenu) {
                $this->_actionsMenu = new SplObjectStorage();
            }

            $this->_actionsMenu->attach($action);
        }

        return $this;
    }

    /**
     * @return DataGridActionInterface[]
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
     * @param string $base Directorio base para la plantilla
     * @return $this
     */
    public function setDataHeaderTemplate($template, $base = null)
    {
        try {
            $this->_headerTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage());
        }

        return $this;
    }

    /**
     * Comprobar si existe una plantilla y devolver la ruta completa
     *
     * @param      $template
     * @param null $base
     * @return string
     * @throws FileNotFoundException
     */
    protected function checkTemplate($template, $base = null)
    {
        $template = null === $base ? $template . '.inc' : $base . DIRECTORY_SEPARATOR . $template . '.inc';
        $file = DiFactory::getTheme()->getViewsPath() . DIRECTORY_SEPARATOR . $template;

        if (!is_readable($file)) {
            throw new FileNotFoundException(SPException::SP_ERROR, sprintf(__('No es posible obtener la plantilla "%s" : %s'), $template, $file));
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
        try {
            $this->_actionsTemplate = $this->checkTemplate($template);
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage());
        }

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
     * @param string $base Directorio base para la plantilla
     * @return $this
     */
    public function setDataPagerTemplate($template, $base = null)
    {
        try {
            $this->_pagerTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage());
        }

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
     * @param string $base Directorio base para la plantilla
     * @return mixed
     */
    public function setDataRowTemplate($template, $base = null)
    {
        try {
            $this->_rowsTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            debugLog($e->getMessage());
        }

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

    /**
     * Devolver las acciones que se muestran en un menu
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenu()
    {
        return $this->_actionsMenu;
    }

    /**
     * Devolver las acciones filtradas
     *
     * @param $filter
     * @return DataGridActionInterface[]
     */
    public function getDataActionsFiltered($filter)
    {
        $actions = [];

        foreach ($this->_actions as $action) {
            if ($action->getReflectionFilter()->invoke($filter)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Devolver las acciones de menu filtradas
     *
     * @param $filter
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenuFiltered($filter)
    {
        $actions = [];

        foreach ($this->_actionsMenu as $action) {
            if ($action->getReflectionFilter()->invoke($filter)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }
}