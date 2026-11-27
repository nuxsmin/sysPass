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

namespace SP\Tests\Modules\Web\Controllers\AccountHistoryManager;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function DI\autowire;
use function DI\get;
use function SP\Tests\getDbHandler;

/**
 * DB-backed integration tests for Account History, AccountHistoryManager,
 * AccountManager bulk edit/delete, and single-account delete/restore contracts against MariaDB.
 */
#[Group('integration')]
final class AccountHistoryDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';
    private const DENIED_PAGE = "You don't have permission to access this page";
    private const DENIED_OP = "You don't have permission to do this operation";
    private const DENIED_ACCOUNT = "You don't have permission to access this account";

    protected function getConfigData(): array
    {
        return array_merge(
            parent::getConfigData(),
            [
                'getAccountCount' => 50,
                'isPublinksEnabled' => true,
                'getPublinksMaxTime' => 600,
                'getPublinksMaxViews' => 3,
            ]
        );
    }

    #[Test]
    public function viewDropdownUsesAccountIdAndViewHistoryEnforcesLiveVsDeletedAuthorization(): void
    {
        $pdo = getDbHandler()->getConnection();

        $viewHistProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewHistory(true);

        $user = $this->seedUser($pdo, 'hist_view_acl_user', 5, $viewHistProfile);
        $otherUser = $this->seedUser($pdo, 'hist_view_other_user', 6, $viewHistProfile);
        $appAdminProfile = $this->getUserProfile();
        $appAdminUser = $this->getUserDataDto();

        // 1. Dropdown on account/view/{id} must match AccountHistory.accountId, NOT AccountHistory.id
        $ownedAccountId = $this->seedAccount($pdo, 'Owned Account Ten', $user->id, 5, 10);
        $otherPrivAccountId = $this->seedAccount($pdo, 'Other Private Eleven', $otherUser->id, 6, 11, 1, 0);

        // History row id=20 belongs to accountId=10 ($ownedAccountId)
        $this->seedAccountHistory($pdo, 20, $ownedAccountId, 'Owned Snap 20', $user->id, 5, 'p20', 1, 0, '2026-02-03 04:05:06');
        // History row id=10 belongs to accountId=11 ($otherPrivAccountId), colliding numerically with $ownedAccountId
        $this->seedAccountHistory($pdo, 10, $otherPrivAccountId, 'Foreign Snap 10', $otherUser->id, 6, 'p10', 1, 0, '2026-09-09 09:09:09');

        $viewTen = $this->dispatchAsUser('get', 'account/view/' . $ownedAccountId, $user, $viewHistProfile);
        $this->assertResponseWasDispatched($viewTen);
        self::assertStringContainsString('value="20"', $viewTen['body']);
        self::assertStringContainsString('2026-02-03 04:05:06', $viewTen['body']);
        self::assertStringNotContainsString('value="10"', $viewTen['body']);
        self::assertStringNotContainsString('2026-09-09 09:09:09', $viewTen['body']);

        // 2. Live Account uses LIVE Account's owner/privacy/grants rather than snapshot's stale fields:
        //    2a. Snapshot was permissive (otherUser, non-private), live Account is now private to otherUser -> DENIED
        $becamePrivAccountId = $this->seedAccount($pdo, 'Now Private Live', $otherUser->id, 6, null, 1, 0);
        $stalePermissiveHistId = $this->seedAccountHistory(
            $pdo,
            null,
            $becamePrivAccountId,
            'Old Public Snap',
            $user->id,
            5,
            'p-old',
            1,
            0,
            '2026-01-10 00:00:00',
            0,
            0
        );
        $resLivePrivDenied = $this->dispatchAsUser('get', 'account/viewHistory/' . $stalePermissiveHistId, $user, $viewHistProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resLivePrivDenied['body']);
        self::assertStringNotContainsString('Old Public Snap', $resLivePrivDenied['body']);

        //    2b. Snapshot was restrictive (otherUser, isPrivate=1), live Account is now non-private shared with user -> ALLOWED
        $becameSharedAccountId = $this->seedAccount($pdo, 'Now Shared Live', $otherUser->id, 6, null, 0, 0);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$becameSharedAccountId, $user->id]);
        $staleRestrictiveHistId = $this->seedAccountHistory(
            $pdo,
            null,
            $becameSharedAccountId,
            'Old Private Snap Allowed Now',
            $otherUser->id,
            6,
            'p-shared',
            1,
            0,
            '2026-01-11 00:00:00',
            1,
            0
        );
        $resLiveSharedOk = $this->dispatchAsUser('get', 'account/viewHistory/' . $staleRestrictiveHistId, $user, $viewHistProfile);
        $this->assertResponseWasDispatched($resLiveSharedOk);
        self::assertStringContainsString('Old Private Snap Allowed Now', $resLiveSharedOk['body']);

        // 3. Deleted Account uses single isDeleted=1 snapshot's ownership/privacy; missing, corrupt or ambiguous fails closed
        $deletedPrivOwnerHistId = $this->seedAccountHistory($pdo, null, 9001, 'Deleted Priv Owner Snap', $user->id, 5, 'p9001', 0, 1, null, 1, 0);
        $deletedOtherPrivHistId = $this->seedAccountHistory($pdo, null, 9002, 'Deleted Other Priv Snap', $otherUser->id, 6, 'p9002', 0, 1, null, 1, 0);
        $deletedOtherPrivGrpHistId = $this->seedAccountHistory($pdo, null, 9003, 'Deleted Other PrivGroup Snap', $otherUser->id, 6, 'p9003', 0, 1, null, 0, 1);

        $resOwnDeleted = $this->dispatchAsUser('get', 'account/viewHistory/' . $deletedPrivOwnerHistId, $user, $viewHistProfile);
        $this->assertResponseWasDispatched($resOwnDeleted);
        self::assertStringContainsString('Deleted Priv Owner Snap', $resOwnDeleted['body']);

        $resOtherDeletedPriv = $this->dispatchAsUser('get', 'account/viewHistory/' . $deletedOtherPrivHistId, $user, $viewHistProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resOtherDeletedPriv['body']);
        self::assertStringNotContainsString('Deleted Other Priv Snap', $resOtherDeletedPriv['body']);

        $resOtherDeletedGrp = $this->dispatchAsUser('get', 'account/viewHistory/' . $deletedOtherPrivGrpHistId, $user, $viewHistProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resOtherDeletedGrp['body']);
        self::assertStringNotContainsString('Deleted Other PrivGroup Snap', $resOtherDeletedGrp['body']);

        // 3a. Missing deletion snapshot (only isModify=1 exists, no live Account): fail closed even for AdminApp!
        $orphanModifyOnlyHistId = $this->seedAccountHistory($pdo, null, 9004, 'Orphan Modify Snap', $user->id, 5, 'p9004', 1, 0);
        $resOrphanAdmin = $this->dispatchAsUser('get', 'account/viewHistory/' . $orphanModifyOnlyHistId, $appAdminUser, $appAdminProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resOrphanAdmin['body']);
        self::assertStringNotContainsString('Orphan Modify Snap', $resOrphanAdmin['body']);

        // 3b. Ambiguous deletion snapshots (2 isDeleted=1 rows for same accountId): fail closed even for owner/AdminApp!
        $ambigDel1 = $this->seedAccountHistory($pdo, null, 9005, 'Ambiguous Del One', $user->id, 5, 'p9005a', 0, 1);
        $this->seedAccountHistory($pdo, null, 9005, 'Ambiguous Del Two', $user->id, 5, 'p9005b', 0, 1);
        $resAmbigAdmin = $this->dispatchAsUser('get', 'account/viewHistory/' . $ambigDel1, $appAdminUser, $appAdminProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resAmbigAdmin['body']);
        self::assertStringNotContainsString('Ambiguous Del One', $resAmbigAdmin['body']);

        // 3c. Corrupt deletion snapshot (empty pass with parentId=0): fail closed even for AdminApp!
        $corruptDelId = $this->seedAccountHistory($pdo, null, 9006, 'Corrupt Empty Pass Snap', $user->id, 5, '', 0, 1);
        $pdo->prepare('UPDATE AccountHistory SET pass = ?, `key` = ? WHERE id = ?')->execute(['', '', $corruptDelId]);
        $resCorruptAdmin = $this->dispatchAsUser('get', 'account/viewHistory/' . $corruptDelId, $appAdminUser, $appAdminProfile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $resCorruptAdmin['body']);
        self::assertStringNotContainsString('Corrupt Empty Pass Snap', $resCorruptAdmin['body']);
    }

    #[Test]
    public function viewPassHistoryAndCopyPassHistoryRequireHistoryViewAndSelectExactHistoryRowWithAcl(): void
    {
        $pdo = getDbHandler()->getConnection();

        $noHistProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccViewHistory(false);

        $fullHistProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccViewHistory(true);

        $userNoHist = $this->seedUser($pdo, 'hist_pass_no_hist', 5, $noHistProfile);
        $userHist = $this->seedUser($pdo, 'hist_pass_full', 5, $fullHistProfile);
        $otherUser = $this->seedUser($pdo, 'hist_pass_other', 6, $fullHistProfile);

        $multiAccId = $this->seedAccount($pdo, 'Multi History Account', $userHist->id, 5);
        $h1 = $this->seedAccountHistory($pdo, null, $multiAccId, 'Multi Snap 1', $userHist->id, 5, 'SecretVer1!');
        $h2 = $this->seedAccountHistory($pdo, null, $multiAccId, 'Multi Snap 2', $userHist->id, 5, 'SecretVer2!');

        $userNoHistAcc = $this->seedAccount($pdo, 'No Hist User Acc', $userNoHist->id, 5);
        $hDenied = $this->seedAccountHistory($pdo, null, $userNoHistAcc, 'Denied Snap', $userNoHist->id, 5, 'HiddenHistPass!');

        // 1. User lacking accViewHistory is denied viewPassHistory and copyPassHistory even with accViewPass=true
        $deniedViewPass = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $hDenied, $userNoHist, $noHistProfile);
        $deniedViewJson = $this->decodeJson($deniedViewPass['body']);
        self::assertSame('ERROR', $deniedViewJson['status'] ?? null);
        self::assertStringNotContainsString('HiddenHistPass!', $deniedViewPass['body']);

        $deniedCopyPass = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $hDenied, $userNoHist, $noHistProfile);
        $deniedCopyJson = $this->decodeJson($deniedCopyPass['body']);
        self::assertSame('ERROR', $deniedCopyJson['status'] ?? null);
        self::assertStringNotContainsString('HiddenHistPass!', $deniedCopyPass['body']);

        // 2. Authorized user fetches exact selected history row across multi-history account by AccountHistory.id
        $viewH1 = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $h1, $userHist, $fullHistProfile);
        $viewH1Json = $this->assertJsonOk($viewH1);
        self::assertStringContainsString('SecretVer1!', (string)($viewH1Json['data']['html'] ?? ''));

        $copyH2 = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $h2, $userHist, $fullHistProfile);
        $copyH2Json = $this->assertJsonOk($copyH2, 'Password copied');
        self::assertSame('SecretVer2!', $copyH2Json['data']['accpass'] ?? null);

        // 3. Passing accountId instead of historyId must NOT match AccountHistory.accountId
        $byAccountId = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $multiAccId, $userHist, $fullHistProfile);
        $byAccountJson = $this->decodeJson($byAccountId['body']);
        self::assertSame('ERROR', $byAccountJson['status'] ?? null);

        // 4. Live account that became private denies historical password reveal even if snapshot was non-private
        $becamePrivAcc = $this->seedAccount($pdo, 'Live Became Priv', $otherUser->id, 6, null, 1, 0);
        $hStalePub = $this->seedAccountHistory($pdo, null, $becamePrivAcc, 'Stale Public Hist', $userHist->id, 5, 'ShouldNotLeak!');
        $privRes = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $hStalePub, $userHist, $fullHistProfile);
        $privJson = $this->decodeJson($privRes['body']);
        self::assertSame('ERROR', $privJson['status'] ?? null);
        self::assertStringNotContainsString('ShouldNotLeak!', $privRes['body']);

        // 5. Missing/ambiguous deletion snapshot fails closed for historical password reveal
        $orphanModifyId = $this->seedAccountHistory($pdo, null, 9101, 'Orphan Pass Modify', $userHist->id, 5, 'OrphanPass!', 1, 0);
        $orphanRes = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $orphanModifyId, $userHist, $fullHistProfile);
        $orphanJson = $this->decodeJson($orphanRes['body']);
        self::assertSame('ERROR', $orphanJson['status'] ?? null);
        self::assertStringNotContainsString('OrphanPass!', $orphanRes['body']);
    }

    #[Test]
    public function saveEditRestoreValidatesIdCorrespondenceAclAndPasswordScopeWhilePreservingLiveSettings(): void
    {
        $pdo = getDbHandler()->getConnection();

        $noHistRestoreProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccViewHistory(false);

        $restoreNoPassProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewHistory(true)
            ->setAccEdit(true)
            ->setAccEditPass(false);

        $restoreWithPassProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewHistory(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);

        $ownerNoHist = $this->seedUser($pdo, 'restore_no_hist_owner', 5, $noHistRestoreProfile);
        $ownerNoPass = $this->seedUser($pdo, 'restore_no_pass_owner', 5, $restoreNoPassProfile);
        $ownerWithPass = $this->seedUser($pdo, 'restore_with_pass_owner', 5, $restoreWithPassProfile);
        $sharedRoUser = $this->seedUser($pdo, 'restore_ro_user', 6, $restoreWithPassProfile);

        // 1. User lacking accViewHistory cannot restore
        $accNoHist = $this->seedAccount($pdo, 'Acc No Hist', $ownerNoHist->id, 5);
        $histNoHist = $this->seedAccountHistory($pdo, null, $accNoHist, 'Old Name NoHist', $ownerNoHist->id, 5, 'old-pass');
        $deniedNoHist = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $histNoHist . '/' . $accNoHist,
            $ownerNoHist,
            $noHistRestoreProfile
        );
        $this->assertJsonDenied($deniedNoHist, self::DENIED_OP);
        self::assertSame('Acc No Hist', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accNoHist));

        // 2. Mismatched historyId and accountId must be rejected even if user owns both
        $accA = $this->seedAccount($pdo, 'Acc A Current', $ownerWithPass->id, 5, null, 0, 0, 'cur-a-pass');
        $accB = $this->seedAccount($pdo, 'Acc B Current', $ownerWithPass->id, 5, null, 0, 0, 'cur-b-pass');
        $histB = $this->seedAccountHistory($pdo, null, $accB, 'Acc B Old', $ownerWithPass->id, 5, 'old-b-pass');

        $mismatchRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $histB . '/' . $accA,
            $ownerWithPass,
            $restoreWithPassProfile
        );
        $mismatchJson = $this->decodeJson($mismatchRes['body']);
        self::assertSame('ERROR', $mismatchJson['status'] ?? null);
        self::assertSame('Acc A Current', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accA));
        self::assertSame('Acc B Current', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accB));

        // 3. Restoring without ACCOUNT_EDIT_PASS restores non-pass fields + historical CFs, retains live password/owner/privacy/grants/tags/files/favorites, revokes PublicLink
        $accNoPassId = $this->seedAccount($pdo, 'Current Live Name', $ownerNoPass->id, 5, null, 1, 0, 'KeepLivePass!');
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')->execute([$accNoPassId, $sharedRoUser->id]);
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$accNoPassId]);
        $pdo->prepare('INSERT INTO AccountToFavorite (accountId, userId) VALUES (?, ?)')->execute([$accNoPassId, $ownerNoPass->id]);
        $this->seedPublicLink($pdo, $accNoPassId, $ownerNoPass->id, 'restore-nopass-hash');
        // Seed live CF value and historical CF snapshot
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$accNoPassId, AclActionsInterface::ACCOUNT, 'live-cf-val']);

        // Snapshot records different owner (1), non-private (0), old name/login, old pass, and historical CF
        $histNoPassId = $this->seedAccountHistory(
            $pdo,
            null,
            $accNoPassId,
            'Historical Name Restored',
            1,
            1,
            'OldHistPassIgnored!',
            1,
            0,
            '2026-01-05 12:00:00',
            0,
            0
        );
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$histNoPassId, AclActionsInterface::ACCOUNT_HISTORY_VIEW, 'hist-cf-val']);

        $restoreNoPassRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $histNoPassId . '/' . $accNoPassId,
            $ownerNoPass,
            $restoreNoPassProfile
        );
        $this->assertJsonOk($restoreNoPassRes, 'Account restored');

        self::assertSame('Historical Name Restored', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accNoPassId));
        self::assertSame($ownerNoPass->id, (int)$this->fetchValue('SELECT userId FROM Account WHERE id = ' . $accNoPassId));
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $accNoPassId));
        self::assertSame('KeepLivePass!', $this->decryptAccountPassword($pdo, $accNoPassId));
        self::assertSame('hist-cf-val', $this->fetchValue('SELECT data FROM CustomFieldData WHERE moduleId = 1 AND itemId = ' . $accNoPassId));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToUser WHERE accountId = ' . $accNoPassId));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToTag WHERE accountId = ' . $accNoPassId));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToFavorite WHERE accountId = ' . $accNoPassId));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $accNoPassId));
        self::assertSame(2, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $accNoPassId));

        // 4. Restoring WITH ACCOUNT_EDIT_PASS also restores historical password
        $accWithPassId = $this->seedAccount($pdo, 'Live Name Before', $ownerWithPass->id, 5, null, 0, 0, 'CurrentPass!');
        $histWithPassId = $this->seedAccountHistory($pdo, null, $accWithPassId, 'Restored Name With Pass', $ownerWithPass->id, 5, 'RestoredOldPass!');
        $restoreWithPassRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $histWithPassId . '/' . $accWithPassId,
            $ownerWithPass,
            $restoreWithPassProfile
        );
        $this->assertJsonOk($restoreWithPassRes, 'Account restored');
        self::assertSame('Restored Name With Pass', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accWithPassId));
        self::assertSame('RestoredOldPass!', $this->decryptAccountPassword($pdo, $accWithPassId));
    }

    #[Test]
    public function accountHistoryManagerSearchFiltersByLiveAndDeletedSnapshotAclAndParenthesizesQuery(): void
    {
        $pdo = getDbHandler()->getConnection();

        $mgrProfile = (new ProfileData())
            ->setMgmAccounts(true)
            ->setAccView(true)
            ->setAccViewHistory(true);
        $mgrUser = $this->seedUser($pdo, 'hist_search_mgr', 5, $mgrProfile);
        $otherUser = $this->seedUser($pdo, 'hist_search_other', 6, $mgrProfile);

        // Live private account owned by otherUser (even with non-private snapshot): hidden
        $liveOtherPriv = $this->seedAccount($pdo, 'Live Priv Target', $otherUser->id, 6, null, 1, 0);
        $this->seedAccountHistory($pdo, null, $liveOtherPriv, 'SECRET_LIVE_PRIV_HIST', $mgrUser->id, 5, 'p', 1, 0, null, 0, 0);

        // Live shared account owned by mgrUser: visible
        $liveOwn = $this->seedAccount($pdo, 'Live Own Target', $mgrUser->id, 5);
        $this->seedAccountHistory($pdo, null, $liveOwn, 'VISIBLE_LIVE_OWN_HIST', $mgrUser->id, 5, 'p', 1, 0);

        // Deleted account with valid single isDeleted=1 snapshot owned by mgrUser: visible (both isModify & isDeleted rows)
        $this->seedAccountHistory($pdo, null, 9201, 'VISIBLE_DEL_MOD_HIST', $mgrUser->id, 5, 'p', 1, 0, null, 1, 0);
        $this->seedAccountHistory($pdo, null, 9201, 'VISIBLE_DEL_SNAP_HIST', $mgrUser->id, 5, 'p', 0, 1, null, 1, 0);

        // Deleted account private to otherUser: hidden
        $this->seedAccountHistory($pdo, null, 9202, 'SECRET_DEL_OTHER_PRIV', $otherUser->id, 6, 'p', 0, 1, null, 1, 0);

        // Deleted account missing deletion snapshot: hidden
        $this->seedAccountHistory($pdo, null, 9203, 'ORPHAN_DEL_NO_SNAP', $mgrUser->id, 5, 'p', 1, 0, null, 0, 0);

        // Deleted account with ambiguous (>1) deletion snapshots: hidden
        $this->seedAccountHistory($pdo, null, 9204, 'AMBIG_DEL_SNAP_1', $mgrUser->id, 5, 'p', 0, 1, null, 0, 0);
        $this->seedAccountHistory($pdo, null, 9204, 'AMBIG_DEL_SNAP_2', $mgrUser->id, 5, 'p', 0, 1, null, 0, 0);

        $res = $this->dispatchAsUser('get', 'accountHistoryManager/search', $mgrUser, $mgrProfile);
        $json = $this->assertJsonOk($res);
        $html = (string)($json['data']['html'] ?? '');

        self::assertStringContainsString('VISIBLE_LIVE_OWN_HIST', $html);
        self::assertStringContainsString('VISIBLE_DEL_MOD_HIST', $html);
        self::assertStringContainsString('VISIBLE_DEL_SNAP_HIST', $html);
        self::assertStringNotContainsString('SECRET_LIVE_PRIV_HIST', $html);
        self::assertStringNotContainsString('SECRET_DEL_OTHER_PRIV', $html);
        self::assertStringNotContainsString('ORPHAN_DEL_NO_SNAP', $html);
        self::assertStringNotContainsString('AMBIG_DEL_SNAP_1', $html);
        self::assertStringNotContainsString('AMBIG_DEL_SNAP_2', $html);

        // Search filter against Client name ('Google') must NOT bypass ACL via unparenthesized OR
        $clientSearchRes = $this->dispatchAsUser(
            'post',
            'accountHistoryManager/search',
            $mgrUser,
            $mgrProfile,
            [],
            ['search' => 'Google']
        );
        $clientJson = $this->assertJsonOk($clientSearchRes);
        $clientHtml = (string)($clientJson['data']['html'] ?? '');
        self::assertStringContainsString('VISIBLE_LIVE_OWN_HIST', $clientHtml);
        self::assertStringNotContainsString('SECRET_LIVE_PRIV_HIST', $clientHtml);
        self::assertStringNotContainsString('SECRET_DEL_OTHER_PRIV', $clientHtml);
    }

    #[Test]
    public function accountHistoryManagerDeleteEnforcesAclSnapshotRetentionAndBatchAtomicity(): void
    {
        $pdo = getDbHandler()->getConnection();

        $noMgrProfile = (new ProfileData())->setAccView(true)->setAccEdit(true)->setAccDelete(true);
        $mgrProfile = (new ProfileData())->setMgmAccounts(true)->setAccView(true)->setAccEdit(true)->setAccDelete(true);

        $noMgrUser = $this->seedUser($pdo, 'hist_del_no_mgr', 5, $noMgrProfile);
        $mgrUser = $this->seedUser($pdo, 'hist_del_mgr', 5, $mgrProfile);
        $otherUser = $this->seedUser($pdo, 'hist_del_other', 6, $mgrProfile);

        $otherPrivAcc = $this->seedAccount($pdo, 'Other Priv Account', $otherUser->id, 6, null, 1, 0);
        $otherPrivHist = $this->seedAccountHistory($pdo, null, $otherPrivAcc, 'Other Priv Hist', $otherUser->id, 6, 'p');

        $roAcc = $this->seedAccount($pdo, 'RO Shared Account', $otherUser->id, 6, null, 0, 0);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')->execute([$roAcc, $mgrUser->id]);
        $roHist = $this->seedAccountHistory($pdo, null, $roAcc, 'RO Shared Hist', $otherUser->id, 6, 'p');

        $ownLiveAcc = $this->seedAccount($pdo, 'Own Live Acc', $mgrUser->id, 5);
        $ownLiveHist1 = $this->seedAccountHistory($pdo, null, $ownLiveAcc, 'Own Live Hist 1', $mgrUser->id, 5, 'p1');
        $ownLiveHist2 = $this->seedAccountHistory($pdo, null, $ownLiveAcc, 'Own Live Hist 2', $mgrUser->id, 5, 'p2');
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$ownLiveHist1, AclActionsInterface::ACCOUNT_HISTORY_VIEW, 'cf-snap-1']);

        // 1. User lacking ACCOUNTMGR_HISTORY_DELETE is denied
        $unprivRes = $this->dispatchAsUser('get', 'accountHistoryManager/delete/' . $ownLiveHist1, $noMgrUser, $noMgrProfile);
        $this->assertJsonDenied($unprivRes, self::DENIED_OP);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $ownLiveHist1));

        // 2. Manager cannot delete other user's private or read-only live history (single or batch)
        $crossPrivRes = $this->dispatchAsUser('get', 'accountHistoryManager/delete/' . $otherPrivHist, $mgrUser, $mgrProfile);
        $crossPrivJson = $this->decodeJson($crossPrivRes['body']);
        self::assertSame('ERROR', $crossPrivJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $otherPrivHist));

        $roRes = $this->dispatchAsUser('get', 'accountHistoryManager/delete/' . $roHist, $mgrUser, $mgrProfile);
        $roJson = $this->decodeJson($roRes['body']);
        self::assertSame('ERROR', $roJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $roHist));

        // 3. Batch containing valid + unauthorized/missing ID rolls back EVERYTHING (all-or-none)
        $mixedBatchRes = $this->dispatchAsUser(
            'post',
            'accountHistoryManager/delete',
            $mgrUser,
            $mgrProfile,
            [],
            ['items' => [$ownLiveHist1, $ownLiveHist1, $otherPrivHist]]
        );
        $mixedBatchJson = $this->decodeJson($mixedBatchRes['body']);
        self::assertSame('ERROR', $mixedBatchJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $ownLiveHist1));

        // 4. For a deleted Account, cannot delete its isDeleted=1 snapshot while older modify history remains, unless ALL remaining history is in the same batch
        $delModHist = $this->seedAccountHistory($pdo, null, 9301, 'Del Mod Hist', $mgrUser->id, 5, 'pm', 1, 0);
        $delSnapHist = $this->seedAccountHistory($pdo, null, 9301, 'Del Snap Hist', $mgrUser->id, 5, 'pd', 0, 1);

        $singleSnapRes = $this->dispatchAsUser('get', 'accountHistoryManager/delete/' . $delSnapHist, $mgrUser, $mgrProfile);
        $singleSnapJson = $this->decodeJson($singleSnapRes['body']);
        self::assertSame('ERROR', $singleSnapJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $delSnapHist));

        // Deleting ALL remaining history of deleted Account 9301 together succeeds and removes last recovery snapshot with warning
        $batchAllDeleted = $this->dispatchAsUser(
            'post',
            'accountHistoryManager/delete',
            $mgrUser,
            $mgrProfile,
            [],
            ['items' => [$delModHist, $delSnapHist]]
        );
        $batchAllJson = $this->decodeJson($batchAllDeleted['body']);
        self::assertContains($batchAllJson['status'] ?? null, ['OK', 'WARNING']);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = 9301'));

        // 5. Authorized delete removes live history row + its historical CF snapshot
        $okDel = $this->dispatchAsUser('get', 'accountHistoryManager/delete/' . $ownLiveHist1, $mgrUser, $mgrProfile);
        $okDelJson = $this->decodeJson($okDel['body']);
        self::assertContains($okDelJson['status'] ?? null, ['OK', 'WARNING']);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $ownLiveHist1));
        self::assertSame(
            0,
            (int)$this->fetchValue(
                'SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = ' . AclActionsInterface::ACCOUNT_HISTORY_VIEW . ' AND itemId = ' . $ownLiveHist1
            )
        );
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $ownLiveHist2));
    }

    #[Test]
    public function accountHistoryManagerRestoreHandlesRemovedIdPreservationCollisionDisambiguationAndLiveAcl(): void
    {
        $pdo = getDbHandler()->getConnection();

        $mgrProfile = (new ProfileData())
            ->setMgmAccounts(true)
            ->setAccView(true)
            ->setAccViewHistory(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);
        $mgrUser = $this->seedUser($pdo, 'hist_mgr_rest', 5, $mgrProfile);
        $otherUser = $this->seedUser($pdo, 'hist_mgr_other', 6, $mgrProfile);

        // 1. Deleted account with unoccupied original ID 9401 restores WITH id=9401, pass, CFs, owner/privacy, NO files/favorites/grants/tags/links
        $unoccId = 9401;
        $delMod9401 = $this->seedAccountHistory($pdo, null, $unoccId, 'Unocc Old Mod', $mgrUser->id, 5, 'mod-pass-9401', 1, 0, '2026-01-01 10:00:00', 1, 0);
        $delSnap9401 = $this->seedAccountHistory($pdo, null, $unoccId, 'Unocc Deleted Snap', $mgrUser->id, 5, 'RestoredSecret9401!', 0, 1, '2026-01-02 10:00:00', 1, 0);
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$delSnap9401, AclActionsInterface::ACCOUNT_HISTORY_VIEW, 'cf-restored-9401']);

        $resUnocc = $this->dispatchAsUser('get', 'accountHistoryManager/restore/' . $delSnap9401, $mgrUser, $mgrProfile);
        $this->assertJsonOk($resUnocc, 'Account restored');

        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $unoccId));
        self::assertSame('Unocc Deleted Snap', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $unoccId));
        self::assertSame('RestoredSecret9401!', $this->decryptAccountPassword($pdo, $unoccId));
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $unoccId));
        self::assertSame($mgrUser->id, (int)$this->fetchValue('SELECT userId FROM Account WHERE id = ' . $unoccId));
        self::assertSame('cf-restored-9401', $this->fetchValue('SELECT data FROM CustomFieldData WHERE moduleId = 1 AND itemId = ' . $unoccId));
        // Both history rows stay retained for restored 9401
        self::assertSame(2, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $unoccId . ' AND id IN (' . $delMod9401 . ',' . $delSnap9401 . ')'));

        // 2. Deleted account whose original ID IS OCCUPIED with cleanly separable dateAdd restores under new ID and re-associates ONLY deleted history
        $occId = 9402;
        $this->seedAccountWithDateAdd($pdo, 'Occupying Account 9402', $otherUser->id, 6, '2026-05-01 00:00:00', $occId);
        $occHistId = $this->seedAccountHistory($pdo, 503, $occId, 'Occupying Snap 503', $otherUser->id, 6, 'occ-pass', 1, 0, '2026-05-02 00:00:00', 0, 0, '2026-05-01 00:00:00');
        $delMod9402 = $this->seedAccountHistory($pdo, 501, $occId, 'Deleted Mod 501', $mgrUser->id, 5, 'del-mod-pass', 1, 0, '2026-02-01 00:00:00', 0, 0, '2026-02-01 00:00:00');
        $delSnap9402 = $this->seedAccountHistory($pdo, 502, $occId, 'Deleted Snap 502', $mgrUser->id, 5, 'DeletedPass9402!', 0, 1, '2026-02-02 00:00:00', 0, 0, '2026-02-01 00:00:00');
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$delSnap9402, AclActionsInterface::ACCOUNT_HISTORY_VIEW, 'cf-del-9402']);

        $resOcc = $this->dispatchAsUser('get', 'accountHistoryManager/restore/' . $delSnap9402, $mgrUser, $mgrProfile);
        $this->assertJsonOk($resOcc, 'Account restored');

        $newRestoredId = (int)$this->fetchValue("SELECT id FROM Account WHERE name = 'Deleted Snap 502'");
        self::assertGreaterThan(0, $newRestoredId);
        self::assertNotSame($occId, $newRestoredId);
        self::assertSame('Occupying Account 9402', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $occId));
        self::assertSame($occId, (int)$this->fetchValue('SELECT accountId FROM AccountHistory WHERE id = ' . $occHistId));
        self::assertSame($newRestoredId, (int)$this->fetchValue('SELECT accountId FROM AccountHistory WHERE id = ' . $delMod9402));
        self::assertSame($newRestoredId, (int)$this->fetchValue('SELECT accountId FROM AccountHistory WHERE id = ' . $delSnap9402));
        self::assertSame('DeletedPass9402!', $this->decryptAccountPassword($pdo, $newRestoredId));
        self::assertSame('cf-del-9402', $this->fetchValue('SELECT data FROM CustomFieldData WHERE moduleId = 1 AND itemId = ' . $newRestoredId));

        // 3. Ambiguous occupied ID (shared dateAdd with occupying Account): FAIL CLOSED without creating Account or moving history
        $ambigOccId = 9503;
        $sharedDate = '2026-06-01 00:00:00';
        $this->seedAccountWithDateAdd($pdo, 'Occupying Ambiguous 9503', $mgrUser->id, 5, $sharedDate, $ambigOccId);
        $ambigSnap = $this->seedAccountHistory($pdo, null, $ambigOccId, 'Ambiguous Collision Snap', $mgrUser->id, 5, 'p-ambig', 0, 1, '2026-06-02 00:00:00', 0, 0, $sharedDate);
        $countBeforeAmbig = (int)$this->fetchValue('SELECT COUNT(*) FROM Account');

        $resAmbigOcc = $this->dispatchAsUser('get', 'accountHistoryManager/restore/' . $ambigSnap, $mgrUser, $mgrProfile);
        $ambigOccJson = $this->decodeJson($resAmbigOcc['body']);
        self::assertSame('ERROR', $ambigOccJson['status'] ?? null);
        self::assertSame($countBeforeAmbig, (int)$this->fetchValue('SELECT COUNT(*) FROM Account'));
        self::assertSame($ambigOccId, (int)$this->fetchValue('SELECT accountId FROM AccountHistory WHERE id = ' . $ambigSnap));

        // 4. Missing recorded Client/Category/Owner/Group fails cleanly without creating Account
        $missingClientDel = $this->seedAccountHistory($pdo, null, 9404, 'Missing Client Snap', $mgrUser->id, 5, 'p9404', 0, 1);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->prepare('UPDATE AccountHistory SET clientId = 999999 WHERE id = ?')->execute([$missingClientDel]);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $resMissingRef = $this->dispatchAsUser('get', 'accountHistoryManager/restore/' . $missingClientDel, $mgrUser, $mgrProfile);
        $missingRefJson = $this->decodeJson($resMissingRef['body']);
        self::assertSame('ERROR', $missingRefJson['status'] ?? null);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = 9404'));

        // 5. Manager cannot restore another user's private deleted account
        $otherPrivDel = $this->seedAccountHistory($pdo, null, 9405, 'Other Priv Deleted', $otherUser->id, 6, 'p9405', 0, 1, null, 1, 0);
        $resOtherPrivDel = $this->dispatchAsUser('get', 'accountHistoryManager/restore/' . $otherPrivDel, $mgrUser, $mgrProfile);
        $otherPrivDelJson = $this->decodeJson($resOtherPrivDel['body']);
        self::assertSame('ERROR', $otherPrivDelJson['status'] ?? null);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = 9405'));
    }

    #[Test]
    public function bulkEditAndHistoryRestoreRejectAnIneligibleClient(): void
    {
        $pdo = getDbHandler()->getConnection();
        $profile = (new ProfileData())->setMgmAccounts(true)->setAccView(true)->setAccEdit(true)->setAccViewHistory(true);
        $user = $this->seedUser($pdo, 'bulk_client_visibility', 5, $profile);
        $target = $this->seedAccount($pdo, 'Eligibility Target', $user->id, 5);
        $other = $this->seedAccount($pdo, 'Hidden Client Owner', 1, 1);
        $pdo->exec("INSERT INTO Client (name, hash, description, isGlobal) VALUES ('History Hidden Client', 'history-hidden-client', '', 0)");
        $clientId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE Account SET clientId = ? WHERE id = ?')->execute([$clientId, $other]);
        $history = $this->seedAccountHistory($pdo, null, $target, 'Historical Target', $user->id, 5, 'synthetic-pass');
        $pdo->prepare('UPDATE AccountHistory SET clientId = ? WHERE id = ?')->execute([$clientId, $history]);

        $form = $this->dispatchAsUser('post', 'accountManager/bulkEdit', $user, $profile, [], [
            'items' => [$target],
        ]);
        $formJson = $this->assertJsonOk($form);
        self::assertStringNotContainsString('History Hidden Client', $formJson['data']['html'] ?? '');

        $bulk = $this->dispatchAsUser('post', 'accountManager/saveBulkEdit', $user, $profile, [], [
            'itemsId' => (string)$target, 'client_id' => (string)$clientId, 'delete_history' => 'true',
        ]);
        self::assertSame('ERROR', $this->decodeJson($bulk['body'])['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT clientId FROM Account WHERE id = ' . $target));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $history));

        $restore = $this->dispatchAsUser('post', 'account/saveEditRestore/' . $history . '/' . $target, $user, $profile);
        self::assertSame('ERROR', $this->decodeJson($restore['body'])['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT clientId FROM Account WHERE id = ' . $target));
    }

    #[Test]
    public function bulkEditAndBatchDeleteEnforceAtomicityPresetsChildLinksAndFullCleanup(): void
    {
        $pdo = getDbHandler()->getConnection();

        $mgrProfile = (new ProfileData())
            ->setMgmAccounts(true)
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccDelete(true)
            ->setAccPermission(true)
            ->setAccViewHistory(true);
        $mgrUser = $this->seedUser($pdo, 'bulk_mgr', 5, $mgrProfile);
        $otherUser = $this->seedUser($pdo, 'bulk_other', 6, $mgrProfile);
        $pdo->exec('UPDATE Client SET isGlobal = 1 WHERE id = 2');

        $a1 = $this->seedAccount($pdo, 'Bulk A1', $mgrUser->id, 5);
        $a2 = $this->seedAccount($pdo, 'Bulk A2', $mgrUser->id, 5);
        $otherPriv = $this->seedAccount($pdo, 'Secret Other Bulk', $otherUser->id, 6, null, 1, 0);
        $this->seedAccountHistory($pdo, null, $a1, 'A1 Hist Init', $mgrUser->id, 5, 'p1');
        $this->seedAccountHistory($pdo, null, $a2, 'A2 Hist Init', $mgrUser->id, 5, 'p2');
        $this->seedPublicLink($pdo, $a1, $mgrUser->id, 'bulk-link-a1');

        // 1. Request-wide invalid field (client_id=999999) MUST reject BEFORE deleting history or touching any Account
        $badClientRes = $this->dispatchAsUser(
            'post',
            'accountManager/saveBulkEdit',
            $mgrUser,
            $mgrProfile,
            [],
            [
                'itemsId' => $a1 . ',' . $a2,
                'client_id' => '999999',
                'delete_history' => 'true',
            ]
        );
        $badClientJson = $this->decodeJson($badClientRes['body']);
        self::assertSame('ERROR', $badClientJson['status'] ?? null);
        self::assertSame(2, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId IN (' . $a1 . ',' . $a2 . ')'));

        // 2. Partial success + deduplicated itemsId + fixed AccountPrivate preset + atomic purge-and-update
        $this->seedItemPreset(
            ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE,
            $mgrUser->id,
            null,
            null,
            10,
            new AccountPrivate(true, false),
            1
        );
        $this->seedAccountHistory($pdo, null, $otherPriv, 'Other Priv Hist Keep', $otherUser->id, 6, 'p-other');

        $mixedBulkRes = $this->dispatchAsUser(
            'post',
            'accountManager/saveBulkEdit',
            $mgrUser,
            $mgrProfile,
            [],
            [
                'itemsId' => $a1 . ',' . $a1 . ',' . $otherPriv,
                'client_id' => '2',
                'delete_history' => 'true',
            ]
        );
        $mixedBulkJson = $this->decodeJson($mixedBulkRes['body']);
        self::assertContains($mixedBulkJson['status'] ?? null, ['WARNING', 'OK']);
        self::assertStringNotContainsString('Secret Other Bulk', $mixedBulkRes['body']);

        // a1: old history purged + 1 new snapshot created + client updated + fixed private preset applied + PublicLink revoked
        self::assertSame(2, (int)$this->fetchValue('SELECT clientId FROM Account WHERE id = ' . $a1));
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $a1));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $a1));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $a1));
        // otherPriv: completely untouched (including its history!)
        self::assertSame(1, (int)$this->fetchValue('SELECT clientId FROM Account WHERE id = ' . $otherPriv));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $otherPriv));

        // 3. No-op bulk edit with delete_history=true purges history WITHOUT adding a new redundant snapshot
        $noopRes = $this->dispatchAsUser(
            'post',
            'accountManager/saveBulkEdit',
            $mgrUser,
            $mgrProfile,
            [],
            [
                'itemsId' => (string)$a1,
                'client_id' => '2',
                'delete_history' => 'true',
            ]
        );
        $this->assertJsonOk($noopRes, 'Accounts updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $a1));

        // 4. Delete & batch delete reject linked parent and perform full atomic snapshot + cleanup
        $childAcc = $this->seedAccountWithParent($pdo, 'Child Account', $mgrUser->id, 5, $a1);

        $singleParentDel = $this->dispatchAsUser('post', 'account/saveDelete/' . $a1, $mgrUser, $mgrProfile);
        $singleParentJson = $this->decodeJson($singleParentDel['body']);
        self::assertSame('ERROR', $singleParentJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $a1));

        // Batch containing valid a2 AND undeletable parent a1 rolls back BOTH
        $batchFailParent = $this->dispatchAsUser(
            'post',
            'accountManager/delete',
            $mgrUser,
            $mgrProfile,
            [],
            ['items' => [$a2, $a1]]
        );
        $batchFailJson = $this->decodeJson($batchFailParent['body']);
        self::assertSame('ERROR', $batchFailJson['status'] ?? null);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $a2));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $a2 . ' AND isDeleted = 1'));

        // Attach files, favorites, tags, grants, CFs, PublicLink to a2 and batch-delete [childAcc, a2, a2]
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')->execute([$a2, $otherUser->id]);
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$a2]);
        $pdo->prepare('INSERT INTO AccountToFavorite (accountId, userId) VALUES (?, ?)')->execute([$a2, $mgrUser->id]);
        $pdo->prepare('INSERT INTO AccountFile (accountId, name, type, size, content, extension) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$a2, 'secret.txt', 'text/plain', 5, 'hello', 'txt']);
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, ?, 1, ?, NULL)')
            ->execute([$a2, AclActionsInterface::ACCOUNT, 'cf-before-batch-del']);
        $this->seedPublicLink($pdo, $a2, $mgrUser->id, 'batch-del-link-a2');

        $batchOk = $this->dispatchAsUser(
            'post',
            'accountManager/delete',
            $mgrUser,
            $mgrProfile,
            [],
            ['items' => [$childAcc, $a2, $a2]]
        );
        $this->assertJsonOk($batchOk, 'Accounts removed');

        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id IN (' . $childAcc . ',' . $a2 . ')'));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $childAcc . ' AND isDeleted = 1'));
        $a2DelSnapId = (int)$this->fetchValue('SELECT id FROM AccountHistory WHERE accountId = ' . $a2 . ' AND isDeleted = 1');
        self::assertGreaterThan(0, $a2DelSnapId);
        // Cleanup of associations, files, favorites, links, live CFs; Tag definition preserved; CF snapshot preserved!
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToUser WHERE accountId = ' . $a2));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToTag WHERE accountId = ' . $a2));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Tag WHERE id = 1'));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToFavorite WHERE accountId = ' . $a2));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountFile WHERE accountId = ' . $a2));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $a2));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = 1 AND itemId = ' . $a2));
        self::assertSame(
            'cf-before-batch-del',
            $this->fetchValue(
                'SELECT data FROM CustomFieldData WHERE moduleId = ' . AclActionsInterface::ACCOUNT_HISTORY_VIEW . ' AND itemId = ' . $a2DelSnapId
            )
        );
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @return array{body: string, code: int}
     */
    private function dispatchAsUser(
        string      $method,
        string      $route,
        UserDto     $user,
        ProfileData $profile,
        array       $paramsGet = [],
        array       $paramsPost = []
    ): array {
        $context = $this->buildSessionContext($user, $profile);
        $configData = self::createConfiguredStub(ConfigDataInterface::class, $this->getConfigData());
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        return $this->dispatch(
            $method,
            $route,
            $paramsGet,
            $paramsPost,
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
    }

    private function buildSessionContext(UserDto $user, ProfileData $profile): SessionContext
    {
        $sidStartTime = time();
        $vault = self::createStub(VaultInterface::class);
        $vault->method('getData')->willReturn(self::MASTER_PASS);
        $vault->method('reKey')->willReturnSelf();
        $accountsCache = null;
        $transient = ['_masterpass' => self::MASTER_PASS];

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($user);
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getSearchFilters')->willReturn(null);
        $context->method('getSidStartTime')->willReturn($sidStartTime);
        $context->method('getVault')->willReturn($vault);
        $context->method('getAccountsCache')->willReturnCallback(static function () use (&$accountsCache) {
            return $accountsCache;
        });
        $context->method('setAccountsCache')->willReturnCallback(static function (array $cache) use (&$accountsCache): void {
            $accountsCache = $cache;
        });
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

    private function decryptAccountPassword(PDO $pdo, int $accountId): string
    {
        $row = $pdo->query('SELECT pass, `key` FROM Account WHERE id = ' . $accountId)->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);

        return (new Crypt())->decrypt((string)$row['pass'], (string)$row['key'], self::MASTER_PASS);
    }

    private function seedUser(PDO $pdo, string $login, int $userGroupId, ProfileData $profile): UserDto
    {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
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
            'isAdminApp' => false,
            'isAdminAcc' => false,
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
        ?int   $explicitId = null,
        int    $isPrivate = 0,
        int    $isPrivateGroup = 0,
        string $plainPass = 'account-secret'
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt($plainPass, $key, self::MASTER_PASS);

        if ($explicitId !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO Account (id, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
                 VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
            );
            $stmt->execute([
                $explicitId,
                $userGroupId,
                $userId,
                $userId,
                $name,
                'acc-login-' . $explicitId,
                'https://example.test',
                $encPass,
                $key,
                'notes',
                $isPrivate,
                $isPrivateGroup,
            ]);

            return $explicitId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userId,
            $name,
            'acc-login',
            'https://example.test',
            $encPass,
            $key,
            'notes',
            $isPrivate,
            $isPrivateGroup,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedAccountWithParent(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        int    $parentId
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), 0, 0, UNIX_TIMESTAMP(), ?)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userId,
            $name,
            'child-login',
            'https://example.test',
            '',
            '',
            'child notes',
            $parentId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedAccountWithDateAdd(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        string $dateAdd,
        int    $explicitId
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt('occ-secret', $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO Account (id, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, ?, 0, 0, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $explicitId,
            $userGroupId,
            $userId,
            $userId,
            $name,
            'occ-login',
            'https://example.test',
            $encPass,
            $key,
            'notes',
            $dateAdd,
        ]);

        return $explicitId;
    }

    private function seedAccountHistory(
        PDO     $pdo,
        ?int    $explicitId,
        int     $accountId,
        string  $name,
        int     $userId,
        int     $userGroupId,
        string  $plainPass,
        int     $isModify = 1,
        int     $isDeleted = 0,
        ?string $dateEdit = '2026-01-01 00:00:00',
        int     $isPrivate = 0,
        int     $isPrivateGroup = 0,
        string  $dateAdd = '2026-01-01 00:00:00'
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $plainPass !== '' ? $crypt->encrypt($plainPass, $key, self::MASTER_PASS) : '';

        if ($explicitId !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO AccountHistory (id, accountId, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, dateEdit, isModify, isDeleted, isPrivate, isPrivateGroup, mPassHash, passDate)
                 VALUES (?, ?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())'
            );
            $stmt->execute([
                $explicitId,
                $accountId,
                $userGroupId,
                $userId,
                $userId,
                $name,
                'hist-login-' . $explicitId,
                'https://hist.example.test',
                $encPass,
                $key,
                'hist notes',
                $dateAdd,
                $dateEdit,
                $isModify,
                $isDeleted,
                $isPrivate,
                $isPrivateGroup,
                Hash::hashKey(self::MASTER_PASS),
            ]);

            return $explicitId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO AccountHistory (accountId, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, dateEdit, isModify, isDeleted, isPrivate, isPrivateGroup, mPassHash, passDate)
             VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())'
        );
        $stmt->execute([
            $accountId,
            $userGroupId,
            $userId,
            $userId,
            $name,
            'hist-login',
            'https://hist.example.test',
            $encPass,
            $key,
            'hist notes',
            $dateAdd,
            $dateEdit,
            $isModify,
            $isDeleted,
            $isPrivate,
            $isPrivateGroup,
            Hash::hashKey(self::MASTER_PASS),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedPublicLink(PDO $pdo, int $accountId, int $userId, string $hash): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO PublicLink (itemId, hash, data, userId, typeId, dateAdd, dateExpire, countViews, maxCountViews, totalCountViews, notify)
             VALUES (?, ?, ?, ?, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 600, 0, 5, 0, 0)'
        );
        $stmt->execute([$accountId, $hash, 'link-payload', $userId]);
    }
}
