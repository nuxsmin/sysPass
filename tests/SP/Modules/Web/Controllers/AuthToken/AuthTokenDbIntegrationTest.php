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
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
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
 * DB-backed target tests for Web AuthToken ownership, one-time bearer exposure,
 * SQL precedence on multi-action token creation, metadata-preserving edits, and single-token refresh.
 */
#[Group('integration')]
final class AuthTokenDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const SYNTHETIC_MASTER_KEY = 'synthetic-master-key-authtoken-2026';

    protected function setUp(): void
    {
        parent::setUp();

        self::truncateTable('AuthToken');
        getDbHandler()->getConnection()->exec(
            'DELETE FROM CustomFieldData WHERE moduleId = ' . AclActionsInterface::AUTHTOKEN
        );
    }

    #[Test]
    public function nonAdminIsScopedAndDeniedCrossUserAccessWhileAdminCanManageAnyTokenInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedUser($pdo, 'synthetic_tok_a_' . bin2hex(random_bytes(4)), false);
        $userB = $this->seedUser($pdo, 'synthetic_tok_b_' . bin2hex(random_bytes(4)), false);
        $admin = $this->seedUser($pdo, 'synthetic_tok_adm_' . bin2hex(random_bytes(4)), true);

        $bearerA1 = 'synthetic-bearer-a1-' . bin2hex(random_bytes(12));
        $bearerB1 = 'synthetic-bearer-b1-' . bin2hex(random_bytes(12));
        $bearerB2 = 'synthetic-bearer-b2-' . bin2hex(random_bytes(12));

        $tokenA1Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $bearerA1,
            AclActionsInterface::ACCOUNT_SEARCH,
            (int)$userA->id
        );
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

        $this->insertAuthTokenCustomField($pdo, $tokenA1Id, 'cf-a1');
        $this->insertAuthTokenCustomField($pdo, $tokenB2Id, 'cf-b2');

        $overridesA = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        // 1. Cross-user view is denied with 403 and never leaks bearer secret; own view masks secret as '***'
        $crossView = $this->dispatch('get', 'authToken/view/' . $tokenB1Id, [], [], $overridesA);
        self::assertSame(403, $crossView['code']);
        self::assertStringNotContainsString($bearerB1, $crossView['body']);

        $ownView = $this->dispatch('get', 'authToken/view/' . $tokenA1Id, [], [], $overridesA);
        $ownViewJson = $this->assertJsonOk($ownView);
        $ownViewHtml = (string)($ownViewJson['data']['html'] ?? '');
        self::assertStringContainsString('***', $ownViewHtml);
        self::assertStringNotContainsString($bearerA1, $ownViewHtml);

        // 2. Non-admin search isolates own rows, renders login column without warnings, and never leaks secrets
        $searchResult = $this->dispatch('get', 'authToken/search', [], [], $overridesA);
        $searchJson = $this->assertJsonOk($searchResult);
        $searchHtml = (string)($searchJson['data']['html'] ?? '');
        self::assertStringContainsString('data-item-id="' . $tokenA1Id . '"', $searchHtml);
        self::assertStringContainsString($userA->login, $searchHtml);
        self::assertStringNotContainsString('data-item-id="' . $tokenB1Id . '"', $searchHtml);
        self::assertStringNotContainsString('data-item-id="' . $tokenB2Id . '"', $searchHtml);
        self::assertStringNotContainsString($bearerA1, $searchHtml);
        self::assertStringNotContainsString($bearerB1, $searchHtml);

        // Parenthesized OR check: searching for userB's login still returns 0 rows for non-admin userA
        $otherSearch = $this->dispatch('get', 'authToken/search', ['search' => $userB->login], [], $overridesA);
        $otherSearchJson = $this->assertJsonOk($otherSearch);
        self::assertStringNotContainsString('data-item-id="' . $tokenB1Id . '"', (string)($otherSearchJson['data']['html'] ?? ''));

        // 3. Cross-user edit and saveEdit are denied (both users=B 400 and users=A 403), leaving MariaDB intact
        $crossEdit = $this->dispatch('get', 'authToken/edit/' . $tokenB1Id, [], [], $overridesA);
        self::assertSame(403, $crossEdit['code']);

        $saveEditOther = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenB1Id,
            [],
            [
                'users' => $userB->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        self::assertSame(400, $saveEditOther['code']);

        $saveEditReassign = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenB1Id,
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        self::assertSame(403, $saveEditReassign['code']);

        $rowStmt = $pdo->prepare('SELECT userId, actionId, token FROM AuthToken WHERE id = :id');
        $rowStmt->execute(['id' => $tokenB1Id]);
        $intactRow = $rowStmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($intactRow);
        self::assertSame((int)$userB->id, (int)$intactRow['userId']);
        self::assertSame(AclActionsInterface::TAG_SEARCH, (int)$intactRow['actionId']);
        self::assertSame($bearerB1, $intactRow['token']);

        // 4. Cross-user single delete & unauthorized-only batch are denied (403);
        //    mixed batch deletes authorized token + its custom fields while leaving unauthorized ones intact (200 WARNING)
        $crossDelete = $this->dispatch('post', 'authToken/delete/' . $tokenB2Id, [], [], $overridesA);
        self::assertSame(403, $crossDelete['code']);

        $unauthOnlyBatch = $this->dispatch(
            'post',
            'authToken/delete',
            [],
            ['items' => [$tokenB2Id]],
            $overridesA
        );
        self::assertSame(403, $unauthOnlyBatch['code']);

        $mixedBatchDelete = $this->dispatch(
            'post',
            'authToken/delete',
            [],
            ['items' => [$tokenA1Id, $tokenB2Id, 999999]],
            $overridesA
        );
        self::assertSame(200, $mixedBatchDelete['code']);
        $mixedJson = $this->decodeJson($mixedBatchDelete['body']);
        self::assertSame('WARNING', $mixedJson['status'] ?? null);
        self::assertSame([$tokenA1Id], $mixedJson['data']['deleted'] ?? null);
        self::assertArrayHasKey((string)$tokenB2Id, $mixedJson['data']['failed'] ?? []);
        self::assertArrayHasKey('999999', $mixedJson['data']['failed'] ?? []);

        $countTokenA1 = $pdo->prepare('SELECT COUNT(*) FROM AuthToken WHERE id = ?');
        $countTokenA1->execute([$tokenA1Id]);
        self::assertSame(0, (int)$countTokenA1->fetchColumn());

        $countCfA1 = $pdo->prepare('SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = ? AND itemId = ?');
        $countCfA1->execute([AclActionsInterface::AUTHTOKEN, $tokenA1Id]);
        self::assertSame(0, (int)$countCfA1->fetchColumn());

        $countTokenB2 = $pdo->prepare('SELECT COUNT(*) FROM AuthToken WHERE id = ?');
        $countTokenB2->execute([$tokenB2Id]);
        self::assertSame(1, (int)$countTokenB2->fetchColumn());

        $countCfB2 = $pdo->prepare('SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = ? AND itemId = ?');
        $countCfB2->execute([AclActionsInterface::AUTHTOKEN, $tokenB2Id]);
        self::assertSame(1, (int)$countCfB2->fetchColumn());

        // 5. Application admin can view (redacted), search, and delete any user's token (cleaning up its custom fields)
        $overridesAdmin = [
            Context::class => $this->buildSessionContext($admin),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        $adminView = $this->dispatch('get', 'authToken/view/' . $tokenB1Id, [], [], $overridesAdmin);
        $adminViewJson = $this->assertJsonOk($adminView);
        self::assertStringContainsString('***', (string)($adminViewJson['data']['html'] ?? ''));
        self::assertStringNotContainsString($bearerB1, (string)($adminViewJson['data']['html'] ?? ''));

        $adminSearch = $this->dispatch('get', 'authToken/search', [], [], $overridesAdmin);
        $adminSearchJson = $this->assertJsonOk($adminSearch);
        self::assertStringNotContainsString('data-item-id="' . $tokenA1Id . '"', (string)($adminSearchJson['data']['html'] ?? ''));
        self::assertStringContainsString('data-item-id="' . $tokenB1Id . '"', (string)($adminSearchJson['data']['html'] ?? ''));

        $adminDelete = $this->dispatch('post', 'authToken/delete/' . $tokenB2Id, [], [], $overridesAdmin);
        $this->assertJsonOk($adminDelete, 'Authorization deleted');
        $countCfB2->execute([AclActionsInterface::AUTHTOKEN, $tokenB2Id]);
        self::assertSame(0, (int)$countCfB2->fetchColumn());
    }

    #[Test]
    public function multiActionTokenCreationGeneratesIndependentSecretsAndRejectsDuplicateScope(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedUser($pdo, 'synthetic_dup_a_' . bin2hex(random_bytes(4)), false);
        $userB = $this->seedUser($pdo, 'synthetic_dup_b_' . bin2hex(random_bytes(4)), false);

        $overridesA = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

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
        $firstJson = $this->assertJsonOk($first, 'Authorization added');
        $token1 = (string)($firstJson['data']['token'] ?? '');
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token1);

        // Second token for user A on a different actionId succeeds with an independent token
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
        $secondJson = $this->assertJsonOk($second, 'Authorization added');
        $token2 = (string)($secondJson['data']['token'] ?? '');
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token2);
        self::assertNotSame($token1, $token2);

        // Key-dependent action (ACCOUNT_VIEW) rejects blank password (400) and requires vault when password provided
        $rejectedView = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::ACCOUNT_VIEW,
                'pass' => '',
            ],
            $overridesA
        );
        self::assertSame(400, $rejectedView['code']);
        self::assertStringContainsString('Password cannot be blank', $rejectedView['body']);

        $third = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::ACCOUNT_VIEW,
                'pass' => 'synthetic-view-pass-2026',
            ],
            $overridesA
        );
        $thirdJson = $this->assertJsonOk($third, 'Authorization added');
        $token3 = (string)($thirdJson['data']['token'] ?? '');
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token3);
        self::assertNotSame($token2, $token3);

        $vaultStmt = $pdo->prepare('SELECT vault, hash FROM AuthToken WHERE token = :tok');
        $vaultStmt->execute(['tok' => $token3]);
        $viewRow = $vaultStmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($viewRow);
        self::assertNotEmpty($viewRow['vault']);
        self::assertNotEmpty($viewRow['hash']);

        // Duplicate (userA, TAG_SEARCH) is rejected with HTTP 400 and no leaked secrets
        $duplicate = $this->dispatch(
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
        self::assertSame(400, $duplicate['code']);
        $dupJson = $this->decodeJson($duplicate['body']);
        self::assertSame('ERROR', $dupJson['status'] ?? null);
        self::assertSame('Authorization already exist', $dupJson['description'] ?? null);
        self::assertNull($dupJson['data']);

        $tokStmt = $pdo->prepare('SELECT token FROM AuthToken WHERE userId = :uid');
        $tokStmt->execute(['uid' => $userA->id]);
        $storedTokens = $tokStmt->fetchAll(PDO::FETCH_COLUMN);
        self::assertCount(3, $storedTokens);
        foreach ($storedTokens as $storedToken) {
            self::assertStringNotContainsString((string)$storedToken, $duplicate['body']);
        }

        // User B can create TAG_SEARCH independently
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

    #[Test]
    public function refreshAndScopeChangeRotateOnlyTargetTokenWhileMetadataEditPreservesItInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userA = $this->seedUser($pdo, 'synthetic_ref_a_' . bin2hex(random_bytes(4)), false);
        $userB = $this->seedUser($pdo, 'synthetic_ref_b_' . bin2hex(random_bytes(4)), false);

        $initialTokenA1 = bin2hex(random_bytes(32));
        $initialTokenA2 = bin2hex(random_bytes(32));
        $initialTokenA3 = bin2hex(random_bytes(32));
        $initialTokenB = bin2hex(random_bytes(32));
        $initialHashA = Hash::hashKey('initial-token-pass-a');
        $initialVaultA1 = 'synthetic-initial-vault-1';
        $initialVaultA2 = 'synthetic-initial-vault-2';

        $tokenA1Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $initialTokenA1,
            AclActionsInterface::ACCOUNT_VIEW_PASS,
            (int)$userA->id,
            $initialVaultA1,
            $initialHashA
        );
        $tokenA2Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $initialTokenA2,
            AclActionsInterface::ACCOUNT_CREATE,
            (int)$userA->id,
            $initialVaultA2,
            $initialHashA
        );
        $tokenA3Id = $this->insertAuthTokenRow(
            $pdo,
            (int)$userA->id,
            $initialTokenA3,
            AclActionsInterface::ACCOUNT_SEARCH,
            (int)$userA->id
        );
        $tokenBId = $this->insertAuthTokenRow(
            $pdo,
            (int)$userB->id,
            $initialTokenB,
            AclActionsInterface::TAG_SEARCH,
            (int)$userB->id
        );

        $overridesA = [
            Context::class => $this->buildSessionContext($userA),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        // 1. Metadata-only edit (same actionId, no refresh) preserves existing token and returns data: null
        $metadataEdit = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenA3Id,
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::ACCOUNT_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        $metadataJson = $this->assertJsonOk($metadataEdit, 'Authorization updated');
        self::assertNull($metadataJson['data']);

        // 2. Scope change rotates only the target token and reveals new secret once
        $scopeEdit = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $tokenA3Id,
            [],
            [
                'users' => $userA->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $overridesA
        );
        $scopeJson = $this->assertJsonOk($scopeEdit, 'Authorization updated');
        $rotatedScopeToken = (string)($scopeJson['data']['token'] ?? '');
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $rotatedScopeToken);
        self::assertNotSame($initialTokenA3, $rotatedScopeToken);

        // 3. Explicit refresh rotates ONLY tokenA1, leaving tokenA2 (same user!) and tokenB untouched
        $newTokenPass = 'synthetic-rotated-pass-2026';
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
        $refreshJson = $this->assertJsonOk($refreshResult, 'Authorization updated');
        $rotatedRefreshToken = (string)($refreshJson['data']['token'] ?? '');
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $rotatedRefreshToken);

        $fetchStmt = $pdo->prepare('SELECT id, actionId, token, vault, hash FROM AuthToken WHERE id IN (?, ?, ?, ?) ORDER BY id ASC');
        $fetchStmt->execute([$tokenA1Id, $tokenA2Id, $tokenA3Id, $tokenBId]);
        $rows = [];
        foreach ($fetchStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[(int)$row['id']] = $row;
        }

        // tokenA1 was rotated
        self::assertSame($rotatedRefreshToken, $rows[$tokenA1Id]['token']);
        self::assertNotSame($initialTokenA1, $rows[$tokenA1Id]['token']);
        self::assertNotSame($initialVaultA1, $rows[$tokenA1Id]['vault']);
        self::assertTrue(Hash::checkHashKey($newTokenPass, (string)$rows[$tokenA1Id]['hash']));

        // tokenA2 (userA's 2nd token) was NOT affected by refreshing tokenA1
        self::assertSame($initialTokenA2, $rows[$tokenA2Id]['token']);
        self::assertSame($initialVaultA2, $rows[$tokenA2Id]['vault']);
        self::assertSame($initialHashA, $rows[$tokenA2Id]['hash']);

        // tokenA3 reflects scope change + rotated token
        self::assertSame(AclActionsInterface::CLIENT_SEARCH, (int)$rows[$tokenA3Id]['actionId']);
        self::assertSame($rotatedScopeToken, $rows[$tokenA3Id]['token']);

        // User B's token was unaffected
        self::assertSame($initialTokenB, $rows[$tokenBId]['token']);
    }

    #[Test]
    public function expiryDefaultsCapsAdminNoExpiryAndScopeValidationInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();
        $user = $this->seedUser($pdo, 'synthetic_exp_u_' . bin2hex(random_bytes(4)), false);
        $admin = $this->seedUser($pdo, 'synthetic_exp_a_' . bin2hex(random_bytes(4)), true);

        $overridesUser = [
            Context::class => $this->buildSessionContext($user),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];
        $overridesAdmin = [
            Context::class => $this->buildSessionContext($admin),
            MasterKeyStore::class => $this->buildMasterKeyStore(),
        ];

        // 1. Default 90-day expiry persisted when omitted
        $before = time();
        $defaultCreate = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $user->id,
                'actions' => AclActionsInterface::ACCOUNT_SEARCH,
                'pass' => '',
            ],
            $overridesUser
        );
        $defaultJson = $this->assertJsonOk($defaultCreate, 'Authorization added');
        $after = time();

        $defaultStmt = $pdo->prepare('SELECT id, expireDate FROM AuthToken WHERE token = :tok');
        $defaultStmt->execute(['tok' => $defaultJson['data']['token']]);
        $defaultRow = $defaultStmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($defaultRow);
        $defaultExp = (int)$defaultRow['expireDate'];
        self::assertGreaterThanOrEqual($before + 7776000, $defaultExp);
        self::assertLessThanOrEqual($after + 7776000, $defaultExp);

        // 2. Over-max expiry (>365d) rejected (400) for both non-admin and admin
        foreach ([$overridesUser, $overridesAdmin] as $overrides) {
            $overMax = $this->dispatch(
                'post',
                'authToken/saveCreate',
                [],
                [
                    'users' => $user->id,
                    'actions' => AclActionsInterface::TAG_SEARCH,
                    'pass' => '',
                    'expire_date' => date('Y-m-d', strtotime('+366 days')),
                    'expire_date_initial' => '__new__',
                ],
                $overrides
            );
            self::assertSame(400, $overMax['code']);
            self::assertStringContainsString('Expiration date exceeds the maximum allowed lifetime', $overMax['body']);
        }

        // 3. No-expiry denied (403) for non-admin, allowed (200, expireDate IS NULL) for admin
        $userNoExpiry = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $user->id,
                'actions' => AclActionsInterface::TAG_SEARCH,
                'pass' => '',
                'no_expiry' => 'on',
            ],
            $overridesUser
        );
        self::assertSame(403, $userNoExpiry['code']);

        $adminNoExpiry = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $admin->id,
                'actions' => AclActionsInterface::TAG_SEARCH,
                'pass' => '',
                'no_expiry' => 'on',
            ],
            $overridesAdmin
        );
        $adminNoExpiryJson = $this->assertJsonOk($adminNoExpiry, 'Authorization added');
        $nullStmt = $pdo->prepare('SELECT id, expireDate FROM AuthToken WHERE token = :tok');
        $nullStmt->execute(['tok' => $adminNoExpiryJson['data']['token']]);
        $nullRow = $nullStmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($nullRow);
        self::assertNull($nullRow['expireDate']);

        // 4. Lowered system max lifetime (30d) affects new tokens only, while existing 120d token preserves expiry on metadata edit
        $legacyExpireDate = time() + 120 * 86400;
        $legacyId = $this->insertAuthTokenRow(
            $pdo,
            (int)$user->id,
            bin2hex(random_bytes(32)),
            AclActionsInterface::CLIENT_SEARCH,
            (int)$user->id,
            null,
            null,
            $legacyExpireDate
        );

        $loweredConfigOverrides = array_merge($overridesUser, [
            ConfigFileService::class => $this->buildConfigFileServiceOverride(15 * 86400, 30 * 86400),
        ]);

        $newAbove30 = $this->dispatch(
            'post',
            'authToken/saveCreate',
            [],
            [
                'users' => $user->id,
                'actions' => AclActionsInterface::CATEGORY_SEARCH,
                'pass' => '',
                'expire_date' => date('Y-m-d', strtotime('+45 days')),
                'expire_date_initial' => '__new__',
            ],
            $loweredConfigOverrides
        );
        self::assertSame(400, $newAbove30['code']);

        $metadataEditLegacy = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $legacyId,
            [],
            [
                'users' => $user->id,
                'actions' => AclActionsInterface::CLIENT_SEARCH,
                'pass' => '',
            ],
            $loweredConfigOverrides
        );
        $this->assertJsonOk($metadataEditLegacy, 'Authorization updated');

        $legacyStmt = $pdo->prepare('SELECT expireDate FROM AuthToken WHERE id = ?');
        $legacyStmt->execute([$legacyId]);
        self::assertSame($legacyExpireDate, (int)$legacyStmt->fetchColumn());

        // 5. Scope change on legacy 120d token re-applies fresh 30d cap -> rejected (400)
        $scopeChangeLegacy = $this->dispatch(
            'post',
            'authToken/saveEdit/' . $legacyId,
            [],
            [
                'users' => $user->id,
                'actions' => AclActionsInterface::CATEGORY_SEARCH,
                'pass' => '',
            ],
            $loweredConfigOverrides
        );
        self::assertSame(400, $scopeChangeLegacy['code']);

        // 6. Expired tokens remain visible as non-secret audit metadata in search
        $expiredBearer = 'synthetic-expired-audit-' . bin2hex(random_bytes(8));
        $expiredId = $this->insertAuthTokenRow(
            $pdo,
            (int)$user->id,
            $expiredBearer,
            AclActionsInterface::TAG_SEARCH,
            (int)$user->id,
            null,
            null,
            time() - 3600
        );

        $searchRes = $this->dispatch('get', 'authToken/search', [], [], $overridesUser);
        $searchJson = $this->assertJsonOk($searchRes);
        $searchHtml = (string)($searchJson['data']['html'] ?? '');
        self::assertStringContainsString('data-item-id="' . $expiredId . '"', $searchHtml);
        self::assertStringContainsString('Expired', $searchHtml);
        self::assertStringNotContainsString($expiredBearer, $searchHtml);
    }

    private function seedUser(PDO $pdo, string $login, bool $isAdminApp): UserDto
    {
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, :isAdminApp, 0, 0, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic ' . $login,
            'login' => $login,
            'pass' => Hash::hashKey('synthetic-user-pass-' . $login),
            'email' => $login . '@example.test',
            'isAdminApp' => $isAdminApp ? 1 : 0,
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
            'isAdminApp' => $isAdminApp,
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
        ?string $hash = null,
        ?int    $expireDate = null
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO AuthToken (userId, token, actionId, createdBy, startDate, expireDate, vault, hash)
             VALUES (:userId, :token, :actionId, :createdBy, UNIX_TIMESTAMP(), :expireDate, :vault, :hash)'
        );
        $stmt->execute([
            'userId' => $userId,
            'token' => $token,
            'actionId' => $actionId,
            'createdBy' => $createdBy,
            'expireDate' => $expireDate,
            'vault' => $vault,
            'hash' => $hash,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function insertAuthTokenCustomField(PDO $pdo, int $itemId, string $data): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO CustomFieldData (moduleId, itemId, definitionId, data, `key`)
             VALUES (:moduleId, :itemId, 1, :data, NULL)'
        );
        $stmt->execute([
            'moduleId' => AclActionsInterface::AUTHTOKEN,
            'itemId' => $itemId,
            'data' => $data,
        ]);
    }

    private function buildConfigFileServiceOverride(int $defaultTime, int $maxTime): ConfigFileService
    {
        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            array_merge(
                $this->getConfigData(),
                [
                    'getAuthTokenDefaultTime' => $defaultTime,
                    'getAuthTokenMaxTime' => $maxTime,
                ]
            )
        );

        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        return $configFileService;
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
