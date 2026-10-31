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

namespace SP\Domain\Common\Providers;

/**
 * Class Environment
 */
final class Environment
{
    private const MIN_PHP_VERSION = 80200;
    private const MAX_PHP_VERSION = 80400;

    /**
     * Comprobar si sysPass se ejecuta en W$indows.
     */
    public static function checkIsWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Comprobar la versión de PHP.
     */
    public static function checkPhpVersion(): bool
    {
        return PHP_VERSION_ID >= self::MIN_PHP_VERSION && PHP_VERSION_ID < self::MAX_PHP_VERSION;
    }

    /**
     * Obtener el tamaño máximo de subida de PHP.
     */
    public static function getMaxUpload(): int
    {
        return min(
            self::convertShortUnit(ini_get('upload_max_filesize')),
            self::convertShortUnit(ini_get('post_max_size')),
            self::convertShortUnit(ini_get('memory_limit'))
        );
    }

    public static function convertShortUnit(string $value): int
    {
        if (preg_match('/(\d+)([KMGkmg])/', $value, $match)) {
            return match (strtoupper($match[2])) {
                'K' => (int)$match[1] * 1024,
                'M' => (int)$match[1] * (1024 ** 2),
                'G' => (int)$match[1] * (1024 ** 3)
            };
        }

        return (int)$value;
    }
}
