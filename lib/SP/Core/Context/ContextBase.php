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

namespace SP\Core\Context;

/**
 * Class ContextBase
 *
 * @package SP\Core\Session
 */
abstract class ContextBase implements ContextInterface
{
    const APP_STATUS_UPDATED = 'updated';
    const APP_STATUS_RELOADED = 'reloaded';
    const APP_STATUS_INSTALLED = 'installed';
    const APP_STATUS_LOGGEDOUT = 'loggedout';

    /**
     * @var ContextCollection
     */
    private $context;
    /**
     * @var ContextCollection
     */
    private $trasient;

    /**
     * ContextBase constructor.
     */
    public function __construct()
    {
        $this->trasient = new ContextCollection();
    }

    /**
     * Sets an arbitrary key in the trasient collection.
     * This key is not bound to any known method or type
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     * @throws ContextException
     */
    public function setTrasientKey(string $key, $value)
    {
        // If the key starts with "_" it's a protected key, thus cannot be overwritten
        if (strpos($key, '_') === 0
            && $this->trasient->exists($key)
            && $this->trasient->get($key) !== $value
        ) {
            throw new ContextException(__u('Unable to change password value'));
        }

        $this->trasient->set($key, $value);

        return $value;
    }

    /**
     * Gets an arbitrary key from the trasient collection.
     * This key is not bound to any known method or type
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getTrasientKey(string $key, $default = null)
    {
        return is_numeric($default) ? (int)$this->trasient->get($key, $default) : $this->trasient->get($key, $default);
    }

    /**
     * @param $context
     *
     * @throws ContextException
     */
    final protected function setContextReference(&$context)
    {
        if ($this->context !== null) {
            throw new ContextException(__u('Context already initialized'));
        }

        if (isset($context['context'])
            && ($context['context'] instanceof ContextCollection) === false
        ) {
            throw new ContextException(__u('Invalid context'));
        } elseif (!isset($context['context'])) {
            $context['context'] = $this->context = new ContextCollection();
            return;
        }

        $this->context =& $context['context'];
    }

    /**
     * @param ContextCollection $contextCollection
     *
     * @throws ContextException
     */
    final protected function setContext(ContextCollection $contextCollection)
    {
        if ($this->context !== null) {
            throw new ContextException(__u('Context already initialized'));
        }

        $this->context = $contextCollection;
    }

    /**
     * Devolver una variable de contexto
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     * @throws ContextException
     */
    protected function getContextKey(string $key, $default = null)
    {
        $this->checkContext();

        return is_numeric($default) ? (int)$this->context->get($key, $default) : $this->context->get($key, $default);
    }

    /**
     * @throws ContextException
     */
    private function checkContext()
    {
        if ($this->context === null) {
            throw new ContextException(__u('Context not initialized'));
        }
    }

    /**
     * Establecer una variable de contexto
     *
     * @param string $key   El nombre de la variable
     * @param mixed  $value El valor de la variable
     *
     * @return mixed
     * @throws ContextException
     */
    protected function setContextKey(string $key, $value)
    {
        $this->checkContext();

        $this->context->set($key, $value);

        return $value;
    }
}