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
use SP\DataModel\CustomerData;
use SP\Http\Request;

/**
 * Class CustomerForm
 *
 * @package SP\Forms
 */
class CustomerForm extends FormBase implements FormInterface
{
    /**
     * @var CustomerData
     */
    protected $CustomerData;

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
            case ActionsInterface::ACTION_MGM_CUSTOMERS_NEW:
            case ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT:
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
        $this->CustomerData = new CustomerData();
        $this->CustomerData->setCustomerId($this->itemId);
        $this->CustomerData->setCustomerName(Request::analyze('name'));
        $this->CustomerData->setCustomerDescription(Request::analyze('description'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->CustomerData->getCustomerName()) {
            throw new ValidationException(__('Es necesario un nombre de cliente', false));
        }
    }

    /**
     * @return CustomerData
     */
    public function getItemData()
    {
        return $this->CustomerData;
    }
}