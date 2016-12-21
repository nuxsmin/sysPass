<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Auth\Auth;
use SP\Auth\AuthUtil;
use SP\Core\Acl;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Util\Json;

/**
 * Class ApiBase
 *
 * @package SP\Api
 */
abstract class ApiBase
{
    /**
     * El ID de la acción
     *
     * @var int
     */
    protected $actionId = 0;
    /**
     * El ID de usuario resuelto
     *
     * @var int
     */
    protected $userId = 0;
    /**
     * Indica si la autentificación es correcta
     *
     * @var bool
     */
    protected $auth = false;
    /**
     * Los parámetros de la acción a ejecutar
     *
     * @var mixed
     */
    protected $params;
    /**
     * @var array
     */
    protected $actionsMap = [];
    /**
     * @var string
     */
    protected $mPass = '';

    /**
     * @param $params
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct($params)
    {
        if (!AuthUtil::checkAuthToken($this->getActionId($params->action), $params->authToken)) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $this->userId = ApiTokensUtil::getUserIdForToken($params->authToken);
        $this->actionId = $this->getActionId($params->action);
        $this->auth = true;
        $this->params = $params;

        if (isset($params->userPass)) {

            $UserData = new UserData();
            $UserData->setUserId($this->userId);
            $UserData->setUserPass($params->userPass);

            User::getItem($UserData)->getById($this->userId);

            $UserPass = UserPass::getItem($UserData);
            $Auth = new Auth($UserData);
            
            if (!$UserData->isUserIsDisabled()
                && $Auth->doAuth()
                && $UserPass->loadUserMPass()
                && $UserPass->checkUserUpdateMPass()
            ) {
                $this->mPass = $UserPass->getClearUserMPass();
                SessionUtil::loadUserSession($UserData);
            } else {
                throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
            }
        }

        Session::setSessionType(Session::SESSION_API);
    }

    /**
     * Devolver el código de acción a realizar a partir del nombre
     *
     * @param $action string El nombre de la acción
     * @return int
     */
    protected function getActionId($action)
    {
        return (is_array($this->actionsMap) && isset($this->actionsMap[$action])) ? $this->actionsMap[$action] : 0;
    }

    /**
     * Comprobar el acceso a la acción
     *
     * @param $action
     * @throws SPException
     */
    protected function checkActionAccess($action)
    {
        if ($this->actionId !== $action) {
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
    protected function wrapJSON(&$data)
    {
        $json = array(
            'action' => Acl::getActionName($this->actionId, true),
            'data' => $data
        );

        return Json::getJson($json);
    }
}