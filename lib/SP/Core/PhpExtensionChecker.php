<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

use SP\Core\Exceptions\CheckException;

use function SP\logger;

/**
 * Class PhpExtensionChecker
 *
 * @package SP\Core
 */
final class PhpExtensionChecker
{
    /**
     * Array of extensions needed by sysPass.
     *
     * true  -> required
     * false -> not required
     */
    public const EXTENSIONS = [
        'ldap' => false,
        'curl' => false,
        'simplexml' => false,
        'libxml' => true,
        'phar' => false,
        'json' => true,
        'xml' => true,
        'pdo' => true,
        'zlib' => false,
        'gettext' => true,
        'openssl' => true,
        'pcre' => true,
        'session' => true,
        'gd' => false,
        'mbstring' => true,
        'pdo_mysql' => true,
        'fileinfo' => true
    ];

    public const MSG_NOT_AVAILABLE = 'Oops, it seems that some extensions are not available: \'%s\'';

    /**
     * Available extensions
     */
    private ?array $available = null;

    /**
     * PhpExtensionChecker constructor.
     */
    public function __construct()
    {
        $this->checkExtensions();
    }

    /**
     * Check for available extensions
     */
    public function checkExtensions(): void
    {
        $this->available = array_intersect(
            array_keys(self::EXTENSIONS),
            array_map('strtolower', get_loaded_extensions())
        );
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkCurlAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('curl', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param string $extension
     * @param bool $exception Throws an exception if the extension is not available
     *
     * @return bool
     * @throws CheckException
     */
    public function checkIsAvailable(
        string $extension,
        bool   $exception = false
    ): bool {
        $result = in_array(strtolower($extension), $this->available, true);

        if (!$result && $exception) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, $extension));
        }

        return $result;
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkLdapAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('ldap', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkSimpleXmlAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('simplexml', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkXmlAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('xml', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkPharAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('phar', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkJsonAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('json', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkPdoAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('pdo', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkGettextAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('gettext', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkOpenSslAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('openssl', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkGdAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('gd', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkMbstringAvailable(bool $exception = false): bool
    {
        return $this->checkIsAvailable('mbstring', $exception);
    }

    /**
     * @throws CheckException
     */
    public function checkMandatory(): void
    {
        $missing = array_filter(
            self::EXTENSIONS,
            function ($v, $k) {
                return $v === true && !in_array($k, $this->available, true);
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($missing) > 0) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, implode(',', array_keys($missing))));
        }

        logger('Extensions checked', 'INFO');
    }

    /**
     * Returns missing extensions
     */
    public function getMissing(): array
    {
        return array_filter(
            self::EXTENSIONS,
            function ($k) {
                return !in_array($k, $this->available, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
