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

namespace SP\Core;

use SP\Core\Exceptions\CheckException;

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
    const EXTENSIONS = [
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
        'mcrypt' => false,
        'gd' => false,
        'mbstring' => true,
        'pdo_mysql' => true,
        'fileinfo' => true
    ];

    const MSG_NOT_AVAILABLE = 'Oops, it seems that some extensions are not available: \'%s\'';

    /**
     * Available extensions
     *
     * @var array
     */
    private $available;

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
    public function checkExtensions()
    {
        $this->available = array_intersect(array_keys(self::EXTENSIONS), array_map('strtolower', get_loaded_extensions()));
    }

    /**
     * Checks if the extension is installed
     *
     * @param bool $exception
     *
     * @return bool
     * @throws CheckException
     */
    public function checkCurlAvailable($exception = false)
    {
        return $this->checkIsAvailable('curl', $exception);
    }

    /**
     * Checks if the extension is installed
     *
     * @param string $extension
     * @param bool   $exception Throws an exception if the extension is not available
     *
     * @return bool
     * @throws CheckException
     */
    public function checkIsAvailable(string $extension, $exception = false)
    {
        $result = in_array(strtolower($extension), $this->available);

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
    public function checkLdapAvailable($exception = false)
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
    public function checkSimpleXmlAvailable($exception = false)
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
    public function checkXmlAvailable($exception = false)
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
    public function checkPharAvailable($exception = false)
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
    public function checkJsonAvailable($exception = false)
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
    public function checkPdoAvailable($exception = false)
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
    public function checkGettextAvailable($exception = false)
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
    public function checkOpenSslAvailable($exception = false)
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
    public function checkGdAvailable($exception = false)
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
    public function checkMbstringAvailable($exception = false)
    {
        return $this->checkIsAvailable('mbstring', $exception);
    }

    /**
     * @throws CheckException
     */
    public function checkMandatory()
    {
        $missing = array_filter(self::EXTENSIONS, function ($v, $k) {
            return $v === true && !in_array($k, $this->available);
        }, ARRAY_FILTER_USE_BOTH);

        if (count($missing) > 0) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, implode(',', array_keys($missing))));
        }

        logger('Extensions checked', 'INFO');
    }

    /**
     * Returns missing extensions
     *
     * @return array
     */
    public function getMissing()
    {
        return array_filter(self::EXTENSIONS, function ($k) {
            return !in_array($k, $this->available);
        }, ARRAY_FILTER_USE_KEY);
    }
}