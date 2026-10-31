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

namespace SP\Modules\Web\Controllers\Plugin;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\PluginGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the plugin area.
 */
#[Group('integration')]
final class PluginTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'plugin/index');
    }

    #[Test]
    #[BodyChecker('checkSearch')]
    public function search(): void
    {
        $this->runRoute('get', 'plugin/search');
    }

    #[Test]
    #[BodyChecker('checkEnabled')]
    public function enable(): void
    {
        $this->runRoute('get', 'plugin/enable/100', $this->managerStub());
    }

    #[Test]
    #[BodyChecker('checkDisabled')]
    public function disable(): void
    {
        $this->runRoute('get', 'plugin/disable/100', $this->managerStub());
    }

    #[Test]
    #[BodyChecker('checkView')]
    public function view(): void
    {
        $manager = $this->createStub(PluginManagerService::class);
        $manager->method('getById')->willReturn(PluginGenerator::factory()->buildPlugin());

        $this->runRoute('get', 'plugin/view/100', [PluginManagerService::class => $manager]);
    }

    #[Test]
    #[BodyChecker('checkReset')]
    public function reset(): void
    {
        $manager = $this->createStub(PluginManagerService::class);
        $manager->method('getById')->willReturn(PluginGenerator::factory()->buildPlugin());

        $this->runRoute(
            'get',
            'plugin/reset/100',
            [
                PluginManagerService::class => $manager,
                PluginDataService::class => $this->createStub(PluginDataService::class),
            ]
        );
    }

    #[Test]
    #[BodyChecker('checkDeleted')]
    public function deleteSingle(): void
    {
        $this->runRoute('get', 'plugin/delete/100', $this->managerStub());
    }

    #[Test]
    #[BodyChecker('checkDeletedMultiple')]
    public function deleteMultiple(): void
    {
        $this->runRoute(
            'get',
            'plugin/delete',
            $this->managerStub(),
            ['items' => [100, 200]]
        );
    }

    public function checkIndex(string $output): void
    {
        self::assertStringContainsString('Plugins', $output);
    }

    public function checkSearch(string $output): void
    {
        self::assertSame('OK', $this->decode($output)['status']);
    }

    public function checkEnabled(string $output): void
    {
        self::assertSame('Plugin enabled', $this->decode($output)['description']);
    }

    public function checkDisabled(string $output): void
    {
        self::assertSame('Plugin disabled', $this->decode($output)['description']);
    }

    public function checkView(string $output): void
    {
        $response = $this->decode($output);

        self::assertSame('OK', $response['status']);
        self::assertStringContainsString('frmPlugins', $response['data']['html']);
    }

    public function checkReset(string $output): void
    {
        self::assertSame('Plugin reset', $this->decode($output)['description']);
    }

    public function checkDeleted(string $output): void
    {
        self::assertSame('Plugin deleted', $this->decode($output)['description']);
    }

    public function checkDeletedMultiple(string $output): void
    {
        self::assertSame('Plugins deleted', $this->decode($output)['description']);
    }

    private function decode(string $output): array
    {
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            self::fail('Invalid JSON: '.substr($output, 0, 400));
        }

        return $decoded;
    }

    private function managerStub(): array
    {
        return [PluginManagerService::class => $this->createStub(PluginManagerService::class)];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $method, string $route, array $definitions = [], array $params = []): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', array_merge(['r' => $route], $params)),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
