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

namespace SP\Domain\Auth\Services;

use SP\Core\Application;
use SP\Domain\Auth\Dtos\LdapCheckResults;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapCheckService;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Common\Services\Service;
use SP\Providers\Auth\Ldap\LdapBase;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class LdapCheck
 */
final class LdapCheck extends Service implements LdapCheckService
{
    public function __construct(
        Application $application,
        private readonly LdapConnectionInterface $ldapConnection,
        private readonly LdapActionsService $ldapActions
    ) {
        parent::__construct($application);
    }

    /**
     * @throws LdapException
     */
    public function getObjectsByFilter(string $filter, ?LdapParams $ldapParams = null): LdapCheckResults
    {
        return new LdapCheckResults(
            self::getObjectsWithAttributes($this->getLdap($ldapParams)->actions(), $filter, ['dn'])
        );
    }

    /**
     * @throws LdapException
     */
    private static function getObjectsWithAttributes(
        LdapActionsService $ldapActionsService,
        string             $filter,
        array              $attributes
    ): array {
        return self::ldapResultsMapper(
            iterator_to_array($ldapActionsService->getObjects($filter, $attributes)->getIterator()),
            $attributes
        );
    }

    /**
     * Obtener los datos de una búsqueda de LDAP de un atributo
     *
     * @param array $data
     * @param string[] $attributes
     *
     * @return array
     */
    private static function ldapResultsMapper(array $data, array $attributes = ['dn']): array
    {
        $attributesKey = array_flip($attributes);

        return array_map(
            static function (mixed $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($k !== 'count') {
                            return $v;
                        }
                    }
                }

                return $value;
            },
            array_filter($data, static fn(mixed $d) => is_array($d) && array_intersect_key($d, $attributesKey))
        );
    }

    /**
     * @throws LdapException
     */
    public function getObjects(bool $includeGroups = true, ?LdapParams $ldapParams = null): LdapCheckResults
    {
        $ldap = $this->getLdap($ldapParams);

        $ldapActionsService = $ldap->actions();

        $indirectFilterItems =
            self::getObjectsWithAttributes($ldapActionsService, $ldap->getGroupMembershipIndirectFilter(), ['dn']);

        $directFilterItems = self::getObjectsWithAttributes(
            $ldapActionsService,
            $ldap->getGroupMembershipDirectFilter(),
            ['member', 'memberUid', 'uniqueMember']
        );


        $ldapCheckResults = new LdapCheckResults(
            array_values(array_unique(array_merge($indirectFilterItems, $directFilterItems))),
            'person'
        );

        if ($includeGroups) {
            $ldapCheckResults->addItems(
                self::getObjectsWithAttributes($ldapActionsService, $ldap->getGroupObjectFilter(), ['dn']),
                'group'
            );
        }

        return $ldapCheckResults;
    }

    /**
     * @param LdapParams $ldapParams
     *
     * @return LdapService
     * @throws LdapException
     */
    private function getLdap(LdapParams $ldapParams): LdapService
    {
        return LdapBase::factory(
            $this->eventDispatcher,
            $this->ldapConnection,
            $this->ldapActions,
            $ldapParams
        );
    }
}
