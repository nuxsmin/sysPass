<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core\Acl;

/**
 * Interface AclInterface
 */
interface AclInterface
{
    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int $actionId El id de la acción
     * @param bool $translate
     *
     * @return string
     * @internal param bool $translate Si se devuelve el nombre corto de la acción
     */
    public function getInfoFor(int $actionId, bool $translate = true): string;

    /**
     * Returns action route
     */
    public function getRouteFor(string $actionId): string;

    /**
     * Comprobar los permisos de acceso del usuario a los módulos de la aplicación.
     */
    public function checkUserAccess(int $action, int $userId = 0): bool;
}
