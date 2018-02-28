<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\DataModel\UserLoginData;
use SP\Providers\Auth\AuthInterface;

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
    protected $userLogin;
    /**
     * @var LdapAuthData
     */
    protected $ldapAuthData;
    /**
     * @var LdapParams
     */
    protected $ldapParams;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var string
     */
    protected $server;
    /**
     * @var bool
     */
    protected $isConnected = false;
    /**
     * @var bool
     */
    protected $isBound = false;
    /**
     * @var bool
     */
    private $debug;

    /**
     * LdapBase constructor.
     *
     * @param LdapParams      $ldapParams
     * @param EventDispatcher $eventDispatcher
     * @param bool            $debug
     */
    public function __construct(LdapParams $ldapParams, EventDispatcher $eventDispatcher, $debug = false)
    {
        $this->ldapParams = $ldapParams;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = (bool)$debug;

        $this->ldapAuthData = new LdapAuthData();
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @throws LdapException
     */
    public function checkConnection()
    {
        try {
            $this->connectAndBind();

            $this->eventDispatcher->notifyEvent('ldap.check.connection',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Conexión a LDAP correcta')))
            );
        } catch (LdapException $e) {
            throw $e;
        }
    }

    /**
     * @throws LdapException
     */
    protected function connectAndBind()
    {
        if (!$this->isConnected && !$this->isBound) {
            $this->connect();
            $this->bind();
        }
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @throws LdapException
     * @return bool
     */
    protected function connect()
    {
        $this->checkParams();

        // Habilitar la traza si el modo debug está habilitado
        if ($this->debug) {
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        $this->ldapHandler = @ldap_connect($this->server, $this->ldapParams->getPort());

        // Conexión al servidor LDAP
        if (!is_resource($this->ldapHandler)) {
            $this->eventDispatcher->notifyEvent('ldap.connection',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('No es posible conectar con el servidor de LDAP'))
                    ->addDetail(__('Servidor'), $this->server)
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage()))
            );

            throw new LdapException(__u('No es posible conectar con el servidor de LDAP'));
        }

        @ldap_set_option($this->ldapHandler, LDAP_OPT_NETWORK_TIMEOUT, 10);
        @ldap_set_option($this->ldapHandler, LDAP_OPT_PROTOCOL_VERSION, 3);

        $this->isConnected = true;

        return true;
    }

    /**
     * Comprobar si los parámetros necesario de LDAP están establecidos.
     *
     * @throws LdapException
     */
    public function checkParams()
    {
        if (!$this->ldapParams->getSearchBase()
            || !$this->ldapParams->getServer()
            || !$this->ldapParams->getBindDn()
        ) {
            $this->eventDispatcher->notifyEvent('ldap.check.params',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Los parámetros de LDAP no están configurados')))
            );

            throw new LdapException(__u('Los parámetros de LDAP no están configurados'));
        }

        $this->server = $this->pickServer();
        $this->ldapAuthData->setServer($this->server);
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected abstract function pickServer();

    /**
     * Registrar error de LDAP y devolver el mensaje de error
     *
     * @return string
     */
    protected function getLdapErrorMessage()
    {
        $this->ldapAuthData->setStatusCode(ldap_errno($this->ldapHandler));

        return sprintf('%s (%d)', ldap_error($this->ldapHandler), $this->ldapAuthData->getStatusCode());
    }

    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $bindDn   con el DN del usuario
     * @param string $bindPass con la clave del usuario
     * @throws LdapException
     * @return bool
     */
    protected function bind($bindDn = null, $bindPass = null)
    {
        $this->ldapAuthData->setAuthenticated($bindDn && $bindPass);

        $dn = $bindDn ?: $this->ldapParams->getBindDn();
        $pass = $bindPass ?: $this->ldapParams->getBindPass();

        if (@ldap_bind($this->ldapHandler, $dn, $pass) === false) {
            $this->eventDispatcher->notifyEvent('ldap.bind',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al conectar (BIND)'))
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP DN', $dn))
            );

            throw new LdapException(__u('Error al conectar (BIND)'));
        }

        $this->isBound = true;

        return true;
    }

    /**
     * @return LdapAuthData
     */
    public function getLdapAuthData()
    {
        return $this->ldapAuthData;
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
     * @param UserLoginData $userLoginData Datos del usuario
     * @return bool
     */
    public function authenticate(UserLoginData $userLoginData)
    {

        try {
            $this->ldapAuthData->setAuthGranted($this->isAuthGranted());
            $this->setUserLogin($userLoginData->getLoginUser());

            $this->connectAndBind();
            $this->getAttributes();
            $this->bind($this->ldapAuthData->getDn(), $userLoginData->getLoginPass());
        } catch (LdapException $e) {
            processException($e);

            return false;
        }

        return true;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return boolean
     */
    public function isAuthGranted()
    {
        return true;
    }

    /**
     * Obtener los atributos del usuario.
     *
     * @return LdapAuthData con los atributos disponibles y sus valores
     * @throws LdapException
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
            $this->ldapAuthData->setName($res['fullname']);
        } else {
            $this->ldapAuthData->setName($res['name'] . ' ' . $res['sn']);
        }

        $this->ldapAuthData->setDn($searchResults[0]['dn']);
        $this->ldapAuthData->setEmail($res['mail']);
        $this->ldapAuthData->setExpire($res['expire']);
        $this->ldapAuthData->setGroups($res['group']);

        if (!empty($this->group) && $this->group !== '*') {
            $this->ldapAuthData->setGroupDn($this->searchGroupDN());
        }

        $this->ldapAuthData->setInGroup($this->searchUserInGroup());

        return $this->ldapAuthData;
    }

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @return array
     * @throws LdapException
     */
    protected function getUserAttributes()
    {
        $searchResults = $this->getResults($this->getUserDnFilter(), self::SEARCH_ATTRIBUTES);

        if ($searchResults === false || $searchResults['count'] > 1) {
            $this->eventDispatcher->notifyEvent('ldap.getAttributes',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al localizar el usuario en LDAP'))
                    ->addDetail(__u('Usuario'), $this->userLogin)
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP DN', $this->getGroupMembershipFilter()))
            );

            throw new LdapException(__u('Error al localizar el usuario en LDAP'));
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
     * @throws LdapException
     * @return string con el RDN del grupo
     */
    protected function searchGroupDN()
    {
        $group = $this->getGroupName() ?: $this->ldapParams->getGroup();
        $filter = '(cn=' . ldap_escape($group) . ')';

        $searchResults = $this->getResults($filter, ['dn', 'cn']);

        if ($searchResults === false || $searchResults['count'] > 1) {
            $this->eventDispatcher->notifyEvent('ldap.search.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar RDN de grupo'))
                    ->addDetail(__u('Grupo'), $filter)
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al buscar RDN de grupo'));
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
        if ($this->ldapParams->getGroup()
            && preg_match('/^cn=(?P<groupname>[\w\s-]+)(?:,.*)?/i', $this->ldapParams->getGroup(), $matches)
        ) {
            return $matches['groupname'];
        }

        return false;
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @return bool
     */
    protected abstract function searchUserInGroup();

    /**
     * Devolver los objetos disponibles
     *
     * @param array $attributes
     * @return array|bool
     * @throws LdapException
     */
    public function findUsersByGroupFilter(array $attributes = self::SEARCH_ATTRIBUTES)
    {
        $this->connectAndBind();

        return $this->getObjects($this->getGroupMembershipFilter(), $attributes);
    }

    /**
     * Obtener los objetos según el filtro indicado
     *
     * @param string $filter
     * @param array  $attributes
     * @return array
     * @throws LdapException
     */
    protected function getObjects($filter, array $attributes = self::SEARCH_ATTRIBUTES)
    {
        if (($searchResults = $this->getResults($filter, $attributes)) === false) {
            $this->eventDispatcher->notifyEvent('ldap.search',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar objetos en DN base'))
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP FILTER', $this->getGroupMembershipFilter()))
            );

            throw new LdapException(__u('Error al buscar objetos en DN base'));
        }

        return $searchResults;
    }

    /**
     * Devolver los objetos disponibles
     *
     * @param array $attributes
     * @return array|bool
     * @throws LdapException
     */
    public function findGroups($attributes = self::SEARCH_ATTRIBUTES)
    {
        $this->connectAndBind();

        return $this->getObjects($this->getGroupObjectFilter(), $attributes);
    }

    /**
     * Devolver el filtro para objetos del tipo grupo
     *
     * @return mixed
     */
    protected abstract function getGroupObjectFilter();

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * @return bool
     */
    public function isBound()
    {
        return $this->isBound;
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

            if (!$searchRes = @ldap_search($this->ldapHandler, $this->ldapParams->getSearchBase(), $filter, $attributes)) {
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
    protected abstract function getGroupMembershipFilter();

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