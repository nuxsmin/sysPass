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

namespace SP\Tests\Modules\Web\Controllers\SecurityManager;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Services\Track as TrackService;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Security\Repositories\Track as TrackRepository;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

/**
 * DB-backed HTTP & service characterization tests for Eventlog, Track, and Plugin controllers:
 * - Eventlog\ClearController deleting all 14 EventLog rows for a blank-profile user (missing ACL check)
 * - Track\UnlockController, Plugin\EnableController, Plugin\DisableController, and single Plugin\DeleteController
 *   crashing HTTP 500 JSON via `Common\Update::limit(1)` / `Common\Delete::limit(1)` `Error`
 * - TrackRepository::getTracksForClientFromTime() `->limit(1)` capping attempt count at 1 and disabling lockout delay
 * - Plugin\ResetController deleting `PluginData` while leaving `Plugin.data` untouched, and non-transactional `plugin/delete` batch
 */
#[Group('integration')]
final class SecurityAndPluginDbIntegrationTest extends DatabaseIntegrationTestCase
{
    #[Test]
    public function eventlogClearDeletesAllRowsEvenForBlankProfileUser(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::assertSame(14, (int)$pdo->query('SELECT COUNT(*) FROM EventLog')->fetchColumn());

        $blankContext = $this->createStub(SessionContext::class);
        $blankContext->method('isLoggedIn')->willReturn(true);
        $blankContext->method('getAuthCompleted')->willReturn(true);
        $blankContext->method('getUserData')->willReturn($this->getUserDataDto());
        $blankContext->method('getUserProfile')->willReturn(new ProfileData());
        $blankContext->method('getCSRF')->willReturn(null);

        $result = $this->dispatch(
            'post',
            'eventlog/clear',
            [],
            [],
            [
                Context::class => $blankContext,
                SessionContext::class => $blankContext,
            ]
        );

        $this->assertJsonOk($result, 'Event log cleared');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM EventLog')->fetchColumn());
    }

    #[Test]
    public function trackUnlockCrashes500ViaUpdateLimitAndCheckTrackingIsCappedAtOneRow(): void
    {
        $pdo = getDbHandler()->getConnection();

        $unlockResult = $this->dispatch('post', 'track/unlock/1');
        self::assertSame(500, $unlockResult['code']);
        $unlockJson = $this->decodeJson($unlockResult['body']);
        self::assertSame('ERROR', $unlockJson['status']);
        self::assertSame('Internal error', $unlockJson['description']);
        self::assertSame(
            0,
            (int)$pdo->query('SELECT COUNT(*) FROM Track WHERE timeUnlock IS NOT NULL')->fetchColumn()
        );

        // Seed 12 recent Track rows matching source='login' and client IP 192.0.2.10
        $repo = new TrackRepository(
            $this->db,
            $this->dbContext,
            self::createStub(EventDispatcherInterface::class),
            new QueryFactory('mysql')
        );
        $requestStub = $this->createStub(RequestService::class);
        $requestStub->method('getClientAddress')->willReturn('192.0.2.10');

        $trackService = new TrackService(
            $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class),
            self::createStub(EventDispatcherInterface::class),
            $this->dbContext,
            $repo,
            $requestStub
        );

        $now = time();
        for ($i = 0; $i < 12; $i++) {
            $trackService->add(new TrackRequest($now, 'login', '192.0.2.10'));
        }

        $trackRequest = $trackService->buildTrackRequest('login');
        $trackModel = new \SP\Domain\Security\Models\Track([
            'ipv4' => $trackRequest->getIpv4(),
            'ipv6' => $trackRequest->getIpv6(),
            'source' => $trackRequest->getSource(),
            'time' => $trackRequest->getTime(),
        ]);
        self::assertSame(1, $repo->getTracksForClientFromTime($trackModel)->getNumRows());
        self::assertFalse($trackService->checkTracking($trackRequest));
    }

    #[Test]
    public function pluginEnableDisableSingleDeleteCrash500WhileResetAndBatchDeleteCharacterizeDbState(): void
    {
        $pdo = getDbHandler()->getConnection();

        // 1. Enable/Disable/Single-Delete all call Update::limit(1) / Delete::limit(1) on autowired QueryFactory::COMMON -> Error -> HTTP 500 JSON
        foreach (['plugin/enable/1', 'plugin/disable/3', 'plugin/delete/1'] as $route) {
            $res = $this->dispatch('post', $route);
            self::assertSame(500, $res['code'], $route);
            $json = $this->decodeJson($res['body']);
            self::assertSame('ERROR', $json['status'], $route);
            self::assertSame('Internal error', $json['description'], $route);
        }
        self::assertSame(0, (int)$this->fetchValue('SELECT enabled FROM Plugin WHERE id = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT enabled FROM Plugin WHERE id = 3'));
        self::assertNotFalse($pdo->query('SELECT id FROM Plugin WHERE id = 1')->fetchColumn());

        // 2. Reset plugin 2 ('XML Exporter', Plugin.data='data', PluginData row exists) -> deletes PluginData, keeps Plugin.data!
        self::assertSame(1, (int)$pdo->query("SELECT COUNT(*) FROM PluginData WHERE name = 'XML Exporter'")->fetchColumn());
        $reset2 = $this->dispatch('post', 'plugin/reset/2');
        $this->assertJsonOk($reset2, 'Plugin reset');
        self::assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM PluginData WHERE name = 'XML Exporter'")->fetchColumn());
        self::assertSame('data', $this->fetchValue('SELECT data FROM Plugin WHERE id = 2'));

        // Second reset finds 0 rows in PluginData -> NoSuchItemException error
        $reset2Again = $this->dispatch('post', 'plugin/reset/2');
        self::assertSame(200, $reset2Again['code']);
        $resetAgainJson = $this->decodeJson($reset2Again['body']);
        self::assertSame('ERROR', $resetAgainJson['status']);

        // 3. Batch delete items=[1, 9999] (no limit(1)) -> deletes plugin 1 permanently without rollback before throwing affected !== count(ids)
        $batchDelete = $this->dispatch('post', 'plugin/delete', [], ['items' => ['1', '9999']]);
        self::assertSame(200, $batchDelete['code']);
        $batchJson = $this->decodeJson($batchDelete['body']);
        self::assertSame('ERROR', $batchJson['status']);
        self::assertSame('Error while deleting the plugins', $batchJson['description']);
        self::assertFalse($pdo->query('SELECT id FROM Plugin WHERE id = 1')->fetchColumn());
    }
}
