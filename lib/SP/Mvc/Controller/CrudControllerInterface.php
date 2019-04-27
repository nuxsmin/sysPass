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

namespace SP\Mvc\Controller;

/**
 * Interface CrudControllerInterface
 *
 * @package SP\Mvc\Controller
 */
interface CrudControllerInterface
{
    /**
     * View action
     *
     * @param $id
     */
    public function viewAction($id);

    /**
     * Search action
     */
    public function searchAction();

    /**
     * Create action
     */
    public function createAction();

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id);

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id = null);

    /**
     * Saves create action
     */
    public function saveCreateAction();

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id);
}