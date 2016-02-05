<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

use SplObjectStorage;

/**
 * Class DataGridHeaderActions para añadir cabeceras para ordenar resultados
 *
 * @package SP\Html\DataGrid
 */
class DataGridHeaderSort extends DataGridHeaderBase
{
    /** @var DataGridActionInterface[] */
    private $_actions = null;

    /** @var DataGridSortInterface[] */
    private $_sortFields = null;

    /**
     * @return DataGridSortInterface[]
     */
    public function getSortFields()
    {
        return $this->_sortFields;
    }

    /**
     * @return DataGridActionInterface[]
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     * @param DataGridActionInterface[] $action
     */
    public function addAction($action)
    {
        if (is_null($this->_actions)) {
            $this->_actions = new SplObjectStorage();
        }

        $this->_actions->attach($action);
    }

    /**
     * @param DataGridSortInterface $field
     */
    public function addSortField($field)
    {
        if (is_null($this->_sortFields)) {
            $this->_sortFields = new SplObjectStorage();
        }

        $this->_sortFields->attach($field);
    }
}