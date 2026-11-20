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

namespace SP\Tests\Modules\Web\Controllers\User;

use Error;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

/**
 * DB-backed HTTP characterization tests for User, UserGroup, UserProfile, and UserSettingsGeneral:
 * - User\SaveEditController failing on real DB NOT NULL `pass` column (`UserRepository::update()` sets pass = NULL)
 * - UserForm::isDemo() calling undefined UserDto::getIsAdminApp() in demo mode on user id=2
 * - User\DeleteController and UserProfile\DeleteController cascades on UserToUserGroup & ItemPreset
 * - UserGroupViewBase / UserProfileViewBase `$this->view->isView === true` omitting disabled/readonly/usedBy
 * - UserGroup\SaveCreateController failing on duplicate users (`UNIQUE KEY uk_UserToUserGroup_01`) and non-numeric user IDs
 * - UserSettingsGeneral\SaveController persisting arbitrary unvalidated theme, lang, and negative resultsPerPage
 */
#[Group('integration')]
final class UserLifecycleDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->configOverrides = [];
    }

    /**
     * Characterization:
     * 1) UserRepository::checkDuplicatedOnUpdate() omits outer parentheses around its OR clauses after `WHERE id <> :id`,
     *    so any non-LDAP edit preserving the user's own email matches row 3 itself -> `'Duplicated user login/email'`.
     * 2) Even with a fresh unused email, UserForm omits `pass` during USER_EDIT, so UserRepository::update() binds NULL
     *    to NOT NULL `User.pass` -> QueryException (`'Error while updating the user'`).
     * 3) When `isDemoEnabled = true` and `$id = 2`, UserForm::isDemo() calls undefined `$userData->getIsAdminApp()`
     *    before reaching DB persistence, producing HTTP 500 Internal error.
     */
    #[Test]
    public function userSaveEditFailsOnNotNullPassConstraintAndCrashesOnDemoUserTwo(): void
    {
        $selfDupEdit = $this->dispatch('post', 'user/saveEdit/3', [], [
            'name' => 'Updated User A',
            'login' => 'user_a',
            'email' => 'user_a@syspass.org',
            'userprofile_id' => 1,
            'usergroup_id' => 2,
        ]);

        $selfDupJson = json_decode($selfDupEdit['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $selfDupJson['status']);
        self::assertSame('Duplicated user login/email', $selfDupJson['description']);
        self::assertSame('User A', $this->fetchValue('SELECT name FROM User WHERE id = 3'));

        $editResult = $this->dispatch('post', 'user/saveEdit/3', [], [
            'name' => 'Updated User A',
            'login' => 'user_a',
            'email' => 'unique_user_a@syspass.org',
            'userprofile_id' => 1,
            'usergroup_id' => 2,
        ]);

        $editJson = json_decode($editResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $editJson['status']);
        self::assertStringContainsString('SQLSTATE[HY093]', $editJson['description']);
        self::assertSame('User A', $this->fetchValue('SELECT name FROM User WHERE id = 3'));

        $this->configOverrides = ['isDemoEnabled' => true];

        $demoResult = $this->dispatch('post', 'user/saveEdit/2', [], [
            'name' => 'Demo Edited',
            'login' => 'demo',
            'email' => 'demo@syspass.org',
            'userprofile_id' => 2,
            'usergroup_id' => 2,
        ]);

        self::assertSame(500, $demoResult['code']);
        $demoJson = json_decode($demoResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $demoJson['status']);
        self::assertSame('Internal error', $demoJson['description']);
        self::assertSame('sysPass demo', $this->fetchValue('SELECT name FROM User WHERE id = 2'));
    }

    #[Test]
    public function userAndUnusedUserProfileDeletionsCascadeItemPresetRows(): void
    {
        $pdo = getDbHandler()->getConnection();

        self::assertNotNull($this->fetchValue('SELECT id FROM ItemPreset WHERE id = 4 AND userId = 2'));
        self::assertNotNull($this->fetchValue('SELECT id FROM ItemPreset WHERE id = 5 AND userProfileId = 3'));

        // User id=2 has Notification id=1 (fk_Notification_userId does not cascade), so deletion fails initially
        $blockedDelete = $this->dispatch('post', 'user/delete/2');
        $blockedJson = json_decode($blockedDelete['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $blockedJson['status']);
        self::assertSame('Integrity constraint', $blockedJson['description']);
        self::assertSame(2, (int)$this->fetchValue('SELECT id FROM User WHERE id = 2'));

        // Once User's Notification rows are removed, user/delete/2 succeeds and cascades ItemPreset id=4
        $pdo->exec('DELETE FROM Notification WHERE userId = 2');
        $deleteUser = $this->dispatch('post', 'user/delete/2');
        $this->assertJsonOk($deleteUser, 'User deleted');
        self::assertFalse($pdo->query('SELECT id FROM User WHERE id = 2')->fetchColumn());
        self::assertFalse($pdo->query('SELECT id FROM ItemPreset WHERE id = 4')->fetchColumn());

        // Fixture UserProfile id=3 ('Usuarios') is not referenced by any fixture User row, so delete succeeds and cascades ItemPreset 5
        $deleteProfile = $this->dispatch('post', 'userProfile/delete/3');
        $this->assertJsonOk($deleteProfile, 'Profile deleted');
        self::assertFalse($pdo->query('SELECT id FROM UserProfile WHERE id = 3')->fetchColumn());
        self::assertFalse($pdo->query('SELECT id FROM ItemPreset WHERE id = 5')->fetchColumn());
    }

    /**
     * Characterization:
     * - UserGroupViewBase omits `disabled`/`readonly` on input elements in `userGroup/view/1`.
     * - UserProfileViewBase evaluates `$this->view->isView === true` (unset magic property) in `setViewDataForUserProfile()`,
     *   omitting `disabled`/`readonly` and leaving `usedBy` empty even for profile id=1 (used by 4 users).
     */
    #[Test]
    public function userGroupAndProfileViewRoutesOmitDisabledReadonlyAndProfileUsedBy(): void
    {
        $groupView = $this->dispatch('get', 'userGroup/view/1');
        $groupJson = $this->assertJsonOk($groupView);
        $groupHtml = $groupJson['data']['html'] ?? '';

        self::assertStringContainsString('Admins', $groupHtml);
        self::assertStringNotContainsString('disabled="disabled"', $groupHtml);
        self::assertStringNotContainsString('readonly="readonly"', $groupHtml);

        $profileView = $this->dispatch('get', 'userProfile/view/1');
        $profileJson = $this->assertJsonOk($profileView);
        $profileHtml = $profileJson['data']['html'] ?? '';

        self::assertStringContainsString('Admin', $profileHtml);
        self::assertStringNotContainsString('disabled="disabled"', $profileHtml);
        self::assertStringNotContainsString('readonly="readonly"', $profileHtml);
        self::assertMatchesRegularExpression('/<ul class="mdl-list">\s*<\/ul>/', $profileHtml);
    }

    #[Test]
    public function userGroupSaveCreateFailsOnDuplicateUserIdsAndNonNumericUserIds(): void
    {
        $pdo = getDbHandler()->getConnection();

        $dupResult = $this->dispatch('post', 'userGroup/saveCreate', [], [
            'name' => 'Dup Members Group',
            'description' => 'Should hit uk_UserToUserGroup_01',
            'users' => ['1', '1'],
        ]);

        $dupJson = json_decode($dupResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $dupJson['status']);
        self::assertSame('Integrity constraint', $dupJson['description']);
        self::assertFalse($pdo->query("SELECT id FROM UserGroup WHERE name = 'Dup Members Group'")->fetchColumn());

        $typeErrResult = $this->dispatch('post', 'userGroup/saveCreate', [], [
            'name' => 'String User Group',
            'description' => 'Non-numeric user id triggers TypeError in strict UserToUserGroup::add, wrapped in Rollback',
            'users' => ['abc'],
        ]);

        self::assertSame(200, $typeErrResult['code']);
        $typeErrJson = json_decode($typeErrResult['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $typeErrJson['status']);
        self::assertSame('Rollback', $typeErrJson['description']);
        self::assertFalse($pdo->query("SELECT id FROM UserGroup WHERE name = 'String User Group'")->fetchColumn());
    }

    #[Test]
    public function userSettingsGeneralPersistsArbitraryUnvalidatedThemeLangAndNegativeResultsPerPage(): void
    {
        $context = $this->getContext();

        $result = $this->dispatch(
            'post',
            'userSettingsGeneral/save',
            [],
            [
                'usertheme' => '../../nonexistent-theme',
                'userlang' => 'xx_INVALID',
                'resultsperpage' => -5,
            ],
            [
                Context::class => $context,
                SessionContext::class => $context,
            ]
        );

        $this->assertJsonOk($result, 'Preferences updated');

        $blob = (string)$this->fetchValue('SELECT preferences FROM User WHERE id = 1');
        $prefs = json_decode($blob, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('../../nonexistent-theme', $prefs['theme']);
        self::assertSame('xx_INVALID', $prefs['lang']);
        self::assertSame(-5, $prefs['resultsPerPage']);
    }

    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), $this->configOverrides);
    }
}
