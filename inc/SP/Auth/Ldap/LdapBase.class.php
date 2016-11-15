<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\UserData;
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
    const SEARCH_ATTRIBUTES = ['dn', 'displayname', 'samaccountname', 'mail', 'memberof', 'lockouttime', 'fullname', 'groupmembership', 'mail'];
    /**
     * @var resource
     */
    protected $ldapHandler;
    /**
     * @var string
     */
    protected $server;
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
     * @var LdapUserData
     */
    protected $LdapUserData;

    /**
     * LdapBase constructor.
     */
    public function __construct()
    {
        $this->LdapUserData = new LdapUserData();
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @return false|int Con el número de entradas encontradas
     */
    public function checkConnection()
    {
        $Log = new Log(__FUNCTION__);

        if (!$this->searchBase || !$this->server || !$this->bindDn || !$this->bindPass) {
            $Log->addDescription(_('Los parámetros de LDAP no están configurados'));
            $Log->writeLog();

            return false;
        }

        try {
            $this->connect();
            $this->bind();
            $numResults = $this->searchBase();
        } catch (SPException $e) {
            return false;
        }

        $Log->addDescription(sprintf(_('Objetos encontrados: %s'), $numResults));
        $Log->writeLog();

        return $numResults;
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @throws SPException
     * @return bool
     */
    protected function connect()
    {
        $Log = new Log(__FUNCTION__);

        // Habilitar la traza si el modo debug está habilitado
        if (Config::getConfig()->isDebug()) {
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        $this->ldapHandler = @ldap_connect($this->server);

        // Conexión al servidor LDAP
        if (!is_resource($this->ldapHandler)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(sprintf('%s \'%s\'', _('No es posible conectar con el servidor de LDAP'), $this->server));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('No es posible conectar con el servidor de LDAP'));
        }

        @ldap_set_option($this->ldapHandler, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option($this->ldapHandler, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        return true;
    }

    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $bindDn con el DN del usuario
     * @param string $bindPass con la clave del usuario
     * @throws SPException
     * @return bool
     */
    protected function bind($bindDn = '', $bindPass = '')
    {
        $Log = new Log(__FUNCTION__);

        $dn = $bindDn ?: $this->bindDn;
        $pass = $bindPass ?: $this->bindPass;

        if (!@ldap_bind($this->ldapHandler, $dn, $pass)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al conectar (BIND)'));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP DN', $dn);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al conectar (BIND)'));
        }

        return true;
    }

    /**
     * Realizar una búsqueda de objetos en la ruta indicada.
     *
     * @throws SPException
     * @return int El número de resultados
     */
    protected function searchBase()
    {
        $Log = new Log(__FUNCTION__);

        $filter = $this->getGroupDnFilter();

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, ['dn']);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar objetos en DN base'));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al buscar objetos en DN base'));
        }

        return @ldap_count_entries($this->ldapHandler, $searchRes);
    }

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return mixed
     */
    protected abstract function getGroupDnFilter();

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
        $this->userLogin = $userLogin;
    }

    /**
     * Autentificar al usuario
     *
     * @param UserData $UserData Datos del usuario
     * @return bool
     */
    public function authenticate(UserData $UserData)
    {
        if (!$this->checkParams()) {
            return false;
        }
        try {
            $this->userLogin = $UserData->getUserLogin();

            $this->connect();
            $this->bind();
            $this->getAttributes();
            $this->bind($UserData->getUserLogin(), $UserData->getUserPass());
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
        $this->bindDn = Config::getConfig()->getLdapBindUser();
        $this->bindPass = Config::getConfig()->getLdapBindPass();
        $this->group = Config::getConfig()->getLdapGroup();

        if (!$this->searchBase || !$this->server || !$this->bindDn || !$this->bindPass) {
            Log::writeNewLog(__FUNCTION__, _('Los parámetros de LDAP no están configurados'));

            return false;
        }

        return true;
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
     * @return LdapUserData con los atributos disponibles y sus valores
     * @throws SPException
     */
    public function getAttributes()
    {
        $searchResults = $this->getUserAttributes();

        $validAttributes = [
            'groupMembership' => 'group',
            'memberOf' => 'group',
            'displayname' => 'name',
            'fullname' => 'name',
            'mail' => 'mail',
            'lockoutTime' => 'expire'];

        $res = [];

        debugLog($searchResults);

        foreach ($searchResults as $result) {
            if (is_array($result)) {
                foreach ($result as $attribute => $values) {
                    if (is_array($values) && array_key_exists($attribute, $validAttributes)) {
                        $count = (int)$values['count'];

                        if ($count > 1) {
                            // Almacenamos un array de valores
                            for ($i = 0; $i <= $count - 1; $i++) {
                                $res[$validAttributes[$attribute]][] = $values[$i];
                            }
                        } else {
                            // Almacenamos  1 solo valor
                            $res[$validAttributes[$attribute]] = $values[0];
                        }
                    }
                }
            }
        }

        $this->LdapUserData->setDn($searchResults[0]['dn']);
        $this->LdapUserData->setName($res['name']);
        $this->LdapUserData->setEmail($res['mail']);
        $this->LdapUserData->setExpire($res['expire']);
        $this->LdapUserData->setGroups($res['group']);
        $this->LdapUserData->setInGroup($this->searchUserInGroup());

        return $this->LdapUserData;
    }

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @return array
     * @throws SPException
     */
    protected function getUserAttributes()
    {
        $Log = new Log(__FUNCTION__);

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $this->getUserDnFilter(), self::SEARCH_ATTRIBUTES);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $this->userLogin);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $this->getUserDnFilter());
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 1) {
            $searchResults = @ldap_get_entries($this->ldapHandler, $searchRes);

            if (!$searchResults) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al localizar el usuario en LDAP'));
                $Log->addDetails(_('Usuario'), $this->userLogin);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
                $Log->writeLog();

                throw new SPException(SPException::SP_ERROR, _('Error al localizar el usuario en LDAP'));
            }

            return $searchResults;
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $this->userLogin);
            $Log->addDetails('LDAP FILTER', $this->getUserDnFilter());
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al buscar el DN del usuario'));
        }
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @return mixed
     */
    protected abstract function getUserDnFilter();

    /**
     * Buscar al usuario en un grupo.
     *
     * @throws SPException
     * @return bool
     */
    protected abstract function searchUserInGroup();

    /**
     * @return LdapUserData
     */
    public function getLdapUserData()
    {
        return $this->LdapUserData;
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
        $chars = ['/(,)(?!uid|cn|ou|dc)/i', '/(?<!uid|cn|ou|dc)(=)/i', '/(")/', '/(;)/', '/(>)/', '/(<)/', '/(\+)/', '/(#)/', '/\G(\s)/', '/(\s)(?=\s*$)/', '/(\/)/'];
        return preg_replace($chars, '\\\$1', $dn);
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @throws SPException
     * @return string con el RDN del grupo
     */
    protected function searchGroupDN()
    {
        $Log = new Log(__FUNCTION__);
        $filter = $this->getGroupName() ?: $this->group;
        $filter = '(cn=' . $filter . ')';

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, ['dn', 'cn']);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al buscar RDN de grupo'));
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 1) {
            $ldapSearchData = @ldap_get_entries($this->ldapHandler, $searchRes);

            if (!$ldapSearchData) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al buscar RDN de grupo'));
                $Log->addDetails(_('Grupo'), $filter);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
                $Log->writeLog();

                throw new SPException(SPException::SP_ERROR, _('Error al buscar RDN de grupo'));
            }

            return $ldapSearchData[0]['dn'];
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al buscar RDN de grupo'));
        }
    }

    /**
     * Obtener el nombre del grupo a partir del CN
     *
     * @return bool
     */
    protected function getGroupName()
    {
        if (null !== $this->group
            && preg_match('/^cn=([\w\s\d-]+)(,.*)?/i', $this->group, $groupName)
        ) {
            return $groupName[1];
        }

        return false;
    }
}