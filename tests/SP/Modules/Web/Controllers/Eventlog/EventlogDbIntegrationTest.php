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

namespace SP\Tests\Modules\Web\Controllers\Eventlog;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Tests\DatabaseIntegrationTestCase;

/**
 * DB-backed HTTP integration tests for the event log area.
 */
#[Group('integration')]
final class EventlogDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_MESSAGE = 'You don\'t have permission to do this operation';

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function indexRendersFixtureEventlogRows(): void
    {
        $result = $this->dispatch('get', 'eventlog/index');

        $this->assertResponseWasDispatched($result);
        self::assertStringContainsString('Event Log', $result['body']);
        self::assertStringContainsString('tblEventLog', $result['body']);
        self::assertStringContainsString('login.session.load', $result['body']);
        self::assertStringContainsString('admin', $result['body']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function searchFiltersAndHydratesFixtureRows(): void
    {
        $result = $this->dispatch('get', 'eventlog/search', ['search' => 'admin']);

        $json = $this->assertJsonOk($result);

        self::assertNotEmpty($json['data']['html'] ?? null);
        self::assertStringContainsString('tblEventLog', $json['data']['html']);
        self::assertStringContainsString('login.session.load', $json['data']['html']);
        self::assertStringContainsString('admin', $json['data']['html']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function indexIsDeniedWithoutEventlogAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'eventlog/index',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::EVENTLOG)]
        );

        $this->assertResponseWasDispatched($result);
        self::assertStringContainsString(self::DENIED_MESSAGE, $result['body']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function searchIsDeniedWithoutEventlogSearchAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'eventlog/search',
            ['search' => 'admin'],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::EVENTLOG_SEARCH)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }
}
