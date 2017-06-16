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

namespace SP\Api;

defined('APP_ROOT') || die();

use ReflectionClass;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\TrackData;
use SP\Http\Request;
use SP\Mgmt\Tracks\Track;
use SP\Util\Json;

/**
 * Class ApiRequest encargada de atender la peticiones a la API de sysPass
 *
 * Procesa peticiones en formato JSON-RPC 2.0
 *
 * {"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 3}
 *
 * @see     http://www.jsonrpc.org/specification
 * @package SP
 */
class ApiRequest
{
    /**
     * Constantes de acciones
     */
    const ACTION = 'action';
    const AUTH_TOKEN = 'authToken';
    const TIME_TRACKING_MAX_ATTEMPTS = 5;
    const TIME_TRACKING = 300;

    /**
     * @var \stdClass
     */
    private $data;
    /** @var string */
    private $verb;
    /** @var ReflectionClass */
    private $ApiReflection;

    /**
     * Devolver un error formateado en JSON-RPC 2.0
     *
     * @param \Exception|SPException $e
     * @return string
     * @throws \SP\Core\Exceptions\SPException
     */
    public function formatJsonError($e)
    {
        $data = function () use ($e) {
            $class = get_class($e);

            if ($class === SPException::class
                || $class === InvalidArgumentException::class
            ) {
                $hint = $e->getHint();

                return is_array($hint) ? $hint : __($hint);
            }

            return '';
        };

        $code = $e->getCode();

        $error = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => __($e->getMessage()),
                'data' => $data()
            ],
            'id' => ($code === -32700 || $code === -32600) ? null : $this->getId()
        ];

        return Json::getJson($error);
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

    /**
     * Obtiene una nueva instancia de la Api
     *
     * @return SyspassApi
     * @throws \SP\Core\Exceptions\SPException
     */
    public function runApi()
    {
        $this->init();

        return $this->ApiReflection->getMethod($this->data->method)->invoke(new SyspassApi($this->data));
    }

    /**
     * Inicializar la API
     *
     * @throws SPException
     */
    protected function init()
    {
        try {
            $this->checkTracking();
            $this->analyzeRequestMethod();
            $this->getRequestData();
            $this->checkBasicData();
            $this->checkAction();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Comprobar los intentos de login
     *
     * @throws \SP\Core\Exceptions\AuthException
     * @throws \SP\Core\Exceptions\SPException
     */
    private function checkTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setTrackSource('API');
            $TrackData->setTrackIp($_SERVER['REMOTE_ADDR']);

            $attempts = count(Track::getItem($TrackData)->getTracksForClientFromTime(time() - self::TIME_TRACKING));
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false), __FUNCTION__, -32601);
        }

        if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
            ApiUtil::addTracking();

            sleep(0.3 * $attempts);

            throw new SPException(SPException::SP_INFO, __('Intentos excedidos', false), '', -32601);
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
                throw new SPException(SPException::SP_WARNING, __('Método inválido', false), '', -32600);
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
        $data = json_decode(Request::parse($request, '', true));

        if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new SPException(SPException::SP_WARNING, __('Datos inválidos', false), '', -32700);
        }

        if (!isset($data->jsonrpc, $data->method, $data->params, $data->id)) {
            throw new SPException(SPException::SP_WARNING, __('Formato incorrecto', false), '', -32600);
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
            throw new SPException(SPException::SP_WARNING, __('Parámetros incorrectos', false), '', -32602);
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
            ApiUtil::addTracking();

            throw new SPException(SPException::SP_WARNING, __('Acción Inválida', false), '', -32601);
        }
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
}