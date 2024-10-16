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

use SP\Domain\Auth\Providers\AuthDataBase;

/**
 * Class LdapUserData
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
final class LdapAuthData extends AuthDataBase
{
    protected ?string $dn      = null;
    protected int     $expire  = 0;
    protected bool    $inGroup = false;

    public function getDn(): ?string
    {
        return $this->dn;
    }

    public function setDn(string $dn): void
    {
        $this->dn = $dn;
    }

    public function getExpire(): int
    {
        return $this->expire;
    }

    public function setExpire(int $expire): void
    {
        $this->expire = $expire;
    }

    public function isInGroup(): bool
    {
        return $this->inGroup;
    }

    public function setInGroup(bool $inGroup): void
    {
        $this->inGroup = $inGroup;
    }
}
