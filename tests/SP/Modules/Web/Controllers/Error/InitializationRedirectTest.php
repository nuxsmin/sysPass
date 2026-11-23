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

namespace SP\Tests\Modules\Web\Controllers\Error;

use Klein\Response;
use Klein\Exceptions\LockedResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Tests\IntegrationTestCase;

#[Group('integration')]
final class InitializationRedirectTest extends IntegrationTestCase
{
    private bool $maintenance = false;
    private ?string $appVersion = null;
    private ?string $databaseVersion = null;
    private ?string $upgradeKey = null;

    protected function getConfigData(): array
    {
        $configData = array_merge(parent::getConfigData(), ['isMaintenance' => $this->maintenance]);

        if ($this->appVersion !== null) {
            $configData['getAppVersion'] = $this->appVersion;
        }

        if ($this->databaseVersion !== null) {
            $configData['getDatabaseVersion'] = $this->databaseVersion;
        }

        if ($this->upgradeKey !== null) {
            $configData['getUpgradeKey'] = $this->upgradeKey;
        }

        return $configData;
    }

    #[Test]
    public function maintenanceErrorRouteRendersMaintenancePageWithoutRedirecting(): void
    {
        $this->maintenance = true;
        $container = $this->buildContainer(self::buildRequest('get', 'index.php', ['r' => 'error/maintenanceError']));
        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertSame(200, $container->get(Response::class)->code());
        self::assertStringContainsString('Application on maintenance', $output);
    }

    public static function databaseFailures(): array
    {
        return [
            'connection' => [false, true, 'error/databaseConnection'],
            'tables' => [true, false, 'error/databaseError'],
        ];
    }

    #[Test]
    #[DataProvider('databaseFailures')]
    public function databaseErrorTargetRendersWhenFailurePersists(bool $connection, bool $tables, string $route): void
    {
        $database = $this->createStub(DatabaseUtilInterface::class);
        $database->method('checkDatabaseConnection')->willReturn($connection);
        $database->method('checkDatabaseTables')->willReturn($tables);
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => $route]),
            [DatabaseUtilInterface::class => $database]
        );
        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }
        self::assertSame(200, $container->get(Response::class)->code());
        self::assertStringContainsString(
            $connection ? 'Error while checking the database' : 'Unable to connect to DB',
            $output
        );
    }

    #[Test]
    #[DataProvider('databaseFailures')]
    public function databaseFailureSetsErrorRedirect(bool $connection, bool $tables, string $expectedRoute): void
    {
        $database = $this->createStub(DatabaseUtilInterface::class);
        $database->method('checkDatabaseConnection')->willReturn($connection);
        $database->method('checkDatabaseTables')->willReturn($tables);
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/search']),
            [DatabaseUtilInterface::class => $database]
        );

        ob_start();
        try {
            self::runApp($container);
            self::fail('Expected the in-process response to be locked');
        } catch (LockedResponseException) {
            // The initializer sends the redirect before bootstrap error handling completes.
        } finally {
            ob_end_clean();
        }

        $location = (string) $container->get(Response::class)->headers()->get('Location');
        self::assertStringContainsString(rawurlencode($expectedRoute), $location);
    }

    #[Test]
    public function maintenanceAjaxLockHolderBypassesMaintenanceRedirect(): void
    {
        $this->maintenance = true;

        $appLock = $this->createStub(\SP\Domain\Core\Ports\AppLockHandler::class);
        $appLock->method('getLock')->willReturn($this->getUserDataDto()->id);

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => 'bootstrap/getEnvironment', 'isAjax' => '1'],
                [],
                [],
                ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
            ),
            [\SP\Domain\Core\Ports\AppLockHandler::class => $appLock]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertSame(200, $container->get(Response::class)->code());
        $decoded = json_decode($output, true);
        self::assertIsArray($decoded);
        self::assertSame('OK', $decoded['status'] ?? null);
    }

    #[Test]
    public function maintenanceMismatchLockOrNonAjaxRedirectsToMaintenanceError(): void
    {
        $this->maintenance = true;

        // Mismatched lock userId even on AJAX request
        $mismatchLock = $this->createStub(\SP\Domain\Core\Ports\AppLockHandler::class);
        $mismatchLock->method('getLock')->willReturn($this->getUserDataDto()->id + 99);

        $ajaxContainer = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/search', 'isAjax' => '1'],
                [],
                [],
                ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
            ),
            [\SP\Domain\Core\Ports\AppLockHandler::class => $mismatchLock]
        );

        ob_start();
        try {
            self::runApp($ajaxContainer);
            self::fail('Expected LockedResponseException for mismatched lock user on AJAX request');
        } catch (LockedResponseException) {
            // Expected
        } finally {
            ob_end_clean();
        }
        self::assertStringContainsString(
            rawurlencode('error/maintenanceError'),
            (string) $ajaxContainer->get(Response::class)->headers()->get('Location')
        );

        // Matching lock userId on NON-AJAX request
        $matchingLock = $this->createStub(\SP\Domain\Core\Ports\AppLockHandler::class);
        $matchingLock->method('getLock')->willReturn($this->getUserDataDto()->id);

        $nonAjaxContainer = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/search']),
            [\SP\Domain\Core\Ports\AppLockHandler::class => $matchingLock]
        );

        ob_start();
        try {
            self::runApp($nonAjaxContainer);
            self::fail('Expected LockedResponseException for non-AJAX request');
        } catch (LockedResponseException) {
            // Expected
        } finally {
            ob_end_clean();
        }
        self::assertStringContainsString(
            rawurlencode('error/maintenanceError'),
            (string) $nonAjaxContainer->get(Response::class)->headers()->get('Location')
        );
    }

    #[Test]
    public function outdatedVersionGeneratesUpgradeKeyAndRedirectsToUpgrade(): void
    {
        $configData = self::createConfiguredStub(
            \SP\Domain\Config\Ports\ConfigDataInterface::class,
            array_merge(
                parent::getConfigData(),
                [
                    'getAppVersion' => '3211.22070201',
                    'getDatabaseVersion' => '3211.22070201',
                    'getUpgradeKey' => null,
                ]
            )
        );

        $configFileService = $this->createMock(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);
        $configFileService->expects(self::once())->method('generateUpgradeKey')->willReturnSelf();

        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/search']),
            [\SP\Domain\Config\Ports\ConfigFileService::class => $configFileService]
        );

        ob_start();
        try {
            self::runApp($container);
            self::fail('Expected the in-process response to be locked');
        } catch (LockedResponseException) {
            // Expected redirect to upgrade
        } finally {
            ob_end_clean();
        }

        $location = (string) $container->get(Response::class)->headers()->get('Location');
        self::assertStringContainsString(rawurlencode('upgrade'), $location);
    }

    #[Test]
    public function pendingUpgradeKeyRedirectsToUpgradeEvenWhenVersionsMatch(): void
    {
        $this->appVersion = \SP\Domain\Common\Providers\Version::getVersionStringNormalized();
        $this->databaseVersion = \SP\Domain\Common\Providers\Version::getVersionStringNormalized();
        $this->upgradeKey = 'pending-upgrade-key';

        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/search'])
        );

        ob_start();
        try {
            self::runApp($container);
            self::fail('Expected the in-process response to be locked');
        } catch (LockedResponseException) {
            // Expected redirect to upgrade
        } finally {
            ob_end_clean();
        }

        $location = (string) $container->get(Response::class)->headers()->get('Location');
        self::assertStringContainsString(rawurlencode('upgrade'), $location);
    }

    #[Test]
    public function upgradeIndexRouteRendersWithoutRedirectWhenUpgradeNeeded(): void
    {
        $this->appVersion = '3211.22070201';
        $this->databaseVersion = '3211.22070201';

        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'upgrade/index'])
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertSame(200, $container->get(Response::class)->code());
        self::assertStringContainsString('id="frmUpgrade"', $output);
    }
}
