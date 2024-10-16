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

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Ports\RequestService;

use function SP\__u;

/**
 * Class LdapParams
 */
final class LdapParams
{
    private const REGEX_SERVER    = '(?<server>(?:(?<proto>ldap|ldaps):\/\/)?[\w\.\-]+)(?::(?<port>\d+))?';
    private const REQUIRED_PARAMS = ['server', 'type', 'bindUser', 'bindPass'];

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
    public static function fromConfig(ConfigDataInterface $configData): LdapParams
    {
        return self::fromArray(
            [
                'server' => $configData->getLdapServer(),
                'type' => $configData->getLdapType(),
                'bindUser' => $configData->getLdapBindUser(),
                'bindPass' => $configData->getLdapBindPass(),
                'searchBase' => $configData->getLdapBase(),
                'group' => $configData->getLdapGroup(),
                'tlsEnabled' => $configData->isLdapTlsEnabled(),
                'filterUserObject' => $configData->getLdapFilterUserObject(),
                'filterGroupObject' => $configData->getLdapFilterGroupObject(),
                'filterUserAttributes' => $configData->getLdapFilterUserAttributes(),
                'filterGroupAttributes' => $configData->getLdapFilterGroupAttributes(),
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    public static function fromArray(array $params): LdapParams
    {
        $validParams = count(
            array_filter(array_intersect(self::REQUIRED_PARAMS, array_keys($params)), static fn($v) => !empty($v))
        );

        if ($validParams !== count(self::REQUIRED_PARAMS)) {
            throw ValidationException::error(__u('Missing LDAP parameters'));
        }

        $data = preg_match(sprintf("#%s#i", self::REGEX_SERVER), $params['server'], $serverAndPort);

        if ($data !== 1 || empty($serverAndPort)) {
            throw ValidationException::error(__u('Wrong LDAP parameters'));
        }

        $ldapParams = new self(
            $serverAndPort['server'],
            LdapTypeEnum::tryFrom($params['type']) ?: LdapTypeEnum::STD,
            $params['bindUser'],
            $params['bindPass']
        );

        $ldapParams->searchBase = $params['searchBase'];
        $ldapParams->port = $serverAndPort['port'] ?? 389;
        $ldapParams->group = $params['group'];
        $ldapParams->tlsEnabled = $params['tlsEnabled'];
        $ldapParams->filterUserObject = $params['filterUserObject'];
        $ldapParams->filterGroupObject = $params['filterGroupObject'];
        $ldapParams->filterUserAttributes = $params['filterUserAttributes'];
        $ldapParams->filterGroupAttributes = $params['filterGroupAttributes'];

        return $ldapParams;
    }

    /**
     * @param RequestService $request
     * @return LdapParams
     * @throws ValidationException
     */
    public static function fromRequest(RequestService $request): LdapParams
    {
        return self::fromArray(
            [
                'server' => $request->analyzeString('ldap_server'),
                'type' => $request->analyzeInt('ldap_server_type'),
                'bindUser' => $request->analyzeString('ldap_binduser'),
                'bindPass' => $request->analyzeEncrypted('ldap_bindpass'),
                'searchBase' => $request->analyzeString('ldap_base'),
                'group' => $request->analyzeString('ldap_group'),
                'tlsEnabled' => $request->analyzeBool('ldap_tls_enabled', false),
                'filterUserObject' => $request->analyzeString('ldap_filter_user_object'),
                'filterGroupObject' => $request->analyzeString('ldap_filter_group_object'),
                'filterUserAttributes' => $request->analyzeArray('ldap_filter_user_attributes'),
                'filterGroupAttributes' => $request->analyzeArray('ldap_filter_group_attributes'),
            ]
        );
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
