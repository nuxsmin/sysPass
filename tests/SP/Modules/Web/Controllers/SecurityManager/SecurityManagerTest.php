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

namespace SP\Tests\Modules\Web\Controllers\SecurityManager;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Security\Ports\EventlogService;
use SP\Domain\Security\Ports\TrackService;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;

/**
 * Characterisation tests for the SecurityManager web area.
 */
#[Group('integration')]
final class SecurityManagerTest extends IntegrationTestCase
{
    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isLogEnabled'] = true;
        $configData['getAccountCount'] = 10;

        return $configData;
    }

    #[Test]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $eventlog = $this->createStub(EventlogService::class);
        $eventlog->method('search')->willReturn(new QueryResult());

        $track = $this->createStub(TrackService::class);
        $track->method('search')->willReturn(new QueryResult());

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'securityManager/index']),
            [
                EventlogService::class => $eventlog,
                TrackService::class => $track,
            ]
        );

        IntegrationTestCase::runApp($container);
    }

    private function outputCheckerIndex(string $output): void
    {
        self::assertStringContainsString('tblEventLog', $output);
        self::assertStringContainsString('tblTracks', $output);
    }
}
