<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\AccountFile;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Tests\IntegrationTestCase;
use SP\Tests\Generators\AccountDataGenerator;

#[Group('integration')]
final class UploadAuthorizationTest extends IntegrationTestCase
{
    public static function deniedActions(): array
    {
        return [
            'file action' => [AclActionsInterface::ACCOUNT_FILE_UPLOAD],
            'account edit action' => [AclActionsInterface::ACCOUNT_EDIT],
        ];
    }

    #[Test]
    #[DataProvider('deniedActions')]
    public function uploadRequiresBothProfileActions(int $deniedAction): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('checkUserAccess')->willReturnCallback(
            static fn(int $action): bool => $action !== $deniedAction
        );
        $files = $this->createMock(AccountFileService::class);
        $files->expects(self::never())->method('create');
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('getByIdEnriched');

        $container = $this->buildContainer(
            self::buildRequest('post', 'index.php', ['r' => 'accountFile/upload/100']),
            [AclInterface::class => $acl, AccountFileService::class => $files, AccountService::class => $accounts]
        );
        self::runApp($container);
        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to do this operation","data":null}'
        );
    }

    #[Test]
    public function uploadRejectsAnInaccessibleAccountBeforeWriting(): void
    {
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::once())->method('isVisible')->with(100)->willReturn(false);
        $accounts->expects(self::never())->method('getByIdEnriched');
        $files = $this->createMock(AccountFileService::class);
        $files->expects(self::never())->method('create');
        $container = $this->buildContainer(
            self::buildRequest('post', 'index.php', ['r' => 'accountFile/upload/100']),
            [AccountService::class => $accounts, AccountFileService::class => $files]
        );
        self::runApp($container);
        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to access this account","data":null}'
        );
    }

    #[Test]
    #[DataProvider('accountEditPermissions')]
    public function uploadRequiresAccountEditAccess(bool $canEdit, string $expected): void
    {
        $accounts = $this->createStub(AccountService::class);
        $accounts->method('isVisible')->willReturn(true);
        $accounts->method('getByIdEnriched')->willReturn(
            AccountDataGenerator::factory()->buildAccountDataView()->mutate(['id' => 100])
        );
        $accounts->method('withUsers')->willReturnArgument(0);
        $accounts->method('withUserGroups')->willReturnArgument(0);
        $permission = new AccountPermission(AclActionsInterface::ACCOUNT_EDIT);
        $permission->setCompiledAccountAccess(true)->setResultView(true)->setResultEdit($canEdit);
        $accountAcl = $this->createMock(AccountAclService::class);
        $accountAcl->expects(self::once())->method('getAcl')
            ->with(AclActionsInterface::ACCOUNT_EDIT, self::anything())->willReturn($permission);
        $files = $this->createMock(AccountFileService::class);
        $files->expects(self::never())->method('create');
        $container = $this->buildContainer(
            self::buildRequest('post', 'index.php', ['r' => 'accountFile/upload/100']),
            [AccountService::class => $accounts, AccountAclService::class => $accountAcl, AccountFileService::class => $files]
        );
        self::runApp($container);
        $this->expectOutputString($expected);
    }

    public static function accountEditPermissions(): array
    {
        return [
            'read only' => [false, '{"status":"ERROR","description":"You don\'t have permission to access this account","data":null}'],
            'edit access reaches input validation' => [true, '{"status":"ERROR","description":"INVALID QUERY","data":null}'],
        ];
    }
}
