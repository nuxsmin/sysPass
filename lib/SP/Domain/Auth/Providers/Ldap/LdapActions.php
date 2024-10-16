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

namespace SP\Domain\Auth\Providers\Ldap;

use Laminas\Ldap\Collection;
use Laminas\Ldap\Exception\LdapException as LaminasLdapException;
use Laminas\Ldap\Ldap as LaminasLdap;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Core\Events\EventDispatcherInterface;

use function SP\__u;

/**
 * Class LdapActions
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
final class LdapActions implements LdapActionsService
{
    public const USER_ATTRIBUTES = [
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
        'cn',
    ];

    public const ATTRIBUTES_MAPPING = [
        'dn' => 'dn',
        'groupmembership' => 'group',
        'memberof' => 'group',
        'displayname' => 'fullname',
        'fullname' => 'fullname',
        'givenname' => 'name',
        'sn' => 'sn',
        'mail' => 'mail',
        'lockouttime' => 'expire',
    ];

    /**
     * @param LaminasLdap $ldap
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $searchBase
     * @param string|null $group
     */
    public function __construct(
        private readonly LaminasLdap              $ldap,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?string                  $searchBase = null,
        private readonly ?string                  $group = null,
    ) {
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @param string $groupFilter
     *
     * @return array Groups' DN
     * @throws LdapException
     */
    public function searchGroupsDn(string $groupFilter): array
    {
        $group = $this->getGroupFromParams();

        /** @noinspection PhpComposerExtensionStubsInspection */
        $filter = sprintf(
            '(&(cn=%s)%s)',
            ldap_escape($group, '', LDAP_ESCAPE_FILTER),
            $groupFilter
        );

        $searchResults = $this->getResults($filter, ['dn']);

        if ($searchResults->count() === 0) {
            $this->eventDispatcher->notify(
                'ldap.search.group',
                new Event(
                    $this,
                    EventMessage::build(__u('Error while searching the group RDN'))
                                ->addDetail(__u('Group'), $group)
                                ->addDetail('LDAP ERROR', $this->ldap->getLastError())
                                ->addDetail('LDAP FILTER', $filter)
                )
            );

            throw LdapException::error(
                __u('Error while searching the group RDN'),
                null,
                LdapCodeEnum::NO_SUCH_OBJECT->value
            );
        }

        return array_values(
            array_filter(
                array_map(
                    static function ($value) {
                        if (is_array($value)) {
                            return $value['dn'];
                        }

                        return null;
                    },
                    $searchResults->toArray()
                )
            )
        );
    }

    private function getGroupFromParams(): string
    {
        if ($this->group === null) {
            return '*';
        }

        if (stripos($this->group, 'cn') === 0) {
            return LdapUtil::getGroupName($this->group) ?: '';
        }

        return $this->group;
    }

    /**
     * Get LDAP search results as a Collection
     *
     * @param string $filter Filtro a utilizar
     * @param array|null $attributes Atributos a devolver
     * @param string|null $searchBase
     *
     * @return Collection
     * @throws LdapException
     */
    private function getResults(
        string $filter,
        ?array $attributes = [],
        ?string $searchBase = null
    ): Collection {
        if (empty($searchBase)) {
            $searchBase = $this->searchBase;
        }

        try {
            return $this->ldap->search($filter, $searchBase, LaminasLdap::SEARCH_SCOPE_SUB, $attributes);
        } catch (LaminasLdapException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw LdapException::error($e->getMessage(), null, $e->getCode(), $e);
        }
    }

    /**
     * @param string $filter
     *
     * @return AttributeCollection
     * @throws LdapException
     */
    public function getAttributes(string $filter): AttributeCollection
    {
        $searchResults = $this->getResults($filter)->getFirst();

        if ($searchResults === null) {
            return new AttributeCollection();
        }

        // Normalize keys for comparing
        $results = array_change_key_case($searchResults);

        $attributeCollection = new AttributeCollection();

        $attributes = array_filter(
            self::ATTRIBUTES_MAPPING,
            static fn(string $attribute) => isset($results[$attribute]),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($attributes as $attribute => $map) {
            if (is_array($results[$attribute])) {
                if ((int)$results[$attribute]['count'] > 1) {
                    // Store the whole array
                    $attributeCollection->set(
                        $map,
                        array_filter($results[$attribute], fn($key) => $key !== 'count', ARRAY_FILTER_USE_KEY)
                    );
                } else {
                    // Store first value
                    $attributeCollection->set($map, trim($results[$attribute][0]));
                }
            } else {
                $attributeCollection->set($map, trim((string)$results[$attribute]));
            }
        }

        return $attributeCollection;
    }

    /**
     * Get LDAP search results
     *
     * @throws LdapException
     */
    public function getObjects(
        string $filter,
        array  $attributes = self::USER_ATTRIBUTES,
        ?string $searchBase = null
    ): LdapResults {
        $results = $this->getResults($filter, $attributes, $searchBase);

        return new LdapResults($results->count(), $results);
    }
}
