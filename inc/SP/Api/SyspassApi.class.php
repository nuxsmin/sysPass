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
use SP\DataModel\AccountData;
use SP\Account\AccountSearch;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\Exceptions\SPException;

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
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountPassword()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW_PASS);

        if (!isset($this->params->accountId)) {
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $AccountData = new AccountData($this->params->accountId);
        $Account = new Account($AccountData);
        $Account->getData();

        $Acl = new Acl(ActionsInterface::ACTION_ACC_VIEW_PASS);
        $Acl->setAccountData($Account->getAccountDataForACL());

        $access = ($Acl->checkAccountAccess()
            && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW_PASS));

        if (!$access){
            throw new SPException(SPException::SP_WARNING, _('Acceso no permitido'));
        }

        $Account->getAccountPassData();
        $Account->incrementDecryptCounter();

        $ret = [
            'accountId' => $AccountData->getAccountId(),
            'pass' => Crypt::getDecrypt($AccountData->getAccountPass(), $AccountData->getAccountIV(), $this->mPass)
        ];

        if (isset($this->params->details)) {
            $ret['details'] = $AccountData;
        }

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver los resultados de una búsqueda
     *
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountSearch()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_SEARCH);

        if (!isset($this->params->searchText)) {
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $count = (isset($this->params->searchCount)) ? (int)$this->params->searchCount : 0;

        $Search = new AccountSearch();
        $Search->setTxtSearch($this->params->searchText);
        $Search->setLimitCount($count);

        $ret = array($this->params, $Search->getAccounts());

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver los detalles de una cuenta
     *
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountData()
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW);

        if (!isset($this->params->accountId)) {
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $Account = new Account(new AccountData($this->params->accountId));
        $Acl = new Acl(ActionsInterface::ACTION_ACC_VIEW);
        $Acl->setAccountData($Account->getAccountDataForACL());

        $access = ($Acl->checkAccountAccess()
            && Acl::checkUserAccess(ActionsInterface::ACTION_ACC_VIEW));

        if (!$access){
            throw new SPException(SPException::SP_WARNING, _('Acceso no permitido'));
        }

        $ret = $Account->getData();
        $Account->incrementViewCounter();

        return $this->wrapJSON($ret);
    }
}