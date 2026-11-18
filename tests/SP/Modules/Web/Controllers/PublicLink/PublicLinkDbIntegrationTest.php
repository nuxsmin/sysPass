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

namespace SP\Tests\Modules\Web\Controllers\PublicLink;

use Error;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Account\Services\PublicLink as PublicLinkServiceImpl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\UserDataGenerator;

use function DI\autowire;
use function DI\get;
use function SP\Tests\getDbHandler;

/**
 * DB-backed Web dispatch characterization tests for creator, other-user, and
 * administrator Public Link management with real ACLs, real Account ACLs, and
 * real MariaDB persistence.
 */
#[Group('integration')]
final class PublicLinkDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_OP = 'You don\'t have permission to do this operation';
    private const MASTER_KEY = 'synthetic-master-key-2026';
    private const ACCOUNT_SECRET = 'synthetic-account-secret-2026';

    protected function getConfigData(): array
    {
        return array_merge(
            parent::getConfigData(),
            [
                'isPublinksEnabled' => true,
                'getPublinksMaxTime' => 600,
                'getPublinksMaxViews' => 3,
                'getApplicationUrl' => 'https://example.test/subpath',
            ]
        );
    }

    /**
     * Characterization of current DB persistence on link creation:
     * Both publicLink/saveCreate and publicLink/saveCreateFromAccount pass a
     * PublicLink model whose $userId is null, but PublicLink::getUserId(): int
     * casts null to 0 before the `?? $this->context->getUserData()->getId()` fallback
     * in PublicLink::buildPublicLink(), triggering a MariaDB FK Integrity constraint error.
     */
    #[Test]
    public function saveCreateAndSaveCreateFromAccountFailForeignKeyWithUserIdZero(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $this->seedRealEncryptedPasswordForAccount(1, self::ACCOUNT_SECRET);
        $this->seedRealEncryptedPasswordForAccount(2, self::ACCOUNT_SECRET . '-2');

        $creatorOverrides = $this->realActorOverrides(
            3,
            (new ProfileData())
                ->setAccView(true)
                ->setAccViewPass(true)
                ->setAccPublicLinks(true)
                ->setMgmPublicLinks(true)
        );

        $createResult = $this->dispatch(
            'post',
            'publicLink/saveCreate',
            [],
            ['accountId' => '1', 'notify' => '0'],
            $creatorOverrides
        );
        $this->assertJsonDenied($createResult, 'Integrity constraint');

        $fromAccResult = $this->dispatch(
            'get',
            'publicLink/saveCreateFromAccount/2/1',
            [],
            [],
            $creatorOverrides
        );
        $this->assertJsonDenied($fromAccResult, 'Integrity constraint');

        self::assertSame(0, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink'));
    }

    /**
     * With real Acl + AccountAcl and real MariaDB persistence:
     * - A non-admin user without mgmPublicLinks (even with accPublicLinks) is denied
     *   publicLink/{search,view,edit,delete} and cannot remove the link in DB.
     * - Another non-admin user (user_b, id=4) holding mgmPublicLinks passes ACL checks on
     *   publicLink/{search,view,edit,refresh,delete} for user_a's link:
     *   - refresh updates hash and resets countViews=0 in MariaDB,
     *   - delete removes user_a's row in MariaDB,
     *   - search hits PHP 8.3 Error ("Dynamic properties not allowed" on clientName hydration),
     *   - view/edit pass GetPublicLinkUseCase and fail HTTP 500 ("Wrong object type" + trace)
     *     in SelectItemAdapter because AccountRepository::getForUser() returns Simple objects.
     * - An admin (id=1) exhibits the same management access (no creator scoping) and persists
     *   refresh/delete on user_a's link.
     */
    #[Test]
    public function creatorOtherUserAndAdminManagementAccessWithRealPersistence(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $this->seedRealEncryptedPasswordForAccount(1, self::ACCOUNT_SECRET);
        $this->seedRealEncryptedPasswordForAccount(2, self::ACCOUNT_SECRET . '-2');

        [$linkId, $initialHash] = $this->seedRealPublicLinkRow(1, 3, self::ACCOUNT_SECRET);

        // Other non-admin user (user_b, id=4) WITHOUT mgmPublicLinks (only accPublicLinks) is denied management routes
        $limitedUserOverrides = $this->realActorOverrides(
            4,
            (new ProfileData())
                ->setAccView(true)
                ->setAccViewPass(true)
                ->setAccPublicLinks(true)
                ->setMgmPublicLinks(false)
        );

        $this->assertJsonDenied($this->dispatch('get', 'publicLink/search', [], [], $limitedUserOverrides), self::DENIED_OP);
        $this->assertJsonDenied($this->dispatch('get', 'publicLink/view/' . $linkId, [], [], $limitedUserOverrides), self::DENIED_OP);
        $this->assertJsonDenied($this->dispatch('get', 'publicLink/edit/' . $linkId, [], [], $limitedUserOverrides), self::DENIED_OP);
        $this->assertJsonDenied($this->dispatch('get', 'publicLink/delete/' . $linkId, [], [], $limitedUserOverrides), self::DENIED_OP);
        self::assertSame(1, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE id = ' . $linkId));

        // Other non-admin user (user_b, id=4) WITH mgmPublicLinks can manage, refresh, and delete user_a's link
        $otherManagerOverrides = $this->realActorOverrides(
            4,
            (new ProfileData())
                ->setAccView(true)
                ->setAccViewPass(true)
                ->setAccPublicLinks(false)
                ->setMgmPublicLinks(true)
        );

        $searchRes = $this->dispatch('get', 'publicLink/search', [], [], $otherManagerOverrides);
        self::assertSame(500, $searchRes['code']);
        $searchJson = $this->decodeJson($searchRes['body']);
        self::assertSame('ERROR', $searchJson['status'] ?? null);
        self::assertSame('Internal error', $searchJson['description'] ?? null);
        self::assertArrayHasKey('data', $searchJson);
        self::assertNull($searchJson['data']);
        self::assertStringNotContainsString('Dynamic properties not allowed', $searchRes['body']);

        $viewRes = $this->dispatch('get', 'publicLink/view/' . $linkId, [], [], $otherManagerOverrides);
        self::assertSame(500, $viewRes['code']);
        $viewJson = $this->decodeJson($viewRes['body']);
        self::assertSame('Internal error', $viewJson['description'] ?? null);
        self::assertArrayHasKey('data', $viewJson);
        self::assertNull($viewJson['data']);

        $editRes = $this->dispatch('get', 'publicLink/edit/' . $linkId, [], [], $otherManagerOverrides);
        self::assertSame(500, $editRes['code']);
        $editJson = $this->decodeJson($editRes['body']);
        self::assertSame('Internal error', $editJson['description'] ?? null);
        self::assertArrayHasKey('data', $editJson);
        self::assertNull($editJson['data']);

        $this->assertJsonOk(
            $this->dispatch('get', 'publicLink/refresh/' . $linkId, [], [], $otherManagerOverrides),
            'Link updated'
        );
        $refreshedHash = (string) $this->fetchValue('SELECT hash FROM PublicLink WHERE id = ' . $linkId);
        self::assertNotSame($initialHash, $refreshedHash);
        self::assertMatchesRegularExpression('/^[0-9a-f]{60}$/', $refreshedHash);
        self::assertSame(0, (int) $this->fetchValue('SELECT countViews FROM PublicLink WHERE id = ' . $linkId));

        $this->assertJsonOk(
            $this->dispatch('get', 'publicLink/delete/' . $linkId, [], [], $otherManagerOverrides),
            'Link deleted'
        );
        self::assertSame(0, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE id = ' . $linkId));

        // Creator (user_a, id=3) owns link on Account 2; Admin (id=1) manages, refreshes & deletes it with real ACLs
        [$link2Id, $link2Hash] = $this->seedRealPublicLinkRow(2, 3, self::ACCOUNT_SECRET . '-2');

        $adminOverrides = $this->realActorOverrides(1, null);
        $adminSearchRes = $this->dispatch('get', 'publicLink/search', [], [], $adminOverrides);
        self::assertSame(500, $adminSearchRes['code']);
        $adminSearchJson = $this->decodeJson($adminSearchRes['body']);
        self::assertSame('ERROR', $adminSearchJson['status'] ?? null);
        self::assertSame('Internal error', $adminSearchJson['description'] ?? null);
        self::assertArrayHasKey('data', $adminSearchJson);
        self::assertNull($adminSearchJson['data']);
        self::assertStringNotContainsString('Dynamic properties not allowed', $adminSearchRes['body']);

        $adminView = $this->dispatch('get', 'publicLink/view/' . $link2Id, [], [], $adminOverrides);
        self::assertSame(500, $adminView['code']);
        $adminViewJson = $this->decodeJson($adminView['body']);
        self::assertSame('Internal error', $adminViewJson['description'] ?? null);
        self::assertArrayHasKey('data', $adminViewJson);
        self::assertNull($adminViewJson['data']);

        $this->assertJsonOk(
            $this->dispatch('get', 'publicLink/refresh/' . $link2Id, [], [], $adminOverrides),
            'Link updated'
        );
        self::assertNotSame($link2Hash, (string) $this->fetchValue('SELECT hash FROM PublicLink WHERE id = ' . $link2Id));

        $this->assertJsonOk(
            $this->dispatch('get', 'publicLink/delete/' . $link2Id, [], [], $adminOverrides),
            'Link deleted'
        );
        self::assertSame(0, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE id = ' . $link2Id));
    }

    /**
     * When the link creator loses visibility to the underlying Account (account becomes private to another user),
     * refreshing fails AccountPermissionException and leaves hash and dateExpire untouched in MariaDB,
     * while management view (up to SelectItemAdapter) and delete still bypass Account visibility if mgmPublicLinks is held.
     */
    #[Test]
    public function creatorLosingAccountAccessCannotRefreshButRetainsProfileBasedManagement(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $this->seedRealEncryptedPasswordForAccount(1, self::ACCOUNT_SECRET);

        $creatorOverrides = $this->realActorOverrides(
            3,
            (new ProfileData())
                ->setAccView(true)
                ->setAccViewPass(true)
                ->setAccPublicLinks(true)
                ->setMgmPublicLinks(true)
        );

        [$linkId, $originalHash] = $this->seedRealPublicLinkRow(1, 3, self::ACCOUNT_SECRET);

        // Creator can refresh while Account 1 is visible
        $this->assertJsonOk($this->dispatch('get', 'publicLink/refresh/' . $linkId, [], [], $creatorOverrides), 'Link updated');
        $refreshedHash = (string) $this->fetchValue('SELECT hash FROM PublicLink WHERE id = ' . $linkId);
        $refreshedExpire = (int) $this->fetchValue('SELECT dateExpire FROM PublicLink WHERE id = ' . $linkId);
        self::assertNotSame($originalHash, $refreshedHash);

        // Revoke user_a's access to Account 1 (make Account 1 private to admin id=1 / group id=1)
        $pdo->exec('UPDATE Account SET isPrivate = 1, isPrivateGroup = 1, userId = 1, userGroupId = 1 WHERE id = 1');

        $refreshDenied = $this->dispatch('get', 'publicLink/refresh/' . $linkId, [], [], $creatorOverrides);
        $this->assertResponseWasDispatched($refreshDenied);
        $decoded = $this->decodeJson($refreshDenied['body']);
        self::assertSame('ERROR', $decoded['status'] ?? null);
        self::assertSame($refreshedHash, (string) $this->fetchValue('SELECT hash FROM PublicLink WHERE id = ' . $linkId));
        self::assertSame($refreshedExpire, (int) $this->fetchValue('SELECT dateExpire FROM PublicLink WHERE id = ' . $linkId));

        // Management view & delete check only Acl::PUBLICLINK_*, not Account visibility
        $viewResult = $this->dispatch('get', 'publicLink/view/' . $linkId, [], [], $creatorOverrides);
        self::assertSame(500, $viewResult['code']);
        $viewResultJson = $this->decodeJson($viewResult['body']);
        self::assertSame('Internal error', $viewResultJson['description'] ?? null);
        self::assertArrayHasKey('data', $viewResultJson);
        self::assertNull($viewResultJson['data']);

        $this->assertJsonOk($this->dispatch('get', 'publicLink/delete/' . $linkId, [], [], $creatorOverrides), 'Link deleted');
        self::assertSame(0, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE id = ' . $linkId));
    }

    /**
     * Batch delete is non-atomic: deleting [$validId, 999999] removes $validId in MariaDB
     * even though DeletePublicLinksUseCase/PublicLink::deleteByIdBatch throws ServiceException
     * and the controller returns an ERROR payload.
     */
    #[Test]
    public function batchDeletePartialHitRemovesExistingRowDespiteErrorResponse(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $this->seedRealEncryptedPasswordForAccount(1, self::ACCOUNT_SECRET);

        [$linkId] = $this->seedRealPublicLinkRow(1, 1, self::ACCOUNT_SECRET);
        $adminOverrides = $this->realActorOverrides(1, null);

        $batchResult = $this->dispatch(
            'get',
            'publicLink/delete',
            ['items' => [$linkId, 999999]],
            [],
            $adminOverrides
        );

        $this->assertJsonDenied($batchResult, 'Error while removing the links');
        self::assertSame(0, (int) $this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE id = ' . $linkId));
    }

    /**
     * End-to-end check of the generated URL vs the public visitor route against MariaDB:
     * - publicLink/view/<id> fails HTTP 500 ("Internal error", data=null) when SelectItemAdapter throws RuntimeException,
     * - PublicLink::getLinkForHash('https://example.test/subpath', $hash) points to
     *   index.php?r=publicLink/view/<hash>, which returns empty 200 to anonymous visitors
     *   and HTTP 500 ("Internal error", data=null) to authenticated managers,
     * - account/viewLink/<hash> discloses the decrypted synthetic secret to an anonymous visitor
     *   and increments countViews in MariaDB.
     */
    #[Test]
    public function endToEndGeneratedPublicLinkUrlVsAccountViewLink(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $this->seedRealEncryptedPasswordForAccount(1, self::ACCOUNT_SECRET);

        [$linkId, $hash] = $this->seedRealPublicLinkRow(
            1,
            1,
            self::ACCOUNT_SECRET,
            'aa' . str_repeat('b0', 29)
        );
        $adminOverrides = $this->realActorOverrides(1, null);

        $viewRes = $this->dispatch('get', 'publicLink/view/' . $linkId, [], [], $adminOverrides);
        self::assertSame(500, $viewRes['code']);
        $viewResJson = $this->decodeJson($viewRes['body']);
        self::assertSame('Internal error', $viewResJson['description'] ?? null);
        self::assertArrayHasKey('data', $viewResJson);
        self::assertNull($viewResJson['data']);

        $generatedUrl = PublicLinkServiceImpl::getLinkForHash('https://example.test/subpath', $hash);
        self::assertSame('https://example.test/subpath/index.php?r=publicLink/view/' . $hash, $generatedUrl);

        parse_str((string) parse_url($generatedUrl, PHP_URL_QUERY), $params);

        // Anonymous dispatch to generated URL hits SessionTimeout (empty 200)
        $anonResult = $this->dispatch(
            'get',
            $params['r'],
            [],
            [],
            [Context::class => $this->getUnauthenticatedContext()]
        );
        self::assertSame(200, $anonResult['code']);
        self::assertSame('', $anonResult['body']);

        // Authenticated dispatch to generated URL with leading hex letter casts to int 0 -> new PublicLinkList() -> HTTP 500 Internal error
        $authGeneratedResult = $this->dispatch('get', $params['r'], [], [], $adminOverrides);
        self::assertSame(500, $authGeneratedResult['code']);
        $authGenJson = $this->decodeJson($authGeneratedResult['body']);
        self::assertSame('Internal error', $authGenJson['description'] ?? null);
        self::assertArrayHasKey('data', $authGenJson);
        self::assertNull($authGenJson['data']);

        // Authenticated dispatch with leading digits (e.g. "99...") casts to non-existent int ID -> HTTP 500 Link not found
        $digitGeneratedResult = $this->dispatch(
            'get',
            'publicLink/view/' . ('99' . str_repeat('a', 58)),
            [],
            [],
            $adminOverrides
        );
        self::assertSame(500, $digitGeneratedResult['code']);
        self::assertSame('Link not found', $this->decodeJson($digitGeneratedResult['body'])['description'] ?? null);

        // Public visitor route account/viewLink/<hash> succeeds and discloses the synthetic secret
        $visitorResult = $this->dispatch(
            'get',
            'account/viewLink/' . $hash,
            [],
            [],
            [Context::class => $this->getUnauthenticatedContext()]
        );
        self::assertSame(200, $visitorResult['code']);
        self::assertStringContainsString(self::ACCOUNT_SECRET, $visitorResult['body']);
        self::assertSame(3, (int) $this->fetchValue('SELECT countViews FROM PublicLink WHERE id = ' . $linkId));
    }

    /**
     * @return array{0: int, 1: string, 2: int}
     */
    private function seedRealPublicLinkRow(
        int $accountId,
        int $userId,
        string $secret,
        ?string $fixedHash = null
    ): array {
        $pdo = getDbHandler()->getConnection();
        $key = new PublicLinkKey($this->passwordSalt, $fixedHash);
        $expire = time() + 600;

        $accountModel = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => $accountId,
            'name' => $accountId === 1 ? 'Google' : 'Apple',
            'pass' => $secret,
            'userName' => 'Synthetic Owner',
            'userEditName' => 'Synthetic Editor',
            'userEditLogin' => 'synthetic_editor',
            'userGroupName' => 'Synthetic Group',
            'clientName' => 'Google',
            'categoryName' => 'SSH',
            'dateAdd' => '2026-01-01 00:00:00',
        ]);
        $vault = Vault::factory(new Crypt())->saveData(
            Serde::serialize(Simple::buildFromSimpleModel($accountModel)),
            $key->getKey()
        );

        $stmt = $pdo->prepare(
            'INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire, maxCountViews, countViews, totalCountViews, useInfo)
             VALUES (:itemId, :hash, :data, :userId, 1, 0, UNIX_TIMESTAMP(), :dateExpire, 3, 2, 2, :useInfo)'
        );
        $stmt->execute([
            'itemId' => $accountId,
            'hash' => $key->getHash(),
            'data' => $vault->getSerialized(),
            'userId' => $userId,
            'dateExpire' => $expire,
            'useInfo' => Serde::serialize([]),
        ]);

        return [(int) $pdo->lastInsertId(), $key->getHash(), $expire];
    }

    /**
     * @return array<class-string, mixed>
     */
    private function realActorOverrides(int $userId, ?ProfileData $profileOverride): array
    {
        $pdo = getDbHandler()->getConnection();
        $row = $pdo->query('SELECT * FROM User WHERE id = ' . $userId)->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row, 'Fixture user not found: ' . $userId);

        if ($profileOverride === null) {
            $rawProfile = $pdo->query('SELECT profile FROM UserProfile WHERE id = ' . (int) $row['userProfileId'])
                ->fetchColumn();
            $profile = Serde::deserialize((string) $rawProfile, ProfileData::class);
        } else {
            $profile = $profileOverride;
        }

        $userDto = UserDto::fromModel(
            UserDataGenerator::factory()->buildUserData()->mutate([
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'userGroupId' => (int) $row['userGroupId'],
                'login' => (string) $row['login'],
                'ssoLogin' => $row['ssoLogin'],
                'email' => (string) ($row['email'] ?? ''),
                'userProfileId' => (int) $row['userProfileId'],
                'isAdminApp' => (bool) $row['isAdminApp'],
                'isAdminAcc' => (bool) $row['isAdminAcc'],
                'isDisabled' => (bool) $row['isDisabled'],
                'isChangePass' => (bool) $row['isChangePass'],
                'isChangedPass' => (bool) $row['isChangedPass'],
                'isLdap' => (bool) $row['isLdap'],
                'isMigrate' => (bool) $row['isMigrate'],
                'lastUpdate' => $row['lastUpdate'] !== null ? (string) $row['lastUpdate'] : null,
            ])
        );

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($userDto);
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);

        $masterKeyStore = new class (self::MASTER_KEY) implements MasterKeyStore {
            public function __construct(private string $masterKey)
            {
            }

            public function get(): string
            {
                return $this->masterKey;
            }

            public function set(string $masterKey): void
            {
                $this->masterKey = $masterKey;
            }

            public function clear(): void
            {
                $this->masterKey = '';
            }
        };

        return [
            Context::class => $context,
            MasterKeyStore::class => $masterKeyStore,
            AclInterface::class => autowire(Acl::class)->constructorParameter('actions', get(ActionsInterface::class)),
            AccountAclService::class => autowire(AccountAcl::class),
        ];
    }

    private function seedRealEncryptedPasswordForAccount(int $accountId, string $secret): void
    {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_KEY);
        $encrypted = $crypt->encrypt($secret, $key, self::MASTER_KEY);

        $stmt = getDbHandler()->getConnection()->prepare('UPDATE Account SET pass = :pass, `key` = :key WHERE id = :id');
        $stmt->execute([
            'pass' => $encrypted,
            'key' => $key,
            'id' => $accountId,
        ]);
    }

    private function getUnauthenticatedContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getUserData')->willReturn(new UserDto());

        return $context;
    }
}
