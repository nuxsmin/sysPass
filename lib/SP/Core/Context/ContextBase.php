<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Context;

use SP\Domain\Core\Context\ContextInterface;

use function SP\__u;

/**
 * Class ContextBase
 *
 * @package SP\Core\Session
 */
abstract class ContextBase implements ContextInterface
{
    public const APP_STATUS_RELOADED  = 'reloaded';
    public const APP_STATUS_LOGGEDOUT = 'loggedout';

    private ?ContextCollection $context = null;
    private ContextCollection  $trasient;

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
     * @throws ContextException
     */
    public function setTrasientKey(string $key, mixed $value)
    {
        // If the key starts with "_" it's a protected key, thus cannot be overwritten
        if (str_starts_with($key, '_')
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
     */
    public function getTrasientKey(string $key, mixed $default = null): mixed
    {
        return is_numeric($default) ?
            (int)$this->trasient->get($key, $default)
            : $this->trasient->get($key, $default);
    }

    public function isInitialized(): bool
    {
        return $this->context !== null;
    }

    /**
     * @throws ContextException
     */
    final protected function setContextReference(&$context): void
    {
        if ($this->context !== null) {
            throw new ContextException(__u('Context already initialized'));
        }

        if (isset($context['context'])
            && ($context['context'] instanceof ContextCollection) === false
        ) {
            throw new ContextException(__u('Invalid context'));
        }

        if (!isset($context['context'])) {
            $context['context'] = $this->context = new ContextCollection();

            return;
        }

        $this->context =& $context['context'];
    }

    /**
     * @throws ContextException
     */
    final protected function setContext(ContextCollection $contextCollection): void
    {
        if ($this->context !== null) {
            throw new ContextException(__u('Context already initialized'));
        }

        $this->context = $contextCollection;
    }

    /**
     * Devolver una variable de contexto
     *
     * @throws ContextException
     */
    protected function getContextKey(string $key, mixed $default = null)
    {
        $this->checkContext();


        return is_numeric($default)
            ? (int)$this->context->get($key, $default)
            : $this->context->get($key, $default);
    }

    /**
     * @throws ContextException
     */
    private function checkContext(): void
    {
        if ($this->context === null) {
            throw new ContextException(__u('Context not initialized'));
        }
    }

    /**
     * Establecer una variable de contexto
     *
     * @param string $key El nombre de la variable
     * @param mixed $value El valor de la variable
     *
     * @throws ContextException
     */
    protected function setContextKey(string $key, mixed $value): mixed
    {
        $this->checkContext();

        $this->context->set($key, $value);

        return $value;
    }
}
