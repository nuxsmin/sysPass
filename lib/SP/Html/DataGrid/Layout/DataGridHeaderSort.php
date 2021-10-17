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

namespace SP\Html\DataGrid\Layout;

defined('APP_ROOT') || die();

use SP\Html\DataGrid\Action\DataGridActionInterface;
use SP\Html\DataGrid\DataGridSortInterface;
use SplObjectStorage;

/**
 * Class DataGridHeaderActions para añadir cabeceras para ordenar resultados
 *
 * @package SP\Html\DataGrid
 */
final class DataGridHeaderSort extends DataGridHeaderBase
{
    /**
     * @var SplObjectStorage|DataGridActionInterface[]|null
     */
    private ?SplObjectStorage $actions = null;

    /**
     * @var SplObjectStorage|DataGridSortInterface[]|null
     */
    private ?SplObjectStorage $sortFields = null;

    /**
     * @return DataGridSortInterface[]|null
     */
    public function getSortFields(): ?object
    {
        return $this->sortFields;
    }

    /**
     * @return DataGridActionInterface[]|null
     */
    public function getActions(): ?object
    {
        return $this->actions;
    }

    /**
     * @param DataGridActionInterface $action
     */
    public function addAction(DataGridActionInterface $action): void
    {
        if (null === $this->actions) {
            $this->actions = new SplObjectStorage();
        }

        $this->actions->attach($action);
    }

    /**
     * @param DataGridSortInterface $field
     *
     * @return $this
     */
    public function addSortField(DataGridSortInterface $field): DataGridHeaderSort
    {
        if (null === $this->sortFields) {
            $this->sortFields = new SplObjectStorage();
        }

        $this->sortFields->attach($field);

        return $this;
    }
}