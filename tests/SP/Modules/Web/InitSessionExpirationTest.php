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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Context\Session;
use SP\Domain\Core\Context\Context;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\IntegrationTestCase;

/**
 * Integration tests verifying Init::initUserSession() invalidates expired
 * in-memory session state on the current request, including on NO_SESSION_ACTIVITY
 * endpoints without extending idle lifetimes.
 */
#[Group('integration')]
#[RunTestsInSeparateProcesses]
final class InitSessionExpirationTest extends IntegrationTestCase
{
    private const SESSION_TIMEOUT = 300;
    private const USER_AGENT      = 'InitSessionExpirationTest/1.0';
    private const REMOTE_ADDR     = '127.0.0.1';

    /**
     * @return array<string, mixed>
     */
    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), [
            'getSessionTimeout' => self::SESSION_TIMEOUT,
        ]);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }

    #[Test]
    public function expiredSessionInvalidatesInMemoryContextOnCurrentRequest(): void
    {
        $this->seedPersistedSession(time() - self::SESSION_TIMEOUT - 10);
        $session = new Session();

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => 'account/index'],
                [],
                [],
                [
                    'HTTP_USER_AGENT' => self::USER_AGENT,
                    'REMOTE_ADDR' => self::REMOTE_ADDR,
                ]
            ),
            [Context::class => $session]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        self::assertFalse($session->isLoggedIn());
        self::assertFalse($session->getAuthCompleted());
        self::assertNull($session->getCSRF());

        /** @var Response $response */
        $response = $container->get(Response::class);
        $headers = $response->headers()->all();
        $location = (string)($headers['location'] ?? $headers['Location'] ?? '');

        self::assertSame(302, $response->code());
        self::assertStringContainsString('r=login', $location);
    }

    #[Test]
    public function expiredSessionIsInvalidatedOnNoSessionActivityEndpoint(): void
    {
        $csrfToken = $this->seedPersistedSession(time() - self::SESSION_TIMEOUT - 10);
        $session = new Session();

        $notificationService = self::createStub(NotificationService::class);
        $notificationService->method('getAllActiveForCurrentUser')->willReturn([]);

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => 'items/notifications'],
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                    'HTTP_X_CSRF' => $csrfToken,
                    'HTTP_USER_AGENT' => self::USER_AGENT,
                    'REMOTE_ADDR' => self::REMOTE_ADDR,
                ]
            ),
            [
                Context::class => $session,
                NotificationService::class => $notificationService,
            ]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        self::assertFalse($session->isLoggedIn());
        self::assertFalse($session->getAuthCompleted());

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(200, $response->code());

        $decoded = json_decode((string)$response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(10, $decoded['status']);
    }

    #[Test]
    public function activeSessionOnNoSessionActivityEndpointDoesNotExtendLastActivity(): void
    {
        $lastActivity = time() - 100;
        $csrfToken = $this->seedPersistedSession($lastActivity);
        $session = new Session();

        $notificationService = self::createStub(NotificationService::class);
        $notificationService->method('getAllActiveForCurrentUser')->willReturn([]);

        $container = $this->buildContainer(
            self::buildRequest(
                'get',
                'index.php',
                ['r' => 'items/notifications'],
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                    'HTTP_X_CSRF' => $csrfToken,
                    'HTTP_USER_AGENT' => self::USER_AGENT,
                    'REMOTE_ADDR' => self::REMOTE_ADDR,
                ]
            ),
            [
                Context::class => $session,
                NotificationService::class => $notificationService,
            ]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        self::assertTrue($session->isLoggedIn());
        self::assertSame($lastActivity, $session->getLastActivity());

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(200, $response->code());

        $decoded = json_decode((string)$response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $decoded['status']);
    }

    #[Test]
    public function expiredSessionOnPostRequestResetsContextBeforeCsrfCheck(): void
    {
        $this->seedPersistedSession(time() - self::SESSION_TIMEOUT - 10);
        $session = new Session();

        $container = $this->buildContainer(
            self::buildRequest(
                'post',
                'index.php',
                ['r' => 'account/saveDelete/100'],
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                    'HTTP_USER_AGENT' => self::USER_AGENT,
                    'REMOTE_ADDR' => self::REMOTE_ADDR,
                ]
            ),
            [Context::class => $session]
        );

        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }

        self::assertFalse($session->isLoggedIn());
        self::assertNull($session->getCSRF());

        /** @var Response $response */
        $response = $container->get(Response::class);
        self::assertSame(200, $response->code());

        $decoded = json_decode((string)$response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(10, $decoded['status']);
    }

    private function seedPersistedSession(int $lastActivity): string
    {
        $csrfToken = Hash::signMessage(
            sha1(sprintf('%s%s', self::USER_AGENT, self::REMOTE_ADDR)),
            $this->passwordSalt
        );

        $session = new Session();
        $session->initialize();

        $preferences = UserDataGenerator::factory()->buildUserPreferencesData();
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
            ->mutate(['preferences' => $preferences]);

        $session->setUserData($userDto);
        $session->setUserProfile($this->getUserProfile());
        $session->setAuthCompleted(true);
        $session->setSessionTimeout(self::SESSION_TIMEOUT);
        $session->setSidStartTime(time());
        $session->setLastActivity($lastActivity);
        $session->setCSRF($csrfToken);

        Session::close();

        return $csrfToken;
    }
}
