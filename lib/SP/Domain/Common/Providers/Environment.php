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
    public const MIN_PHP_VERSION = 80300;
    public const MAX_PHP_VERSION = 80600;

    /**
     * Comprobar si sysPass se ejecuta en W$indows.
     */
    public static function checkIsWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Comprobar la versión de PHP.
     *
     * @param int|null $phpVersionId PHP_VERSION_ID value, defaults to the running interpreter
     */
    public static function checkPhpVersion(?int $phpVersionId = null): bool
    {
        $phpVersionId ??= PHP_VERSION_ID;

        return $phpVersionId >= self::MIN_PHP_VERSION && $phpVersionId < self::MAX_PHP_VERSION;
    }

    /**
     * Obtener los límites de versión de PHP soportados, para mostrar en mensajes.
     *
     * The upper bound is exclusive, so the displayed maximum is the highest accepted version.
     *
     * @return array{0: string, 1: string}
     */
    public static function getSupportedPhpVersionLabels(): array
    {
        return [
            self::formatVersionId(self::MIN_PHP_VERSION),
            self::formatVersionId(self::MAX_PHP_VERSION - 1),
        ];
    }

    private static function formatVersionId(int $versionId): string
    {
        return intdiv($versionId, 10000) . '.' . intdiv($versionId % 10000, 100);
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
