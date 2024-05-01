<?php
declare(strict_types=1);
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

namespace SP\Domain\Auth\Providers\Ldap;

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\ValidationException;

use function SP\__u;

/**
 * Class LdapParams
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
final class LdapParams
{
    private const REGEX_SERVER = '(?<server>(?:(?<proto>ldap|ldaps):\/\/)?[\w\.\-]+)(?::(?<port>\d+))?';

    private int     $port                  = 389;
    private ?string $searchBase            = null;
    private ?string $group                 = null;
    private bool    $tlsEnabled            = false;
    private ?string $filterUserObject      = null;
    private ?string $filterGroupObject     = null;
    private ?array  $filterUserAttributes  = null;
    private ?array  $filterGroupAttributes = null;

    public function __construct(
        private readonly string       $server,
        private readonly LdapTypeEnum $type,
        private readonly string       $bindDn,
        private readonly string       $bindPass
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function getFrom(ConfigDataInterface $configData): LdapParams
    {
        $data = self::getServerAndPort($configData->getLdapServer());

        if (count($data) === 0) {
            throw ValidationException::error(__u('Wrong LDAP parameters'));
        }

        $ldapParams = new self(
            $data['server'],
            LdapTypeEnum::from($configData->getLdapType()),
            $configData->getLdapBindUser(),
            $configData->getLdapBindPass()
        );

        $ldapParams->setPort($data['port'] ?? 389);
        $ldapParams->setSearchBase($configData->getLdapBase());
        $ldapParams->setGroup($configData->getLdapGroup());
        $ldapParams->setFilterUserObject($configData->getLdapFilterUserObject());
        $ldapParams->setFilterGroupObject($configData->getLdapFilterGroupObject());
        $ldapParams->setFilterUserAttributes($configData->getLdapFilterUserAttributes());
        $ldapParams->setFilterGroupAttributes($configData->getLdapFilterGroupAttributes());

        return $ldapParams;
    }

    /**
     * Devolver el puerto del servidor si está establecido
     *
     * @param $server
     *
     * @return array
     */
    public static function getServerAndPort($server): array
    {
        return preg_match(
            '#' . self::REGEX_SERVER . '#i',
            $server,
            $matches
        ) ? $matches : [];
    }

    public function getFilterUserObject(): ?string
    {
        return $this->filterUserObject;
    }

    public function setFilterUserObject(?string $filterUserObject = null): void
    {
        if (!empty($filterUserObject)) {
            $this->filterUserObject = $filterUserObject;
        }
    }

    public function getFilterGroupObject(): ?string
    {
        return $this->filterGroupObject;
    }

    public function setFilterGroupObject(?string $filterGroupObject = null): void
    {
        if (!empty($filterGroupObject)) {
            $this->filterGroupObject = $filterGroupObject;
        }
    }

    public function getFilterUserAttributes(): ?array
    {
        return $this->filterUserAttributes;
    }

    public function setFilterUserAttributes(?array $filterUserAttributes = null): void
    {
        $this->filterUserAttributes = $filterUserAttributes;
    }

    public function getFilterGroupAttributes(): ?array
    {
        return $this->filterGroupAttributes;
    }

    public function setFilterGroupAttributes(?array $filterGroupAttributes = null): void
    {
        $this->filterGroupAttributes = $filterGroupAttributes;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): LdapParams
    {
        $this->port = $port;

        return $this;
    }

    public function getSearchBase(): ?string
    {
        return $this->searchBase;
    }

    public function setSearchBase(string $searchBase): LdapParams
    {
        $this->searchBase = $searchBase;

        return $this;
    }

    public function getBindDn(): ?string
    {
        return $this->bindDn;
    }

    public function getBindPass(): ?string
    {
        return $this->bindPass;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(string $group): LdapParams
    {
        $this->group = $group;

        return $this;
    }

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function getType(): LdapTypeEnum
    {
        return $this->type;
    }

    public function isTlsEnabled(): bool
    {
        return $this->tlsEnabled;
    }

    public function setTlsEnabled(bool $tlsEnabled): LdapParams
    {
        $this->tlsEnabled = $tlsEnabled;

        return $this;
    }
}
