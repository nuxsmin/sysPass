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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Tests\IntegrationTestCase;
use Throwable;

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

    #[Test]
    public function checkReleaseHandlesConnectionError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitionsFor(
                new ConnectException(
                    'Connection refused',
                    new GuzzleRequest('GET', AppInfoInterface::APP_UPDATES_URL)
                )
            )
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
    }

    #[Test]
    public function checkReleaseHandlesHttpError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitionsFor(
                new ClientException(
                    'Not Found',
                    new GuzzleRequest('GET', AppInfoInterface::APP_UPDATES_URL),
                    new GuzzleResponse(404)
                )
            )
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
    }

    #[Test]
    public function checkReleaseHandlesNonJsonResponse(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitionsFor(
                new GuzzleResponse(200, ['content-type' => 'text/html'], '<html></html>')
            )
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
    }

    #[Test]
    public function checkNoticesHandlesHttpError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkNotices']),
            $this->definitionsFor(
                new ConnectException(
                    'Connection refused',
                    new GuzzleRequest('GET', AppInfoInterface::APP_NOTICES_URL)
                )
            )
        );

        $response = $container->get(CheckNoticesController::class)->checkNoticesAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
    }

    #[Test]
    public function checkReleaseThroughRealGuzzleHandlerStack(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitionsWithClient(
                $this->guzzleClient(
                    new GuzzleResponse(
                        200,
                        ['content-type' => 'application/json'],
                        json_encode(
                            [
                                'tag_name' => 'v5.2.10.23010101',
                                'html_url' => 'https://example.com/release',
                                'name' => 'Release',
                                'body' => 'Release body',
                                'published_at' => '2023-01-01',
                            ],
                            JSON_THROW_ON_ERROR
                        )
                    )
                )
            )
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::OK, $response->status);
        self::assertSame('v5.2.10.23010101', $response->extra['version']);
    }

    #[Test]
    public function checkReleaseHandlesRealGuzzleHttpError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
            $this->definitionsWithClient($this->guzzleClient(new GuzzleResponse(404)))
        );

        $response = $container->get(StatusController::class)->statusAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function definitions(mixed $payload): array
    {
        return $this->definitionsFor(
            new GuzzleResponse(
                200,
                ['content-type' => 'application/json'],
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function definitionsFor(ResponseInterface|Throwable $result): array
    {
        $client = $this->createStub(ClientInterface::class);

        if ($result instanceof Throwable) {
            $client->method('request')->willThrowException($result);
        } else {
            $client->method('request')->willReturn($result);
        }

        return $this->definitionsWithClient($client);
    }

    private function guzzleClient(ResponseInterface $response): ClientInterface
    {
        return new GuzzleClient(
            ['handler' => HandlerStack::create(new MockHandler([$response]))]
        );
    }

    private function definitionsWithClient(ClientInterface $client): array
    {
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
