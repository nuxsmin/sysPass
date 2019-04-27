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

namespace SP\Modules\Web\Forms;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CustomFieldDefinitionData;

/**
 * Class CustomFieldDefForm
 *
 * @package SP\Modules\Web\Forms
 */
final class CustomFieldDefForm extends FormBase implements FormInterface
{
    /**
     * @var CustomFieldDefinitionData
     */
    protected $customFieldDefData;

    /**
     * Validar el formulario
     *
     * @param $action
     *
     * @return CustomFieldDefForm
     * @throws ValidationException
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

        return $this;
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
        $this->customFieldDefData->setName($this->request->analyzeString('name'));
        $this->customFieldDefData->setTypeId($this->request->analyzeInt('type'));
        $this->customFieldDefData->setModuleId($this->request->analyzeInt('module'));
        $this->customFieldDefData->setHelp($this->request->analyzeString('help'));
        $this->customFieldDefData->setRequired($this->request->analyzeBool('required', false));
        $this->customFieldDefData->setIsEncrypted($this->request->analyzeBool('encrypted', false));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->customFieldDefData->getName()) {
            throw new ValidationException(__u('Field name not set'));
        }

        if (0 === $this->customFieldDefData->getTypeId()) {
            throw new ValidationException(__u('Field type not set'));
        }

        if (0 === $this->customFieldDefData->getModuleId()) {
            throw new ValidationException(__u('Field module not set'));
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