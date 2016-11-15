<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace Auth\Ldap;
use SP\Auth\Ldap\LdapUserData;

/**
 * Interface LdapInterface
 *
 * @package Auth\Ldap
 */
interface LdapInterface
{
    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $bindDn   con el DN del usuario
     * @param string $bindPass con la clave del usuario
     * @return void
     */
    public function bind($bindDn = '', $bindPass = '');

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @return bool
     */
    public function checkConnection();

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @return resource|false
     */
    public function connect();

    /**
     * Comprobar si los parámetros necesarios de LDAP están establecidos.
     *
     * @return bool
     */
    public function checkParams();

    /**
     * Desconectar del servidor de LDAP
     *
     * @return mixed
     */
    public function unbind();

    /**
     * Obtener los atributos del usuario.
     *
     * @return LdapUserData
     */
    public function getAttributes();
}