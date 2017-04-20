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
use SP\DataModel\CustomFieldDefData;
use SP\Http\Request;

/**
 * Class CustomFieldDefForm
 *
 * @package SP\Forms
 */
class CustomFieldDefForm extends FormBase implements FormInterface
{
    /**
     * @var CustomFieldDefData
     */
    protected $CustomFieldDefData;

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
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW:
            case ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT:
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
        $this->CustomFieldDefData = new CustomFieldDefData();
        $this->CustomFieldDefData->setCustomfielddefId($this->itemId);
        $this->CustomFieldDefData->setId($this->itemId);
        $this->CustomFieldDefData->setName(Request::analyze('name'));
        $this->CustomFieldDefData->setType(Request::analyze('type', 0));
        $this->CustomFieldDefData->setModule(Request::analyze('module', 0));
        $this->CustomFieldDefData->setHelp(Request::analyze('help'));
        $this->CustomFieldDefData->setRequired(Request::analyze('required', false, false, true));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->CustomFieldDefData->getName()) {
            throw new ValidationException(__('Nombre del campo no indicado', false));
        } elseif ($this->CustomFieldDefData->getType() === 0) {
            throw new ValidationException(__('Tipo del campo no indicado', false));
        } elseif ($this->CustomFieldDefData->getModule() === 0) {
            throw new ValidationException(__('Módulo del campo no indicado', false));
        }
    }

    /**
     * @return CustomFieldDefData
     */
    public function getItemData()
    {
        return $this->CustomFieldDefData;
    }
}