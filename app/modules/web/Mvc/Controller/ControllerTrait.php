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

namespace SP\Modules\Web\Mvc\Controller;

use Closure;
use Klein\Klein;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Header;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Providers\Uri;

use function SP\__u;
use function SP\processException;

/**
 * Trait ControllerTrait
 */
trait ControllerTrait
{
    protected Klein $router;

    /**
     * Logout from current session
     *
     * @param RequestService $request
     * @param ConfigDataInterface $configData
     * @param Closure $onRedirect
     *
     * @throws SPException
     */
    protected function sessionLogout(
        RequestService $request,
        ConfigDataInterface $configData,
        Closure        $onRedirect
    ): void {
        if ($request->isJson()) {
            $this->router->response()
                ->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value)
                ->body(json_encode([
                    'status' => 10,
                    'description' => __u('Session not started or timed out'),
                    'data' => [],
                    'messages' => [],
                ], JSON_THROW_ON_ERROR))
                ->send(true);
        } elseif ($request->isAjax()) {
            self::logout();
        } else {
            try {
                // Analyzes if there is any direct route within the URL
                // then it computes the route HMAC to build a signed URI
                // which would be used during logging in
                $route = $request->analyzeString('r');
                $hash = $request->analyzeString('h');

                $uri = new Uri($this->uriContext->getWebRoot() . $this->uriContext->getSubUri());
                $uri->addParam('_r', 'login');

                if ($route && $hash) {
                    $key = $configData->getPasswordSalt();
                    $request->verifySignature($key);

                    $uri->addParam('from', $route);

                    $onRedirect->call($this, $uri->getUriSigned($key));
                } else {
                    $onRedirect->call($this, $uri->getUri());
                }
            } catch (SPException $e) {
                processException($e);
            }
        }
    }

    /**
     * Realiza el proceso de logout.
     */
    private static function logout(): never
    {
        exit('<script>sysPassApp.actions.main.logout();</script>');
    }

    /**
     * @throws SPException
     * @deprecated
     */
    protected function checkSecurityToken(
        string         $previousToken,
        RequestService $request,
        ConfigDataInterface $configData
    ): void {
        if ($request->analyzeString('h') !== null && $request->analyzeString('from') === null) {
            $request->verifySignature($configData->getPasswordSalt());
        } else {
            $sk = $request->analyzeString('sk');

            if (!$sk || $previousToken !== $sk) {
                throw SPException::error(__u('Invalid Action'), null, 1);
            }
        }
    }
}
