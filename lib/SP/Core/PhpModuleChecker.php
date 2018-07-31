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
     * Available modules
     *
     * @var array
     */
    protected $available;

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

        $this->available = array_filter(self::MODULES, function ($module) use ($loaded) {
            return in_array($module, $loaded);
        });
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkCurlAvailable()
    {
        if (!$this->checkIsAvailable('curl')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'curl'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @param $module
     * @return bool
     */
    public function checkIsAvailable(string $module)
    {
        return in_array(strtolower($module), $this->available);
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkLdapAvailable()
    {
        if (!$this->checkIsAvailable('ldap')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'ldap'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkSimpleXmlAvailable()
    {
        if (!$this->checkIsAvailable('simplexml')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'simplexml'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkPharAvailable()
    {
        if (!$this->checkIsAvailable('phar')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'phar'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkJsonAvailable()
    {
        if (!$this->checkIsAvailable('json')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'json'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkPdoAvailable()
    {
        if (!$this->checkIsAvailable('pdo')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'pdo'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkGettextAvailable()
    {
        if (!$this->checkIsAvailable('gettext')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'gettext'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkOpenSslAvailable()
    {
        if (!$this->checkIsAvailable('openssl')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'openssl'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkGdAvailable()
    {
        if (!$this->checkIsAvailable('gd')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'gd'));
        }
    }

    /**
     * Comprobar si el módulo está instalado.
     *
     * @throws CheckException
     */
    public function checkMbstringAvailable()
    {
        if (!$this->checkIsAvailable('mbstring')) {
            throw new CheckException(sprintf(__('Módulo \'%s\' no disponible'), 'mbstring'));
        }
    }
}