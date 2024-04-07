<?php
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

namespace SP\Providers\Auth;

use SP\Core\Application;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Providers\Provider;
use SplObjectStorage;

use function SP\__u;

defined('APP_ROOT') || die();

/**
 * Class Auth
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 *
 * @package SP\Providers\Auth
 */
class AuthProvider extends Provider implements AuthProviderInterface
{
    /**
     * @var SplObjectStorage<AuthInterface>
     */
    protected readonly SplObjectStorage $auths;

    public function __construct(Application $application)
    {
        parent::__construct($application);

        $this->auths = new SplObjectStorage();
    }

    /**
     * Auth initializer
     *
     */
    public function initialize(): void
    {
    }

    /**
     * Register authentication methods
     *
     * @param AuthInterface $auth
     * @param AuthTypeEnum $authTypeEnum
     * @throws AuthException
     */
    public function registerAuth(AuthInterface $auth, AuthTypeEnum $authTypeEnum): void
    {
        if ($this->auths->contains($auth)) {
            throw new AuthException(
                __u('Authentication already initialized'),
                SPException::ERROR,
                $auth::class
            );
        }

        $this->auths->attach($auth, $authTypeEnum->value);
    }

    /**
     * Probar los métodos de autentificación
     *
     * @param UserLoginDto $userLoginData
     *
     * @return false|AuthResult[]
     */
    public function doAuth(UserLoginDto $userLoginData): array|bool
    {
        $authsResult = [];

        $this->auths->rewind();

        while ($this->auths->valid()) {
            $auth = $this->auths->current();
            $authName = $this->auths->getInfo();

            $authsResult[] = new AuthResult($authName, $auth->authenticate($userLoginData));

            $this->auths->next();
        }

        return count($authsResult) > 0 ? $authsResult : false;
    }
}
