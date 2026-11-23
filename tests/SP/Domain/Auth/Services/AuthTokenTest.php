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

namespace SP\Tests\Domain\Auth\Services;

use SP\Core\Context\StatelessMasterKeyStore;
use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Context\ContextException;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Ports\AuthTokenRepository;
use SP\Domain\Auth\Services\AuthToken;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Crypt\Adapters\HashServiceAdapter;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\Generators\AuthTokenGenerator;
use SP\Tests\Generators\ItemSearchDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AuthTokenTest
 *
 */
#[Group('unitary')]
class AuthTokenTest extends UnitaryTestCase
{

    private AuthTokenRepository|MockObject    $authTokenRepository;
    private CryptInterface|MockObject         $crypt;
    private CustomFieldDataService|MockObject $customFieldDataService;
    private AuthToken                         $authToken;

    public static function secureActionDataProvider(): array
    {
        return [
            [AclActionsInterface::ACCOUNT_VIEW_PASS],
            [AclActionsInterface::ACCOUNT_EDIT_PASS],
            [AclActionsInterface::ACCOUNT_CREATE],
            [AclActionsInterface::ACCOUNT_VIEW],
            [AclActionsInterface::CATEGORY_VIEW],
            [AclActionsInterface::CLIENT_VIEW],
        ];
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    #[Test]
    public function testDeleteByIdBatch(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $ids = [10, 20, 10, 30];

        $this->authTokenRepository
            ->expects(self::exactly(3))
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::exactly(3))
            ->method('getById')
            ->willReturnCallback(
                static fn(int $id): QueryResult => new QueryResult([
                    AuthTokenGenerator::factory()->buildAuthToken()->mutate(['id' => $id, 'userId' => $ownerId]),
                ])
            );

        $this->authTokenRepository
            ->expects(self::exactly(3))
            ->method('delete')
            ->willReturn(new QueryResult(null, 1));

        $this->customFieldDataService
            ->expects(self::exactly(3))
            ->method('deleteByItem')
            ->willReturnCallback(static function (int $id, int $moduleId): void {
                self::assertSame(AclActionsInterface::AUTHTOKEN, $moduleId);
            });

        $result = $this->authToken->deleteByIdBatch($ids);

        self::assertSame(['deleted' => [10, 20, 30], 'failed' => []], $result);
    }

    #[Test]
    public function testDeleteByIdBatchRejectsEmptyIds(): void
    {
        $this->authTokenRepository->expects(self::never())->method('delete');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while removing the tokens');

        $this->authToken->deleteByIdBatch([]);
    }

    #[Test]
    public function testDeleteByIdBatchSupportsPartialSuccessAcrossMixedOutcomes(): void
    {
        $ownerId = $this->context->getUserData()->id;

        $this->authTokenRepository
            ->expects(self::exactly(4))
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::exactly(4))
            ->method('getById')
            ->willReturnCallback(static function (int $id) use ($ownerId): QueryResult {
                return match ($id) {
                    10, 30 => new QueryResult([AuthTokenGenerator::factory()->buildAuthToken()->mutate(['id' => $id, 'userId' => $ownerId])]),
                    20 => new QueryResult([AuthTokenGenerator::factory()->buildAuthToken()->mutate(['id' => 20, 'userId' => $ownerId + 1])]),
                    default => new QueryResult([]),
                };
            });

        $this->authTokenRepository
            ->expects(self::exactly(2))
            ->method('delete')
            ->willReturn(new QueryResult(null, 1));

        $this->customFieldDataService
            ->expects(self::exactly(2))
            ->method('deleteByItem')
            ->willReturnCallback(static function (int $id, int $moduleId): void {
                if ($id === 30) {
                    throw new ServiceException('Custom field cleanup error');
                }
            });

        $result = $this->authToken->deleteByIdBatch([10, 20, 999, 30]);

        self::assertSame([10], $result['deleted']);
        self::assertSame([
            20 => 'You don\'t have permission to do this operation',
            999 => 'Token not found',
            30 => 'Error while removing the tokens',
        ], $result['failed']);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Test]
    public function testSearch(): void
    {
        $itemSearch = ItemSearchDataGenerator::factory()->buildItemSearchData();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('search')
            ->with($itemSearch);

        $this->authToken->search($itemSearch);
    }

    /**
     * @throws ConstraintException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws SPException
     * @throws QueryException
     */
    #[Test]
    public function testCreateGeneratesIndependentTokenWithoutUserLookup(): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'token' => 'preexisting',
            ]);

        $capturedToken = null;
        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($authToken, &$capturedToken) {
                    $capturedToken = $current->getToken();

                    return $current->getId() === $authToken->getId()
                           && $current->getUserId() === $authToken->getUserId()
                           && $current->getStartDate() === $authToken->getStartDate()
                           && $current->getActionId() === $authToken->getActionId()
                           && $current->getVault() === null
                           && is_string($capturedToken)
                           && strlen($capturedToken) === 64
                           && $capturedToken !== $authToken->getToken()
                           && $current->getHash() === null
                           && $current->getCreatedBy() === $this->context->getUserData()->id;
                })
            )
            ->willReturn(new QueryResult(null, 0, 100));

        $out = $this->authToken->create($authToken);

        $this->assertEquals(100, $out);
        $this->assertSame($capturedToken, $this->authToken->getLastGeneratedToken());
    }

    #[Test]
    public function testCreateForbiddenForOtherUser(): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id + 1,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'hash' => null,
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateAllowsAdminForOtherUser(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));

        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id + 1,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'hash' => null,
            ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->willReturn(new QueryResult(null, 0, 102));

        self::assertSame(102, $this->authToken->create($authToken));
    }

    #[DataProvider('secureActionDataProvider')]
    #[Test]
    public function testCreateWithKeyDependentActionRejectsBlankPass(int $action): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => $action,
                'hash' => '',
                'vault' => null,
            ]);

        $this->crypt->expects(self::never())->method('makeSecuredKey');
        $this->crypt->expects(self::never())->method('encrypt');
        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password cannot be blank');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateWithKeyDependentActionFailsClosedWhenMasterKeyMissing(): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_VIEW,
                'hash' => 'secret-pass',
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while retrieving master password from context');

        $this->authToken->create($authToken);
    }

    /**
     * @param int $action
     * @throws ConstraintException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     * @throws ContextException
     */
    #[DataProvider('secureActionDataProvider')]
    #[Test]
    public function testCreateWithSecureAction(int $action): void
    {
        $this->context->setTrasientKey('_masterpass', 'test_pass');

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id,
            'actionId' => $action,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($authToken) {
                    return $current->getId() === $authToken->getId()
                           && $current->getUserId() === $authToken->getUserId()
                           && $current->getStartDate() === $authToken->getStartDate()
                           && $current->getActionId() === $authToken->getActionId()
                           && $current->getToken() !== $authToken->getToken()
                           && strlen((string)$current->getToken()) === 64
                           && $current->getVault() !== $authToken->getVault()
                           && $current->getHash() !== $authToken->getHash()
                           && $current->getCreatedBy() === $this->context->getUserData()->id;
                })
            )
            ->willReturn(new QueryResult(null, 0, 100));

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->willReturn('key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with('test_pass', 'key', self::isType('string'))
            ->willReturn('secure_data');

        $out = $this->authToken->create($authToken);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    #[Test]
    public function testUpdateRaw(): void
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with($authToken);

        $this->authToken->updateRaw($authToken);
    }

    /**
     * @throws NoSuchItemException
     */
    #[Test]
    public function testGetTokenByToken(): void
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($authToken->getActionId(), $authToken->getToken())
            ->willReturn(new QueryResult([$authToken]));

        $out = $this->authToken->getTokenByToken($authToken->getActionId(), $authToken->getToken());

        $this->assertEquals($authToken, $out);
    }

    /**
     * @throws NoSuchItemException
     */
    #[Test]
    public function testGetTokenByTokenWithNoFound(): void
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($authToken->getActionId(), $authToken->getToken())
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Token not found');

        $this->authToken->getTokenByToken($authToken->getActionId(), $authToken->getToken());
    }

    #[Test]
    public function testUpdatePreservesTokenWhenActionUnchanged(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
        ]);
        $updatedInput = $existing->mutate(['hash' => null]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($existing) {
                    return $current->getId() === $existing->getId()
                           && $current->getUserId() === $existing->getUserId()
                           && $current->getActionId() === $existing->getActionId()
                           && $current->getVault() === null
                           && $current->getToken() === $existing->getToken()
                           && $current->getHash() === null
                           && $current->getCreatedBy() === $this->context->getUserData()->id;
                })
            )
            ->willReturn(true);

        $this->authToken->update($updatedInput);
        self::assertNull($this->authToken->getLastGeneratedToken());
    }

    #[Test]
    public function testUpdateRotatesTokenWhenActionChanges(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
        ]);
        $updatedInput = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $newToken = null;
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($existing, &$newToken) {
                    $newToken = $current->getToken();

                    return $current->getId() === $existing->getId()
                           && $current->getActionId() === AclActionsInterface::TAG_SEARCH
                           && $newToken !== $existing->getToken()
                           && strlen((string)$newToken) === 64;
                })
            )
            ->willReturn(true);

        $this->authToken->update($updatedInput);
        self::assertSame($newToken, $this->authToken->getLastGeneratedToken());
    }

    #[Test]
    public function testUpdateForbiddenForOtherUser(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate(['userId' => $ownerId + 1]);
        $input = $existing->mutate(['userId' => $ownerId]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->update($input);
    }

    /**
     * @param int $action
     * @throws ConstraintException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     * @throws ContextException
     */
    #[DataProvider('secureActionDataProvider')]
    #[Test]
    public function testUpdateWithSecureAction(int $action): void
    {
        $this->context->setTrasientKey('_masterpass', 'test_pass');
        $ownerId = $this->context->getUserData()->id;

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => $action,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($authToken->getId())
            ->willReturn(new QueryResult([$authToken]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($authToken) {
                    return $current->getId() === $authToken->getId()
                           && $current->getUserId() === $authToken->getUserId()
                           && $current->getStartDate() === $authToken->getStartDate()
                           && $current->getActionId() === $authToken->getActionId()
                           && $current->getToken() === $authToken->getToken()
                           && $current->getVault() !== $authToken->getVault()
                           && $current->getHash() !== $authToken->getHash()
                           && $current->getCreatedBy() === $this->context->getUserData()->id;
                })
            )
            ->willReturn(true);

        $password = sha1($authToken->getHash() . $authToken->getToken());

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with($password)
            ->willReturn('key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with('test_pass', 'key', $password)
            ->willReturn('secure_data');

        $this->authToken->update($authToken);
        self::assertNull($this->authToken->getLastGeneratedToken());
    }

    #[DataProvider('secureActionDataProvider')]
    #[Test]
    public function testUpdateWithKeyDependentActionPreservesValidVaultWhenPasswordBlank(int $action): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => $action,
            'vault' => 'serialized-existing-vault',
            'hash' => 'existing-pass-hash',
        ]);
        $updatedInput = $existing->mutate(['hash' => null, 'vault' => null]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->crypt->expects(self::never())->method('makeSecuredKey');
        $this->crypt->expects(self::never())->method('encrypt');

        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(static fn(AuthTokenModel $current): bool => $current->getToken() === $existing->getToken()
                    && $current->getVault() === 'serialized-existing-vault'
                    && $current->getHash() === 'existing-pass-hash')
            )
            ->willReturn(true);

        $this->authToken->update($updatedInput);
        self::assertNull($this->authToken->getLastGeneratedToken());
    }

    #[Test]
    public function testUpdateWithKeyDependentActionRejectsBlankPassWhenExistingLacksVault(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_VIEW,
            'vault' => null,
            'hash' => null,
        ]);
        $updatedInput = $existing->mutate(['hash' => null, 'vault' => null]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password cannot be blank');

        $this->authToken->update($updatedInput);
    }

    #[DataProvider('secureActionDataProvider')]
    #[Test]
    public function testUpdateScopeChangeToKeyDependentActionRejectsBlankPass(int $action): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'vault' => null,
            'hash' => null,
        ]);
        $updatedInput = $existing->mutate([
            'actionId' => $action,
            'hash' => '',
            'vault' => null,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password cannot be blank');

        $this->authToken->update($updatedInput);
    }

    #[Test]
    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();
        $ownerId = $this->context->getUserData()->id;

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate(['userId' => $ownerId]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$authToken]));

        $out = $this->authToken->getById($id);

        $this->assertEquals($authToken, $out);
    }

    #[Test]
    public function testGetByIdForbiddenForOtherUser(): void
    {
        $id = self::$faker->randomNumber();
        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id + 1,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$authToken]));

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->getById($id);
    }

    #[Test]
    public function testGetByIdAllowsAdminForOtherUser(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));
        $id = self::$faker->randomNumber();
        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id + 1,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$authToken]));

        self::assertEquals($authToken, $this->authToken->getById($id));
    }

    #[Test]
    public function testGetByIdNotFound(): void
    {
        $id = self::$faker->randomNumber();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Token not found');

        $this->authToken->getById($id);
    }

    #[Test]
    public function testGetAll(): void
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$authToken]));

        $out = $this->authToken->getAll();

        $this->assertEquals([$authToken], $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    #[Test]
    public function testDelete(): void
    {
        $id = self::$faker->randomNumber();
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => $id,
            'userId' => $this->context->getUserData()->id,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult(null, 1));

        $this->customFieldDataService
            ->expects(self::once())
            ->method('deleteByItem')
            ->with($id, AclActionsInterface::AUTHTOKEN);

        $this->authToken->delete($id);
    }

    #[Test]
    public function testDeleteForbiddenForOtherUser(): void
    {
        $id = self::$faker->randomNumber();
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => $id,
            'userId' => $this->context->getUserData()->id + 1,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('delete');
        $this->customFieldDataService->expects(self::never())->method('deleteByItem');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Test]
    public function testDeleteWithNotFound(): void
    {
        $id = self::$faker->randomNumber();

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([]));

        $this->authTokenRepository->expects(self::never())->method('delete');
        $this->customFieldDataService->expects(self::never())->method('deleteByItem');

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Token not found');

        $this->authToken->delete($id);
    }

    /**
     * @throws ContextException
     * @throws Exception
     */
    #[Test]
    public function testRefreshAndUpdateRotatesOnlyTargetToken(): void
    {
        $this->context->setTrasientKey('_masterpass', 'test_pass');
        $ownerId = $this->context->getUserData()->id;

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_VIEW_PASS,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($authToken->getId())
            ->willReturn(new QueryResult([$authToken]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->with(self::anything())
            ->willReturn('key');

        $this->crypt
            ->expects(self::once())
            ->method('encrypt')
            ->with('test_pass', 'key', self::anything())
            ->willReturn('secure_data');

        $rotatedToken = null;
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(
                new Callback(function (AuthTokenModel $current) use ($authToken, &$rotatedToken) {
                    $rotatedToken = $current->getToken();

                    return $current->getId() === $authToken->getId()
                           && $current->getUserId() === $authToken->getUserId()
                           && $current->getActionId() === $authToken->getActionId()
                           && $current->getVault() !== $authToken->getVault()
                           && $current->getToken() !== $authToken->getToken()
                           && strlen((string)$rotatedToken) === 64
                           && $current->getHash() !== $authToken->getHash()
                           && $current->getCreatedBy() === $this->context->getUserData()->id;
                })
            )
            ->willReturn(true);

        $this->authToken->refreshAndUpdate($authToken);
        self::assertSame($rotatedToken, $this->authToken->getLastGeneratedToken());
    }

    #[Test]
    public function testRefreshAndUpdateForbiddenForOtherUser(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate(['userId' => $ownerId + 1]);
        $input = $existing->mutate(['userId' => $ownerId]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->refreshAndUpdate($input);
    }

    #[Test]
    public function testRefreshAndUpdateWithKeyDependentActionRejectsBlankPass(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_VIEW,
        ]);
        $input = $existing->mutate(['hash' => '', 'vault' => null]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($existing->getId())
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password cannot be blank');

        $this->authToken->refreshAndUpdate($input);
    }

    #[Test]
    public function testDeleteRollsBackWhenCustomFieldCleanupFails(): void
    {
        $id = self::$faker->randomNumber();
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => $id,
            'userId' => $this->context->getUserData()->id,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::isInstanceOf(\Closure::class), $this->authToken)
            ->willReturnCallback(static fn(callable $callback) => $callback());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with($id)
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('delete')
            ->with($id)
            ->willReturn(new QueryResult(null, 1));

        $this->customFieldDataService
            ->expects(self::once())
            ->method('deleteByItem')
            ->with($id, AclActionsInterface::AUTHTOKEN)
            ->willThrowException(new ServiceException('Cleanup failed'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Cleanup failed');

        $this->authToken->delete($id);
    }

    #[Test]
    public function testCreateAssignsDefault90DayExpiryWhenOmitted(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $ownerId,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => null,
            ]);

        $before = time();
        $capturedExpireDate = null;

        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->with(new Callback(function (AuthTokenModel $current) use (&$capturedExpireDate): bool {
                $capturedExpireDate = $current->getExpireDate();

                return true;
            }))
            ->willReturn(new QueryResult(null, 0, 105));

        self::assertSame(105, $this->authToken->create($authToken));
        $after = time();

        self::assertNotNull($capturedExpireDate);
        self::assertGreaterThanOrEqual($before + 7776000, $capturedExpireDate);
        self::assertLessThanOrEqual($after + 7776000, $capturedExpireDate);
    }

    #[Test]
    public function testCreateAcceptsValidCustomExpireDateWithinSystemMax(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $targetExpireDate = time() + 30 * 86400;
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $ownerId,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => $targetExpireDate,
            ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === $targetExpireDate))
            ->willReturn(new QueryResult(null, 0, 106));

        self::assertSame(106, $this->authToken->create($authToken));
    }

    #[Test]
    public function testCreateRejectsExpireDateExceedingSystemMaxForNonAdmin(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $ownerId,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => time() + 366 * 86400,
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expiration date exceeds the maximum allowed lifetime');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateRejectsExpireDateExceedingSystemMaxEvenForAdmin(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => time() + 366 * 86400,
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expiration date exceeds the maximum allowed lifetime');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateRejectsPastOrZeroExpireDate(): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => time() - 60,
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid expiration date');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateNoExpiryForbiddenForNonAdmin(): void
    {
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => null,
                'noExpiry' => true,
            ]);

        $this->authTokenRepository->expects(self::never())->method('create');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateNoExpiryAllowedForAdminAndPersistsNull(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));
        $authToken = AuthTokenGenerator::factory()
            ->buildAuthToken()
            ->mutate([
                'userId' => $this->context->getUserData()->id,
                'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                'expireDate' => null,
                'noExpiry' => true,
            ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('create')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === null))
            ->willReturn(new QueryResult(null, 0, 107));

        self::assertSame(107, $this->authToken->create($authToken));
    }

    #[Test]
    public function testUpdateMetadataPreservesExistingExpireDateEvenAboveNewSystemMax(): void
    {
        $this->config->getConfigData()->setAuthTokenMaxTime(30 * 86400);

        $ownerId = $this->context->getUserData()->id;
        $legacyExpireDate = time() + 180 * 86400;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => $legacyExpireDate,
        ]);
        $input = $existing->mutate(['hash' => null, 'expireDate' => null, 'noExpiry' => null]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === $legacyExpireDate))
            ->willReturn(true);

        $this->authToken->update($input);
    }

    #[Test]
    public function testUpdateScopeChangeRejectsExistingExpireDateExceedingCurrentMax(): void
    {
        $this->config->getConfigData()->setAuthTokenMaxTime(30 * 86400);

        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => time() + 120 * 86400,
        ]);
        $input = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expiration date exceeds the maximum allowed lifetime');

        $this->authToken->update($input);
    }

    #[Test]
    public function testUpdateScopeChangePreventsNonAdminRetainingNullExpireDate(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => null,
        ]);
        $input = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $before = time();
        $capturedExpireDate = null;

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(new Callback(function (AuthTokenModel $current) use (&$capturedExpireDate): bool {
                $capturedExpireDate = $current->getExpireDate();

                return true;
            }))
            ->willReturn(true);

        $this->authToken->update($input);

        self::assertNotNull($capturedExpireDate);
        self::assertGreaterThanOrEqual($before + 7776000, $capturedExpireDate);
    }

    /**
     * @return array<string, array{int|null}>
     */
    public static function invalidOrExpiredDatesProvider(): array
    {
        return [
            'past' => [time() - 1],
            'exact boundary now' => [time()],
            'zero' => [0],
            'negative' => [-50],
        ];
    }

    #[DataProvider('invalidOrExpiredDatesProvider')]
    #[Test]
    public function testGetTokenByTokenFailsClosedOnExpiredOrInvalidExpiry(?int $expireDate): void
    {
        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'expireDate' => $expireDate,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getTokenByToken')
            ->with($authToken->getActionId(), $authToken->getToken())
            ->willReturn(new QueryResult([$authToken]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Token not found');

        $this->authToken->getTokenByToken($authToken->getActionId(), $authToken->getToken());
    }

    #[Test]
    public function testGetTokenByTokenAllowsValidFutureAndNullExpiry(): void
    {
        $futureToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'expireDate' => time() + 3600,
        ]);
        $noExpiryToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'expireDate' => null,
        ]);

        $this->authTokenRepository
            ->expects(self::exactly(2))
            ->method('getTokenByToken')
            ->willReturnOnConsecutiveCalls(new QueryResult([$futureToken]), new QueryResult([$noExpiryToken]));

        self::assertEquals($futureToken, $this->authToken->getTokenByToken($futureToken->getActionId(), $futureToken->getToken()));
        self::assertEquals($noExpiryToken, $this->authToken->getTokenByToken($noExpiryToken->getActionId(), $noExpiryToken->getToken()));
    }

    #[Test]
    public function testDeleteThrowsNoSuchItemWhenZeroAffectedRows(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => 10,
            'userId' => $ownerId,
        ]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->willReturnCallback(static fn(callable $fn) => $fn());

        $this->authTokenRepository
            ->expects(self::once())
            ->method('getById')
            ->with(10)
            ->willReturn(new QueryResult([$existing]));

        $this->authTokenRepository
            ->expects(self::once())
            ->method('delete')
            ->with(10)
            ->willReturn(new QueryResult(null, 0));

        $this->customFieldDataService->expects(self::never())->method('deleteByItem');

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Token not found');

        $this->authToken->delete(10);
    }

    #[Test]
    public function testDeleteByIdBatchRecordsFailureForNonPositiveIds(): void
    {
        $this->authTokenRepository->expects(self::never())->method('delete');

        $result = $this->authToken->deleteByIdBatch([0, -5]);

        self::assertSame([], $result['deleted']);
        self::assertSame([
            0 => 'Token not found',
            -5 => 'Token not found',
        ], $result['failed']);
    }

    #[Test]
    public function testCreateRejectsInvalidMaxLifetimeConfig(): void
    {
        $this->config->getConfigData()->setAuthTokenMaxTime(0);

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid maximum token lifetime configuration');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateRejectsInvalidDefaultLifetimeConfig(): void
    {
        $this->config->getConfigData()->setAuthTokenMaxTime(3600);
        $this->config->getConfigData()->setAuthTokenDefaultTime(7200);

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Default token lifetime exceeds the maximum allowed lifetime');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testCreateAdminNoExpiryWithExplicitExpireDateThrowsValidation(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));

        $authToken = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => time() + 3600,
            'noExpiry' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid expiration date');

        $this->authToken->create($authToken);
    }

    #[Test]
    public function testUpdatePreservesRequestedExpireDateUnchangedWhenSameAction(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $legacyExpireDate = time() - 10;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => $legacyExpireDate,
        ]);
        $input = $existing->mutate(['hash' => null, 'noExpiry' => null]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === $legacyExpireDate))
            ->willReturn(true);

        $this->authToken->update($input);
    }

    #[Test]
    public function testUpdateScopeChangeWithExpiredExistingDateThrowsValidation(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => time() - 60,
        ]);
        $input = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid expiration date');

        $this->authToken->update($input);
    }

    #[Test]
    public function testUpdateScopeChangePreservesValidExistingFutureExpireDate(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $futureDate = time() + 7200;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $ownerId,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => $futureDate,
        ]);
        $input = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === $futureDate))
            ->willReturn(true);

        $this->authToken->update($input);
    }

    #[Test]
    public function testUpdateScopeChangePreservesNullExpireDateForAdmin(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['isAdminApp' => true]));
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'userId' => $this->context->getUserData()->id,
            'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
            'expireDate' => null,
        ]);
        $input = $existing->mutate([
            'actionId' => AclActionsInterface::TAG_SEARCH,
            'hash' => null,
            'expireDate' => null,
            'noExpiry' => null,
        ]);

        $this->authTokenRepository->expects(self::once())->method('getById')->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository
            ->expects(self::once())
            ->method('update')
            ->with(new Callback(static fn(AuthTokenModel $current): bool => $current->getExpireDate() === null))
            ->willReturn(true);

        $this->authToken->update($input);
    }

    #[Test]
    public function testRefreshAndUpdateRejectsReassigningOwnerByNonAdmin(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => 25,
            'userId' => $ownerId,
        ]);
        $input = $existing->mutate(['userId' => $ownerId + 99]);

        $this->authTokenRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->willReturnCallback(static fn(callable $fn) => $fn());

        $this->authTokenRepository->expects(self::once())->method('getById')->with(25)->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->refreshAndUpdate($input);
    }

    #[Test]
    public function testUpdateRejectsReassigningOwnerByNonAdmin(): void
    {
        $ownerId = $this->context->getUserData()->id;
        $existing = AuthTokenGenerator::factory()->buildAuthToken()->mutate([
            'id' => 26,
            'userId' => $ownerId,
        ]);
        $input = $existing->mutate(['userId' => $ownerId + 99]);

        $this->authTokenRepository->expects(self::once())->method('getById')->with(26)->willReturn(new QueryResult([$existing]));
        $this->authTokenRepository->expects(self::never())->method('update');

        $this->expectException(UnauthorizedActionException::class);

        $this->authToken->update($input);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authTokenRepository = $this->createMock(AuthTokenRepository::class);
        $this->crypt = $this->createMock(CryptInterface::class);
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);

        $this->authToken = new AuthToken(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->authTokenRepository,
            $this->crypt,
            new StatelessMasterKeyStore($this->context),
            new HashServiceAdapter(),
            $this->customFieldDataService
        );
    }
}
