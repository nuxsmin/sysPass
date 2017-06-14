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
use SP\DataModel\ApiTokenData;
use SP\Http\Request;
use SP\Mgmt\ApiTokens\ApiTokensUtil;

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
    protected $ApiTokenData;

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
            case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
            case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
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
        $this->ApiTokenData = new ApiTokenData();
        $this->ApiTokenData->setAuthtokenId($this->itemId);
        $this->ApiTokenData->setAuthtokenUserId(Request::analyze('users', 0));
        $this->ApiTokenData->setAuthtokenActionId(Request::analyze('actions', 0));
        $this->ApiTokenData->setAuthtokenHash(Request::analyzeEncrypted('pass'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if ($this->ApiTokenData->getAuthtokenUserId() === 0) {
            throw new ValidationException(__('Usuario no indicado', false));
        }

        if ($this->ApiTokenData->getAuthtokenActionId() === 0) {
            throw new ValidationException(__('Acción no indicada', false));
        }

        $action = $this->ApiTokenData->getAuthtokenActionId();

        if (($action === ActionsInterface::ACTION_ACC_VIEW_PASS
                || $action === ActionsInterface::ACTION_ACC_NEW)
            && $this->ApiTokenData->getAuthtokenHash() === ''
        ) {
            throw new ValidationException(__('La clave no puede estar en blanco', false));
        }
    }

    /**
     * @return ApiTokenData
     */
    public function getItemData()
    {
        return $this->ApiTokenData;
    }
}