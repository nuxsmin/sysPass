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
 * DB-backed tests for Web AccountFile authorization (list, view, download,
 * upload, delete and search), MIME/size validation and batch deletion
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
    public function fileReadAndDeleteBoundariesAndUploadAuthorization(): void
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

        // 1. List is denied without the files profile action, whatever the account is
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
        self::assertSame("You don't have permission to do this operation", $listRes['body']);
        self::assertStringNotContainsString('a-private-secret.txt', $listRes['body']);

        // 2. View is denied for every file id (text, image, unsupported type and missing ones alike)
        foreach ([$txtFileId, $pngFileId, 1, 999999] as $fileId) {
            $this->assertJsonDenied(
                $this->dispatchAsUser('get', 'accountFile/view/' . $fileId, $unprivilegedUser, $emptyProfile),
                "You don't have permission to do this operation"
            );
        }

        // 3. Download returns no content
        $downloadRes = $this->dispatchAsUser('get', 'accountFile/download/' . $txtFileId, $unprivilegedUser, $emptyProfile);
        $this->assertResponseWasDispatched($downloadRes);
        self::assertSame("You don't have permission to do this operation", $downloadRes['body']);

        // 4. Upload requires its profile action. The remaining input behavior is
        // characterized with a separate authorized owner and account.
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
            $this->assertJsonDenied($spoofRes, "You don't have permission to do this operation");
            $uploadedId = (int)$pdo->query(
                "SELECT id FROM AccountFile WHERE accountId = $privateAccId AND name = 'spoofed.bin'"
            )->fetchColumn();
            self::assertSame(0, $uploadedId);

            $uploadProfile = (new ProfileData())->setAccFiles(true)->setAccEdit(true);
            $uploadUser = $this->seedUser($pdo, 'file_upload_owner', 5, $uploadProfile);
            $uploadAccountId = $this->seedAccount($pdo, 'Upload Owner Account', $uploadUser->id, 5, 0, 0);
            $crossAccount = $this->dispatchAsUser(
                'post', 'accountFile/upload/' . $privateAccId, $uploadUser, $uploadProfile, [], [],
                ['inFile' => [
                    'name' => 'denied-cross.txt', 'tmp_name' => $oversize1001File,
                    'size' => 10, 'type' => 'text/plain',
                ]],
                ['isFilesEnabled' => true, 'getFilesAllowedMime' => ['text/plain'], 'getFilesAllowedSize' => 2]
            );
            $this->assertJsonDenied($crossAccount, "You don't have permission to access this account");
            self::assertSame(0, (int)$pdo->query(
                "SELECT COUNT(*) FROM AccountFile WHERE accountId = $privateAccId AND name = 'denied-cross.txt'"
            )->fetchColumn());
            $readOnlyAccountId = $this->seedAccount($pdo, 'Read Only Upload Account', 1, 1);
            $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
                ->execute([$readOnlyAccountId, $uploadUser->id]);
            $readOnly = $this->dispatchAsUser(
                'post', 'accountFile/upload/' . $readOnlyAccountId, $uploadUser, $uploadProfile, [], [],
                ['inFile' => [
                    'name' => 'denied-readonly.txt', 'tmp_name' => $oversize1001File,
                    'size' => 10, 'type' => 'text/plain',
                ]],
                ['isFilesEnabled' => true, 'getFilesAllowedMime' => ['text/plain'], 'getFilesAllowedSize' => 2]
            );
            $this->assertJsonDenied($readOnly, "You don't have permission to access this account");
            self::assertSame(0, (int)$pdo->query(
                "SELECT COUNT(*) FROM AccountFile WHERE accountId = $readOnlyAccountId AND name = 'denied-readonly.txt'"
            )->fetchColumn());
            $allowedRes = $this->dispatchAsUser(
                'post', 'accountFile/upload/' . $uploadAccountId, $uploadUser, $uploadProfile, [], [],
                ['inFile' => [
                    'name' => 'owner-upload.txt', 'tmp_name' => $oversize1001File,
                    'size' => 10, 'type' => 'text/plain',
                ]],
                ['isFilesEnabled' => true, 'getFilesAllowedMime' => ['text/plain'], 'getFilesAllowedSize' => 2]
            );
            $this->assertJsonOk($allowedRes, 'File saved');
            self::assertSame(1, (int)$pdo->query(
                "SELECT COUNT(*) FROM AccountFile WHERE accountId = $uploadAccountId AND name = 'owner-upload.txt'"
            )->fetchColumn());

            // 1001 bytes exceeds allowedSize=1 * 1000 (decimal, not 1024 KiB)
            $overRes = $this->dispatchAsUser(
                'post',
                'accountFile/upload/' . $uploadAccountId,
                $uploadUser,
                $uploadProfile,
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
                'accountFile/upload/' . $uploadAccountId,
                $uploadUser,
                $uploadProfile,
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

        // 5. Unprivileged user cannot delete the file (single and batch)
        $deleteRes = $this->dispatchAsUser('post', 'accountFile/delete/' . $txtFileId, $unprivilegedUser, $emptyProfile);
        $this->assertJsonDenied($deleteRes, "You don't have permission to do this operation");
        $batchRes = $this->dispatchAsUser(
            'post',
            'accountFile/delete',
            $unprivilegedUser,
            $emptyProfile,
            [],
            ['items' => [$txtFileId, $pngFileId]]
        );
        $this->assertJsonDenied($batchRes, "You don't have permission to do this operation");
        self::assertSame(
            2,
            (int)$pdo->query("SELECT COUNT(*) FROM AccountFile WHERE id IN ($txtFileId, $pngFileId)")->fetchColumn()
        );
    }

    #[Test]
    public function authorizedOwnerReadsAndDeletesOwnFilesButNotOthers(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())->setAccFiles(true)->setAccEdit(true)->setAccView(true);
        $owner = $this->seedUser($pdo, 'file_owner_user', 5, $profile);
        $ownAccountId = $this->seedAccount($pdo, 'Owner Files Account', $owner->id, 5);
        $ownFileId = $this->seedAccountFile($pdo, $ownAccountId, 'own.txt', 'text/plain', 'TXT', 'OWN_PAYLOAD', 'no_thumb');

        $otherAccountId = $this->seedAccount($pdo, 'Admin Private Files Account', 1, 1, 1, 0);
        $otherFileId = $this->seedAccountFile($pdo, $otherAccountId, 'other.txt', 'text/plain', 'TXT', 'OTHER_PAYLOAD', 'no_thumb');

        $list = $this->dispatchAsUser(
            'get', 'accountFile/list/' . $ownAccountId, $owner, $profile, [], [], [], ['isFilesEnabled' => true]
        );
        $this->assertResponseWasDispatched($list);
        self::assertStringContainsString('own.txt', $list['body']);

        $view = $this->assertJsonOk($this->dispatchAsUser('get', 'accountFile/view/' . $ownFileId, $owner, $profile));
        self::assertStringContainsString('OWN_PAYLOAD', (string)($view['data']['html'] ?? ''));

        $download = $this->dispatchAsUser('get', 'accountFile/download/' . $ownFileId, $owner, $profile);
        $this->assertResponseWasDispatched($download);
        self::assertSame('OWN_PAYLOAD', $download['body']);

        // The files of an account the user cannot access (and missing files) get the very same denial
        $noAccount = "You don't have permission to access this account";
        $denied = $this->dispatchAsUser(
            'get', 'accountFile/list/' . $otherAccountId, $owner, $profile, [], [], [], ['isFilesEnabled' => true]
        );
        self::assertSame($noAccount, $denied['body']);
        foreach ([$otherFileId, 999999] as $fileId) {
            $this->assertJsonDenied($this->dispatchAsUser('get', 'accountFile/view/' . $fileId, $owner, $profile), $noAccount);
            self::assertSame(
                $noAccount,
                $this->dispatchAsUser('get', 'accountFile/download/' . $fileId, $owner, $profile)['body']
            );
            $this->assertJsonDenied(
                $this->dispatchAsUser('post', 'accountFile/delete/' . $fileId, $owner, $profile),
                $noAccount
            );
        }
        self::assertSame(
            1,
            (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $otherFileId)->fetchColumn()
        );

        // Read only access to an account is not enough to delete its files
        $readOnlyAccountId = $this->seedAccount($pdo, 'Read Only Files Account', 1, 1);
        $readOnlyFileId = $this->seedAccountFile($pdo, $readOnlyAccountId, 'ro.txt', 'text/plain', 'TXT', 'RO', 'no_thumb');
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$readOnlyAccountId, $owner->id]);
        $this->assertJsonDenied(
            $this->dispatchAsUser('post', 'accountFile/delete/' . $readOnlyFileId, $owner, $profile),
            $noAccount
        );
        self::assertSame(
            1,
            (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $readOnlyFileId)->fetchColumn()
        );

        // The owner can delete the own file
        $this->assertJsonOk(
            $this->dispatchAsUser('post', 'accountFile/delete/' . $ownFileId, $owner, $profile),
            'File deleted'
        );
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountFile WHERE id = ' . $ownFileId)->fetchColumn());
    }

    #[Test]
    public function searchRequiresTheFilesPermissionAndOnlyListsVisibleAccountFiles(): void
    {
        $pdo = getDbHandler()->getConnection();

        $fileProfile = (new ProfileData())
            ->setAccFiles(true)
            ->setMgmFiles(true)
            ->setAccView(true);

        $fileMgrAccAdmin = $this->seedUser($pdo, 'file_mgr_acc_admin', 5, $fileProfile, false, true);
        $appAdmin = $this->seedUser($pdo, 'file_app_admin', 5, $fileProfile, true, false);
        $plainUser = $this->seedUser($pdo, 'file_plain_user', 5, $fileProfile);
        $noFilesUser = $this->seedUser($pdo, 'file_nofiles_user', 5, new ProfileData());

        $privateAccId = $this->seedAccount($pdo, 'Private Search Account', 1, 1, 1, 0);
        $privGrpAccId = $this->seedAccount($pdo, 'PrivateGroup Search Account', 1, 1, 0, 1);
        $publicAccId = $this->seedAccount($pdo, 'Public Search Account', 1, 1, 0, 0);

        $this->seedAccountFile($pdo, $privateAccId, 'priv-search-secret.txt', 'text/plain', 'TXT', 'priv', 'no_thumb');
        $this->seedAccountFile($pdo, $privGrpAccId, 'privgrp-search-secret.txt', 'text/plain', 'TXT', 'privgrp', 'no_thumb');
        $this->seedAccountFile($pdo, $publicAccId, 'public-search-visible.txt', 'text/plain', 'TXT', 'pub', 'no_thumb');

        // 1. Without the files profile action the search is denied
        $this->assertJsonDenied(
            $this->dispatchAsUser('post', 'accountFile/search', $noFilesUser, new ProfileData(), [], ['search' => 'search']),
            "You don't have permission to do this operation"
        );

        // 2. Private accounts files are never listed, neither for the account administrators
        foreach ([[$fileMgrAccAdmin, $fileProfile], [$appAdmin, $fileProfile]] as [$user, $profile]) {
            $html = (string)($this->assertJsonOk(
                $this->dispatchAsUser('post', 'accountFile/search', $user, $profile, [], ['search' => 'search'])
            )['data']['html'] ?? '');
            self::assertStringContainsString('public-search-visible.txt', $html);
            self::assertStringNotContainsString('priv-search-secret.txt', $html);
            self::assertStringNotContainsString('privgrp-search-secret.txt', $html);
        }

        // 3. A user without any access to the accounts does not see any file
        $html = (string)($this->assertJsonOk(
            $this->dispatchAsUser('post', 'accountFile/search', $plainUser, $fileProfile, [], ['search' => 'search'])
        )['data']['html'] ?? '');
        self::assertStringNotContainsString('public-search-visible.txt', $html);
        self::assertStringNotContainsString('priv-search-secret.txt', $html);
        self::assertStringNotContainsString('privgrp-search-secret.txt', $html);
    }

    #[Test]
    public function batchDeleteIsAllOrNothing(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())->setAccFiles(true)->setAccEdit(true)->setAccView(true);
        $user = $this->seedUser($pdo, 'file_batch_del_user', 5, $profile);

        $ownAccountId = $this->seedAccount($pdo, 'Batch File Owner', $user->id, 5);
        $f1 = $this->seedAccountFile($pdo, $ownAccountId, 'batch-1.txt', 'text/plain', 'TXT', 'one', 'no_thumb');
        $f2 = $this->seedAccountFile($pdo, $ownAccountId, 'batch-2.txt', 'text/plain', 'TXT', 'two', 'no_thumb');
        $f3 = $this->seedAccountFile($pdo, $ownAccountId, 'batch-3.txt', 'text/plain', 'TXT', 'three', 'no_thumb');

        $otherAccountId = $this->seedAccount($pdo, 'Batch File Other Priv', 1, 1, 1, 0);
        $foreign = $this->seedAccountFile($pdo, $otherAccountId, 'batch-foreign.txt', 'text/plain', 'TXT', 'x', 'no_thumb');

        $noAccount = "You don't have permission to access this account";
        $count = static fn(string $ids): int => (int)$pdo->query("SELECT COUNT(*) FROM AccountFile WHERE id IN ($ids)")
                                                         ->fetchColumn();

        // 1. Empty items array returns OK "Files deleted" without touching rows
        $this->assertJsonOk(
            $this->dispatchAsUser('post', 'accountFile/delete', $user, $profile, [], ['items' => []]),
            'Files deleted'
        );
        self::assertSame(3, $count("$f1, $f2, $f3"));

        // 2. [valid, missing] deletes nothing
        $this->assertJsonDenied(
            $this->dispatchAsUser('post', 'accountFile/delete', $user, $profile, [], ['items' => [$f1, 999999]]),
            $noAccount
        );
        self::assertSame(3, $count("$f1, $f2, $f3"));

        // 3. [valid, forbidden] deletes nothing, with the same response as a missing file
        $this->assertJsonDenied(
            $this->dispatchAsUser('post', 'accountFile/delete', $user, $profile, [], ['items' => [$f1, $foreign]]),
            $noAccount
        );
        self::assertSame(2, $count("$f1, $foreign"));

        // 4. Duplicates are authorized, but the affected rows differ from the requested ones (unchanged behavior)
        $dupJson = $this->decodeJson(
            $this->dispatchAsUser('post', 'accountFile/delete', $user, $profile, [], ['items' => [$f2, $f2]])['body']
        );
        self::assertSame('ERROR', $dupJson['status'] ?? null);
        self::assertSame('Error while deleting the files', $dupJson['description'] ?? null);
        self::assertSame(0, $count((string)$f2));

        // 5. Every file authorized: all of them are deleted
        $this->assertJsonOk(
            $this->dispatchAsUser('post', 'accountFile/delete', $user, $profile, [], ['items' => [$f1, $f3]]),
            'Files deleted'
        );
        self::assertSame(0, $count("$f1, $f3"));
        self::assertSame(1, $count((string)$foreign));
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
