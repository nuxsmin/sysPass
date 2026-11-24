<?php
/*
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

namespace SP\Tests\Modules\Web;

use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests verifying consistent HTTP, HTML-AJAX, and JSON timeout responses
 * across ControllerBase, IndexController, and SimpleControllerBase.
 */
#[Group('integration')]
#[RunTestsInSeparateProcesses]
final class SessionTimeoutTest extends IntegrationTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'index' => ['index/index'],
            'account/index' => ['account/index'],
            'account/view' => ['account/view/1'],
            'configManager/index' => ['configManager/index'],
            'items/notifications' => ['items/notifications'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function controllerBaseAndIndexRoutesProvider(): array
    {
        return [
            'index' => ['index/index'],
            'account/index' => ['account/index'],
            'configManager/index' => ['configManager/index'],
            'items/notifications' => ['items/notifications'],
        ];
    }

    #[Test]
    #[DataProvider('protectedRoutesProvider')]
    public function unauthenticatedBrowserGetRedirectsToLogin(string $route): void
    {
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => $route]),
            [Context::class => $this->createAnonymousContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $location = $this->getLocationHeader($response);

        self::assertSame(302, $response->code());
        self::assertStringContainsString('r=login', $location);
        self::assertStringNotContainsString('from=', $location);
    }

    #[Test]
    public function incompleteAuthBrowserGetRedirectsToLogin(): void
    {
        $context = $this->createIncompleteAuthContext();

        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/index']),
            [Context::class => $context]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(302, $response->code());
        self::assertStringContainsString('r=login', $this->getLocationHeader($response));
    }

    #[Test]
    public function unauthenticatedBrowserGetWithValidSignedRoutePreservesSignedFromParam(): void
    {
        $route = 'account/view/1';
        $unsignedQuery = 'r=' . urlencode($route);
        $validHash = Hash::signMessage($unsignedQuery, $this->passwordSalt);

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => $route, 'h' => $validHash],
                [],
                [],
                ['QUERY_STRING' => $unsignedQuery . '&h=' . urlencode($validHash)]
            ),
            [Context::class => $this->createAnonymousContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $location = $this->getLocationHeader($response);

        self::assertSame(302, $response->code());
        self::assertStringContainsString('r=login', $location);
        self::assertStringContainsString('from=' . urlencode($route), $location);
        self::assertStringContainsString('&h=', $location);
    }

    #[Test]
    public function unauthenticatedBrowserGetWithTamperedSignatureStillRedirectsToUnsignedLogin(): void
    {
        $route = 'account/view/1';

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => $route, 'h' => 'tampered-signature'],
                [],
                [],
                ['QUERY_STRING' => 'r=' . urlencode($route) . '&h=tampered-signature']
            ),
            [Context::class => $this->createAnonymousContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $location = $this->getLocationHeader($response);

        self::assertSame(302, $response->code());
        self::assertStringContainsString('r=login', $location);
        self::assertStringNotContainsString('from=', $location);
    }

    #[Test]
    #[DataProvider('controllerBaseAndIndexRoutesProvider')]
    public function unauthenticatedAjaxHtmlReturnsLogoutScriptWithoutExitingAndReleasesLock(string $route): void
    {
        $lock = $this->createMock(MasterPasswordRequestLock::class);
        $lock->expects(self::once())->method('acquire');
        $lock->expects(self::once())->method('release');

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => $route],
                [],
                [],
                ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
            ),
            [
                Context::class => $this->createAnonymousContext(),
                MasterPasswordRequestLock::class => $lock,
            ]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $headers = $response->headers()->all();
        $contentType = (string)($headers['content-type'] ?? $headers['Content-type'] ?? $headers['Content-Type'] ?? '');

        self::assertSame(200, $response->code());
        self::assertStringContainsString('text/html', $contentType);
        self::assertSame('<script>sysPassApp.actions.main.logout();</script>', (string)$response->body());
    }

    #[Test]
    #[DataProvider('controllerBaseAndIndexRoutesProvider')]
    public function unauthenticatedJsonXhrReturnsStatus10(string $route): void
    {
        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => $route],
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                ]
            ),
            [Context::class => $this->createAnonymousContext()]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $headers = $response->headers()->all();
        $contentType = (string)($headers['content-type'] ?? $headers['Content-type'] ?? $headers['Content-Type'] ?? '');

        self::assertSame(200, $response->code());
        self::assertStringContainsString('application/json', $contentType);

        $decoded = json_decode((string)$response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(10, $decoded['status']);
    }

    #[Test]
    public function authenticatedIndexRendersMainLayoutWithoutRedirect(): void
    {
        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'index/index'])
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(200, $response->code());
        self::assertSame('', $this->getLocationHeader($response));
        self::assertNotEmpty((string)$response->body());
    }

    #[Test]
    public function unauthenticatedPublicLinkRemainsAccessibleWithoutRedirect(): void
    {
        $expiredLink = \SP\Tests\Generators\PublicLinkDataGenerator::factory()
            ->buildPublicLink()
            ->mutate([
                'hash' => 'abc123',
                'dateExpire' => 0,
                'countViews' => 0,
                'maxCountViews' => 1,
            ]);

        $publicLinkService = self::createStub(PublicLinkService::class);
        $publicLinkService->method('getByHash')->willReturn($expiredLink);

        $container = $this->buildContainer(
            self::buildRequest('get', 'index.php', ['r' => 'account/viewLink/abc123']),
            [
                Context::class => $this->createAnonymousContext(),
                PublicLinkService::class => $publicLinkService,
            ]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(200, $response->code());
        self::assertSame('', $this->getLocationHeader($response));
        self::assertNotEmpty((string)$response->body());
    }

    private function createAnonymousContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getUserData')->willReturn(new UserDto());

        return $context;
    }

    private function createIncompleteAuthContext(): SessionContext
    {
        $preferences = UserDataGenerator::factory()->buildUserPreferencesData();
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
            ->mutate(['preferences' => $preferences]);

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getUserData')->willReturn($userDto);
        $context->method('getUserProfile')->willReturn($this->getUserProfile());

        return $context;
    }

    private function getLocationHeader(Response $response): string
    {
        $headers = $response->headers()->all();

        return (string)($headers['location'] ?? $headers['Location'] ?? '');
    }
}
