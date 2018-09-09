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


/**
 * Class LdapActions
 *
 * @package SP\Providers\Auth\Ldap
 */
final class LdapActions
{
    /**
     * Atributos de búsqueda
     */
    const USER_ATTRIBUTES = [
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
     * @var LdapParams
     */
    private $ldapParams;
    /**
     * @var resource
     */
    private $ldapHandler;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * LdapActions constructor.
     *
     * @param LdapConnectionInterface $ldapConnection
     * @param EventDispatcher         $eventDispatcher
     *
     */
    public function __construct(LdapConnectionInterface $ldapConnection, EventDispatcher $eventDispatcher)
    {
        $this->ldapHandler = $ldapConnection->connectAndBind();
        $this->ldapParams = $ldapConnection->getLdapParams();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @param string $groupFilter
     *
     * @return array Groups' DN
     * @throws LdapException
     */
    public function searchGroupsDn(string $groupFilter)
    {
        $filter = '(&(cn='
            . ldap_escape($this->getGroupFromParams(), null, LDAP_ESCAPE_FILTER)
            . ')'
            . $groupFilter
            . ')';

        $searchResults = $this->getResults($filter, ['dn']);

        if ((int)$searchResults['count'] === 0) {
            $this->eventDispatcher->notifyEvent('ldap.search.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar RDN de grupo'))
                    ->addDetail(__u('Grupo'), $this->getGroupFromParams())
                    ->addDetail('LDAP ERROR', LdapConnection::getLdapErrorMessage($this->ldapHandler))
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al buscar RDN de grupo'));
        }

        return array_filter(array_map(function ($value) {
            if (is_array($value)) {
                return $value['dn'];
            }

            return null;
        }, $searchResults));
    }

    /**
     * @return string
     */
    protected function getGroupFromParams(): string
    {
        if (strpos($this->ldapParams->getGroup(), 'cn') === 0) {
            return LdapUtil::getGroupName($this->ldapParams->getGroup());
        }

        return $this->ldapParams->getGroup();
    }

    /**
     * Devolver los resultados de una paginación
     *
     * @param string $filter     Filtro a utilizar
     * @param array  $attributes Atributos a devolver
     *
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
     * Obtener los atributos del usuario.
     *
     * @param string $filter
     *
     * @return array
     * @throws LdapException
     */
    public function getAttributes(string $filter)
    {
        $validAttributes = [
            'dn' => 'dn',
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
            'dn' => '',
            'name' => '',
            'sn' => '',
            'mail' => '',
            'group' => [],
            'expire' => 0
        ];

        $searchResults = $this->getObjects($filter);

        if ((int)$searchResults['count'] === 0) {
            $this->eventDispatcher->notifyEvent('ldap.getAttributes',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al localizar el usuario en LDAP'))
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al localizar el usuario en LDAP'));
        }

        foreach ($searchResults as $result) {
            if (is_array($result)) {
                foreach ($result as $attribute => $values) {
                    $normalizedAttribute = strtolower($attribute);

                    if (array_key_exists($normalizedAttribute, $validAttributes)) {
                        if (is_array($values)) {
                            $count = (int)$values['count'];

                            if ($count > 1) {
                                unset($values['count']);

                                $res[$validAttributes[$normalizedAttribute]] = $values;
                            } else {
                                // Almacenamos  1 solo valor
                                $res[$validAttributes[$normalizedAttribute]] = trim($values[0]);
                            }
                        } else {
                            $res[$validAttributes[$normalizedAttribute]] = trim($values);
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Obtener los objetos según el filtro indicado
     *
     * @param string $filter
     * @param array  $attributes
     *
     * @return array
     * @throws LdapException
     */
    public function getObjects($filter, array $attributes = self::USER_ATTRIBUTES)
    {
        if (($searchResults = $this->getResults($filter, $attributes)) === false) {
            $this->eventDispatcher->notifyEvent('ldap.search',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar objetos en DN base'))
                    ->addDetail('LDAP ERROR', LdapConnection::getLdapErrorMessage($this->ldapHandler))
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al buscar objetos en DN base'));
        }

        return $searchResults;
    }
}