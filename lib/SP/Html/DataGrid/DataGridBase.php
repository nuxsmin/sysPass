<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\UI\ThemeInterface;
use SP\Html\DataGrid\Action\DataGridActionInterface;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Html\DataGrid\Layout\DataGridHeaderInterface;
use SP\Html\DataGrid\Layout\DataGridHeaderSort;
use SP\Html\DataGrid\Layout\DataGridPagerBase;
use SP\Html\DataGrid\Layout\DataGridPagerInterface;

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
    protected $time = 0;
    /**
     * El id de la matriz
     *
     * @var string
     */
    protected $id = '';
    /**
     * La cabecera de la matriz
     *
     * @var DataGridHeaderInterface
     */
    protected $header;
    /**
     * Los datos de la matriz
     *
     * @var DataGridData
     */
    protected $data;
    /**
     * El paginador
     *
     * @var DataGridPagerBase
     */
    protected $pager;
    /**
     * Las acciones asociadas a los elementos de la matriz
     *
     * @var DataGridActionInterface[]
     */
    protected $actions = [];
    /**
     * @var int
     */
    protected $actionsCount = 0;
    /**
     * Las acciones asociadas a los elementos de la matriz que se muestran en un menú
     *
     * @var DataGridActionInterface[]
     */
    protected $actionsMenu = [];
    /**
     * @var int
     */
    protected $actionsMenuCount = 0;
    /**
     * La acción a realizar al cerrar la matriz
     *
     * @var int
     */
    protected $onCloseAction = 0;
    /**
     * La plantilla a utilizar para presentar la cabecera
     *
     * @var string
     */
    protected $headerTemplate;
    /**
     * La plantilla a utilizar para presentar las acciones
     *
     * @var string
     */
    protected $actionsTemplate;
    /**
     * La plantilla a utilizar para presentar el paginador
     *
     * @var string
     */
    protected $pagerTemplate;
    /**
     * La plantilla a utilizar para presentar los datos
     *
     * @var string
     */
    protected $rowsTemplate;
    /**
     * La plantilla a utilizar para presentar la tabla
     *
     * @var string
     */
    protected $tableTemplate;
    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * DataGridBase constructor.
     *
     * @param ThemeInterface $theme
     */
    public function __construct(ThemeInterface $theme)
    {
        $this->theme = $theme;
    }

    /**
     * @return int
     */
    public function getOnCloseAction()
    {
        return $this->onCloseAction;
    }

    /**
     * @param ActionsInterface $action
     *
     * @return $this
     */
    public function setOnCloseAction(ActionsInterface $action)
    {
        $this->onCloseAction = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id string
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DataGridHeader|DataGridHeaderSort|DataGridHeaderInterface
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param DataGridHeaderInterface $header
     *
     * @return $this
     */
    public function setHeader(DataGridHeaderInterface $header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return DataGridDataInterface
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param DataGridDataInterface $data
     *
     * @return $this
     */
    public function setData(DataGridDataInterface $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param DataGridActionInterface $action
     * @param bool                    $isMenu Añadir al menu de acciones
     *
     * @return $this
     */
    public function addDataAction(DataGridActionInterface $action, $isMenu = false)
    {
        if ($isMenu === false) {
            $this->actions[] = $action;

            if (!$action->isSkip()) {
                $this->actionsCount++;
            }
        } else {
            $this->actionsMenu[] = $action;

            if (!$action->isSkip()) {
                $this->actionsMenuCount++;
            }
        }

        return $this;
    }

    /**
     * @return DataGridActionInterface[]
     */
    public function getDataActions()
    {
        return $this->actions;
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
     * @param string $base     Directorio base para la plantilla
     *
     * @return $this
     */
    public function setDataHeaderTemplate($template, $base = null)
    {
        try {
            $this->headerTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * Comprobar si existe una plantilla y devolver la ruta completa
     *
     * @param      $template
     * @param null $base
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function checkTemplate($template, $base = null)
    {
        $template = null === $base ? $template . '.inc' : $base . DIRECTORY_SEPARATOR . $template . '.inc';
        $file = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $template;

        if (!is_readable($file)) {
            throw new FileNotFoundException(sprintf(__('Unable to retrieve "%s" template: %s'), $template, $file));
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
        return $this->headerTemplate;
    }

    /**
     * Establecer la plantilla utilizada para las acciones
     *
     * @param string $template El nombre de la plantilla a utilizar
     *
     * @return $this
     */
    public function setDataActionsTemplate($template)
    {
        try {
            $this->actionsTemplate = $this->checkTemplate($template);
        } catch (FileNotFoundException $e) {
            logger($e->getMessage());
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
        return $this->actionsTemplate;
    }

    /**
     * Establecer la plantilla utilizada para el paginador
     *
     * @param string $template El nombre de la plantilla a utilizar
     * @param string $base     Directorio base para la plantilla
     *
     * @return $this
     */
    public function setDataPagerTemplate($template, $base = null)
    {
        try {
            $this->pagerTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            logger($e->getMessage());
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
        return $this->pagerTemplate;
    }

    /**
     * @param string $template El nombre de la plantilla a utilizar
     * @param string $base     Directorio base para la plantilla
     *
     * @return mixed
     */
    public function setDataRowTemplate($template, $base = null)
    {
        try {
            $this->rowsTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDataRowTemplate()
    {
        return $this->rowsTemplate;
    }

    /**
     * Devolver el paginador
     *
     * @return DataGridPagerInterface
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * Establecer el paginador
     *
     * @param DataGridPagerInterface $pager
     *
     * @return $this
     */
    public function setPager(DataGridPagerInterface $pager)
    {
        $this->pager = $pager;

        return $this;
    }

    /**
     * Actualizar los datos del paginador
     */
    public function updatePager()
    {
        if ($this->pager instanceof DataGridPagerInterface) {
            $this->pager->setTotalRows($this->data->getDataCount());
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return abs($this->time);
    }

    /**
     * @param int $time
     *
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Devolver las acciones que se muestran en un menu
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenu()
    {
        return $this->actionsMenu;
    }

    /**
     * Devolver las acciones filtradas
     *
     * @param $filter
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsFiltered($filter)
    {
        $actions = [];

        foreach ($this->actions as $action) {
            if ($action->getRuntimeFilter()($filter)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Devolver las acciones de menu filtradas
     *
     * @param $filter
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenuFiltered($filter)
    {
        $actions = [];

        foreach ($this->actionsMenu as $action) {
            if ($action->getRuntimeFilter()($filter)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * @return string
     */
    public function getDataTableTemplate()
    {
        return $this->tableTemplate;
    }

    /**
     * @param      $template
     * @param null $base
     *
     * @return DataGridBase
     */
    public function setDataTableTemplate($template, $base = null)
    {
        try {
            $this->tableTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getDataActionsMenuCount()
    {
        return $this->actionsMenuCount;
    }

    /**
     * @return int
     */
    public function getDataActionsCount()
    {
        return $this->actionsCount;
    }
}