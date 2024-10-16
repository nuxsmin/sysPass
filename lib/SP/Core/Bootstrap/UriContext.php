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

namespace SP\Core\Bootstrap;

use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Http\Ports\RequestService;

/**
 * Class UriContext
 */
final readonly class UriContext implements UriContextInterface
{
    private string $subUri;
    private string $webRoot;
    private string $webUri;

    public function __construct(RequestService $request)
    {
        $this->subUri = $this->buildSubUri($request);
        $this->webRoot = $this->buildWebRoot($request);
        $this->webUri = $request->getHttpHost() . $this->webRoot;
    }

    private function buildSubUri(RequestService $request): string
    {
        return '/' . basename($request->getServer('SCRIPT_FILENAME'));
    }

    private function buildWebRoot(RequestService $request): string
    {
        $uri = $request->getServer('REQUEST_URI');

        $pos = strpos($uri, $this->subUri);

        if ($pos > 0) {
            return substr($uri, 0, $pos);
        }

        return '';
    }

    public function getWebUri(): string
    {
        return $this->webUri;
    }

    public function getWebRoot(): string
    {
        return $this->webRoot;
    }

    public function getSubUri(): string
    {
        return $this->subUri;
    }

}
