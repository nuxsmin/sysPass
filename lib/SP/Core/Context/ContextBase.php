<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Context;

/**
 * Class ContextBase
 * @package SP\Core\Session
 */
abstract class ContextBase implements ContextInterface
{
    const APP_STATUS_UPDATED = 'updated';
    const APP_STATUS_RELOADED = 'reloaded';
    const APP_STATUS_INSTALLED = 'installed';
    const APP_STATUS_LOGGEDOUT = 'loggedout';

    /**
     * @var array
     */
    private $context = [];

    /**
     * @param $context
     */
    final protected function setContextReference(&$context)
    {
        $this->context =& $context;
    }

    /**
     * @param $context
     */
    final protected function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Devolver una variable de contexto
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getContextKey($key, $default = null)
    {
        if (isset($this->context[$key])) {
            return is_numeric($default) ? (int)$this->context[$key] : $this->context[$key];
        }

        return $default;
    }

    /**
     * Establecer una variable de contexto
     *
     * @param string $key El nombre de la variable
     * @param mixed $value El valor de la variable
     * @return mixed
     */
    protected function setContextKey($key, $value)
    {
        $this->context[$key] = $value;

        return $value;
    }
}