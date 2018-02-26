<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\AuthTokenData;
use SP\Http\Request;

/**
 * Class ApiTokenForm
 *
 * @package SP\Forms
 */
class AuthTokenForm extends FormBase implements FormInterface
{
    /**
     * @var AuthTokenData
     */
    protected $authTokenData;
    /**
     * @var bool
     */
    protected $refresh = false;

    /**
     * Validar el formulario
     *
     * @param $action
     * @return AuthTokenForm
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
        $this->refresh = (bool)Request::analyze('refreshtoken', 0, false, 1);

        $this->authTokenData = new AuthTokenData();
        $this->authTokenData->setId($this->itemId);
        $this->authTokenData->setUserId(Request::analyze('users', 0));
        $this->authTokenData->setActionId(Request::analyze('actions', 0));
        $this->authTokenData->setHash(Request::analyzeEncrypted('pass'));
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon()
    {
        if ($this->authTokenData->getUserId() === 0) {
            throw new ValidationException(__u('Usuario no indicado'));
        }

        if ($this->authTokenData->getActionId() === 0) {
            throw new ValidationException(__u('Acción no indicada'));
        }

        $action = $this->authTokenData->getActionId();

        if (($action === ActionsInterface::ACCOUNT_VIEW_PASS
                || $action === ActionsInterface::ACCOUNT_CREATE
                || $this->isRefresh())
            && $this->authTokenData->getHash() === ''
        ) {
            throw new ValidationException(__u('La clave no puede estar en blanco'));
        }
    }

    /**
     * @return bool
     */
    public function isRefresh()
    {
        return $this->refresh;
    }

    /**
     * @return AuthTokenData
     */
    public function getItemData()
    {
        return $this->authTokenData;
    }
}