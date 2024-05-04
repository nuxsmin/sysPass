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

use SP\Domain\Install\Services\Installer;

/**
 * Class Version
 */
final class Version
{
    /**
     * Devolver versión normalizada en cadena
     */
    public static function getVersionStringNormalized(): string
    {
        return implode('', Installer::VERSION) . '.' . Installer::BUILD;
    }

    /**
     * Compare versions
     *
     * @param string $version1
     * @param array|string $version2 A version or a list of comparable versions
     *
     * @return bool True if $version1 is lower than $version2
     */
    public static function checkVersion(string $version1, array|string $version2): bool
    {
        if (is_array($version2)) {
            $version2 = array_pop($version2);
        }

        $version1 = self::normalizeVersionForCompare($version1);
        $version2 = self::normalizeVersionForCompare($version2);

        if ($version1 === null || $version2 === null) {
            return false;
        }

        if (PHP_INT_SIZE > 4) {
            return version_compare($version1, $version2) === -1;
        }

        [$versionOut1, $currentBuild] = explode('.', $version1, 2);
        [$versionOut2, $upgradeBuild] = explode('.', $version2, 2);

        $versionResult = (int)$versionOut1 < (int)$versionOut2;

        return (($versionResult && (int)$upgradeBuild === 0)
                || ($versionResult && (int)$currentBuild < (int)$upgradeBuild));
    }

    /**
     * Return a normalized version string to be compared
     *
     * @param array|string|null $versionIn
     *
     * @return string|null
     */
    public static function normalizeVersionForCompare(array|string|null $versionIn): ?string
    {
        if (!empty($versionIn)) {
            if (is_string($versionIn)) {
                [$version, $build] = explode('.', $versionIn);
            } elseif (is_array($versionIn) && count($versionIn) === 4) {
                $version = implode('', array_slice($versionIn, 0, 3));
                $build = $versionIn[3];
            } else {
                return '';
            }

            $nomalizedVersion = 0;

            foreach (str_split($version) as $key => $value) {
                $nomalizedVersion += (int)$value * (10 ** (3 - $key));
            }

            return $nomalizedVersion . '.' . $build;
        }

        return null;
    }

    /**
     * @param string $version
     *
     * @return float|int
     */
    public static function versionToInteger(string $version): float|int
    {
        $intVersion = 0;

        $strSplit = str_split(str_replace('.', '', $version));

        foreach ($strSplit as $key => $value) {
            $intVersion += (int)$value * (10 ** (3 - $key));
        }

        return $intVersion;
    }

    /**
     * Devuelve la versión de sysPass.
     *
     * @param bool $retBuild devolver el número de compilación
     *
     * @return array con el número de versión
     */
    public static function getVersionArray(bool $retBuild = false): array
    {
        $version = array_values(Installer::VERSION);

        if ($retBuild === true) {
            $version[] = Installer::BUILD;

            return $version;
        }

        return $version;
    }
}
