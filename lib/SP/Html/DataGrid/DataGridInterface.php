<?php
declare(strict_types=1);
/**
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

use SP\Html\DataGrid\Action\DataGridActionInterface;
use SP\Html\DataGrid\Layout\DataGridHeaderInterface;
use SP\Html\DataGrid\Layout\DataGridPagerInterface;

/**
 * Interface DataGridInterface
 *
 * @package SP\Html\DataGrid
 */
interface DataGridInterface
{
    public function setId(string $id);

    public function getId(): string;

    public function setHeader(DataGridHeaderInterface $header);

    public function getHeader(): DataGridHeaderInterface;

    public function setData(DataGridDataInterface $data);

    public function getData(): DataGridDataInterface;

    public function addDataAction(DataGridActionInterface $action, bool $isMenu = false): DataGridInterface;

    /**
     * @return DataGridActionInterface[]
     */
    public function getDataActions(): array;

    public function getGrid(): DataGridInterface;

    public function setPager(DataGridPagerInterface $pager);

    public function getPager(): ?DataGridPagerInterface;

    public function setOnCloseAction(int $action);

    /**
     * Establecer la plantilla utilizada para la cabecera
     */
    public function setDataHeaderTemplate(string $template);

    /**
     * Devolver la plantilla utilizada para la cabecera
     */
    public function getDataHeaderTemplate(): string;

    /**
     * Establecer la plantilla utilizada para las acciones
     */
    public function setDataActionsTemplate(string $template);

    /**
     * Devolver la plantilla utilizada para las acciones
     */
    public function getDataActionsTemplate(): ?string;

    /**
     * Establecer la plantilla utilizada para el paginador
     */
    public function setDataPagerTemplate(string $template);

    /**
     * Devolver la plantilla utilizada para el paginador
     */
    public function getDataPagerTemplate(): ?string;

    /**
     * Establcer la plantilla utilizada para los datos de la consulta
     */
    public function setDataRowTemplate(string $template);

    /**
     * Devolver la plantilla utilizada para los datos de la consulta
     */
    public function getDataRowTemplate(): ?string;

    /**
     * Devuelve el tiempo total de carga del DataGrid
     */
    public function getTime(): int;

    /**
     * Establece el tiempo total de carga del DataGrid
     */
    public function setTime(int|float $time);

    /**
     * Devolver las acciones que se muestran en un menu
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenu(): array;

    /**
     * Devolver las acciones filtradas
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsFiltered(mixed $filter): array;

    /**
     * Devolver las acciones de menu filtradas
     *
     * @return DataGridActionInterface[]
     */
    public function getDataActionsMenuFiltered(mixed $filter): array;

    /**
     * Actualizar los datos del paginador
     */
    public function updatePager(): DataGridInterface;
}
