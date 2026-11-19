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

namespace SP\Tests\Modules\Web\Controllers\AccountFile;

use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function DI\autowire;
use function DI\get;
use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for Web AccountFile authorization,
 * MIME/size validation, list limit, search ACL, and non-atomic batch deletion
 * against MariaDB.
 */
#[Group('integration')]
final class AccountFileAuthorizationDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';
    private const ONE_PIXEL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    #[Test]
    public function anonymousAndIncompleteSessionsAreRejectedOnAccountFileRoutes(): void
    {
        $pdo = getDbHandler()->getConnection();
        $initialCount = (int)$pdo->query('SELECT COUNT(*) FROM AccountFile')->fetchColumn();

        $profile = (new ProfileData())
            ->setAccFiles(true)
            ->setMgmFiles(true);
        $user = $this->seedUser($pdo, 'file_incomplete_user', 5, $profile);

        foreach ([
            'anonymous' => [false, false, null, null],
            'incomplete' => [true, false, $user, $profile],
        ] as $label => [$loggedIn, $authCompleted, $actor, $actorProfile]) {
            foreach ([
                ['get', 'accountFile/list/1'],
                ['get', 'accountFile/search'],
                ['get', 'accountFile/view/1'],
                ['get', 'accountFile/download/1'],
                ['post', 'accountFile/delete/1'],
            ] as [$method, $route]) {
                $res = $this->dispatchSessionState($method, $route, $loggedIn, $authCompleted, $actor, $actorProfile);
                self::assertSame('', $res['body'], "$label $route");
            }

            $tmpUpload = tempnam(TMP_PATH, 'anon-upload-');
            self::assertNotFalse($tmpUpload);

            try {
                file_put_contents($tmpUpload, 'blocked upload content');
                $uploadRes = $this->dispatchSessionState(
                    'post',
                    'accountFile/upload/1',
                    $loggedIn,
                    $authCompleted,
                    $actor,
                    $actorProfile,
                    [],
                    [],
                    [
                        'inFile' => [
                            'name' => 'blocked.txt',
                            'tmp_name' => $tmpUpload,
                            'size' => filesize($tmpUpload),
                            'type' => 'text/plain',
                        ],
                    ]
                );
                self::assertSame('', $uploadRes['body'], "$label accountFile/upload/1");
            } finally {
                @unlink($tmpUpload);
            }
        }

        self::assertSame($initialCount, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile')->fetchColumn());
    }

    #[Test]
    public function listViewDownloadUploadDeleteLackProfileAclAndAccountVisibilityChecks(): void
    {
        $pdo = getDbHandler()->getConnection();

        $emptyProfile = new ProfileData();
        $unprivilegedUser = $this->seedUser($pdo, 'file_unpriv_user', 5, $emptyProfile);

        // Admin-owned isPrivate=1 account with 2 files seeded
        $privateAccId = $this->seedAccount($pdo, 'Admin Private With Files', 1, 1, 1, 0);
        $pngBytes = base64_decode(self::ONE_PIXEL_PNG_BASE64, true);
        self::assertNotFalse($pngBytes);

        $txtFileId = $this->seedAccountFile(
            $pdo,
            $privateAccId,
            'a-private-secret.txt',
            'text/plain',
            'TXT',
            'SECRET_PRIVATE_FILE_PAYLOAD <tag>',
            'no_thumb'
        );
        $pngFileId = $this->seedAccountFile(
            $pdo,
            $privateAccId,
            'b-private-image.png',
            'image/png',
            'PNG',
            $pngBytes,
            base64_encode($pngBytes)
        );

        // 1. List renders private account file metadata despite blank ProfileData and no Account access,
        //    and AccountFileRepository::getByAccountId() applies LIMIT 1 so the 2nd file is omitted.
        $listRes = $this->dispatchAsUser(
            'get',
            'accountFile/list/' . $privateAccId,
            $unprivilegedUser,
            $emptyProfile,
            ['del' => '1'],
            [],
            [],
            ['isFilesEnabled' => true]
        );
        $this->assertResponseWasDispatched($listRes);
        self::assertStringContainsString('a-private-secret.txt', $listRes['body']);
        self::assertStringNotContainsString('b-private-image.png', $listRes['body']);
        // Acl::getRouteFor() returns action routes regardless of user permissions, exposing delete/download/view links
        self::assertStringContainsString('data-action-route="accountFile/delete"', $listRes['body']);
        self::assertStringContainsString('data-action-route="accountFile/download"', $listRes['body']);
        self::assertStringContainsString('data-action-route="accountFile/view"', $listRes['body']);

        // 2. View discloses text/plain and image/png from isPrivate=1 account; XML returns WARNING; missing returns ERROR
        $viewTxt = $this->dispatchAsUser('get', 'accountFile/view/' . $txtFileId, $unprivilegedUser, $emptyProfile);
        $viewTxtJson = $this->assertJsonOk($viewTxt);
        self::assertStringContainsString('SECRET_PRIVATE_FILE_PAYLOAD &lt;tag&gt;', (string)($viewTxtJson['data']['html'] ?? ''));

        $viewPng = $this->dispatchAsUser('get', 'accountFile/view/' . $pngFileId, $unprivilegedUser, $emptyProfile);
        $viewPngJson = $this->assertJsonOk($viewPng);
        self::assertStringContainsString(
            chunk_split(base64_encode($pngBytes)),
            (string)($viewPngJson['data']['html'] ?? '')
        );

        // Fixture id=1 is application/xml (not image or text/plain)
        $viewXml = $this->dispatchAsUser('get', 'accountFile/view/1', $unprivilegedUser, $emptyProfile);
        $viewXmlJson = $this->decodeJson($viewXml['body']);
        self::assertSame('WARNING', $viewXmlJson['status'] ?? null);
        self::assertSame('File not supported for preview', $viewXmlJson['description'] ?? null);

        $viewMissing = $this->dispatchAsUser('get', 'accountFile/view/999999', $unprivilegedUser, $emptyProfile);
        $viewMissingJson = $this->decodeJson($viewMissing['body']);
        self::assertSame('ERROR', $viewMissingJson['status'] ?? null);
        self::assertSame('File not found', $viewMissingJson['description'] ?? null);

        // 3. Download returns exact bytes from isPrivate=1 account to unprivileged user
        $downloadRes = $this->dispatchAsUser('get', 'accountFile/download/' . $txtFileId, $unprivilegedUser, $emptyProfile);
        $this->assertResponseWasDispatched($downloadRes);
        self::assertSame('SECRET_PRIVATE_FILE_PAYLOAD <tag>', $downloadRes['body']);

        // 4. Upload succeeds onto Admin's isPrivate=1 account even with blank ProfileData AND isFilesEnabled=false,
        //    trusts client-declared MIME type over detected binary content, enforces decimal 1000-byte multiplier,
        //    and fails on 0-byte upload (SplFileObject::fread(0) ValueError).
        $binarySpoofFile = tempnam(TMP_PATH, 'spoof-');
        $oversize1001File = tempnam(TMP_PATH, 'over1001-');
        $emptyFile = tempnam(TMP_PATH, 'empty0-');
        self::assertNotFalse($binarySpoofFile);
        self::assertNotFalse($oversize1001File);
        self::assertNotFalse($emptyFile);

        try {
            $spoofPayload = "\x00\x01\x02\x7fELF-SPOOFED-AS-TEXT";
            file_put_contents($binarySpoofFile, $spoofPayload);
            file_put_contents($oversize1001File, str_repeat('A', 1001));
            file_put_contents($emptyFile, '');

            $uploadConfig = [
                'isFilesEnabled' => false,
                'getFilesAllowedMime' => ['text/plain'],
                'getFilesAllowedSize' => 1,
            ];

            $spoofRes = $this->dispatchAsUser(
                'post',
                'accountFile/upload/' . $privateAccId,
                $unprivilegedUser,
                $emptyProfile,
                [],
                [],
                [
                    'inFile' => [
                        'name' => 'spoofed.bin',
                        'tmp_name' => $binarySpoofFile,
                        'size' => strlen($spoofPayload),
                        'type' => 'text/plain',
                    ],
                ],
                $uploadConfig
            );
            $this->assertJsonOk($spoofRes, 'File saved');
            $uploadedId = (int)$pdo->query(
                "SELECT id FROM AccountFile WHERE accountId = $privateAccId AND name = 'spoofed.bin'"
            )->fetchColumn();
            self::assertGreaterThan(0, $uploadedId);

            // 1001 bytes exceeds allowedSize=1 * 1000 (decimal, not 1024 KiB)
            $overRes = $this->dispatchAsUser(
                'post',
                'accountFile/upload/' . $privateAccId,
                $unprivilegedUser,
                $emptyProfile,
                [],
                [],
                [
                    'inFile' => [
                        'name' => 'over1001.txt',
                        'tmp_name' => $oversize1001File,
                        'size' => 1001,
                        'type' => 'text/plain',
                    ],
                ],
                $uploadConfig
            );
            $overJson = $this->decodeJson($overRes['body']);
            self::assertSame('ERROR', $overJson['status'] ?? null);
            self::assertSame('File size exceeded', $overJson['description'] ?? null);

            // 0-byte upload fails FileHandler::readToString() (ValueError caught -> FileException)
            $emptyRes = $this->dispatchAsUser(
                'post',
                'accountFile/upload/' . $privateAccId,
                $unprivilegedUser,
                $emptyProfile,
                [],
                [],
                [
                    'inFile' => [
                        'name' => 'empty.txt',
                        'tmp_name' => $emptyFile,
                        'size' => 0,
                        'type' => 'text/plain',
                    ],
                ],
                $uploadConfig
            );
            $emptyJson = $this->decodeJson($emptyRes['body']);
            self::assertSame('ERROR', $emptyJson['status'] ?? null);
            self::assertSame('Internal error while reading the file', $emptyJson['description'] ?? null);
        } finally {
            @unlink($binarySpoofFile);
            @unlink($oversize1001File);
            @unlink($emptyFile);
        }

        // 5. Unprivileged user deletes file on Admin's isPrivate=1 account
        $deleteRes = $this->dispatchAsUser('post', 'accountFile/delete/' . $txtFileId, $unprivilegedUser, $emptyProfile);
        $this->assertJsonOk($deleteRes, 'File deleted');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $txtFileId)->fetchColumn());
    }

    #[Test]
    public function searchDeniesNonAdminAppEvenWithFileFlagsAndAdminAppSeesPrivateAccountFiles(): void
    {
        $pdo = getDbHandler()->getConnection();

        $fileProfile = (new ProfileData())
            ->setAccFiles(true)
            ->setMgmFiles(true)
            ->setAccView(true);

        // Even with isAccFiles + isMgmFiles + isAdminAcc=true, Acl::checkUserAccess(ACCOUNT_FILE_SEARCH=25) returns false
        $fileMgrAccAdmin = $this->seedUser($pdo, 'file_mgr_acc_admin', 5, $fileProfile, false, true);
        $appAdmin = $this->seedUser($pdo, 'file_app_admin', 5, $fileProfile, true, false);

        $privateAccId = $this->seedAccount($pdo, 'Private Search Account', 1, 1, 1, 0);
        $privGrpAccId = $this->seedAccount($pdo, 'PrivateGroup Search Account', 1, 1, 0, 1);

        $this->seedAccountFile($pdo, $privateAccId, 'priv-search-secret.txt', 'text/plain', 'TXT', 'priv', 'no_thumb');
        $this->seedAccountFile($pdo, $privGrpAccId, 'privgrp-search-secret.txt', 'text/plain', 'TXT', 'privgrp', 'no_thumb');

        // 1. Non-adminApp denied on accountFile/search because ACCOUNT_FILE_SEARCH (25) is missing from Acl::checkUserAccess
        $deniedRes = $this->dispatchAsUser('post', 'accountFile/search', $fileMgrAccAdmin, $fileProfile, [], ['search' => 'priv']);
        $this->assertJsonDenied($deniedRes, "You don't have permission to do this operation");

        // 2. If ACL passes (e.g. stubbed or adminApp), AccountFileRepository::search() applies NO AccountFilter visibility check
        $adminRes = $this->dispatchAsUser('post', 'accountFile/search', $appAdmin, $fileProfile, [], ['search' => 'priv']);
        $adminJson = $this->assertJsonOk($adminRes);
        $html = (string)($adminJson['data']['html'] ?? '');
        self::assertStringContainsString('priv-search-secret.txt', $html);
        self::assertStringContainsString('privgrp-search-secret.txt', $html);
        self::assertStringContainsString('Private Search Account', $html);
        self::assertStringContainsString('PrivateGroup Search Account', $html);
    }

    #[Test]
    public function batchDeleteDeletesValidFilesWithoutTransactionOnMissingOrDuplicateIds(): void
    {
        $pdo = getDbHandler()->getConnection();

        $emptyProfile = new ProfileData();
        $unprivilegedUser = $this->seedUser($pdo, 'file_batch_del_user', 5, $emptyProfile);

        $privateAccId = $this->seedAccount($pdo, 'Batch File Owner Priv', 1, 1, 1, 0);
        $f1 = $this->seedAccountFile($pdo, $privateAccId, 'batch-1.txt', 'text/plain', 'TXT', 'one', 'no_thumb');
        $f2 = $this->seedAccountFile($pdo, $privateAccId, 'batch-2.txt', 'text/plain', 'TXT', 'two', 'no_thumb');

        // 1. Empty items array returns OK "Files deleted" without touching rows
        $emptyBatch = $this->dispatchAsUser(
            'post',
            'accountFile/delete',
            $unprivilegedUser,
            $emptyProfile,
            [],
            ['items' => []]
        );
        $this->assertJsonOk($emptyBatch, 'Files deleted');
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $f1)->fetchColumn());

        // 2. Mixed [valid, missing] returns ERROR yet deletes $f1 in MariaDB (no transaction)
        $mixedBatch = $this->dispatchAsUser(
            'post',
            'accountFile/delete',
            $unprivilegedUser,
            $emptyProfile,
            [],
            ['items' => [$f1, 999999]]
        );
        $mixedJson = $this->decodeJson($mixedBatch['body']);
        self::assertSame('ERROR', $mixedJson['status'] ?? null);
        self::assertSame('Error while deleting the files', $mixedJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $f1)->fetchColumn());

        // 3. Duplicate [$f2, $f2] deletes $f2 in MariaDB despite returning ERROR (affected=1 !== count=2)
        $dupBatch = $this->dispatchAsUser(
            'post',
            'accountFile/delete',
            $unprivilegedUser,
            $emptyProfile,
            [],
            ['items' => [$f2, $f2]]
        );
        $dupJson = $this->decodeJson($dupBatch['body']);
        self::assertSame('ERROR', $dupJson['status'] ?? null);
        self::assertSame('Error while deleting the files', $dupJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $f2)->fetchColumn());
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $files
     * @param array<string, mixed> $configOverrides
     * @return array{body: string, code: int}
     */
    private function dispatchAsUser(
        string      $method,
        string      $route,
        UserDto     $user,
        ProfileData $profile,
        array       $paramsGet = [],
        array       $paramsPost = [],
        array       $files = [],
        array       $configOverrides = []
    ): array {
        return $this->dispatchSessionState(
            $method,
            $route,
            true,
            true,
            $user,
            $profile,
            $paramsGet,
            $paramsPost,
            $files,
            $configOverrides
        );
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $files
     * @param array<string, mixed> $configOverrides
     * @return array{body: string, code: int}
     */
    private function dispatchSessionState(
        string       $method,
        string       $route,
        bool         $loggedIn,
        bool         $authCompleted,
        ?UserDto     $user,
        ?ProfileData $profile,
        array        $paramsGet = [],
        array        $paramsPost = [],
        array        $files = [],
        array        $configOverrides = []
    ): array {
        $context = $this->buildSessionContext($loggedIn, $authCompleted, $user, $profile);
        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            array_merge($this->getConfigData(), $configOverrides)
        );
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        $request = static::buildRequest(
            $method,
            'index.php',
            array_merge(['r' => $route], $paramsGet),
            $paramsPost,
            $files
        );

        $container = $this->buildContainer(
            $request,
            [
                Context::class => $context,
                ConfigFileService::class => $configFileService,
                ConfigDataInterface::class => $configData,
                AclInterface::class => autowire(Acl::class)
                    ->constructorParameter('actions', get(ActionsInterface::class)),
                AccountAclService::class => autowire(AccountAcl::class)
                    ->constructorParameter('fileCache', null),
            ]
        );

        ob_start();
        try {
            static::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'code' => (int)$response->code(),
        ];
    }

    private function buildSessionContext(
        bool         $loggedIn,
        bool         $authCompleted,
        ?UserDto     $user,
        ?ProfileData $profile
    ): SessionContext {
        $sidStartTime = time();
        $vault = self::createStub(VaultInterface::class);
        $vault->method('getData')->willReturn(self::MASTER_PASS);
        $vault->method('reKey')->willReturnSelf();
        $transient = ['_masterpass' => self::MASTER_PASS];

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn($loggedIn);
        $context->method('getAuthCompleted')->willReturn($authCompleted);
        $context->method('getUserData')->willReturn($user ?? new UserDto());
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getSearchFilters')->willReturn(null);
        $context->method('getSidStartTime')->willReturn($sidStartTime);
        $context->method('getVault')->willReturn($vault);
        $context->method('getTrasientKey')->willReturnCallback(
            static fn(string $key, mixed $default = null) => $transient[$key] ?? $default
        );
        $context->method('setTrasientKey')->willReturnCallback(
            static function (string $key, mixed $value) use (&$transient) {
                $transient[$key] = $value;

                return $value;
            }
        );

        return $context;
    }

    private function seedUser(
        PDO         $pdo,
        string      $login,
        int         $userGroupId,
        ProfileData $profile,
        bool        $isAdminApp = false,
        bool        $isAdminAcc = false
    ): UserDto {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
            $isAdminApp ? 1 : 0,
            $isAdminAcc ? 1 : 0,
            sha1($login),
            Serde::serialize(new UserPreferences(['lang' => 'en_US'])),
        ]);
        $userId = (int)$pdo->lastInsertId();

        return UserDto::fromModel(UserDataGenerator::factory()->buildUserData())->mutate([
            'id' => $userId,
            'userGroupId' => $userGroupId,
            'userProfileId' => $profileId,
            'login' => $login,
            'ssoLogin' => $login,
            'name' => 'Synthetic ' . $login,
            'email' => $login . '@example.test',
            'lastUpdateMPass' => 1528236611,
            'isAdminApp' => $isAdminApp,
            'isAdminAcc' => $isAdminAcc,
            'isDisabled' => false,
            'isChangePass' => false,
            'isChangedPass' => false,
            'isLdap' => false,
            'isMigrate' => false,
            'preferences' => new UserPreferences(['lang' => 'en_US']),
        ]);
    }

    private function seedAccount(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        int    $isPrivate = 0,
        int    $isPrivateGroup = 0
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt('secret-file-account-pass', $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userId,
            $name,
            'file-acc-login',
            'https://file-acc.example.test',
            $encPass,
            $key,
            'notes',
            $isPrivate,
            $isPrivateGroup,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedAccountFile(
        PDO    $pdo,
        int    $accountId,
        string $name,
        string $type,
        string $extension,
        string $content,
        string $thumb
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO AccountFile (accountId, name, type, size, content, extension, thumb)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $accountId,
            $name,
            $type,
            strlen($content),
            $content,
            $extension,
            $thumb,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
