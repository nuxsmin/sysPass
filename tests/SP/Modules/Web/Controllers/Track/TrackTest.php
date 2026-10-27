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

namespace SP\Modules\Web\Controllers\Track;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Security\Ports\TrackService;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the track area.
 */
#[Group('integration')]
final class TrackTest extends IntegrationTestCase
{
    #[Test]
    #[BodyChecker('checkSearch')]
    public function search(): void
    {
        $this->runRoute('get', 'track/search');
    }

    #[Test]
    #[BodyChecker('checkClear')]
    public function clear(): void
    {
        $this->runRoute('get', 'track/clear');
    }

    #[Test]
    #[BodyChecker('checkUnlock')]
    public function unlock(): void
    {
        $trackService = self::createStub(TrackService::class);

        $this->runRoute('get', 'track/unlock/100', [TrackService::class => $trackService]);
    }

    public function checkSearch(string $output): void
    {
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK', $response['status']);
        self::assertArrayHasKey('html', $response['data']);
    }

    public function checkClear(string $output): void
    {
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK', $response['status']);
        self::assertSame('Tracks cleared out', $response['description']);
    }

    public function checkUnlock(string $output): void
    {
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK', $response['status']);
        self::assertSame('Track unlocked', $response['description']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(string $method, string $route, array $definitions = []): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route]),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
