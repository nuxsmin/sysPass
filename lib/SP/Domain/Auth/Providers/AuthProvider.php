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

namespace SP\Domain\Auth\Providers;

use SP\Core\Application;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Providers\Provider;
use SP\Domain\User\Dtos\UserDataDto;
use SplObjectStorage;

use function SP\__u;

defined('APP_ROOT') || die();

/**
 * Class AuthProvider
 *
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
final class AuthProvider extends Provider implements AuthProviderService
{
    /**
     * @var SplObjectStorage<AuthService,AuthType>
     */
    protected readonly SplObjectStorage $auths;

    public function __construct(Application $application)
    {
        parent::__construct($application);

        $this->auths = new SplObjectStorage();
    }

    /**
     * Register authentication methods
     *
     * @param AuthService $auth
     * @param AuthType $authTypeEnum
     * @throws AuthException
     */
    public function registerAuth(AuthService $auth, AuthType $authTypeEnum): void
    {
        if ($this->auths->contains($auth)) {
            throw AuthException::error(__u('Authentication already initialized'), $auth::class);
        }

        $this->auths->attach($auth, $authTypeEnum);
    }

    /**
     * @inheritDoc
     */
    public function doAuth(UserLoginDto $userLoginData, callable $callback): ?UserDataDto
    {
        $this->auths->rewind();

        while ($this->auths->valid()) {
            $authResult = new AuthResult(
                $this->auths->getInfo(),
                $this->auths->current()->authenticate($userLoginData)
            );

            $callback($authResult);

            $authData = $authResult->getAuthData();

            if ($authData->isAuthoritative() && $authData->isOk()) {
                return $authData->getUserDataDto();
            }

            $this->auths->next();
        }

        return null;
    }
}
