<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use SP\Api\ApiTokens;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Http\Request;

/**
 * Class ApiTokenForm
 *
 * @package SP\Forms
 */
class ApiTokenForm extends FormBase implements FormInterface
{
    /**
     * @var ApiTokens
     */
    protected $ApiTokens;

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
            case ActionsInterface::ACTION_MGM_APITOKENS_NEW:
            case ActionsInterface::ACTION_MGM_APITOKENS_EDIT:
                $this->checkCommon();
                break;
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if ($this->ApiTokens->getUserId() === 0) {
            throw new ValidationException(__('Usuario no indicado', false));
        } elseif ($this->ApiTokens->getActionId() === 0) {
            throw new ValidationException(__('Acción no indicada', false));
        }
    }

    /**
     * @return ApiTokens
     */
    public function getItemData()
    {
        return $this->ApiTokens;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData()
    {
        $this->ApiTokens = new ApiTokens();
        $this->ApiTokens->setTokenId($this->itemId);
        $this->ApiTokens->setUserId(Request::analyze('users', 0));
        $this->ApiTokens->setActionId(Request::analyze('actions', 0));
        $this->ApiTokens->setRefreshToken(Request::analyze('refreshtoken', false, false, true));
    }
}