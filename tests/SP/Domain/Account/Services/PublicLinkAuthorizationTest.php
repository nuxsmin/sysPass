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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Services\PublicLinkAuthorization;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;

/**
 * Unit tests for the public link account authorization guard.
 */
#[Group('unitary')]
final class PublicLinkAuthorizationTest extends TestCase
{
    private AccountService&MockObject $accountService;
    private AclInterface&MockObject $acl;
    private PublicLinkAuthorization $authorization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = $this->createMock(AccountService::class);
        $this->acl = $this->createMock(AclInterface::class);
        $this->authorization = new PublicLinkAuthorization($this->accountService, $this->acl);
    }

    #[Test]
    public function allowsAVisibleAccountWithPasswordViewPermission(): void
    {
        $this->accountService
            ->expects(self::once())
            ->method('isVisible')
            ->with(10)
            ->willReturn(true);

        $this->acl
            ->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW_PASS)
            ->willReturn(true);

        $this->authorization->check(10);
    }

    #[Test]
    public function rejectsAnInvisibleAccount(): void
    {
        $this->accountService
            ->expects(self::once())
            ->method('isVisible')
            ->with(10)
            ->willReturn(false);

        $this->acl->expects(self::never())->method('checkUserAccess');

        $this->expectException(AccountPermissionException::class);

        $this->authorization->check(10);
    }

    #[Test]
    public function rejectsWithoutPasswordViewPermission(): void
    {
        $this->accountService
            ->expects(self::once())
            ->method('isVisible')
            ->with(10)
            ->willReturn(true);

        $this->acl
            ->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW_PASS)
            ->willReturn(false);

        $this->expectException(AccountPermissionException::class);

        $this->authorization->check(10);
    }
}
