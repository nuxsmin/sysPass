<?php
/*
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

namespace SP\Http;

use SP\Core\Crypt\Hash;

/**
 * Class Uri
 */
final class Uri
{
    private array $params = [];

    public function __construct(private readonly string $base)
    {
    }

    private static function mapParts(string $key, string $value): string
    {
        if (str_starts_with($key, '_')) {
            $key = substr($key, 1);
        }

        return sprintf('%s=%s', $key, urlencode($value));
    }

    /**
     * @param string $param Param's name. If an '_' is set at the beginning, it will be a protected param
     * @param int|string $value
     *
     * @return Uri
     */
    public function addParam(string $param, int|string $value): Uri
    {
        $this->params[$param] = (string)$value;

        return $this;
    }

    /**
     * @param array $params Param's name. If an '_' is set at the beginning, it will be a protected param
     *
     * @return Uri
     */
    public function addParams(array $params): Uri
    {
        $this->params = array_map(static fn(int|string $value) => (string)$value, $params);

        return $this;
    }

    public function getUri(): string
    {
        return sprintf(
            '%s?%s',
            $this->base,
            implode('&', array_map([__CLASS__, 'mapParts'], array_keys($this->params), $this->params))
        );
    }

    public function getUriSigned(string $key): string
    {
        $uri = implode('&', array_map([__CLASS__, 'mapParts'], array_keys($this->params), $this->params));

        return sprintf('%s?%s&h=%s', $this->base, $uri, Hash::signMessage($uri, $key));
    }
}
