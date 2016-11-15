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

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Log\Log;

/**
 * Class LdapStd
 *
 * @package SP\Auth\Ldap
 */
class LdapStd extends LdapBase
{
    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return mixed
     */
    protected function getGroupDnFilter()
    {
        $groupDN = (!empty($this->group)) ? $this->searchGroupDN() : '*';

        return '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected function pickServer()
    {
        return Config::getConfig()->getLdapServer();
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @return mixed
     */
    protected function getUserDnFilter()
    {
        return '(&(|(samaccountname=' . $this->userLogin . ')(cn=' . $this->userLogin . ')(uid=' . $this->userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @throws SPException
     * @return bool
     */
    protected function searchUserInGroup()
    {
        $Log = new Log(__FUNCTION__);

        $groupDN = $this->getGroupName() ?: $this->group;
        $userDN = $this->escapeLdapDN($this->LdapUserData->getDn());

        // Comprobar si está establecido el filtro de grupo o el grupo coincide con
        // los grupos del usuario
        if (!$this->group
            || $this->group === '*'
            || in_array($groupDN, $this->LdapUserData->getGroups())
        ) {
            $Log->addDescription(_('Usuario verificado en grupo'));
            $Log->writeLog();

            return true;
        }

        $filter = '(&(cn=' . $groupDN . ')(|(member=' . $userDN . ')(uniqueMember=' . $userDN . '))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, ['member', 'uniqueMember']);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el grupo de usuarios'));
            $Log->addDetails(_('Grupo'), $groupDN);
            $Log->addDetails(_('Usuario'), $this->LdapUserData->getDn());
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, $Log->getDescription());
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 0) {
            $Log->addDescription(_('Usuario no pertenece al grupo'));
            $Log->addDetails(_('Usuario'), $this->LdapUserData->getDn());
            $Log->addDetails(_('Grupo'), $groupDN);

            return false;
        }

        $Log->addDescription(_('Usuario verificado en grupo'));
        $Log->addDescription($groupDN);
        $Log->addDescription($filter);
        $Log->writeLog();

        return true;
    }
}