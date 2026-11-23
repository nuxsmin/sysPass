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
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Tests\IntegrationTestCase;
use Throwable;

/**
 * Integration tests for the status area.
 */
#[Group('integration')]
final class StatusTest extends IntegrationTestCase
{
    private bool $isAdminApp = true;

    #[Test]
    public function checkReleaseRouteReturnsAnUpdateEnvelope(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitions(
                [
                    'tag_name' => 'v5.2.10.23010101',
                    'html_url' => 'https://github.com/nuxsmin/sysPass/releases/tag/v5.2.10.23010101',
                    'name' => 'Release',
                    'body' => 'Release body',
                    'published_at' => '2023-01-01',
                ]
            )
        );

        ob_start();
        IntegrationTestCase::runApp($container);
        $output = ob_get_clean();
        $response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(ResponseStatus::OK->name, $response['status']);
        self::assertSame('v5.2.10.23010101', $response['data']['version']);
        self::assertSame(
            'https://github.com/nuxsmin/sysPass/releases/tag/v5.2.10.23010101',
            $response['data']['url']
        );
    }

    #[Test]
    public function checkReleaseRejectsNonAdminUsers(): void
    {
        $this->isAdminApp = false;
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitions(
                [
                    'tag_name' => 'v5.2.10.23010101',
                    'html_url' => 'https://github.com/nuxsmin/sysPass/releases/tag/latest',
                ]
            )
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::ERROR, $response->status);
        self::assertNull($response->extra);
    }

    #[Test]
    public function checkReleaseRejectsNonGitHubReleaseUrls(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitions(
                [
                    'tag_name' => 'v5.2.10.23010101',
                    'html_url' => 'https://example.com/release',
                    'name' => 'Release',
                    'body' => 'Release body',
                ]
            )
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::WARNING, $response->status);
        self::assertNull($response->extra);
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitionsFor(
                new ConnectException(
                    'Connection refused',
                    new GuzzleRequest('GET', AppInfoInterface::APP_UPDATES_URL)
                )
            )
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::WARNING, $response->status);
        self::assertNull($response->extra);
    }

    #[Test]
    public function checkReleaseHandlesHttpError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitionsFor(
                new ClientException(
                    'Not Found',
                    new GuzzleRequest('GET', AppInfoInterface::APP_UPDATES_URL),
                    new GuzzleResponse(404)
                )
            )
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::WARNING, $response->status);
        self::assertNull($response->extra);
    }

    #[Test]
    public function checkReleaseHandlesNonJsonResponse(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitionsFor(
                new GuzzleResponse(200, ['content-type' => 'text/html'], '<html></html>')
            )
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::WARNING, $response->status);
        self::assertNull($response->extra);
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
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitionsWithClient(
                $this->guzzleClient(
                    new GuzzleResponse(
                        200,
                        ['content-type' => 'application/json'],
                        json_encode(
                            [
                                'tag_name' => 'v5.2.10.23010101',
                                'html_url' => 'https://github.com/nuxsmin/sysPass/releases/tag/v5.2.10.23010101',
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

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::OK, $response->status);
        self::assertSame('v5.2.10.23010101', $response->extra['version']);
    }

    #[Test]
    public function checkReleaseHandlesRealGuzzleHttpError(): void
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitionsWithClient($this->guzzleClient(new GuzzleResponse(404)))
        );

        $response = $container->get(CheckReleaseController::class)->checkReleaseAction();

        self::assertSame(ResponseStatus::WARNING, $response->status);
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

    protected function getUserDataDto(): UserDto
    {
        return parent::getUserDataDto()->mutate(['isAdminApp' => $this->isAdminApp]);
    }

    #[Test]
    public function dispatchedAnonymousIncompleteAndNonAdminAreDeniedCheckNoticesAndStatusIsRetired(): void
    {
        $anonCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $anonCtx->method('isLoggedIn')->willReturn(false);
        $anonCtx->method('getAuthCompleted')->willReturn(false);
        $anonCtx->method('getUserData')->willReturn(new UserDto());

        $incompleteCtx = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $incompleteCtx->method('isLoggedIn')->willReturn(true);
        $incompleteCtx->method('getAuthCompleted')->willReturn(false);
        $incompleteCtx->method('getUserData')->willReturn(
            parent::getUserDataDto()->mutate(['isAdminApp' => true])
        );

        $this->isAdminApp = false;

        $demoConfigData = self::createConfiguredStub(
            \SP\Domain\Config\Ports\ConfigDataInterface::class,
            array_merge($this->getConfigData(), ['isDemoEnabled' => true])
        );
        $demoConfigService = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
        $demoConfigService->method('getConfigData')->willReturn($demoConfigData);

        $actors = [
            'anonymous' => [
                'overrides' => [\SP\Domain\Core\Context\Context::class => $anonCtx],
                'kind' => 'unauthenticated',
            ],
            'incomplete' => [
                'overrides' => [\SP\Domain\Core\Context\Context::class => $incompleteCtx],
                'kind' => 'unauthenticated',
            ],
            'non-admin' => [
                'overrides' => [],
                'kind' => 'non_admin',
            ],
            'non-admin-demo' => [
                'overrides' => [
                    \SP\Domain\Config\Ports\ConfigFileService::class => $demoConfigService,
                    \SP\Domain\Config\Ports\ConfigDataInterface::class => $demoConfigData,
                ],
                'kind' => 'non_admin',
            ],
        ];

        foreach ($actors as $label => $actor) {
            $actorOverrides = $actor['overrides'];

            // 1. status/checkNotices blocks before any outbound HTTP request
            $noticesClient = $this->createMock(ClientInterface::class);
            $noticesClient->expects(self::never())->method('request');

            if ($actor['kind'] === 'unauthenticated') {
                $cNoticesJson = $this->buildContainer(
                    IntegrationTestCase::buildRequest(
                        'get',
                        'index.php',
                        ['r' => 'status/checkNotices'],
                        [],
                        [],
                        ['HTTP_ACCEPT' => 'application/json']
                    ),
                    $this->definitionsWithClient($noticesClient) + $actorOverrides
                );
                ob_start();
                try {
                    IntegrationTestCase::runApp($cNoticesJson);
                } finally {
                    $noticesOut = (string) ob_get_clean();
                }
                $noticesBody = (string) $cNoticesJson->get(\Klein\Response::class)->body();
                $decodedNotices = json_decode($noticesBody, true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(10, $decodedNotices['status'], $label);
                self::assertSame($noticesBody, $noticesOut, $label);
            } else {
                $cNoticesNonAdmin = $this->buildContainer(
                    IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkNotices']),
                    $this->definitionsWithClient($noticesClient) + $actorOverrides
                );
                ob_start();
                try {
                    IntegrationTestCase::runApp($cNoticesNonAdmin);
                } finally {
                    $noticesOut = (string) ob_get_clean();
                }
                $decodedNotices = json_decode($noticesOut, true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(ResponseStatus::ERROR->name, $decodedNotices['status'], $label);
                self::assertNull($decodedNotices['data'], $label);
            }

            // 2. Legacy status/status is retired (404, zero outbound HTTP)
            $statusClient = $this->createMock(ClientInterface::class);
            $statusClient->expects(self::never())->method('request');

            $cStatus = $this->buildContainer(
                IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/status']),
                $this->definitionsWithClient($statusClient) + $actorOverrides
            );
            ob_start();
            try {
                IntegrationTestCase::runApp($cStatus);
            } finally {
                ob_end_clean();
            }
            self::assertSame(404, $cStatus->get(\Klein\Response::class)->code(), $label);

            // 3. status/checkRelease blocks before making any outbound HTTP request.
            $releaseClient = $this->createMock(ClientInterface::class);
            $releaseClient->expects(self::never())->method('request');

            $cReleaseNoAccept = $this->buildContainer(
                IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
                $this->definitionsWithClient($releaseClient) + $actorOverrides
            );
            if ($actor['kind'] === 'unauthenticated') {
                ob_start();
                try {
                    IntegrationTestCase::runApp($cReleaseNoAccept);
                } finally {
                    ob_end_clean();
                }
                /** @var \Klein\Response $releaseNoAcceptResp */
                $releaseNoAcceptResp = $cReleaseNoAccept->get(\Klein\Response::class);
                $releaseHeaders = $releaseNoAcceptResp->headers()->all();
                self::assertSame(302, $releaseNoAcceptResp->code(), $label);
                self::assertStringContainsString(
                    'r=login',
                    (string) ($releaseHeaders['location'] ?? $releaseHeaders['Location'] ?? ''),
                    $label
                );

                // With Accept: */*, checks() sends nothing and throws SessionTimeout (empty 200)
                $releaseClientStar = $this->createMock(ClientInterface::class);
                $releaseClientStar->expects(self::never())->method('request');
                $cReleaseStar = $this->buildContainer(
                    IntegrationTestCase::buildRequest(
                        'get',
                        'index.php',
                        ['r' => 'status/checkRelease'],
                        [],
                        [],
                        ['HTTP_ACCEPT' => '*/*']
                    ),
                    $this->definitionsWithClient($releaseClientStar) + $actorOverrides
                );
                ob_start();
                try {
                    IntegrationTestCase::runApp($cReleaseStar);
                } finally {
                    $starOut = (string) ob_get_clean();
                }
                self::assertSame('', $starOut, $label);

                // With Accept: application/json, the timeout response is sent once.
                $releaseClientJson = $this->createMock(ClientInterface::class);
                $releaseClientJson->expects(self::never())->method('request');
                $cReleaseJson = $this->buildContainer(
                    IntegrationTestCase::buildRequest(
                        'get',
                        'index.php',
                        ['r' => 'status/checkRelease'],
                        [],
                        [],
                        ['HTTP_ACCEPT' => 'application/json']
                    ),
                    $this->definitionsWithClient($releaseClientJson) + $actorOverrides
                );
                ob_start();
                try {
                    IntegrationTestCase::runApp($cReleaseJson);
                } finally {
                    $jsonOut = (string) ob_get_clean();
                }
                $responseBody = (string) $cReleaseJson->get(\Klein\Response::class)->body();
                $jsonDecoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(10, $jsonDecoded['status'], $label);
                self::assertSame($responseBody, $jsonOut, $label);
            } else {
                ob_start();
                try {
                    IntegrationTestCase::runApp($cReleaseNoAccept);
                } finally {
                    $releaseOut = (string) ob_get_clean();
                }
                $decodedRelease = json_decode($releaseOut, true, 512, JSON_THROW_ON_ERROR);
                self::assertSame(ResponseStatus::ERROR->name, $decodedRelease['status'], $label);
                self::assertNull($decodedRelease['data'], $label);
            }
        }
    }

    #[Test]
    public function dispatchedCheckReleaseAndNoticesReturnSafeEnvelopesOnMalformedResponses(): void
    {
        $this->isAdminApp = true;

        $malformedRelease = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkRelease']),
            $this->definitions(['unexpected' => 'fields'])
        );
        ob_start();
        try {
            IntegrationTestCase::runApp($malformedRelease);
        } finally {
            $relOut = (string) ob_get_clean();
        }
        $relDecoded = json_decode($relOut, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(ResponseStatus::WARNING->name, $relDecoded['status']);
        self::assertNull($relDecoded['data']);

        $malformedNoticesHtml = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkNotices']),
            $this->definitionsFor(new GuzzleResponse(200, ['content-type' => 'text/html'], '<html>oops</html>'))
        );
        ob_start();
        try {
            IntegrationTestCase::runApp($malformedNoticesHtml);
        } finally {
            $notOut = (string) ob_get_clean();
        }
        $notDecoded = json_decode($notOut, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(ResponseStatus::ERROR->name, $notDecoded['status']);
        self::assertNull($notDecoded['data']);

        $malformedNoticesJson = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'status/checkNotices']),
            $this->definitions(['unexpected' => 'fields'])
        );
        ob_start();
        try {
            IntegrationTestCase::runApp($malformedNoticesJson);
        } finally {
            $notJsonOut = (string) ob_get_clean();
        }
        $notJsonDecoded = json_decode($notJsonOut, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(ResponseStatus::ERROR->name, $notJsonDecoded['status']);
        self::assertNull($notJsonDecoded['data']);
    }
}
