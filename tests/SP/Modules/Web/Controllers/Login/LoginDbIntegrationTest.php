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

namespace SP\Tests\Modules\Web\Controllers\Login;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Auth\Services\Login as RealLoginService;
use SP\Domain\Auth\Services\LoginAuthHandler;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\User as UserModel;
use SP\Infrastructure\Security\Repositories\Track as TrackRepository;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for Web login tracking, disabled-user sessions,
 * and forced password-change persistence against MariaDB.
 */
#[Group('integration')]
final class LoginDbIntegrationTest extends DatabaseIntegrationTestCase
{
    protected static function buildRequest(
        string $method,
        string $uri,
        array $paramsGet = [],
        array $paramsPost = [],
        array $files = [],
        array $server = []
    ): \Klein\Request {
        return parent::buildRequest(
            $method,
            $uri,
            $paramsGet,
            $paramsPost,
            $files,
            array_merge(['REMOTE_ADDR' => '127.0.0.1'], $server)
        );
    }

    /**
     * Characterization of TrackRepository::getTracksForClientFromTime() LIMIT 1 bug and
     * LoginBase static::class source-bucket splitting:
     * 1. 11 blank-credential login attempts persist 11 rows under source=Login::class, yet
     *    getTracksForClientFromTime() caps results with LIMIT 1, so checkTracking() returns false.
     * 2. Wrong-password attempts persist rows under source=LoginAuthHandler::class (not Login::class),
     *    meaning wrong passwords never accumulate in the bucket checked by Login::checkTracking().
     */
    #[Test]
    public function repeatedFailedLoginsPersistTracksInDbWithoutTriggeringLimitOneThrottle(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE Track');

        $validHash = Hash::hashKey('synthetic-correct-pass-2026');
        $stmt = $pdo->prepare('UPDATE User SET pass = :pass, isDisabled = 0, isChangePass = 0, isMigrate = 0 WHERE id = 1');
        $stmt->execute(['pass' => $validHash]);

        for ($i = 0; $i < 11; $i++) {
            $result = $this->dispatch('post', 'login/login', [], ['user' => '', 'pass' => '']);
            $this->assertJsonDenied($result, 'Wrong login');
        }

        for ($i = 0; $i < 3; $i++) {
            $result = $this->dispatch('post', 'login/login', [], [
                'user' => 'admin',
                'pass' => 'synthetic-wrong-pass-' . $i,
            ]);
            $this->assertJsonDenied($result, 'Wrong login');
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM Track WHERE source = :source');

        $countStmt->execute(['source' => RealLoginService::class]);
        self::assertSame(11, (int)$countStmt->fetchColumn());

        $countStmt->execute(['source' => LoginAuthHandler::class]);
        self::assertSame(3, (int)$countStmt->fetchColumn());

        $trackRepo = new TrackRepository(
            $this->db,
            $this->dbContext,
            self::createStub(EventDispatcherInterface::class),
            $this->queryFactory
        );
        $trackRow = new \SP\Domain\Security\Models\Track([
            'ipv4' => (new TrackRequest(time() - 600, RealLoginService::class, '127.0.0.1'))->getIpv4(),
            'ipv6' => (new TrackRequest(time() - 600, RealLoginService::class, '127.0.0.1'))->getIpv6(),
            'source' => RealLoginService::class,
            'time' => time() - 600,
        ]);

        // LIMIT 1 in TrackRepository::getTracksForClientFromTime() caps numRows at 1 instead of 11
        self::assertSame(1, $trackRepo->getTracksForClientFromTime($trackRow)->getNumRows());
    }

    /**
     * Characterization:
     * 1. Active Web sessions do not re-check User.isDisabled in MariaDB on protected routes.
     * 2. Forced password change (isChangePass=1) creates a persistent UserPassRecover row on login.
     */
    #[Test]
    public function disabledUserActiveSessionSucceedsAndChangePassPersistsRecoverToken(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE UserPassRecover');
        $pdo->exec('TRUNCATE TABLE PublicLink');

        // Seed synthetic disabled non-admin user (id inserted dynamically)
        $disabledLogin = 'synthetic_disabled_' . bin2hex(random_bytes(4));
        $insert = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, 0, 0, 1, :salt, 0, 0, 0, 0)'
        );
        $insert->execute([
            'name' => 'Synthetic Disabled',
            'login' => $disabledLogin,
            'pass' => Hash::hashKey('synthetic-disabled-pass'),
            'email' => $disabledLogin . '@example.test',
            'salt' => sha1('salt'),
        ]);
        $disabledId = (int)$pdo->lastInsertId();

        $disabledDto = UserDto::fromModel(new UserModel([
            'id' => $disabledId,
            'userGroupId' => 1,
            'userProfileId' => 1,
            'login' => $disabledLogin,
            'ssoLogin' => $disabledLogin,
            'name' => 'Synthetic Disabled',
            'email' => $disabledLogin . '@example.test',
            'isAdminApp' => false,
            'isAdminAcc' => false,
            'isDisabled' => true,
            'preferences' => serialize(UserDataGenerator::factory()->buildUserPreferencesData()),
        ]));

        $disabledContext = self::createStub(SessionContext::class);
        $disabledContext->method('isLoggedIn')->willReturn(true);
        $disabledContext->method('getAuthCompleted')->willReturn(true);
        $disabledContext->method('getUserData')->willReturn($disabledDto);
        $disabledContext->method('getUserProfile')->willReturn($this->getUserProfile());

        // Characterization: session-holding disabled user still dispatches protected routes
        $accountSearch = $this->dispatch('get', 'account/search', [], [], [Context::class => $disabledContext]);
        $this->assertJsonOk($accountSearch);

        $publicLinkSearch = $this->dispatch('get', 'publicLink/search', [], [], [Context::class => $disabledContext]);
        $this->assertJsonOk($publicLinkSearch);

        // Now verify real login against MariaDB for a user with isChangePass = 1
        $changeLogin = 'synthetic_chg_' . bin2hex(random_bytes(4));
        $insert->execute([
            'name' => 'Synthetic ChangePass',
            'login' => $changeLogin,
            'pass' => Hash::hashKey('synthetic-chg-pass'),
            'email' => $changeLogin . '@example.test',
            'salt' => sha1('salt'),
        ]);
        $changeId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE User SET isDisabled = 0, isChangePass = 1 WHERE id = :id')->execute(['id' => $changeId]);

        $loginResult = $this->dispatch('post', 'login/login', [], [
            'user' => $changeLogin,
            'pass' => 'synthetic-chg-pass',
        ]);
        $json = $this->assertJsonOk($loginResult);
        self::assertMatchesRegularExpression('#^index\.php\?r=userPassReset%2Freset%2F([0-9a-f]{32})$#', $json['data']['url']);

        preg_match('/([0-9a-f]{32})$/', $json['data']['url'], $matches);
        $row = $pdo->prepare('SELECT userId, hash, used FROM UserPassRecover WHERE userId = :id');
        $row->execute(['id' => $changeId]);
        $recoverRow = $row->fetch(PDO::FETCH_ASSOC);

        self::assertIsArray($recoverRow);
        self::assertSame($changeId, (int)$recoverRow['userId']);
        self::assertSame($matches[1], $recoverRow['hash']);
        self::assertSame(0, (int)$recoverRow['used']);
    }
}
