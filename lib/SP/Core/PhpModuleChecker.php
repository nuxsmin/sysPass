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

/**
 * Class PhpModuleChecker
 * @package SP\Core
 */
class PhpModuleChecker
{
    const MODULES = [
        'ldap',
        'curl',
        'simplexml',
        'phar',
        'json',
        'xml',
        'pdo',
        'zlib',
        'gettext',
        'openssl',
        'pcre',
        'session',
        'mcrypt',
        'gd',
        'mbstring'
    ];

    /**
     * Missing modules
     *
     * @var array
     */
    protected $missing;

    /**
     * PhpModuleChecker constructor.
     */
    public function __construct()
    {
        $this->checkModules();
    }

    /**
     * Check for missing modules
     */
    public function checkModules()
    {
        $loaded = get_loaded_extensions();

        $this->missing = array_filter(self::MODULES, function ($module) use ($loaded) {
            return !in_array($module, $loaded);
        });
    }

    /**
     * Comprobar si el módulo de LDAP está instalado.
     *
     * @return bool
     */
    public function ldapIsAvailable()
    {
        return extension_loaded('ldap');
    }
}