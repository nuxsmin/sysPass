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

namespace SP\Tests\Modules\Web\Controllers\Login;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\LoginStatus;
use SP\Tests\BodyChecker;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Characterisation tests for the Login web area.
 */
#[Group('integration')]
final class LoginTest extends IntegrationTestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[BodyChecker('outputCheckerIndex')]
    public function index(): void
    {
        $this->runRoute('get', 'login/index');
    }

    #[Test]
    #[BodyChecker('outputCheckerLogin')]
    public function login(): void
    {
        $loginService = $this->createStub(LoginService::class);
        $loginService->method('doLogin')
                     ->willReturn(new LoginResponseDto(LoginStatus::OK, 'index.php'));

        $this->runRoute(
            'post',
            'login/login',
            [],
            ['user' => 'admin', 'pass' => 'secret'],
            [LoginService::class => $loginService]
        );
    }

    #[Test]
    #[BodyChecker('outputCheckerWrongLogin')]
    public function wrongLogin(): void
    {
        $loginService = $this->createStub(LoginService::class);
        $loginService->method('doLogin')
                     ->willThrowException(
                         AuthException::info('Wrong login', __FUNCTION__, LoginStatus::INVALID_LOGIN->value)
                     );

        $this->runRoute(
            'post',
            'login/login',
            [],
            ['user' => 'admin', 'pass' => 'wrong'],
            [LoginService::class => $loginService]
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[BodyChecker('outputCheckerLogout')]
    public function logout(): void
    {
        $this->runRoute('get', 'login/logout');
    }

    private function outputCheckerIndex(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//form[@id="frmLogin"]'));
    }

    private function outputCheckerLogin(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('OK', $json['status']);
        self::assertArrayHasKey('url', $json['data']);
    }

    private function outputCheckerWrongLogin(string $output): void
    {
        $json = json_decode($output, true);

        self::assertIsArray($json, $output);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Wrong login', $json['description']);
    }

    private function outputCheckerLogout(string $output): void
    {
        $crawler = new Crawler($output);

        self::assertCount(1, $crawler->filterXPath('//div[@id="box-pub-noheader"]'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runRoute(
        string $method,
        string $route,
        array $paramsGet = [],
        array $paramsPost = [],
        array $definitions = []
    ): void {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest($method, 'index.php', ['r' => $route] + $paramsGet, $paramsPost),
            $definitions
        );

        IntegrationTestCase::runApp($container);
    }
}
