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

namespace SP\Tests\Modules\Web\Controllers\Install;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Ports\InstallerService;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the Install web area.
 */
#[Group('integration')]
final class InstallTest extends IntegrationTestCase
{
    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isInstalled'] = false;
        $configData['getSiteLang'] = 'en_US';

        return $configData;
    }

    #[Test]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'install/index', ['skipInstalled' => 1]);
    }

    #[Test]
    #[BodyChecker('outputCheckerInstall')]
    public function install(): void
    {
        $installer = $this->createStub(InstallerService::class);

        $this->runRoute(
            'post',
            'install/install',
            [],
            [
                'sitelang' => 'en_US',
                'adminlogin' => 'admin',
                'adminpass' => 'secret',
                'masterpassword' => 'master',
                'dbuser' => 'root',
                'dbpass' => 'secret',
                'dbname' => 'syspass',
                'dbhost' => 'localhost',
            ],
            [InstallerService::class => $installer]
        );
    }

    /**
     * The controller must use the request-built InstallData from the shared
     * container instead of rebuilding a second object. The same values are what
     * DatabaseSetupService receives when the real installer runs.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function installPassesTheSharedRequestValuesToTheInstaller(): void
    {
        $captured = null;
        $installer = $this->createMock(InstallerService::class);
        $installer->expects(self::once())
                  ->method('run')
                  ->willReturnCallback(
                      static function (InstallData $installData) use (&$captured, $installer): InstallerService {
                          $captured = $installData;

                          return $installer;
                      }
                  );

        $container = $this->runRoute(
            'post',
            'install/install',
            [],
            [
                'sitelang' => 'es_ES',
                'adminlogin' => 'install-admin',
                'adminpass' => 'install-admin-pass',
                'masterpassword' => 'install-master-password',
                'dbuser' => 'install-db-user',
                'dbpass' => 'install-db-pass',
                'dbname' => 'install_db_name',
                'dbhost' => 'install-db-host',
                'hostingmode' => '1',
            ],
            [InstallerService::class => $installer]
        );

        self::assertInstanceOf(InstallData::class, $captured);
        self::assertSame($container->get(InstallData::class), $captured);
        self::assertSame('install-admin', $captured->getAdminLogin());
        self::assertSame('install-admin-pass', $captured->getAdminPass());
        self::assertSame('install-master-password', $captured->getMasterPassword());
        self::assertSame('install-db-user', $captured->getDbAdminUser());
        self::assertSame('install-db-pass', $captured->getDbAdminPass());
        self::assertSame('install_db_name', $captured->getDbName());
        self::assertSame('install-db-host', $captured->getDbHost());
        self::assertSame('es_ES', $captured->getSiteLang());
        self::assertTrue($captured->isHostingMode());
    }

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmInstall"]'));

        // The browser validates these patterns with the `v` flag. The hyphen
        // and dot must stay escaped inside the character classes or the page
        // emits a console error and the installer never becomes ready.
        self::assertSame(
            '[0-9a-zA-Z_$\-]+',
            $crawler->filterXPath('//input[@name="dbname"]')->attr('pattern')
        );
        self::assertSame(
            '([0-9a-zA-Z._\-]+|([0-9]{1,3}\.)+)(:[0-9]{1,5})?|unix:.*',
            $crawler->filterXPath('//input[@name="dbhost"]')->attr('pattern')
        );
    }

    private function outputCheckerInstall(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertSame('Installation finished', $json['description']);
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
    ): ContainerInterface {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route] + $paramsGet, $paramsPost),
            $definitions
        );

        IntegrationTestCase::runApp($container);

        return $container;
    }

    #[Test]
    public function installWhenAlreadyInstalledDispatchesToInstallerWithoutControllerGuard(): void
    {
        $installedConfig = self::createConfiguredStub(
            \SP\Domain\Config\Ports\ConfigDataInterface::class,
            array_merge(parent::getConfigData(), ['isInstalled' => true, 'getSiteLang' => 'en_US'])
        );
        $configService = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configService->method('getConfigData')->willReturn($installedConfig);

        $installer = $this->createMock(InstallerService::class);
        $installer->expects(self::once())->method('run');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'install/install'],
                [
                    'sitelang' => 'en_US',
                    'adminlogin' => 'admin',
                    'adminpass' => 'synthetic-secret',
                    'masterpassword' => 'synthetic-master',
                    'dbuser' => 'root',
                    'dbpass' => 'synthetic-dbpass',
                    'dbname' => 'syspass_test',
                    'dbhost' => '127.0.0.1',
                ]
            ),
            [
                \SP\Domain\Config\Ports\ConfigFileService::class => $configService,
                \SP\Domain\Config\Ports\ConfigDataInterface::class => $installedConfig,
                InstallerService::class => $installer,
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
        self::assertSame('Installation finished', $json['description']);
    }

    #[Test]
    public function indexResolvesLanguageAndAccessibleHintsOnFreshInstall(): void
    {
        $freshConfig = self::createConfiguredStub(
            \SP\Domain\Config\Ports\ConfigDataInterface::class,
            array_merge(parent::getConfigData(), ['isInstalled' => false, 'getSiteLang' => null])
        );
        $configService = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
        $configService->method('getConfigData')->willReturn($freshConfig);

        $storedLocale = '';
        $context = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getUserData')->willReturn(new \SP\Domain\User\Dtos\UserDto());
        $context->method('setLocale')->willReturnCallback(
            static function (string $locale) use (&$storedLocale): void {
                $storedLocale = $locale;
            }
        );
        $context->method('getLocale')->willReturnCallback(
            static function () use (&$storedLocale): string {
                return $storedLocale;
            }
        );

        $outputs = [];

        // Run twice in the same session to verify non-reload requests preserve <html lang="...">
        for ($i = 0; $i < 2; $i++) {
            $container = $this->buildContainer(
                IntegrationTestCase::buildRequest(
                    'get',
                    'index.php',
                    ['r' => 'install/index'],
                    [],
                    [],
                    ['HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.9']
                ),
                [
                    \SP\Domain\Config\Ports\ConfigFileService::class => $configService,
                    \SP\Domain\Config\Ports\ConfigDataInterface::class => $freshConfig,
                    \SP\Domain\Core\Context\Context::class => $context,
                ]
            );

            ob_start();
            try {
                IntegrationTestCase::runApp($container);
            } finally {
                $outputs[] = (string) ob_get_clean();
            }
        }

        foreach ($outputs as $output) {
            $crawler = new Crawler($output);

            self::assertSame('en', $crawler->filterXPath('//html')->attr('lang'));
            self::assertSame(
                'en_US',
                $crawler->filterXPath('//select[@id="sel-sitelang"]/option[@selected]')->attr('value')
            );
            self::assertSame(
                'sitelang-hint',
                $crawler->filterXPath('//select[@id="sel-sitelang"]')->attr('aria-describedby')
            );
            self::assertCount(1, $crawler->filterXPath('//p[@id="sitelang-hint"]'));
            self::assertSame(
                'masterpasswordR-error',
                $crawler->filterXPath('//input[@id="masterpasswordR"]')->attr('aria-describedby')
            );
            self::assertCount(1, $crawler->filterXPath('//div[@id="masterpasswordR-error"]'));
        }
    }
}
