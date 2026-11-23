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

namespace SP\Tests\Modules\Web\Controllers\Upgrade;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Upgrade\Ports\UpgradeService;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the Upgrade web area.
 */
#[Group('integration')]
final class UpgradeTest extends IntegrationTestCase
{
    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['getAppVersion'] = '3.2';
        $configData['getUpgradeKey'] = 'upgrade-key';

        return $configData;
    }

    #[Test]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $this->runRoute(
            'get',
            'upgrade/index',
            [],
            [],
            [ActionsInterface::class => $this->createStub(ActionsInterface::class)]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerUpgrade')]
    public function upgrade(): void
    {
        $this->runRoute(
            'post',
            'upgrade/upgrade',
            [],
            ['chkConfirm' => 1, 'key' => 'upgrade-key'],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerWrongKey')]
    public function wrongKey(): void
    {
        $this->runRoute(
            'post',
            'upgrade/upgrade',
            [],
            ['chkConfirm' => 1, 'key' => 'wrong'],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerNotConfirmed')]
    public function notConfirmed(): void
    {
        $this->runRoute(
            'post',
            'upgrade/upgrade',
            [],
            ['key' => 'upgrade-key'],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmUpgrade" and @method="post"]'));
    }

    private function outputCheckerUpgrade(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertSame('Application successfully updated', $json['description']);
    }

    private function outputCheckerWrongKey(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong security code', $json['description']);
    }

    private function outputCheckerNotConfirmed(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('The updating need to be confirmed', $json['description']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(
        string $method,
        string $route,
        array $paramsGet = [],
        array $paramsPost = [],
        array $definitions = []
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route] + $paramsGet, $paramsPost),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    public function omittedKeyWhenConfiguredKeyIsSetFailsWrongSecurityCode(): void
    {
        $upgradeService = $this->createMock(UpgradeService::class);
        $upgradeService->expects(self::never())->method('upgrade');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'upgrade/upgrade'], ['chkConfirm' => 1]),
            [UpgradeService::class => $upgradeService]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong security code', $json['description']);
    }

    #[Test]
    public function getRequestIsRejectedWithoutRunningUpgrade(): void
    {
        $upgradeService = $this->createMock(UpgradeService::class);
        $upgradeService->expects(self::never())->method('upgrade');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'upgrade/upgrade', 'chkConfirm' => 1, 'key' => 'upgrade-key']
            ),
            [UpgradeService::class => $upgradeService]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Invalid request method', $json['description']);
    }

    public static function invalidConfiguredUpgradeKeyProvider(): array
    {
        return [
            'null key' => [null, null],
            'empty key' => ['', ''],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConfiguredUpgradeKeyProvider')]
    public function emptyOrNullConfiguredUpgradeKeyRejectsUpgrade(?string $configuredKey, ?string $submittedKey): void
    {
        $configData = new \SP\Domain\Config\Adapters\ConfigData();
        $configData->setInstalled(true);
        $configData->setAppVersion('3.2');
        $configData->setUpgradeKey($configuredKey);
        $configData->setPasswordSalt($this->passwordSalt);

        $configService = $this->createMock(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configService->method('getConfigData')->willReturn($configData);
        $configService->expects(self::never())->method('save');

        $upgradeService = $this->createMock(UpgradeService::class);
        $upgradeService->expects(self::never())->method('upgrade');

        $paramsPost = ['chkConfirm' => 1];

        if ($submittedKey !== null) {
            $paramsPost['key'] = $submittedKey;
        }

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'upgrade/upgrade'], $paramsPost),
            [
                \SP\Domain\Config\Ports\ConfigFileService::class => $configService,
                \SP\Domain\Config\Ports\ConfigDataInterface::class => $configData,
                UpgradeService::class => $upgradeService,
            ]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong security code', $json['description']);
    }

    #[Test]
    public function upgradeFallsBackToDatabaseVersionWhenAppVersionIsEmptyAndClearsUpgradeKey(): void
    {
        $configData = new \SP\Domain\Config\Adapters\ConfigData();
        $configData->setInstalled(true);
        $configData->setAppVersion('');
        $configData->setDatabaseVersion('3211.22070201');
        $configData->setConfigVersion('3211.22070201');
        $configData->setUpgradeKey('upgrade-key');
        $configData->setPasswordSalt($this->passwordSalt);

        $configService = $this->createMock(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configService->method('getConfigData')->willReturn($configData);
        $configService->expects(self::once())->method('save')->with($configData);

        $upgradeService = $this->createMock(UpgradeService::class);
        $upgradeService->expects(self::once())->method('upgrade')->with('3211.22070201', $configData);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'upgrade/upgrade'],
                ['chkConfirm' => 1, 'key' => 'upgrade-key']
            ),
            [
                \SP\Domain\Config\Ports\ConfigFileService::class => $configService,
                \SP\Domain\Config\Ports\ConfigDataInterface::class => $configData,
                UpgradeService::class => $upgradeService,
            ]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $json['status']);
        self::assertNull($configData->getUpgradeKey());
    }

    #[Test]
    public function upgradeLockContentionReturnsErrorWithoutClearingUpgradeKey(): void
    {
        $configData = new \SP\Domain\Config\Adapters\ConfigData();
        $configData->setInstalled(true);
        $configData->setAppVersion('3.2');
        $configData->setUpgradeKey('upgrade-key');
        $configData->setPasswordSalt($this->passwordSalt);

        $configService = $this->createMock(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configService->method('getConfigData')->willReturn($configData);
        $configService->expects(self::never())->method('save');

        $appLock = $this->createStub(\SP\Domain\Core\Ports\AppLockHandler::class);
        $appLock->method('tryLock')->willReturn(false);

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'upgrade/upgrade'],
                ['chkConfirm' => 1, 'key' => 'upgrade-key']
            ),
            [
                \SP\Domain\Config\Ports\ConfigFileService::class => $configService,
                \SP\Domain\Config\Ports\ConfigDataInterface::class => $configData,
                \SP\Domain\Core\Ports\AppLockHandler::class => $appLock,
            ]
        );

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
        } finally {
            $output = (string) ob_get_clean();
        }

        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Another operation is in progress', $json['description']);
        self::assertSame('upgrade-key', $configData->getUpgradeKey());
    }
}
