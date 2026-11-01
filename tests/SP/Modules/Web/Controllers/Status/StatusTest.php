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

namespace SP\Modules\Web\Controllers\Status;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests for the status area.
 */
#[Group('integration')]
final class StatusTest extends IntegrationTestCase
{
    #[Test]
    public function checkRelease(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitions(
                [
                    'tag_name' => 'v5.2.10.23010101',
                    'html_url' => 'https://example.com/release',
                    'name' => 'Release',
                    'body' => 'Release body',
                    'published_at' => '2023-01-01',
                ]
            )
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::OK, $response->status);
        self::assertSame('v5.2.10.23010101', $response->extra['version']);
    }

    #[Test]
    public function checkNotices(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkNotices']),
            $this->definitions(
                [
                    [
                        'title' => 'Notice',
                        'created_at' => '2023-01-01',
                        'body' => 'Notice body',
                    ],
                ]
            )
        );

        $response = $container->get(CheckNoticesController::class)->checkNoticesAction();

        self::assertSame(ResponseStatus::OK, $response->status);
        self::assertSame('Notice', $response->extra[0]['title']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function definitions(mixed $payload): array
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('request')->willReturn(
            new GuzzleResponse(
                200,
                ['content-type' => 'application/json'],
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );

        $extensionChecker = new class implements PhpExtensionCheckerService {
            public function checkIsAvailable(string $extension, bool $exception = false): bool
            {
                return true;
            }

            public function checkMandatory(): void
            {
            }

            public function getMissing(): array
            {
                return [];
            }

            public function __call(string $name, array $arguments): bool
            {
                return true;
            }
        };

        return [
            ClientInterface::class => $client,
            PhpExtensionCheckerService::class => $extensionChecker,
        ];
    }
}
