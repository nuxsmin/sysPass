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
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Authorization matrix for the account file routes that work on existing files
 * (list, view, download, delete and batch delete).
 */
#[Group('integration')]
final class AccountFileRoutesAuthorizationTest extends IntegrationTestCase
{
    private const NO_OPERATION = 'You don\'t have permission to do this operation';
    private const NO_ACCOUNT = 'You don\'t have permission to access this account';

    /**
     * route name => [method, route, post params, json response, file action, requires account edit]
     */
    public static function routes(): array
    {
        return [
            'list' => ['get', 'accountFile/list/100', [], false, AclActionsInterface::ACCOUNT_FILE_LIST, false],
            'view' => ['get', 'accountFile/view/55', [], true, AclActionsInterface::ACCOUNT_FILE_VIEW, false],
            'download' => ['get', 'accountFile/download/55', [], false, AclActionsInterface::ACCOUNT_FILE_DOWNLOAD, false],
            'delete' => ['post', 'accountFile/delete/55', [], true, AclActionsInterface::ACCOUNT_FILE_DELETE, true],
            'batch delete' => [
                'post',
                'accountFile/delete',
                ['items' => [55, 56]],
                true,
                AclActionsInterface::ACCOUNT_FILE_DELETE,
                true
            ],
        ];
    }

    public static function fileIdRoutes(): array
    {
        return array_filter(self::routes(), static fn(string $name): bool => $name !== 'list', ARRAY_FILTER_USE_KEY);
    }

    public static function deniedProfileActions(): array
    {
        $cases = [];

        foreach (self::routes() as $name => $route) {
            $cases[$name . ' without files permission'] = [...$route, $route[4]];

            if ($route[5]) {
                $cases[$name . ' without account edit permission'] = [...$route, AclActionsInterface::ACCOUNT_EDIT];
            }
        }

        return $cases;
    }

    public static function deleteRoutes(): array
    {
        return array_filter(self::routes(), static fn(array $route): bool => $route[5]);
    }

    public static function readRoutes(): array
    {
        return array_filter(self::routes(), static fn(array $route): bool => !$route[5]);
    }

    #[Test]
    #[DataProvider('deniedProfileActions')]
    public function routeRequiresTheProfilePermissionsBeforeTouchingAnything(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit,
        int $deniedAction
    ): void {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('checkUserAccess')->willReturnCallback(
            static fn(int $action): bool => $action !== $deniedAction
        );

        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('isVisible');

        $this->dispatch($method, $route, $post, [
            AclInterface::class => $acl,
            AccountService::class => $accounts,
            AccountFileService::class => $this->untouchedFiles([55 => 100, 56 => 100]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_OPERATION));
    }

    #[Test]
    #[DataProvider('routes')]
    public function routeRejectsAnInvisibleAccount(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit
    ): void {
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::atLeastOnce())->method('isVisible')->willReturn(false);
        $accounts->expects(self::never())->method('getByIdEnriched');

        $accountAcl = $this->createMock(AccountAclService::class);
        $accountAcl->expects(self::never())->method('getAcl');

        $this->dispatch($method, $route, $post, [
            AccountService::class => $accounts,
            AccountAclService::class => $accountAcl,
            AccountFileService::class => $this->untouchedFiles([55 => 100, 56 => 100]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_ACCOUNT));
    }

    #[Test]
    #[DataProvider('deleteRoutes')]
    public function deleteRoutesRequireEditAccessOnTheAccount(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit
    ): void {
        // Read only access is never enough to delete
        $accountAcl = $this->createMock(AccountAclService::class);
        $accountAcl->expects(self::atLeastOnce())
                   ->method('getAcl')
                   ->with(AclActionsInterface::ACCOUNT_EDIT, self::anything())
                   ->willReturn($this->permission(AclActionsInterface::ACCOUNT_EDIT, true, false));

        $this->dispatch($method, $route, $post, [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $accountAcl,
            AccountFileService::class => $this->untouchedFiles([55 => 100, 56 => 100]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_ACCOUNT));
    }

    #[Test]
    #[DataProvider('readRoutes')]
    public function readRoutesRequireViewAccessOnTheAccount(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit
    ): void {
        $accountAcl = $this->createMock(AccountAclService::class);
        $accountAcl->expects(self::atLeastOnce())
                   ->method('getAcl')
                   ->with(AclActionsInterface::ACCOUNT_VIEW, self::anything())
                   ->willReturn($this->permission(AclActionsInterface::ACCOUNT_VIEW, false, false));

        $this->dispatch($method, $route, $post, [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $accountAcl,
            AccountFileService::class => $this->untouchedFiles([55 => 100, 56 => 100]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_ACCOUNT));
    }

    #[Test]
    #[DataProvider('fileIdRoutes')]
    public function fileIdFromAnInaccessibleAccountIsRejectedEvenIfAnotherAccountIsAccessible(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit
    ): void {
        // The file ids belong to account 200, the user can only see account 100
        $accounts = $this->createMock(AccountService::class);
        $accounts->method('isVisible')->willReturnCallback(static fn(int $id): bool => $id === 100);
        $accounts->expects(self::never())->method('getByIdEnriched');

        $this->dispatch($method, $route, $post, [
            AccountService::class => $accounts,
            AccountFileService::class => $this->untouchedFiles([55 => 200, 56 => 200]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_ACCOUNT));
    }

    #[Test]
    #[DataProvider('fileIdRoutes')]
    public function missingFileIsIndistinguishableFromAForbiddenOne(
        string $method,
        string $route,
        array $post,
        bool $json,
        int $fileAction,
        bool $requiresEdit
    ): void {
        $accounts = $this->createMock(AccountService::class);
        $accounts->expects(self::never())->method('isVisible');

        // No file id is known
        $this->dispatch($method, $route, $post, [
            AccountService::class => $accounts,
            AccountFileService::class => $this->untouchedFiles([]),
        ]);

        $this->expectOutputString($this->error($json, self::NO_ACCOUNT));
    }

    #[Test]
    public function batchDeleteDeletesNothingWhenOneFileIsForbidden(): void
    {
        $accounts = $this->createMock(AccountService::class);
        $accounts->method('isVisible')->willReturnCallback(static fn(int $id): bool => $id === 100);
        $accounts->method('getByIdEnriched')->willReturn(
            AccountDataGenerator::factory()->buildAccountDataView()->mutate(['id' => 100])
        );
        $accounts->method('withUsers')->willReturnArgument(0);
        $accounts->method('withUserGroups')->willReturnArgument(0);

        // 55 is allowed (account 100), 56 is not (account 200)
        $this->dispatch('post', 'accountFile/delete', ['items' => [55, 56]], [
            AccountService::class => $accounts,
            AccountAclService::class => $this->accountAcl(true, true),
            AccountFileService::class => $this->untouchedFiles([55 => 100, 56 => 200]),
        ]);

        $this->expectOutputString($this->error(true, self::NO_ACCOUNT));
    }

    #[Test]
    public function batchDeleteDeletesNothingWhenOneFileIsMissing(): void
    {
        $accounts = $this->createMock(AccountService::class);
        $accounts->method('isVisible')->willReturn(true);
        $accounts->method('getByIdEnriched')->willReturn(
            AccountDataGenerator::factory()->buildAccountDataView()->mutate(['id' => 100])
        );
        $accounts->method('withUsers')->willReturnArgument(0);
        $accounts->method('withUserGroups')->willReturnArgument(0);

        $this->dispatch('post', 'accountFile/delete', ['items' => [55, 56]], [
            AccountService::class => $accounts,
            AccountAclService::class => $this->accountAcl(true, true),
            AccountFileService::class => $this->untouchedFiles([55 => 100]),
        ]);

        $this->expectOutputString($this->error(true, self::NO_ACCOUNT));
    }

    #[Test]
    public function batchDeleteDeletesTheFilesWhenEveryOneIsAuthorized(): void
    {
        $files = $this->createMock(AccountFileService::class);
        $files->method('getAccountIdById')->willReturnMap([[55, 100], [56, 100]]);
        $files->expects(self::once())->method('deleteByIdBatch')->with([55, 56])->willReturn(2);

        $this->dispatch('post', 'accountFile/delete', ['items' => [55, 56]], [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $this->accountAcl(true, true),
            AccountFileService::class => $files,
        ]);

        $this->expectOutputString('{"status":"OK","description":"Files deleted","data":null}');
    }

    #[Test]
    public function singleDeleteDeletesTheFileWhenAuthorized(): void
    {
        $files = $this->createMock(AccountFileService::class);
        $files->method('getAccountIdById')->with(55)->willReturn(100);
        $files->expects(self::once())->method('delete')->with(55)->willReturnSelf();

        $this->dispatch('post', 'accountFile/delete/55', [], [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $this->accountAcl(true, true),
            AccountFileService::class => $files,
        ]);

        $this->expectOutputString('{"status":"OK","description":"File deleted","data":null}');
    }

    #[Test]
    public function viewOnlyAccessCannotDeleteButCanReachTheFileService(): void
    {
        $files = $this->createMock(AccountFileService::class);
        $files->method('getAccountIdById')->with(55)->willReturn(100);
        $files->expects(self::never())->method('delete');

        $this->dispatch('post', 'accountFile/delete/55', [], [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $this->accountAcl(true, false),
            AccountFileService::class => $files,
        ]);

        $this->expectOutputString($this->error(true, self::NO_ACCOUNT));
    }

    #[Test]
    public function downloadReturnsTheContentWhenTheAccountIsAccessible(): void
    {
        $file = \SP\Domain\Account\Dtos\FileDto::fromResult(
            new \SP\Domain\Database\Dtos\QueryResult(
                [
                    \SP\Domain\Account\Models\File::buildFromSimpleModel(
                        \SP\Tests\Generators\FileDataGenerator::factory()->buildFileData()
                    )->mutate(['content' => 'file-content', 'type' => 'text/plain', 'size' => 12])
                ]
            ),
            \SP\Domain\Account\Models\File::class
        );

        $files = $this->createMock(AccountFileService::class);
        $files->method('getAccountIdById')->with(55)->willReturn(100);
        $files->expects(self::once())->method('getById')->with(55)->willReturn($file);

        $this->dispatch('get', 'accountFile/download/55', [], [
            AccountService::class => $this->visibleAccounts(),
            AccountAclService::class => $this->accountAcl(true, false),
            AccountFileService::class => $files,
        ]);

        $this->expectOutputString('file-content');
    }

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isFilesEnabled'] = true;

        return $configData;
    }

    private function dispatch(string $method, string $route, array $post, array $overrides): void
    {
        $container = $this->buildContainer(
            self::buildRequest($method, 'index.php', ['r' => $route], $post),
            $overrides
        );
        self::runApp($container);
    }

    private function error(bool $json, string $message): string
    {
        return $json
            ? '{"status":"ERROR","description":"' . $message . '","data":null}'
            : $message;
    }

    /**
     * A file service that knows the owners of some files and fails on any read/delete
     *
     * @param array<int, int> $owners file id => account id
     */
    private function untouchedFiles(array $owners): AccountFileService&MockObject
    {
        $files = $this->createMock(AccountFileService::class);
        $files->method('getAccountIdById')->willReturnCallback(
            static fn(int $id): int => $owners[$id] ?? throw new NoSuchItemException('File not found')
        );

        foreach (['getById', 'getByAccountId', 'delete', 'deleteByIdBatch'] as $method) {
            $files->expects(self::never())->method($method);
        }

        return $files;
    }

    private function visibleAccounts(): AccountService
    {
        $accounts = $this->createStub(AccountService::class);
        $accounts->method('isVisible')->willReturn(true);
        $accounts->method('getByIdEnriched')->willReturn(
            AccountDataGenerator::factory()->buildAccountDataView()->mutate(['id' => 100])
        );
        $accounts->method('withUsers')->willReturnArgument(0);
        $accounts->method('withUserGroups')->willReturnArgument(0);

        return $accounts;
    }

    private function accountAcl(bool $canView, bool $canEdit): AccountAclService
    {
        $accountAcl = $this->createStub(AccountAclService::class);
        $accountAcl->method('getAcl')->willReturnCallback(
            fn(int $action): AccountPermission => $this->permission($action, $canView, $canEdit)
        );

        return $accountAcl;
    }

    private function permission(int $action, bool $canView, bool $canEdit): AccountPermission
    {
        return (new AccountPermission($action))
            ->setCompiledAccountAccess(true)
            ->setResultView($canView)
            ->setResultEdit($canEdit);
    }
}
