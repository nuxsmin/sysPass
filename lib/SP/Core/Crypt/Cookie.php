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

namespace SP\Core\Crypt;

use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Http\Ports\RequestService;

use function SP\logger;

/**
 * Class Cookie
 */
abstract class Cookie
{
    protected function __construct(
        private readonly string              $cookieName,
        protected readonly RequestService $request,
        private readonly UriContextInterface $uriContext
    ) {
    }

    /**
     * Firmar la cookie para autentificación
     */
    final public function sign(string $data, string $cypher): string
    {
        $data = base64_encode($data);

        return Hash::signMessage($data, $cypher) . ';' . $data;
    }

    /**
     * Comprobar la firma de la cookie y devolver los datos
     *
     * @param string $data
     * @param string $cypher
     *
     * @return bool|string
     */
    final public function getCookieData(string $data, string $cypher): bool|string
    {
        if (!str_contains($data, ';')) {
            return false;
        }

        [$signature, $data] = explode(';', $data, 2);

        return Hash::checkMessage($data, $cypher, $signature)
            ? base64_decode($data)
            : false;
    }

    /**
     * Returns cookie raw data
     *
     * @return bool|string
     */
    protected function getCookie(): bool|string
    {
        return $this->request
            ->getRequest()
            ->cookies()
            ->get($this->cookieName, false);
    }

    /**
     * Sets cookie data
     */
    protected function setCookie(string $data): bool
    {
        if (headers_sent()) {
            logger('Headers already sent', 'ERROR');

            return false;
        }

        return setcookie($this->cookieName, $data, 0, $this->uriContext->getWebRoot());
    }
}
