<?php
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\AuthToken;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Services\AuthToken as AuthTokenService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User as UserModel;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for Web AuthToken ownership, bearer exposure,
 * duplicate-check SQL precedence on second-token creation, and user-wide token refresh.
 */
#[Group('integration')]
final class AuthTokenDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const SYNTHETIC_MASTER_KEY = 'synthetic-master-key-authtoken-2026';

    protected function setUp(): void
    {
        parent::setUp();

        self::truncateTable('AuthToken');
    }

    /**
     * Characterization of current cross-user Web AuthToken access and bearer exposure:
     * 1. Non-admin user A with AUTHTOKEN_* permissions can view another user B's token via authToken/view/<id>,
     *    and the rendered HTML exposes user B's bearer token.
     * 2. authToken/search returns user B's token row to non-admin user A (without exposing the bearer secret in HTML).
     * 3. authToken/edit/<id> loads user B's token row into the edit popup.
     * 4. authToken/saveEdit/<id> rejects submitting users=<userB>, yet when submitted with users=<userA> it mutates
     *    user B's token row in MariaDB to user A because ownership of the existing token ID is not checked.
     * 5. authToken/delete/<id> deletes user B's token row without an owner check.
     */
    #[Test]
    public function nonAdminCanViewSearchEditReassignAndDeleteAnotherUsersTokenInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedNonAdminUser($pdo, 'synthetic_tok_a_' . bin2hex(random_bytes(4)));
        $userB = $this->seedNonAdminUser($pdo, 'synthetic_tok_b_' . bin2hex(random_bytes(4)));

        $bearerB1 = 'synthetic-bearer-b1-' . bin2hex(random_bytes(12));
        $bearerB2 = 'synthetic-bearer-b2-' . bin2hex(random_bytes(12));

        $tokenB1Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userB->id,
            $bearerB1,
            AclActionsInterface::TAG_SEARCH,
            (int)$userB->id
        );
        $tokenB2Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userB->id,
            $bearerB2,
            AclActionsInterface::CATEGORY_SEARCH,
            (int)$userB->id
        );

        $overrides = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        // 1. Cross-user authToken/view/<id> succeeds and exposes bearer token in rendered HTML
        $viewResult = $this->dispatch('get', 'authToken/view/' . $tokenB1Id, [], [], $overrides);
        $viewJson = $this->assertJsonOk($viewResult);
        self::assertStringContainsString($bearerB1, $viewJson['data']['html'] ?? '');

        // 2. Cross-user authToken/search lists user B's rows without rendering bearer secrets in the grid HTML
        // Note: AuthTokenGrid reads `userLogin` while AuthTokenRepository::search() selects `User.login` into `login`,
        // leaving the User column blank (&nbsp;) with an undefined-property warning.
        $searchResult = @$this->dispatch('get', 'authToken/search', [], [], $overrides);
        $searchJson = $this->assertJsonOk($searchResult);
        $searchHtml = (string)($searchJson['data']['html'] ?? '');
        self::assertStringContainsString('data-item-id="' . $tokenB1Id . '"', $searchHtml);
        self::assertStringContainsString('data-item-id="' . $tokenB2Id . '"', $searchHtml);
        self::assertStringNotContainsString($bearerB1, $searchHtml);
        self::assertStringNotContainsString($bearerB2, $searchHtml);

        // 3. Cross-user authToken/edit/<id> renders form for user B's token
        $editResult = $this->dispatch('get', 'authToken/edit/' . $tokenB1Id, [], [], $overrides);
        $editJson = $this->assertJsonOk($editResult);
        self::assertStringContainsString('authToken/saveEdit/' . $tokenB1Id, $editJson['data']['html'] ?? '');
        self::assertStringNotContainsString($bearerB1, $editJson['data']['html'] ?? '');

        // 4a. Non-admin saveCreate / saveEdit specifying users=userB throws uncaught ValidationException;
        // Web\Bootstrap returns HTTP 500 with ERROR description.
        $saveCreateOther = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userB->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overrides
        );
        self::assertSame(500, $saveCreateOther['code']);
        $createOtherJson = $this->decodeJson($saveCreateOther['body']);
        self::assertSame('ERROR', $createOtherJson['status'] ?? null);
        self::assertSame("You don't have permission to do this operation", $createOtherJson['description'] ?? null);

        $saveEditOther = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenB1Id,
            [],
            [
                'users' => $userB->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overrides
        );
        self::assertSame(500, $saveEditOther['code']);
        $editOtherJson = $this->decodeJson($saveEditOther['body']);
        self::assertSame('ERROR', $editOtherJson['status'] ?? null);
        self::assertSame("You don't have permission to do this operation", $editOtherJson['description'] ?? null);

        // 4b. Submitting users=userA on user B's token ID passes AuthTokenForm and overwrites user B's row in MariaDB
        $saveEditSelfUser = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenB1Id,
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overrides
        );
        $this->assertJsonOk($saveEditSelfUser, 'Authorization updated');

        $rowStmt = $pdo->prepare('SELECT userId, actionId FROM AuthToken WHERE id = :id');
        $rowStmt->execute(['id' => $tokenB1Id]);
        $mutatedRow = $rowStmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($mutatedRow);
        self::assertSame((int)$userA->id, (int)$mutatedRow['userId']);
        self::assertSame(AclActionsInterface::CLIENT_SEARCH, (int)$mutatedRow['actionId']);

        // 5. Cross-user authToken/delete/<id> deletes user B's remaining token without owner check
        $deleteResult = $this->dispatch('post', 'authToken/delete/' . $tokenB2Id, [], [], $overrides);
        $this->assertJsonOk($deleteResult, 'Authorization deleted');

        $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM AuthToken WHERE id = :id');
        $existsStmt->execute(['id' => $tokenB2Id]);
        self::assertSame(0, (int)$existsStmt->fetchColumn());
    }

    /**
     * Characterization of AuthTokenRepository::checkDuplicatedOnAdd() SQL operator-precedence defect
     * and AuthToken::getOrBuildToken() single-token reuse:
     * 1. First token creation for a user succeeds via POST authToken/saveCreate.
     * 2. Creating a second token for the same user on a DIFFERENT actionId fails with "Authorization already exist"
     *    because `WHERE userId = :userId OR token = :token AND actionId = :actionId` lacks parentheses around the OR
     *    clause (`userId = :userId OR (token = :token AND actionId = :actionId)`).
     * 3. Directly invoking AuthTokenService::create() or seeding two tokens shows getOrBuildToken() reuses the first
     *    token when a user already has one row, while the schema unique index uk_AuthToken_01(token, actionId) permits
     *    distinct actionId values per user.
     */
    #[Test]
    public function secondTokenCreationFailsDueToCheckDuplicatedOnAddPrecedenceDefect(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedNonAdminUser($pdo, 'synthetic_dup_a_' . bin2hex(random_bytes(4)));
        $userB = $this->seedNonAdminUser($pdo, 'synthetic_dup_b_' . bin2hex(random_bytes(4)));

        $overridesA = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        // First token for user A (ACCOUNT_SEARCH) succeeds
        $first = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::ACCOUNT_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        $this->assertJsonOk($first, 'Authorization added');

        // Second token for user A on a DIFFERENT actionId (TAG_SEARCH) is rejected as duplicate
        $second = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::TAG_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        // Second token for user A on a DIFFERENT actionId (TAG_SEARCH) throws uncaught DuplicatedItemException;
        // Web\Bootstrap returns HTTP 500 with an exception stack trace whose args expose user A's reused bearer token.
        self::assertSame(500, $second['code']);
        $secondJson = $this->decodeJson($second['body']);
        self::assertSame('ERROR', $secondJson['status'] ?? null);
        self::assertSame('Authorization already exist', $secondJson['description'] ?? null);
        self::assertIsArray($secondJson['data'] ?? null);

        // Independent DB check: user A still has only 1 row, and its token leaked into the trace args
        $tokStmt = $pdo->prepare('SELECT token FROM AuthToken WHERE userId = :uid');
        $tokStmt->execute(['uid' => $userA->id]);
        $existingTokens = $tokStmt->fetchAll(PDO::FETCH_COLUMN);
        self::assertCount(1, $existingTokens);
        self::assertStringContainsString((string)$existingTokens[0], $second['body']);

        // User B can still create TAG_SEARCH without conflicting with user A
        $overridesB = [
            Context::class => $this->buildSessionContext($userB),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];
        $otherUserCreate = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userB->id,
                'actions' => AclActionsInterface::TAG_SEARCH,
                'pass' => '',
            ],
            $overridesB
        );
        $this->assertJsonOk($otherUserCreate, 'Authorization added');
    }

    /**
     * Characterization of AuthToken::refreshAndUpdate() user-wide rotation:
     * Refreshing one token ID belonging to user A calls refreshTokenByUserId() and refreshVaultByUserId(),
     * rotating the bearer token, vault, and hash on ALL of user A's existing tokens in MariaDB,
     * while leaving another user B's token untouched.
     */
    #[Test]
    public function refreshAndUpdateRotatesTokenAndVaultAcrossAllUsersTokensInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedNonAdminUser($pdo, 'synthetic_ref_a_' . bin2hex(random_bytes(4)));
        $userB = $this->seedNonAdminUser($pdo, 'synthetic_ref_b_' . bin2hex(random_bytes(4)));

        $initialTokenA = 'synthetic-initial-a-' . bin2hex(random_bytes(12));
        $initialTokenB = 'synthetic-initial-b-' . bin2hex(random_bytes(12));
        $initialHashA = Hash::hashKey('initial-token-pass-a');
        $initialVaultA1 = 'synthetic-initial-vault-1';
        $initialVaultA2 = 'synthetic-initial-vault-2';

        // Seed two secured-action tokens for user A (permitted by uk_AuthToken_01 on (token, actionId))
        $tokenA1Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $initialTokenA,
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            (int)$userA->id,
            $initialVaultA1,
            $initialHashA
        );
        $tokenA2Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $initialTokenA,
            AclActionsInterface::ACCOUNT_CREATE,
            (int)$userA->id,
            $initialVaultA2,
            $initialHashA
        );
        $tokenBId = $this->insertAuthTokenRow(
            $pdo,
            (int)$userB->id,
            $initialTokenB,
            AclActionsInterface::TAG_SEARCH,
            (int)$userB->id
        );

        $newTokenPass = 'synthetic-rotated-pass-2026';
        $overridesA = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        $refreshResult = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenA1Id,
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::ACCOUNT_VIEW_PASS,
                'pass' => $newTokenPass,
                'refreshtoken' => 'on',
            ],
            $overridesA
        );
        $this->assertJsonOk($refreshResult, 'Authorization updated');

        $fetchStmt = $pdo->prepare('SELECT id, token, vault, hash FROM AuthToken WHERE id IN (?, ?, ?) ORDER BY id ASC');
        $fetchStmt->execute([$tokenA1Id, $tokenA2Id, $tokenBId]);
        $rows = [];
        foreach ($fetchStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[(int)$row['id']] = $row;
        }

        // Both tokens belonging to user A were rotated together to the same new token and new vault/hash
        self::assertNotSame($initialTokenA, $rows[$tokenA1Id]['token']);
        self::assertSame($rows[$tokenA1Id]['token'], $rows[$tokenA2Id]['token']);
        self::assertNotSame($initialVaultA1, $rows[$tokenA1Id]['vault']);
        self::assertNotSame($initialVaultA2, $rows[$tokenA2Id]['vault']);
        self::assertTrue(Hash::checkHashKey($newTokenPass, (string)$rows[$tokenA1Id]['hash']));
        self::assertTrue(Hash::checkHashKey($newTokenPass, (string)$rows[$tokenA2Id]['hash']));

        // User B's token was unaffected
        self::assertSame($initialTokenB, $rows[$tokenBId]['token']);
    }

    private function seedNonAdminUser(PDO $pdo, string $login): UserDto
    {
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, 0, 0, 0, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic ' . $login,
            'login' => $login,
            'pass' => Hash::hashKey('synthetic-user-pass-' . $login),
            'email' => $login . '@example.test',
            'salt' => sha1($login),
        ]);
        $userId = (int)$pdo->lastInsertId();

        return UserDto::fromModel(new UserModel([
            'id' => $userId,
            'userGroupId' => 1,
            'userProfileId' => 1,
            'login' => $login,
            'ssoLogin' => $login,
            'name' => 'Synthetic ' . $login,
            'email' => $login . '@example.test',
            'isAdminApp' => false,
            'isAdminAcc' => false,
            'isDisabled' => false,
            'preferences' => serialize(UserDataGenerator::factory()->buildUserPreferencesData()),
        ]));
    }

    private function insertAuthTokenRow(
        PDO     $pdo,
        int     $userId,
        string  $token,
        int     $actionId,
        int     $createdBy,
        ?string $vault = null,
        ?string $hash = null
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO AuthToken (userId, token, actionId, createdBy, startDate, vault, hash)
             VALUES (:userId, :token, :actionId, :createdBy, UNIX_TIMESTAMP(), :vault, :hash)'
        );
        $stmt->execute([
            'userId' => $userId,
            'token' => $token,
            'actionId' => $actionId,
            'createdBy' => $createdBy,
            'vault' => $vault,
            'hash' => $hash,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function buildSessionContext(UserDto $userDto): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($userDto);
        $context->method('getUserProfile')->willReturn(new ProfileData());
        $context->method('getCSRF')->willReturn(null);

        return $context;
    }

    private function buildMasterKeyStore(): MasterKeyStore
    {
        return new class (self::SYNTHETIC_MASTER_KEY) implements MasterKeyStore {
            public function __construct(private string $key)
            {
            }

            public function get(): string
            {
                return $this->key;
            }

            public function set(string $masterKey): void
            {
                $this->key = $masterKey;
            }

            public function clear(): void
            {
                $this->key = '';
            }
        };
    }
}
