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
            'get',
            'upgrade/upgrade',
            ['chkConfirm' => 1, 'key' => 'upgrade-key'],
            [],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerWrongKey')]
    public function wrongKey(): void
    {
        $this->runRoute(
            'get',
            'upgrade/upgrade',
            ['chkConfirm' => 1, 'key' => 'wrong'],
            [],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerNotConfirmed')]
    public function notConfirmed(): void
    {
        $this->runRoute(
            'get',
            'upgrade/upgrade',
            ['key' => 'upgrade-key'],
            [],
            [UpgradeService::class => $this->createStub(UpgradeService::class)]
        );
    }

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmUpgrade"]'));
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
}
