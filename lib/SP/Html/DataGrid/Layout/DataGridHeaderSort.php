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
     * @var DataGridActionInterface[]
     */
    private $actions;

    /**
     * @var DataGridSortInterface[]
     */
    private $sortFields;

    /**
     * @return DataGridSortInterface[]
     */
    public function getSortFields()
    {
        return $this->sortFields;
    }

    /**
     * @return DataGridActionInterface[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param DataGridActionInterface[] $action
     */
    public function addAction($action)
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
    public function addSortField($field)
    {
        if (null === $this->sortFields) {
            $this->sortFields = new SplObjectStorage();
        }

        $this->sortFields->attach($field);

        return $this;
    }
}