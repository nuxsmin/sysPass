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

namespace SP\Core\Context;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Crypt\Ports\MasterKeyStore;

use function SP\__u;
use function SP\logger;

/**
 * Master key store backed by the transient keys of a stateless context.
 */
final class StatelessMasterKeyStore implements MasterKeyStore
{
    public function __construct(private readonly Context $context)
    {
    }

    /**
     * @throws ServiceException
     */
    public function get(): string
    {
        try {
            $key = $this->context->getTrasientKey(Context::MASTER_PASSWORD_KEY);
        } catch (ContextException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while retrieving master password from context'));
        }

        if (empty($key)) {
            throw new ServiceException(__u('Error while retrieving master password from context'));
        }

        return $key;
    }

    /**
     * @throws ServiceException
     */
    public function set(string $masterKey): void
    {
        try {
            $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, $masterKey);
        } catch (ContextException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while setting master password in context'));
        }
    }

    /**
     * @throws ServiceException
     */
    public function clear(): void
    {
        try {
            $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, null);
        } catch (ContextException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while setting master password in context'));
        }
    }
}
