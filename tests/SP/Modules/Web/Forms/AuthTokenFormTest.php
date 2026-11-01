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

        $form = $this->buildForm(2, AclActionsInterface::ACCOUNT_VIEW);

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
                  ->with(AclActionsInterface::ACCOUNT_VIEW)
                  ->willReturn(false);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_VIEW);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('You don\'t have permission to do this operation');

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);
    }

    #[Test]
    public function nonAdminCanCreateTokenForOwnUserAndAllowedAction(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false));

        $this->acl->expects(self::once())
                  ->method('checkUserAccess')
                  ->with(AclActionsInterface::ACCOUNT_VIEW)
                  ->willReturn(true);

        $form = $this->buildForm(1, AclActionsInterface::ACCOUNT_VIEW);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertSame(1, $form->getItemData()->getUserId());
        self::assertSame(AclActionsInterface::ACCOUNT_VIEW, $form->getItemData()->getActionId());
    }

    #[Test]
    public function adminCanCreateTokenForAnotherUser(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true));

        $this->acl->expects(self::never())->method('checkUserAccess');

        $form = $this->buildForm(2, AclActionsInterface::ACCOUNT_VIEW);

        $form->validateFor(AclActionsInterface::AUTHTOKEN_CREATE);

        self::assertSame(2, $form->getItemData()->getUserId());
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

    private function buildForm(int $userId, int $actionId): AuthTokenForm
    {
        $this->request->method('analyzeBool')->with('refreshtoken', false)->willReturn(false);
        $this->request->method('analyzeInt')->willReturnCallback(
            static fn(string $param, ?int $default = null): int => match ($param) {
                'users' => $userId,
                'actions' => $actionId,
                default => $default ?? 0,
            }
        );
        $this->request->method('analyzeEncrypted')->with('pass')->willReturn(null);

        return new AuthTokenForm($this->config, $this->context, $this->request, $this->acl);
    }
}
