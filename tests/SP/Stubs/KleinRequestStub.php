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

namespace SP\Tests\Stubs;

use Klein\DataCollection\DataCollection;
use Klein\DataCollection\HeaderDataCollection;
use Klein\Request;

/**
 * Klein\Request uses a method named "method", which PHPUnit 12 (12.5) refuses to
 * double. This stub exposes only the request data needed by the tests and
 * delegates every collection to an injectable instance, so mock expectations
 * on those collections are preserved.
 */
final class KleinRequestStub extends Request
{
    private int $paramsCalls = 0;
    private DataCollection $stubCookies;

    public function __construct(
        private DataCollection $stubParamsGet = new DataCollection(),
        private DataCollection $stubParamsPost = new DataCollection(),
        private DataCollection $stubServer = new DataCollection(),
        private HeaderDataCollection $stubHeaders = new HeaderDataCollection(),
        private DataCollection $stubFiles = new DataCollection(),
        DataCollection $cookies = new DataCollection(),
        private string $httpMethod = 'GET'
    ) {
        $this->stubCookies = $cookies;

        parent::__construct();
    }

    public function setMethod(string $method): void
    {
        $this->httpMethod = strtoupper($method);
    }

    public function setParamsGet(DataCollection $paramsGet): void
    {
        $this->stubParamsGet = $paramsGet;
    }

    public function setParamsPost(DataCollection $paramsPost): void
    {
        $this->stubParamsPost = $paramsPost;
    }

    public function setServer(DataCollection $server): void
    {
        $this->stubServer = $server;
    }

    public function setHeaders(HeaderDataCollection $headers): void
    {
        $this->stubHeaders = $headers;
    }

    public function setFiles(DataCollection $files): void
    {
        $this->stubFiles = $files;
    }

    public function setCookies(DataCollection $cookies): void
    {
        $this->stubCookies = $cookies;
    }

    public function paramsGet()
    {
        return $this->stubParamsGet;
    }

    public function paramsPost()
    {
        return $this->stubParamsPost;
    }

    public function server()
    {
        return $this->stubServer;
    }

    public function headers()
    {
        return $this->stubHeaders;
    }

    public function files()
    {
        return $this->stubFiles;
    }

    public function cookies()
    {
        return $this->stubCookies;
    }

    public function method($is = null, $allow_override = true)
    {
        if ($is !== null) {
            return strcasecmp($this->httpMethod, $is) === 0;
        }

        return $this->httpMethod;
    }

    /**
     * @param array<int, string>|null $mask
     * @return array<string, mixed>
     */
    public function params($mask = null, $fill_with_nulls = true)
    {
        $this->paramsCalls++;

        return [];
    }

    public function getParamsCalls(): int
    {
        return $this->paramsCalls;
    }
}
