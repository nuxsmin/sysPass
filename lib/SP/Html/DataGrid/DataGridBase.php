<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Html\DataGrid\Action\DataGridActionInterface;
use SP\Html\DataGrid\Layout\DataGridHeaderInterface;
use SP\Html\DataGrid\Layout\DataGridPagerBase;
use SP\Html\DataGrid\Layout\DataGridPagerInterface;

use function SP\__;
use function SP\logger;
use function SP\processException;

/**
 * Class DataGridBase para crear una matriz de datos
 *
 * @package SP\Html\DataGrid
 */
abstract class DataGridBase implements DataGridInterface
{
    /**
     * Tiempo de ejecución
     */
    protected int $time = 0;
    /**
     * El id de la matriz
     */
    protected string $id = '';
    /**
     * La cabecera de la matriz
     */
    protected ?DataGridHeaderInterface $header = null;
    /**
     * Los datos de la matriz
     */
    protected ?DataGridData $data = null;
    protected ?DataGridPagerBase $pager = null;
    /**
     * Las acciones asociadas a los elementos de la matriz
     *
     * @var DataGridActionInterface[]
     */
    protected array $actions      = [];
    protected int   $actionsCount = 0;
    /**
     * Las acciones asociadas a los elementos de la matriz que se muestran en un menú
     *
     * @var DataGridActionInterface[]
     */
    protected array $actionsMenu      = [];
    protected int   $actionsMenuCount = 0;
    /**
     * La acción a realizar al cerrar la matriz
     */
    protected int $onCloseAction = 0;
    /**
     * La plantilla a utilizar para presentar la cabecera
     */
    protected ?string $headerTemplate = null;
    /**
     * La plantilla a utilizar para presentar las acciones
     */
    protected ?string $actionsTemplate = null;
    /**
     * La plantilla a utilizar para presentar el paginador
     */
    protected ?string $pagerTemplate = null;
    /**
     * La plantilla a utilizar para presentar los datos
     */
    protected ?string $rowsTemplate = null;
    /**
     * La plantilla a utilizar para presentar la tabla
     */
    protected ?string         $tableTemplate = null;
    protected ?ThemeInterface $theme         = null;

    /**
     * DataGridBase constructor.
     *
     * @param ThemeInterface $theme
     */
    public function __construct(ThemeInterface $theme)
    {
        $this->theme = $theme;
    }

    public function getOnCloseAction(): int
    {
        return $this->onCloseAction;
    }

    public function setOnCloseAction(int $action): DataGridBase
    {
        $this->onCloseAction = $action;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): DataGridBase
    {
        $this->id = $id;

        return $this;
    }

    public function getHeader(): DataGridHeaderInterface
    {
        return $this->header;
    }

    public function setHeader(DataGridHeaderInterface $header): DataGridBase
    {
        $this->header = $header;

        return $this;
    }

    public function getData(): DataGridDataInterface
    {
        return $this->data;
    }

    public function setData(DataGridDataInterface $data): DataGridBase
    {
        $this->data = $data;

        return $this;
    }

    public function addDataAction(DataGridActionInterface $action, bool $isMenu = false): DataGridInterface
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

    public function getGrid(): DataGridInterface
    {
        return $this;
    }

    /**
     * Establecer la plantilla utilizada para la cabecera
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
     * @throws FileNotFoundException
     */
    protected function checkTemplate(string $template, ?string $base = null): string
    {
        $template = null === $base
            ? $template . '.inc'
            : $base . DIRECTORY_SEPARATOR . $template . '.inc';

        $file = $this->theme->getViewsPath() . DIRECTORY_SEPARATOR . $template;

        if (!is_readable($file)) {
            throw new FileNotFoundException(sprintf(__('Unable to retrieve "%s" template: %s'), $template, $file));
        }

        return $file;
    }

    /**
     * Devolver la plantilla utilizada para la cabecera
     */
    public function getDataHeaderTemplate(): string
    {
        return $this->headerTemplate;
    }

    /**
     * Establecer la plantilla utilizada para las acciones
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
     */
    public function getDataActionsTemplate(): ?string
    {
        return $this->actionsTemplate;
    }

    /**
     * Establecer la plantilla utilizada para el paginador
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
     */
    public function getDataPagerTemplate(): ?string
    {
        return $this->pagerTemplate;
    }

    public function setDataRowTemplate(string $template, ?string $base = null): DataGridBase
    {
        try {
            $this->rowsTemplate = $this->checkTemplate($template, $base);
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return $this;
    }

    public function getDataRowTemplate(): ?string
    {
        return $this->rowsTemplate;
    }

    /**
     * Devolver el paginador
     */
    public function getPager(): ?DataGridPagerInterface
    {
        return $this->pager;
    }

    /**
     * Establecer el paginador
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

    public function getTime(): int
    {
        return abs($this->time);
    }

    public function setTime(int|float $time): DataGridInterface
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
     * @return DataGridActionInterface[]
     */
    public function getDataActionsFiltered(mixed $filter): array
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
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenuFiltered(mixed $filter): array
    {
        $actions = [];

        foreach ($this->actionsMenu as $action) {
            if ($action->getRuntimeFilter()($filter)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    public function getDataTableTemplate(): ?string
    {
        return $this->tableTemplate;
    }

    public function getDataActionsMenuCount(): int
    {
        return $this->actionsMenuCount;
    }

    public function getDataActionsCount(): int
    {
        return $this->actionsCount;
    }
}
