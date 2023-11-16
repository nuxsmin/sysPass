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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\AclActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\UserGroupData;

/**
 * Class UserGroupForm
 *
 * @package SP\Modules\Web\Forms
 */
final class UserGroupForm extends FormBase implements FormInterface
{
    protected ?UserGroupData $groupData = null;

    /**
     * Validar el formulario
     *
     * @param  int  $action
     * @param  int|null  $id
     *
     * @return UserGroupForm|FormInterface
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::GROUP_CREATE:
            case AclActionsInterface::GROUP_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
        }

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData(): void
    {
        $this->groupData = new UserGroupData();
        $this->groupData->setId($this->itemId);
        $this->groupData->setName($this->request->analyzeString('name'));
        $this->groupData->setDescription($this->request->analyzeString('description'));
        $this->groupData->setUsers($this->request->analyzeArray('users', null, []));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon(): void
    {
        if (!$this->groupData->getName()) {
            throw new ValidationException(__u('A group name is needed'));
        }
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getItemData(): UserGroupData
    {
        if (null === $this->groupData) {
            throw new SPException(__u('Group data not set'));
        }

        return $this->groupData;
    }
}
