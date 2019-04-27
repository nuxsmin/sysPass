<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Util;

use SP\Services\Install\Installer;

/**
 * Class VersionUtil
 *
 * @package SP\Util
 */
final class VersionUtil
{
    /**
     * Devolver versión normalizada en cadena
     *
     * @return string
     */
    public static function getVersionStringNormalized()
    {
        return implode('', Installer::VERSION) . '.' . Installer::BUILD;
    }

    /**
     * Compare versions
     *
     * @param string       $currentVersion
     * @param array|string $upgradeableVersion
     *
     * @return bool True if $currentVersion is lower than $upgradeableVersion
     */
    public static function checkVersion($currentVersion, $upgradeableVersion)
    {
        if (is_array($upgradeableVersion)) {
            $upgradeableVersion = array_pop($upgradeableVersion);
        }

        $currentVersion = self::normalizeVersionForCompare($currentVersion);
        $upgradeableVersion = self::normalizeVersionForCompare($upgradeableVersion);

        if (empty($currentVersion) || empty($upgradeableVersion)) {
            return false;
        }

        if (PHP_INT_SIZE > 4) {
            return version_compare($currentVersion, $upgradeableVersion) === -1;
        }

        list($currentVersion, $build) = explode('.', $currentVersion, 2);
        list($upgradeVersion, $upgradeBuild) = explode('.', $upgradeableVersion, 2);

        $versionRes = (int)$currentVersion < (int)$upgradeVersion;

        return (($versionRes && (int)$upgradeBuild === 0)
            || ($versionRes && (int)$build < (int)$upgradeBuild));
    }

    /**
     * Devuelve una versión normalizada para poder ser comparada
     *
     * @param string $versionIn
     *
     * @return string
     */
    public static function normalizeVersionForCompare($versionIn)
    {
        if (is_string($versionIn) && !empty($versionIn)) {
            list($version, $build) = explode('.', $versionIn);

            $nomalizedVersion = 0;

            foreach (str_split($version) as $key => $value) {
                $nomalizedVersion += (int)$value * (10 ** (3 - $key));
            }

            return $nomalizedVersion . '.' . $build;
        }

        return '';
    }

    /**
     * @param string $version
     *
     * @return float|int
     */
    public static function versionToInteger(string $version)
    {
        $intVersion = 0;

        foreach (str_split(str_replace('.', '', $version)) as $key => $value) {
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
    public static function getVersionArray($retBuild = false)
    {
        $version = array_values(Installer::VERSION);

        if ($retBuild === true) {
            $version[] = Installer::BUILD;

            return $version;
        }

        return $version;
    }

    /**
     * Devolver versión normalizada en array
     *
     * @return array
     */
    public static function getVersionArrayNormalized()
    {
        return [implode('', Installer::VERSION), Installer::BUILD];
    }
}