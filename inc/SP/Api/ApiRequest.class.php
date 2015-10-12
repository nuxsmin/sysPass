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

use SP\Http\Request;
use SP\Core\SPException;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ApiRequest encargada de atender la peticiones a la API de sysPass
 *
 * @package SP
 */
class ApiRequest extends Request
{
    const ACTION_ID = 'a';
    const USER = 'u';
    const USER_PASS = 'up';
    const AUTH_TOKEN = 't';
    const ITEM = 'i';
    const SEARCH = 's';
    const SEARCH_COUNT = 'sc';

    /**
     * @var \stdClass
     */
    private $_vars;

    public function __construct(){
        $authToken = self::analyze(self::AUTH_TOKEN);
        $actionId = self::analyze(self::ACTION_ID, 0);

        if (!$authToken || !$actionId){
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        $this->addVar('authToken', $authToken);
        $this->addVar('actionId', $actionId);
        $this->addVar('userPass', null);
    }

    /**
     * Añade una nueva variable de petición al array
     *
     * @param $name string El nombre de la variable
     * @param $value mixed El valor de la variable
     */
    public function addVar($name, $value)
    {
        $this->_vars->$name = $value;
    }

    /**
     * Obtiene una nueva instancia de la Api
     *
     * @return Api
     */
    public function getApi()
    {
        return new Api($this->_vars->actionId, $this->_vars->authToken, $this->_vars->userPass);
    }

    /**
     * Obtener el id de la acción
     *
     * @return int
     */
    public function getAction()
    {
        return $this->_vars->actionId;
    }

    /**
     * Devolver un array con la ayuda de parámetros
     *
     * @return array
     */
    public static function getHelp()
    {
        return array(
            self::AUTH_TOKEN => _('Token de autorización'),
            self::ACTION_ID => _('Acción a realizar'),
            self::USER_PASS => _('Clave de usuario (opcional)'),
            self::SEARCH => _('Cadena a buscar'),
            self::SEARCH_COUNT => _('Numero de cuentas a mostar en la búsqueda'),
            self::ITEM => _('Item a devolver')
        );
    }

}