<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Providers\Ldap\LdapParams;
use SP\Domain\Providers\Ldap\LdapTypeEnum;

use function SP\__u;

/**
 * Trait ConfigLdapTrait
 */
trait ConfigLdapTrait
{
    /**
     * @param RequestInterface $request
     *
     * @return LdapParams
     * @throws ValidationException
     */
    protected function getLdapParamsFromRequest(RequestInterface $request): LdapParams
    {
        $data = LdapParams::getServerAndPort($request->analyzeString('ldap_server'));

        if (count($data) === 0) {
            throw new ValidationException(__u('Wrong LDAP parameters'));
        }

        $type = LdapTypeEnum::tryFrom($request->analyzeInt('ldap_server_type')) ?: LdapTypeEnum::STD;

        $params = new LdapParams(
            $data['server'],
            $type,
            $request->analyzeString('ldap_binduser'),
            $request->analyzeEncrypted('ldap_bindpass')
        );

        $params->setPort($data['port'] ?? 389);
        $params->setSearchBase($request->analyzeString('ldap_base'));
        $params->setGroup($request->analyzeString('ldap_group'));
        $params->setTlsEnabled($request->analyzeBool('ldap_tls_enabled', false));
        $params->setFilterUserObject($request->analyzeString('ldap_filter_user_object'));
        $params->setFilterGroupObject($request->analyzeString('ldap_filter_group_object'));
        $params->setFilterUserAttributes($request->analyzeArray('ldap_filter_user_attributes'));
        $params->setFilterGroupAttributes($request->analyzeArray('ldap_filter_group_attributes'));

        return $params;
    }
}
