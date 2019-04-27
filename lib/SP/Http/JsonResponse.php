<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Http;

use JsonSerializable;
use stdClass;

/**
 * Class Json para definir la estructura de una respuesta en formato JSON
 *
 * @package SP\Http
 */
final class JsonResponse implements JsonSerializable
{
    const JSON_SUCCESS = 0;
    const JSON_SUCCESS_STICKY = 100;
    const JSON_ERROR = 1;
    const JSON_ERROR_STICKY = 101;
    const JSON_WARNING = 2;
    const JSON_WARNING_STICKY = 102;
    const JSON_LOGOUT = 10;

    /**
     * @var int
     */
    protected $status = 1;
    /**
     * @var string
     */
    protected $description = '';
    /**
     * @var string
     */
    protected $action = '';
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var array
     */
    protected $messages = [];
    /**
     * @var string
     */
    protected $container = '';
    /**
     * @var string
     */
    protected $csrf = '';

    /**
     * JsonResponse constructor.
     *
     * @param string $description
     */
    public function __construct(string $description = null)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return JsonResponse
     */
    public function setStatus($status)
    {
        $this->status = (int)$status;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return JsonResponse
     */
    public function setDescription($description)
    {
        $this->description = __($description);

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return JsonResponse
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array|stdClass $data
     *
     * @return JsonResponse
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     *
     * @return JsonResponse
     */
    public function setMessages(array $messages)
    {
        $this->messages = array_map('__', $messages);

        return $this;
    }

    /**
     * @return string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $container
     *
     * @return JsonResponse
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return string
     */
    public function getCsrf()
    {
        return $this->csrf;
    }

    /**
     * @param string $csrf
     *
     * @return JsonResponse
     */
    public function setCsrf($csrf)
    {
        $this->csrf = $csrf;

        return $this;
    }

    /**
     * @param $message
     *
     * @return JsonResponse
     */
    public function addMessage($message)
    {
        $this->messages[] = __($message);
        return $this;
    }

    /**
     * @param $param
     *
     * @return $this
     */
    public function addParam($param)
    {
        if (is_numeric($param)) {
            $param = (int)$param;
        }

        $this->data[] = $param;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getJsonArray();
    }

    /**
     * Devolver un array con las propiedades del objeto
     *
     * @return array
     */
    public function getJsonArray()
    {
        $out = [];

        foreach ($this as $key => $value) {
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Establecer los valores por defecto
     *
     * @return JsonResponse
     */
    public function clear()
    {
        $this->status = 0;
        $this->action = '';
        $this->data = [];
        $this->messages = [];
        $this->container = '';
        $this->csrf = '';

        return $this;
    }
}