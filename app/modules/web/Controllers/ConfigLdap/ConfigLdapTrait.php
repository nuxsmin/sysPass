<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers\ConfigLdap;


use SP\Core\Exceptions\ValidationException;
use SP\Http\RequestInterface;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapTypeInterface;

/**
 * Trait ConfigLdapTrait
 */
trait ConfigLdapTrait
{
    /**
     * @param  \SP\Http\RequestInterface  $request
     *
     * @return LdapParams
     * @throws \SP\Core\Exceptions\ValidationException
     */
    protected function getLdapParamsFromRequest(RequestInterface $request): LdapParams
    {
        $data = LdapParams::getServerAndPort($request->analyzeString('ldap_server'));

        if (count($data) === 0) {
            throw new ValidationException(__u('Wrong LDAP parameters'));
        }

        $params = new LdapParams();
        $params->setServer($data['server']);
        $params->setPort($data['port'] ?? 389);
        $params->setSearchBase($request->analyzeString('ldap_base'));
        $params->setGroup($request->analyzeString('ldap_group'));
        $params->setBindDn($request->analyzeString('ldap_binduser'));
        $params->setBindPass($request->analyzeEncrypted('ldap_bindpass'));
        $params->setType($request->analyzeInt('ldap_server_type', LdapTypeInterface::LDAP_STD));
        $params->setTlsEnabled($request->analyzeBool('ldap_tls_enabled', false));
        $params->setFilterUserObject($request->analyzeString('ldap_filter_user_object', null));
        $params->setFilterGroupObject($request->analyzeString('ldap_filter_group_object', null));
        $params->setFilterUserAttributes($request->analyzeArray('ldap_filter_user_attributes'));
        $params->setFilterGroupAttributes($request->analyzeArray('ldap_filter_group_attributes'));

        return $params;
    }
}