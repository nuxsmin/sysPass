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

namespace SP\Auth\Ldap;

use SP\Auth\AuthInterface;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\LogMessage;
use SP\DataModel\UserLoginData;
use SP\Log\Log;

/**
 * Class LdapBase
 *
 * @package Auth\Ldap
 */
abstract class LdapBase implements LdapInterface, AuthInterface
{
    /**
     * Atributos de búsqueda
     */
    const SEARCH_ATTRIBUTES = [
        'dn',
        'displayname',
        'samaccountname',
        'mail',
        'memberof',
        'lockouttime',
        'fullname',
        'groupmembership',
        'uid',
        'givenname',
        'sn',
        'userprincipalname',
        'cn'
    ];
    /**
     * @var resource
     */
    protected $ldapHandler;
    /**
     * @var string
     */
    protected $server;
    /**
     * @var int
     */
    protected $serverPort;
    /**
     * @var string
     */
    protected $searchBase;
    /**
     * @var string
     */
    protected $bindDn;
    /**
     * @var string
     */
    protected $bindPass;
    /**
     * @var string
     */
    protected $group;
    /**
     * @var array
     */
    protected $searchData;
    /**
     * @var string
     */
    protected $userLogin;
    /**
     * @var LdapAuthData
     */
    protected $LdapAuthData;
    /**
     * @var LogMessage
     */
    protected $LogMessage;

    /**
     * LdapBase constructor.
     */
    public function __construct()
    {
        $this->LdapAuthData = new LdapAuthData();
        $this->LogMessage = new LogMessage();
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @return false|array Con el número de entradas encontradas
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkConnection()
    {
        $this->LogMessage->setAction(__FUNCTION__);

        if (!$this->searchBase || !$this->server || !$this->bindDn || !$this->bindPass) {
            $this->LogMessage->addDescription(__('Los parámetros de LDAP no están configurados', false));
            $this->writeLog();

            return false;
        }

        try {
            $this->connect();
            $this->bind();
            $results = $this->searchBase();
        } catch (SPException $e) {
            throw $e;
        }

        $this->LogMessage->addDescription(__('Conexión a LDAP correcta', false));
        $this->LogMessage->addDetails(__('Objetos encontrados', false), (int)$results['count']);
        $this->writeLog(Log::INFO);

        return $results;
    }

    /**
     * Escribir en el registro de eventos
     *
     * @param string $level
     */
    protected function writeLog($level = Log::ERROR)
    {
        $Log = new Log($this->LogMessage);
        $Log->setLogLevel($level);
        $Log->writeLog();
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @throws SPException
     * @return bool
     */
    protected function connect()
    {
        // Habilitar la traza si el modo debug está habilitado
        if (Config::getConfig()->isDebug()) {
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        $this->ldapHandler = @ldap_connect($this->server, $this->serverPort);

        // Conexión al servidor LDAP
        if (!is_resource($this->ldapHandler)) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('No es posible conectar con el servidor de LDAP', false));
            $this->LogMessage->addDetails(__('Servidor'), $this->server);
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, $this->LogMessage->getDescription(), $this->LogMessage->getDetails());
        }

        @ldap_set_option($this->ldapHandler, LDAP_OPT_NETWORK_TIMEOUT, 10);
        @ldap_set_option($this->ldapHandler, LDAP_OPT_PROTOCOL_VERSION, 3);

        return true;
    }

    /**
     * Registrar error de LDAP y devolver el mensaje de error
     *
     * @return string
     */
    protected function ldapError()
    {
        $error = ldap_error($this->ldapHandler);
        $errno = ldap_errno($this->ldapHandler);

        $this->LdapAuthData->setStatusCode($errno);

        return sprintf('%s (%d)', $error, $errno);
    }

    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $bindDn   con el DN del usuario
     * @param string $bindPass con la clave del usuario
     * @throws SPException
     * @return bool
     */
    protected function bind($bindDn = '', $bindPass = '')
    {
        if ($bindDn && $bindPass) {
            $this->LdapAuthData->setAuthenticated(1);
        }

        $dn = $bindDn ?: $this->bindDn;
        $pass = $bindPass ?: $this->bindPass;

        if (!@ldap_bind($this->ldapHandler, $dn, $pass)) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Error al conectar (BIND)', false));
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->LogMessage->addDetails('LDAP DN', $dn);
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, __($this->LogMessage->getDescription()));
        }

        return true;
    }

    /**
     * Realizar una búsqueda de objetos en la ruta indicada.
     *
     * @throws SPException
     * @return array Con los resultados de la búsqueda
     */
    protected function searchBase()
    {
        $searchResults = $this->getResults($this->getGroupDnFilter(), ['dn']);

        if ($searchResults === false) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Error al buscar objetos en DN base', false));
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->LogMessage->addDetails('LDAP FILTER', $this->getGroupDnFilter());
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, $this->LogMessage->getDescription());
        }

        return $searchResults;
    }

    /**
     * Devolver los resultados de una paginación
     *
     * @param string $filter     Filtro a utilizar
     * @param array  $attributes Atributos a devolver
     * @return bool|array
     */
    protected function getResults($filter, array $attributes = null)
    {
        $cookie = '';
        $results = [];

        do {
            ldap_control_paged_result($this->ldapHandler, 1000, false, $cookie);

            if (!$searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, $attributes)) {
                return false;
            }

            if (@ldap_count_entries($this->ldapHandler, $searchRes) === 0
                || !$entries = @ldap_get_entries($this->ldapHandler, $searchRes)
            ) {
                return false;
            }

            $results = array_merge($results, $entries);

            ldap_control_paged_result_response($this->ldapHandler, $searchRes, $cookie);
        } while (!empty($cookie));

        return $results;
    }

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return mixed
     */
    protected abstract function getGroupDnFilter();

    /**
     * @return LdapAuthData
     */
    public function getLdapAuthData()
    {
        return $this->LdapAuthData;
    }

    /**
     * Obtener el recurso de conexión a LDAP.
     *
     * @return resource|false
     */
    public function getConnect()
    {
        return is_resource($this->ldapHandler) ? $this->ldapHandler : false;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
        $this->serverPort = $this->getServerPort();
    }

    /**
     * @return string
     */
    public function getSearchBase()
    {
        return $this->searchBase;
    }

    /**
     * @param string $searchBase
     */
    public function setSearchBase($searchBase)
    {
        $this->searchBase = $searchBase;
    }

    /**
     * @return string
     */
    public function getBindDn()
    {
        return $this->bindDn;
    }

    /**
     * @param string $bindDn
     */
    public function setBindDn($bindDn)
    {
        $this->bindDn = $bindDn;
    }

    /**
     * @return string
     */
    public function getBindPass()
    {
        return $this->bindPass;
    }

    /**
     * @param string $bindPass
     */
    public function setBindPass($bindPass)
    {
        $this->bindPass = $bindPass;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * @param string $userLogin
     */
    public function setUserLogin($userLogin)
    {
        $this->userLogin = strtolower($userLogin);
    }

    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $UserData Datos del usuario
     * @return bool
     */
    public function authenticate(UserLoginData $UserData)
    {
        if (!$this->checkParams()) {
            return false;
        }

        try {
            $this->setUserLogin($UserData->getLogin());

            $this->connect();
            $this->bind();
            $this->getAttributes();
            $this->bind($this->LdapAuthData->getDn(), $UserData->getLoginPass());
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    /**
     * Comprobar si los parámetros necesario de LDAP están establecidos.
     *
     * @return bool
     */
    public function checkParams()
    {
        $this->searchBase = Config::getConfig()->getLdapBase();
        $this->server = $this->pickServer();
        $this->serverPort = $this->getServerPort();
        $this->bindDn = Config::getConfig()->getLdapBindUser();
        $this->bindPass = Config::getConfig()->getLdapBindPass();
        $this->group = Config::getConfig()->getLdapGroup();

        if (!$this->searchBase || !$this->server || !$this->bindDn || !$this->bindPass) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Los parámetros de LDAP no están configurados', false));
            $this->writeLog();

            return false;
        }

        $this->LdapAuthData->setServer($this->server);

        return true;
    }

    /**
     * Devolver el puerto del servidor si está establecido
     *
     * @return int
     */
    protected function getServerPort()
    {
        return preg_match('/[\d\.]+:(\d+)/', $this->server, $port) ? $port[1] : 389;
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected abstract function pickServer();

    /**
     * Obtener los atributos del usuario.
     *
     * @return LdapAuthData con los atributos disponibles y sus valores
     * @throws SPException
     */
    public function getAttributes()
    {
        $searchResults = $this->getUserAttributes();

        $validAttributes = [
            'groupmembership' => 'group',
            'memberof' => 'group',
            'displayname' => 'fullname',
            'fullname' => 'fullname',
            'givenname' => 'name',
            'sn' => 'sn',
            'mail' => 'mail',
            'lockouttime' => 'expire'
        ];

        $res = [
            'name' => '',
            'sn' => '',
            'mail' => '',
            'group' => [],
            'expire' => 0
        ];

        foreach ($searchResults as $result) {
            if (is_array($result)) {
                foreach ($result as $attribute => $values) {
                    $normalizedAttribute = strtolower($attribute);

                    if (is_array($values) && array_key_exists($normalizedAttribute, $validAttributes)) {
                        $count = (int)$values['count'];

                        if ($count > 1) {
                            unset($values['count']);

                            $res[$validAttributes[$normalizedAttribute]] = $values;
                        } else {
                            // Almacenamos  1 solo valor
                            $res[$validAttributes[$normalizedAttribute]] = trim($values[0]);
                        }
                    }
                }
            }
        }

        if (!empty($res['fullname'])) {
            $this->LdapAuthData->setName($res['fullname']);
        } else {
            $this->LdapAuthData->setName($res['name'] . ' ' . $res['sn']);
        }

        $this->LdapAuthData->setDn($searchResults[0]['dn']);
        $this->LdapAuthData->setEmail($res['mail']);
        $this->LdapAuthData->setExpire($res['expire']);
        $this->LdapAuthData->setGroups($res['group']);

        if (!empty($this->group) && $this->group !== '*') {
            $this->LdapAuthData->setGroupDn($this->searchGroupDN());
        }

        $this->LdapAuthData->setInGroup($this->searchUserInGroup());

        return $this->LdapAuthData;
    }

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @return array
     * @throws SPException
     */
    protected function getUserAttributes()
    {
        $searchResults = $this->getResults($this->getUserDnFilter(), self::SEARCH_ATTRIBUTES);

        if ($searchResults === false || $searchResults['count'] > 1) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Error al localizar el usuario en LDAP', false));
            $this->LogMessage->addDetails(__('Usuario', false), $this->userLogin);
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->LogMessage->addDetails('LDAP FILTER', $this->getUserDnFilter());
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, $this->LogMessage->getDescription());
        }

        return $searchResults;
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @return mixed
     */
    protected abstract function getUserDnFilter();

    /**
     * Obtener el RDN del grupo.
     *
     * @throws SPException
     * @return string con el RDN del grupo
     */
    protected function searchGroupDN()
    {
        $group = $this->getGroupName() ?: $this->group;
        $filter = '(cn=' . $group . ')';

        $searchResults = $this->getResults($filter, ['dn', 'cn']);

        if ($searchResults === false || $searchResults['count'] > 1) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Error al buscar RDN de grupo', false));
            $this->LogMessage->addDetails(__('Grupo', false), $filter);
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->LogMessage->addDetails('LDAP FILTER', $filter);
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, $this->LogMessage->getDescription());
        }

        return $searchResults[0]['dn'];
    }

    /**
     * Obtener el nombre del grupo a partir del CN
     *
     * @return bool
     */
    protected function getGroupName()
    {
        if (null !== $this->group
            && preg_match('/^cn=([\w\s-]+)(,.*)?/i', $this->group, $groupName)
        ) {
            return $groupName[1];
        }

        return false;
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @throws SPException
     * @return bool
     */
    protected abstract function searchUserInGroup();

    /**
     * Devolver los objetos disponibles
     *
     * @return array|bool
     */
    public function findObjects()
    {
        if (!$this->checkParams()) {
            return false;
        }

        try {
            $this->connect();
            $this->bind();
            return $this->getObjects();
        } catch (SPException $e) {
            return false;
        }
    }

    /**
     * Obtener los objetos que se pertenecen al grupo filtrado
     *
     * @return int
     * @throws SPException
     */
    protected function getObjects()
    {
        $searchResults = $this->getResults($this->getGroupDnFilter(), self::SEARCH_ATTRIBUTES);

        if ($searchResults === false) {
            $this->LogMessage->setAction(__FUNCTION__);
            $this->LogMessage->addDescription(__('Error al buscar objetos en DN base', false));
            $this->LogMessage->addDetails('LDAP ERROR', $this->ldapError());
            $this->LogMessage->addDetails('LDAP FILTER', $this->getGroupDnFilter());
            $this->writeLog();

            throw new SPException(SPException::SP_ERROR, $this->LogMessage->getDescription());
        }

        return $searchResults;
    }

    /**
     * Realizar la desconexión del servidor de LDAP.
     */
    protected function unbind()
    {
        @ldap_unbind($this->ldapHandler);
    }

    /**
     * Escapar carácteres especiales en el RDN de LDAP.
     *
     * @param string $dn con el RDN del usuario
     * @return string
     */
    protected function escapeLdapDN($dn)
    {
        $chars = [
            '/(,)(?!uid|cn|ou|dc)/i',
            '/(?<!uid|cn|ou|dc)(=)/i',
            '/(")/',
            '/(;)/',
            '/(>)/',
            '/(<)/',
            '/(\+)/',
            '/(#)/',
            '/\G(\s)/',
            '/(\s)(?=\s*$)/',
            '/(\/)/'
        ];
        return preg_replace($chars, '\\\$1', $dn);
    }
}