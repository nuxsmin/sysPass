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

namespace SP\Tests\Modules\Web\Controllers\Install;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmInstall"]'));
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
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route] + $paramsGet, $paramsPost),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
