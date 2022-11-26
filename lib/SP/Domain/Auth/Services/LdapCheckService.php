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

namespace SP\Domain\Auth\Services;

use SP\Domain\Auth\Ports\LdapCheckServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapInterface;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class LdapCheckService
 *
 * @package SP\Domain\Import\Services
 */
final class LdapCheckService extends Service implements LdapCheckServiceInterface
{
    protected ?LdapInterface $ldap = null;

    /**
     * @param  LdapParams  $ldapParams
     *
     * @throws LdapException
     */
    public function checkConnection(LdapParams $ldapParams): void
    {
        $this->ldap = Ldap::factory(
            $ldapParams,
            $this->eventDispatcher,
            true
        );
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function getObjectsByFilter(string $filter): array
    {
        $objects = $this->ldapResultsMapper(
            $this->ldap->getLdapActions()->getObjects($filter, ['dn'])
        );

        return [
            'count'   => count($objects),
            'results' => [
                [
                    'icon'  => '',
                    'items' => $objects,
                ],
            ],
        ];
    }

    /**
     * Obtener los datos de una búsqueda de LDAP de un atributo
     *
     * @param  array  $data
     * @param  string[]  $attributes
     *
     * @return array
     */
    public function ldapResultsMapper(
        array $data,
        array $attributes = ['dn']
    ): array {
        $out = [];

        foreach ($data as $result) {
            if (is_array($result)) {
                foreach ($result as $ldapAttribute => $value) {
                    if (in_array(strtolower($ldapAttribute), $attributes, true)) {
                        if (is_array($value)) {
                            unset($value['count']);

                            $out = array_merge($out, $value);
                        } else {
                            $out[] = $value;
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function getObjects(bool $includeGroups = true): array
    {
        $ldapActions = $this->ldap->getLdapActions();

        $data = ['count' => 0, 'results' => []];

        $indirectFilterItems = $this->ldapResultsMapper(
            $ldapActions->getObjects($this->ldap->getGroupMembershipIndirectFilter(), ['dn'])
        );

        $directFilterItems = $this->ldapResultsMapper(
            $ldapActions->getObjects(
                $this->ldap->getGroupMembershipDirectFilter(),
                ['member', 'memberUid', 'uniqueMember']
            ),
            ['member', 'memberUid', 'uniqueMember']
        );

        $userItems = array_unique(array_merge($indirectFilterItems, $directFilterItems));

        $data['results'][] = [
            'icon'  => 'person',
            'items' => array_values($userItems),
        ];

        if ($includeGroups) {
            $groupItems = $this->ldapResultsMapper(
                $ldapActions->getObjects($this->ldap->getGroupObjectFilter(), ['dn'])
            );

            $data['results'][] = [
                'icon'  => 'group',
                'items' => $groupItems,
            ];
        }

        array_walk(
            $data['results'],
            static function ($value) use (&$data) {
                $data['count'] += count($value['items']);
            }
        );

        return $data;
    }
}
