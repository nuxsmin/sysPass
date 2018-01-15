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
use SP\DataModel\ApiTokenData;
use SP\Http\Request;

/**
 * Class ApiTokenForm
 *
 * @package SP\Forms
 */
class ApiTokenForm extends FormBase implements FormInterface
{
    /**
     * @var ApiTokenData
     */
    protected $apiTokenData;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return ApiTokenForm
     * @throws \SP\Core\Exceptions\ValidationException
     */
    public function validate($action)
    {
        switch ($action) {
            case ActionsInterface::APITOKEN_CREATE:
            case ActionsInterface::APITOKEN_EDIT:
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
        $this->apiTokenData = new ApiTokenData();
        $this->apiTokenData->setId($this->itemId);
        $this->apiTokenData->setUserId(Request::analyze('users', 0));
        $this->apiTokenData->setActionId(Request::analyze('actions', 0));
        $this->apiTokenData->setHash(Request::analyzeEncrypted('pass'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if ($this->apiTokenData->getUserId() === 0) {
            throw new ValidationException(__u('Usuario no indicado'));
        }

        if ($this->apiTokenData->getActionId() === 0) {
            throw new ValidationException(__u('Acción no indicada'));
        }

        $action = $this->apiTokenData->getActionId();

        if (($action === ActionsInterface::ACCOUNT_VIEW_PASS
                || $action === ActionsInterface::ACCOUNT_CREATE)
            && $this->apiTokenData->getHash() === ''
        ) {
            throw new ValidationException(__u('La clave no puede estar en blanco'));
        }
    }

    /**
     * @return ApiTokenData
     */
    public function getItemData()
    {
        return $this->apiTokenData;
    }
}