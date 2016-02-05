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
use SP\Core\Acl;
use SP\Core\Session;
use SP\Core\SPException;
use SP\Mgmt\User\User;
use SP\Mgmt\User\UserPass;
use SP\Mgmt\User\UserUtil;

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
    protected $actionsMap = array();

    /**
     * @param $params
     * @throws SPException
     */
    public function __construct($params)
    {
        if (!Auth::checkAuthToken($this->getActionId($params->action), $params->authToken)) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $this->userId = ApiTokensUtil::getUserIdForToken($params->authToken);
        $this->actionId = $this->getActionId($params->action);
        $this->auth = true;
        $this->params = $params;

        if (isset($params->userPass)) {
            $userLogin = UserUtil::getUserLoginById($this->userId);

            $User = new User();
            $User->setUserId($this->userId);
            $User->setUserLogin($userLogin);
            $User->setUserPass($params->userPass);

            if (Auth::authUserMySQL($userLogin, $params->userPass)
                && !UserUtil::checkUserIsDisabled($userLogin)
                && UserPass::checkUserMPass($User)
                && UserPass::checkUserUpdateMPass($userLogin)
                && !$User->isUserChangePass()
            ) {
                $this->_mPass = $User->getUserMPass(true);
            } else {
                throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
            }
        }

        Session::setUserId($this->userId);
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
    protected function wrapJSON($data)
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
            'action' => Acl::getActionName($this->actionId, true),
            'data' => $data,
        ));

        if ($json === false) {
            throw new SPException(SPException::SP_CRITICAL, sprintf('%s : %s', _('Error de codificación'), json_last_error_msg()));
        }

        return $json;
    }
}