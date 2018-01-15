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
use SP\DataModel\ClientData;
use SP\Http\Request;

/**
 * Class ClientForm
 *
 * @package SP\Forms
 */
class ClientForm extends FormBase implements FormInterface
{
    /**
     * @var ClientData
     */
    protected $clientData;

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
            case ActionsInterface::CLIENT_CREATE:
            case ActionsInterface::CLIENT_EDIT:
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
        $this->clientData = new ClientData();
        $this->clientData->setId($this->itemId);
        $this->clientData->setName(Request::analyze('name'));
        $this->clientData->setDescription(Request::analyze('description'));
        $this->clientData->setIsGlobal(Request::analyze('isglobal', 0, false, 1));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if (!$this->clientData->getName()) {
            throw new ValidationException(__u('Es necesario un nombre de cliente'));
        }
    }

    /**
     * @return ClientData
     */
    public function getItemData()
    {
        return $this->clientData;
    }
}