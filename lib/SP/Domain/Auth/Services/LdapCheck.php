<?php

declare(strict_types=1);
/**
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

use SP\Domain\Auth\Dtos\LdapCheckResults;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapCheckService;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Auth\Providers\Ldap\LdapBase;
use SP\Domain\Auth\Providers\Ldap\LdapException;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Core\Events\EventDispatcherInterface;

/**
 * Class LdapCheck
 */
final readonly class LdapCheck implements LdapCheckService
{
    public function __construct(
        private LdapConnectionHandler $ldapConnection,
        private LdapActionsService $ldapActions,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws LdapException
     */
    public function getObjectsByFilter(LdapParams $ldapParams, string $filter): LdapCheckResults
    {
        $ldap = $this->getLdapService($ldapParams);

        return new LdapCheckResults(
            self::getObjectsWithAttributes($ldap->actions(), $filter, ['dn'])
        );
    }

    /**
     * @throws LdapException
     */
    private function getLdapService(LdapParams $ldapParams): LdapService
    {
        return LdapBase::factory($this->eventDispatcher, $this->ldapConnection, $this->ldapActions, $ldapParams);
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
    public function getObjects(LdapParams $ldapParams, bool $includeGroups = true): LdapCheckResults
    {
        $ldap = $this->getLdapService($ldapParams);

        $ldapActionsService = $ldap->actions();

        $indirectFilterItems = self::getObjectsWithAttributes(
            $ldapActionsService,
            $ldap->getGroupMembershipIndirectFilter(),
            ['dn']
        );

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
}
