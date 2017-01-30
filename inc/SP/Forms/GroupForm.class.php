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

namespace SP\Forms;

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\GroupData;
use SP\Http\Request;

/**
 * Class GroupForm
 *
 * @package SP\Forms
 */
class GroupForm extends FormBase implements FormInterface
{
    /**
     * @var GroupData
     */
    protected $GroupData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return bool
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::ACTION_USR_GROUPS_NEW:
            case ActionsInterface::ACTION_USR_GROUPS_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return true;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->GroupData = new GroupData();
        $this->GroupData->setUsergroupId($this->itemId);
        $this->GroupData->setUsergroupName(Request::analyze('name'));
        $this->GroupData->setUsergroupDescription(Request::analyze('description'));
        $this->GroupData->setUsers(Request::analyze('users', 0));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->GroupData->getUsergroupName()) {
            throw new ValidationException(__('Es necesario un nombre de grupo', false));
        }
    }

    /**
     * @return GroupData
     */
    public function getItemData()
    {
        return $this->GroupData;
    }
}