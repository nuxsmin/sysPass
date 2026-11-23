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

namespace SP\Modules\Web\Controllers\Eventlog;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the event log area.
 */
#[Group('integration')]
final class EventlogTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'eventlog/index');
    }

    #[Test]
    #[BodyChecker('checkSearch')]
    public function search(): void
    {
        $this->runRoute('get', 'eventlog/search');
    }

    #[Test]
    #[BodyChecker('checkClear')]
    public function clear(): void
    {
        $this->runRoute('get', 'eventlog/clear');
    }

    public function checkIndex(string $output): void
    {
        self::assertStringContainsString('Event Log', $output);
    }

    public function checkSearch(string $output): void
    {
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK', $response['status']);
        self::assertNotEmpty($response['data']['html']);
    }

    public function checkClear(string $output): void
    {
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK', $response['status']);
        self::assertSame('Event log cleared', $response['description']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $method, string $route): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route])
        );

        IntegrationTestCase::runApp($container);
    }
}
