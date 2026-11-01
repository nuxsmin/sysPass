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

namespace SP\Domain\Account\Services;

use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkAuthorizationService;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Enforces account visibility and password-view permission before a public
 * link exposes an account.
 */
final readonly class PublicLinkAuthorization implements PublicLinkAuthorizationService
{
    public function __construct(
        private AccountService $accountService,
        private AclInterface $acl
    ) {
    }

    /**
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function check(int $accountId): void
    {
        if (!$this->accountService->isVisible($accountId)
            || !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS)
        ) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }
}
