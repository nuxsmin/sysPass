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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Http\Request;

/**
 * Class CustomFieldDefForm
 *
 * @package SP\Forms
 */
class CustomFieldDefForm extends FormBase implements FormInterface
{
    /**
     * @var CustomFieldDefinitionData
     */
    protected $customFieldDefData;

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
            case ActionsInterface::CUSTOMFIELD_CREATE:
            case ActionsInterface::CUSTOMFIELD_EDIT:
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
        $this->customFieldDefData = new CustomFieldDefinitionData();
        $this->customFieldDefData->setId($this->itemId);
        $this->customFieldDefData->setName(Request::analyze('name'));
        $this->customFieldDefData->setTypeId(Request::analyze('type', 0));
        $this->customFieldDefData->setModuleId(Request::analyze('module', 0));
        $this->customFieldDefData->setHelp(Request::analyze('help'));
        $this->customFieldDefData->setRequired(Request::analyze('required', false, false, true));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->customFieldDefData->getName()) {
            throw new ValidationException(__u('Nombre del campo no indicado'));
        }

        if ($this->customFieldDefData->getTypeId() === 0) {
            throw new ValidationException(__u('Tipo del campo no indicado'));
        }

        if ($this->customFieldDefData->getModuleId() === 0) {
            throw new ValidationException(__u('Módulo del campo no indicado'));
        }
    }

    /**
     * @return CustomFieldDefinitionData
     */
    public function getItemData()
    {
        return $this->customFieldDefData;
    }
}