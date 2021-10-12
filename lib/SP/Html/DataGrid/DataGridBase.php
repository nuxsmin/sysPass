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

defined('APP_ROOT') || die();

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
    protected int $time = 0;
    /**
     * El id de la matriz
     *
     * @var string
     */
    protected string $id = '';
    /**
     * La cabecera de la matriz
     *
     * @var DataGridHeaderInterface|null
     */
    protected ?DataGridHeaderInterface $header = null;
    /**
     * Los datos de la matriz
     *
     * @var DataGridData|null
     */
    protected ?DataGridData $data = null;
    /**
     * El paginador
     *
     * @var DataGridPagerBase|null
     */
    protected ?DataGridPagerBase $pager = null;
    /**
     * Las acciones asociadas a los elementos de la matriz
     *
     * @var DataGridActionInterface[]
     */
    protected array $actions = [];
    /**
     * @var int
     */
    protected int $actionsCount = 0;
    /**
     * Las acciones asociadas a los elementos de la matriz que se muestran en un menú
     *
     * @var DataGridActionInterface[]
     */
    protected array $actionsMenu = [];
    /**
     * @var int
     */
    protected int $actionsMenuCount = 0;
    /**
     * La acción a realizar al cerrar la matriz
     *
     * @var int
     */
    protected int $onCloseAction = 0;
    /**
     * La plantilla a utilizar para presentar la cabecera
     *
     * @var string|null
     */
    protected ?string $headerTemplate = null;
    /**
     * La plantilla a utilizar para presentar las acciones
     *
     * @var string|null
     */
    protected ?string $actionsTemplate = null;
    /**
     * La plantilla a utilizar para presentar el paginador
     *
     * @var string|null
     */
    protected ?string $pagerTemplate = null;
    /**
     * La plantilla a utilizar para presentar los datos
     *
     * @var string|null
     */
    protected ?string $rowsTemplate = null;
    /**
     * La plantilla a utilizar para presentar la tabla
     *
     * @var string|null
     */
    protected ?string $tableTemplate = null;
    protected ?ThemeInterface $theme = null;

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
     * @param int $action
     *
     * @return $this
     */
    public function setOnCloseAction(int $action): DataGridBase
    {
        $this->onCloseAction = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param $id string
     *
     * @return $this
     */
    public function setId(string $id): DataGridBase
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DataGridHeader|DataGridHeaderSort|DataGridHeaderInterface
     */
    public function getHeader(): DataGridHeaderInterface
    {
        return $this->header;
    }

    /**
     * @param DataGridHeaderInterface $header
     *
     * @return $this
     */
    public function setHeader(DataGridHeaderInterface $header): DataGridBase
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
    public function setData(DataGridDataInterface $data): DataGridBase
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
    public function addDataAction(DataGridActionInterface $action, $isMenu = false): DataGridInterface
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
    public function getDataActions(): array
    {
        return $this->actions;
    }

    /**
     * @return $this
     */
    public function getGrid(): DataGridInterface
    {
        return $this;
    }

    /**
     * Establecer la plantilla utilizada para la cabecera
     *
     * @param string $template El nombre de la plantilla a utilizar
     *
     * @return $this
     */
    public function setDataHeaderTemplate(string $template): DataGridBase
    {
        try {
            $this->headerTemplate = $this->checkTemplate($template);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * Comprobar si existe una plantilla y devolver la ruta completa
     *
     * @param string      $template
     * @param string|null $base
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function checkTemplate(string $template, ?string $base = null): string
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
    public function getDataHeaderTemplate(): string
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
    public function setDataActionsTemplate(string $template): DataGridBase
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
     * @return string|null
     */
    public function getDataActionsTemplate(): ?string
    {
        return $this->actionsTemplate;
    }

    /**
     * Establecer la plantilla utilizada para el paginador
     *
     * @param string      $template El nombre de la plantilla a utilizar
     * @param string|null $base
     *
     * @return $this
     */
    public function setDataPagerTemplate(string $template, ?string $base = null): DataGridBase
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
     * @return string|null
     */
    public function getDataPagerTemplate(): ?string
    {
        return $this->pagerTemplate;
    }

    /**
     * @param string      $template El nombre de la plantilla a utilizar
     * @param string|null $base
     *
     * @return \SP\Html\DataGrid\DataGridBase
     */
    public function setDataRowTemplate(string $template, ?string $base = null): DataGridBase
    {
        try {
            $this->rowsTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDataRowTemplate(): ?string
    {
        return $this->rowsTemplate;
    }

    /**
     * Devolver el paginador
     *
     * @return DataGridPagerInterface|null
     */
    public function getPager(): ?DataGridPagerInterface
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
    public function setPager(DataGridPagerInterface $pager): DataGridBase
    {
        $this->pager = $pager;

        return $this;
    }

    /**
     * Actualizar los datos del paginador
     */
    public function updatePager(): DataGridInterface
    {
        if ($this->pager instanceof DataGridPagerInterface) {
            $this->pager->setTotalRows($this->data->getDataCount());
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return abs($this->time);
    }

    /**
     * @param int|float $time
     *
     * @return $this
     */
    public function setTime($time): DataGridInterface
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Devolver las acciones que se muestran en un menu
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenu(): array
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
    public function getDataActionsFiltered($filter): array
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
    public function getDataActionsMenuFiltered($filter): array
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
     * @return string|null
     */
    public function getDataTableTemplate(): ?string
    {
        return $this->tableTemplate;
    }

    /**
     * @param      $template
     * @param null $base
     *
     * @return DataGridBase
     */
    public function setDataTableTemplate($template, $base = null): DataGridBase
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
    public function getDataActionsMenuCount(): int
    {
        return $this->actionsMenuCount;
    }

    /**
     * @return int
     */
    public function getDataActionsCount(): int
    {
        return $this->actionsCount;
    }
}