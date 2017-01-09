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

use ReflectionClass;
use SP\Http\Request;
use SP\Core\Exceptions\SPException;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ApiRequest encargada de atender la peticiones a la API de sysPass
 *
 * Procesa peticiones en formato JSON-RPC 2.0
 *
 * {"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 3}
 *
 * @package SP
 */
class ApiRequest extends Request
{
    /**
     * Constantes de acciones
     */
    const ACTION = 'action';
    const AUTH_TOKEN = 'authToken';

    /**
     * @var \stdClass
     */
    private $data;
    /** @var string */
    private $verb;
    /** @var ReflectionClass */
    private $ApiReflection;

    /**
     * ApiRequest constructor.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct()
    {
        try {
            $this->analyzeRequestMethod();
            $this->getRequestData();
            $this->checkBasicData();
            $this->checkAction();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Analizar y establecer el método HTTP a utilizar
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function analyzeRequestMethod()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Sólo se permiten estos métodos
        switch ($requestMethod) {
            case 'GET':
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $this->verb = $requestMethod;
                break;
            default:
                throw new SPException(SPException::SP_WARNING, _('Método inválido'), '', -32600);
        }
    }

    /**
     * Obtener los datos de la petición
     *
     * Comprueba que el JSON esté bien formado
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function getRequestData()
    {
        $request = file_get_contents('php://input');
        $data = json_decode(self::parse($request, '', true));

        if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new SPException(SPException::SP_WARNING, _('Datos inválidos'), '', -32700);
        } elseif (!isset($data->jsonrpc, $data->method, $data->params, $data->id)) {
            throw new SPException(SPException::SP_WARNING, _('Formato incorrecto'), '', -32600);
        }

        $this->data = $data;
    }

    /**
     * Comprobar los datos básicos de la petición
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function checkBasicData()
    {
        if (!isset($this->data->params->authToken)) {
            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'), '', -32602);
        }
    }

    /**
     * Comprobar si la API tiene implementada dicha acción
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function checkAction()
    {
        $this->ApiReflection = new ReflectionClass(SyspassApi::class);

        if (!$this->ApiReflection->hasMethod($this->data->method)) {
            throw new SPException(SPException::SP_WARNING, _('Acción inválida'), '', -32601);
        }
    }

    /**
     * Obtiene una nueva instancia de la Api
     *
     * @return SyspassApi
     * @throws \SP\Core\Exceptions\SPException
     */
    public function runApi()
    {
        return $this->ApiReflection->getMethod($this->data->method)->invoke(new SyspassApi($this->data));
    }

    /**
     * Obtener el id de la acción
     *
     * @return int
     */
    public function getAction()
    {
        return $this->data->method;
    }

    /**
     * Devielve el Id de la petición
     *
     * @return int
     */
    public function getId()
    {
        return (int)$this->data->id;
    }
}