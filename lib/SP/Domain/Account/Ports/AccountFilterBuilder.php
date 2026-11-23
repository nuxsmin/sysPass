<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Domain\Account\Ports;

use Aura\SqlQuery\Common\SelectInterface;

/**
 * Class AccountFilterUser
 */
interface AccountFilterBuilder
{
    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     */
    public function buildFilterHistory(bool $useGlobalSearch = false, ?SelectInterface $query = null): SelectInterface;

    /**
     * Devuelve el filtro para la consulta SQL de cuentas que un usuario puede acceder
     */
    public function buildFilter(bool $useGlobalSearch = false, ?SelectInterface $query = null): SelectInterface;

    /**
     * Returns true when a non-admin user is performing an authorized global discovery query.
     */
    public function isGlobalDiscoveryActive(bool $useGlobalSearch): bool;

    /**
     * Builds the normal (non-global-search) account visibility OR predicate and unique bind values.
     *
     * @return array{sql: string, bindValues: array<string, mixed>}
     */
    public function buildVisibilityPredicate(string $prefix = 'vis'): array;
}
