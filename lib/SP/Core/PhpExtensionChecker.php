<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
class PhpExtensionChecker
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
     * @throws CheckException
     */
    public function checkCurlAvailable()
    {
        if (!$this->checkIsAvailable('curl')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'curl'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @param $extension
     *
     * @return bool
     */
    public function checkIsAvailable(string $extension)
    {
        return in_array(strtolower($extension), $this->available);
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkLdapAvailable()
    {
        if (!$this->checkIsAvailable('ldap')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'ldap'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkSimpleXmlAvailable()
    {
        if (!$this->checkIsAvailable('simplexml')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'simplexml'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkXmlAvailable()
    {
        if (!$this->checkIsAvailable('xml')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'xml'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkPharAvailable()
    {
        if (!$this->checkIsAvailable('phar')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'phar'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkJsonAvailable()
    {
        if (!$this->checkIsAvailable('json')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'json'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkPdoAvailable()
    {
        if (!$this->checkIsAvailable('pdo')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'pdo'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkGettextAvailable()
    {
        if (!$this->checkIsAvailable('gettext')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'gettext'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkOpenSslAvailable()
    {
        if (!$this->checkIsAvailable('openssl')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'openssl'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkGdAvailable()
    {
        if (!$this->checkIsAvailable('gd')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'gd'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @throws CheckException
     */
    public function checkMbstringAvailable()
    {
        if (!$this->checkIsAvailable('mbstring')) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, 'mbstring'));
        }
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
}