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

namespace SP\Domain\Auth\Providers\Browser;

use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Providers\AuthService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\Ports\RequestService;

/**
 * Class Browser
 *
 * Autentificación basada en credenciales del navegador
 *
 * @implements AuthService<BrowserAuthData>
 */
final class BrowserAuth implements BrowserAuthService
{
    private ConfigDataInterface $configData;
    private RequestService $request;

    public function __construct(ConfigDataInterface $configData, RequestService $request)
    {
        $this->configData = $configData;
        $this->request = $request;
    }

    /**
     * Authenticate using user's data
     *
     * @param UserLoginDto $userLoginDto
     * @return BrowserAuthData
     */
    public function authenticate(UserLoginDto $userLoginDto): BrowserAuthData
    {
        $browserAuthData = new BrowserAuthData($this->isAuthGranted());

        if (!empty($userLoginDto->getLoginUser())
            && !empty($userLoginDto->getLoginPass())
            && $this->checkServerAuthUser($userLoginDto->getLoginUser())
        ) {
            return $browserAuthData->success();
        }

        if ($this->configData->isAuthBasicAutoLoginEnabled()) {
            $authUser = $this->getServerAuthUser();
            $authPass = $this->getAuthPass();

            if ($authUser !== null && $authPass !== null) {
                $userLoginDto->setLoginUser($authUser);
                $userLoginDto->setLoginPass($authPass);

                $browserAuthData->setName($authUser);

                return $browserAuthData->success();
            }

            return $browserAuthData->fail();
        }

        return $this->checkServerAuthUser($userLoginDto->getLoginUser())
            ? $browserAuthData->success()
            : $browserAuthData->fail();
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return bool
     */
    public function isAuthGranted(): bool
    {
        return $this->configData->isAuthBasicAutoLoginEnabled();
    }

    /**
     * Comprobar si el usuario es autentificado por el servidor web
     *
     * @param $login string El login del usuario a comprobar
     *
     * @return bool|null
     */
    public function checkServerAuthUser(string $login): ?bool
    {
        $domain = $this->configData->getAuthBasicDomain() ?? '';
        $authUser = $this->getServerAuthUser();

        if (empty($authUser)) {
            return null;
        }

        if (preg_match('/\w+@\w+/', $authUser)) {
            return sprintf('%s@%s', $login, $domain) === $authUser;
        }

        return $authUser === $login;
    }

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string|null
     */
    public function getServerAuthUser(): ?string
    {
        $authUser = $this->request->getServer('PHP_AUTH_USER');

        if (!empty($authUser)) {
            return $authUser;
        }

        $remoteUser = $this->request->getServer('REMOTE_USER');

        if (!empty($remoteUser)) {
            return $remoteUser;
        }

        return null;
    }

    /**
     * Devolver la clave del usuario autentificado por el servidor web
     *
     * @return string|null
     */
    protected function getAuthPass(): ?string
    {
        $authPass = $this->request->getServer('PHP_AUTH_PW');

        return !empty($authPass) ? $authPass : null;
    }
}
