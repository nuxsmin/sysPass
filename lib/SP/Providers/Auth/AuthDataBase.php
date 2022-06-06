<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Auth;

/**
 * Class AuthData
 *
 * @package Auth
 */
abstract class AuthDataBase
{
    /**
     * @var string
     */
    protected string $name;
    /**
     * @var string
     */
    protected string $email;
    /**
     * @var bool
     */
    protected ?bool $authenticated;
    /**
     * @var int
     */
    protected int $statusCode = 0;
    /**
     * @var string
     */
    protected string $server;
    /**
     * @var bool
     */
    protected bool $authoritative = false;
    /**
     * @var bool
     */
    protected bool $failed = false;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param  string  $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return int|null
     */
    public function getAuthenticated(): ?int
    {
        return $this->authenticated;
    }

    /**
     * @param  bool  $authenticated
     *
     * @return $this
     */
    public function setAuthenticated(?bool $authenticated = null): AuthDataBase
    {
        $this->authenticated = $authenticated;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getServer(): ?string
    {
        return $this->server;
    }

    /**
     * @param  string  $server
     */
    public function setServer(string $server)
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return (int)$this->statusCode;
    }

    /**
     * @param  int  $statusCode
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return bool
     */
    public function isAuthoritative(): bool
    {
        return $this->authoritative;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @param  bool  $authoritative
     */
    public function setAuthoritative(bool $authoritative): void
    {
        $this->authoritative = $authoritative;
    }

    /**
     * @return bool|null
     */
    public function isFailed(): ?bool
    {
        return $this->failed;
    }

    /**
     * @param  bool  $failed
     */
    public function setFailed(bool $failed)
    {
        $this->failed = $failed;
    }
}