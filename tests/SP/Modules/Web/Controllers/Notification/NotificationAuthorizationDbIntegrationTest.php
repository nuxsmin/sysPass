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

namespace SP\Tests\Modules\Web\Controllers\Notification;

use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
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
 * DB-backed characterization tests for Notification routes (UC-NOTIF-*),
 * cross-user / onlyAdmin / sticky isolation, polling (items/notifications),
 * form broadcast persistence, and non-atomic batch deletion against MariaDB.
 */
#[Group('integration')]
final class NotificationAuthorizationDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';

    #[Test]
    public function anonymousAndIncompleteSessionsAreRejectedAcrossNotificationAndPollingRoutes(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $victim = $this->seedUser($pdo, 'notif_gate_victim', 5, new ProfileData());
        $seedId = $this->seedNotification(
            $pdo,
            'Alert',
            'Accounts',
            'Sensitive gate target description',
            $victim->id,
            0,
            0,
            0
        );

        $emptyProfile = new ProfileData();
        $incompleteUser = $this->seedUser($pdo, 'notif_gate_incomplete', 5, $emptyProfile);

        foreach ([
            'anonymous' => [false, false, null, null],
            'incomplete' => [true, false, $incompleteUser, $emptyProfile],
        ] as $label => [$loggedIn, $authCompleted, $actor, $actorProfile]) {
            foreach ([
                ['get', 'notification/index', [], []],
                ['get', 'notification/search', [], []],
                ['get', 'notification/create', [], []],
                ['get', 'notification/edit/' . $seedId, [], []],
                ['get', 'notification/view/' . $seedId, [], []],
                [
                    'post',
                    'notification/saveCreate',
                    [],
                    [
                        'notification_type' => 'Test',
                        'notification_component' => 'Accounts',
                        'notification_description' => 'Unauthorized',
                        'notification_user' => (string)$victim->id,
                    ],
                ],
                [
                    'post',
                    'notification/saveEdit/' . $seedId,
                    [],
                    [
                        'notification_type' => 'Test',
                        'notification_component' => 'Accounts',
                        'notification_description' => 'Edited Unauthorized',
                        'notification_user' => (string)$victim->id,
                    ],
                ],
                ['post', 'notification/check/' . $seedId, [], []],
                ['post', 'notification/delete/' . $seedId, [], []],
                ['post', 'notification/delete', [], ['items' => [$seedId]]],
            ] as [$method, $route, $get, $post]) {
                $res = $this->dispatchSessionState(
                    $method,
                    $route,
                    $loggedIn,
                    $authCompleted,
                    $actor,
                    $actorProfile,
                    $get,
                    $post
                );
                self::assertSame('', $res['body'], "$label $route");
            }

            // SimpleControllerBase::checks() redirects on HTML GET and emits JSON status 10 for JSON Accept
            $pollHtmlRes = $this->dispatchSessionState(
                'get',
                'items/notifications',
                $loggedIn,
                $authCompleted,
                $actor,
                $actorProfile
            );
            self::assertSame('', $pollHtmlRes['body'], "$label items/notifications html");

            $pollJsonRes = $this->dispatchSessionState(
                'get',
                'items/notifications',
                $loggedIn,
                $authCompleted,
                $actor,
                $actorProfile,
                [],
                [],
                [],
                ['HTTP_ACCEPT' => 'application/json']
            );
            $pollJson = $this->decodeJson($pollJsonRes['body']);
            self::assertSame(10, $pollJson['status'] ?? null, "$label items/notifications json");
            self::assertStringNotContainsString('Sensitive gate target description', $pollJsonRes['body']);
        }

        // Verify victim notification was neither marked read nor modified/deleted
        $row = $pdo->query('SELECT checked, description FROM Notification WHERE id = ' . $seedId)->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertSame(0, (int)$row['checked']);
        self::assertSame('Sensitive gate target description', $row['description']);
    }

    #[Test]
    public function nonAdminViewAndCheckBypassCrossUserAndOnlyAdminIsolationAndMutateSharedBroadcastState(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $emptyProfile = new ProfileData();
        $userA = $this->seedUser($pdo, 'notif_actor_a', 5, $emptyProfile);
        $userB = $this->seedUser($pdo, 'notif_victim_b', 6, $emptyProfile);

        $nPrivateB = $this->seedNotification(
            $pdo,
            'PrivateB',
            'Accounts',
            'SECRET_USER_B_NOTICE_TEXT',
            $userB->id,
            0,
            0,
            0
        );
        $nOnlyAdmin = $this->seedNotification(
            $pdo,
            'AdminOnly',
            'System',
            'SECRET_ONLY_ADMIN_NOTICE_TEXT',
            null,
            0,
            1,
            0
        );
        $nStickyBroadcast = $this->seedNotification(
            $pdo,
            'Broadcast',
            'General',
            'GLOBAL_STICKY_BROADCAST_TEXT',
            null,
            1,
            0,
            0
        );

        // 1. User A (blank ProfileData, non-admin) views User B's private notification and onlyAdmin=1 notification
        $viewB = $this->dispatchAsUser('get', 'notification/view/' . $nPrivateB, $userA, $emptyProfile);
        $viewBJson = $this->assertJsonOk($viewB);
        self::assertStringContainsString('SECRET_USER_B_NOTICE_TEXT', (string)($viewBJson['data']['html'] ?? ''));

        $viewAdmin = $this->dispatchAsUser('get', 'notification/view/' . $nOnlyAdmin, $userA, $emptyProfile);
        $viewAdminJson = $this->assertJsonOk($viewAdmin);
        self::assertStringContainsString('SECRET_ONLY_ADMIN_NOTICE_TEXT', (string)($viewAdminJson['data']['html'] ?? ''));

        // Missing ID returns HTTP 500 NoSuchItemException
        $viewMissing = $this->dispatchAsUser('get', 'notification/view/999999', $userA, $emptyProfile);
        self::assertSame(500, $viewMissing['code']);
        $viewMissingJson = $this->decodeJson($viewMissing['body']);
        self::assertSame('ERROR', $viewMissingJson['status'] ?? null);
        self::assertSame('Notification not found', $viewMissingJson['description'] ?? null);

        // 2. Under production DI (CoreDefinitions QueryFactory::COMMON), setCheckedById()->limit(1) throws
        //    uncaught \Error (Update::limit() undefined), returning HTTP 500 "Internal error" without updating DB
        $checkCommonCrash = $this->dispatchAsUser('post', 'notification/check/' . $nPrivateB, $userA, $emptyProfile);
        self::assertSame(500, $checkCommonCrash['code']);
        $checkCommonJson = $this->decodeJson($checkCommonCrash['body']);
        self::assertSame('ERROR', $checkCommonJson['status'] ?? null);
        self::assertSame('Internal error', $checkCommonJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT checked FROM Notification WHERE id = ' . $nPrivateB)->fetchColumn());

        // 3. With QueryFactory('mysql'), User A marks User B's private notification AND onlyAdmin=1 notification checked=1
        $checkB = $this->dispatchAsUser('post', 'notification/check/' . $nPrivateB, $userA, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($checkB, 'Notification read');
        self::assertSame(1, (int)$pdo->query('SELECT checked FROM Notification WHERE id = ' . $nPrivateB)->fetchColumn());

        $checkAdmin = $this->dispatchAsUser('post', 'notification/check/' . $nOnlyAdmin, $userA, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($checkAdmin, 'Notification read');
        self::assertSame(1, (int)$pdo->query('SELECT checked FROM Notification WHERE id = ' . $nOnlyAdmin)->fetchColumn());

        // 4. Shared broadcast read state: User B sees sticky broadcast before User A checks it; after User A checks it,
        //    checked=1 mutates the shared row so User B polling items/notifications returns count=0
        $pollBBefore = $this->dispatchAsUser('get', 'items/notifications', $userB, $emptyProfile);
        $pollBBeforeJson = $this->assertJsonOk($pollBBefore);
        self::assertSame(1, $pollBBeforeJson['data']['count'] ?? null);

        $checkSticky = $this->dispatchAsUser('post', 'notification/check/' . $nStickyBroadcast, $userA, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($checkSticky, 'Notification read');
        self::assertSame(
            1,
            (int)$pdo->query('SELECT checked FROM Notification WHERE id = ' . $nStickyBroadcast)->fetchColumn()
        );

        $pollBAfter = $this->dispatchAsUser('get', 'items/notifications', $userB, $emptyProfile);
        $pollBAfterJson = $this->assertJsonOk($pollBAfter);
        self::assertSame(0, $pollBAfterJson['data']['count'] ?? null);

        // 5. Repeated check on already checked=1 row returns OK "Notification read" (PDO FOUND_ROWS matches 1 row)
        $recheck = $this->dispatchAsUser('post', 'notification/check/' . $nStickyBroadcast, $userA, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($recheck, 'Notification read');
        self::assertSame(
            1,
            (int)$pdo->query('SELECT checked FROM Notification WHERE id = ' . $nStickyBroadcast)->fetchColumn()
        );
    }

    #[Test]
    public function deleteLacksAclCheckAllowsCrossUserAndOnlyAdminDeletionAndIsNonTransactionalOnBatch(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $emptyProfile = new ProfileData();
        $userA = $this->seedUser($pdo, 'notif_del_a', 5, $emptyProfile);
        $userB = $this->seedUser($pdo, 'notif_del_b', 6, $emptyProfile);
        $admin = $this->seedUser($pdo, 'notif_del_admin', 1, $emptyProfile, true);

        $nB1 = $this->seedNotification($pdo, 'PrivB1', 'Accounts', 'User B row 1', $userB->id, 0, 0, 0);
        $nB2 = $this->seedNotification($pdo, 'PrivB2', 'Accounts', 'User B row 2', $userB->id, 0, 0, 0);
        $nB3 = $this->seedNotification($pdo, 'PrivB3', 'Accounts', 'User B row 3', $userB->id, 0, 0, 0);
        $nOnlyAdmin = $this->seedNotification($pdo, 'AdminDel', 'System', 'Admin only row', null, 0, 1, 0);
        $nSticky = $this->seedNotification($pdo, 'Sticky', 'System', 'Sticky row', null, 1, 0, 0);
        $nAdminVictim1 = $this->seedNotification($pdo, 'AdmV1', 'System', 'Admin batch victim 1', $userB->id, 0, 0, 0);
        $nAdminVictim2 = $this->seedNotification($pdo, 'AdmV2', 'System', 'Admin batch victim 2', $userB->id, 0, 0, 0);

        // 1. Under production DI (QueryFactory::COMMON), single delete($id)->limit(1) crashes HTTP 500 "Internal error"
        $delCommonCrash = $this->dispatchAsUser('post', 'notification/delete/' . $nB1, $userA, $emptyProfile);
        self::assertSame(500, $delCommonCrash['code']);
        $delCommonJson = $this->decodeJson($delCommonCrash['body']);
        self::assertSame('ERROR', $delCommonJson['status'] ?? null);
        self::assertSame('Internal error', $delCommonJson['description'] ?? null);
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nB1)->fetchColumn());

        // 2. Yet batch delete (no ->limit(1)) SUCCEEDS under production DI (QueryFactory::COMMON) AND lacks
        //    NOTIFICATION_DELETE ACL check: User A deletes User B's row and onlyAdmin=1 row
        $batchDelCommon = $this->dispatchAsUser(
            'post',
            'notification/delete',
            $userA,
            $emptyProfile,
            [],
            ['items' => [$nB1, $nOnlyAdmin]]
        );
        $this->assertJsonOk($batchDelCommon, 'Notifications deleted');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nB1)->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nOnlyAdmin)->fetchColumn());

        // 3. With QueryFactory('mysql'), single delete($id) also lacks ACL check and deletes User B's notification,
        //    while non-admin cannot delete sticky=1 row (WHERE id = :id AND sticky = 0)
        $delB = $this->dispatchAsUser('post', 'notification/delete/' . $nB2, $userA, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($delB, 'Notification deleted');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nB2)->fetchColumn());

        $delSticky = $this->dispatchAsUser('post', 'notification/delete/' . $nSticky, $userA, $emptyProfile, [], [], [], [], true);
        $delStickyJson = $this->decodeJson($delSticky['body']);
        self::assertSame('ERROR', $delStickyJson['status'] ?? null);
        self::assertSame('Notification not found', $delStickyJson['description'] ?? null);
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nSticky)->fetchColumn());

        // 4. Empty batch items=[] returns OK without DB changes (tested under default QueryFactory::COMMON)
        $emptyBatch = $this->dispatchAsUser(
            'post',
            'notification/delete',
            $userA,
            $emptyProfile,
            [],
            ['items' => []]
        );
        $this->assertJsonOk($emptyBatch, 'Notifications deleted');
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nB3)->fetchColumn());

        // 5. Non-admin mixed batch [otherUserNonSticky, sticky] permanently deletes otherUserNonSticky without a transaction
        $nonAdminMixed = $this->dispatchAsUser(
            'post',
            'notification/delete',
            $userA,
            $emptyProfile,
            [],
            ['items' => [$nB3, $nSticky]]
        );
        $nonAdminMixedJson = $this->decodeJson($nonAdminMixed['body']);
        self::assertSame('ERROR', $nonAdminMixedJson['status'] ?? null);
        self::assertSame('Error while deleting the notifications', $nonAdminMixedJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nB3)->fetchColumn());
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nSticky)->fetchColumn());

        // 6. Admin mixed [valid, 999999] and duplicate [valid, valid] also delete valid rows outside any transaction
        $adminMixed = $this->dispatchAsUser(
            'post',
            'notification/delete',
            $admin,
            $emptyProfile,
            [],
            ['items' => [$nAdminVictim1, 999999]]
        );
        $adminMixedJson = $this->decodeJson($adminMixed['body']);
        self::assertSame('ERROR', $adminMixedJson['status'] ?? null);
        self::assertSame('Error while deleting the notifications', $adminMixedJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nAdminVictim1)->fetchColumn());

        $adminDup = $this->dispatchAsUser(
            'post',
            'notification/delete',
            $admin,
            $emptyProfile,
            [],
            ['items' => [$nAdminVictim2, $nAdminVictim2]]
        );
        $adminDupJson = $this->decodeJson($adminDup['body']);
        self::assertSame('ERROR', $adminDupJson['status'] ?? null);
        self::assertSame('Error while deleting the notifications', $adminDupJson['description'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nAdminVictim2)->fetchColumn());

        // Admin single delete sticky=1 succeeds with QueryFactory('mysql')
        $adminDelSticky = $this->dispatchAsUser('post', 'notification/delete/' . $nSticky, $admin, $emptyProfile, [], [], [], [], true);
        $this->assertJsonOk($adminDelSticky, 'Notification deleted');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE id = ' . $nSticky)->fetchColumn());
    }

    #[Test]
    public function searchAndPollingExposeAsymmetricVisibilityAcrossAdminOnlyNullUserAndStickyNotifications(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $emptyProfile = new ProfileData();
        $userA = $this->seedUser($pdo, 'notif_vis_a', 5, $emptyProfile);
        $userB = $this->seedUser($pdo, 'notif_vis_b', 6, $emptyProfile);
        $admin = $this->seedUser($pdo, 'notif_vis_admin', 1, $emptyProfile, true);

        $this->seedNotification($pdo, 'OwnA', 'Accounts', 'DESC_OWN_A', $userA->id, 0, 0, 0);
        $this->seedNotification($pdo, 'OwnB', 'Accounts', 'DESC_OWN_B', $userB->id, 0, 0, 0);
        $this->seedNotification($pdo, 'AdmSticky', 'System', 'DESC_ADMIN_STICKY', null, 1, 1, 0);
        $this->seedNotification($pdo, 'AdmUserB', 'System', 'DESC_ADMIN_USER_B', $userB->id, 0, 1, 0);
        $this->seedNotification($pdo, 'NullNonSticky', 'System', 'DESC_NULL_NONSTICKY', null, 0, 0, 0);
        $this->seedNotification($pdo, 'GlobalSticky', 'General', 'DESC_GLOBAL_STICKY', null, 1, 0, 0);

        // 1. Non-admin search (searchForUserId): leaks onlyAdmin=1 sticky=1 due to bare "OR sticky = 1",
        //    includes (userId IS NULL AND onlyAdmin = 0), excludes OwnB & AdmUserB
        $userSearch = $this->dispatchAsUser('get', 'notification/search', $userA, $emptyProfile);
        $userSearchJson = $this->assertJsonOk($userSearch);
        $userHtml = (string)($userSearchJson['data']['html'] ?? '');
        self::assertStringContainsString('DESC_OWN_A', $userHtml);
        self::assertStringContainsString('DESC_ADMIN_STICKY', $userHtml);
        self::assertStringContainsString('DESC_NULL_NONSTICKY', $userHtml);
        self::assertStringContainsString('DESC_GLOBAL_STICKY', $userHtml);
        self::assertStringNotContainsString('DESC_OWN_B', $userHtml);
        self::assertStringNotContainsString('DESC_ADMIN_USER_B', $userHtml);

        // 2. Non-admin polling (getAllActiveForUserId): returns OwnA and GlobalSticky,
        //    excludes AdmSticky (onlyAdmin=0) and NullNonSticky (userId IS NULL omitted!)
        $userPoll = $this->dispatchAsUser('get', 'items/notifications', $userA, $emptyProfile);
        $userPollJson = $this->assertJsonOk($userPoll);
        self::assertSame(2, $userPollJson['data']['count'] ?? null);
        $userPollItems = implode(' | ', $userPollJson['data']['notifications'] ?? []);
        self::assertStringContainsString('DESC_OWN_A', $userPollItems);
        self::assertStringContainsString('DESC_GLOBAL_STICKY', $userPollItems);
        self::assertStringNotContainsString('DESC_ADMIN_STICKY', $userPollItems);
        self::assertStringNotContainsString('DESC_NULL_NONSTICKY', $userPollItems);

        // 3. Admin search (searchForAdmin): omits NullNonSticky (userId IS NULL, onlyAdmin=0, sticky=0), includes AdmUserB
        $adminSearch = $this->dispatchAsUser('get', 'notification/search', $admin, $emptyProfile);
        $adminSearchJson = $this->assertJsonOk($adminSearch);
        $adminHtml = (string)($adminSearchJson['data']['html'] ?? '');
        self::assertStringContainsString('DESC_ADMIN_STICKY', $adminHtml);
        self::assertStringContainsString('DESC_ADMIN_USER_B', $adminHtml);
        self::assertStringContainsString('DESC_GLOBAL_STICKY', $adminHtml);
        self::assertStringNotContainsString('DESC_NULL_NONSTICKY', $adminHtml);
        self::assertStringNotContainsString('DESC_OWN_A', $adminHtml);

        // 4. Admin polling (getAllActiveForAdmin): includes NullNonSticky (userId IS NULL),
        //    omits AdmUserB (userId = B->id, onlyAdmin = 1, sticky = 0)!
        $adminPoll = $this->dispatchAsUser('get', 'items/notifications', $admin, $emptyProfile);
        $adminPollJson = $this->assertJsonOk($adminPoll);
        self::assertSame(3, $adminPollJson['data']['count'] ?? null);
        $adminPollItems = implode(' | ', $adminPollJson['data']['notifications'] ?? []);
        self::assertStringContainsString('DESC_ADMIN_STICKY', $adminPollItems);
        self::assertStringContainsString('DESC_NULL_NONSTICKY', $adminPollItems);
        self::assertStringContainsString('DESC_GLOBAL_STICKY', $adminPollItems);
        self::assertStringNotContainsString('DESC_ADMIN_USER_B', $adminPollItems);
    }

    #[Test]
    public function createAndEditDenyNonAdminBlockBroadcastFormSubmissionsAndResetCheckedState(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $emptyProfile = new ProfileData();
        $nonAdmin = $this->seedUser($pdo, 'notif_mgmt_nonadmin', 5, $emptyProfile);
        $admin = $this->seedUser($pdo, 'notif_mgmt_admin', 1, $emptyProfile, true);

        $existingCheckedId = $this->seedNotification(
            $pdo,
            'Existing',
            'Accounts',
            'Already checked content',
            $nonAdmin->id,
            0,
            0,
            1
        );

        // 1. Non-admin is denied create, edit, saveCreate, saveEdit
        foreach ([
            ['get', 'notification/create', []],
            ['get', 'notification/edit/' . $existingCheckedId, []],
            [
                'post',
                'notification/saveCreate',
                [
                    'notification_type' => 'Notice',
                    'notification_component' => 'Accounts',
                    'notification_description' => 'Non-admin create attempt',
                    'notification_user' => (string)$nonAdmin->id,
                ],
            ],
            [
                'post',
                'notification/saveEdit/' . $existingCheckedId,
                [
                    'notification_type' => 'Notice',
                    'notification_component' => 'Accounts',
                    'notification_description' => 'Non-admin edit attempt',
                    'notification_user' => (string)$nonAdmin->id,
                ],
            ],
        ] as [$method, $route, $post]) {
            $res = $this->dispatchAsUser($method, $route, $nonAdmin, $emptyProfile, [], $post);
            $this->assertJsonDenied($res, "You don't have permission to do this operation");
        }

        // 2. Broadcast via HTML <select> empty option ('') OR omitted notification_user:
        //    Filter::getInt('') returns null (not 0), so $userId === 0 is false, sticky/onlyAdmin stay false,
        //    and NotificationForm rejects both with ValidationException "A target  is needed"
        foreach ([
            'empty_select' => ['notification_user' => ''],
            'omitted_user' => [],
        ] as $label => $userParam) {
            $res = $this->dispatchAsUser(
                'post',
                'notification/saveCreate',
                $admin,
                $emptyProfile,
                [],
                array_merge(
                    [
                        'notification_type' => 'Notice',
                        'notification_component' => 'System',
                        'notification_description' => 'Broadcast ' . $label,
                        'notification_sticky' => 'on',
                    ],
                    $userParam
                )
            );
            $json = $this->decodeJson($res['body']);
            self::assertSame('ERROR', $json['status'] ?? null, $label);
            self::assertSame('A target  is needed', $json['description'] ?? null, $label);
        }

        // 3. Explicit notification_user='0' + sticky=1 passes NotificationForm validation and sets userId=0,
        //    then fails MariaDB FK fk_Notification_userId -> ERROR "Error while adding the notification"
        $fkBroadcastRes = $this->dispatchAsUser(
            'post',
            'notification/saveCreate',
            $admin,
            $emptyProfile,
            [],
            [
                'notification_type' => 'Notice',
                'notification_component' => 'System',
                'notification_description' => 'Broadcast explicit userId=0',
                'notification_user' => '0',
                'notification_sticky' => 'on',
            ]
        );
        $fkBroadcastJson = $this->decodeJson($fkBroadcastRes['body']);
        self::assertSame('ERROR', $fkBroadcastJson['status'] ?? null);
        self::assertSame('Integrity constraint', $fkBroadcastJson['description'] ?? null);

        // 4. Admin creating for explicit valid userId persists checked=0 notification in MariaDB (works under QueryFactory::COMMON)
        $createOkRes = $this->dispatchAsUser(
            'post',
            'notification/saveCreate',
            $admin,
            $emptyProfile,
            [],
            [
                'notification_type' => 'Notice',
                'notification_component' => 'System',
                'notification_description' => 'Created for explicit user',
                'notification_user' => (string)$nonAdmin->id,
            ]
        );
        $this->assertJsonOk($createOkRes, 'Notification created');
        self::assertSame(
            2,
            (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE userId = ' . $nonAdmin->id)->fetchColumn()
        );

        // 5. Under production DI (QueryFactory::COMMON), saveEdit->limit(1) crashes HTTP 500 "Internal error"
        $editCommonCrash = $this->dispatchAsUser(
            'post',
            'notification/saveEdit/' . $existingCheckedId,
            $admin,
            $emptyProfile,
            [],
            [
                'notification_type' => 'ExistingEdited',
                'notification_component' => 'Accounts',
                'notification_description' => 'Should crash on QueryFactory::COMMON',
                'notification_user' => (string)$nonAdmin->id,
            ]
        );
        self::assertSame(500, $editCommonCrash['code']);
        $editCommonJson = $this->decodeJson($editCommonCrash['body']);
        self::assertSame('ERROR', $editCommonJson['status'] ?? null);
        self::assertSame('Internal error', $editCommonJson['description'] ?? null);
        self::assertSame(
            'Already checked content',
            $pdo->query('SELECT description FROM Notification WHERE id = ' . $existingCheckedId)->fetchColumn()
        );

        // 6. Even with QueryFactory('mysql'), NotificationRepository::update() excludes only ['id'] (not ['id', 'date'])
        //    in cols() while overriding ->set('date', 'UNIX_TIMESTAMP()'), leaving an unused :date bind parameter ->
        //    SQLSTATE[HY093]: Invalid parameter number: parameter was not defined (row remains checked=1, unedited)
        $editRes = $this->dispatchAsUser(
            'post',
            'notification/saveEdit/' . $existingCheckedId,
            $admin,
            $emptyProfile,
            [],
            [
                'notification_type' => 'ExistingEdited',
                'notification_component' => 'Accounts',
                'notification_description' => 'Updated content blocked by HY093',
                'notification_user' => (string)$nonAdmin->id,
            ],
            [],
            [],
            true
        );
        $editJson = $this->decodeJson($editRes['body']);
        self::assertSame('ERROR', $editJson['status'] ?? null);
        self::assertStringContainsString('SQLSTATE[HY093]', (string)($editJson['description'] ?? ''));

        $editedRow = $pdo->query(
            'SELECT checked, description FROM Notification WHERE id = ' . $existingCheckedId
        )->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($editedRow);
        self::assertSame(1, (int)$editedRow['checked']);
        self::assertSame('Already checked content', $editedRow['description']);

        // 7. Editing non-existent ID with QueryFactory('mysql') also hits SQLSTATE[HY093] before matching rows
        $editMissingRes = $this->dispatchAsUser(
            'post',
            'notification/saveEdit/999999',
            $admin,
            $emptyProfile,
            [],
            [
                'notification_type' => 'Ghost',
                'notification_component' => 'Accounts',
                'notification_description' => 'Missing notification',
                'notification_user' => (string)$nonAdmin->id,
            ],
            [],
            [],
            true
        );
        $editMissingJson = $this->decodeJson($editMissingRes['body']);
        self::assertSame('ERROR', $editMissingJson['status'] ?? null);
        self::assertStringContainsString('SQLSTATE[HY093]', (string)($editMissingJson['description'] ?? ''));
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $configOverrides
     * @param array<string, string> $server
     * @return array{body: string, code: int}
     */
    private function dispatchAsUser(
        string      $method,
        string      $route,
        UserDto     $user,
        ProfileData $profile,
        array       $paramsGet = [],
        array       $paramsPost = [],
        array       $configOverrides = [],
        array       $server = [],
        bool        $useMysqlQueryFactory = false
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
            $configOverrides,
            $server,
            $useMysqlQueryFactory
        );
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $configOverrides
     * @param array<string, string> $server
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
        array        $configOverrides = [],
        array        $server = [],
        bool         $useMysqlQueryFactory = false
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
            [],
            $server
        );

        $overrides = [
            Context::class => $context,
            ConfigFileService::class => $configFileService,
            ConfigDataInterface::class => $configData,
            AclInterface::class => autowire(Acl::class)
                ->constructorParameter('actions', get(ActionsInterface::class)),
        ];

        if ($useMysqlQueryFactory) {
            $overrides[\Aura\SqlQuery\QueryFactory::class] = $this->queryFactory;
        }

        $container = $this->buildContainer($request, $overrides);

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
        bool        $isAdminApp = false
    ): UserDto {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
            $isAdminApp ? 1 : 0,
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
            'isAdminAcc' => false,
            'isDisabled' => false,
            'isChangePass' => false,
            'isChangedPass' => false,
            'isLdap' => false,
            'isMigrate' => false,
            'preferences' => new UserPreferences(['lang' => 'en_US']),
        ]);
    }

    private function seedNotification(
        PDO     $pdo,
        string  $type,
        string  $component,
        string  $description,
        ?int    $userId,
        int     $sticky,
        int     $onlyAdmin,
        int     $checked
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO Notification (type, component, description, date, checked, userId, sticky, onlyAdmin)
             VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?, ?, ?, ?)'
        );
        $stmt->execute([
            $type,
            $component,
            $description,
            $checked,
            $userId,
            $sticky,
            $onlyAdmin,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
