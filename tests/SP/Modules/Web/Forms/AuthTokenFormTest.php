<?php
declare(strict_types=1);
/*
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

namespace SP\Tests\Modules\Web\Forms;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\User\Dtos\UserDto;
use SP\Modules\Web\Forms\AuthTokenForm;
use SP\Tests\UnitaryTestCase;

/**
 * Class AuthTokenFormTest
 */
#[Group('unitary')]
class AuthTokenFormTest extends UnitaryTestCase
{
    private AclInterface|MockObject   $acl;
    private RequestService|MockObject $request;

    #[Test]
    public function nonAdminCannotCreateTokenForAnotherUser(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));

        $this->acl->expects(self::never())->method('checkUserAccess');

        $form = $this->buildForm(2, AclActionsInterface::ACCOUNT_SEARCH);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You don\'t have permission to do this operation');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function nonAdminCannotCreateTokenForActionWithoutPermission(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));

        $this->acl->expects(self::once())
                  ->method('checkUserAccess')
                  ->with(AclActionsInterface::ACCOUNT_SEARCH)
                  ->willReturn(false);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You don\'t have permission to do this operation');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function nonAdminCanCreateNonKeyTokenForOwnUserWithoutPassword(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));

        $this->acl->expects(self::once())
                  ->method('checkUserAccess')
                  ->with(AclActionsInterface::ACCOUNT_SEARCH)
                  ->willReturn(true);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertSame(1, $form->getItemData()->getUserId());
        self::assertSame(AclActionsInterface::ACCOUNT_SEARCH, $form->getItemData()->getActionId());
        self::assertNull($form->getItemData()->getHash());
    }

    #[Test]
    public function createKeyDependentActionRejectsBlankPassword(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));

        $this->acl->expects(self::once())
                  ->method('checkUserAccess')
                  ->with(AclActionsInterface::ACCOUNT_VIEW)
                  ->willReturn(true);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_VIEW, null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password cannot be blank');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function adminCanCreateKeyDependentTokenForAnotherUserWithPassword(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true));

        $this->acl->expects(self::never())->method('checkUserAccess');

        $form = $this->buildForm(2, AclActionsInterface::ACCOUNT_VIEW, 'secret-pass');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertSame(2, $form->getItemData()->getUserId());
        self::assertSame(AclActionsInterface::ACCOUNT_VIEW, $form->getItemData()->getActionId());
        self::assertSame('secret-pass', $form->getItemData()->getHash());
    }

    #[Test]
    public function createAcceptsValidExpireDate(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));
        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);

        $targetDate = date('Y-m-d', strtotime('+30 days'));
        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH, null, [
            'expire_date' => $targetDate,
            'expire_date_initial' => '__new__',
        ]);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertNotNull($form->getItemData()->getExpireDate());
        self::assertGreaterThan(time() + 29 * 86400, $form->getItemData()->getExpireDate());
        self::assertLessThanOrEqual(time() + 31 * 86400, $form->getItemData()->getExpireDate());
        self::assertFalse($form->getItemData()->getNoExpiry());
    }

    #[Test]
    public function createRejectsMalformedExpireDate(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));
        $this->acl->expects(self::never())->method('checkUserAccess');

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH, null, [
            'expire_date' => '2026-02-31',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid expiration date');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function nonAdminCannotRequestNoExpiry(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));
        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH, null, [], ['no_expiry' => true]);

        $this->expectException(UnauthorizedActionException::class);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function adminCanRequestNoExpiry(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true));

        $form = $this->buildForm(2, AclActionsInterface::ACCOUNT_SEARCH, null, [], ['no_expiry' => true]);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertTrue($form->getItemData()->getNoExpiry());
        self::assertNull($form->getItemData()->getExpireDate());
    }

    #[Test]
    public function adminCannotSubmitNoExpiryTogetherWithExpireDate(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true));

        $form = $this->buildForm(
            2,
            AclActionsInterface::ACCOUNT_SEARCH,
            null,
            ['expire_date' => date('Y-m-d', strtotime('+30 days'))],
            ['no_expiry' => true]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot specify both an expiration date and no-expiry');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function editWithUnchangedInitialExpireDatePreservesExistingExpirySignal(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));
        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);

        $initialDate = date('Y-m-d', strtotime('+45 days'));
        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH, null, [
            'expire_date' => $initialDate,
            'expire_date_initial' => $initialDate,
        ]);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_EDIT, 10);

        self::assertNull($form->getItemData()->getExpireDate());
        self::assertNull($form->getItemData()->getNoExpiry());
    }

    #[Test]
    public function editWithClearedExpireDateSignalsDefaultReset(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));
        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_SEARCH, null, [
            'expire_date' => '',
            'expire_date_initial' => date('Y-m-d', strtotime('+45 days')),
        ]);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_EDIT, 10);

        self::assertNull($form->getItemData()->getExpireDate());
        self::assertFalse($form->getItemData()->getNoExpiry());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \SP\Domain\Core\Exceptions\SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->acl = $this->createMock(AclInterface::class);
        $this->request = $this->createMock(RequestService::class);
    }

    /**
     * @param array<string, ?string> $strings
     * @param array<string, bool> $bools
     */
    private function buildForm(
        int     $userId,
        int     $actionId,
        ?string $pass = null,
        array   $strings = [],
        array   $bools = []
    ): AuthTokenForm {
        $this->request->method('analyzeBool')->willReturnCallback(
            static fn(string $param, ?bool $default = null): bool => $bools[$param] ?? ($default ?? false)
        );
        $this->request->method('analyzeInt')->willReturnCallback(
            static fn(string $param, ?int $default = null): int => match ($param) {
                'users' => $userId,
                'actions' => $actionId,
                default => $default ?? 0,
            }
        );
        $this->request->method('analyzeString')->willReturnCallback(
            static fn(string $param, ?string $default = null): ?string => $strings[$param] ?? $default
        );
        $this->request->method('analyzeEncrypted')->with('pass')->willReturn($pass);

        return new AuthTokenForm($this->config, $this->context, $this->request, $this->acl);
    }
}
