<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Api;

use SP\Account\Account;
use SP\Account\AccountData;
use SP\Account\AccountSearch;
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\SPException;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Api para la gestión de peticiones a la API de sysPass
 *
 * @package SP
 */
class SyspassApi extends ApiBase
{
    /**
     * @var array
     */
    protected $actionsMap = array(
        'getAccountPassword' => ActionsInterface::ACTION_ACC_VIEW_PASS,
        'getAccountSearch' => ActionsInterface::ACTION_ACC_SEARCH,
        'getAccountData' => ActionsInterface::ACTION_ACC_VIEW
    );

    /**
     * Devolver la clave de una cuenta
     *
     * @return string
     * @throws SPException
     */
    public function getAccountPassword()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW_PASS);

        if (!isset($this->params->accountId)){
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $accountId = intval($this->params->accountId);

        $AccountData = new AccountData($accountId);
        $Account = new Account($AccountData);
        $Account->getAccountPassData();
        $Account->incrementDecryptCounter();

        $ret = array(
            'accountId' => $accountId,
            'pass' => Crypt::getDecrypt($AccountData->getAccountPass(), $AccountData->getAccountIV(), $this->_mPass)
        );

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver los resultados de una búsqueda
     *
     * @return string
     * @throws SPException
     */
    public function getAccountSearch()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_SEARCH);

        if (!isset($this->params->searchText)){
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $count = (isset($this->params->searchCount)) ? intval($this->params->searchCount) : 0;

        $Search = new AccountSearch();
        $Search->setTxtSearch($this->params->searchText);
        $Search->setLimitCount($count);

        $ret = $Search->getAccounts();

        return $this->wrapJSON(array($this->params, $ret));
    }

    /**
     * Devolver la clave de una cuenta
     *
     * @return string
     * @throws SPException
     */
    public function getAccountData()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW);

        if (!isset($this->params->accountId)){
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $accountId = intval($this->params->accountId);

        $Account = new Account(new AccountData($accountId));
        $ret = $Account->getData();
        $Account->incrementViewCounter();

        return $this->wrapJSON($ret);
    }
}