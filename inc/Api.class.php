<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.or
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

namespace SP;

use SP\Controller\ActionsInterface;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Api para la gestión de peticiones a la API de sysPass
 *
 * @package SP
 */
class Api
{
    /**
     * @var int
     */
    private $_userId = 0;
    /**
     * @var int
     */
    private $_actionId = 0;
    /**
     * @var bool
     */
    private $_auth = false;
    /**
     * @var string
     */
    private $_mPass = '';

    /**
     * @param      $userLogin string El login del usuario
     * @param      $actionId  int El id de la acción
     * @param      $authToken string El token de seguridad
     * @param null $userPass  string La clave del usuario
     * @throws SPException
     */
    public function __construct($userLogin, $actionId, $authToken, $userPass = null)
    {
        $this->_userId = UserUtil::getUserIdByLogin($userLogin);

        if (!Auth::checkAuthToken($this->_userId, $actionId, $authToken)) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $this->_actionId = $actionId;
        $this->_auth = true;

        if (!is_null($userPass)) {
            $User = new User();
            $User->setUserId($this->_userId);
            $User->setUserLogin($userLogin);
            $User->setUserPass($userPass);

            if (Auth::authUserMySQL($userLogin, $userPass)
                && !UserUtil::checkUserIsDisabled($userLogin)
                && UserUtil::checkUserMPass($User)
                && UserUtil::checkUserUpdateMPass($userLogin)
                && !$User->isUserChangePass()
            ) {
                $this->_mPass = $User->getUserMPass(true);
            } else {
                throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
            }
        }

        Session::setUserId($this->_userId);
    }

    /**
     * Devolver la clave de una cuenta
     *
     * @param $accountId
     * @return string
     */
    public function getAccountPassword($accountId)
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW_PASS);

        $Account = new Account($accountId);
        $Account->getAccountPassData();
        $Account->incrementDecryptCounter();

        $ret = array(
            'accountId' => $accountId,
            'pass' => Crypt::getDecrypt($Account->getAccountPass(), $this->_mPass, $Account->getAccountIV())
        );

        return $this->wrapJSON($ret);
    }

    /**
     * Comprobar el acceso a la acción
     *
     * @param $action
     * @throws SPException
     */
    private function checkActionAccess($action)
    {
        if ($this->_actionId !== $action) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @param string $data Los datos a devolver
     * @return bool
     * @throws SPException
     */
    private function wrapJSON($data)
    {
        $arrStrFrom = array("\\", '"', "'");
        $arrStrTo = array("\\", '\"', "\'");

        if (is_array($data) || is_object($data)) {
            array_walk($data,
                function (&$value) use ($arrStrFrom, $arrStrTo) {
                    if (is_object($value)) {
                        foreach ($value as &$attribute) {
                            str_replace($arrStrFrom, $arrStrTo, $attribute);
                        }

                        return $value;
                    } else {
                        return str_replace($arrStrFrom, $arrStrTo, $value);
                    }
                }
            );
        } else {
            $data = str_replace($arrStrFrom, $arrStrTo, $data);
        }

        $json = json_encode(array(
            'action' => Acl::getActionName($this->_actionId, true),
            'data' => $data,
        ));

        if ($json === false) {
            throw new SPException(SPException::SP_CRITICAL, sprintf('%s : %s', _('Error de codificación'), json_last_error_msg()));
        }

        return $json;
    }

    /**
     * Devolver los resultados de una búsqueda
     *
     * @param $search
     * @return string
     */
    public function getAccountSearch($search, $count = 0)
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_SEARCH);

        $Search = new AccountSearch();
        $Search->setTxtSearch($search);

        if ($count > 0) {
            $Search->setLimitCount($count);
        }

        $ret = $Search->getAccounts();

        return $this->wrapJSON($ret);
    }

    /**
     * Devolver la clave de una cuenta
     *
     * @param $accountId
     * @return string
     */
    public function getAccountData($accountId)
    {
        $this->checkActionAccess(ActionsInterface::ACTION_ACC_VIEW);

        $Account = new Account($accountId);
        $ret = $Account->getAccountData();
        $Account->incrementViewCounter();

        return $this->wrapJSON($ret);
    }
}